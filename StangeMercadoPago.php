<?php

	namespace StangeMercadoPago{

		use \Shopware\Components\Plugin	as	BaseShopwarePlugin;
		use \Shopware\components\plugin\context\ActivateContext;
		use \Shopware\components\plugin\context\DeactivateContext;
		use \Shopware\components\plugin\context\InstallContext;
		use \Shopware\components\plugin\context\UninstallContext;
		use \Shopware\models\payment\Payment	as	PaymentModel;

		class StangeMercadoPago extends BaseShopwarePlugin{

			public function requireAutoloader(){

				if(!class_exists('MP')){

					require __DIR__.'/vendor/autoload.php';

				}

			}

			/**
			 * @param InstallContext $context
			 */

			public function install(InstallContext $context){

			  /**
				* @var \Shopware\Components\Plugin\PaymentInstaller $installer 
				*/

				$installer = $this->container->get('shopware.plugin_payment_installer');
		
				$img	=	'https://secure.mlstatic.com/components/resources/mp/desktop/css/assets/desktop-logo-mercadopago.png';

				$options = [
								'name'						=>	'stange_mercadopago_checkout',
								'description'				=>	'Mercado Pago Payment method',
								'action'						=> 'MercadoPago',
								'active'						=>	0,
								'position'					=> 0,
								'additionalDescription' =>	"<img src=\"$img\" />" 
				];

				$installer->createOrUpdate($context->getPlugin(), $options);

			}


			/**
			 *
			 * Due that MercadoPago does not convert automatically between the currency rate set in the store
			 * to the currency of the mercado pago application, this workaround had to be thought 
			 * to be able to convert from the store currency to the mercado pago application currency.
			 * 
			 * Example Problem Story:
			 *------------------------------------------------------------------------------------------------
			 *
			 * Setting
			 *-----------------------------------------------------------------
			 *
			 * Federico has created a Mercado pago application
			 * Federico is from Argentina
			 * Federico wants to sell Argentine products to anyone in the world.
			 * Federico wants to be able to cash in using mercadopago
			 *
			 * Plot
			 *-----------------------------------------------------------------
			 *
			 * A customer from Germany browses through Federico's store and buys a typical Argentine "Mate"
			 * 
			 * Federico has enabled MercadoPago as the only possible payment method, he knows it's a reliable
			 * payment gateway and that he will be able to charge the money instantly.
			 *
			 * The German customer has set the store in the EUR (Euro) currency rate.
			 * The customer goes to checkout and selects MercadoPago as the payment method (the only one available)
			 *
			 * During checkout, the server will now process the basket item list from the German customer 
			 * and translate said list into a mercadopago payment request.
			 *
			 * While processing the basket list, MercadoPago has to receive the adequate currency from 
			 * Federico's country of origin (Argentina), this could be one of two, ARS or USD.
			 *
			 * Resolution
			 *-----------------------------------------------------------------
			 *
			 * This is why we need to *convert* from EUR (Euro) to ARS (Argentine Peso) or USD (US Dollars),
			 * or else, if the currency is inadequate for Federico's country, we will get an 
			 * exception from MercadoPago pretty much like this: 
			 *
			 * core.ERROR: exception 'MercadoPagoException' with message 'currency_id invalid'
			 *
			 * How do we convert between currencies? 
			 *-----------------------------------------------------------------
			 *
			 * We do this by using MercadoPago's currency converter located at:
			 *
			 * https://api.mercadolibre.com/currency_conversions/search?from=<from_currency>&to=<to_currency>
			 *
			 * Forum thread (in Spanish): https://groups.google.com/forum/#!topic/mercadopago-developers/vSK5bXoJjag
			 *
			 * NOTE: According to the forum thread, this "problem" (or lack of enhancement) has been around since 2014
			 *
			 */

			public function convertRate($from,$to){

				$url	=	'https://api.mercadolibre.com/currency_conversions/search?from=%s&to=%s';
				$url	=	sprintf($url,$from,$to);
				$rate	=	@file_get_contents($url);

				if(empty($rate)){

					$msg	=	"Could not connect to MercadoLibre currency conversion API";
					throw new \RuntimeException($msg);

				}

				$rate	=	json_decode($rate);

				if(!isset($rate->ratio)){

					$msg	=	"Wrong conversion response from MercadoLibre from \"$from\" to \"$to\"";
					throw new \RuntimeException($msg);

				}

				return floatval($rate->ratio);

			}

			/**
			 * @param UninstallContext $context
			 */

			public function uninstall(UninstallContext $context){

				$this->setActiveFlag($context->getPlugin()->getPayments(), FALSE);

			}

			/**
			 * @param DeactivateContext $context
			 */

			public function deactivate(DeactivateContext $context){

				$this->setActiveFlag($context->getPlugin()->getPayments(), FALSE);

			}

			/**
			 * @param ActivateContext $context
			 */

			public function activate(ActivateContext $context){

				$this->setActiveFlag($context->getPlugin()->getPayments(), TRUE);

			}

			/**
			 * @param Payment[] $payments
			 * @param $active bool
			 */

			private function setActiveFlag($payments, $active){

				$em = $this->container->get('models');

				foreach ($payments as $payment) {

					$payment->setActive($active);

				}

				$em->flush();

			}

			public function getConfig($name){

				$configNs	=	strtolower(__CLASS__);
				$bsPos		=	stripos($configNs,"\\");

				if($bsPos){

					$configNs	=	substr($configNs,$bsPos+1);

				}

				$value	=	\Shopware()->Config()->getByNamespace($configNs,$name);

				//Not really possible since all values are required but just in case.

				if($value === NULL){

					$msg	=	"Configuration value \"$name\" has not been configured, please configure this value";
					throw new \LogicException($msg);

				}

				$value	=	trim($value);

				if(empty($value)){

					$msg	=	"Configuration value \"$name\" could not be found. Please CLEAR your store cache";
					throw new \LogicException($msg);

				}

				return $value;

			}

		}

	}

