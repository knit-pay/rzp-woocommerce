<?php

/**
 * The admin-facing functionality of the plugin.
 *
 * @package    Razorpay Payment Links for WooCommerce
 * @subpackage Includes
 * @author     Sayan Datta
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */

add_action( 'admin_notices', 'rzpwc_rating_admin_notice' );
add_action( 'admin_init', 'rzpwc_dismiss_rating_admin_notice' );

function rzpwc_rating_admin_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $show_rating = true;
    if ( rzpwc_plugin_get_installed_time() > strtotime( '-10 days' )
        || '1' === get_option( 'rzpwc_plugin_dismiss_rating_notice' )
        || apply_filters( 'rzpwc/hide_sticky_rating_notice', false ) ) {
        $show_rating = false;
    }

    if ( $show_rating ) {
        $dismiss = wp_nonce_url( add_query_arg( 'rzpwc_notice_action', 'dismiss_rating' ), 'rzpwc_notice_nonce' );
        $no_thanks = wp_nonce_url( add_query_arg( 'rzpwc_notice_action', 'no_thanks_rating' ), 'rzpwc_notice_nonce' ); ?>
        
        <div class="notice notice-success">
            <p><?php echo wp_kses_post( 'Hey, I noticed you\'ve been using Razorpay Payment Links for WooCommerce for more than 1 week – that’s awesome! Could you please do me a BIG favor and give it a <strong>5-star</strong> rating on WordPress? Just to help us spread the word and boost my motivation.', 'rzp-woocommerce' ); ?></p>
            <p><a href="https://wordpress.org/support/plugin/rzp-woocommerce/reviews/?filter=5#new-post" target="_blank" class="button button-secondary" rel="noopener"><?php esc_html_e( 'Ok, you deserve it', 'rzp-woocommerce' ); ?></a>&nbsp;
            <a href="<?php echo esc_url( $dismiss ); ?>" class="already-did"><strong><?php esc_html_e( 'I already did', 'rzp-woocommerce' ); ?></strong></a>&nbsp;<strong>|</strong>
            <a href="<?php echo esc_url( $no_thanks ); ?>" class="later"><strong><?php esc_html_e( 'Nope&#44; maybe later', 'rzp-woocommerce' ); ?></strong></a></p>
        </div>
        <?php
    }

    $show_donate = true;
    if ( rzpwc_plugin_get_installed_time() > strtotime( '-240 hours' )
        || '1' === get_option( 'rzpwc_plugin_dismiss_donate_notice' )
        || apply_filters( 'rzpwc/hide_sticky_donate_notice', false ) ) {
        $show_donate = false;
    }

    if ( $show_donate ) {
        $dismiss = wp_nonce_url( add_query_arg( 'rzpwc_notice_action', 'dismiss_donate' ), 'rzpwc_notice_nonce' );
        $no_thanks = wp_nonce_url( add_query_arg( 'rzpwc_notice_action', 'no_thanks_donate' ), 'rzpwc_notice_nonce' ); ?>
        
        <div class="notice notice-success">
            <p><?php echo wp_kses_post( 'Hey, I noticed you\'ve been using Razorpay Payment Links for WooCommerce for more than 2 week – that’s awesome! If you like Razorpay Payment Links for WooCommerce and you are satisfied with the plugin, isn’t that worth a coffee or two? Please consider donating. Donations help me to continue support and development of this free plugin! Thank you very much!', 'rzp-woocommerce' ); ?></p>
            <p><a href="https://rzp.io/l/Bq3W5pr" target="_blank" class="button button-secondary" rel="noopener"><?php esc_html_e( 'Donate Now', 'rzp-woocommerce' ); ?></a>&nbsp;
            <a href="<?php echo esc_url( $dismiss ); ?>" class="already-did"><strong><?php esc_html_e( 'I already donated', 'rzp-woocommerce' ); ?></strong></a>&nbsp;<strong>|</strong>
            <a href="<?php echo esc_url( $no_thanks ); ?>" class="later"><strong><?php esc_html_e( 'Nope&#44; maybe later', 'rzp-woocommerce' ); ?></strong></a></p>
        </div>
        <?php
    }
}

function rzpwc_dismiss_rating_admin_notice() {
    // Check for Rating Notice
	if ( get_option( 'rzpwc_plugin_no_thanks_rating_notice' ) === '1'
        && get_option( 'rzpwc_plugin_dismissed_time' ) <= strtotime( '-14 days' ) ) {
        delete_option( 'rzpwc_plugin_dismiss_rating_notice' );
        delete_option( 'rzpwc_plugin_no_thanks_rating_notice' );
    }

    // Check for Donate Notice
    if ( get_option( 'rzpwc_plugin_no_thanks_donate_notice' ) === '1'
        && get_option( 'rzpwc_plugin_dismissed_time_donate' ) <= strtotime( '-15 days' ) ) {
        delete_option( 'rzpwc_plugin_dismiss_donate_notice' );
        delete_option( 'rzpwc_plugin_no_thanks_donate_notice' );
    }

    if ( ! isset( $_REQUEST['rzpwc_notice_action'] ) || empty( $_REQUEST['rzpwc_notice_action'] ) ) {
        return;
    }

    check_admin_referer( 'rzpwc_notice_nonce' );

    $notice = sanitize_text_field( $_REQUEST['rzpwc_notice_action'] );
    $notice = explode( '_', $notice );
    $notice_type = end( $notice );
    array_pop( $notice );
    $notice_action = join( '_', $notice );

    if ( 'dismiss' === $notice_action ) {
        update_option( 'rzpwc_plugin_dismiss_' . $notice_type . '_notice', '1' );
    }

    if ( 'no_thanks' === $notice_action ) {
        update_option( 'rzpwc_plugin_no_thanks_' . $notice_type . '_notice', '1' );
        update_option( 'rzpwc_plugin_dismiss_' . $notice_type . '_notice', '1' );
        if ( 'donate' === $notice_type ) {
            update_option( 'rzpwc_plugin_dismissed_time_donate', time() );
        } else {
            update_option( 'rzpwc_plugin_dismissed_time', time() );
        }
    }

    wp_redirect( remove_query_arg( [ 'rzpwc_notice_action', '_wpnonce' ] ) );
    exit;
}

function rzpwc_plugin_get_installed_time() {
    $installed_time = get_option( 'rzpwc_plugin_installed_time' );
    if ( ! $installed_time ) {
        $installed_time = time();
        update_option( 'rzpwc_plugin_installed_time', $installed_time );
    }
    return $installed_time;
}