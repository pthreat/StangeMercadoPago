<?php

	namespace StangeMercadoPago{

		use \Shopware\Components\Plugin	as	BaseShopwarePlugin;
		use \Shopware\components\plugin\context\ActivateContext;
		use \Shopware\components\plugin\context\DeactivateContext;
		use \Shopware\components\plugin\context\InstallContext;
		use \Shopware\components\plugin\context\UninstallContext;
		use \Shopware\models\payment\Payment	as	PaymentModel;

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
								'name'						=>	'stange_mercadopago_checkout',
								'description'				=>	'Mercado Pago Payment method',
								'action'						=> 'MercadoPago',
								'active'						=>	0,
								'position'					=> 0,
								'additionalDescription' =>	"<img src=\"$img\" />" .
																	'<div id="payment_desc">'.
																	'Enable your customers to pay through the '.
																	"most prefered latin america's payment method (MercadoPago)".
																	'</div>'
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

			public function getConfig($name){

				$configNs	=	strtolower(__CLASS__);
				$bsPos		=	stripos($configNs,"\\");

				if($bsPos){

					$configNs	=	substr($configNs,$bsPos+1);

				}

				return \Shopware()->Config()->getByNamespace($configNs,$name);

			}

		}

	}

