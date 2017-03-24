<?php

	/**
	 * Base class for mercado pago checkout services containing useful
	 * methods, such as API exchange rate conversion.
	 * 
	 * @author Federico Stange <jpfstange@gmail.com>
	 * @license MIT (See the LICENSE file for more information)
	 */

	namespace StangeMercadoPago\Components\Service\Checkout{

		abstract class Base{

			/**
			 * @var \MP Mercado pago instance
			 */

			private	$mp				=	NULL;

			/**
			 * Your MercadoPago Application Currency 
			 * @var string Application currency name
			 */

			private	$appCurrency	=	NULL;

			/**
			 * Your store currency, i.e the currency the end user has set 
			 * to operate in the store.
			 *
			 * @var string Store currency
			 */
		
			private	$storeCurrency	=	NULL;

			/**
			 * Contains the calculated conversion rate between the store currency 
			 * and the application currency.
			 *
			 * This rate can also be manually set in the constructor or by calling
			 * set rate after the object was constructed.
			 *
			 * @var double The calculated conversion rate
			 */

			private	$rate				=	NULL;

			/**
			 * Holds the IPN Url. Said URL must be *accesible* to the internet.
			 * @var string a valid url for getting instant payment notifications
			 */

			private	$ipnURL			=	NULL;

			/**
			 * Holds the url which mercadopago will use (if specified) 
			 * when a payment is successful.
			 *
			 * @var string A valid url
			 */

			private	$successURL			=	NULL;

			/**
			 * Holds the url which mercadopago will use (if specified) 
			 * when a payment is cancelled.
			 *
			 * @var string A valid url
			 */

			private	$cancelURL			=	NULL;

			/**
			 * Holds the url which mercadopago will use (if specified) 
			 * when a payment is pending.
			 *
			 * @var string A valid url
			 */

			private	$pendingURL			=	NULL;

			/**
			 * Contains the payment id
			 * @var string Payment id
			 */

			private	$paymentId			=	NULL;

			/**
			 * The constructor takes in a Shopware_Components_Config object or 
			 * an array, this duality is provided for easing testing.
			 *
			 * @param Array $params an array containing the following structure
			 * <code>
			 * [
			 *		'id'					=>	'mercado pago client id'
			 *		'secret'				=>	'mercado pago client secret'
			 *		'token'				=>	'token (if any)'
			 *		'app_currency'		=> 'Application currency',
			 *    'rate'				=> NULL, //DO NOT SPECIFY! TESTING ONLY!
			 * ]
			 * </code>
			 *
			 * @param \Shopware_Components_Config $params You can also pass 
			 * a shopware components config object.
			 *
			 * @throws \InvalidArgumentException if id and secret or token
			 * were not specified.
			 */

			public function __construct($params){

				if(
						is_object($params) && 
						is_a($params,'Shopware_Components_Config')
				){

					$params	=	[
										'id'					=>	$params->get('CLIENT_ID'),
										'secret'				=>	$params->get('CLIENT_SECRET'),
										'token'				=>	$params->get('TOKEN'),
										'app_currency'		=>	$params->get('CURRENCY')
					];

				}

				$this->setAppCurrency($params['app_currency']);

				if(isset($params['store_currency'])){

					$this->setStoreCurrency($params['store_currency']);

				}

				if(isset($params['rate'])){

					$this->setRate($params['rate']);

				}

				if(!(empty($params['id']) && empty($params['secret']))){

					$this->mp	=	new \MP($params['id'],$params['secret']);

					return;

				}

				if(!empty($params['token'])){

					$this->mp	=	new \MP($params['token']);
					return;

				}

				$msg	=	"(id and secret) or token needed for MercadoPago client";

				throw new \InvalidArgumentException($msg);

			}

			/**
			 * Set the payment identifier to append into the notification URL
			 * This allows us to set an order id on the mercadopago notification_url 
			 *
			 * @param string $id Payment id
			 * @return $this
			 */

			public function setPaymentId($id){

				$_id	=	trim($id);

				if(empty($_id)){
			
					$msg	=	"Payment id can not be empty";
					throw new \InvalidArgumentException($msg);

				}

				$this->paymentId	=	$id;

				return $this;

			}

			/**
			 * Returns the payment id
			 *
			 * @return string payment id 
			 */

			public function getPaymentId(){

				return $this->paymentId;

			}

			/**
			 * Sets the IPN URL for receiving payment notifications
			 *
			 * @throws \InvalidArgumentException If the URL is invalid
			 * @return $this
			 */

			public function setIPNUrl($url){

				if(!filter_var($url,\FILTER_VALIDATE_URL)){

					$msg	=	"IPN URL is invalid. Remember said URL must contain a path, example: http://myshop.com/MercadoPago";

					throw new \InvalidArgumentException($msg);

				}

				$this->ipnURL	=	$url;

				return $this;

			}

			/**
			 * Returns the IPN url or null in case said url has not been set
			 *
			 * @return string IPN Url
			 * @return url IPN url has not been set
			 */

			public function getIPNUrl(){

				return $this->ipnURL;

			}

			/**
			 * Set the URL for mercadopago to redirect on payment success
			 *
			 * @param string $url Success url
			 * @throws \InvalidArgumentException If the url is not valid
			 * @return $this
			 */

			public function setSuccessUrl($url){

				if(!filter_var($url,\FILTER_VALIDATE_URL)){

					$msg	=	"Invalid success URL: \"$url\"";

					throw new \InvalidArgumentException($msg);

				}

				$this->successURL	=	$url;

				return $this;

			}

			/**
			 * Get the URL for mercadopago to redirect on payment success
			 * @return string Success URL
			 * @return null Success URL has not been set
			 */

			public function getSuccessUrl(){
		
				return $this->successURL;

			}

			/**
			 * Set the URL for mercadopago to redirect when a payment is cancelled
			 *
			 * @param string $url cancel url
			 * @throws \InvalidArgumentException If the url is not valid
			 * @return $this
			 */

			public function setCancelUrl($url){

				if(!filter_var($url,\FILTER_VALIDATE_URL)){

					$msg	=	"Invalid cancel URL: \"$url\"";

					throw new \InvalidArgumentException($msg);

				}

				$this->cancelURL	=	$url;

				return $this;

			}

			/**
			 * Get the URL for mercadopago to redirect when a payment is cancelled
			 *
			 * @return string Cancel URL
			 * @return null Cancel URL has not been set
			 */

			public function getCancelUrl(){
		
				return $this->cancelURL;

			}

			/**
			 * Set the URL for mercadopago to redirect when a payment is pending
			 *
			 * @param string $url pending url
			 * @throws \InvalidArgumentException If the url is not valid
			 * @return $this
			 */

			public function setPendingUrl($url){

				if(!filter_var($url,\FILTER_VALIDATE_URL)){

					$msg	=	"Invalid pending URL: \"$url\"";

					throw new \InvalidArgumentException($msg);

				}

				$this->pendingURL	=	$url;

				return $this;

			}

			/**
			 * Get the URL for mercadopago to redirect when a payment is pending
			 *
			 * @return string Pending URL
			 * @return null Pending URL has not been set
			 */

			public function getPendingUrl(){
		
				return $this->pendingURL;

			}

			/**
			 * Returns the MP Client instance
			 * @return \MP The mercado pago client object
			 */

			public function getMPClient(){

				return $this->mp;

			}

			/**
			 * Sets the application currency i.e your mercadopago's application 
			 * currency.
			 *
			 * Given currency code must comply to ISO-4217 (Alpha-3).
			 *
			 * Additionally, given currency must be one of: 
			 *
			 * ARS, BOB, BRS, CLF, CLP, COP, CRC, CUC, DOP, GTQ, EUR
			 * HNL, NIO, PAB, PEN, USD, UYU or VEF
			 * 
			 * @return $this
			 */

			public function setAppCurrency($currency){

				$this->appCurrency	=	$this->validateCurrency($currency);

				/** Reset the exchange rate for it to be recalculated **/
				$this->rate				=	NULL;

				return $this;

			}

			/**
			 * Returns the mercadopago application currency set by setAppCurrency
			 *
			 * @return null No application currency was set
			 * @return string The application currency name.
			 */

			public function getAppCurrency(){

				return $this->appCurrency;

			}

			/**
			 * Sets the currency in which the store operates.
			 * Given currency code must comply to ISO-4217 (Alpha-3).
			 *
			 * @link http://www.xe.com/iso4217.php#section2
			 * @return $this
			 */

			public function setStoreCurrency($currency){

				$this->storeCurrency	=	$this->validateCurrency($currency);

				/** Reset the exchange rate for it to be recalculated **/

				$this->rate				=	NULL;

				return $this;

			}

			/**
			 * Returns the store currency name
			 *
			 * @return null the store currency has not been set
			 * @return string ISO4217 currency code
			 */

			public function getStoreCurrency(){

				return $this->storeCurrency;

			}

			/**
			 * Validates a currency name.
			 *
			 * @param $name string ISO4217 currency name
			 * @param $restrictTo Array Currency $name must be in $restricTo
			 * @throws \InvalidArgumentException The currency name is invalid 
			 * (exception code 1).
			 * @throws \InvalidArgumentException If the currency name is not 
			 * in the restricted set (exception code 2).
			 *
			 * @TODO Make use of a currency object
			 * @TODO Make use of a currency validator
			 *
			 * @return string Normalized currency name
			 */

			private function validateCurrency($name, Array $restrictTo=Array()){

				$name	=	trim(strtoupper($name));
		
				if(empty($name)){

					$msg	=	"Empty currency name provided";
					throw new \InvalidArgumentException($msg);

				}

				if(strlen($name)!==3){

					$msg	=	"Invalid currency name: $name";
					throw new \InvalidArgumentException($msg,1);

				}

				if($restrictTo && !in_array($name,$restrictTo)){

					$msg	=	sprintf(
											'Currency must be in: %s',
											implode(',',$restrictTo)
					);

					throw new \InvalidArgumentException($msg,2);
					
				}

				return $name;

			}

			/**
			 *
			 * Due that MercadoPago does not convert automatically between the 
			 * currency rate set in the store to the currency of the mercado pago 
			 * application, this workaround had to be thought to be able to convert
			 * from the store currency to the mercado pago application currency.
			 * 
			 * Example Problem Story:
			 *-------------------------------------------------------------------
			 *
			 * Setting
			 *-------------------------------------------------------------------
			 *
			 * Federico has created a Mercado pago application
			 * Federico is from Argentina
			 * Federico wants to sell Argentine products to anyone in the world 
			 * (flasks of dulce de leche anyone?)
			 *
			 * Federico wants to be able to cash in using mercadopago
			 *
			 * Plot
			 *-----------------------------------------------------------------
			 *
			 * A customer from Germany browses through Federico's store and buys
			 * a flask of dulce de leche.
			 * 
			 * Federico has enabled MercadoPago as the only possible payment 
			 * method, he knows it's a reliable payment gateway and that he will 
			 * be able to charge the money instantly.
			 *
			 * The German customer has set the store in the EUR (Euro) currency 
			 * rate.
			 *
			 * The customer goes to checkout and selects MercadoPago as 
			 * the payment method (the only one available)
			 *
			 * During checkout, the server will now process the basket item 
			 * list from the German customer and translate said list into 
			 * a mercado pago payment request.
			 *
			 * While processing the basket list, MercadoPago has to receive the 
			 * adequate currency from Federico's country of origin (Argentina), 
			 * this could be one of two, ARS or USD.
			 * 
			 *
			 * Resolution
			 *-----------------------------------------------------------------
			 *
			 * This is why we need to *convert* from EUR (Euro) to 
			 * ARS (Argentine Peso) or USD (US Dollars), since, if the currency is
			 * inadequate for Federico's country, we will get an 
			 * exception from MercadoPago pretty much like this: 
			 *
			 * core.ERROR: exception 'MercadoPagoException' with message 
			 * 'currency_id invalid'
			 *
			 * How do we convert between currencies? 
			 *-----------------------------------------------------------------
			 *
			 * We do this by using MercadoPago's currency converter located at:
			 *
			 * https://api.mercadolibre.com/currency_conversions/search?from=<from_currency>&to=<to_currency>
			 *
			 * Forum thread (in Spanish): 
			 * https://groups.google.com/forum/#!topic/mercadopago-developers/vSK5bXoJjag
			 *
			 * NOTE: According to the forum thread, this "problem" 
			 * (or lack of enhancement) has been around since 2014
			 *
			 * @param string $from ISO4217 Currency code
			 * @param string $to ISO4217 Currency code
			 * @throws \RuntimeException In case of connection failure (code 1)
			 * @throws \RuntimeException If the response is invalid (code 2)
			 * @see self::validateCurrency 
			 * @return double the exchange rate
			 *
			 */

			public function calculateRate($from,$to){

				$from =	$this->validateCurrency($from);
				$to	=	$this->validateCurrency($to);

				$url	=	'https://api.mercadolibre.com/currency_conversions/';
				$url	.=	'search?from=%s&to=%s';
				$url	=	sprintf($url,$from,$to);

				//@TODO Replace with guzzle or a pure curl call through the MP Client
				$rate	=	@file_get_contents($url);

				if(empty($rate)){

					$msg	=	"Could not connect to currency conversion API";
					throw new \RuntimeException($msg,1);

				}

				file_put_contents('/tmp/rate',var_export($rate,TRUE));

				$rate	=	json_decode($rate);

				if(!isset($rate->ratio)){

					$msg	=	"Wrong API rate response from \"$from\" to \"$to\"";
					throw new \RuntimeException($msg,2);

				}

				return floatval($rate->ratio);

			}

			/**
			 * Calculates the exchange rate between the mercado pago application 
			 * rate and the store rate attributes.
			 * 
			 * If a rate has been set in the constructor and the recalculate 
			 * parameter is set to the default value (FALSE) the exchange 
			 * rate set in the constructor will be used.

			 * @param $recalculate recalculate the rate even if the exchange rate
			 * has been previously set.
			 *
			 * @see self::calculateRate
			 * @return double the exchange rate 
			 * 
			 */

			public function getCalculatedRate($recalculate=FALSE){

				if(!$recalculate && $this->getRate()){

					return $this->getRate();

				}

				if(!$this->getStoreCurrency()){

					$msg	=	"To calculate currency rate, the store currency"; 
					$msg .=	"must be set";

					throw new \RuntimeException($msg);

				}

				if(!$this->getAppCurrency()){

					$msg	=	"To calculate currency rate, the app currency"; 
					$msg .=	"must be set";

					throw new \RuntimeException($msg);

				}

				/** 
				 * If the customer currency is the same as the one set in the 
				 * plugin, assume conversion of 1
				 */ 

				if($this->storeCurrency == $this->appCurrency){

					return $this->setRate(1);

				}

				/**
				 * If the store currency does not matches the mercadopago 
				 * application currency (normally set through the backend in the 
				 * plugin) convert the store currency to the application currency.
				 *
				 * NOTE: IF currency conversion is needed, the value of the 
				 * currency rate exchange will be obtained from the mercado pago API
				 * through an API request.
				 */

				$this->setRate(
									$this->calculateRate(
																$this->storeCurrency,
																$this->appCurrency
									)
				);

				return $this->getRate();

			}

			/**
			 * Set the exchange rate that will take effect when building the amount
			 * of money to be paid during checkout.
			 *
			 * WARNING: Setting this manually could lead to irregular amounts
			 * of money charged to the customer.PLEASE, use the "setAppCurrency"
			 * and the "setStoreCurrency" methods for the exchange rate to be 
			 * calculated automatically using the mercado pago exchange rate API.
			 *
			 * @param $rate double exchange rate amount.
			 * 
			 * @see self::setAppCurrency
			 * @see self::setStoreCurrency
			 * @see self::getCalculatedRate
			 * @return $this
			 */

			public function setRate($rate){

				if($rate <= 0){

					throw new \InvalidArgumentException("Invalid exchange rate $rate");

				}

				$this->rate	=	floatval($rate);

				return $this;

			}

			/**
			 * Get the exchange rate
			 *
			 * @return null the exchange rate is not set
			 * @return double the exchange rate amount.
			 */

			public function getRate(){

				return $this->rate;

			}

		}

	}
