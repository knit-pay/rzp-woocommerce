![Razorpay Payment Links for WooCommerce](.github/banner.png "Plugin Banner")

# Razorpay Payment Links for WooCommerce

This is the Razorpay Payment Gateway plugin for WooCommerce. Allows you to accept UPI, Credit Cards, Debit Cards, Netbanking, Wallets, EMI and Apple Pay payments with the WooCommerce plugin.

[![WP compatibility](https://plugintests.com/plugins/rzp-woocommerce/wp-badge.svg)](https://plugintests.com/plugins/rzp-woocommerce/latest) [![PHP compatibility](https://plugintests.com/plugins/rzp-woocommerce/php-badge.svg)](https://plugintests.com/plugins/rzp-woocommerce/latest)

## Description

### Razorpay Payment Links for WooCommerce

This is the independent Razorpay Payment Gateway plugin for WooCommerce maintained by the Knit Pay team. Allows you to accept UPI, Credit Cards, Debit Cards, Netbanking, Wallets, EMI and Apple Pay payments with the WooCommerce plugin.

It uses a Razorpay's Payment Link API integration, allowing the customer to pay on your website being redirected to Razorpay's Secure Payment Page. This allows for refunds, works across all browsers, and is compatible with the latest WooCommerce.

#### Plugin Features

* Razorpay Payment Links API integration.
* Supports OAuth Integration - connects without API keys in just 2 clicks.
* WooCommerce High-Performance Order Storage (HPOS) Support.
* WooCommerce Blocks based Checkout Support.
* Apple Pay for international payments.
* Razorpay Affordability Widget on product pages (EMI, Cardless EMI, Pay Later & Offers).
* Ability to send Payment Links via SMS and Email notification to customers.
* Automatic Webhook configuration with payment.authorized and refund.created events.
* One time Payment for your website.
* Customized "Order Received" message.
* Mode of transaction Live and Test Mode.
* Reference Order ID & Transaction ID.
* Auto Refund from WooCommerce Order Details Section. Instant Refund supported.
* Ability to set payment link expiry time.
* Collect Gateway Fees from Customer.
* Ability to send Payment Reminder automatically.
* Secure Payment Capture Mechanism.
* 94 [Razorpay Currency](https://razorpay.com/docs/international-payments/#supported-currencies) Support.
* Order note for every Transaction related process.
* Detailed Payment process Log via WooCommerce Logger.
* Lots of filters available to customize the output.

Like Razorpay Payment Links for WooCommerce plugin? Consider leaving a [5 star review](https://wordpress.org/support/plugin/rzp-woocommerce/reviews/?rate=5#new-post).

### Compatibility

* This plugin is fully compatible with WordPress Version 4.6 and beyond and also compatible with any WordPress theme.

### Support
* Community support via the [support forums](https://wordpress.org/support/plugin/rzp-woocommerce) at wordpress.org.

### Contribute
* Active development of this plugin is handled [on GitHub](https://github.com/knit-pay/rzp-woocommerce).
* Feel free to [fork the project on GitHub](https://github.com/knit-pay/rzp-woocommerce) and submit your contributions via pull request.

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
1. Connect your Razorpay account (two-click OAuth authentication), enable/disable options as per your need and save changes.
1. Optional: to display the Affordability Widget on product pages, go to 'WooCommerce > Settings > Payments > Affordability Widget'.

### Frequently Asked Questions

#### Is there any admin interface for this plugin?

Yes. The payment gateway settings are available from 'WooCommerce > Settings > Payments > Razorpay Payment Gateway' and the Affordability Widget settings from 'WooCommerce > Settings > Payments > Affordability Widget'.

#### How to use this plugin?

Go to 'WooCommerce > Settings > Payments > Razorpay Payment Gateway', connect your Razorpay account, enable/disable options as per your need and save your changes.

#### How to display the Affordability Widget on product pages?

Go to 'WooCommerce > Settings > Payments > Affordability Widget', enable the "Affordability Widget" option and save changes. The widget appears only if the Affordability feature is enabled on your Razorpay account, which is verified automatically whenever the settings are saved.

#### How to use webhook? What webhooks are supported?

Go to Razorpay 'Dashboard > Settings > Webhooks'. Enter the URL from the plugin settings page and paste the webhook secret key into the plugin settings and save changes. By default this plugin supports only these two Webhooks: "payment.authorized" and "refund.created". If you want more webhooks supported, please feel free to contact us at https://www.knitpay.org/contact-us/ as it needs custom development.

#### How to send automatic payment reminder to customer, if customer does not make payment after initiating the payment procedure?

Enable the "Payment Reminder" option in the plugin settings. The plugin requests Razorpay to send automatic reminders for its payment links; reminder schedules are managed from your Razorpay Dashboard.

#### I want to use Razorpay Web Integration like Automatic Checkout/Manual Checkout (On site Checkout - No Redirection) with webhooks?

It needs custom development. Please contact us at https://www.knitpay.org/contact-us/.

#### I want to customize the look of the default Razorpay Gateway like colors/text etc. How can I get this?

It needs custom development. Please contact us at https://www.knitpay.org/contact-us/.

#### Which Razorpay API Type should I select?

Select 'Standard API' (Payment Links API) if your Razorpay account was created on or after 1 September, 2020 - it is the recommended option. 'Legacy API' (Invoices API) is kept only for very old accounts where the standard API does not work.

#### Is this plugin compatible with any themes?

Yes, this plugin is compatible with any theme.

#### The plugin isn't working or have a bug?

Post detailed information about the issue in the [support forum](https://wordpress.org/support/plugin/rzp-woocommerce) and the team will work to fix it.

## Changelog
[View Changelog](CHANGELOG.md)