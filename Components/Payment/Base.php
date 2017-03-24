<?php

	namespace StangeMercadoPago\Components\Payment{

		abstract class Base{

			/**
			 * Taken from ShopWare's payment statuses (s_core_states)
			 */

			const PAYMENTSTATUSOPEN				=	17;
			const PAYMENTSTATUSPARTIALLYPAID	=	11;
			const PAYMENTSTATUSPAID				=	12;
			const PAYMENTSTATUSREJECTED		=	4;
			const PAYMENTSTATUSCANCELLED		=	-1;	

			/**
			 * Contains the \MP instance in order to be able to get the payment information through 
			 * the payment id.
			 * @var \MP Mercado Pago Client instance
			 */

			private	$mp		=	NULL;

			public function __construct(Array $params=Array()){

				$this->setMp(isset($params['mp']) ? $params['mp'] : NULL);

			}

			/**
			 * Set the mercado pago client object to get more information about
			 * this payment.
			 * 
			 * @param \MP $mp mercadopago client
			 * @return $this
			 */

			public function setMP(\MP $mp){

				$this->mp	=	$mp;
				return $this;

			}

			/**
			 * Return the mercado pago client object
			 *
			 * @return \MP Mercado pago client object
			 */

			public function getMP(){

				return $this->mp;

			}

		}
		
	}
