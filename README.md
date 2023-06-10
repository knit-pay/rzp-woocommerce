![Razorpay Payment Links for WooCommerce](.github/banner.png "Plugin Banner")

# Razorpay Payment Links for WooCommerce

This is the Un-Official Razorpay Payment Gateway plugin for WooCommerce. Allows you to accept Credit Cards, Debit Cards, Netbanking, Wallets, and UPI Payments with the WooCommerce plugin.

[![WP compatibility](https://plugintests.com/plugins/rzp-woocommerce/wp-badge.svg)](https://plugintests.com/plugins/rzp-woocommerce/latest) [![PHP compatibility](https://plugintests.com/plugins/rzp-woocommerce/php-badge.svg)](https://plugintests.com/plugins/rzp-woocommerce/latest)

## Description

### Razorpay Payment Links for WooCommerce

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

Like Razorpay Payment Links for WooCommerce plugin? Consider leaving a [5 star review](https://wordpress.org/support/plugin/rzp-woocommerce/reviews/?rate=5#new-post).

### Compatibility

* This plugin is fully compatible with WordPress Version 4.6 and beyond and also compatible with any WordPress theme.

### Support
* Community support via the [support forums](https://wordpress.org/support/plugin/rzp-woocommerce) at wordpress.org.

### Contribute
* Active development of this plugin is handled [on GitHub](https://github.com/iamsayan/rzp-woocommerce).
* Feel free to [fork the project on GitHub](https://github.com/iamsayan/rzp-woocommerce) and submit your contributions via pull request.

## Installation

### From within WordPress
1. Visit 'Plugins > Add New'.
1. Search for 'Razorpay Payment Links for WooCommerce'.
1. Activate Razorpay Payment Links for WooCommerce from your Plugins page.
1. Go to "after activation" below.

### Manually
1. Upload the `rzp-woocommerce` folder to the `/wp-content/plugins/` directory.
1. Activate Razorpay Payment Links for WooCommerce plugin through the 'Plugins' menu in WordPress.
1. Go to "after activation" below.

### After activation
1. After activation go to 'WooCommerce > Settings > Payments > Razorpay Payment Gateway'.
1. Enable/disable options and save changes.

### Frequently Asked Questions

#### Is there any admin interface for this plugin?

Yes. You can access this from 'WooCommerce > Settings > Payments > Razorpay Payment Gateway'.

#### How to use this plugin?

Go to 'WooCommerce > Settings > Payments > Razorpay Payment Gateway', enable/disable options as per your need and save your changes.

#### How to use webhook? What webhooks are supported? =

Go to Razorpay 'Dashboard > Settings > Webhooks'. Enter the URL from plugin settings page and create and copy webhook secret key and paste it to plugin settings and save changes. By Default this plugin supports only these two Webhooks: "payment.authorized" and "refund.created". If you want more webhooks supported, please feel free to contact me at iamsayan@protonmail.com or https://www.sayandatta.co.in/contact/ as it needs custom developmet. 

#### How to send automatic payment reminder to customer, if customer does not make payment after initiating the payment procedure? =

It needs custom developement. Please contact me at iamsayan@protonmail.com or https://www.sayandatta.co.in/contact/.

#### I want to use Razorpay Web Integration like Automatic Checkout/Manual Checkout (On site Checkout - No Redirection) with webhooks? =

It needs custom developement. Please contact me at iamsayan@protonmail.com or https://www.sayandatta.co.in/contact/.

#### I want to customize the look of the default Razorpay Gateway like colors/text etc. How can I get this? =

It needs custom developement. Please contact me at iamsayan@protonmail.com or https://www.sayandatta.co.in/contact/.

#### Is this plugin compatible with any themes?

Yes, this plugin is compatible with any theme.

#### The plugin isn't working or have a bug?

Post detailed information about the issue in the [support forum](https://wordpress.org/support/plugin/rzp-woocommerce) and I will work to fix it.

## Changelog
[View Changelog](CHANGELOG.md)