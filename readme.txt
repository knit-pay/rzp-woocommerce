=== Razorpay Gateway for WooCommerce ===
Contributors: infosatech
Tags: razorpay, qrcode, upi, woocommerce, PaywithRazorpay
Requires at least: 4.6
Tested up to: 5.3
Stable tag: 1.0.1
Requires PHP: 5.6
Donate link: https://www.paypal.me/iamsayan
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

The easiest and most secure solution to collect payments with WooCommerce. Allow customers to securely pay via Razorpay (Credit/Debit Cards, NetBanking, UPI, Wallets, QR Code).

== Description ==

### Razorpay Gateway for WooCommerce

This is the Un-Official Razorpay Payment Gateway plugin for WooCommerce. Allows you to accept Credit Cards, Debit Cards, Netbanking, Wallets, and UPI Payments with the WooCommerce plugin.

It uses a Razorpay's Payment Link API integration, allowing the customer to pay on your website being redirected to Razorpay's Secure Payment Page. This allows for refunds, works across all browsers, and is compatible with the latest WooCommerce.

#### Plugin Features

* Collect Payments using Razorpay Payment Links.
* Ability to send Payment Links via SMS and Email notification to customers.
* One time Payment for your website.
* Customized "Order Received" message.
* Mode of transaction Live and Test Mode.
* Reference Order ID & Transaction ID.
* Auto Refund from WooCommerce Order Details Section.
* Ability to set payment link expiry time.
* Collect Gateway Fees from Customer.
* Ability to send Payment Reminder automatically.
* Secure Payment Capture Mechanism.
* 92 [Razorpay Currency](https://razorpay.com/docs/international-payments/#supported-currencies) Support.
* Order note for every Transaction related process.
* Detailed Payment process Log via WooCommerce Logger.
* Lots of filters available to customize the output.

Like Razorpay Gateway for WooCommerce plugin? Consider leaving a [5 star review](https://wordpress.org/support/plugin/rzp-woocommerce/reviews/?rate=5#new-post).

#### Compatibility

* This plugin is fully compatible with WordPress Version 4.6 and WooCommerce v3.0.0 and beyond and also compatible with any WordPress theme.

#### Support
* Community support via the [support forums](https://wordpress.org/support/plugin/rzp-woocommerce) at WordPress.org.

#### Contribute
* Active development of this plugin is handled [on GitHub](https://github.com/iamsayan/rzp-woocommerce).
* Feel free to [fork the project on GitHub](https://github.com/iamsayan/rzp-woocommerce) and submit your contributions via pull request.

== Installation ==

1. Visit 'Plugins > Add New'.
1. Search for 'Razorpay Gateway for WooCommerce' and install it.
1. Or you can upload the `rzp-woocommerce` folder to the `/wp-content/plugins/` directory manually.
1. Activate Razorpay Gateway for WooCommerce from your Plugins page.
1. After activation go to 'WooCommerce > Settings > Payments > Razorpay Payment Gateway'.
1. Enable options and save changes.

== Frequently Asked Questions ==

= Is there any admin interface for this plugin? =

Yes. You can access this from 'WooCommerce > Settings > Payments > Razorpay Payment Gateway'.

= How to use this plugin? =

Go to 'WooCommerce > Settings > Payments > Razorpay Payment Gateway', enable/disable options as per your need and save your changes.

= Is this plugin compatible with any themes? =

Yes, this plugin is compatible with any theme.

= The plugin isn't working or have a bug? =

Post detailed information about the issue in the [support forum](https://wordpress.org/support/plugin/rzp-woocommerce) and I will work to fix it.

== Screenshots ==

1. Admin Dashboard
2. Checkout Page
3. Payment Page
4. Payment Success Page
5. Order History
6. Refund Area

== Changelog ==

If you like Razorpay Gateway for WooCommerce, please take a moment to [give a 5-star rating](https://wordpress.org/support/plugin/rzp-woocommerce/reviews/?rate=5#new-post). It helps to keep development and support going strong. Thank you!

= 1.0.1 =
Release Date: January 30, 2020

* Added: A filter `rzpwc_charge_custom_tax_amount` to set custom tax amount on cart total.
* Added: A filter `rzpwc_payment_success_redirect` to set custom redirect url after successful payment verification.
* Improved: Payment verfication mechanism.
* Tweak: API Secret Key fields are now password type fields.
* Fixed: Minor bugs.

= 1.0.0 =
Release Date: January 25, 2020

* Initial release.