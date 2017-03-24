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

### IPN: Instant payment notifications

There's no additional need to configure any URL on the MercadoPago backend.
Just have your store available to the outside world internet and you will get Instant Payment Notifications.

### UI MODE Configuration

The UI MODE option was removed to provide simplicity and correct operation of the plugin.

### Application currency configuration

It is *very important* that you set the *correct* currency matching your mercadopago account given your geographical location

### Checkout mode Configuration

The plugin admits two configuration modes, production and sandbox

(2017-03-05) As of this date the "sandbox mode" doesn't works correctly. The purposed workaround by MercadoPago
is to use production mode and use testing credit cards and testing users provided here: 

https://www.mercadopago.com.ar/developers/es/solutions/payments/custom-checkout/test-cards/

### Console commands

This plugin makes available the following console commands:

#### Create a mercado pago test user

```
php bin/console stange:mercadopago:create:test-user
```
#### Check the exchange rate between two currencies

```
php bin/console stange:mercadopago:currency:exchange <from_curency> <to_currency> [amount]
```

---

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

## Running tests

For running the integration tests your plugin must be properly configured.
While testing, please, use the Shopware's shipped phpunit binary located at shopware-folder/vendor/bin/phpunit

---
## For coders

### Coding style
The indentation style of the code must be respected. I use tabs only. Set your editor to have a tab stop of 3 for the code to display correctly in your editor.

### Namespaces

A personal preference of mine is to surround with the namespace declaration.

```php
namespace StangeMercadoPago{
   class Test{
	}
}
```
I believe this way makes you conscious that you are working within a namespace and not in the global namespace.

### Collaborating

Simply send a pull request through github, send me an email or add an issue in github.

---

## CHANGELOG

#### Version 1.0.1

- [ ] Removed UI Modes for simplicity and correct plugin operation
- [ ] Added success, cancel and pending payment pages. When the user finishes buying on mercadopago the user will be redirected to your shop's success, cancel or pending page. Note that these pages can not be trusted for finishing the checkout process since mercadopago has a delay of 5 seconds in which the customer may close the window. For this reason, Instant Payment Notifications are prefered instead.
- [ ] Added small localization .ini files (Deutsch, English)

---

## TODO

- [ ] Enable edition of allowed checkout methods through the backend
- [ ] Enable edition of checkout methods preferences (cc installments, etc)

