<?php

/*
  Plugin Name: Event Espresso Lite - Event Registration and Management
  Plugin URI: http://eventespresso.com/
  Description: Out-of-the-box Events Registration integrated with PayPal IPN for your WordPress blog/website. <a href="admin.php?page=support" >Support</a>

  Reporting features provide a list of events, list of attendees, and excel export.

  Version: 3.1.25.L

  Author: Event Espresso
  Author URI: http://www.eventespresso.com

  Copyright (c) 2008-2011 Event Espresso  All Rights Reserved.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */


//Define the version of the plugin
function espresso_version() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	return '3.1.25.L';
}

//This tells the system to check for updates to the paid version
global $espresso_check_for_updates;
$espresso_check_for_updates = true;

function ee_init_session($admin_override = false) {
	//Keep sessions from loading in the WP admin
	if ( is_admin() && (!isset($_REQUEST['events']) && !isset($_REQUEST['event_admin_reports'])) )
		return;
	global $org_options;
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	if (!isset($_SESSION)) {
		session_start();
	}

	if (( isset($_REQUEST['page_id'])
					&& ($_REQUEST['page_id'] == $org_options['return_url']
					|| $_REQUEST['page_id'] == $org_options['notify_url'] ) )
					|| !isset($_SESSION['espresso_session']['id'])
					|| $_SESSION['espresso_session']['id'] == array()) {

		$_SESSION['espresso_session'] = '';
		//Debug
		//echo "<pre>espresso_session - ".print_r($_SESSION['espresso_session'],true)."</pre>";
		$_SESSION['espresso_session'] = array();
		//Debug
		//echo "<pre>espresso_session array - ".print_r($_SESSION['espresso_session'],true)."</pre>";
		$_SESSION['espresso_session']['id'] = session_id() . '-' . uniqid('', true);
		//Debug
		//echo "<pre>".print_r($_SESSION,true)."</pre>";
		$_SESSION['espresso_session']['events_in_session'] = '';
		$_SESSION['espresso_session']['coupon_code'] = '';
		$_SESSION['espresso_session']['grand_total'] = '';
	}
}

add_action('init', 'ee_init_session', 1);

add_action('admin_init', 'ee_check_for_export');

function ee_check_for_export() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	if (isset($_REQUEST['export'])) {
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/functions/export.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/functions/export.php');
			espresso_export_stuff();
		}
	}
}

function espresso_info_header() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	print( "<meta name='generator' content='Event Espresso Version " . EVENT_ESPRESSO_VERSION . "' />");
}

add_action('wp_head', 'espresso_info_header');

//Globals
global $org_options, $wpdb, $this_is_a_reg_page;
$org_options = get_option('events_organization_settings');
	
if (empty($org_options['event_page_id'])) {
	$org_options['event_page_id'] = '';
	$org_options['return_url'] = '';
	$org_options['cancel_return'] = '';
	$org_options['notify_url'] = '';
}
$page_id = isset($_REQUEST['page_id']) ? $_REQUEST['page_id'] : '';

//Registration page check
//From Brent C. http://events.codebasehq.com/projects/event-espresso/tickets/99
$this_is_a_reg_page = FALSE;
$reg_page_ids = array(
		'event_page_id' => $org_options['event_page_id'],
		'return_url' => $org_options['return_url'],
		'cancel_return' => $org_options['cancel_return'],
		'notify_url' => $org_options['notify_url']
);
if (is_ssl())
	$find = str_replace('https://', '', site_url()); else
	$find = str_replace('http://', '', site_url());
$find = str_replace($_SERVER['SERVER_NAME'], '', $find);
$uri_string = str_replace($find, '', $_SERVER['REQUEST_URI']);
$uri_string = isset($_SERVER['QUERY_STRING'])?str_replace($_SERVER['QUERY_STRING'], '', $uri_string):$uri_string;
$uri_string = rtrim($uri_string, '?');
$uri_string = trim($uri_string, '/');
$this_page = basename($uri_string);
$uri_segments = explode('/', $uri_string);
foreach ($uri_segments as $uri_segment) {
	$seg_page_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $wpdb->posts WHERE post_name = %s ", $uri_segment));
	if ($wpdb->num_rows > 0) {
		if (in_array($seg_page_id, $reg_page_ids)) {
			$this_is_a_reg_page = TRUE;
		}
	}
}
if (isset($_REQUEST['page_id']) || is_admin())
	$this_is_a_reg_page = TRUE;

//This will (should) make sure everything is loaded via SSL
//So that the "..not everything is secure.." message doesn't appear
//Still will be a problem if other themes and plugins do not implement ssl correctly
$wp_plugin_url = WP_PLUGIN_URL;
$wp_content_url = WP_CONTENT_URL;

if (is_ssl()) {

	$wp_plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL);
	$wp_content_url = str_replace('http://', 'https://', WP_CONTENT_URL);
}

define("EVENT_ESPRESSO_VERSION", espresso_version());
define('EVENT_ESPRESSO_POWERED_BY', 'Event Espresso - ' . EVENT_ESPRESSO_VERSION);
//Define the plugin directory and path
define("EVENT_ESPRESSO_PLUGINPATH", "/" . plugin_basename(dirname(__FILE__)) . "/");
define("EVENT_ESPRESSO_PLUGINFULLPATH", WP_PLUGIN_DIR . EVENT_ESPRESSO_PLUGINPATH);
define("EVENT_ESPRESSO_PLUGINFULLURL", $wp_plugin_url . EVENT_ESPRESSO_PLUGINPATH);
//End - Define the plugin directory and path
//Define dierectory structure for uploads
if (!defined('WP_CONTENT_DIR')) {
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
$upload_path = WP_CONTENT_DIR . "/uploads";
$event_espresso_upload_dir = "{$upload_path}/espresso/";
$event_espresso_template_dir = "{$event_espresso_upload_dir}templates/";

$includes_directory = EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/';
define("EVENT_ESPRESSO_INCLUDES_DIR", $includes_directory);

define("EVENT_ESPRESSO_UPLOAD_DIR", $event_espresso_upload_dir);
define("EVENT_ESPRESSO_UPLOAD_URL", $wp_content_url . '/uploads/espresso/');
define("EVENT_ESPRESSO_TEMPLATE_DIR", $event_espresso_template_dir);
$event_espresso_gateway_dir = EVENT_ESPRESSO_UPLOAD_DIR . "gateways/";
define("EVENT_ESPRESSO_GATEWAY_DIR", $event_espresso_gateway_dir);
define("EVENT_ESPRESSO_GATEWAY_URL", $wp_content_url . '/uploads/espresso/gateways/');
//End - Define dierectory structure for uploads

require_once EVENT_ESPRESSO_PLUGINFULLPATH . 'class/espresso_log.php';
$message = "REQUEST variables:\n";
foreach ($_REQUEST as $key => $value) {
	if ($key == 'cc' || $key == 'card_num' || $key == 'EPS_CARDNUMBER') {
		$value = substr($value, 0, 4) . "-XXXX-XXXX-XXXX";
	}
	$message .= $key . " = " . $value . "\n";
}
do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, $message);

//Set the default time zone
//If the default time zone is set up in the WP Settings, then we will use that as the default.
if (get_option('timezone_string') != '') {
	date_default_timezone_set(get_option('timezone_string'));
}

//Define all of the plugins database tables
define("EVENTS_CATEGORY_TABLE", $wpdb->prefix . "events_category_detail");
define("EVENTS_CATEGORY_REL_TABLE", $wpdb->prefix . "events_category_rel");
define("EVENTS_DETAIL_TABLE", $wpdb->prefix . "events_detail");
define("EVENTS_ATTENDEE_TABLE", $wpdb->prefix . "events_attendee");
define("EVENTS_ATTENDEE_META_TABLE", $wpdb->prefix . "events_attendee_meta");
define("EVENTS_START_END_TABLE", $wpdb->prefix . "events_start_end");
define("EVENTS_QUESTION_TABLE", $wpdb->prefix . "events_question");
define("EVENTS_QST_GROUP_REL_TABLE", $wpdb->prefix . "events_qst_group_rel");
define("EVENTS_QST_GROUP_TABLE", $wpdb->prefix . "events_qst_group");
define("EVENTS_ANSWER_TABLE", $wpdb->prefix . "events_answer");
define("EVENTS_DISCOUNT_CODES_TABLE", $wpdb->prefix . "events_discount_codes");
define("EVENTS_DISCOUNT_REL_TABLE", $wpdb->prefix . "events_discount_rel");
define("EVENTS_PRICES_TABLE", $wpdb->prefix . "events_prices");
define("EVENTS_EMAIL_TABLE", $wpdb->prefix . "events_email");
//define("EVENTS_SESSION_TABLE", $wpdb->prefix . "events_sessions");
define("EVENTS_VENUE_TABLE", $wpdb->prefix . "events_venue");
define("EVENTS_VENUE_REL_TABLE", $wpdb->prefix . "events_venue_rel");
define("EVENTS_LOCALE_TABLE", $wpdb->prefix . "events_locale");
define("EVENTS_LOCALE_REL_TABLE", $wpdb->prefix . "events_locale_rel");
define("EVENTS_PERSONNEL_TABLE", $wpdb->prefix . "events_personnel");
define("EVENTS_PERSONNEL_REL_TABLE", $wpdb->prefix . "events_personnel_rel");

//Added by Imon
define("EVENTS_MULTI_EVENT_REGISTRATION_ID_GROUP_TABLE", $wpdb->prefix . "events_multi_event_registration_id_group");
define("EVENTS_ATTENDEE_COST_TABLE", $wpdb->prefix . "events_attendee_cost");

//Wordpress function for setting the locale.
//print get_locale();
//setlocale(LC_ALL, get_locale());
setlocale(LC_TIME, get_locale());

//Get language files
load_plugin_textdomain('event_espresso', false, dirname(plugin_basename(__FILE__)) . '/languages/');

//Addons
//Ticketing
if ( file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php") || function_exists('espresso_ticketing_version') ) {
	global $ticketing_installed;
	$ticketing_installed = true;
}

//Seating chart
if ($this_is_a_reg_page == TRUE && file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "/seatingchart/seatingchart.php")) {
	require_once( EVENT_ESPRESSO_UPLOAD_DIR . "/seatingchart/seatingchart.php");
}

//Global files
//Premium funtions. If this is a paid version, then we need to include these files.
global $espresso_premium;
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/misc_functions.php')) {
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/misc_functions.php');
	$espresso_premium = espresso_system_check();
}else{
	$espresso_premium = false;
}

//Build the addon files
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/addons_includes.php')) {
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/addons_includes.php');
}

//Core function files
require_once("includes/functions/main.php");

function espresso_load_pricing_functions() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	require_once("includes/functions/pricing.php");
}

add_action('plugins_loaded', 'espresso_load_pricing_functions', 2);

require_once("includes/functions/time_date.php");
require_once("includes/shortcodes.php");
require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "includes/functions/ical.php");

/* Core template files used by this plugin */
//These may be laoded in posts and pages outside of the default EE pages

// prevent firefox prefetching of the rel='next' link, which could be one of the
// pages that clears the ee session id
// http://www.ebrueggeman.com/blog/wordpress-relnext-and-firefox-prefetching
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');

//Events Listing - Shows the events on your page. Used with the [ESPRESSO_EVENTS] shortcode
event_espresso_require_template('event_list.php');

//This is the form page for registering the attendee
event_espresso_require_template('registration_page.php');

//Registration forms
require_once("includes/functions/form_build.php");

//List Attendees - Used with the [LISTATTENDEES] shortcode
event_espresso_require_template('attendee_list.php');

require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "includes/functions/cart.php");

//Custom post type integration
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/custom_post_type.php')
				&& isset($org_options['use_custom_post_types']) && $org_options['use_custom_post_types'] == 'Y') {
	require('includes/admin-files/custom_post_type.php');
}

//Widget - Display the list of events in your sidebar
//The widget can be over-ridden with the custom files addon
event_espresso_require_template('widget.php');

function load_event_espresso_widget() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	register_widget('Event_Espresso_Widget');
}

add_action('widgets_init', 'load_event_espresso_widget');

/* End Core template files used by this plugin */

function event_espresso_pagination() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
    event_espresso_require_template('event_list.php');
    $_REQUEST['use_wrapper'] = false; 
    event_espresso_get_event_details($_REQUEST); 
    die();
}

//Load these files if we are in an actuial registration page
if ($this_is_a_reg_page == TRUE) {
	
	//Process email confirmations
	require_once("includes/functions/email.php");

	//Various attendee functions
	require_once("includes/functions/attendee_functions.php");


	//Payment/Registration Processing - Used to display the payment options and the payment link in the email. Used with the [ESPRESSO_PAYMENTS] tag
	require_once("includes/process-registration/payment_page.php");

	//Add attendees to the database
	require_once("includes/process-registration/add_attendees_to_db.php");

	//Payment processing - Used for onsite payment processing. Used with the [ESPRESSO_TXN_PAGE] shortcode
	event_espresso_require_gateway('process_payments.php');
	event_espresso_require_gateway('PaymentGateway.php');


	/*
	 * AJAX functions
	 */

	add_action('wp_ajax_event_espresso_add_item', 'event_espresso_add_item_to_session');
	add_action('wp_ajax_nopriv_event_espresso_add_item', 'event_espresso_add_item_to_session');

	add_action('wp_ajax_event_espresso_delete_item', 'event_espresso_delete_item_from_session');
	add_action('wp_ajax_nopriv_event_espresso_delete_item', 'event_espresso_delete_item_from_session');

	add_action('wp_ajax_event_espresso_update_item', 'event_espresso_update_item_in_session');
	add_action('wp_ajax_nopriv_event_espresso_update_item', 'event_espresso_update_item_in_session');

	add_action('wp_ajax_event_espresso_calculate_total', 'event_espresso_calculate_total');
	add_action('wp_ajax_nopriv_event_espresso_calculate_total', 'event_espresso_calculate_total');

	add_action('wp_ajax_event_espresso_load_regis_form', 'event_espresso_load_regis_form');
	add_action('wp_ajax_nopriv_event_espresso_load_regis_form', 'event_espresso_load_regis_form');

	add_action('wp_ajax_event_espresso_confirm_and_pay', 'event_espresso_confirm_and_pay');
	add_action('wp_ajax_nopriv_event_espresso_confirm_and_pay', 'event_espresso_confirm_and_pay');
    
    add_action('wp_ajax_events_pagination','event_espresso_pagination');
    add_action('wp_ajax_nopriv_events_pagination','event_espresso_pagination');
    
}


if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/coupon-management/index.php')) {
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/coupon-management/index.php');
	//Include dicount codes
	require_once("includes/admin-files/coupon-management/use_coupon_code.php");
} else {
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/coupon_management.php');
}
require_once("includes/functions/admin.php");

//Admin only files
if (is_admin()) {

	if ($espresso_premium != true)
		require_once("includes/test_drive_pro.php");
	
	//Load the roles and permissions functions
	do_action('action_hook_espresso_permissions');
	
	//Update notifications
	do_action('action_hook_espresso_core_update_api');
	do_action('action_hook_espresso_members_update_api');
	do_action('action_hook_espresso_multiple_update_api');
	do_action('action_hook_espresso_calendar_update_api');
	do_action('action_hook_espresso_groupon_update_api');
	do_action('action_hook_espresso_permissions_basic_update_api');
	do_action('action_hook_espresso_permissions_pro_update_api');
	do_action('action_hook_espresso_seating_update_api');
	do_action('action_hook_espresso_social_update_api');
	do_action('action_hook_espresso_recurring_update_api');
	do_action('action_hook_espresso_ticketing_update_api');
	do_action('action_hook_espresso_mailchimp_update_api');
	
	//New form builder
	require_once("includes/form-builder/index.php");
	require_once("includes/form-builder/groups/index.php");

	//Install/Update Tables when plugin is activated
	require_once("includes/functions/database_install.php");
	register_activation_hook(__FILE__, 'events_data_tables_install');
	register_activation_hook(__FILE__, 'espresso_update_active_gateways');

	//Premium funtions. If this is a paid version, then we need to include these files.
	//Premium upgrade options if the piad plugin is not installed
	require_once("includes/premium_upgrade.php");

	//Get the payment settings page
	event_espresso_require_gateway('payment_gateways.php');

	//Email Manager
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/email-manager/index.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/email-manager/index.php');
		} else {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/email-manager.php');
		}

	//Event Registration Subpage - Add/Delete/Edit Venues
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/venue-management/index.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/venue-management/index.php');
		} else {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/venue_management.php');
		}
	
	//Add/Delete/Edit Locales
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/locale-management/index.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/locale-management/index.php');
		} else {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/locale_management.php');
		}

	//Add/Delete/Edit Staff
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/staff-management/index.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/staff-management/index.php');
		} else {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/staff-management.php');
		}

	//Main functions
	if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/functions.php')) {
		require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/functions.php');
	}

	//Available addons
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/admin_addons.php')) {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/admin_addons.php');
		} else {
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin_addons.php');
		}

	//Admin Widget - Display event stats in your admin dashboard
	event_espresso_require_file('dashboard_widget.php', EVENT_ESPRESSO_PLUGINFULLPATH . "includes/admin-files/", '', false, true);


	//Admin only functions
	require_once("includes/functions/admin_menu.php");

	//Event Registration Subpage - Configure Organization
	if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'event_espresso' ) {
		require_once("includes/organization_config.php");
	}

	//Event Registration Subpage - Add/Delete/Edit Events
	if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'events' ) {
		require_once("includes/event-management/index.php");
	}

	//Event styles & template layouts Subpage
	if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/template_settings/index.php')) {
		require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/template_settings/index.php');
	}

	//Plugin Support
	if ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'support' ) {
		require_once("includes/admin_support.php");
	}

	//Admin Reporting
	//require_once("includes/admin-reports/index.php");
	//Event Registration Subpage - Category Manager
	require_once("includes/category-management/index.php");

	//Load scripts and styles for the admin
	if (isset($_REQUEST['page'])) {
		$espresso_pages = array(
				'event_espresso',
				'discounts',
				'groupons',
				'event_categories',
				'admin_reports',
				'form_builder',
				'form_groups', 
				'my-events',
				'event_emails',
				'event_venues',
				'event_staff',
				'events',
				'espresso_reports',
				'support',
				'template_confg',
				'payment_gateways',
				'members',
				'admin_addons',
				'espresso_calendar',
				'event_tickets',
				'espresso-mailchimp',
				'espresso_social',
				'espresso_permissions',
				'roles',
				'event_locales',
				'event_groups'
		);
		if (in_array($_REQUEST['page'], $espresso_pages)) {
			add_action('admin_print_scripts', 'event_espresso_config_page_scripts');
			add_action('admin_print_styles', 'event_espresso_config_page_styles');
		}
	}

	add_action('wp_ajax_update_sequence', 'event_espresso_questions_config_mnu'); //Update the question sequences
	add_action('wp_ajax_update_qgr_sequence', 'event_espresso_question_groups_config_mnu'); //Update the question group sequences
}

//Load the required Javascripts
add_action('wp_footer', 'espresso_load_javascript_files');
add_action('init', 'espresso_load_jquery', 10);
add_action('init', 'espresso_load_EEGlobals_jquery', 10);
if (!function_exists('espresso_load_javascript_files')) {

	function espresso_load_javascript_files() {
		global $load_espresso_scripts;
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!$load_espresso_scripts)
			return;
		wp_register_script('reCopy', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/reCopy.js"), false, '1.1.0');
		wp_print_scripts('reCopy');

		wp_register_script('jquery.validate.js', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/jquery.validate.min.js"), false, '1.8.1');
		wp_print_scripts('jquery.validate.js');

		wp_register_script('validation', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/validation.js"), false, EVENT_ESPRESSO_VERSION);
		wp_print_scripts('validation');
        
        wp_register_script('ee_pagination_plugin', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/jquery.pajinate.min.js"), false, EVENT_ESPRESSO_VERSION);
		wp_print_scripts('ee_pagination_plugin');
        
        wp_register_script('ee_pagination', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/pagination.js"), false, EVENT_ESPRESSO_VERSION);
        $data = array( 'ajaxurl' => admin_url( 'admin-ajax.php'  ));
        wp_localize_script( 'ee_pagination', 'ee_pagination', $data );
		wp_print_scripts('ee_pagination');
        
	}

}

//Used for the drap and drop questions
if (!function_exists('espresso_load_EEGlobals_jquery')) {	
	function espresso_load_EEGlobals_jquery(){
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if ( isset($_REQUEST['page']) && ( $_REQUEST['page'] == 'form_builder' || $_REQUEST['page'] == 'form_groups') ){
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script('ee_ajax_request', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/espresso_EEGlobals_functions.js', array('jquery'));
			wp_localize_script( 'ee_ajax_request', 'EEGlobals', array('ajaxurl' => admin_url('admin-ajax.php'), 'plugin_url' => EVENT_ESPRESSO_PLUGINFULLURL) );
		}
	}
}

//Used for the event cart
if (!function_exists('espresso_load_jquery')) {
	function espresso_load_jquery() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!is_admin() ) {
			global $org_options;
			wp_enqueue_script('jquery');
			if ( function_exists('event_espresso_multi_reg_init') ) {
				wp_enqueue_script('ee_ajax_request', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/espresso_cart_functions.js', array('jquery'));
				wp_localize_script('ee_ajax_request', 'EEGlobals', array('ajaxurl' => admin_url('admin-ajax.php'), 'plugin_url' => EVENT_ESPRESSO_PLUGINFULLURL, 'event_page_id' => $org_options['event_page_id']));
			}
		}
	}

}
//End Javascript files
//Load the style sheets for the reegistration pages

//This is the old style settings
	add_action('wp_print_styles', 'add_event_espresso_stylesheet');
if (!function_exists('add_event_espresso_stylesheet')) {

	function add_event_espresso_stylesheet() {
		global $org_options;
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (isset($org_options['enable_default_style']) && $org_options['enable_default_style'] != 'Y')
			return;
		
		if (!empty($org_options['style_settings']['enable_default_style']) && $org_options['style_settings']['enable_default_style'] == 'Y')
			return;

		// for backpat we check options to see if event_espresso_style.css is set if is or no option is set we load it from original folder
		if (empty($org_options['selected_style']) || $org_options['selected_style'] == 'event_espresso_style.css') {
			$style_path = 'templates/event_espresso_style.css';
		} else {
			$style_path = 'templates/css/' . $org_options['selected_style'];
		}

		$event_espresso_style_sheet = EVENT_ESPRESSO_PLUGINFULLURL . $style_path;


		if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "templates/event_espresso_style.css")) {
			$event_espresso_style_sheet = EVENT_ESPRESSO_UPLOAD_URL . 'templates/event_espresso_style.css';
		}

		wp_register_style('event_espresso_style_sheets', $event_espresso_style_sheet);
		wp_enqueue_style('event_espresso_style_sheets');

		if (!file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "templates/event_espresso_style.css") && !empty($org_options['style_color'])) {
			$event_espresso_style_color = EVENT_ESPRESSO_PLUGINFULLURL . 'templates/css/colors/' . $org_options['style_color'];

			wp_register_style('event_espresso_style_color', $event_espresso_style_color);
			wp_enqueue_style('event_espresso_style_color');
		}
	}

}

add_action('wp_print_styles', 'add_espresso_themeroller_stylesheet');
function add_espresso_themeroller_stylesheet() {
	global $org_options;

	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	
	

	//Load the ThemeRoller styles if enabled
	if (!empty($org_options['style_settings']['enable_default_style']) && $org_options['style_settings']['enable_default_style'] == 'Y') {

		/*if (!empty($org_options['style_settings']['use_grid_layout']) && $org_options['style_settings']['use_grid_layout'] == 'Y') {
			if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . 'css/grid_layout.css')) {
				wp_register_style('espresso_grid_layout', EVENT_ESPRESSO_UPLOAD_URL . 'css/grid_layout.css');
			} else {
				wp_register_style('espresso_grid_layout', EVENT_ESPRESSO_PLUGINFULLURL . 'templates/css/grid_layout.css');
			}
			wp_enqueue_style('espresso_grid_layout');
		}*/

		//Define the path to the ThemeRoller files
		if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "themeroller/index.php")) {
			$themeroller_style_path = EVENT_ESPRESSO_UPLOAD_URL . 'themeroller/';
		} else {
			$themeroller_style_path = EVENT_ESPRESSO_PLUGINFULLURL . 'templates/css/themeroller/';
		}

		//Load custom style sheet if available
		if (!empty($org_options['style_settings']['css_name'])) {
			wp_register_style('espresso_custom_css', EVENT_ESPRESSO_UPLOAD_URL . 'css/' . $org_options['style_settings']['css_name']);
			wp_enqueue_style('espresso_custom_css');
		}

		//Register the ThemeRoller styles
		if (!empty($org_options['themeroller']) && !is_admin()) {

			//Load the themeroller base style sheet
			//If the themeroller-base.css is in the uploads folder, then we will use it instead of the one in the core
			if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . $themeroller_style_path . 'themeroller-base.css')) {
				wp_register_style('espresso_themeroller_base', $themeroller_style_path . 'themeroller-base.css');
			} else {
				wp_register_style('espresso_themeroller_base', EVENT_ESPRESSO_PLUGINFULLURL . 'templates/css/themeroller/themeroller-base.css');
			}
			wp_enqueue_style('espresso_themeroller_base');

			//Load the smoothness style by default<br />
			if (!isset($org_options['themeroller']['themeroller_style']) || empty($org_options['themeroller']['themeroller_style'])) {
				$org_options['themeroller']['themeroller_style'] = 'smoothness';
			}

			//Load the selected themeroller style
			wp_register_style('espresso_themeroller', $themeroller_style_path . $org_options['themeroller']['themeroller_style'] . '/style.css');
			wp_enqueue_style('espresso_themeroller');
		}
	}
}


//End styles

/**
 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the
 * settings page.
 */
function event_espresso_filter_plugin_actions($links, $file) {
	// Static so we don't call plugin_basename on every plugin row.
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	static $this_plugin;
	if (!$this_plugin)
		$this_plugin = plugin_basename(__FILE__);

	if ($file == $this_plugin) {
		$org_settings_link = '<a href="admin.php?page=event_espresso">' . __('Settings') . '</a>';
		$events_link = '<a href="admin.php?page=events">' . __('Events') . '</a>';
		array_unshift($links, $org_settings_link, $events_link); // before other links
	}
	return $links;
}

//Settings link in the plugins overview page
add_filter('plugin_action_links', 'event_espresso_filter_plugin_actions', 10, 2);

//Admin menu
add_action('admin_menu', 'add_event_espresso_menus');

//Run the program
if (!function_exists('event_espresso_run')) {

	function event_espresso_run() {
		global $wpdb, $org_options, $load_espresso_scripts;
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		ob_start();

		//Make sure scripts are loading
		echo espresso_check_scripts();

		// Get action type
		$regevent_action = isset($_REQUEST['regevent_action']) ? $_REQUEST['regevent_action'] : '';

		if (isset($_REQUEST['ee'])) {
			$regevent_action = "register";
			$_REQUEST['event_id'] = $_REQUEST['ee'];
		}
		if (isset($_REQUEST['edit_attendee']))
			$regevent_action = "register";

		switch ($regevent_action) {
			case "post_attendee":
				event_espresso_add_attendees_to_db();
				break;
			case "register":
				register_attendees();
				break;
			case "add_to_session":
				break;
			case "show_shopping_cart":
				//This is the form page for registering the attendee
				event_espresso_require_template('shopping_cart.php');
				event_espresso_shopping_cart();
				break;
			case "load_checkout_page":
				if ($_POST)
					event_espresso_calculate_total('details');
				event_espresso_load_checkout_page();
				break;
			case "post_multi_attendee":
				//echo " YESssss";
				event_espresso_update_item_in_session('attendees');
				event_espresso_add_attendees_to_db_multi();
				break;
			default:
				display_all_events();
		}

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

}

function espresso_cancelled() {
	global $org_options;
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	$_REQUEST['page_id'] = $org_options['return_url'];
	ee_init_session();
}

//New way of doing it with shortcodes
add_shortcode('ESPRESSO_PAYMENTS', 'event_espresso_pay');
add_shortcode('ESPRESSO_TXN_PAGE', 'event_espresso_txn');
add_shortcode('ESPRESSO_EVENTS', 'event_espresso_run');
add_shortcode('ESPRESSO_CANCELLED', 'espresso_cancelled');


/*
 * These actions need to be loaded a the bottom of this script to prevent errors when post/get requests are received.
 */

// Export iCal file
if (!empty($_REQUEST['iCal'])) {
	espresso_ical();
}

//Export PDF invoice
if (isset($_REQUEST['download_invoice']) && $_REQUEST['download_invoice'] == 'true') {
	if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/invoice/template.php")) {
		require_once(EVENT_ESPRESSO_GATEWAY_DIR . "/invoice/template.php");
	} else {
		require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "gateways/invoice/template.php");
	}
}

if (is_admin()) {
	//Check to make sure there are no empty registration id fields in the database.
	if (event_espresso_verify_attendee_data() == true && $_POST['action'] != 'event_espresso_update_attendee_data') {
		add_action('admin_notices', 'event_espresso_registration_id_notice');
	}

	//copy themes to template directory
	if (isset($_REQUEST['event_espresso_admin_action'])) {
		if ($_REQUEST['event_espresso_admin_action'] == 'copy_templates') {
			add_action('admin_init', 'event_espresso_trigger_copy_templates');
		}
	}
	//copy gateways to gateway directory
	if (isset($_REQUEST['event_espresso_admin_action'])) {
		if ($_REQUEST['event_espresso_admin_action'] == 'copy_gateways') {
			add_action('admin_init', 'event_espresso_trigger_copy_gateways');
		}
	}
	//Check to make sure all of the main pages are setup properly, if not show an admin message.
	if (((!isset($_REQUEST['event_page_id']) || $_REQUEST['event_page_id'] == NULL) && ($org_options['event_page_id'] == ('0' || ''))) || $org_options['return_url'] == ('0' || '') || $org_options['notify_url'] == ('0' || '')) {
		add_action('admin_notices', 'event_espresso_activation_notice');
	}
}

//Export PDF Ticket (new)
function espresso_export_ticket() {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	//Version 2.0
	if (isset($_REQUEST['ticket_launch']) && $_REQUEST['ticket_launch'] == 'true') {
		echo espresso_ticket_launch($_REQUEST['id'], $_REQUEST['r_id']);
	}
	//End Version 2.0
	
	//Deprecated version 1.0
	//Export PDF Ticket
	if (isset($_REQUEST['download_ticket']) && $_REQUEST['download_ticket'] == 'true') {
		if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php")) {
			require_once(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php");
			
			//Old url string
			$r_id = espresso_return_reg_id();
			
			//Attendee id
			$a_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? $_REQUEST['id'] : false;
			
			if ( $a_id != false && $r_id != false )
				espresso_ticket($a_id, $r_id);			
		}
	}
	//End Deprecated version 1.0
}

add_action('plugins_loaded', 'espresso_export_ticket', 30);
