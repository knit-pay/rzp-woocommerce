<?php
/**
 * The admin-facing functionality of the plugin.
 *
 * @package    Razorpay Gateway for WooCommerce
 * @subpackage Includes
 * @author     Sayan Datta
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */

// add razorpay Gateway to woocommerce
add_filter( 'woocommerce_payment_gateways', 'rzpwc_payment_add_gateway_class' );

function rzpwc_payment_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_RZP_Woo_Gateway'; // class name
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'rzpwc_payment_gateway_class' );

function rzpwc_payment_gateway_class() {

    // If the WooCommerce payment gateway class is not available nothing will return
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
 
	class WC_RZP_Woo_Gateway extends WC_Payment_Gateway {

        /**
	     * Whether or not logging is enabled
	     *
	     * @var bool
	     */
	    public static $log_enabled = false;
    
	    /**
	     * Logger instance
	     *
	     * @var WC_Logger
	     */
	    public static $log = false;
     
 	    /**
 		 * Class constructor
 		 */
 		public function __construct() {
 
            $this->id = 'wc-razorpay'; // payment gateway plugin ID
            $this->icon = apply_filters( 'rzpwc_custom_gateway_icon', RZPWC_WOO_PLUGIN_DIR . 'includes/images/logo.png' ); // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = false; // in case need a custom credit card form
            $this->method_title = __( 'Razorpay Payment Gateway', 'rzp-woocommerce' );
            $this->method_description = __( 'Allow customers to securely pay via Razorpay Payment Links using Credit/Debit Cards, NetBanking, UPI, Wallets, QR Codes.', 'rzp-woocommerce' ); // will be displayed on the options page
            $this->order_button_text = __( 'Proceed to Payment', 'rzp-woocommerce' );
            $this->supports = array(
                'products',
				'refunds',
            );

            // Method with all the options fields
            $this->init_form_fields();
         
            // Load the settings.
            $this->init_settings();

            $this->enabled = $this->get_option( 'enabled' );
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->thank_you = $this->get_option( 'thank_you' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->key_id = $this->testmode ? $this->get_option( 'test_key_id' ) : $this->get_option( 'key_id' );
            $this->key_secret = $this->testmode ? $this->get_option( 'test_key_secret' ) : $this->get_option( 'key_secret' );
            $this->sms_notification = $this->get_option( 'sms_notification' );
            $this->email_notification = $this->get_option( 'gateway_fee' );
            $this->reminder = $this->get_option( 'reminder' );
            $this->link_expire = $this->get_option( 'link_expire' );
            $this->gateway_fee = $this->get_option( 'gateway_fee' );
            $this->debug = 'yes' === $this->get_option( 'debug_mode', 'no' );
            self::$log_enabled = $this->debug;

            if ( $this->testmode ) {
                $this->title .= ' ' . __( '(Test Mode)', 'rzp-woocommerce' );
                /* translators: %s: Link to Razorpay testing guide page */
                $this->description .= ' ' . sprintf( __( 'TESTING MODE ENABLED. You can use Razorpay testing accounts only. See the <a href="%s" target="_blank">Razorpay Testing Guide</a> for more details.', 'rzp-woocommerce' ), 'https://razorpay.com/docs/payment-gateway/test-card-details/' );
                $this->description = trim( $this->description );
            }
            
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // You can also register a webhook here
            add_action( 'woocommerce_api_rzp-payment', array( $this, 'capture_payment' ) );

            // cancel invoice on order cancel
            add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_payment_link' ), 10, 1 );

            // add custom text on thankyou page
            add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 2 );

            // change wc payment link if exists razorpay link
            add_filter( 'woocommerce_get_checkout_payment_url', array( $this, 'custom_checkout_url' ), 10, 2 );

            // set custom meta query
            add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_query_var' ), 10, 2 );

            if ( ! $this->is_valid_for_use() ) {
                $this->enabled = 'no';
            }
        }
         
        /**
	     * Logging method.
	     *
	     * @param string $message Log message.
	     * @param string $level Optional. Default 'info'. Possible values:
	     *                      emergency|alert|critical|error|warning|notice|info|debug.
	     */
	    public static function log( $message, $level = 'info' ) {
	    	if ( self::$log_enabled ) {
	    		if ( empty( self::$log ) ) {
	    			self::$log = wc_get_logger();
	    		}
	    		self::$log->log( $level, $message, array( 'source' => 'razorpay' ) );
	    	}
        }
        
        /**
	     * Processes and saves options.
	     * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	     *
	     * @return bool was anything saved?
	     */
	    public function process_admin_options() {
	    	$saved = parent::process_admin_options();
    
	    	// Maybe clear logs.
	    	if ( 'yes' !== $this->get_option( 'debug_mode', 'no' ) ) {
	    		if ( empty( self::$log ) ) {
	    			self::$log = wc_get_logger();
	    		}
	    		self::$log->clear( 'razorpay' );
	    	}
    
	    	return $saved;
        }

        /**
	     * Check if this gateway is enabled and available in the user's country.
	     *
	     * @return bool
	     */
	    public function is_valid_for_use() {
	    	return in_array(
	    		get_woocommerce_currency(),
	    		apply_filters(
	    			'rzpwc_gateway_supported_currencies',
	    			array( 'AED', 'ALL', 'AMD', 'ARS', 'AUD', 'AWG', 'BBD', 'BDT', 'BMD', 'BND', 'BOB', 'BSD', 'BWP', 'BZD', 'CAD', 'CHF', 'CNY', 'COP', 'CRC', 'CUP', 'CZK', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'GBP', 'GIP', 'GHS', 'GMD', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'JMD', 'KES', 'KGS', 'KHR', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MKD', 'MMK', 'MNT', 'MOP', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PEN', 'PGK', 'PHP', 'PKR', 'QAR', 'RUB', 'SAR', 'SCR', 'SEK', 'SGD', 'SLL', 'SOS', 'SSP', 'SVC', 'SZL', 'THB', 'TTD', 'TZS', 'USD', 'UYU', 'UZS', 'YER', 'ZAR' )
	    		),
	    		true
	    	);
        }
        
        /**
	     * Admin Panel Options.
	     * - Options for bits like 'title' and availability on a country-by-country basis.
	     *
	     * @since 1.0.0
	     */
	    public function admin_options() {
	    	if ( $this->is_valid_for_use() ) {
	    		parent::admin_options();
	    	} else {
	    		?>
	    		<div class="inline error">
	    			<p>
	    				<strong><?php esc_html_e( 'Gateway disabled', 'rzp-woocommerce' ); ?></strong>: <?php echo sprintf( __( 'Razorpay does not support your store currency. Please check the supported currency list from <a href="%s" target="_blank">here</a>.', 'rzp-woocommerce' ), 'https://razorpay.com/docs/international-payments/#supported-currencies' ); ?>
	    			</p>
	    		</div>
	    		<?php
	    	}
        }
    
		/**
	     * Initialise Gateway Settings Form Fields.
	     */
 		public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __( 'Enable/Disable:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable Razorpay Gateway', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable Razorpay Payment Gateway for this website.', 'rzp-woocommerce' ),
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'title' => array(
                    'title'       => __( 'Title:', 'rzp-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'rzp-woocommerce' ),
                    'default'     => __( 'Pay with Razorpay', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description:', 'rzp-woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => __( 'Pay securely by Credit or Debit card or Internet Banking or UPI or QR Code or Wallets through Razorpay.', 'rzp-woocommerce' ),
                ),
                'thank_you' => array(
                    'title'       => __( 'Thank You Message:', 'rzp-woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'This displays a message to customer after a successful payment is made.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => __( 'Thank you for your payment. Your transaction has been completed, and your order has been successfully placed. Please check you Email inbox for details. You can view your bank account to view transaction details.', 'rzp-woocommerce' ),
                ),
                'api_details' => array(
                    'title'       => __( 'API Credentials', 'rzp-woocommerce' ),
                    'type'        => 'title',
                    'description' => '',
                ),
                'key_id' => array(
                    'title'       => __( 'Live Client API Key:', 'rzp-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use live key for live mode.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                ),
                'key_secret' => array(
                    'title'       => __( 'Live Client Secret Key:', 'rzp-woocommerce' ),
                    'type'        => 'password',
                    'description' => __( 'The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use live secret for live mode.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                ),
                'testmode' => array(
                    'title'       => __( 'Test Mode:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable Test Mode', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Run the Razorpay Payment Gateway in test mode.', 'rzp-woocommerce' ),
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_key_id' => array(
                    'title'       => __( 'Test Client API Key:', 'rzp-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test key for test mode.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                ),
                'test_key_secret' => array(
                    'title'       => __( 'Test Client Secret Key:', 'rzp-woocommerce' ),
                    'type'        => 'password',
                    'description' => __( 'The key Id and key secret can be generated from "API Keys" section of Razorpay Dashboard. Use test secret for test mode.', 'rzp-woocommerce' ),
                    'desc_tip'    => true,
                ),
                'configure' => array(
                    'title'       => __( 'Razorpay Settings', 'rzp-woocommerce' ),
                    'type'        => 'title',
                    'description' => '',
                ),
                'sms_notification' => array(
                    'title'       => __( 'SMS Notification:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to send payment links to your customer\'s Mobile Number as a SMS.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'email_notification' => array(
                    'title'       => __( 'Email Notification:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to send payment links to your customer\'s Email Address as a Email.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'reminder' => array(
                    'title'       => __( 'Payment Reminder:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to send payment reminder alerts to your customers if they do not completed their payment yet. It only works when you will enable Payment Reminder from your Razorpay Account.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'link_expire' => array(
                    'title'       => __( 'Payment Link Auto Expire:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to auto expire payment links depending on hold stock duration. It will work only when Stock Management is enabled in WooCommerce Settings.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'gateway_fee' => array(
                    'title'       => __( 'Payment Gateway Fees:', 'rzp-woocommerce' ),
                    'label'       => __( 'Collect Razorpay Gateway Fees from Customer', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to collect the Razorpay Gateway Fee from your customers for the payments they make.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'debug_mode' => array(
                    'title'       => __( 'Debug Mode:', 'rzp-woocommerce' ),
                    'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __( 'Enable this option to view the detailed communication between the Gateway API and WooCommerce in a WooCommerce log file.', 'rzp-woocommerce' ),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
            );
 
        }
         
		/*
		 * Processing the payments
		 */
		public function process_payment( $order_id ) {
            $this->log( "Creating Razorpay Payment Link for Order ID: $order_id" );
            // we need it to get any order details
            $order = wc_get_order( $order_id );

            $amount = $order->get_total();
            $tc = ( $amount / 100 ) * 2;
            $tax = $tc + ( ( $tc / 100 ) * 18 );
            $tax = apply_filters( 'rzpwc_charge_custom_tax_amount', $tax, $amount, $order );
            if( $this->gateway_fee === 'yes' ) {
                $amount = $amount + $tax;
            }

            // api url
            $api_endpoint = 'https://api.razorpay.com/v1/invoices/';

            /*
              * Array with parameters for API interaction
             */
            $args = array(
                'type' => 'link',
                'view_less' => 1,
                'amount' => round( $amount * 100 ),
                'currency' => $order->get_currency(),
                'description' => apply_filters( 'rzpwc_payment_init_description', __( 'Order ID: ', 'rzp-woocommerce' ), $order ) . $order_id,
                'receipt' => substr( hash( 'sha256', mt_rand() . microtime() ), 0, 20 ),
                'customer' => array(
                    'name' => html_entity_decode( $order->get_formatted_billing_full_name(), ENT_QUOTES, 'UTF-8' ),
                    'email' => substr( $order->get_billing_email(), 0, 100 ),
                    'contact' => substr( $order->get_billing_phone(), -10 )
                ),
                'reminder_enable' => ( $this->reminder === 'yes' ) ? true : false,
                'sms_notify' => ( $this->sms_notification === 'yes' ) ? 1 : 0,
                'email_notify' => ( $this->email_notification === 'yes' ) ? 1 : 0,
                'notes' => array(
                    'woocommerce_order_id' => $order_id,
                    'full_name' => html_entity_decode( $order->get_formatted_billing_full_name(), ENT_QUOTES, 'UTF-8' ),
                    'email' => substr( $order->get_billing_email(), 0, 100 ),
                    'contact' => substr( $order->get_billing_phone(), -10 )
                ),
                'callback_url' => get_home_url().'/wc-api/rzp-payment/',
                'callback_method' => 'get',
            );

            $held_duration = apply_filters( 'rzpwc_payment_link_expire_duration', get_option( 'woocommerce_hold_stock_minutes' ) );
            if( $this->link_expire === 'yes' && 'yes' === get_option( 'woocommerce_manage_stock' ) && $held_duration >= 1 ) { 
                $args['expire_by'] = time() + ( absint( $held_duration ) * 60 ); 
            } 

            $args = apply_filters( 'rzpwc_payment_init_payload', $args, $order );

            $this->log( 'Data sent for creating Payment Link: ' . wc_print_r( $args, true ) );

            // get order meta
            $pay_url = $order->get_meta( '_rzp_payment_url', true );

            if( empty( $pay_url ) ) {

                $this->log( "Key ID: $this->key_id | Key Secret: $this->key_secret" );

                $auth = base64_encode( $this->key_id . ':' . $this->key_secret );
    
                /*
                 * Build API interaction
                  */
                $response = wp_remote_post( $api_endpoint, array(
                    'body'    => json_encode( $args ),
                    'headers' => array(
                        'Content-Type'   => 'application/json',
                        'Authorization'  => "Basic $auth",
                    ) )
                );
             
                if( is_wp_error( $response ) ) {

                    $this->log( 'Payment Link Generation Failed: ' . $response->get_error_message(), 'error' );
                        
                    // add error notice
                    wc_add_notice( __( 'Error Occured! Please contact with Site Administrator to resolve this issue.', 'rzp-woocommerce' ), 'error' );
                    return;

                } else {
             
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    
                    $this->log( 'Response from server on creating Payment Link: ' . wc_print_r( $body, true ) );
    
                    // check the json response from Razorpay payment processor
                    if( isset( $body['status'] ) && $body['status'] === 'issued' ) {
        
                        // we received the payment init request
                        $order->update_status( apply_filters( 'rzpwc_order_status_on_payment_init', 'pending' ) );
    
                        update_post_meta( $order->get_id(), '_rzp_receipt_id', esc_attr( $body['receipt'] ) );
                        update_post_meta( $order->get_id(), '_rzp_order_id', esc_attr( $body['order_id'] ) );
                        update_post_meta( $order->get_id(), '_rzp_invoice_id', esc_attr( $body['id'] ) );
                        update_post_meta( $order->get_id(), '_rzp_payment_url', esc_url( $body['short_url'] ) );
    
                        // add some order notes
                        $order->add_order_note( __( 'Payment is Pending against this order. URL: ', 'rzp-woocommerce' ) . esc_url( $body['short_url'] ), false );
                        
                        // Empty cart
                        WC()->cart->empty_cart();
                 
                        // Redirect to the the payment page
                        return array(
                            'result'   => 'success',
                            'redirect' => apply_filters( 'rzpwc_payment_init_redirect', esc_url( $body['short_url'] ), $order )
                        );
            
                    } elseif( isset( $body['error'] ) ) {

                        $this->log( __( 'Error Occured: ', 'rzp-woocommerce' ) . esc_attr( $body['error']['code'] ) . ' : ' . esc_attr( $body['error']['description'] ) );
                        
                        // add order note
                        $order->add_order_note( esc_attr( $body['error']['code'] ) . ' : ' . esc_attr( $body['error']['description'] ), false );
            
                        // update the order status
                        $order->update_status( 'failed' );

                        // Redirect to the the thankyou page
                        return array(
                            'result'   => 'success',
                            'redirect' => apply_filters( 'rzpwc_payment_failed_redirect', $this->get_return_url( $order ), $order )
                        );
            
                    }
             
                }

            } else {
                // add details to log
                $this->log( 'Payment Link already exists: ' . esc_url( $pay_url ) );
                // Redirect to the the payment page
                return array(
                    'result'   => 'success',
                    'redirect' => apply_filters( 'rzpwc_payment_init_redirect', esc_url( $pay_url ) )
                );
            }
        }

        /**
	     * Can the order be refunded via Razorpay?
	     *
	     * @param  WC_Order $order Order object.
	     * @return bool
	     */
	    public function can_refund_order( $order ) {
	    	$has_api_creds = false;
    
	    	if ( $this->testmode ) {
	    		$has_api_creds = $this->get_option( 'test_key_id' ) && $this->get_option( 'test_key_secret' );
	    	} else {
	    		$has_api_creds = $this->get_option( 'key_id' ) && $this->get_option( 'key_secret' );
	    	}
    
	    	return $order && $order->get_transaction_id() && ! $order->get_meta( '_rzp_refund_id', true ) && $has_api_creds;
	    }
        
        /**
	     * Process a refund if supported.
	     *
	     * @param  int    $order_id Order ID.
	     * @param  float  $amount Refund amount.
	     * @param  string $reason Refund reason.
	     * @return bool|WP_Error
	     */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            // we need it to get any order details
            $order = wc_get_order( $order_id );

            if ( ! $this->can_refund_order( $order ) ) {
                $this->log( 'Refund failed. Order ID: ' . $order->get_id() );

                return new WP_Error( 'error', __( 'Refund failed.', 'rzp-woocommerce' ) );
            }
        
            // get order meta
            $payment_id = $order->get_transaction_id();

            // build array
            $args = array();

            // amount
            if ( ! is_null( $amount ) ) {
                $args['amount'] = round( $amount * 100 );
            }

            // add notes to array
            $args['notes']['reason'] = ! empty( $reason ) ? $reason : __( 'No reason specified!', 'rzp-woocommerce' );

            $args = apply_filters( 'rzpwc_payment_refund_payload', $args, $order );

            $this->log( 'Data sent for Refund: ' . wc_print_r( $args, true ) );
        
            // api url
            $api_endpoint = 'https://api.razorpay.com/v1/payments/'.$payment_id.'/refund';

            $this->log( "Key ID: $this->key_id | Key Secret: $this->key_secret" );
        
            $auth = base64_encode( $this->key_id . ':' . $this->key_secret );
            
            /*
             * Build API interaction
              */
            $response = wp_remote_post( $api_endpoint, array(
                'body'    => json_encode( $args ),
                'headers' => array(
                    'Content-Type'   => 'application/json',
                    'Authorization'  => "Basic $auth",
                ) )
            );
    
            if( is_wp_error( $response ) ) {
                $this->log( 'Refund Capture Failed: ' . $response->get_error_message(), 'error' );
                    
                /* translators: %s: Razorpay gateway error message */
			    $order->add_order_note( sprintf( __( 'Refund could not be captured: %s', 'rzp-woocommerce' ), $response->get_error_message() ) );
                
                return new WP_Error( 'error', $response->get_error_message() );
            } else {
                 
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
    
                $this->log( 'Response from server on Refund: ' . wc_print_r( $body, true ) );
    
                // check the json response from Razorpay payment processor
                if( isset( $body['entity'] ) && $body['entity'] === 'refund' ) {
                    // add order note
                    $order->add_order_note( sprintf( __( 'Amount Refunded. Rs. %1$s<br>Refund ID: %2$s<br>Reason: %3$s' ), esc_attr( round( $body['amount'] / 100 ) ), esc_attr( $body['id'] ), esc_attr( $body['notes']['reason'] ) ), false );
                    // store refund id to meta
                    update_post_meta( $order->get_id(), '_rzp_refund_id', esc_attr( $body['id'] ) );
                    
                    return true;
                } elseif( isset( $body['error'] ) ) {
                    $this->log( __( 'Refund Error Occured: ', 'rzp-woocommerce' ) . esc_attr( $body['error']['code'] ) . ' : ' . esc_attr( $body['error']['description'] ) );
        
                    return new WP_Error( 'error', esc_attr( $body['error']['code'] ) . ' : ' . esc_attr( $body['error']['description'] ) );
                }
            }
                
            return false;
        }

        /**
	     * Process a payment capture.
	     */
        public function capture_payment() {
            // check GET veriables
            if( ! isset( $_GET['razorpay_payment_id'] ) || ! isset( $_GET['razorpay_invoice_id'] ) || ! isset( $_GET['razorpay_invoice_receipt'] ) || ! isset( $_GET['razorpay_invoice_status'] ) || ! isset( $_GET['razorpay_signature'] ) ) {
                $this->log( 'Missing the nessesary GET variables.' );
                // create redirect
                wp_safe_redirect( home_url() );
                exit;
            }

            // load get data
            $signature_payload = esc_attr( $_GET['razorpay_invoice_id'] ) . '|' . esc_attr( $_GET['razorpay_invoice_receipt'] ) . '|' . esc_attr( $_GET['razorpay_invoice_status'] ) . '|' . esc_attr( $_GET['razorpay_payment_id'] );

            $this->log( "Payload: $signature_payload" );

            // generate hash
            $expected_signature = hash_hmac( 'sha256', $signature_payload, $this->key_secret );

            $this->log( "Original Signature: " . esc_attr( $_GET['razorpay_signature'] ) );
            $this->log( "Generated Signature: $expected_signature" );

            $orders = wc_get_orders( array( 
                'txn_id' => esc_attr( $_GET['razorpay_invoice_receipt'] ),
                'limit'  => 1
            ) );

            if ( ! empty( $orders ) ) {
                if( isset( $_GET['razorpay_signature'] ) && esc_attr( $_GET['razorpay_signature'] ) === $expected_signature ) {
                    $this->log( 'Original and Generated Signature matched.' );
                    foreach ( $orders as $order ) {
                        if ( $this->id === $order->get_payment_method() ) {
                            // update the payment reference
                            $order->payment_complete( esc_attr( $_GET['razorpay_payment_id'] ) );
                            wc_reduce_stock_levels( $order->get_id() );
                            
                            // add some order notes
                            $order->add_order_note( __( 'Payment is Successful against this order.<br/>Transaction ID: ', 'rzp-woocommerce' ) . esc_attr( $_GET['razorpay_payment_id'] ), false );
                        
                            delete_post_meta( $order->get_id(), '_rzp_payment_url' );
            
                            $this->log( 'Order marked as paid successfully. Redirecting...' );

                            wp_safe_redirect( apply_filters( 'rzpwc_payment_success_redirect', $this->get_return_url( $order ), $order ) );
                            exit;
                        }
                    }

                } else {
                    $this->log( 'Original and Generated Signature not matched.' );
    
                    // update the order status
                    $order->update_status( 'failed' );
    
                    $title = apply_filters( 'rzpwc_payment_signature_verify_failed_text', __( 'Signature Verification Failed. If the money debited from your account, please Contact with Site Administrator for further action.', 'rzp-woocommerce' ) );
                        
                    wp_die( $title, get_bloginfo( 'name' ) );
                    exit;
                }

            } else {
                $this->log( 'Order containing the Valid transaction ID is not found. Aborting...' );

                $title = __( 'Order containing the Valid transaction ID is not found. If the money debited from your account, please Contact with Site Administrator for further action.', 'rzp-woocommerce' );
                    
                wp_die( $title, get_bloginfo( 'name' ) );
                exit;
            }
        }

        /**
	     * Cancel payment link if supported.
	     *
	     * @param  int    $order_id Order ID.
	     */
        public function cancel_payment_link( $order_id ) {
            // we need it to get any order details
            $order = wc_get_order( $order_id );
        
            // get order meta
            $inv_id = $order->get_meta( '_rzp_invoice_id', true );
            $pay_id = $order->get_transaction_id();
            $refund_id = $order->get_meta( '_rzp_refund_id', true );
        
            if( ! empty( $inv_id ) && empty( $pay_id ) && empty( $refund_id ) && apply_filters( 'rzpwc_cancel_link_on_order_cancel', true ) ) {
                // api url
                $api_endpoint = 'https://api.razorpay.com/v1/invoices/'.$inv_id.'/cancel';

                $this->log( "Key ID: $this->key_id | Key Secret: $this->key_secret" );
            
                $auth = base64_encode( $this->key_id . ':' . $this->key_secret );
                
                /*
                 * Build API interaction
                  */
                $response = wp_remote_post( $api_endpoint, array(
                    'headers' => array(
                        'Content-Type'   => 'application/json',
                        'Authorization'  => "Basic $auth",
                    ) )
                );
        
                if( is_wp_error( $response ) ) {

                    $this->log( 'Link Cancellation Capture Failed: ' . $response->get_error_message(), 'error' );
                        
                    /* translators: %s: Razorpay gateway error message */
				    $order->add_order_note( sprintf( __( 'Link Cancellation could not be captured: %s', 'rzp-woocommerce' ), $response->get_error_message() ) );
				    return;

                } else {
                     
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
                    $this->log( 'Response from server on Link Cancellation: ' . wc_print_r( $body, true ) );
        
                    // check the json response from Razorpay payment processor
                    if( isset( $body['status'] ) && $body['status'] === 'cancelled' ) {
                        // add order note
                        $order->add_order_note( __( 'Invoice Link Cancelled.', 'rzp-woocommerce' ), false );
                        // remove old payment link
                        delete_post_meta( $order->get_id(), '_rzp_payment_url' );
                        
                    } elseif( isset( $body['error'] ) ) {
                        $this->log( __( 'Link Cancellation Error Occured: ', 'rzp-woocommerce' ) . esc_attr( $body['error']['code'] ) . ' : ' . esc_attr( $body['error']['description'] ) );
            
                        // add order note
                        $order->add_order_note( $body['error']['code'] . ' : ' . $body['error']['description'], false );
                    } 
                }
            }
        }

        /**
	     * Custom Razorpay order received text.
	     *
	     * @param string   $text Default text.
	     * @param WC_Order $order Order data.
	     * @return string
	     */
	    public function order_received_text( $text, $order ) {
	    	if ( $this->id === $order->get_payment_method() && ! empty( $this->thank_you ) ) {
	    		return esc_html( $this->thank_you );
	    	}
    
	    	return $text;
        }
        
        /**
	     * Custom Razorpay checkout URL.
	     *
	     * @param string   $url Default URL.
	     * @param WC_Order $order Order data.
	     * @return string
	     */
	    public function custom_checkout_url( $url, $order ) {
            $pay_url = $order->get_meta( '_rzp_payment_url', true );
	    	if ( $this->id === $order->get_payment_method() && ! empty( $pay_url ) && apply_filters( 'rzpwc_custom_checkout_url', true ) ) {
	    		return esc_url( $pay_url );
	    	}
    
	    	return $url;
        }

        /**
         * Handle a custom 'txn_id' query var to get orders with the '_rzp_receipt_id' meta.
         * @param array $query - Args for WP_Query.
         * @param array $query_vars - Query vars from WC_Order_Query.
         * @return array modified $query
         */
        public function handle_custom_query_var( $query, $query_vars ) {
        	if ( ! empty( $query_vars['txn_id'] ) ) {
        		$query['meta_query'][] = array(
        			'key' => '_rzp_receipt_id',
        			'value' => esc_attr( $query_vars['txn_id'] ),
        		);
        	}
        
        	return $query;
        }
 	}
}