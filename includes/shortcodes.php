<?php
if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');
//These are the core shortcodes used by the plugin.
//If you would like to add your own shortcodes, please puchasse the custom shortcodes addon from http://eventespresso.com/download/plugins-and-addons/custom-files-addon/
//For a list and description of available shortcodes, please refer to http://eventespresso.com/forums/2010/10/post-type-variables-and-shortcodes/

/*
 *
 * Single Event
 * Displays a single event
 *
 */
//[SINGLEEVENT single_event_id="your_event_identifier"]
if (!function_exists('show_single_event')) {

	function show_single_event($atts) {
		extract(shortcode_atts(array('single_event_id' => __('No ID Supplied', 'event_espresso')), $atts));
		$single_event_id = "{$single_event_id}";
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		//echo $single_event_id;
		ob_start();
		register_attendees($single_event_id);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('SINGLEEVENT', 'show_single_event');

/*
 *
 * Event Category
 * Displays a list of events by category
 * [EVENT_ESPRESSO_CATEGORY event_category_id="your_category_identifier"]
 *
 */

if (!function_exists('show_event_category')) {

	function show_event_category($atts) {
		extract(shortcode_atts(array('event_category_id' => __('No Category ID Supplied', 'event_espresso'), 'css_class' => ''), $atts));
		$event_category_id = "{$event_category_id}";
		$css_class = "{$css_class}";
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		ob_start();
		display_event_espresso_categories($event_category_id, $css_class); //This function is called from the "/templates/event_list.php" file.
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('EVENT_ESPRESSO_CATEGORY', 'show_event_category');

/*
 *
 * List of Attendees
 * Displays a lsit of attendees
 * [LISTATTENDEES]
 * [LISTATTENDEES limit="30"]
 * [LISTATTENDEES show_expired="false"]
 * [LISTATTENDEES show_deleted="false"]
 * [LISTATTENDEES show_secondary="false"]
 * [LISTATTENDEES show_gravatar="true"]
  //[LISTATTENDEES paid_only="true"]
 * [LISTATTENDEES show_recurrence="false"]
 * [LISTATTENDEES event_identifier="your_event_identifier"]
 * [LISTATTENDEES category_identifier="your_category_identifier"]
 */
if (!function_exists('event_espresso_attendee_list')) {

	function event_espresso_attendee_list($event_identifier='NULL', $category_identifier='NULL', $show_gravatar='false', $show_expired='false', $show_secondary='false', $show_deleted='false', $show_recurrence='true', $limit='0', $paid_only='false', $sort_by='last name') {
		$show_expired = $show_expired == 'false' ? " AND e.start_date >= '" . date('Y-m-d') . "' " : '';
		$show_secondary = $show_secondary == 'false' ? " AND e.event_status != 'S' " : '';
		$show_deleted = $show_deleted == 'false' ? " AND e.event_status != 'D' " : '';
		$show_recurrence = $show_recurrence == 'false' ? " AND e.recurrence_id = '0' " : '';
		$sort = $sort_by == 'last name' ? " ORDER BY lname " : '';
		$limit = $limit > 0 ? " LIMIT 0," . $limit . " " : '';
		if ($event_identifier != 'NULL') {
			$type = 'event';
		} else if ($category_identifier != 'NULL') {
			$type = 'category';
		}

		if (!empty($type) && $type == 'event') {
			$sql = "SELECT e.* FROM " . EVENTS_DETAIL_TABLE . " e ";
			$sql .= " WHERE e.is_active = 'Y' ";
			$sql .= " AND e.event_identifier = '" . $event_identifier . "' ";
			$sql .= $show_secondary;
			$sql .= $show_expired;
			$sql .= $show_deleted;
			$sql .= $show_recurrence;
			$sql .= $limit;
			event_espresso_show_attendess($sql, $show_gravatar, $paid_only, $sort);
		} else if (!empty($type) && $type == 'category') {
			$sql = "SELECT e.* FROM " . EVENTS_CATEGORY_TABLE . " c ";
			$sql .= " JOIN " . EVENTS_CATEGORY_REL_TABLE . " r ON r.cat_id = c.id ";
			$sql .= " JOIN " . EVENTS_DETAIL_TABLE . " e ON e.id = r.event_id ";
			$sql .= " WHERE c.category_identifier = '" . $category_identifier . "' ";
			$sql .= " AND e.is_active = 'Y' ";
			$sql .= $show_secondary;
			$sql .= $show_expired;
			$sql .= $show_deleted;
			$sql .= $show_recurrence;
			$sql .= $limit;
			event_espresso_show_attendess($sql, $show_gravatar, $paid_only, $sort);
		} else {
			$sql = "SELECT e.* FROM " . EVENTS_DETAIL_TABLE . " e ";
			$sql .= " WHERE e.is_active='Y' ";
			$sql .= $show_secondary;
			$sql .= $show_expired;
			$sql .= $show_deleted;
			$sql .= $show_recurrence;
			$sql .= $limit;
			event_espresso_show_attendess($sql, $show_gravatar, $paid_only, $sort);
		}
	}

}

if (!function_exists('event_espresso_list_attendees')) {

	function event_espresso_list_attendees($atts) {
		//echo $atts;
		extract(shortcode_atts(array('event_identifier' => 'NULL', 'category_identifier' => 'NULL', 'event_category_id' => 'NULL', 'show_gravatar' => 'NULL', 'show_expired' => 'NULL', 'show_secondary' => 'NULL', 'show_deleted' => 'NULL', 'show_recurrence' => 'NULL', 'limit' => 'NULL', 'paid_only' => 'NULL'), $atts));
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		//get the event identifiers
		$event_identifier = "{$event_identifier}";
		
		$show_gravatar = "{$show_gravatar}";

		//get the category identifiers
		$category_identifier = "{$category_identifier}";
		$event_category_id = "{$event_category_id}";
		$category_identifier = ($event_category_id != 'NULL') ? $event_category_id : $category_identifier;

		//Get the extra parameters
		$show_expired = "{$show_expired}";
		$show_secondary = "{$show_secondary}";
		$show_deleted = "{$show_deleted}";
		$show_recurrence = "{$show_recurrence}";
		$paid_only = "{$paid_only}";

		ob_start();
		event_espresso_attendee_list($event_identifier, $category_identifier, $show_gravatar, $show_expired, $show_secondary, $show_deleted, $show_recurrence, $limit, $paid_only);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('LISTATTENDEES', 'event_espresso_list_attendees');

/*
 *
 * Event Times
 * Returs the times for an event. Sucha s start and end times, registration start and end times, etc.
 * Please refer to http://php.net/manual/en/function.date.php for date formats
 *
 */
if (!function_exists('espresso_event_time_sc')) {

	function espresso_event_time_sc($atts) {
		extract(shortcode_atts(array('event_id' => '0', 'type' => '', 'format' => ''), $atts));
		$event_id = "{$event_id}";
		$type = "{$type}";
		$format = "{$format}";
		ob_start();
		espresso_event_time($event_id, $type, $format);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('EVENT_TIME', 'espresso_event_time_sc');

/*
 *
 * Registration Page
 * Returns the registration page for an event
 *
 */
if (!function_exists('espresso_reg_page_sc')) {

	function espresso_reg_page_sc($atts) {
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		extract(shortcode_atts(array('event_id' => '0'), $atts));
		$event_id = "{$event_id}";
		ob_start();
		register_attendees(NULL, $event_id);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('ESPRESSO_REG_PAGE', 'espresso_reg_page_sc');

/*
 *
 * Registration Form
 * Returns only the registration form for an event
 *
 */
if (!function_exists('espresso_reg_form_sc')) {

	function espresso_reg_form_sc($atts) {
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		extract(shortcode_atts(array('event_id' => '0'), $atts));
		$event_id = "{$event_id}";
		ob_start();
		register_attendees(NULL, $event_id, true);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('ESPRESSO_REG_FORM', 'espresso_reg_form_sc');

/*
 *
 * Category Name
 * Returns an array of category data based on an event id
 *
 */
if (!function_exists('espresso_category_name_sc')) {

	function espresso_category_name_sc($atts) {
		global $wpdb, $org_options;
		extract(shortcode_atts(array('event_id' => '0'), $atts));
		$event_id = "{$event_id}";
		$category_name = espresso_event_category_data($event_id);
		return $category_name['category_name'];
	}

}
add_shortcode('CATEGORY_NAME', 'espresso_category_name_sc');

/*
 *
 * Price Dropdown
 * Returns a price dropdown if multiple prices are associated with an event, based on an event id
 *
 */
if (!function_exists('espresso_price_dd_sc')) {

	function espresso_price_dd_sc($atts) {
		global $wpdb, $org_options;
		extract(shortcode_atts(array('event_id' => '0'), $atts));
		$event_id = "{$event_id}";
		ob_start();
		event_espresso_price_dropdown($event_id);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('EVENT_PRICE_DROPDOWN', 'espresso_price_dd_sc');

/*
 *
 * Event Price
 * Returns a price for a single event, based on an event id
 *
 */
if (!function_exists('get_espresso_price_sc')) {

	function get_espresso_price_sc($atts) {
		extract(shortcode_atts(array('event_id' => '0', 'number' => '0'), $atts));
		$event_id = "{$event_id}";
		$number = "{$number}";
		ob_start();
		espresso_return_single_price($event_id, $number);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('EVENT_PRICE', 'get_espresso_price_sc');


/*
 *
 * Returns the number of attendees, registration limits, etc based on an event id
 *
 */
if (!function_exists('espresso_attendees_data_sc')) {

	function espresso_attendees_data_sc($atts) {
		global $wpdb, $org_options;
		extract(shortcode_atts(array('event_id' => '0', 'type' => ''), $atts));
		$event_id = "{$event_id}";
		$type = "{$type}";
		$data = get_number_of_attendees_reg_limit($event_id, $type);
		return $data;
	}

}
add_shortcode('ATTENDEE_NUMBERS', 'espresso_attendees_data_sc');

/*
 *
 * Event List
 * Returns a list of events
 * [EVENT_LIST]
 * [EVENT_LIST limit=1]
 * [EVENT_LIST css_class=my-custom-class]
 * [EVENT_LIST show_expired=true]
 * [EVENT_LIST show_deleted=true]
 * [EVENT_LIST show_secondary=true]
 * [EVENT_LIST show_recurrence=true]
 * [EVENT_LIST category_identifier=your_category_identifier]
 *
 */
if (!function_exists('display_event_list_sc')) {

	function display_event_list_sc($attributes) {
//		global $wpdb, $org_options;
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
//      $events_per_page = 50;
//      $num_page_links_to_display = 10;
//		extract(shortcode_atts(array('category_identifier' => 'NULL', 'show_expired' => 'false', 'show_secondary' => 'false', 'show_deleted' => 'false', 'show_recurrence' => 'false', 'limit' => '0', 'order_by' => 'NULL', 'css_class' => 'NULL', 'events_per_page' => 50, 'num_page_links_to_display'=>10), $atts));
//
//		if ($category_identifier != 'NULL') {
//			$type = 'category';
//		}
//
//		$show_expired = $show_expired == 'false' ? " AND (e.start_date >= '" . date('Y-m-d') . "' OR e.event_status = 'O' OR e.registration_end >= '" . date('Y-m-d') . "') " : '';
//		$show_secondary = $show_secondary == 'false' ? " AND e.event_status != 'S' " : '';
//		$show_deleted = $show_deleted == 'false' ? " AND e.event_status != 'D' " : '';
//		$show_recurrence = $show_recurrence == 'false' ? " AND e.recurrence_id = '0' " : '';
//		$limit = $limit > 0 ? " LIMIT 0," . $limit . " " : '';
//		$order_by = $order_by != 'NULL' ? " ORDER BY " . $order_by . " ASC " : " ORDER BY date(start_date), id ASC ";
//
//		if (!empty($type) && $type == 'category') {
//			$sql = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
//			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
//			$sql .= " FROM " . EVENTS_CATEGORY_TABLE . " c ";
//			$sql .= " JOIN " . EVENTS_CATEGORY_REL_TABLE . " r ON r.cat_id = c.id ";
//			$sql .= " JOIN " . EVENTS_DETAIL_TABLE . " e ON e.id = r.event_id ";
//			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
//			$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
//			$sql .= " JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
//			$sql .= " WHERE c.category_identifier = '" . $category_identifier . "' ";
//			$sql .= " AND e.is_active = 'Y' ";
//		} else {
//			$sql = "SELECT e.*, ese.start_time, ese.end_time, p.event_cost ";
//			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
//			$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
//			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = r.venue_id " : '';
//			$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
//			$sql .= " JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
//			$sql .= " WHERE e.is_active = 'Y' ";
//		}
//
//		$sql .= $show_expired;
//		$sql .= $show_secondary;
//		$sql .= $show_deleted;
//		$sql .= $show_recurrence;
//		$sql .= " GROUP BY e.id ";
//		$sql .= $order_by;
//		$sql .= $limit;
		//template located in event_list_dsiplay.php
		ob_start();
		//echo $sql;
		//event_espresso_get_event_details($sql, $css_class, $allow_override = 1, $events_per_page, $num_page_links_to_display);
        event_espresso_get_event_details($attributes);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('EVENT_LIST', 'display_event_list_sc');


//Search
//Shortcode to create an autocomplete search tool.
function ee_create_autocomplete_search(){
	global $wpdb, $espresso_manager, $current_user, $org_options;
	ob_start();
	?>
	<div class="ui-widget">
		<form name="form" method="post" action="<?php echo $_SERVER["REQUEST_URI"] ?>">
			<input id="ee_autocomplete" class="ui-autocomplete-input ui-corner-all" />
			<input id="event_id" name="event_id" type="hidden">
		</form>
	</div>
	<script type="text/javascript" charset="utf-8">
			//<![CDATA[
			jQuery(document).ready(function() {
				//jQuery('#ee_autocomplete').css('width','400px');
				//jQuery('.ui-autocomplete li').css(['width','400px');
				//Auto complete
				jQuery("input#ee_autocomplete").autocomplete({
					
					source: [
						//Examples:
						//"c++", "java", "php", "coldfusion", "javascript", "asp", "ruby"
						//{ label: "Choice1", value: "value1" }
		<?php 
			$sql = "SELECT e.*, v.city venue_city, v.state venue_state";
			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= ", v.name venue_name, v.address venue_address, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta " : '';
			$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
			//$sql .= " JOIN " . EVENTS_CATEGORY_REL_TABLE . " r ON r.cat_id = c.id ";
			//$sql .= " JOIN " . EVENTS_DETAIL_TABLE . " e ON e.id = r.event_id ";
			isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y' ? $sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON vr.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = vr.venue_id " : '';
			//$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
			//$sql .= " JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
			//$sql .= " WHERE c.category_identifier = '" . $category_identifier . "' ";
			$sql .= " WHERE e.is_active = 'Y' ";
			$sql .= " AND e.event_status != 'D' ";
			//echo '<p>$sql = '.$sql.'</p>';							
			$events = $wpdb->get_results($sql);
			$num_rows = $wpdb->num_rows;
										
			if ($num_rows > 0) {
				foreach ($events as $event){
					$venue_city = !empty($event->venue_city) ? stripslashes_deep($event->venue_city)  : '';
					$venue_state = !empty($event->venue_state) ?  (!empty($event->venue_city) ? ', ' : '') .stripslashes_deep($event->venue_state)  : '';

					$venue_name = !empty($event->venue_name) ?' @' . stripslashes_deep($event->venue_name)  . ' - ' . $venue_city . $venue_state . ''  : '';
					//An Array of Objects with label and value properties:
					echo '{ url:"?page_id=4&ee='.$event->id.'", value: "'.stripslashes_deep($event->event_name) . $venue_name .'", id: "'.$event->id.'" },';
				}
			}
		?>
							],
							success: function(data) {
							  response(jQuery.map(data, function(item) {
								return {
									url: item.url,
									value: item.name
								}
							  }
							))
							},
							select: function( event, ui ) {
								window.location.href = ui.item.url;
							},
							minLength: 2


				});
						//End auto complete
			});
		
			//]]>
			
		</script>
	<?php
	//echo '<p>$sql = '.$sql.'</p>';	
	//Load scripts
	add_action('wp_footer', 'ee_load_jquery_autocomplete_scripts');	
	$buffer = ob_get_contents();
	ob_end_clean();
	return $buffer;		
}
add_shortcode('EVENT_SEARCH', 'ee_create_autocomplete_search');


//Returns the price
/* function espresso_get_price_sc($atts){
  global $wpdb, $org_options;
  extract(shortcode_atts(array('event_id' =>'0'), $atts));
  $event_id = "{$event_id}";
  return event_espresso_get_price($event_id);
  }
  add_shortcode('EVENT_PRICE', 'espresso_get_price_sc'); */

function espresso_session_id_sc() {
	return event_espresso_session_id();
}

add_shortcode('SESSION_ID', 'espresso_session_id_sc');

/**
  Staff Details shortcode
  http://eventespresso.com/forums/2010/10/post-type-variables-and-shortcodes/#staff_shortcode

  Example:
  [ESPRESSO_STAFF outside_wrapper="div" outside_wrapper_class="event_staff" inside_wrapper="p" inside_wrapper_class="event_person"]

  Parameters:
  id (The id of the staff member. The daefault is auto loaded of from the event.)
  outside_wrapper_class
  outside_wrapper
  inside_wrapper_class
  inside_wrapper
  name_class
  name_wrapper
  image_class
  show_image (true|false default true)
  show_staff_titles (true|false default true)
  show_staff_roles (true|false default true)
  show_staff_details (true|false default true)
  show_image (true|false default true)
  show_description (true|false default true)
 * */
if (!function_exists('espresso_staff_sc')) {

	function espresso_staff_sc($atts) {

		global $wpdb, $espresso_premium, $this_event_id;
		if ($espresso_premium != true)
			return;

		empty($atts) ? '' : extract($atts);

		//Outside wrapper
		$outside_wrapper_class = isset($outside_wrapper_class) ? 'class="' . $outside_wrapper_class . '"' : 'class="event_staff"';
		$wrapper_start = isset($outside_wrapper) ? '<' . $outside_wrapper . ' ' . $outside_wrapper_class : '<div ' . $outside_wrapper_class;
		$wrapper_end = isset($outside_wrapper) ? '</' . $outside_wrapper . '>' : '</div>';

		//Persons title
		$name_class = isset($name_class) ? 'class="' . $name_class . '"' : 'class="person_name"';
		$name_wrapper_start = isset($name_wrapper) ? '<' . $name_wrapper . ' ' . $name_class . '>' : '<strong ' . $name_class . '>';
		$name_wrapper_end = isset($name_wrapper) ? '</' . $name_wrapper . '>' : '</strong>';

		//Image class
		$image_class = isset($image_class) ? 'class="' . $image_class . '"' : 'class="staff_image"';
		$image_wrapper_class = isset($image_wrapper_class) ? 'class="' . $image_wrapper_class . '"' : 'class="image_wrapper"';
		$image_wrapper_start = isset($image_wrapper) ? '<' . $image_wrapper . ' ' . $image_wrapper_class : '<p ' . $image_wrapper_class . '>';
		$image_wrapper_end = isset($image_wrapper) ? '</' . $image_wrapper . '>' : '</p>';

		//Inside wrappers
		$inside_wrapper_class = isset($inside_wrapper_class) ? 'class="' . $inside_wrapper_class . '"' : 'class="event_person"';
		$inside_wrapper_before = isset($inside_wrapper) ? '<' . $inside_wrapper . ' ' . $inside_wrapper_class . '>' : '<p ' . $inside_wrapper_class . '>';
		$inside_wrapper_after = isset($inside_wrapper) ? '</' . $inside_wrapper . '>' : '</p>';

		//Show the persons title?
		$show_staff_titles = isset($show_staff_titles) && $show_staff_titles == 'false' ? false : true;
		
		//Show the persons role?
		$show_staff_roles = isset($show_staff_roles) && $show_staff_roles == 'false' ? false : true;

		//Show the persons details?
		$show_staff_details = isset($show_staff_details) && $show_staff_details == 'false' ? false : true;

		//Show image?
		$show_image = isset($show_image) && $show_image == 'false' ? false : true;

		//Show the description?
		$show_description = isset($show_description) && $show_description == 'false' ? false : true;

		//Find the event id
		if (isset($event_id)) {
			$event_id = $event_id; //Check to see if the event is used in the shortcode parameter
		} elseif (isset($this_event_id)) {
			$event_id = $this_event_id; //Check to see if the global event id is being used
		} elseif (isset($_REQUEST['event_id'])) {
			$event_id = $_REQUEST['event_id']; //If the first two are not being used, then get the event id from the url
		} elseif (!isset($event_id) && !isset($id)) {
			//_e('No event or staff id supplied!', 'event_espresso') ;
			return;
		}
		$limit = isset($limit) && $limit > 0 ? " LIMIT 0," . $limit . " " : '';
		$sql = "SELECT s.id, s.name, s.role, s.meta ";
		$sql .= " FROM " . EVENTS_PERSONNEL_TABLE . ' s ';
		if (isset($id) && $id > 0) {
			$sql .= " WHERE s.id ='" . $id . "' ";
		} else {
			$sql .= " JOIN " . EVENTS_PERSONNEL_REL_TABLE . " r ON r.person_id = s.id ";
			$sql .= " WHERE r.event_id ='" . $event_id . "' ";
		}
		$sql .= $limit;
		//echo $sql;
		$event_personnel = $wpdb->get_results($sql);
		$num_rows = $wpdb->num_rows;
		if ($num_rows > 0) {
			$html = '';
			foreach ($event_personnel as $person) {
				$person_id = $person->id;
				$person_name = $person->name;
				$person_role = $person->role;

				$meta = unserialize($person->meta);

				$html .= $wrapper_start . ' id="person_id_' . $person_id . '">';

				//Build the persons name/title
				$html .= $inside_wrapper_before;
				
				if ($show_staff_roles != false) {
					$person_title = $person_role != '' ? ' - ' . stripslashes_deep($person_role) : '';
				}
				
				$html .= $name_wrapper_start . stripslashes_deep($person_name) . $name_wrapper_end . $person_title;
				$html .= $inside_wrapper_after;

				//Build the image
				if ($show_image != false) {
					$html .= $meta['image'] != '' ? $image_wrapper_start . '<img id="staff_image_' . $person_id . '" ' . $image_class . ' src="' . stripslashes_deep($meta['image']) . '" />' . $image_wrapper_end : '';
				}

				//Build the description
				if ($show_description != false) {
					$html .= $meta['description'] != '' ? html_entity_decode(stripslashes_deep($meta['description'])) : '';
				}

				//Build the additional details
				if ($show_staff_details != false) {
					$html .= $inside_wrapper_before;
					$html .= isset($meta['organization']) ? __('Company:', 'event_espresso') . ' ' . stripslashes_deep($meta['organization']) . '<br />' : '';
					if ($show_staff_titles != false) {
						$html .= isset($meta['title']) ? __('Title:', 'event_espresso') . ' ' . stripslashes_deep($meta['title']) . '<br />' : '';
					}
					$html .= isset($meta['industry']) ? __('Industry:', 'event_espresso') . ' ' . stripslashes_deep($meta['industry']) . '<br />' : '';
					$html .= isset($meta['city']) ? __('City:', 'event_espresso') . ' ' . stripslashes_deep($meta['city']) . '<br />' : '';
					$html .= isset($meta['country']) ? __('Country:', 'event_espresso') . ' ' . stripslashes_deep($meta['country']) . '<br />' : '';
					$html .= isset($meta['website']) ? __('Website:', 'event_espresso') . ' <a href="' . stripslashes_deep($meta['website']) . '" target="_blank">' . stripslashes_deep($meta['website']) . '</a><br />' : '';
					$html .= isset($meta['twitter']) ? __('Twitter:', 'event_espresso') . ' <a href="http://twitter.com/#!/' . stripslashes_deep($meta['twitter']) . '" target="_blank">@' . stripslashes_deep($meta['twitter']) . '</a><br />' : '';
					$html .= isset($meta['phone']) ? __('Phone:', 'event_espresso') . ' ' . stripslashes_deep($meta['phone']) . '<br />' : '';
					$html .= $inside_wrapper_after;
				}


				$html .= $wrapper_end;
			}
		}

		ob_start();
		echo wpautop($html);
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

}
add_shortcode('ESPRESSO_STAFF', 'espresso_staff_sc');

/**
  Venue Details Shortcode
  http://eventespresso.com/forums/2010/10/post-type-variables-and-shortcodes/#venue_shortcode

  Example:
  [ESPRESSO_VENUE outside_wrapper="div" outside_wrapper_class="event_venue"]

  Parameters:
  outside_wrapper
  outside_wrapper_class
  title_wrapper
  title_class
  inside_wrapper
  inside_wrapper_class
  image_class
  show_google_map_link (true|false default true)
  map_link_text
  show_map_image (true|false default true)
  map_image_wrapper
  map_image_class
  map_w (map image width default 400)
  map_h (map image height default 400)
  show_title (true|false default true)
  show_image (true|false default true)
  show_description (true|false default true)
  show_address (true|false default true)
  show_additional_details (true|false default true)
 * */
if (!function_exists('espresso_venue_details_sc')) {

	function espresso_venue_details_sc($atts) {

		global $wpdb, $this_event_id;

		empty($atts) ? '' : extract($atts);

		//Outside wrapper
		$outside_wrapper_class = isset($outside_wrapper_class) ? 'class="' . $outside_wrapper_class . '"' : 'class="event_venue"';
		$wrapper_start = isset($outside_wrapper) ? '<' . $outside_wrapper . ' ' . $outside_wrapper_class : '<div ' . $outside_wrapper_class;
		$wrapper_end = isset($outside_wrapper) ? '</' . $outside_wrapper . '>' : '</div>';

		//Image class
		$image_class = isset($image_class) ? 'class="' . $image_class . '"' : 'class="venue_image"';
		$image_wrapper_class = isset($image_wrapper_class) ? 'class="' . $image_wrapper_class . '"' : 'class="image_wrapper"';
		$image_wrapper_start = isset($image_wrapper) ? '<' . $image_wrapper . ' ' . $image_wrapper_class : '<p ' . $image_wrapper_class . '>';
		$image_wrapper_end = isset($image_wrapper) ? '</' . $image_wrapper . '>' : '</p>';

		//Venue title
		$title_class = isset($title_class) ? 'class="' . $title_class . '"' : 'class="venue_name"';
		$title_wrapper_start = isset($title_wrapper) ? '<' . $title_wrapper . ' ' . $title_class : '<h3 ' . $title_class;
		$title_wrapper_end = isset($title_wrapper) ? '</' . $title_wrapper . '>' : '</h3>';

		//Inside wrappers
		$inside_wrapper_class = isset($inside_wrapper_class) ? 'class="' . $inside_wrapper_class . '"' : 'class="venue_details"';
		$inside_wrapper_before = isset($inside_wrapper) ? '<' . $inside_wrapper . ' ' . $inside_wrapper_class . '>' : '<p ' . $inside_wrapper_class . '>';
		$inside_wrapper_after = isset($inside_wrapper) ? '</' . $inside_wrapper . '>' : '</p>';

		//Map image class
		$map_image_class = isset($map_image_class) ? 'class="' . $map_image_class . '"' : 'class="venue_map_image"';
		$map_image_wrapper_class = isset($map_image_wrapper_class) ? 'class="' . $map_image_wrapper_class . '"' : 'class="map_image_wrapper"';
		$map_image_wrapper_start = isset($map_image_wrapper) ? '<' . $map_image_wrapper . ' ' . $map_image_wrapper_class : '<p ' . $map_image_wrapper_class;
		$map_image_wrapper_end = isset($map_image_wrapper) ? '</' . $map_image_wrapper . '>' : '</p>';

		//Google Map link text
		$show_google_map_link = isset($show_google_map_link) && $show_google_map_link == 'false' ? false : true;
		$map_link_text = isset($map_link_text) ? $map_link_text : __('Map and Directions', 'event_espresso');

		//Show Google map image?
		$show_map_image = isset($show_map_image) && $show_map_image == 'false' ? false : true;

		//Show title?
		$show_title = isset($show_title) && $show_title == 'false' ? false : true;

		//Show image?
		$show_image = isset($show_image) && $show_image == 'false' ? false : true;

		//Show the description?
		$show_description = isset($show_description) && $show_description == 'false' ? false : true;

		//Show address details?
		$show_address = isset($show_address) && $show_address == 'false' ? false : true;

		//Show additional details
		$show_additional_details = isset($show_additional_details) && $show_additional_details == 'false' ? false : true;

		$FROM = " FROM ";
		$order_by = isset($order_by) && $order_by != '' ? " ORDER BY " . $order_by . " ASC " : " ORDER BY name ASC ";
		$limit = $limit > 0 ? " LIMIT 0," . $limit . " " : '';

		$using_id = false;
		//Find the event id
		if (isset($id) && $id > 0) {

		} elseif (isset($event_id)) {
			$event_id = $event_id; //Check to see if the event is used in the shortcode parameter
			$using_id = true;
		} elseif (isset($this_event_id)) {
			$event_id = $this_event_id; //Check to see if the global event id is being used
			$using_id = true;
		} elseif (isset($_REQUEST['event_id'])) {
			$event_id = $_REQUEST['event_id']; //If the first two are not being used, then get the event id from the url
			$using_id = true;
		}

		$sql = "SELECT ev.* ";

		if ($using_id == true) {
			$sql .= " $FROM " . EVENTS_DETAIL_TABLE . " e ";
			$sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON e.id = vr.event_id ";
			$FROM = " LEFT JOIN ";
		}

		$sql .= " $FROM " . EVENTS_VENUE_TABLE . " ev ";

		if ($using_id == true) {
			$sql .= " ON vr.venue_id = ev.id ";
		}

		if (isset($id) && $id > 0) {
			$sql .= " WHERE ev.id = '" . $id . "' ";
		} elseif (isset($event_id) && $event_id > 0) {
			$sql .= " WHERE e.id ='" . $event_id . "' ";
		} else {
			$sql .= " GROUP BY ev.name ";
		}

		if ($using_id == false) {
			$sql .= $order_by;
			$sql .= $limit;
		}
		//echo $sql ;

		$venues = $wpdb->get_results($sql);

		$num_rows = $wpdb->num_rows;
		if ($num_rows > 0) {
			$html = '';
			foreach ($venues as $venue) {
				$venue_id = $venue->id;
				$meta = unserialize($venue->meta);

				//Google map link creation
				$google_map_link = espresso_google_map_link(array('address' => $venue->address, 'city' => $venue->city, 'state' => $venue->state, 'zip' => $venue->zip, 'country' => $venue->country, 'text' => $map_link_text, 'type' => 'text'));

				//Google map image creation
				if ($show_map_image != false) {
					$map_w = isset($map_w) ? $map_w : 400;
					$map_h = isset($map_h) ? $map_h : 400;
					$google_map_image = espresso_google_map_link(array('id' => $venue_id, 'map_image_class' => $map_image_class, 'address' => $venue->address, 'city' => $venue->city, 'state' => $venue->state, 'zip' => $venue->zip, 'country' => $venue->country, 'text' => $map_link_text, 'type' => 'map', 'map_h' => $map_h, 'map_w' => $map_w));
				}

				//Build the venue title
				if ($show_title != false) {
					$html .= $venue->name != '' ? $title_wrapper_start . '>' . stripslashes_deep($venue->name) . $title_wrapper_end : '';
				}

				//Build the venue image
				if ($show_image != false) {
					$html .= $meta['image'] != '' ? $image_wrapper_start . '<img id="venue_image_' . $venue_id . '" ' . $image_class . ' src="' . stripslashes_deep($meta['image']) . '" />' . $image_wrapper_end : '';
				}

				//Build the description
				if ($show_description != false) {
					$html .= $meta['description'] != '' ? espresso_format_content($meta['description']) : '';
				}

				//Build the address details
				if ($show_address != false) {
					$html .= $inside_wrapper_before;
					$html .= $venue->address != '' ? stripslashes_deep($venue->address) . '<br />' : '';
					$html .= $venue->address2 != '' ? stripslashes_deep($venue->address2) . '<br />' : '';
					$html .= $venue->city != '' ? stripslashes_deep($venue->city) . '<br />' : '';
					$html .= $venue->state != '' ? stripslashes_deep($venue->state) . '<br />' : '';
					$html .= $venue->zip != '' ? stripslashes_deep($venue->zip) . '<br />' : '';
					$html .= $venue->country != '' ? stripslashes_deep($venue->country) . '<br />' : '';
					$html .= $show_google_map_link != false ? $google_map_link : '';
					$html .= $inside_wrapper_after;
				}

				//Build the additional details
				if ($show_additional_details != false) {
					$html .= $inside_wrapper_before;
					$html .= $meta['website'] != '' ? __('Website:', 'event_espresso') . ' <a href="' . stripslashes_deep($meta['website']) . '" target="_blank">' . stripslashes_deep($meta['website']) . '</a><br />' : '';
					$html .= $meta['contact'] != '' ? __('Contact:', 'event_espresso') . ' ' . stripslashes_deep($meta['contact']) . '<br />' : '';
					$html .= $meta['phone'] != '' ? __('Phone:', 'event_espresso') . ' ' . stripslashes_deep($meta['phone']) . '<br />' : '';
					$html .= $meta['twitter'] != '' ? __('Twitter:', 'event_espresso') . ' <a href="http://twitter.com/#!/' . stripslashes_deep($meta['twitter']) . '" target="_blank">@' . stripslashes_deep($meta['twitter']) . '</a><br />' : '';
					$html .= $inside_wrapper_after;
				}

				//Build the venue image
				if ($show_map_image != false) {
					$html .= $map_image_wrapper_start . $google_map_image . $map_image_wrapper_end;
				}
			}
		}
		//ob_start();
		return $wrapper_start . ' id="venue_id_' . $venue_id . '">' . $html . $wrapper_end;
		//$buffer = ob_get_contents();
		//ob_end_clean();
		//return $buffer;
	}

}
add_shortcode('ESPRESSO_VENUE', 'espresso_venue_details_sc');

if (!function_exists('espresso_venue_event_list_sc')) {

	function espresso_venue_event_list_sc($atts) {
		global $wpdb;
		global $load_espresso_scripts;
		$load_espresso_scripts = true; //This tells the plugin to load the required scripts
		if (empty($atts))
			return 'No venue id supplied!';
		extract($atts);
		if (isset($id) && $id > 0) {
			$atts = array_merge($atts, array('venue_id'=>$id, 'use_venue_id'=>true));
		}
			
//		$order_by = (isset($order_by) && $order_by != '') ? " ORDER BY " . $order_by . " ASC " : " ORDER BY name, id ASC ";
//		$limit = $limit > 0 ? " LIMIT 0," . $limit . " " : '';
//
//		if (isset($id) && $id > 0) {
//			$sql = "SELECT e.*, ev.name venue_name, ese.start_time, ese.end_time, p.event_cost ";
//			$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
//			$sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON e.id = vr.event_id ";
//			$sql .= " LEFT JOIN " . EVENTS_VENUE_TABLE . " ev ON vr.venue_id = ev.id  ";
//			$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id= e.id ";
//			$sql .= " LEFT JOIN " . EVENTS_PRICES_TABLE . " p ON p.event_id=e.id ";
//			$sql .= " WHERE e.event_status != 'D' AND e.is_active = 'Y' AND ev.id = '" . $id . "' ";
//
//			$sql .= $order_by;
//			$sql .= $limit;
//			//echo $sql;
//
//			$wpdb->get_results($sql);
//			$num_rows = $wpdb->num_rows;
//			if ($num_rows > 0) {
//
//				$name_before = isset($name_before) ? $name_before : '<p class="venue_name">';
//				$name_after = isset($name_after) ? $name_after : '</p>';
//
//				$venue_name = $wpdb->last_result[0]->venue_name;

				//template located in event_list_dsiplay.php
				ob_start();
				//echo $sql;
				//echo $name_before . $venue_name . $name_after;
				event_espresso_get_event_details($atts);
				$buffer = ob_get_contents();
				ob_end_clean();
				return $buffer;
			//} else {
//				return 'No events in this venue';
//			}
//		}
	}

}
add_shortcode('ESPRESSO_VENUE_EVENTS', 'espresso_venue_event_list_sc');

function ee_show_meta_sc($atts) {
	global $event_meta, $venue_meta, $all_meta;
	//echo '<p>event_meta = '.print_r($event_meta).'</p>';
	if (empty($atts))
		return;

	extract($atts);

	if (!isset($name))
		return;

	switch ($type) {

		case 'venue':
		case 'venue_meta':
		default:
			return ee_show_meta($venue_meta, $name);

		case 'event':
		case 'event_meta':
			return ee_show_meta($event_meta, $name);

		case 'all':
		case 'all_meta':
		default:
			return ee_show_meta($all_meta, $name);
	}
}

add_shortcode('EE_META', 'ee_show_meta_Sc');

if (!function_exists('espresso_questions_answers')) {
	function espresso_questions_answers($atts) {
		global $wpdb;
		if (empty($atts))
			return;

		extract($atts);
		//echo '<p>'.print_r($atts).'</p>';

		$sql = "select qst.question as question, ans.answer as answer from " . EVENTS_ANSWER_TABLE . " ans inner join " . EVENTS_QUESTION_TABLE . " qst on ans.question_id = qst.id where ans.attendee_id = '" . $a . "' AND qst.id= '" . $q . "' ";
		//echo '<p>'.$sql.'</p>';
		//Get the questions and answers
		$questions = $wpdb->get_results($sql, ARRAY_A);
		//echo '<p>'.print_r($questions).'</p>';

		if ($wpdb->num_rows > 0 && $wpdb->last_result[0]->question != NULL) {
			foreach ($questions as $q) {
				//$k = $q['question'];
				$v = $q['answer'];
				return rtrim($v, ',');
			}
		}
	}
}
add_shortcode('EE_ANSWER', 'espresso_questions_answers');
