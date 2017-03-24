<?php

	/**
	 * This plugin enables the usage of the MercadoPago API for MercadoLibre in ShopWare
	 *
	 * @license MIT
	 * @author Federico Stange
	 */

	/**
	 * As MercadoPago does not returns a CSRF token on basic checkout, we have to white list the IPN action 
	 * We do this in order to receive IPN's (Instant Payment Notifications) from their servers.
	 * If we don't white list said action a CSRF token exception will be thrown.
	 */

	use Shopware\Components\CSRFWhitelistAware;

	use StangeMercadoPago\Controller\Base				as	BaseController;
	use StangeMercadoPago\Components\Payment\Base	as	BasePayment;

	class Shopware_Controllers_Frontend_MercadoPagoBasic extends \Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware{

		/**
		 * Small shortcut to access the plugin throughout the controller
		 * @var \Shopware\Components\Plugin $plugin 
		 */

		private	$plugin		=	NULL;

		public function preDispatch(){

			/**
			 * @var \Shopware\Components\Plugin $plugin 
			 */

			$this->plugin = $this->get('kernel')->getPlugins()['StangeMercadoPago'];

			$this->plugin->registerMyComponents();

			$this->get('template')
			->addTemplateDir(
									sprintf(
												'%s/Resources/views',
												$this->plugin->getPath()
									)
			);

		}

		/**
		 * Return a list of white listed (non-csrf protected) actions
		 *
		 * @see \Shopware\Components\CSRFWhitelistAware
		 * @see self::ipnAction
		 */

		public function getWhitelistedCSRFActions(){

			return [
						'ipn'
			];

		}

		/**
		 * The index action used to choose the UI mode for the end user, but since most UI modes
		 * from mercadopago are unstable, the UI mode option has been removed in this version
		 * in favor of redirecting *directly* to mercadopago.
		 */

		public function indexAction(){

			/** In case the basket is empty, redirect to checkout **/

			if(empty($this->getBasket())){

				return $this->redirect(['controller' => 'index']);

			}

			/** 
			 * Save the order before going through mercadopago, set
			 * the order in "In progress" status.
			 */

			$paymentId		=	$this->createPaymentUniqueId();

			$this->saveOrder(
									$paymentId,
									$paymentId,
									BasePayment::PAYMENTSTATUSOPEN
			);

			$basket	=	$this->getBasket();

			$this->redirect(
									$this->getCheckoutUrl(
																	$paymentId,
																	$basket['sCurrencyName']
									)
			);

		}

		/**
		 * Creates and returns the basic checkout URL through the basic checkout service
		 *
		 * @return string A MercadoPago basic checkout URL.
		 */

		private function getCheckoutUrl($paymentId,$storeCurrency){

			/** 
			 * Set the STORE_CURRENCY parameter in the configuration object
			 * This parameter is dynamic since the end user is the one who selects 
			 * the currency to be used, ergo, it is not configurable through the backend 
			 * and we have to add it to the configuration object.
			 */
			
			$this->get('config')['STORE_CURRENCY']	=	$storeCurrency;
			$this->get('config')['CURRENCY']			=	$this->plugin->getConfig('CURRENCY');

			$service	=	$this->getService();

			$router	=	$this->Front()->Router();

			$service->setIPNUrl($router->assemble(['action'=>'ipn','forceSecure'=>FALSE]));

			$service->setSuccessUrl($router->assemble(['action'=>'success','forceSecure'=>TRUE]));
			$service->setCancelUrl($router->assemble(['action'=>'cancel','forceSecure'=>TRUE]));
			$service->setPendingUrl($router->assemble(['action'=>'pending','forceSecure'=>TRUE]));

			$service->setPaymentId($paymentId);
			$service->setStoreCurrency($storeCurrency);

			$url	=	$service->getCheckoutUrl([
				'items'		=>	$this->getBasket()['content'],
				'customer'	=>	$this->getUser()['additional']['user'],
				'shipment'	=>	[
										'price'		=>	$this->getShipment(),
										'address'	=>	$this->getUser()['shippingaddress']
				]
			]);

			return $url;

		}

		/**
		 * IMPORTANT:
		 *------------------------------------------------------------------
		 * It is not safe to do any kind of processing on success, failure
		 * or pending payment, the user could close the payment window
		 * before the redirect from mercadopago (which takes 5 seconds)
		 * takes place, for this reason we don't pass tokens and handle the 
		 * payment status update from the ipn action.
		 */

		public function successAction(){

			$response	=	$this->getService()
			->createPaymentResponse($this->Request());

			$this->assignTranslationToView();

		}

		public function cancelAction(){

			$response	=	$this->getService()
			->createPaymentResponse($this->Request());
			$this->assignTranslationToView();

		}

		public function pendingAction(){

			$response	=	$this->getService()
			->createPaymentResponse($this->Request());
			$this->assignTranslationToView();

		}

		private function assignTranslationToView(){

			$locale	=	$this->get('locale');
			$locale	=	substr($locale,0,strpos($locale,'_'));
			$locale	=	sprintf(
										'%s/Resources/locale/%s.ini',
										$this->plugin->getPath(),
										$locale
			);

			if(!file_exists($locale)){

				$msg	=	"Locale $locale not found for StangeMercadoPago plugin";
				throw new \Exception($msg);

			}

			foreach(parse_ini_file($locale) as $key=>$value){

				$this->View()->assign($key,$value);

			}

		}

		/**
		 * Instant payment notifications are notifications sent by MercadoPago
		 * when the customer has pressed the "pay" button in the mercadopago site.
		 *
		 * This action creates an IPN Payment Response from an enlight request
		 * and proceeds to update the customer order to the result obtained
		 * from the mercadopago API (which is mapped to Shopware order statuses).
		 *
		 * NOTE: In this version it is not necessary to configure the IPN url through
		 * the mercadopago backend, the URL is automatically generated on checkout.
		 */

		public function ipnAction(){

			try{

				/** 
				 * We are only interested in the payment topic, by default
				 * mercadopago will send both merchant_order and payment.
				 */

				if($this->Request()->get('topic') == 'merchant_order'){

					return;

				}

				$pid			=	$this->Request()->get('pid');

				$response	=	$this->getService()
				->createIPNResponse($this->Request());

				$this->savePaymentStatus($pid,$pid,$response->getStatus());

			}catch(\Exception $e){

				throw new \Exception($e->getMessage());

			}

		}

		/**
		 * Little private helper for returning the basic checkout service.
		 *
		 * @return \StangeMercadoPago\Components\Service\Basic
		 */

		private function getService(){

			return $this->container->get('basic_checkout');

		}

	}
