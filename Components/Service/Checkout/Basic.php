<?php

	/**
	 * This service uses the \MP client class by composition in order to obtain a valid checkout URL.
	 *
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (For more details, see the LICENSE file)
	 * 
	 * @TODO replace array structures with "shopware structs" to be able to type hint each method.
	 *
	 * @TODO Once every structure has been defined in a separate class, create separate 
	 * factory classes for building said objects from arrays.
	 *
	 */

	namespace StangeMercadoPago\Components\Service\Checkout{

		use \StangeMercadoPago\Components\Service\Checkout\Base	as	BaseCheckout;
		use \StangeMercadoPago\Components\Payment\Response\IPN	as	IPNPaymentResponse;

		class Basic extends BaseCheckout{

			/**
			 * Convert ShopWare customer data to MercadoPago customer expected structure
			 *
			 * Expected structure example:
			 * <code>
			 *	[
			 *		'firstname'	=>	'Federico',
			 *		'lastname'	=>	'Stange',
			 *		'email'		=>	'jpfstange@gmail.com'
			 *	]
			 * </code>
			 *
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @throws \InvalidArgumentException if the customer data passed in the array is invalid.
			 * @return Array An array containing customer data in MP format.
			 */

			public function customerToMp(Array $customerData){

				$this->validateCustomer($customerData);

				/**
				 * The phone field needs to be more specific for MercadoPago
				 * Maybe: Use google's libphonenumber to parse the phone
				 */

				//'phone'		=>	$customer['shippingaddress']['phone'],

				return [
							'name'		=>	$customerData['firstname'],
							'surname'	=>	$customerData['lastname'],
							'email'		=>	$customerData['email']
				];

			}

			/**
			 * Convert ShopWare customer shipping address to MercadoPago shippments format
			 *
			 * You must pass an array with the following (example) format:
			 *
			 * <code>
			 * [
			 *    "price"   => 10.2,
			 *    "address" => [
			 *                  "street"	=> "Lausse strasse",
			 *                  "zipcode"	=> "1214"
			 *    ]
			 * ]
			 * </code>
			 *
			 * @throws \InvalidArgumentException if the shipment data passed in the array is invalid.
			 * @params Array An Array containing the shipment parameters.
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @return Array An array containing shippment data in MP format.
			 */

			public function shipmentToMP(Array $params){

				$this->validateShipment($params);

				$cost			=	floatval($params['price']) * $this->getCalculatedRate();

				$street		=	trim(preg_replace('/[0-9]/','',$params['address']['street']));
				$number		=	explode(' ',$params['address']['street']);
				$num			=	0;

				foreach($number as $num){

					if(preg_match('/[0-9]+/',$num)){

						$num	=	(int)$num;
						break;

					}

				}

				$address		=	[
										'zip_code'			=>	$params['address']['zipcode'],
										'street_name'		=>	$street,
										'street_number'	=>	$num,
										'floor'				=>	'',		//@TODO floor parsing
										'apartment'			=>	'',		//@TODO apartment parsing
				];

				$mpShipment	=	[
										'mode'					=>	'custom',
										'cost'					=>	$cost
				];

				if(!$cost){

					$mpShipment['free_shipping']	=	TRUE;

				}

				$mpShipment['receiver_address']	=	$address;

				return $mpShipment;

			}

			/**
			 * Parse Shopware Basket items to MercadoPago items structure
			 *
			 * Expected structure example:
			 *
			 * <code>
			 *    [
			 *			[
			 *				'articlename' => 'Napkins',
			 *				'quantity'    => 1,
			 *				'price'       => 11.2
			 *			],
			 *			[
			 *				'articlename' => 'Sunglasses',
			 *				'quantity'    => 2,
			 *				'price'       => 30
			 *			]
			 *    ]
			 * </code>
			 *
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @throws \InvalidArgumentException if the basket data passed in the array is invalid.
			 * @return Array An array containing items data in MP format.
			 */

			public function basketToMP(Array $bItems){

				$this->getCalculatedRate();

				$items	=	[];
				$rate		=	$this->getRate();

				foreach($bItems as $article){

					$this->validateArticle($article);

					$price	=	$rate * floatval(number_format($article['price'],2,'.',''));

					$items[]	=	[
										"title"			=>	$article['articlename'],
										"quantity"		=>	(int)$article['quantity'],
										"currency_id"	=>	$appCurrency,
										"unit_price"	=>	floatval($price)
					];
					
				}

				return $items;

			}

			/**
			 * Creates an Array that contains the expected array structure for the \MP Client.
			 *
			 * Said structure is described below:
			 *
			 * <code>
			 *  [
			 *     'items'    => [
			 *                     [
			 *                      'articlename' => 'Napkins',
			 *                      'quantity'    => 1,
			 *                      'price'       => 11.2
			 *                     ],
			 *                     [
			 *                      'articlename' => 'Sunglasses',
			 *                      'quantity'    => 2,
			 *                      'price'       => 30
			 *                     ],
			 *     ],
			 *     'customer' => [
			 *                    'firstname' => 'Federico',
			 *                    'lastname'  => 'Stange'
			 *     ],
			 *     'shipment' => [
			 *                    'price'   => (float)
			 *                    'address' => [
			 *												'street'  => 'Lause strasse 1234',
			 *												'zipcode' => '1234'
			 *                    ]
			 *
			 *     ]
			 *  ]
			 *
			 * </code>
			 *
			 * Each item in the code shown above will be converted to the expected mercadopago
			 * structure.
			 *
			 * @see self::basketToMp
			 * @see self::customerToMp
			 * @see self::shipmentToMp
			 *
			 * @throws \InvalidArgumentException if any of the conversion methods 
			 * receives invalid data an exception will be thrown by them.
			 *
			 * @return Array An Array containing the expected structure by mercado pago
			 */

			public function createMPPreferenceStructure(Array $params){

				return [
							'items'		=>	$this->basketToMp(
																isset($params['items'])		?
																$params['items'] : []
							),
							'payer'		=>	$this->customerToMp(
																isset($params['customer'])	?	
																$params['customer'] : []
							),
							'shipments'	=>	$this->shipmentToMp(
																isset($params['shipment'])	?	
																$params['shipment'] : []
							)
				];

			}

			/**
			 * Creates a mercado pago preference
			 *
			 * @params Array An array containing the structure described in self::createMPPreferenceStructure
			 * @see self::createMPPreferenceStructure
			 * @return Array Mercado Pago checkout preference array.
			 */

			public function createMPPreference(Array $params){

				return $this->getMPClient()
				->create_preference(
											$this->createMPPreferenceStructure($params)
				);

			}

			/**
			 * Returns the checkout URL for mercado pago
			 *
			 * @throws \RuntimeException if it wasn't possible to create the mercadopago preference
			 * @see self::createMPPreference
			 * @return string The mercadopago checkout url
			 */

			public function getCheckoutUrl(Array $params,$mode='prod'){

				$mode			=	strtolower($mode);
				$preference	=	$this->createMPPreference($params);

				if(!$preference){

					$msg	=	"Failed fetching MercadoPago preference data";

					throw new \RuntimeException($msg);

				}

				return $mode == 'prod' ? 
					$preference['response']['init_point'] : 
					$peference['response']['sandbox_init_point'];

			}

			/**
			 * Creates a payment response from an enlight controller request
			 *
			 * @param $request \Enlight_Controller_Request_Request
			 * @return \stange\mercadopago\payment\Response
			 */

			public function createIPNResponseFromRequest(\Enlight_Controller_Request_Request $request){

				$response	=	new IPNPaymentResponse([
																	'id'		=>	$request->get('id'),
																	'topic'	=>	$request->get('topic'),
																	'mp'		=>	$this->getMPClient()
				]);

				$response->parse();

				return $response;

			}

			/** 
			 * Validation: 
			 * These methods will be reaplaced by shopware struct's 
			 */

			/**
			 * Validate customer data.
			 *
			 * @throws \InvalidArgumentException firstname missing (code 1)
			 * @throws \InvalidArgumentException lastname missing (code 2)
			 * @throws \InvalidArgumentException email missing (code 3)
			 * @throws \InvalidArgumentException invalid email (code 4)
			 */

			public function validateCustomer(Array $customer){

				if(!isset($customer['firstname'])){

					throw new \InvalidArgumentException("Missing customer firstname",1);

				}

				if(!isset($customer['lastname'])){

					throw new \InvalidArgumentException("Missing customer lastname",2);

				}

				if(!isset($customer['email'])){

					throw new \InvalidArgumentException("Missing customer email",3);

				}

				if(!filter_var($customer['email'],\FILTER_VALIDATE_EMAIL)){

					throw new \InvalidArgumentException("Invalid customer email",4);

				}

			}

			/**
			 * Validate shipment data
			 * @throws \InvalidArgumentException if any data is missing
			 */

			public function validateShipment(Array $params){

				if(!isset($params['price'])){

					throw new \InvalidArgumentException("Missing shipment price");

				}

				if(!isset($params['address'])){

					throw new \InvalidArgumentException("Missing shipping address");

				}

				if(!isset($params['address']['street'])){

					throw new \InvalidArgumentException("Missing shipping address street");

				}

				if(!isset($params['address']['zipcode'])){

					throw new \InvalidArgumentException("Missing shipping address zip code");

				}

			}

			/**
			 * Validate article data
			 * @throws \InvalidArgumentException if any data is missing
			 */

			public function validateArticle(Array $article){

				if(!isset($article['articlename'])){

					throw new \InvalidArgumentException("Item does not has an articlename index");

				}

				if(!isset($article['quantity'])){

					throw new \InvalidArgumentException("Item does not has an articlename index");

				}

				if(!isset($article['price'])){

					throw new \InvalidArgumentException("Item does not has an unit_price index");

				}

			}

		}

	}
