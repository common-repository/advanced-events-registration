<?php

function espresso_help_popup($name) {
	return '<a class="thickbox" href="#TB_inline?height=400&amp;width=500&amp;inlineId=' . $name . '" target="_blank"><span class="question">[?]</span></a>';
}

add_filter('espresso_help', 'espresso_help_popup');

//This is the payment gateway settings page.
function event_espresso_gateways_options() {
	global $active_gateways;
	$active_gateways = get_option('event_espresso_active_gateways', array());
	?>
	<div id="event_reg_theme" class="wrap">
		<div id="icon-options-event" class="icon32"></div>
		<h2><?php _e('Manage Payment Gateways', 'event_espresso'); ?></h2>
		<?php ob_start(); ?>
		<div id="payment_settings" class="meta-box-sortables ui-sortables">
			<?php
			$gateways_glob = glob(EVENT_ESPRESSO_PLUGINFULLPATH . "gateways/*/settings.php");
			$upload_gateways_glob = glob(EVENT_ESPRESSO_GATEWAY_DIR . '*/settings.php');
			if (!is_array($upload_gateways_glob))
				$upload_gateways_glob = array();
			foreach ($upload_gateways_glob as $upload_gateway) {
				$pos = strpos($upload_gateway, 'gateways');
				$sub = substr($upload_gateway, $pos);
				foreach ($gateways_glob as &$gateway) {
					$pos2 = strpos($gateway, 'gateways');
					$sub2 = substr($gateway, $pos2);
					if ($sub == $sub2) {
						$gateway = $upload_gateway;
					}
				}
				unset($gateway);
			}
			$gateways = array_merge($upload_gateways_glob, $gateways_glob);
			$gateways = array_unique($gateways);

			foreach ($gateways as $gateway) {
				require_once($gateway);
			}

			do_action('action_hook_espresso_display_gateway_settings');

			if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/gateway_developer.php')) {
				require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/gateway_developer.php');
			}
			global $espresso_premium;
			if ($espresso_premium != true) {
				?>
				<h2><?php _e('Need more payment options?', 'event_espresso'); ?><a href="http://eventespresso.com/download/" target="_blank"><?php _e('Upgrade Now!', 'event_espresso'); ?></a></h2>
			<?php } ?>
		</div><!-- / .meta-box-sortables -->
		<?php
		$post_content = ob_get_clean();
		espresso_choose_layout('', event_espresso_display_right_column(), $post_content);
		?>
	</div><!-- / #wrap -->
	<div id="button_image" style="display:none">
		<h2><?php _e('Button Image URL', 'event_espresso'); ?></h2>
		<p><?php _e('A default payment button is provided. A custom payment button may be used, choose your image or upload a new one, and just copy the "file url" here (optional.)', 'event_espresso'); ?></p>
	</div>
	<div id="bypass_confirmation" style="display:none">
		<h2><?php _e('Bypassing the Confirmation Page', 'event_espresso'); ?></h2>
		<p><?php _e('This will allow you to send your customers directly to the payment gateway of your choice.', 'event_espresso'); ?></p>
	</div>
	'<div id="force_ssl_return" style="display:none">';
		<h2><?php _e('Force HTTPS on Return URL', 'event_espresso'); ?></h2>
		<p><?php _e('Forces the gateway provider to send the customer back to the return page -- or pull the return page from the site -- using HTTPS.  This is required in some instances to prevent a warning that the page the user is going to is not secure.', 'event_espresso'); ?></p>
	</div>
	<div id="display_header" style="display:none">
		<h2><?php _e('Display a Form Header', 'event_espresso'); ?></h2>
		<p><?php _e('Select if you would like to display a header above the payment form.', 'event_espresso'); ?></p>
	</div>
	<script type="text/javascript" charset="utf-8">
		//<![CDATA[
		jQuery(document).ready(function() {
			postboxes.add_postbox_toggles("payment_gateways");
		});
		//]]>
	</script>
	<?php
}

function espresso_update_active_gateways() {
	//upgrade script for those updating from versions prior to 3.1.16.P
	//hooked to plugin activation

	global $espresso_premium;

	$paypal_settings = get_option('event_espresso_paypal_settings');
	if (!empty($paypal_settings) && strpos($paypal_settings['button_url'], "/paypal/btn_stdCheckout2.gif")) {
		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/paypal/btn_stdCheckout2.gif")) {
			$paypal_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/paypal/btn_stdCheckout2.gif";
		} else {
			$paypal_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/paypal/btn_stdCheckout2.gif";
		}
		update_option('event_espresso_paypal_settings', $paypal_settings);
	}

	if ($espresso_premium == true) {
		$twocheckout_settings = get_option('event_espresso_2checkout_settings');
		if (!empty($twocheckout_settings) && strpos($twocheckout_settings['button_url'], "/2checkout/logo.png")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/2checkout/logo.png")) {
				$twocheckout_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/2checkout/logo.png";
			} else {
				$twocheckout_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/2checkout/logo.png";
			}
			update_option('event_espresso_2checkout_settings', $twocheckout_settings);
		}
		$alipay_settings = get_option('event_espresso_alipay_settings');
		if (!empty($alipay_settings) && strpos($alipay_settings['button_url'], "/alipay/new_logo.jpg")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/alipay/new_logo.jpg")) {
				$alipay_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/alipay/new_logo.jpg";
			} else {
				$alipay_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/alipay/new_logo.jpg";
			}
			update_option('event_espresso_alipay_settings', $alipay_settings);
		}
		$authnet_settings = get_option('event_espresso_authnet_settings');
		if (!empty($authnet_settings) && strpos($authnet_settings['button_url'], "/authnet/btn_cc_vmad.gif")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/authnet/btn_cc_vmad.gif")) {
				$authnet_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/authnet/btn_cc_vmad.gif";
			} else {
				$authnet_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/authnet/btn_cc_vmad.gif";
			}
			update_option('event_espresso_authnet_settings', $authnet_settings);
		}
		$eway_settings = get_option('event_espresso_eway_settings');
		if (!empty($eway_settings) && strpos($eway_settings['button_url'], "/eway/eway_logo.png")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/eway/eway_logo.png")) {
				$eway_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/eway/eway_logo.png";
			} else {
				$eway_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/eway/eway_logo.png";
			}
			update_option('event_espresso_eway_settings', $eway_settings);
		}

		$exact_settings = get_option('event_espresso_exact_settings');
		if (!empty($exact_settings) && strpos($exact_settings['button_url'], "/exact/btn_cc_vmad.gif")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/exact/btn_cc_vmad.gif")) {
				$exact_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/exact/btn_cc_vmad.gif";
			} else {
				$exact_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/exact/btn_cc_vmad.gif";
			}
			update_option('event_espresso_exact_settings', $exact_settings);
		}

		$firstdata_connect_2_settings = get_option('event_espresso_firstdata_connect_2_settings');
		if (!empty($firstdata_connect_2_settings) && strpos($firstdata_connect_2_settings['button_url'], "/firstdata_connect_2/standard_button.gif")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/firstdata_connect_2/standard_button.gif")) {
				$firstdata_connect_2_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/firstdata_connect_2/standard_button.gif";
			} else {
				$firstdata_connect_2_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/firstdata_connect_2/standard_button.gif";
			}
			update_option('event_espresso_firstdata_connect_2_settings', $firstdata_connect_2_settings);
		}
		$mwarrior_settings = get_option('event_espresso_mwarrior_settings');
		if (!empty($mwarrior_settings) && strpos($mwarrior_settings['button_url'], "/mwarrior/btn_checkout.png")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/mwarrior/btn_checkout.png")) {
				$mwarrior_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/mwarrior/btn_checkout.png";
			} else {
				$mwarrior_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/mwarrior/btn_checkout.png";
			}
			update_option('event_espresso_mwarrior_settings', $mwarrior_settings);
		}
		$realauth_settings = get_option('event_espresso_realauth_settings');
		if (!empty($realauth_settings) && !empty($realauth_settings['button_url']) && strpos($realauth_settings['button_url'], "/realauth/logo.gif")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/realauth/logo.gif")) {
				$realauth_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/realauth/logo.gif";
			} else {
				$realauth_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/realauth/logo.gif";
			}
			update_option('event_espresso_realauth_settings', $realauth_settings);
		}
		$wepay_settings = get_option('event_espresso_wepay_settings');
		if (!empty($wepay_settings) && strpos($wepay_settings['button_url'], "/wepay/logo.png")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/wepay/logo.png")) {
				$wepay_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/wepay/logo.png";
			} else {
				$wepay_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/wepay/logo.png";
			}
			update_option('event_espresso_wepay_settings', $wepay_settings);
		}
		$worldpay_settings = get_option('event_espresso_worldpay_settings');
		if (!empty($worldpay_settings) && strpos($worldpay_settings['button_url'], "/worldpay/WorldPaylogoBluetrans.png")) {
			if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/worldpay/WorldPaylogoBluetrans.png")) {
				$worldpay_settings['button_url'] = EVENT_ESPRESSO_GATEWAY_URL . "/worldpay/WorldPaylogoBluetrans.png";
			} else {
				$worldpay_settings['button_url'] = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/worldpay/WorldPaylogoBluetrans.png";
			}
			update_option('event_espresso_worldpay_settings', $worldpay_settings);
		}
	}


	$active_gateways = get_option('event_espresso_active_gateways', array());
	if (!empty($active_gateways)) {
		if (array_key_exists('2checkout', $active_gateways)) {
			$active_gateways['2checkout'] = "/gateways/2checkout";
		}
		if (array_key_exists('aim', $active_gateways)) {
			$active_gateways['aim'] = "/gateways/aim";
		}
		if (array_key_exists('alipay', $active_gateways)) {
			$active_gateways['alipay'] = "/gateways/alipay";
		}
		if (array_key_exists('authnet', $active_gateways)) {
			$active_gateways['authnet'] = "/gateways/authnet";
		}
		if (array_key_exists('bank', $active_gateways)) {
			$active_gateways['bank'] = "/gateways/bank";
		}
		if (array_key_exists('check', $active_gateways)) {
			$active_gateways['check'] = "/gateways/check";
		}
		if (array_key_exists('eway', $active_gateways)) {
			$active_gateways['eway'] = "/gateways/eway";
		}
		if (array_key_exists('exact', $active_gateways)) {
			$active_gateways['exact'] = "/gateways/exact";
		}
		if (array_key_exists('firstdata', $active_gateways)) {
			$active_gateways['firstdata'] = "/gateways/firstdata";
		}
		if (array_key_exists('firstdata_connect_2', $active_gateways)) {
			$active_gateways['firstdata_connect_2'] = "/gateways/firstdata_connect_2";
		}
		if (array_key_exists('ideal', $active_gateways)) {
			$active_gateways['ideal'] = "/gateways/ideal";
		}
		if (array_key_exists('invoice', $active_gateways)) {
			$active_gateways['invoice'] = "/gateways/invoice";
		}
		if (array_key_exists('mwarrior', $active_gateways)) {
			$active_gateways['mwarrior'] = "/gateways/mwarrior";
		}
		if (array_key_exists('nab', $active_gateways)) {
			$active_gateways['nab'] = "/gateways/nab";
		}
		if (array_key_exists('paypal', $active_gateways)) {
			$active_gateways['paypal'] = "/gateways/paypal";
		}
		if (array_key_exists('paypal_pro', $active_gateways)) {
			$active_gateways['paypal_pro'] = "/gateways/paypal_pro";
		}
		if (array_key_exists('paytrace', $active_gateways)) {
			$active_gateways['paytrace'] = "/gateways/paytrace";
		}
		if (array_key_exists('quickpay', $active_gateways)) {
			$active_gateways['quickpay'] = "/gateways/quickpay";
		}
		if (array_key_exists('realauth', $active_gateways)) {
			$active_gateways['realauth'] = "/gateways/realauth";
		}
		if (array_key_exists('stripe', $active_gateways)) {
			$active_gateways['stripe'] = "/gateways/stripe";
		}
		if (array_key_exists('wepay', $active_gateways)) {
			$active_gateways['wepay'] = "/gateways/wepay";
		}
		if (array_key_exists('worldpay', $active_gateways)) {
			$active_gateways['worldpay'] = "/gateways/worldpay";
		}
	} else {
		if (get_option('events_2checkout_active') == true) {
			$active_gateways['2checkout'] = "/gateways/2checkout";
		}
		if (get_option('events_authnet_aim_active') == true) {
			$active_gateways['aim'] = "/gateways/aim";
		}
		if (get_option('events_alipay_active') == true) {
			$active_gateways['alipay'] = "/gateways/alipay";
		}
		if (get_option('events_authnet_active') == true) {
			$active_gateways['authnet'] = "/gateways/authnet";
		}
		if (get_option('events_bank_payment_active') == true) {
			$active_gateways['bank'] = "/gateways/bank";
		}
		if (get_option('events_check_payment_active') == true) {
			$active_gateways['check'] = "/gateways/check";
		}
		if (get_option('events_eway_active') == true) {
			$active_gateways['eway'] = "/gateways/eway";
		}
		if (get_option('events_exact_active') == true) {
			$active_gateways['exact'] = "/gateways/exact";
		}
		if (get_option('events_firstdata_active') == true) {
			$active_gateways['firstdata'] = "/gateways/firstdata";
		}
		if (get_option('events_firstdata_connect_2_active') == true) {
			$active_gateways['firstdata_connect_2'] = "/gateways/firstdata_connect_2";
		}
		if (get_option('events_ideal_active') == true) {
			$active_gateways['ideal'] = "/gateways/ideal";
		}
		if (get_option('events_invoice_payment_active') == true) {
			$active_gateways['invoice'] = "/gateways/invoice";
		}
		if (get_option('events_mwarrior_active') == true) {
			$active_gateways['mwarrior'] = "/gateways/mwarrior";
		}
		if (get_option('events_nab_active') == true) {
			$active_gateways['nab'] = "/gateways/nab";
		}
		if (get_option('events_paypal_active') == true) {
			$active_gateways['paypal'] = "/gateways/paypal";
		}
		if (get_option('events_paypal_pro_active') == true) {
			$active_gateways['paypal_pro'] = "gateways/paypal_pro";
		}
		if (get_option('events_paytrace_active') == true) {
			$active_gateways['paytrace'] = "gateways/paytrace";
		}
		if (get_option('events_quickpay_active') == true) {
			$active_gateways['quickpay'] = "gateways/quickpay";
		}
		$payment_settings = get_option('event_espresso_realauth_settings');
		if (!empty($payment_settings['active'])) {
			$active_gateways['realauth'] = "gateways/realauth";
		}
		if (get_option('events_stripe_active') == true) {
			$active_gateways['stripe'] = "gateways/stripe";
		}
		if (get_option('events_wepay_active') == true) {
			$active_gateways['wepay'] = "wepay";
		}
		if (get_option('events_worldpay_active') == true) {
			$active_gateways['worldpay'] = "worldpay";
		}
	}

	delete_option('events_2checkout_active');
	delete_option('events_authnet_aim_active');
	delete_option('events_alipay_active');
	delete_option('events_authnet_active');
	delete_option('events_bank_payment_active');
	delete_option('events_check_payment_active');
	delete_option('events_eway_active');
	delete_option('events_exact_active');
	delete_option('events_firstdata_active');
	delete_option('events_firstdata_connect_2_active');
	delete_option('events_ideal_active');
	delete_option('events_invoice_payment_active');
	delete_option('events_mwarrior_active');
	delete_option('events_nab_active');
	delete_option('events_paypal_active');
	delete_option('events_paypal_pro_active');
	delete_option('events_paytrace_active');
	delete_option('events_quickpay_active');
	$payment_settings = get_option('event_espresso_realauth_settings');
	$payment_settings['active'] = false;
	update_option('event_espresso_realauth_settings', $payment_settings);
	delete_option('events_stripe_active');
	delete_option('events_wepay_active');
	delete_option('events_worldpay_active');
	update_option('event_espresso_active_gateways', $active_gateways);

	chdir('../');
	if (file_exists('paypal.ipn_results.log')) {
		if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . 'logs/paypal.ipn_results.log')) {
			$old = file_get_contents('paypal.ipn_results.log');
			$new = file_get_contents(EVENT_ESPRESSO_UPLOAD_DIR . 'logs/paypal.ipn_results.log');
			$result1 = file_put_contents(EVENT_ESPRESSO_UPLOAD_DIR . 'logs/paypal.ipn_results.log', $old . $new);
			$result2 = unlink('paypal.ipn_results.log');
			if ($result1 && $result2)
				do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, "appended new log file to old log file in new location and deleted old log file");
			else
				do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, "there was a problem appending new log file to old log file in new location or deleting old log file");
		} else {
			if (rename('paypal.ipn_results.log', EVENT_ESPRESSO_UPLOAD_DIR . 'logs/paypal.ipn_results.log'))
				do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'moved old paypal log file to new location');
			else
				do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'could not move old paypal log file to new location');
		}
	} else
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'old paypal log file did not exist');
	if (!file_exists(EVENT_ESPRESSO_UPLOAD_DIR . 'logs/.htaccess')) {
		if (file_put_contents(EVENT_ESPRESSO_UPLOAD_DIR . 'logs/.htaccess', 'deny from all'))
			do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'created .htaccess file that blocks direct access to logs folder');
		else
			do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'there was a problem creating .htaccess file to block direct access to logs folder');
	} else
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '.htaccess file already exists in logs folder');
}
