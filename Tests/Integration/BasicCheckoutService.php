<?php

	/**
	 * Test the whole basic checkout URL generation process
	 * By definition this is more of an "Integration" test case more than a "Functional" test.
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (See the LICENSE file for more information)
	 * 
	 */

	use \StangeMercadoPago\Components\Service\Checkout\Basic	as	BasicCheckoutService;
	use \StangeMercadoPago\Test\Base	as	BaseTest;

	class BasicCheckoutServiceTest extends BaseTest{

		protected static $ensureLoadedPlugins	=	[
																	'StangeMercadoPago'
		];

		public function testServiceInstantiation(){

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

		public function testValuesSetInBasicCheckoutService(){

			$service	=	$this->testServiceInstantiation();

			$this->assertNotNull($service->getAppCurrency());
			$this->assertNotNull($service->getStoreCurrency());
			$this->assertInstanceOf('MP',$service->getMPClient());
			$this->assertNull($service->getRate());

		}

		public function testCalculatedAPIExchangeRate(){

			$service	=	$this->testServiceInstantiation();

			$this->assertInternalType('double',$service->getCalculatedRate());
			$this->assertInternalType('double',$service->getRate());

		}

		public function mockAndTestBasicCheckoutURLGeneration(){

			$service	=	$this->testServiceInstantiation();

			/**
			 * Simulate some checkout data
			 */

			$basket	=	[
								[
									'articlename' => 'Napkins',
									'quantity'    => 1,
									'price'       => 11.2
								],
								[
									'articlename' => 'Sunglasses',
									'quantity'    => 2,
									'price'       => 30
								]
			];

			$customer	=	[
									'firstname'	=>	'Federico',
									'lastname'	=>	'Stange',
									'email'		=>	'jpfstange@gmail.com'
			];

			$shipment	=	[
									"price"   => 10.2,
									"address" => [
														"street"		=>	"Lausse strasse",
														"zipcode"	=>	"1214"
									]
			];

			$this->assertNotEmpty(
										$service->getCheckoutURL([
																			'items'		=>	$basket,
																			'customer'	=>	$customer,
																			'shipment'	=>	$shipment
										])
			);

		}

	}

