<?php

	/**
	 * This class is used for handling and parsing IPN responses 
	 * through the mercado pago API.
	 *
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (See the LICENSE file for more information)
	 */

	namespace StangeMercadoPago\Components\Payment{

		class Response{

			/**
			 * Taken from ShopWare's payment statuses
			 */

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

			/**
			 * Contains the payment IPN (Notification id)
			 * @var string|int Payment topic
			 */

			private	$id		=	NULL;

			/**
			 * Contains the payment topic
			 * @var string Payment topic
			 */
	
			private	$topic	=	NULL;

			/**
			 * Contains the status of the operation
			 * @var int operation status
			 */

			private	$status	=	NULL;

			public function __construct(Array $params=Array()){

				if(isset($params['mp'])){

					$this->setMp($params['mp']);

				}

				if(isset($params['topic'])){

					$this->setTopic($params['topic']);

				}

				if(isset($params['id'])){

					$this->setId($params['id']);

				}

			}

			public function setMP(\MP $mp){

				$this->mp	=	$mp;
				return $this;

			}

			public function getMP(){

				return $this->mp;

			}

			public function setTopic($topic){

				$topic	=	strtolower(trim($topic));

				if($topic!=='payment'){

					$msg	=	"Invalid topic received, expected \"payment\" got: \"$topic\"";
					throw new \InvalidArgumentException($msg);

				}

				$this->topic	=	$topic;

				return $this;

			}

			public function getTopic(){

				return $this->topic;

			}

			public function setId($id){

				if(!($id&&is_numeric($id)&&$id>0)){

					$msg	=	"Invalid payment id received, expected numeric payment id, got: \"$topic\"";
					throw new \InvalidArgumentException($msg);

				}

				$this->id	=	$id;

				return $this;

			}

			public function getId(){

				return $this->id;

			}

			public function parse(){

				$this->setMp($this->mp);
				$this->setId($this->id);
				$this->setTopic($this->topic);

				$payment	=	$this->mp
				->get(
						sprintf(
									"/collections/notifications/%s",
									$this->id
						)
				);

				if(!isset($payment['status']) || $payment['status']!==200){

					throw new \InvalidArgumentException("Invalid IPN Response");

				}

				if(
						!isset($payment['response']['collection']['status']) ||
						!isset($payment['response']['collection']['status_detail']) 
				){

					throw new \RuntimeException("Wrong MP IPN Response");

				}

				$transactionStatus	=	$payment['response']['collection']['status'];
				$statusDetail			=	$payment['response']['collection']['status_detail'];

				switch($transactionStatus){

					case 'approved':

						$status	=	$statusDetail=='accredited' ? 
						self::PAYMENTSTATUSPAID :
						self::PAYMENTSTATUSPARTIALLYPAID;

					break;

					case 'cancelled':

						$status	=	self::PAYMENTSTATUSCANCELLED;

					break;

					case 'rejected':

						$status	=	self::PAYMENTSTATUSREJECTED;

					break;

					default:

						throw \Exception("Unknown payment status \"$status\"");

					break;

				}

				$this->status	=	$status;

			}

			public function __get($name){

				if(!isset($this->$name)){

					throw new \InvalidArgumentException("Unknown attribute $name");

				}

				return $this->$name;

			}

		}

	}
