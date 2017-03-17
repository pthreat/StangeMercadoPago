# Shopware 5.2 MercadoPago Payment Plugin 
### (by Federico Stange)

## First Steps

For using this plugin you need to create a mercado pago developer account at
https://www.mercadopago.com/developers/

After you have created your developer account and while signed in, obtain your 
MercadoPago credentials at https://www.mercadopago.com/mla/herramientas/aplicaciones

Configure the MercadoPago plugin in your ShopWare backend with the provided MercadoPago Credentials

Depending on your location (Argentina, Brazil, Cuba, etc) make *sure* to set the "Application Currency"
setting to the correct currency from your country.

NOTE: Argentina accepts two currencies, USD and ARS

---

## Plugin installation 

### Manual

As of now the plugin will not show up in the backend plugin list since I haven't submitted it to the ShopWare 
plugin store yet.

For installing it, get into your shopware directory, i.e: cd /var/www/shopware

Now get into the custom/plugins folder

Run the command: 

git clone https://github.com/pthreat/StangeMercadoPago.git

Now get into to the plugin directory:

cd StangeMercadoPago

Run:  ../../../composer.phar install 

Get back into your shopware directory (in this example /var/www/shopware)

run php bin/console sw:plugin:refresh

run php bin/console sw:plugin:install --activate StangeMercadoPago

### Composer

Simply run:

```
composer require stange/shopware-mercadopago
```

After running the command, move the vendor/stange/shopware-mercadopago folder to custom/plugins/StangeMercadoPago

Just in case, cd to custom/plugins/StangeMercadoPago and then run composer install

---

## Configuring the plugin

Login into your backend and open up the plugin manager

Look under the tab installed plugins, configure the plugin and save the configuration.

Refresh the shop cache (Under Configuration -> Cache/Performance -> Clear Shop Cache)

That's it! You should be ready to use the MercadoPago checkout plugin!

NOTE: Make sure that everytime you configure this plugin you clear your shop cache (See TODO list)


### IPN: Instant payment notifications

For getting to save any kind of payment in your shopware store through basic checkout, you need to configure MercadoPago's 
Instant Payment Notifications (or IPN's for short).

For doing so, you must login into your mercado pago developer account and go to 

https://www.mercadopago.com.ar/ipn-notifications

Please notice that this link varies according to your country

Once there, enter your shop URL with the following path:

http://YOUR-shop-name.com*/MercadoPago/ipn*

Once this is set, you can start receiveing payment notifications from mercadopago into your Shopware store.

NOTE: If you are using test users, make sure to login with your seller test user and configure the IPN.

### UI MODE Configuration

The plugin admits several UI modes for operation:

[-] php redirect
[-] Iframe
[-] Javascript redirect
[-] Javascript modal
[-] Javascript new window

(2017-03-05) As of this date, the iframe UI mode is not working correctly (MercadoPago Problem)
The Javascript popup and redirect modes are highly discouraged since most browsers will block said popups.

The most safe option here is to use the php redirect mode.

### Application currency configuration

It is *very important* that you set the *correct* currency matching your mercadopago account given your geographical location

### Checkout mode Configuration

The plugin admits two configuration modes, production and sandbox

(2017-03-05) As of this date the "sandbox mode" doesn't works correctly. The purposed workaround by MercadoPago
is to use production mode and use testing credit cards and testing users provided here: 

https://www.mercadopago.com.ar/developers/es/solutions/payments/custom-checkout/test-cards/

### Using test cards and test users:

For testing you will need to create two test users before using the testing credit cards.

You can create said users through the Shopware's command line once the plugin is *installed and configured*.
 
For doing so I have created a simple console command that will do just that.

Please run: 

```

php bin/console stange:mercadopago:create:test-user

```
Run it twice, one for creating the Buyer test user, and once more for creating the Seller user

After creating the test users, login into mercadopago with the selling user and obtain your application credentials.

Reconfigure your shopware backend to use the test user client id and client secret.

Go through the checkout process and when redirected to the mercadopago page login with your Buyer credentials

Grab any test card that belongs to your country here:

https://www.mercadopago.com.ar/developers/es/solutions/payments/custom-checkout/test-cards/

Use said card in the checkout process, don't worry about the PIN number (you can put any 3 digit number there)

Same goes for the expiration date, but remember that the expiration date should be set in the future.

---

## UPDATES

Added IPN Notification support for basic checkout
Added a handy command for developers to be able to create mercadopago test users (stange:mercadopago:create:test-user)

---

## Running tests

For running the integration tests your plugin must be properly configured.
While testing, please, use the Shopware's shipped phpunit binary located at shopware-folder/vendor/bin/phpunit

---

## TODO

- [ ]	Refresh config cache for easy managment (i.e: Not having to refresh the cache manually from the backend every time the plugin is configured)
- [ ]	Add support for custom checkout methods (On store through mercadopago js sdk)
- [ ] Enable other types of checkout (Rapipago, PagoFacil)

