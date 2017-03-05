<?php

	use \StangeMercadoPago\Components\MercadoPago\PaymentResponse;
	use \StangeMercadoPago\Components\MercadoPago\MercadoPagoService;

	class Shopware_Controllers_Frontend_MercadoPago extends Shopware_Controllers_Frontend_Payment{

		private	$plugin	=	NULL;

		public function preDispatch(){

			/**
			 * @var \Shopware\Components\Plugin $plugin 
			 */

			$this->plugin = $this->get('kernel')->getPlugins()['StangeMercadoPago'];

			$this->get('template')
			->addTemplateDir(
									sprintf(
												'%s/Resources/views',
												$this->plugin->getPath()
									)
			);

		}

		public function indexAction(){

			$uiMode	=	strtolower($this->plugin->getConfig('uimode'));

			switch($uiMode){
				case 'popup':
				case 'blank':
				case 'redirect':
				case 'modal':
					$action	=	'javascript';
				break;

				case 'iframe':
				case 'phpredirect':
					$action	=	$uiMode;
				break;

			}

			switch($this->getPaymentShortName()){

				case 'stange_mercadopago_checkout':

					return $this->redirect([
													'action'			=>	$action,
													'forceSecure'	=>	TRUE
					]);

				break; //Not needed but this is what anyone would expect for code comprehension

				default:

					return $this->redirect(['controller'	=>	'checkout']);

				break; //Not needed but this is what anyone would expect for code comprehension

			}

		}

		/**
		 * Gateway action method.
		 *
		 * Collects the payment information and transmit it to the payment provider.
		 */

		public function javascriptAction(){

			$mpPreference	=	$this->getProviderUrl();
			$checkoutMode	=	strtolower($this->plugin->getConfig('checkoutmode'));
			$uiMode			=	strtolower($this->plugin->getConfig('uimode'));

			$url				=	$checkoutMode == 'prod' ? $mpPreference['response']['init_point'] : 
									$mpPreference['response']['sandbox_init_point'];

			$this->View()->assign('jsMode',$uiMode);
			$this->View()->assign('gatewayUrl',$url);

		}

		public function phpRedirectAction(){

			$mpPreference	=	$this->getProviderUrl();
			$checkoutMode	=	strtolower($this->plugin->getConfig('checkoutmode'));

			$url				=	$checkoutMode == 'prod' ? $mpPreference['init_point'] : 
									$mpPreference['sandbox_init_point'];

			$this->redirect($url);

		}

		public function iframeAction(){

			$mpPreference	=	$this->getProviderUrl();
			$checkoutMode	=	strtolower($this->plugin->getConfig('checkoutmode'));

			$url				=	$checkoutMode == 'prod' ? $mpPreference['init_point'] : 
									$mpPreference['sandbox_init_point'];

			$this->View()->assign('gatewayUrl',$url);

		}

		public function returnAction(){

			/**
			 * @var MercadoPagoPaymentService $service
			 */

			$service	=	$this->getService();
			$user		=	$this->getUser();
			$billing	=	$user['billingaddress'];

			/**
			 * @var PaymentResponse $response
			 */

			$response	=	$service->createPaymentResponse($this->Request());
			$token		=	$service->createPaymentToken($this->getAmount(), $billing['customernumber']);

			if(!$service->isValidToken($response, $token)){

				$this->forward('cancel');
				return;

			}

			switch ($response->status){

				case 'accepted':

					$this->saveOrder(
											$response->transactionId,
											$response->token,
											self::PAYMENTSTATUSPAID
					);


					$this->redirect(['controller' => 'checkout', 'action' => 'finish']);

             break;

            default:
                $this->forward('cancel');
				break;

			}

		}

		/**
		 * Cancel action method
		 */

		public function cancelAction(){

		}

		private function getService($name=NULL){

			return $this->container->get('stange_mercadopago_checkout.mercado_pago');

		}

		/**
		 * Creates the url parameters
		 */

		private function getUrlParameters(){

			/**
			 * @var MercadoPagoPaymentService $service 
			 */

			$service	=	$this->getService();
			$router	=	$this->Front()->Router();
			$user		=	$this->getUser();
			$billing	=	$user['billingaddress'];

			$parameter = [
								'amount'		=>	$this->getAmount(),
								'currency'	=>	$this->getCurrencyShortName(),
								'firstName'	=>	$billing['firstname'],
								'lastName'	=>	$billing['lastname'],
								'returnUrl'	=>	$router->assemble(['action' => 'return', 'forceSecure' => true]),
								'cancelUrl'	=>	$router->assemble(['action' => 'cancel', 'forceSecure' => true]),
								'token'		=>	$service->createPaymentToken($this->getAmount(), $billing['customernumber'])
			];

        return '?' . http_build_query($parameter);

		}

		private function basketToMpItems(){

			$basket			=	$this->getBasket();;
			$content			=	$basket['content'];
			$currency		=	$basket['sCurrencyName'];
			$appCurrency	=	$this->plugin->getConfig('currency');

			$rate				=	$appCurrency !== $currency ? 
									$this->plugin->convertRate($currency,$appCurrency) : 1;

			$items		=	[];

			foreach($content as $article){

				$price	=	$rate * floatval(number_format($article['price'],2,'.',''));

				$items[]	=	[
									"title"			=>	$article['articlename'],
									"quantity"		=>	(int)$article['quantity'],
									"currency_id"	=>	$appCurrency,
									"unit_price"	=>	$price
				];
				
			}

			return $items;

		}

		/**
		 * Returns the URL of the payment provider.
		 *
		 * @return string
		 */

		protected function getProviderUrl($params){

			$mp	=	new MP(
								$this->plugin->getConfig('clientid'),
								$this->plugin->getConfig('clientsecret')
			);

			$preference	=	Array(
										'items'	=>	$this->basketToMpItems()
			);

			return $mp->create_preference($preference);

		}

	}
