=== Stark ===
Contributors: starkpayments
Tags: bitcoin,digital currency, ethereum,bitcoincash,stark,cryptocurrency
Requires at least: 4.4 or higher
Requires PHP: 5.6 or higher
Tested up to: 5.5
License: MIT

Pay using a digital currency, such as Bitcoin, Ethereum or BitcoinCash from any wallet via Stark

== Description ==
The Stark plugin extends WooCommerce allowing you to take payments with cryptocurrencies like bitcoin, bitcoin cash, ethereum etc.

== Installation ==
* Install the WooCommerce Stark Plugin
* Activate the plugin
* Go to the WooCommerce Settings Page
* Access Payment Gateways Tab
* Select “Stark”
* Check the Enable/Disable Checkbox.
* Enter the API Key which you will get from stark dashboard [Stark dashboard](https://dashboard.starkpayments.net)

== Frequently Asked Questions ==

= How to update Webhook? = 

To update the payment status automatically you should configure your webhook URL in the Stark dashboard. Your webhook URL will be WordPress domain name + \"?wc-api=wc_gateway_stark\"
ex: https://mydomain.com/?wc-api=wc_gateway_stark