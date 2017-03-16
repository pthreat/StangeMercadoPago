<?php

	namespace StangeMercadoPago\Components\Service\Checkout{

		abstract class Base{

			private	$mp				=	NULL;

			private	$appCurrency	=	'ARS';

			private	$storeCurrency	=	NULL;

			private	$rate				=	NULL;

			public function __construct($params){

				if(is_object($params) && is_a($params,'Shopware_Components_Config')){

					$params	=	[
										'id'					=>	$params->get('CLIENT_ID'),
										'secret'				=>	$params->get('CLIENT_SECRET'),
										'token'				=>	$params->get('TOKEN'),
										'app_currency'		=>	$params->get('APP_CURRENCY')
					];

				}

				if(isset($params['app_currency'])){

					$this->setAppCurrency($appCurrency);

				}

				if(isset($params['store_currency'])){

					$this->setStoreCurrency($params['store_currency']);

				}

				if(isset($params['id']) && isset($params['secret'])){

					$this->mp	=	new \MP($params['id'],$params['secret']);

					return;

				}

				if(isset($params['token'])){

					$this->mp	=	new \MP($params['token']);
					return;

				}

				$msg	=	"Must pass and secret or token to initialize the MercadoPago client";

				throw new \InvalidArgumentException($msg);

			}

			/**
			 * Returns the MP Client instance
			 * @return \MP The mercado pago client object
			 */

			public function getMPClient(){

				return $this->mp;

			}

			public function setAppCurrency($currency){

				$currency	=	strtoupper(trim($currency));

				if(empty($currency)){

					throw new \InvalidArgumentException("Store currency must have a value");

				}

				$this->appCurrency	=	$currency;
				$this->rate				=	NULL;

				return $this;

			}

			public function getAppCurrency(){

				return $this->appCurrency;

			}

			public function setStoreCurrency($currency){

				$currency	=	strtoupper(trim($currency));

				if(empty($currency)){

					throw new \InvalidArgumentException("Store currency must have a value");

				}

				$this->storeCurrency	=	$currency;
				$this->rate				=	NULL;

				return $this;

			}

			public function getStoreCurrency(){

				return $this->storeCurrency;

			}

			/**
			 *
			 * Due that MercadoPago does not convert automatically between the currency rate set in the store
			 * to the currency of the mercado pago application, this workaround had to be thought 
			 * to be able to convert from the store currency to the mercado pago application currency.
			 * 
			 * Example Problem Story:
			 *------------------------------------------------------------------------------------------------
			 *
			 * Setting
			 *-----------------------------------------------------------------
			 *
			 * Federico has created a Mercado pago application
			 * Federico is from Argentina
			 * Federico wants to sell Argentine products to anyone in the world (flasks of dulce de leche anyone?)
			 * Federico wants to be able to cash in using mercadopago
			 *
			 * Plot
			 *-----------------------------------------------------------------
			 *
			 * A customer from Germany browses through Federico's store and buys a typical Argentine "Mate"
			 * 
			 * Federico has enabled MercadoPago as the only possible payment method, he knows it's a reliable
			 * payment gateway and that he will be able to charge the money instantly.
			 *
			 * The German customer has set the store in the EUR (Euro) currency rate.
			 * The customer goes to checkout and selects MercadoPago as the payment method (the only one available)
			 *
			 * During checkout, the server will now process the basket item list from the German customer 
			 * and translate said list into a mercadopago payment request.
			 *
			 * While processing the basket list, MercadoPago has to receive the adequate currency from 
			 * Federico's country of origin (Argentina), this could be one of two, ARS or USD.
			 *
			 * Resolution
			 *-----------------------------------------------------------------
			 *
			 * This is why we need to *convert* from EUR (Euro) to ARS (Argentine Peso) or USD (US Dollars),
			 * or else, if the currency is inadequate for Federico's country, we will get an 
			 * exception from MercadoPago pretty much like this: 
			 *
			 * core.ERROR: exception 'MercadoPagoException' with message 'currency_id invalid'
			 *
			 * How do we convert between currencies? 
			 *-----------------------------------------------------------------
			 *
			 * We do this by using MercadoPago's currency converter located at:
			 *
			 * https://api.mercadolibre.com/currency_conversions/search?from=<from_currency>&to=<to_currency>
			 *
			 * Forum thread (in Spanish): https://groups.google.com/forum/#!topic/mercadopago-developers/vSK5bXoJjag
			 *
			 * NOTE: According to the forum thread, this "problem" (or lack of enhancement) has been around since 2014
			 *
			 */

			public function calculateRate($from,$to){

				$url	=	'https://api.mercadolibre.com/currency_conversions/search?from=%s&to=%s';
				$url	=	sprintf($url,$from,$to);
				$rate	=	@file_get_contents($url);

				if(empty($rate)){

					$msg	=	"Could not connect to MercadoLibre currency conversion API";
					throw new \RuntimeException($msg);

				}

				$rate	=	json_decode($rate);

				if(!isset($rate->ratio)){

					$msg	=	"Wrong conversion response from MercadoLibre from \"$from\" to \"$to\"";
					throw new \RuntimeException($msg);

				}

				return floatval($rate->ratio);

			}

			public function getCalculatedRate($recalculate=FALSE){

				if(!$recalculate && $this->rate){

					return $this->rate;

				}

				if(!$this->getStoreCurrency()){

					throw new \RuntimeException("To calculate currency rate, the store currency must be set");

				}

				if(!$this->getAppCurrency()){

					throw new \RuntimeException("To calculate currency rate, the app currency must be set");

				}

				/** 
				 * If the customer currency is the same as the one set in the plugin, assume conversion of 1
				 */ 

				if($this->storeCurrency == $this->appCurrency){

					return $this->rate	=	1;

				}

				/**
				 * If the store currency does not matches the mercadopago application currency 
				 * (normally set through the backend in the plugin)
				 * convert the store currency to the application currency.
				 *
				 *
				 * NOTE: If currency conversion is needed, the value of the currency will be obtained from 
				 * the mercadopago API through an API request.
				 */

				$this->rate	=	$this->calculateRate($this->storeCurrency,$this->appCurrency);

				return $this->rate;

			}

			public function setRate($rate){

				$this->rate	=	$rate;
				return $this;

			}

			public function getRate(){

				return $this->rate;

			}

		}

	}
