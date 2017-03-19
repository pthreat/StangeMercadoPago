<?php

	/**
	 * MercadoPago ShopWare plugin integration. 毘
	 *
	 * @license MIT (See the LICENSE file for more information)
	 * @author Federico Stange <jpfstange@gmail.com>
	 */

	namespace StangeMercadoPago{

		use \Shopware\Components\Plugin	as	BaseShopwarePlugin;

		use \Shopware\Components\Console\Application;
		use \Shopware\Components\plugin\context\ActivateContext;
		use \Shopware\Components\plugin\context\DeactivateContext;
		use \Shopware\Components\plugin\context\InstallContext;
		use \Shopware\Components\plugin\context\UninstallContext;

		use \StangeMercadoPago\Command\CreateTestUser	as	CreateTestUserCommand;
		use \StangeMercadoPago\Command\CurrencyExchange	as	CurrencyExchangeCommand;

		class StangeMercadoPago extends BaseShopwarePlugin{

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
								'name'						=>	'basic_checkout',
								'description'				=>	'MercadoPago Basic Checkout',
								'action'						=> 'MercadoPagoBasic',
								'active'						=>	0,
								'position'					=> 0,
								'additionalDescription' =>	"<img src=\"$img\" />" 
				];

				$installer->createOrUpdate($context->getPlugin(), $options);

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

			/**
			 * Require the autoload.php script generated by composer
			 */

			public function registerMyComponents(){

				require_once $this->getPath().'/vendor/autoload.php';

			}

			public function registerCommands(Application $application){

				$this->registerMyComponents();

				$application->add(new CreateTestUserCommand());
				$application->add(new CurrencyExchangeCommand());

			}

			/**
			 * Helper method to be able to quickly get a configuration parameter from this plugin 
			 * @param string Configuration name, example: "CLIENT_ID"
			 * @return string The configuration value
			 */

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

