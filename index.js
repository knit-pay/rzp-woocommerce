import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'wc-razorpay_data', {} );
const label = decodeEntities( settings.title ) || 'Razorpay Payment Gateway';

const Content = () => {
	return decodeEntities( settings.description || '' );
};

const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

const razorpayPaymentLinks = {
	name: "wc-razorpay",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	placeOrderButtonLabel: settings?.button_text,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( razorpayPaymentLinks );