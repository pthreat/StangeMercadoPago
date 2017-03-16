<?php

	namespace StangeMercadoPago\Components\Service\Checkout{

		use \StangeMercadoPago\Components\Service\Checkout\Base	as	BaseCheckout;
		use \StangeMercadoPago\Components\Payment\Response\IPN	as	IPNPaymentResponse;

		class Basic extends BaseCheckout{

			/**
			 * Convert ShopWare customer data to MercadoPago customer expected structure
			 *
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @return Array An array containing customer data in MP format.
			 */

			public function customerToMp(Array $customerData){

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
			 * You must pass an array with the following (example) format:
			 *
			 * <code>
			 * [
			 *    "price"   => 10,
			 *    "address" => [
			 *                  "street"	=> "Lausse strasse",
			 *                  "zipcode"	=> 1214
			 *    ]
			 * ]
			 * </code>
			 *
			 * @params Array An Array containing the shipment parameters.
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @return Array An array containing shippment data in MP format.
			 */

			public function shipmentToMP(Array $params){

				$this->validateShopwareShipment($params);

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
			 * @link https://www.mercadopago.com.ar/developers/es/api-docs/basic-checkout/checkout-preferences/
			 * @return Array An array containing items data in MP format.
			 */

			public function basketToMP(Array $basket){

				$this->validateBasket($basket);
				$this->setStoreCurrency($basket['sCurrencyName']);
				$this->getCalculatedRate();

				$content	=	$basket['content'];
				$items	=	[];
				$rate		=	$this->getRate();

				foreach($content as $article){

					$price	=	$rate * floatval(number_format($article['price'],2,'.',''));

					$this->validateShopwareArticleStructure($article);

					$items[]	=	[
										"title"			=>	$article['articlename'],
										"quantity"		=>	(int)$article['quantity'],
										"currency_id"	=>	$appCurrency,
										"unit_price"	=>	floatval($price)
					];
					
				}

				return $items;

			}

			public function createMPPreferenceStructure(Array $params){

				return [
							'items'		=>	$this->basketToMp(isset($params['items'])			?	$params['items'] : []),
							'payer'		=>	$this->customerToMp(isset($params['customer'])	?	$params['customer'] : []),
							'shipments'	=>	$this->shipmentToMp(isset($params['shipment'])	?	$params['shipment'] : [])
				];

			}

			/**
			 * Returns the proper MercadoPago payment URL
			 *
			 * @return string
			 */

			public function createMPPreference(Array $params){

				return $this->getMPClient()
				->create_preference(
											$this->createMPPreferenceStructure($params)
				);

			}

			public function getCheckoutUrl(Array $params,$mode='prod'){

				$mode			=	strtolower($mode);
				$preference	=	$this->createMPPreference($params);

				return $mode == 'prod' ? $preference['response']['init_point'] : 
												$peference['response']['sandbox_init_point'];

			}


			/**
			 * Creates a payment response from an enlight controller request
			 *
			 * @param $request \Enlight_Controller_Request_Request
			 * @return \stange\mercadopago\payment\Response
			 */

			public function createIPNPaymentResponseFromEnlightRequest(\Enlight_Controller_Request_Request $request){

				$response	=	new IPNPaymentResponse([
																	'id'		=>	$request->get('id'),
																	'topic'	=>	$request->get('topic'),
																	'mp'		=>	$this->getMPClient()
				]);

				$response->parse();

				return $response;

			}

			public function validateBasket(Array $basket){

				if(!isset($basket['content'])){

					throw new \InvalidArgumentException("No basket content was set");

				}

				if(!isset($basket['sCurrencyName'])){

					throw new \InvalidArgumentException("No sCurrencyName was specified");

				}

			}

			public function validateShopwareShipment(Array $params){

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

			public function validateShopwareArticleStructure(Array $article){

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
