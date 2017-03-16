<?php

	/**
	 * This plugin enables the usage of the MercadoPago API for MercadoLibre in ShopWare
	 * @license MIT
	 * @author Federico Stange
	 */

	/**
	 * As MercadoPago does not returns a CSRF token on basic checkout, we have to white list the IPN action 
	 * We do this in order to receive IPN's (Instant Payment Notifications) from their servers.
	 */

	use Shopware\Components\CSRFWhitelistAware as WhiteList;

	use \StangeMercadoPago\Controller\Base	as	BaseController;

	class Shopware_Controllers_Frontend_MercadoPagoBasic extends BaseController implements WhiteList{

		/**
		 * Return a list of white listed (non-csrf protected) actions
		 *
		 * @see \Shopware\Components\CSRFWhitelistAware
		 * @see self::ipnAction
		 */

		public function getWhiteListedCSRFActions(){

			return [
						'ipn'
			];

		}

		/**
		 * The index action acts as a simple entry point to redirect 
		 * to the configured ui mode in the backend.
		 */

		public function indexAction(){

			$basicCheckoutAction	=	$this->getActionForUIMode($this->getPlugin()->getConfig('uimode'));

			return $this->redirect([
											'action'			=>	strtolower($basicCheckoutAction),
											'forceSecure'	=>	TRUE
			]);

		}

		/**
		 * Determines the basic checkout action according to the configured ui mode
		 *
		 * @throws \InvalidArgumentException if the ui mode is not recognized.
		 * @return string The action to redirect the client to. 
		 */

		private function getActionForUiMode($uiMode){

			/**
			 * According to which uimode was selected decide what is the action to be executed
			 */

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

				default:
					throw new \InvalidArgumentException("Invalid ui mode \"$uiMode\"");
				break;

			}

			return $action;

		}

		/**
		 * Creates and returns the basic checkout URL through the basic checkout service
		 *
		 * @return string A MercadoPago basic checkout URL.
		 */

		private function getCheckoutUrl(){

			return $this->getService()
			->getCheckoutUrl(
									Array(
											'items'		=>	$this->getBasket(),
											'payer'		=>	$this->getUser()['additional']['user'],
											'shipment'	=>	[
																	'price'		=>	$this->getShipment(),
																	'address'	=>	$this->getUser()['shippingaddress']
											]

									)
			);

		}

		/**
		 * The javascript action takes in several ways of displaying itself.
		 *
		 * Said ways can be one of the following:
		 *
		 * redirect	: redirects to the mercadopago checkout process leaving the shop
		 * modal		: Creates an html modal box inside of your shop
		 * popup		: Opens up a popup showing the mercadopago checkout process (not recommended)
		 * blank		: Opens a new tab in the browser showing the mercadopago checkout process
		 *
		 * This way is determined by the configuration set in the backend (uimode) in 
		 * the mercado pago plugin.
		 *
		 */

		public function javascriptAction(){

			$this->View()
			->assign(
						'gatewayUrl',
						$this->getCheckoutUrl()
			);

			$this->View()
			->assign(
						'jsMode',
						strtolower($this->getPlugin()->getConfig('uimode'))
			);

		}

		/**
		 * The PHP action redirects immediately to the mercado pago URL 
		 * created by self::getCheckoutUrl leaving your shop.
		 */

		public function phpRedirectAction(){

			$this->redirect($this->getCheckoutUrl());

		}

		/**
		 * The iframe action renders an iframe showing the mercadopago checkout process
		 * inside of the shop.
		 */

		public function iframeAction(){

			$this->View()
			->assign(
						'gatewayUrl',
						$this->getCheckoutUrl()
			);

		}

		/**
		 * Instant payment notifications are notifications sent by MercadoPago
		 * when the customer has pressed the "pay" button in the mercadopago site.
		 *
		 * IPN's must be configured (while LOGGED IN mercadopago) 
		 * from the following URL: https://www.mercadopago.com.ar/ipn-notifications
		 * 
		 * While configuring the ipn notification in mercado pago, it must match 
		 * your domain, for instance: https://www.your-domain.com/MercadoPago/ipn
		 *
		 * This action creates an IPN Payment Response from an enlight request
		 * and proceeds to save the order in your shop.
		 *
		 */

		public function ipnAction(){

			$response	=	$this->getService()
			->createIPNPaymentResponseFromEnlightRequest($this->Request());

			$this->saveOrder(
									$response->id,
									$this->createPaymentUniqueId(),
									$response->status
			);

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
