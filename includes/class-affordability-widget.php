<?php
/**
 * The Razorpay Affordability Widget functionality of the plugin.
 *
 * @package    Razorpay Payment Links for WooCommerce
 * @subpackage Includes
 * @author     KnitPay
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */

// If this file is called directly, abort!!!
defined( 'ABSPATH' ) || exit;

/**
 * RZP_WC_Affordability_Widget class.
 *
 * @class Displays the Razorpay Affordability Widget on single product pages.
 */
final class RZP_WC_Affordability_Widget {

	/**
	 * The single instance of the class.
	 *
	 * @var RZP_WC_Affordability_Widget
	 */
	private static $instance = null;

	/**
	 * Settings option name of the payment gateway.
	 *
	 * @var string
	 */
	const SETTINGS_OPTION = 'woocommerce_wc-razorpay_settings';

	/**
	 * Checkout tab section id of the widget settings.
	 *
	 * @var string
	 */
	const SECTION_ID = 'rzpwc-affordability-widget';

	/**
	 * Duration of the feature verification cache.
	 *
	 * @var int
	 */
	const VERIFY_CACHE_DURATION = 12 * HOUR_IN_SECONDS;

	/**
	 * Default customization values of the widget.
	 *
	 * @var array
	 */
	private static $default_customizations = [
		'theme_color'      => '#8BBFFF',
		'heading_color'    => 'black',
		'heading_font_size' => '10',
		'content_color'    => 'grey',
		'content_font_size' => '10',
		'link_color'       => 'blue',
		'link_font_size'   => '10',
		'footer_color'     => 'grey',
		'footer_font_size' => '10',
	];

	/**
	 * Result of the most recent feature verification in the current request.
	 *
	 * @var array|null
	 */
	private $last_verify_status = null;

	/**
	 * Initialize the widget.
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		// Verify the Razorpay account after the widget settings are saved.
		add_action( 'woocommerce_update_options_payment_gateways_wc-razorpay', [ $this, 'verify_feature_after_save' ], 99 );

		// Separate checkout tab section for the widget settings, like the official plugin.
		add_filter( 'woocommerce_get_sections_checkout', [ __CLASS__, 'add_checkout_section' ] );
		add_filter( 'woocommerce_get_settings_checkout', [ __CLASS__, 'checkout_section_fields' ], 10, 2 );
		add_action( 'woocommerce_update_options_checkout_' . self::SECTION_ID, [ $this, 'verify_feature_after_save' ] );

		// Verify the Razorpay account when the widget settings page is opened.
		add_action( 'admin_init', [ $this, 'maybe_verify_feature' ] );

		add_action( 'admin_notices', [ $this, 'verify_status_notice' ] );

		add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'maybe_render_widget' ] );
	}

	/**
	 * Verify the account feature from the widget settings page.
	 */
	public function maybe_verify_feature() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Settings screen detection only.
		$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'wc-settings' !== $page || 'checkout' !== $tab ) {
			return;
		}

		if ( self::SECTION_ID !== $section && 'wc-razorpay' !== $section ) {
			return;
		}

		$this->verify_feature();
	}

	/**
	 * Wrapper to force a fresh verification once settings are saved.
	 */
	public function verify_feature_after_save() {
		$this->verify_feature( true );
	}

	/**
	 * Verify whether the Affordability Widget feature is enabled on the connected Razorpay account.
	 *
	 * @param bool $force Whether to bypass the verification cache.
	 */
	public function verify_feature( $force = false ) {
		$settings = self::get_settings();

		if ( 'yes' !== self::get_setting( $settings, 'affordability_widget', 'no' ) ) {
			$this->last_verify_status = null;
			return;
		}

		$test_mode = 'yes' === self::get_setting( $settings, 'testmode', 'no' );
		$prefix    = $test_mode ? 'test_' : '';
		$key_id    = self::get_setting( $settings, $prefix . 'key_id', '' );

		if ( empty( $key_id ) ) {
			$this->last_verify_status = [
				'type'    => 'error',
				'message' => __( 'Razorpay is not connected. Please connect your Razorpay account before enabling the Affordability Widget.', 'rzp-woocommerce' ),
			];
			RZP_WC_Payment_Gateway::log( 'Affordability Widget verification skipped: Razorpay account is not connected.', 'error' );
			return;
		}

		$transient_key = 'rzpwc_afd_feature_' . md5( $key_id );

		// A cached result belongs to the connection that was verified before it.
		// When a different connection is in use (merchant switched Razorpay
		// accounts or reconnects with different credentials), the previous
		// verification result is stale and must not gate the widget.
		$checked_for = self::get_setting( $settings, $prefix . 'affordability_widget_feature_checked_for', '' );
		if ( (string) $checked_for !== (string) $key_id ) {
			delete_transient( $transient_key );
		}

		if ( $force ) {
			delete_transient( $transient_key );
		}

		$cached = get_transient( $transient_key );
		if ( false !== $cached && is_array( $cached ) ) {
			self::update_settings(
				[
					$prefix . 'affordability_widget_feature_enabled'   => $cached['enabled'] ? 'yes' : 'no',
					$prefix . 'affordability_widget_feature_checked_for' => $key_id,
					$prefix . 'affordability_widget_feature_checked_at' => time(),
				]
			);
			if ( ! $force && isset( $cached['error'] ) ) {
				$this->last_verify_status = [
					'type'    => 'error',
					'message' => sprintf( __( 'Could not verify the Razorpay Affordability Widget feature: %s', 'rzp-woocommerce' ), esc_html( $cached['error'] ) ),
				];
			}
			return;
		}

		$mode_code = ( 0 === strpos( (string) $key_id, 'rzp_test_' ) ) ? 2 : 1;

		try {
			$enabled = $this->fetch_feature_enabled( $mode_code );
			$error   = null;
		} catch ( \Exception $e ) {
			$enabled = false;
			$error   = $e->getMessage();

			RZP_WC_Payment_Gateway::log( 'Affordability Widget feature verification failed: ' . $error, 'error' );
		}

		set_transient(
			$transient_key,
			[
				'enabled' => $enabled,
				'error'   => $error,
			],
			self::VERIFY_CACHE_DURATION
		);

		self::update_settings(
			[
				$prefix . 'affordability_widget_feature_enabled'   => $enabled ? 'yes' : 'no',
				$prefix . 'affordability_widget_feature_checked_for' => $key_id,
				$prefix . 'affordability_widget_feature_checked_at' => time(),
			]
		);

		RZP_WC_Payment_Gateway::log( 'Affordability Widget feature verification result: ' . ( $enabled ? 'Enabled' : 'Not Enabled' ) );

		if ( $error ) {
			$this->last_verify_status = [
				'type'    => 'error',
				'message' => sprintf( __( 'Could not verify the Razorpay Affordability Widget feature: %s', 'rzp-woocommerce' ), esc_html( $error ) ),
			];
		} elseif ( $enabled ) {
			$this->last_verify_status = [
				'type'    => 'success',
				'message' => __( 'The Razorpay Affordability Widget is enabled on your Razorpay account and will now appear on product pages.', 'rzp-woocommerce' ),
			];
		} else {
			$this->last_verify_status = [
				'type'    => 'warning',
				'message' => __( 'The Razorpay Affordability Widget is not enabled on your Razorpay account yet. Please activate the Affordability Suite from your Razorpay Dashboard or contact Razorpay support to enable it.', 'rzp-woocommerce' ),
			];
		}
	}

	/**
	 * Fetch the feature status from Razorpay.
	 *
	 * @param int $mode_code Mode code of the connected account. 1 for live, 2 for test.
	 * @return bool
	 * @throws Exception When both verification requests fail.
	 */
	private function fetch_feature_enabled( $mode_code ) {
		$gateway = new RZP_WC_Payment_Gateway();

		$enabled    = false;
		$last_error = null;
		$endpoints  = [
			'app/merchant/api/verify/3/' . $mode_code,
			'app/merchant/api/verify/4/' . $mode_code,
		];

		foreach ( $endpoints as $endpoint ) {
			$response = $gateway->api_data( $endpoint, null, 'GET' );

			if ( is_wp_error( $response ) ) {
				$last_error = $response->get_error_message();
				continue;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $body['error'] ) ) {
				$last_error = $body['error']['description'];
				continue;
			}

			if ( isset( $body['enabled'] ) && true === $body['enabled'] ) {
				$enabled    = true;
				$last_error = null;
			}
		}

		if ( ! $enabled && ! empty( $last_error ) ) {
			throw new \Exception( $last_error );
		}

		return $enabled;
	}

	/**
	 * Show the result of the last feature verification.
	 */
	public function verify_status_notice() {
		if ( empty( $this->last_verify_status ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$class = 'notice-' . $this->last_verify_status['type'];
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
			<p><b><?php esc_html_e( 'Razorpay Affordability Widget', 'rzp-woocommerce' ); ?></b> — <?php echo wp_kses_post( $this->last_verify_status['message'] ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the Affordability Widget on single product pages.
	 */
	public function maybe_render_widget() {
		$settings = self::get_settings();

		if ( 'yes' !== self::get_setting( $settings, 'affordability_widget', 'no' ) ) {
			return;
		}

		$test_mode = 'yes' === self::get_setting( $settings, 'testmode', 'no' );
		$prefix    = $test_mode ? 'test_' : '';

		if ( 'yes' !== self::get_setting( $settings, $prefix . 'affordability_widget_feature_enabled', 'no' ) ) {
			return;
		}

		$key_id = self::get_setting( $settings, $prefix . 'key_id', '' );
		if ( empty( $key_id ) ) {
			return;
		}

		if ( ! $this->can_display_widget( $settings, $test_mode ) ) {
			return;
		}

		if ( ! apply_filters( 'rzpwc_afd_widget_should_display', true, $settings ) ) {
			return;
		}

		global $product;
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$amount = (float) wc_get_price_to_display( $product );
		$this->print_widget( $key_id, (int) round( $amount * 100 ), $settings );
	}

	/**
	 * Can the widget be displayed to the current user.
	 *
	 * @param array $settings  Gateway settings.
	 * @param bool  $test_mode Whether the gateway is in test mode.
	 * @return bool
	 */
	private function can_display_widget( $settings, $test_mode ) {
		$preview_only = 'yes' === self::get_setting( $settings, 'affordability_widget_preview_only', 'no' );

		if ( ! $test_mode && ! $preview_only ) {
			return true;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Print the widget markup and register its assets.
	 *
	 * @param string $key      Key Id of the connected Razorpay account.
	 * @param int    $amount   Product price in smallest currency unit.
	 * @param array  $settings Gateway settings.
	 */
	private function print_widget( $key, $amount, $settings ) {
		wp_enqueue_script(
			'rzpwc-affordability-widget',
			'https://cdn.razorpay.com/widgets/affordability/affordability.js',
			[ 'jquery' ],
			null
		);

		$params = self::widget_params( $key, $amount, $settings );

		wp_localize_script( 'rzpwc-affordability-widget', 'rzpwcAfdWidget', $params );
		wp_add_inline_script( 'rzpwc-affordability-widget', $this->get_inline_script(), 'after' );

		echo '<div id="razorpay-affordability-widget"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup.
	}

	/**
	 * Build the widget configuration.
	 *
	 * @param string $key      Key Id of the connected Razorpay account.
	 * @param int    $amount   Product price in smallest currency unit.
	 * @param array  $settings Gateway settings.
	 * @return array
	 */
	public static function widget_params( $key, $amount, $settings ) {
		$offers_enabled = 'yes' === self::get_setting( $settings, 'affordability_widget_offers', 'yes' );

		$params = [
			'key'              => $key,
			'amount'           => $amount,
			'themeColor'       => self::customization( $settings, 'affordability_widget_theme_color', 'theme_color' ),
			'additionalOffers' => $offers_enabled ? self::parse_list( self::get_setting( $settings, 'affordability_widget_additional_offers', '' ) ) : [],
			'display'          => [
				'offers'     => $offers_enabled ? self::offers_display_config( $settings ) : false,
				'emi'        => self::method_display_config( $settings, 'affordability_widget_emi', 'affordability_widget_limited_emi_providers', 'issuers' ),
				'cardlessEmi' => self::method_display_config( $settings, 'affordability_widget_cardless_emi', 'affordability_widget_limited_cardless_emi_providers', 'providers' ),
				'paylater'   => self::method_display_config( $settings, 'affordability_widget_pay_later', 'affordability_widget_limited_pay_later_providers', 'providers' ),
				'widgetMain' => [
					'heading'     => [
						'color'    => self::customization( $settings, 'affordability_widget_heading_color', 'heading_color' ),
						'fontSize' => self::font_size( $settings, 'affordability_widget_heading_font_size', 'heading_font_size' ),
					],
					'content'     => [
						'color'    => self::customization( $settings, 'affordability_widget_content_color', 'content_color' ),
						'fontSize' => self::font_size( $settings, 'affordability_widget_content_font_size', 'content_font_size' ),
					],
					'link'        => [
						'color'    => self::customization( $settings, 'affordability_widget_link_color', 'link_color' ),
						'fontSize' => self::font_size( $settings, 'affordability_widget_link_font_size', 'link_font_size' ),
					],
					'footer'      => [
						'color'    => self::customization( $settings, 'affordability_widget_footer_color', 'footer_color' ),
						'fontSize' => self::font_size( $settings, 'affordability_widget_footer_font_size', 'footer_font_size' ),
						'darkLogo' => 'yes' === self::get_setting( $settings, 'affordability_widget_dark_logo', 'yes' ),
					],
				],
			],
		];

		return apply_filters( 'rzpwc_afd_widget_params', $params, $settings );
	}

	/**
	 * Build the offers display configuration.
	 *
	 * @param array $settings Gateway settings.
	 * @return array
	 */
	private static function offers_display_config( $settings ) {
		$config = [];

		$limited_offers = self::parse_list( self::get_setting( $settings, 'affordability_widget_limited_offers', '' ) );
		if ( ! empty( $limited_offers ) ) {
			$config['offerIds'] = $limited_offers;
		}

		$config['showDiscount'] = 'yes' === self::get_setting( $settings, 'affordability_widget_show_discount', 'yes' );

		return $config;
	}

	/**
	 * Build the display configuration for EMI/Cardless EMI/Pay Later methods.
	 *
	 * @param array  $settings       Gateway settings.
	 * @param string $enable_key     Setting key of the enable option.
	 * @param string $limited_key    Setting key of the limited providers option.
	 * @param string $providers_key  Key of the providers list in the widget configuration.
	 * @return bool|array
	 */
	private static function method_display_config( $settings, $enable_key, $limited_key, $providers_key ) {
		if ( 'yes' !== self::get_setting( $settings, $enable_key, 'yes' ) ) {
			return false;
		}

		$providers = self::parse_list( self::get_setting( $settings, $limited_key, '' ) );
		return empty( $providers ) ? true : [ $providers_key => $providers ];
	}

	/**
	 * Get a customized value of the widget.
	 *
	 * @param array  $settings      Gateway settings.
	 * @param string $setting_key   Setting key.
	 * @param string $default_key   Key of the default value.
	 * @return string
	 */
	private static function customization( $settings, $setting_key, $default_key ) {
		$value = sanitize_text_field( self::get_setting( $settings, $setting_key, '' ) );

		if ( '' === $value ) {
			return self::$default_customizations[ $default_key ];
		}

		return str_replace( [ '<', '>', '"', "'", '\\' ], '', $value );
	}

	/**
	 * Get a font size value of the widget.
	 *
	 * @param array  $settings     Gateway settings.
	 * @param string $setting_key  Setting key.
	 * @param string $default_key  Key of the default value.
	 * @return string
	 */
	private static function font_size( $settings, $setting_key, $default_key ) {
		$value  = self::customization( $settings, $setting_key, $default_key );
		$number = str_replace( 'px', '', $value );

		if ( '' === $number || ! ctype_digit( $number ) ) {
			$number = self::$default_customizations[ $default_key ];
		}

		return $number . 'px';
	}

	/**
	 * Get a comma separated list as an array.
	 *
	 * @param string $value Comma separated list.
	 * @return array
	 */
	public static function parse_list( $value ) {
		$list = array_map(
			function ( $item ) {
				return sanitize_text_field( trim( $item ) );
			},
			explode( ',', (string) $value )
		);

		return array_values( array_filter( $list, 'strlen' ) );
	}

	/**
	 * Get the gateway settings.
	 *
	 * @return array
	 */
	private static function get_settings() {
		$settings = get_option( self::SETTINGS_OPTION, [] );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Get a setting value.
	 *
	 * @param array  $settings Gateway settings.
	 * @param string $key      Setting key.
	 * @param mixed  $default  Default value.
	 * @return mixed
	 */
	private static function get_setting( $settings, $key, $default = 'no' ) {
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update the gateway settings.
	 *
	 * @param array $changes Settings to update.
	 */
	private static function update_settings( $changes ) {
		update_option( self::SETTINGS_OPTION, array_merge( self::get_settings(), $changes ) );
	}

	/**
	 * Widget status row displayed at the bottom of the payment gateway settings page.
	 */
	public static function gateway_page_footer() {
		$settings   = self::get_settings();
		$widget_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::SECTION_ID );

		if ( 'yes' === self::get_setting( $settings, 'affordability_widget', 'no' ) ) {
			$test_mode  = 'yes' === self::get_setting( $settings, 'testmode', 'no' );
			$prefix     = $test_mode ? 'test_' : '';
			$feature_ok = 'yes' === self::get_setting( $settings, $prefix . 'affordability_widget_feature_enabled', 'no' );

			if ( $feature_ok ) {
				$message = sprintf( __( 'The Affordability Widget is active on your product pages. Manage its settings %1$shere%2$s.', 'rzp-woocommerce' ), '<a href="' . esc_url( $widget_url ) . '">', '</a>' );
			} else {
				$message = sprintf( __( 'The Affordability Widget is enabled, but the feature has not been verified on your Razorpay account yet. Please verify %1$shere%2$s.', 'rzp-woocommerce' ), '<a href="' . esc_url( $widget_url ) . '">', '</a>' );
			}
		} else {
			$message = sprintf( __( 'NEW: Display Card EMI, Cardless EMI, Pay Later and Offer information to your customers on product pages before checkout. %1$sConfigure it now%2$s.', 'rzp-woocommerce' ), '<a href="' . esc_url( $widget_url ) . '">', '</a>' );
		}

		echo '<div class="notice notice-info inline" style="margin: 12px 0;"><p><b>' . esc_html__( 'Affordability Widget', 'rzp-woocommerce' ) . ':</b> ' . wp_kses_post( $message ) . '</p></div>';
	}

	/**
	 * Widget rendering script.
	 *
	 * @return string
	 */
	private function get_inline_script() {
		return <<<'JS'
(function ($) {
	var params = window.rzpwcAfdWidget || {};
	if (!params.key) {
		return;
	}

	var render = function (amount) {
		if (typeof RazorpayAffordabilitySuite === "undefined") {
			return;
		}

		// wp_localize_script casts top level scalars to strings and products
		// without a price resolve to 0. The widget rejects non numeric or
		// non positive amounts, so normalize and guard before rendering.
		amount = Math.round(Number(amount));
		if (!isFinite(amount) || amount <= 0) {
			return;
		}

		new RazorpayAffordabilitySuite({
			key: params.key,
			amount: amount,
			theme: { color: params.themeColor },
			features: { offers: { list: params.additionalOffers } },
			display: {
				offers: params.display.offers,
				emi: params.display.emi,
				cardlessEmi: params.display.cardlessEmi,
				paylater: params.display.paylater,
				widget: {
					main: params.display.widgetMain
				}
			}
		}).render();
	};

	$(window).on("load", function () {
		render(params.amount);
	});

	$(document).on("change", "input.variation_id", function () {
		var variationsForm = $(this).closest("form.variations_form");
		if (!variationsForm.length) {
			return;
		}

		try {
			var variations = JSON.parse(variationsForm.attr("data-product_variations") || "{}");
		} catch (e) {
			return;
		}

		var selectedId = parseInt(this.value, 10);
		var selected = null;
		$.each(variations, function (index, variation) {
			if (variation.variation_id === selectedId) {
				selected = variation;
				return false;
			}
		});

		if (selected && typeof selected.display_price !== "undefined") {
			render(Math.round(selected.display_price * 100));
		}
	});
})(jQuery);
JS;
	}

	/**
	 * Add the widget settings section to the checkout tab navigation.
	 *
	 * @param array $sections Check out tab sections.
	 * @return array
	 */
	public static function add_checkout_section( $sections ) {
		global $current_section;

		// Show a focused navigation on the plugin's payment gateway and widget settings pages.
		if ( self::SECTION_ID === $current_section || 'wc-razorpay' === $current_section ) {
			$gateway_label = isset( $sections['wc-razorpay'] ) ? $sections['wc-razorpay'] : __( 'Razorpay Payment Gateway', 'rzp-woocommerce' );

			return [
				'wc-razorpay'    => $gateway_label,
				self::SECTION_ID => __( 'Affordability Widget', 'rzp-woocommerce' ),
			];
		}

		$sections[ self::SECTION_ID ] = __( 'Affordability Widget', 'rzp-woocommerce' );

		return $sections;
	}

	/**
	 * Affordability Widget settings fields of the standalone checkout tab section.
	 *
	 * @param array  $settings Existing section settings.
	 * @param string $section  Current section id.
	 * @return array
	 */
	public static function checkout_section_fields( $settings, $section ) {
		if ( self::SECTION_ID !== $section ) {
			return $settings;
		}

		$fields = [];

		// Guide merchants without any connection to the payment gateway settings.
		$connection = get_option( self::SETTINGS_OPTION, [] );
		$connected_anywhere = ! empty( $connection['access_token'] ) || ! empty( $connection['test_access_token'] )
			|| ( ! empty( $connection['key_id'] ) && ! empty( $connection['key_secret'] ) )
			|| ( ! empty( $connection['test_key_id'] ) && ! empty( $connection['test_key_secret'] ) );

		if ( ! $connected_anywhere ) {
			$fields[] = [
				'type'        => 'notice',
				'notice_type' => 'info',
				'text'        => sprintf( __( 'Connect your Razorpay account first. %s', 'rzp-woocommerce' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc-razorpay' ) ) . '">' . __( 'Connect Razorpay', 'rzp-woocommerce' ) . '</a>' ),
			];
		}

		foreach ( self::settings_fields() as $key => $field ) {
			if ( in_array( $field['type'], [ 'title', 'sectionend', 'info' ], true ) ) {
				$field['id'] = $key;
			} else {
				$field['id']         = self::SETTINGS_OPTION . '[' . $key . ']';
				$field['field_name'] = self::SETTINGS_OPTION . '[' . $key . ']';
			}

			$field['desc'] = isset( $field['description'] ) ? $field['description'] : '';
			unset( $field['description'] );

			$fields[] = $field;
		}

		$fields[] = [
			'type' => 'sectionend',
			'id'   => 'affordability_widget_sectionend',
		];

		return $fields;
	}

	/**
	 * Affordability Widget settings fields.
	 *
	 * @return array
	 */
	public static function settings_fields() {
		return [
			'affordability_widget_section' => [
				'title'       => __( 'Affordability Widget', 'rzp-woocommerce' ),
				'type'        => 'title',
				'description' => __( 'Display the Razorpay Affordability Widget on product pages to inform your customers about EMI, Cardless EMI, Pay Later and offer based payment options before they reach checkout. The widget appears only if the Affordability feature is enabled on your Razorpay account, which is verified automatically whenever the settings are saved.', 'rzp-woocommerce' ),
			],
			'affordability_widget'         => [
				'title'       => __( 'Affordability Widget:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to display the Razorpay Affordability Widget on product pages.', 'rzp-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => false,
			],
			'affordability_widget_preview_only' => [
				'title'       => __( 'Widget Preview Mode:', 'rzp-woocommerce' ),
				'label'       => __( 'Show widget only to administrators', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to preview the widget as administrators only, before showing it to your customers.', 'rzp-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => false,
			],
			'affordability_widget_offers'  => [
				'title'       => __( 'Offers:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to display offers on the widget.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
			'affordability_widget_additional_offers' => [
				'title'       => __( 'Additional Offers:', 'rzp-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Enter Offer IDs separated by comma, for offers that did not have the "Show Offer on Checkout" option enabled at the time of creation.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_limited_offers' => [
				'title'       => __( 'Limited Offers:', 'rzp-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'In case you want to display limited offers on the widget, enter the Offer IDs separated by comma that you want to display.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_show_discount' => [
				'title'       => __( 'Show Discount Amount:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Display the exact amount of discount on offers.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
			'affordability_widget_emi'     => [
				'title'       => __( 'Card EMI:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to display Card EMI options on the widget.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
			'affordability_widget_limited_emi_providers' => [
				'title'       => __( 'Limited Card EMI Providers:', 'rzp-woocommerce' ),
				'type'        => 'textarea',
				'description' => sprintf( __( 'In case you want to display limited Card EMI options on the widget, enter the list of provider codes separated by comma. Please find the list of %1$sprovider codes%2$s here.', 'rzp-woocommerce' ), '<a target="_blank" href="https://razorpay.com/docs/payments/payment-gateway/affordability/faqs/#2-what-are-the-standard-credit-card-interest">', '</a>' ),
				'desc_tip'    => false,
			],
			'affordability_widget_cardless_emi' => [
				'title'       => __( 'Cardless EMI:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to display Cardless EMI options on the widget.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
			'affordability_widget_limited_cardless_emi_providers' => [
				'title'       => __( 'Limited Cardless EMI Providers:', 'rzp-woocommerce' ),
				'type'        => 'textarea',
				'description' => sprintf( __( 'In case you want to display limited Cardless EMI options on the widget, enter the list of provider codes separated by comma. Please find the list of %1$sprovider codes%2$s here.', 'rzp-woocommerce' ), '<a target="_blank" href="https://razorpay.com/docs/payments/payment-methods/emi/cardless-emi/">', '</a>' ),
				'desc_tip'    => false,
			],
			'affordability_widget_pay_later' => [
				'title'       => __( 'Pay Later:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to display Pay Later options on the widget.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
			'affordability_widget_limited_pay_later_providers' => [
				'title'       => __( 'Limited Pay Later Providers:', 'rzp-woocommerce' ),
				'type'        => 'textarea',
				'description' => sprintf( __( 'In case you want to display limited Pay Later options on the widget, enter the list of provider codes separated by comma. Please find the list of %1$sprovider codes%2$s here.', 'rzp-woocommerce' ), '<a target="_blank" href="https://razorpay.com/docs/payments/payment-methods/pay-later/">', '</a>' ),
				'desc_tip'    => false,
			],
			'affordability_widget_theme_color' => [
				'title'       => __( 'Theme Color:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the 6 character hex code of the theme color. Default is blue.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_heading_color' => [
				'title'       => __( 'Heading Color:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the heading color based on your requirement. Default is black.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_heading_font_size' => [
				'title'       => __( 'Heading Font Size:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the font size of the heading in px. Default is 10px.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_content_color' => [
				'title'       => __( 'Content Color:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the content color based on your requirement. Default is grey.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_content_font_size' => [
				'title'       => __( 'Content Font Size:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the font size of the content in px. Default is 10px.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_link_color' => [
				'title'       => __( 'Link Color:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the link color based on your requirement. Default is blue.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_link_font_size' => [
				'title'       => __( 'Link Font Size:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the font size of the link in px. Default is 10px.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_footer_color' => [
				'title'       => __( 'Footer Color:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the footer color based on your requirement. Default is grey.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_footer_font_size' => [
				'title'       => __( 'Footer Font Size:', 'rzp-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter the font size of the footer in px. Default is 10px.', 'rzp-woocommerce' ),
				'desc_tip'    => false,
			],
			'affordability_widget_dark_logo' => [
				'title'       => __( 'Dark Logo:', 'rzp-woocommerce' ),
				'label'       => __( 'Enable/Disable', 'rzp-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Show the black text Razorpay logo in the widget footer.', 'rzp-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => false,
			],
		];
	}
}