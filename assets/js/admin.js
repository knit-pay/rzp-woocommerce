jQuery(document).ready(function() {
	jQuery('#razorpay-platform-connect-test, #razorpay-platform-connect-live').on('click', function(event) {
		event.preventDefault();

		jQuery("#razorpay-platform-connect-test").attr("disabled", "");
		jQuery("#razorpay-platform-connect-live").attr("disabled", "");

		let rzp_woocommerce_connect_action_mode = event.target.getAttribute('rzp-woocommerce-connect-mode');
		let rzp_woocommerce_connect_action_value = event.target.getAttribute('rzp-woocommerce-connect-action');

		trigger_connection_action(this, rzp_woocommerce_connect_action_mode, rzp_woocommerce_connect_action_value);
	});

	jQuery('#woocommerce_wc-razorpay_testmode').on('change', function(event) {
		let mode = 'live';
		if (this.checked) {
			mode = 'test';
		}

		let rzp_woocommerce_connect_action_value = jQuery('#razorpay-platform-connect-' + mode).attr('rzp-woocommerce-connect-action');
		if ('connect' === rzp_woocommerce_connect_action_value) {
			if (confirm('Razorpay is not connected in ' + mode + ' mode. Would you like to connect now?')) {
				trigger_connection_action(this, mode, rzp_woocommerce_connect_action_value);
			}
		}
	})
});

function trigger_connection_action(this_object, rzp_woocommerce_connect_action_mode, rzp_woocommerce_connect_action_value) {
	// Create a mode element
	var inputMode = document.createElement("input");
	inputMode.type = "hidden";
	inputMode.name = "rzp-woocommerce-connect-mode";
	inputMode.value = rzp_woocommerce_connect_action_mode;

	// Create a action element
	var inputAction = document.createElement("input");
	inputAction.type = "hidden";
	inputAction.name = "rzp-woocommerce-connect-action";
	inputAction.value = rzp_woocommerce_connect_action_value;

	// Insert the input element after the button
	this_object.parentNode.insertBefore(inputMode, this.nextSibling);
	this_object.parentNode.insertBefore(inputAction, this.nextSibling);

	// TODO confirm Google signin before continue.
	jQuery(".woocommerce-save-button").click();
}