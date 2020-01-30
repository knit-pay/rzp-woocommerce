<?php
/**
 * Plugin Name: Razorpay Gateway for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/rzp-woocommerce/
 * Description: The easiest and most secure solution to collect payments with WooCommerce. Allow customers to securely pay via Razorpay (Credit/Debit Cards, NetBanking, UPI, Wallets, QR Code).
 * Version: 1.0.1
 * Author: Sayan Datta
 * Author URI: https://about.me/iamsayan
 * License: GPLv3
 * Text Domain: rzp-woocommerce
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 3.9.0
 * 
 * Razorpay Gateway for WooCommerce is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Razorpay Gateway for WooCommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Razorpay Gateway for WooCommerce. If not, see <http://www.gnu.org/licenses/>.
 * 
 * @category WooCommerce
 * @package  Razorpay Gateway for WooCommerce
 * @author   Sayan Datta <iamsayan@pm.me>
 * @license  http://www.gnu.org/licenses/ GNU General Public License
 * @link     https://wordpress.org/plugins/rzp-woocommerce/
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$consts = array(
    'RZPWC_WOO_PLUGIN_VERSION'     => '1.0.1', // plugin version
    'RZPWC_WOO_PLUGIN_BASENAME'    => plugin_basename( __FILE__ ),
    'RZPWC_WOO_PLUGIN_DIR'         => plugin_dir_url( __FILE__ )
);

foreach( $consts as $const => $value ) {
    if ( ! defined( $const ) ) {
        define( $const, $value );
    }
}

// Internationalization
add_action( 'plugins_loaded', 'rzpwc_plugin_load_textdomain' );

/**
 * Load plugin textdomain.
 * 
 * @since 1.0.0
 */
function rzpwc_plugin_load_textdomain() {
    load_plugin_textdomain( 'rzp-woocommerce', false, dirname( RZPWC_WOO_PLUGIN_BASENAME ) . '/languages/' ); 
}

// register activation hook
register_activation_hook( __FILE__, 'rzpwc_plugin_activation' );

// register deactivation hook
register_deactivation_hook( __FILE__, 'rzpwc_plugin_deactivation' );

function rzpwc_plugin_activation() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    set_transient( 'rzpwc-admin-notice-on-activation', true, 5 );
}

function rzpwc_plugin_deactivation() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    delete_option( 'rzpwc_plugin_dismiss_rating_notice' );
    delete_option( 'rzpwc_plugin_no_thanks_rating_notice' );
    delete_option( 'rzpwc_plugin_installed_time' );
}

// plugin action links
add_filter( 'plugin_action_links_' . RZPWC_WOO_PLUGIN_BASENAME, 'rzpwc_add_action_links', 10, 2 );

// plugin row elements
add_filter( 'plugin_row_meta', 'rzpwc_plugin_meta_links', 10, 2 );

// add action links
function rzpwc_add_action_links( $links ) {
    $rzpwclinks = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-razorpay' ) . '">' . __( 'Settings', 'rzp-woocommerce' ) . '</a>',
    );
    return array_merge( $rzpwclinks, $links );
}

function rzpwc_plugin_meta_links( $links, $file ) {
    $plugin = RZPWC_WOO_PLUGIN_BASENAME;
    if ( $file == $plugin ) // only for this plugin
        return array_merge( $links, 
            array( '<a href="https://wordpress.org/support/plugin/rzp-woocommerce/" target="_blank">' . __( 'Support', 'rzp-woocommerce' ) . '</a>' ),
            array( '<a href="https://wordpress.org/plugins/rzp-woocommerce/#faq" target="_blank">' . __( 'FAQ', 'rzp-woocommerce' ) . '</a>' ),
            array( '<a href="https://www.paypal.me/iamsayan/" target="_blank">' . __( 'Donate', 'rzp-woocommerce' ) . '</a>' )
        );
    return $links;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/payment.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/notice.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/donate.php';