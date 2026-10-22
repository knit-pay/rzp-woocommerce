jQuery(document).ready(function() {
	var params = window.rzpwcAdmin || {};

	// Connect / Disconnect actions of the connection panel.
	jQuery(document).on('click', '.rzpwc-connect, .rzpwc-disconnect', function(event) {
		event.preventDefault();

		var action = jQuery(this).hasClass('rzpwc-disconnect') ? 'disconnect' : 'connect';
		var mode = jQuery(this).attr('data-rzpwc-mode');

		if ('disconnect' === action) {
			if (!window.confirm(params.confirm_disconnect || 'Disconnect your Razorpay account in this mode?')) {
				return;
			}
		}

		jQuery('.rzpwc-connect, .rzpwc-disconnect').addClass('is-busy');
		trigger_connection_action(this, mode, action);
	});

	// Offer to connect the mode that is not connected when Test Mode is switched.
	jQuery('#woocommerce_wc-razorpay_testmode').on('change', function(event) {
		var mode = this.checked ? 'test' : 'live';

		var connect_button = jQuery('.rzpwc-connect[data-rzpwc-mode="' + mode + '"]');
		if ( ! connect_button.length ) {
			return;
		}

		var connect_notice = 'test' === mode ? ( params.confirm_test || 'Razorpay is not connected in Test Mode yet. Would you like to connect it now?' ) : ( params.confirm_live || 'Razorpay is not connected in Live Mode yet. Would you like to connect it now?' );

		if (window.confirm(connect_notice)) {
			jQuery('.rzpwc-connect, .rzpwc-disconnect').addClass('is-busy');
			trigger_connection_action(connect_button[0], mode, 'connect');
		}
	});
});

function trigger_connection_action(this_object, rzp_woocommerce_connect_action_mode, rzp_woocommerce_connect_action_value) {
	// Create a mode element
	var inputMode = document.createElement('input');
	inputMode.type = 'hidden';
	inputMode.name = 'rzp-woocommerce-connect-mode';
	inputMode.value = rzp_woocommerce_connect_action_mode;

	// Create a action element
	var inputAction = document.createElement('input');
	inputAction.type = 'hidden';
	inputAction.name = 'rzp-woocommerce-connect-action';
	inputAction.value = rzp_woocommerce_connect_action_value;

	// Insert the input elements and submit the settings form.
	var form = this_object.closest('form');
	if ( ! form ) {
		form = document.getElementById('mainform');
	}
	if ( form ) {
		form.appendChild(inputMode);
		form.appendChild(inputAction);
	}

	var submit_connection_action = function () {
		// Enable Save Button and trigger the connection action.
		jQuery('.woocommerce-save-button').prop('disabled', false).click();
	}

	var release_busy_state = function () {
		jQuery('.rzpwc-connect, .rzpwc-disconnect').removeClass('is-busy');
	};

	// Safety release: if the connection request never completes (server error,
	// interruption), the panel must not stay locked until a manual reload.
	setTimeout(release_busy_state, 15000);

	submit_connection_action();
}