# Shopware MercadoPago Payment Plugin 
### (by Federico Stange)

For using this plugin you need to create a mercado pago developer account at
https://www.mercadopago.com/developers/

After you have created your developer account and while signed in, obtain your 
MercadoPago credentials at https://www.mercadopago.com/mla/herramientas/aplicaciones

Configure the MercadoPago plugin in your ShopWare backend with the provided MercadoPago Credentials

Depending on your location (Argentina, Brazil, Cuba, etc) make *sure* to set the "Application Currency"
setting to the correct currency from your country.

NOTE: Argentina accepts two currencies, USD and ARS

### Configuring the plugin

Make sure that everytime you configure this plugin you clear your shop cache (See TODO list)

###UI MODES

(2017-03-05) As of this date, the iframe UI mode is not working correctly (MercadoPago Problem)

Please use any of the javascript modes (except modal) or the PHP Redirect mode

The Javascript popup mode is highly discouraged since most browsers will block said popups.

### Checkout mode

(2017-03-05) As of this date the "sandbox mode" doesn't works correctly. The purposed workaround by MercadoPago
is to use production mode and use testing credit cards provided here: 

https://www.mercadopago.com.ar/developers/es/solutions/payments/custom-checkout/test-cards/

### TODO

- [ ]	Refresh config cache for easy managment (i.e: Not having to refresh the cache manually from the backend)
- [ ]	Add support for IPN's (Instant payment notifications)
- [ ]	Add support for custom checkout methods 
