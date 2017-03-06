<?php

	use \StangeMercadoPago\Components\MercadoPago\PaymentResponse;
	use \StangeMercadoPago\Components\MercadoPago\MercadoPagoService;

	class Shopware_Controllers_Frontend_MercadoPago extends Shopware_Controllers_Frontend_Payment{

		private	$plugin	=	NULL;

		private	$rate		=	NULL;

		public function preDispatch(){

			//In case the basket is empty, redirect to the DocumentRoot

			if(empty($this->getBasket())){

				$this->redirect('/');

			}

			/**
			 * @var \Shopware\Components\Plugin $plugin 
			 */

			$this->plugin = $this->get('kernel')->getPlugins()['StangeMercadoPago'];

			/**
			 * There must be a better way to handle this. As of now "it works" (tm)
			 */

			$this->plugin->requireAutoloader();

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

			$url				=	$checkoutMode == 'prod' ? $mpPreference['response']['init_point'] : 
									$mpPreference['response']['sandbox_init_point'];

			$this->redirect($url);

		}

		public function iframeAction(){

			$mpPreference	=	$this->getProviderUrl();
			$checkoutMode	=	strtolower($this->plugin->getConfig('checkoutmode'));

			$url				=	$checkoutMode == 'prod' ? $mpPreference['response']['init_point'] : 
									$mpPreference['response']['sandbox_init_point'];

			$this->View()->assign('gatewayUrl',$url);

		}

		/**
		 * @TODO Instant payment notifications
		 */

		public function ipnAction(){

		}

		/**
		 * Cancel action method
		 */

		public function cancelAction(){

		}

		private function getService($name=NULL){

			return $this->container->get('stange_mercadopago_checkout.mercado_pago');

		}

		/******************************************************************************/
		
		/** 
		 * Conversion / Helper methods
		 *
		 * @TODO Move these to another class, perhaps to the service class?
		 *
		 */

		/**
		 * Convert ShopWare customer data to MercadoPago customer expected structure
		 *
		 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
		 * @return Array An array containing customer data in MP format.
		 */

		private function customerDataToMp(){

			$customer	=	$this->getUser()['additional']['user'];

			/**
			 * The phone field needs to be more specific for MercadoPago
			 * Maybe: Use google's libphonenumber to parse the phone
			 */

			//'phone'		=>	$customer['shippingaddress']['phone'],

			return [
						'name'		=>	$customer['firstname'],
						'surname'	=>	$customer['lastname'],
						'email'		=>	$customer['email']
			];

		}

		/**
		 * Convert ShopWare customer shipping address to MercadoPago shippments format
		 *
		 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
		 * @return Array An array containing shippment data in MP format.
		 */

		private function shipmentsToMp(){

			$cost			=	floatval($this->getShipment()) * $this->rate;

			$swAddress	=	$this->getUser()['shippingaddress'];

			$street		=	trim(preg_replace('/[0-9]/','',$swAddress['street']));
			$number		=	explode(' ',$swAddress['street']);
			$num			=	0;

			foreach($number as $num){

				if(preg_match('/[0-9]+/',$num)){

					$num	=	(int)$num;
					break;

				}

			}

			$address		=	[
									'zip_code'			=>	$swAddress['zipcode'],
									'street_name'		=>	$street,
									'street_number'	=>	$num,
									'floor'				=>	'',		//@TODO floor parsing
									'apartment'			=>	'',		//@TODO apartment parsing
			];

			$mpShipment	=	[
									'mode'					=>	'custom',
									'cost'					=>	$cost
			];

			if(!$cost){

				$mpShipment['free_shipping']	=	TRUE;

			}

			$mpShipment['receiver_address']	=	$address;

			return $mpShipment;

		}

		/**
		 * Parse Shopware Basket items to MercadoPago items structure
		 *
		 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
		 * @return Array An array containing items data in MP format.
		 */

		private function basketToMpItems(){

			$basket			=	$this->getBasket();
			$content			=	$basket['content'];
			$currency		=	$basket['sCurrencyName'];
			$appCurrency	=	$this->plugin->getConfig('currency');

			$rate				=	$appCurrency !== $currency ? 
									$this->plugin->convertRate($currency,$appCurrency) : 1;

			/**
			 * Set the rate in the rate class attribute so other methods can make use of said rate
			 * (i.e: The shipping method)
			 */

			$this->rate		=	$rate;

			$items		=	[];

			foreach($content as $article){

				$price	=	$this->rate * floatval(number_format($article['price'],2,'.',''));

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
										'items'		=>	$this->basketToMpItems(),
										'payer'		=>	$this->customerDataToMp(),
										'shipments'	=>	$this->shipmentsToMp()

			);

			return $mp->create_preference($preference);

		}

	}
