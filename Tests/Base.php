<?php

	namespace StangeMercadoPago\Test{

		use \Shopware\Components\Test\Plugin\TestCase;
		use \StangeMercadoPago\Components\Service\Checkout\Basic	as	BasicCheckoutService;

		class Base extends TestCase{

			public function getService(){

				static $service;

				if($service){

					return $service;

				}

				$params	=	[
									'id'					=>	$this->CLIENT_ID,
									'secret'				=>	$this->CLIENT_SECRET,
									'token'				=>	$this->TOKEN,
									'app_currency'		=>	$this->CURRENCY,
									'store_currency'	=>	'EUR'
				];

				$service	=	new BasicCheckoutService($params);;

				return $service;

			}

			public function __get($name){

				return \Shopware()
				->Config()
				->getByNamespace('StangeMercadoPago',strtoupper($name));

			}

		}

	}
