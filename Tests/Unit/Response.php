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
	use \StangeMercadoPago\Components\Payment\Response	as	PaymentResponse;

	class PaymentResponseTest extends BaseTest{

		public function testPaymentResponse(){

			$service	=	$this->getService();

			$response	=	new PaymentResponse([
															'mp'						=>	$this->getService()->getMPClient(),
															'collection_id'		=>	2134,
															'collection_status'	=>	'approved',
															'preference_id'		=>	'1a2b3c4d',
															'payment_type'			=>	'credit_card',
															'merchant_order_id'	=>	12345,
															'pid'						=>	'abd12h10'
			]);

			$this->assertInstanceOf('MP',$response->getMP());
			$this->assertEquals('approved',$response->getStatus());

		}

	}

