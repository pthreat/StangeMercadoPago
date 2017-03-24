<?php

	/**
	 * This class is used for handling and parsing IPN responses 
	 * through the mercado pago API.
	 *
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (See the LICENSE file for more information)
	 */

	namespace StangeMercadoPago\Components\Payment\Response{

		use \StangeMercadoPago\Components\Payment\Base	as	BasePayment;

		class IPN extends BasePayment{

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

				parent::__construct($params);

				$this->setId($params['id']);
				$this->setTopic($params['topic']);

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

				$this->setId($this->id);
				$this->setTopic($this->topic);

				$payment	=	$this->getMP()
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
						parent::PAYMENTSTATUSPAID :
						parent::PAYMENTSTATUSPARTIALLYPAID;

					break;

					case 'cancelled':

						$status	=	parent::PAYMENTSTATUSCANCELLED;

					break;

					case 'rejected':

						$status	=	parent::PAYMENTSTATUSREJECTED;

					break;

					default:

						throw \Exception("Unknown payment status \"$status\"");

					break;

				}

				$this->status	=	$status;

				return $this;

			}

			public function getStatus(){

				return $this->status;

			}

		}

	}
