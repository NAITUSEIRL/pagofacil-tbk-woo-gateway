=== PagoFácil.org - WebpayPlus PST para Woocommerce ===
Contributors: ctala
Donate link: http://cristiantala.cl/
Tags: ecommerce, payments
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: V1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows the connection between the Chilean payment gateway through PagoFacil.org.

== Description ==

This service makes receiving payments in Chile really easy. The platform is already certified so you don't need to certified your store to start receiving money.

== Installation ==

This section describes how to install the plugin and get it working.


1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Get the Tokens from : https://dashboard.pagofacil.org
1. Use the Woocommerce -> Settings-> Checkout-> PagoFacil.org WebpayPlus to configure the plugin
1. Use your token_service and token_secret in the config.


== Frequently Asked Questions ==

= NO FAQS YET =

An answer to that question.


== Changelog ==


= 1.3.1 =
* Forced Verification of montos.


= 1.3.0 =
* Added HTTPHelper
* Added Header returns for callbacks.

= 1.2 =
* Added Custom Box with order information
* Added Namespaces
* Added Complete order at the finalUrl instead of only on the callback.
* Added new version of Transaccion that includes mail.

= 1.1 =
* First release.
