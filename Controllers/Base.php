<?php

	namespace StangeMercadoPago\Controller{

		class Base extends \Shopware_Controllers_Frontend_Payment{

			/**
			 * Small shortcut to access the plugin throughout the controller
			 * @var \Shopware\Components\Plugin $plugin 
			 */

			private	$plugin	=	NULL;

			public function preDispatch(){

				/** In case the basket is empty, redirect to checkout **/

				if(empty($this->getBasket())){

					return $this->redirect(['controller' => 'index']);

				}

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

			public function getPlugin(){

				return $this->plugin;

			}

		}

	}

