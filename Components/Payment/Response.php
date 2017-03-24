<?php

	namespace StangeMercadoPago\Components\Payment{

		use \StangeMercadoPago\Components\Payment\Base	as	BasePayment;

		class Response extends BasePayment{

			/** 
			 * Holds the current payment status (approved,cancelled or pending)
			 * @var string $status
			 */

			private	$status			=	NULL;

			/**
			 * Holds the collection id sent by mercadopago
			 * @param int $collectionId
			 */

			private	$collectionId	=	NULL;

			/**
			 * Contains the merchant order id
			 * @var int $merchOrderId
			 */

			private	$merchOrderId	=	NULL;

			/**
			 * Contains the mercadopago preference id
			 * @var string $preferenceId
			 */

			private	$preferenceId	=	NULL;

			/**
			 * Mercado Pago Client Object
			 * @var \MP 
			 */

			 private $mpClient		=	NULL;


			/**
			 * Contains the payment method used by the customer
			 *
			 * @link https://api.mercadopago.com/payment_types
			 * @var string $paymentType
			 */

			private	$paymentType	=	NULL;

			/**
			 * Contains the Shopware payment id
			 * @var string Payment id
			 */

			 private	$paymentId		=	NULL;

			public function __construct(Array $params){
	
				parent::__construct($params);

				$this->setCollectionId(
												isset($params['collection_id']) ?
												$params['collection_id']	:	
												NULL
				);
												
				$this->setStatus(
										isset($params['collection_status']) ? 
										$params['collection_status'] : NULL
				);

				$this->setPreferenceId(
												isset($params['preference_id']) ?
												$params['preference_id'] : 
												NULL
				);

				$this->setPaymentType(
												isset($params['payment_type']) ? 
												$params['payment_type']	:	
												NULL
				);

				$this->setMerchantOrderId(
													isset($params['merchant_order_id']) ?
													$params['merchant_order_id']	:	
													NULL
				);

				$this->setPaymentId($params['pid']);

			}


			/**
			 * Set Shopware's internal payment id
			 *
			 * @param string $id 
			 * @return $this
			 */

			public function setPaymentId($id){

				$this->paymentId	=	$id;
				return $this;

			}

			/**
			 * Get shopware's payment id
			 *
			 * @return string Payment id
			 */

			public function getPaymentId(){

				return $this->paymentId;

			}

			/**
			 * Set the mercadopago collection id.
			 *
			 * @param int $id COllection id
			 * @return $this
			 */

			public function setCollectionId($id){

				$id	=	(int)$id;

				if($id<=0){
					$msg	=	"Invalid collection id";
					throw new \InvalidArgumentException($msg);	
				}

				$this->collectionId	=	$id;

				return $this;

			}

			/**
			 * Return the mercadopago collection id
			 *
			 * @return int the collection id
			 */

			public function getCollectionId(){

				return $this->collectionId;

			}

			/**
			 * Set the mercado pago preference id
			 *
			 * @return $this
			 */

			public function setPreferenceId($id){

				$id	=	trim($id);

				if(empty($id)){

					$msg	=	"Preference id can not be empty";
					throw new \InvalidArgumentException($msg);	

				}

				$this->preferenceId	=	$id;

				return $this;

			}

			/**
			 * Get the mercado pago preference id
			 * @return string mercado pago preference id
			 */

			public function getPreferenceId(){

				return $this->preferenceId;

			}

			/**
			 * Set the payment status.
			 * Said status can be one of: approved, pending, cancelled
			 */
	
			public function setStatus($status){

				if(!in_array($status,['approved','pending','cancelled'])){

					throw new \InvalidArgumentException("Invalid payment status");

				}

				$this->status	=	$status;

				return $this;

			}

			/**
			 * Get the payment status
			 *
			 * @see self::setStatus
			 * @return string one of the 3 possible payment statuses 
			 */

			public function getStatus(){

				return $this->status;

			}

			/**
			 * Set the payment type
			 *
			 * @param string $type Payment type
			 * @throws \InvalidArgumentException if the payment type is invalid
			 * @return $this
			 */

			public function setPaymentType($type){
				
				$type	=	trim($type);

				if(empty($type)){

					$msg	=	"Payment type can not be empty";
					throw new \InvalidArgumentException($msg);

				}

				$this->paymentType	=	$type;

				return $this;

			}

			/**
			 * Get the payment type
			 * @return string Payment type
			 */

			public function getPaymentType(){

				return $this->paymentType;

			}

			/**
			 * Set the merchant order id
			 * @param int $id Merchant order id
			 */

			public function setMerchantOrderId($id){

				if($id<=0){

					$msg	=	"Invalid merchant id";
					throw new \InvalidArgumentException($msg);

				}

				$this->merchOrderId	=	$id;

				return $this;

			}

			/**
			 * Get the merchant order id
			 * @return int Mercado pago merchant order id.
			 */

			public function getMerchantOrderId(){

				return $this->merchOrderId;

			}

			public function getMerchantInfo(){

				return parent::getMP()->get(sprintf('/merchant_orders/%s',$this->merchOrderId));

			}

		}

	}
