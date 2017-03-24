<?php

	/**
	 * Unit test the IPN Response
	 *
	 * We can't really do any integration testing since each IPN id is independent 
	 * of each client.
	 *
	 *
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (See the LICENSE file for more information)
	 * 
	 */

	use \StangeMercadoPago\Test\Base	as	BaseTest;
	use \StangeMercadoPago\Components\Payment\Response\IPN	as	IPNPaymentResponse;

	class IPNResponseTest extends BaseTest{

		public function testIPNResponse(){

			$service	=	$this->getService();

			$response	=	new IPNPaymentResponse([
																'id'		=>	1234567,
																'topic'	=>	'payment',
																'mp'		=>	$service->getMPClient()

			]);

			$this->assertInstanceOf('MP',$response->getMP());
			$this->assertEquals('payment',$response->getTopic());
			$this->assertNotEmpty($response->getId());

		}

	}

