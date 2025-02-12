<?php

// TODO find out why $post_content is only added to the first post in case of a recurring event

function update_event($recurrence_arr = array()) {
    //print_r($_REQUEST);
    global $wpdb, $org_options, $current_user, $espresso_premium;


    $wpdb->show_errors();
    /*
     * Begin Recurrence handling
     *
     * Will clean up in V 1.2.0
     *
     */
    if (defined('EVENT_ESPRESSO_RECURRENCE_TABLE')) {
        require_once(EVENT_ESPRESSO_RECURRENCE_FULL_PATH . "functions/re_functions.php");

        if ($_POST['recurrence_id'] > 0) {
            /*
             * If the array is empty, then find the recurring dates
             */
            if (count($recurrence_arr) == 0) {

                // Prepare the parameters array for use with various RE functions
                $re_params = array(
                    'start_date' => !empty($_POST['recurrence_start_date']) ? $_POST['recurrence_start_date'] :'',
                    'event_end_date' => !empty($_POST['recurrence_event_end_date']) ? $_POST['recurrence_event_end_date'] : '',
                    'end_date' => !empty($_POST['recurrence_end_date']) ? $_POST['recurrence_end_date'] : '',
                    'registration_start' => !empty($_POST['recurrence_regis_start_date']) ? $_POST['recurrence_regis_start_date'] : '',
                    'registration_end' => !empty($_POST['recurrence_regis_end_date']) ? $_POST['recurrence_regis_end_date'] : '',
                    'frequency' => !empty($_POST['recurrence_frequency']) ? $_POST['recurrence_frequency'] : '',
                    'interval' => !empty($_POST['recurrence_interval']) ? $_POST['recurrence_interval'] : '',
                    'recurrence_type' => !empty($_POST['recurrence_type']) ? $_POST['recurrence_type'] : '',
                    'weekdays' => !empty($_POST['recurrence_weekday']) ? $_POST['recurrence_weekday'] : '',
                    'repeat_by' => !empty($_POST['recurrence_repeat_by']) ? $_POST['recurrence_repeat_by'] : '',
                    'recurrence_regis_date_increment' => !empty($_POST['recurrence_regis_date_increment']) ? $_POST['recurrence_regis_date_increment'] : '',
                    'recurrence_manual_dates' => !empty($_POST['recurrence_manual_dates']) ? $_POST['recurrence_manual_dates'] : '',
                    'recurrence_manual_end_dates' => !empty($_POST['recurrence_manual_end_dates']) ? $_POST['recurrence_manual_end_dates'] : '',
                    'recurrence_visibility' => ''/*$_POST['recurrence_visibility']*/,
                    'recurrence_id' => !empty($_POST['recurrence_id']) ? $_POST['recurrence_id'] : ''
                );

                //$re_params['adding_to_db'] = 'Y';
                //Has the form been modified
                $recurrence_form_modified = recurrence_form_modified($re_params);

                //echo ($recurrence_form_modified) ? "Yes" : 'No';


                if ($_POST['recurrence_apply_changes_to'] == 2) {
                    //Update all events in the series based on recurrence id

                    $recurrence_dates = ($_POST['recurrence_type'] == 'm') ? find_recurrence_manual_dates($re_params) : find_recurrence_dates($re_params);

                    //$DEL_SQL = 'UPDATE ' . EVENTS_DETAIL_TABLE . " SET event_status = 'D' WHERE start_date NOT IN (" . $delete_in . ") AND recurrence_id = " . $wpdb->escape($_POST['recurrence_id']);


                    $UPDATE_SQL = "SELECT id,start_date,event_identifier FROM " . EVENTS_DETAIL_TABLE . " WHERE recurrence_id = %d AND NOT event_status = 'D'";
                } else {
                    //Update this and upcoming events based on recurrence id and start_date >=start_date
                    $re_params['start_date'] = $_POST['start_date'];
                    $recurrence_dates = find_recurrence_dates($re_params);

                    //$DEL_SQL = 'UPDATE ' . EVENTS_DETAIL_TABLE . " SET event_status = 'D' WHERE start_date >='" . $wpdb->escape($_POST['start_date']) . "' AND start_date NOT IN (" . $delete_in . ") AND recurrence_id = " . $_POST['recurrence_id'];
                    $UPDATE_SQL = "SELECT id,start_date,event_identifier FROM " . EVENTS_DETAIL_TABLE . " WHERE start_date >='" . $wpdb->escape($_POST['start_date']) . "' AND recurrence_id = %d and NOT event_status = 'D' ";
                }

                //Recurrence Form modified and changes need to apply to all
                if ($recurrence_form_modified && $_POST['recurrence_apply_changes_to'] > 1) {

                    //Update the recurrence table record with the new RE selections
                    update_recurrence_master_record();

                    /*
                     * Delete the records that don't belong in the formula
                     */

                    if (count($recurrence_dates) > 0) {
                        $delete_in = '';
                        foreach ($recurrence_dates as $k => $v) {
                            $delete_in .= "'" . $k . "',";
                        }
                        $delete_in = substr($delete_in, 0, -1);
                    }


                    if ($_POST['recurrence_apply_changes_to'] == 2) {
                        //Update all events in the series based on recurrence id
                        //$DEL_SQL = 'UPDATE ' . EVENTS_DETAIL_TABLE . " SET event_status = 'D' WHERE start_date NOT IN (" . $delete_in . ") AND recurrence_id = " . $_POST['recurrence_id'];
                        $DEL_SQL = 'DELETE EDT, EAT FROM ' . EVENTS_DETAIL_TABLE . " EDT
                            LEFT JOIN " . EVENTS_ATTENDEE_TABLE . " EAT
                                ON EDT.id = EAT.event_id
                            WHERE EAT.id IS NULL
                            AND EDT.start_date NOT IN (" . $delete_in . ")
                            AND recurrence_id = " . $_POST['recurrence_id'];

                        $UPDATE_SQL = "SELECT id,start_date,event_identifier FROM " . EVENTS_DETAIL_TABLE . " WHERE recurrence_id = %d and NOT event_status = 'D' ORDER BY start_date";
                    } else {
                        //Update this and upcoming events based on recurrence id and start_date >=start_date
                        //$DEL_SQL = 'UPDATE ' . EVENTS_DETAIL_TABLE . " SET event_status = 'D' WHERE start_date >='" . $wpdb->escape($_POST['start_date']) . "' AND start_date NOT IN (" . $delete_in . ") AND recurrence_id = " . $_POST['recurrence_id'];
                        $DEL_SQL = 'DELETE EDT, EAT FROM ' . EVENTS_DETAIL_TABLE . " EDT
                            LEFT JOIN " . EVENTS_ATTENDEE_TABLE . " EAT
                                ON EDT.id = EAT.event_id
                            WHERE EAT.id IS NULL
                            AND EDT.start_date >='" . $wpdb->escape($_POST['start_date']) . "'
                            AND EDT.start_date NOT IN (" . $delete_in . ")
                            AND recurrence_id = " . $_POST['recurrence_id'];
                        $UPDATE_SQL = "SELECT id,start_date,event_identifier FROM " . EVENTS_DETAIL_TABLE . " WHERE start_date >='" . $wpdb->escape($_POST['start_date']) . "' AND recurrence_id = %d AND NOT event_status = 'D'  ORDER BY start_date";
                    }

                    if ($delete_in != '')
                        $wpdb->query($wpdb->prepare($DEL_SQL));

                    /*
                     * Add the new records based on the new formula
                     * The $recurrence_dates array will contain the new dates
                     */
                    if (!function_exists('add_event_to_db')) {
                        require_once ('insert_event.php');
                    }

                    foreach ($recurrence_dates as $k => $v) {
                        $result = $wpdb->get_row($wpdb->prepare("SELECT ID FROM " . EVENTS_DETAIL_TABLE . " WHERE recurrence_id = %d and start_date = %s and NOT event_status = 'D'", array($_POST['recurrence_id'], $k)));

                        if ($wpdb->num_rows == 0) {
                            add_event_to_db(array(
                                'recurrence_id' => $_POST['recurrence_id'],
                                'recurrence_start_date' => $v['start_date'],
                                'recurrence_event_end_date' => $v['event_end_date'],
                                'recurrence_end_date' => $v['start_date'],
                                'registration_start' => $v['registration_start'],
                                'registration_end' => $v['registration_end']));
                        } else {

                        }
                    }

                    /*
                     * Find all the event ids in the series and feed into the $recurrence_dates array
                     * This array will be used at the end of this document to invoke the recursion of update_event function so all the events in the series
                     * can be updated with the information.
                     */
                }

                $result = $wpdb->get_results($wpdb->prepare($UPDATE_SQL, array($_POST['recurrence_id'])));

                foreach ($result as $row) {
                    if ($row->start_date != '') {
                        $recurrence_dates[$row->start_date]['event_id'] = $row->id;
                        $recurrence_dates[$row->start_date]['event_identifier'] = $row->event_identifier;
                    }
                }
            }
        }
    }

    //  echo_f('rd',$recurrence_dates);


    if (defined('EVENT_ESPRESSO_RECURRENCE_MODULE_ACTIVE') &&
        !empty($_POST['recurrence']) && $_POST['recurrence'] == 'Y' &&
        count($recurrence_arr) == 0 && $_POST['recurrence_apply_changes_to'] > 1) {
//skip the first update
    } else {

        $event_meta = array(); //will be used to hold event meta data

        $event_id = array_key_exists('event_id', $recurrence_arr) ? $recurrence_arr['event_id'] : $_REQUEST['event_id'];
        $event_name = $_REQUEST['event'];
        //$event_identifier=array_key_exists('event_identifier', $recurrence_arr)?$recurrence_arr['event_identifier']:($_REQUEST['event_identifier'] == '') ? $event_identifier = sanitize_title_with_dashes($event_name.'-'.rand()) : $event_identifier = sanitize_title_with_dashes($_REQUEST['event_identifier']);
        $event_desc = $_REQUEST['event_desc'];
        $display_desc = $_REQUEST['display_desc'];
        $display_reg_form = $_REQUEST['display_reg_form'];
        $reg_limit = $_REQUEST['reg_limit'];
        $allow_multiple = $_REQUEST['allow_multiple'];

        $overflow_event_id = (empty($_REQUEST['overflow_event_id'])) ? '0' : $_REQUEST['overflow_event_id'];
        $allow_overflow = empty($_REQUEST['allow_overflow']) ? 'N' : $_REQUEST['allow_overflow'];

        $additional_limit = $_REQUEST['additional_limit'];
        //$member_only=$_REQUEST['member_only'];
        $member_only = empty($_REQUEST['member_only']) ? 'N' : $_REQUEST['member_only'];

        $is_active = $_REQUEST['is_active'];
        $event_status = $_REQUEST['event_status'];

        $address = !empty($_REQUEST['address']) ? esc_html($_REQUEST['address']):'';
        $address2 = !empty($_REQUEST['address2']) ? esc_html($_REQUEST['address2']):'';
        $city = !empty($_REQUEST['city']) ? esc_html($_REQUEST['city']):'';
        $state = !empty($_REQUEST['state']) ? esc_html($_REQUEST['state']):'';
        $zip = !empty($_REQUEST['zip']) ? esc_html($_REQUEST['zip']):'';
        $country = !empty($_REQUEST['country']) ? esc_html($_REQUEST['country']):'';
        $phone = !empty($_REQUEST['phone']) ? esc_html($_REQUEST['phone']):'';
        $externalURL = !empty($_REQUEST['externalURL']) ? esc_html($_REQUEST['externalURL']):'';

        //$event_location = $address . ' ' . $city . ', ' . $state . ' ' . $zip;
        $event_location = ($address != '' ? $address . ' ' : '') . ($city != '' ? '<br />' . $city : '') . ($state != '' ? ', ' . $state : '') . ($zip != '' ? '<br />' . $zip : '') . ($country != '' ? '<br />' . $country : '');

        //Get the first instance of the start and end times
        $start_time = $_REQUEST['start_time'][0];
        $end_time = $_REQUEST['end_time'][0];

        // Add registration times
        $registration_startT = event_date_display($_REQUEST['registration_startT'], 'H:i');
        $registration_endT = event_date_display($_REQUEST['registration_endT'], 'H:i');

        //Add timezone
        $timezone_string = empty($_REQUEST['timezone_string']) ? '' : $_REQUEST['timezone_string'];

        //Early discounts
        $early_disc = $_REQUEST['early_disc'];
        $early_disc_date = $_REQUEST['early_disc_date'];
        $early_disc_percentage = $_REQUEST['early_disc_percentage'];

        $conf_mail = $_REQUEST['conf_mail'];
        $use_coupon_code = $_REQUEST['use_coupon_code'];
        $alt_email = $_REQUEST['alt_email'];

        $send_mail = $_REQUEST['send_mail'];
        $email_id = isset($_REQUEST['email_name']) ? $_REQUEST['email_name'] : '0';
		
		$ticket_id = isset($_REQUEST['ticket_id']) ? $_REQUEST['ticket_id'] : '0';
		

        $event_category = serialize(empty($_REQUEST['event_category']) ? '' : $_REQUEST['event_category']);
        $event_discount = serialize(empty($_REQUEST['event_discount']) ? '' : $_REQUEST['event_discount']);

        $registration_start = array_key_exists('registration_start', $recurrence_arr) ? $recurrence_arr['registration_start'] : $_REQUEST['registration_start'];
        $registration_end = array_key_exists('registration_end', $recurrence_arr) ? $recurrence_arr['registration_end'] : $_REQUEST['registration_end'];

        $start_date = array_key_exists('recurrence_start_date', $recurrence_arr) ? $recurrence_arr['recurrence_start_date'] : ($_REQUEST['start_date'] == '' ? $_REQUEST['recurrence_start_date'] : $_REQUEST['start_date']);
        $end_date = array_key_exists('recurrence_event_end_date', $recurrence_arr) ? $recurrence_arr['recurrence_event_end_date'] : ($_REQUEST['end_date'] == '' ? $_REQUEST['recurrence_start_date'] : $_REQUEST['end_date']);

        $visible_on = array_key_exists('visible_on', $recurrence_arr) ? $recurrence_arr['visible_on'] : empty($_REQUEST['visible_on']) ? '' : $_REQUEST['visible_on'];

        //Venue Information
        $venue_title = isset($_REQUEST['venue_title']) ? $_REQUEST['venue_title']:'';
        $venue_url = isset($_REQUEST['venue_url']) ? $_REQUEST['venue_url']:'';
        $venue_phone = isset($_REQUEST['venue_phone']) ? $_REQUEST['venue_phone']:'';
        $venue_image = isset($_REQUEST['venue_image']) ? $_REQUEST['venue_image']:'';


        //Virtual location
        $virtual_url = isset($_REQUEST['virtual_url']) ? $_REQUEST['virtual_url']:'';
        $virtual_phone = isset($_REQUEST['virtual_phone']) ? $_REQUEST['virtual_phone']:'';

        if (isset($reg_limit) && $reg_limit == '') {
            $reg_limit = 999999;
        }

        $question_groups = serialize($_REQUEST['question_groups']);
        $add_attendee_question_groups = empty($_REQUEST['add_attendee_question_groups']) ? '' : $_REQUEST['add_attendee_question_groups'];

        $event_meta['default_payment_status'] = $_REQUEST['default_payment_status'];
        $event_meta['venue_id'] = empty($_REQUEST['venue_id']) ? '' : $_REQUEST['venue_id'][0];
        $event_meta['additional_attendee_reg_info'] = $_REQUEST['additional_attendee_reg_info'];
        $event_meta['add_attendee_question_groups'] = $add_attendee_question_groups;//empty($_REQUEST['add_attendee_question_groups']) ? '' : $_REQUEST['add_attendee_question_groups'];
        $event_meta['date_submitted'] = $_REQUEST['date_submitted'];
		
		/*
		 * Added for seating chart addon
		 */
		if ( isset($_REQUEST['seating_chart_id']) )
		{
			$cls_seating_chart = new seating_chart();
			$seating_chart_result = $cls_seating_chart->associate_event_seating_chart($_REQUEST['seating_chart_id'],$event_id);
			$tmp_seating_chart_id = $_REQUEST['seating_chart_id'];
			if ( $tmp_seating_chart_id > 0 )
			{
				if ( $seating_chart_result === false )
				{
					$tmp_seating_chart_row = $wpdb->get_row($wpdb->prepare("select seating_chart_id from ".EVENTS_SEATING_CHART_EVENT_TABLE." where event_id = $event_id"));
					if ( $tmp_seating_chart_row !== NULL )
					{
						$tmp_seating_chart_id = $tmp_seating_chart_row->seating_chart_id;
					}
					else
					{
						$tmp_seating_chart_id = 0;
					}
					
				}
				
				if ( $_REQUEST['allow_multiple'] == 'Y' && isset($_REQUEST['seating_chart_id']) && $tmp_seating_chart_id > 0 )
				{
					
					$event_meta['additional_attendee_reg_info'] = 3;
				}
			}
		}
		/*
		 * End
		 */
		 
		if (isset($_REQUEST['upload_image']) && !empty($_REQUEST['upload_image']) )
			 $event_meta['event_thumbnail_url'] = $_REQUEST['upload_image'];
			
        if ($_REQUEST['emeta'] != '') {
            foreach ($_REQUEST['emeta'] as $k => $v) {
                $event_meta[$v] = $_REQUEST['emetad'][$k];
            }
        }
		
		//print_r($_REQUEST['emeta'] );
        $event_meta = serialize($event_meta);
        ############ Added by wp-developers ######################
        $require_pre_approval = 0;
        if (isset($_REQUEST['require_pre_approval'])) {
            $require_pre_approval = $_REQUEST['require_pre_approval'];
        }

        ################# END #################
        //When adding colums to the following arrays, be sure both arrays have equal values.
        $sql = array('event_name' => $event_name, 'event_desc' => $event_desc, 'display_desc' => $display_desc, 'display_reg_form' => $display_reg_form,
            'address' => $address, 'address2' => $address2, 'city' => $city, 'state' => $state, 'zip' => $zip, 'country' => $country, 'phone' => $phone, 'virtual_url' => $virtual_url,
            'virtual_phone' => $virtual_phone, 'venue_title' => $venue_title, 'venue_url' => $venue_url, 'venue_phone' => $venue_phone, 'venue_image' => $venue_image,
            'registration_start' => $registration_start, 'registration_end' => $registration_end, 'start_date' => $start_date, 'end_date' => $end_date,
            'allow_multiple' => $allow_multiple, 'send_mail' => $send_mail, 'is_active' => $is_active, 'event_status' => $event_status,
            'conf_mail' => $conf_mail, 'use_coupon_code' => $use_coupon_code, 'member_only' => $member_only, 'externalURL' => $externalURL,
            'early_disc' => $early_disc, 'early_disc_date' => $early_disc_date, 'early_disc_percentage' => $early_disc_percentage, 'alt_email' => $alt_email,
            'question_groups' => $question_groups, 'allow_overflow' => $allow_overflow,
            'overflow_event_id' => $overflow_event_id, 'additional_limit' => $additional_limit,
            'reg_limit' => $reg_limit, 'email_id' => $email_id, 'registration_startT' => $registration_startT, 'registration_endT' => $registration_endT, 'event_meta' => $event_meta, 'require_pre_approval' => $require_pre_approval, 'timezone_string' => $timezone_string, 'ticket_id' => $ticket_id);

        $sql_data = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d');

        $update_id = array('id' => $event_id);

        /* echo 'Debug: <br />';
          print_r($sql);
          echo '<br />';
          print 'Number of vars: ' . count ($sql);
          echo '<br />';
          print 'Number of cols: ' . count($sql_data); */


        if (function_exists('event_espresso_add_event_to_db_groupon')) {
            $sql = event_espresso_add_event_to_db_groupon($sql, $_REQUEST['use_groupon_code']);
            ///print count ($sql);
            $sql_data = array_merge((array) $sql_data, (array) '%s');
            //print count($sql_data);
            $wpdb->prepare($wpdb->update(EVENTS_DETAIL_TABLE, $sql, $update_id, $sql_data, array('%d')));
            /* echo 'Debug: <br />';
              print 'Number of vars: ' . count ($sql);
              echo '<br />';
              print 'Number of cols: ' . count($sql_data); */
        } else {
            $wpdb->prepare($wpdb->update(EVENTS_DETAIL_TABLE, $sql, $update_id, $sql_data, array('%d')));
            /* echo 'Debug: <br />';
              print 'Number of vars: ' . count ($sql);
              echo '<br />';
              print 'Number of cols: ' . count($sql_data); */
        }
        //print $wpdb->print_error();

        $del_cats = "DELETE FROM " . EVENTS_CATEGORY_REL_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($wpdb->prepare($del_cats));

        if (!empty($_REQUEST['event_category'])) {
            foreach ($_REQUEST['event_category'] as $k => $v) {
                if ($v != '') {
                    $sql_cat = "INSERT INTO " . EVENTS_CATEGORY_REL_TABLE . " (event_id, cat_id) VALUES ('" . $event_id . "', '" . $v . "')";
                    //echo "$sql_cat <br>";
                    $wpdb->query($wpdb->prepare($sql_cat));
                }
            }
        }

        $del_ppl = "DELETE FROM " . EVENTS_PERSONNEL_REL_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($wpdb->prepare($del_ppl));

        if (!empty($_REQUEST['event_person'])) {
            foreach ($_REQUEST['event_person'] as $k => $v) {
                if ($v != '') {
                    $sql_ppl = "INSERT INTO " . EVENTS_PERSONNEL_REL_TABLE . " (event_id, person_id) VALUES ('" . $event_id . "', '" . $v . "')";
                    //echo "$sql_ppl <br>";
                    $wpdb->query($wpdb->prepare($sql_ppl));
                }
            }
        }

        $del_venues = "DELETE FROM " . EVENTS_VENUE_REL_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($wpdb->prepare($del_venues));

        if (!empty($_REQUEST['venue_id'])) {
            foreach ($_REQUEST['venue_id'] as $k => $v) {
                if ($v != '' && $v != 0) {
                    $sql_venues = "INSERT INTO " . EVENTS_VENUE_REL_TABLE . " (event_id, venue_id) VALUES ('" . $event_id . "', '" . $v . "')";
                    //echo "$sql_venues <br>";
                    $wpdb->query($wpdb->prepare($sql_venues));
                }
            }
        }

        $del_discounts = "DELETE FROM " . EVENTS_DISCOUNT_REL_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($del_discounts);

        if (!empty($_REQUEST['event_discount'])) {
            foreach ($_REQUEST['event_discount'] as $k => $v) {
                if ($v != '') {
                    $sql_discount = "INSERT INTO " . EVENTS_DISCOUNT_REL_TABLE . " (event_id, discount_id) VALUES ('" . $event_id . "', '" . $v . "')";
                    //echo "$sql_discount <br>";
                    $wpdb->query($wpdb->prepare($sql_discount));
                }
            }
        }

        $del_times = "DELETE FROM " . EVENTS_START_END_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($del_times);

        if ($_REQUEST['start_time'] != '') {
            foreach ($_REQUEST['start_time'] as $k => $v) {
                if ($v != '') {
                    $time_qty = empty($_REQUEST['time_qty'][$k]) ? '0' : "'" . $_REQUEST['time_qty'][$k] . "'";
                    $sql_times = "INSERT INTO " . EVENTS_START_END_TABLE . " (event_id, start_time, end_time, reg_limit) VALUES ('" . $event_id . "', '" . event_date_display($v, 'H:i') . "', '" . event_date_display($_REQUEST['end_time'][$k], 'H:i') . "', " . $time_qty . ")";
                    //echo "$sql_times <br>";
                    $wpdb->query($wpdb->prepare($sql_times));
                }
            }
        }

        $del_prices = "DELETE FROM " . EVENTS_PRICES_TABLE . " WHERE event_id = '" . $event_id . "'";
        $wpdb->query($del_prices);

        if (!empty($_REQUEST['event_cost'])) {
            foreach ($_REQUEST['event_cost'] as $k => $v) {
                if ($v != '') {
                    $price_type = $_REQUEST['price_type'][$k] != '' ? $_REQUEST['price_type'][$k] : __('General Admission', 'event_espresso');
                    $member_price_type = !empty($_REQUEST['member_price_type'][$k]) ? $_REQUEST['member_price_type'][$k] : __('Members Admission', 'event_espresso');
                    $member_price = !empty($_REQUEST['member_price'][$k]) ? $_REQUEST['member_price'][$k] : $v;
                    $sql_prices = "INSERT INTO " . EVENTS_PRICES_TABLE . " (event_id, event_cost, surcharge, surcharge_type, price_type, member_price, member_price_type) VALUES ('" . $event_id . "', '" . $v . "', '" . $_REQUEST['surcharge'][$k] . "', '" . $_REQUEST['surcharge_type'][$k] . "', '" . $price_type . "', '" . $member_price . "', '" . $member_price_type . "')";
                    //echo "$sql_prices <br>";
                    $wpdb->query($wpdb->prepare($sql_prices));
                }
            }
        } else {
            $sql_price = "INSERT INTO " . EVENTS_PRICES_TABLE . " (event_id, event_cost, surcharge, price_type, member_price, member_price_type) VALUES ('" . $event_id . "', '0.00', '0.00', '" . __('Free', 'event_espresso') . "', '0.00', '" . __('Free', 'event_espresso') . "')";
            if ( !$wpdb->prepare($wpdb->query($sql_price)) ) {
                $error = true;
            }
        }

        ############# MailChimp Integration ###############
        if (defined('EVENTS_MAILCHIMP_ATTENDEE_REL_TABLE') && $espresso_premium == true) {
            MailChimpController::update_event_list_rel($event_id);
        }
        if (function_exists('espresso_fb_createevent') == 'true' && $espresso_premium == true) {
            espresso_fb_updateevent($event_id);
        }

        /// Create Event Post Code Here
        if ( isset( $_REQUEST[ 'create_post' ] ) ) {
            switch ( $_REQUEST[ 'create_post' ] ) {
                case 'N':
                    $sql = " SELECT * FROM " . EVENTS_DETAIL_TABLE;
                    $sql .= " WHERE id = '" . $event_id . "' ";
                    $wpdb->get_results($wpdb->prepare($sql));
                    $post_id = $wpdb->last_result[0]->post_id;
                    if ($wpdb->num_rows > 0 && !empty($_REQUEST['delete_post']) && $_REQUEST['delete_post'] == 'Y') {
                        $sql = array('post_id' => '', 'post_type' => '');
                        $sql_data = array('%d', '%s');
                        $update_id = array('id' => $event_id);
                        $wpdb->prepare($wpdb->update(EVENTS_DETAIL_TABLE, $sql, $update_id, $sql_data, array('%d')));
                        wp_delete_post($post_id, 'true');
                    }
                    break;

                case 'Y':
                    $post_type = $_REQUEST['espresso_post_type'];
                    if ($post_type == 'post') {
                        if (file_exists(EVENT_ESPRESSO_TEMPLATE_DIR . "event_post.php") || file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . "templates/event_post.php")) {
                            // Load message from template into message post variable
                            ob_start();
                            if (file_exists(EVENT_ESPRESSO_TEMPLATE_DIR . "event_post.php")) {
                                require_once(EVENT_ESPRESSO_TEMPLATE_DIR . "event_post.php");
                            } else {
                                require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "templates/event_post.php");
                            }
                            $post_content = ob_get_contents();
                            ob_end_clean();
                        } else {
                            _e('There was error finding a post template. Please verify your post templates are available.', 'event_espresso');
                        }
                    } elseif ($post_type == 'espresso_event') {
                        ob_start();
                        echo $event_desc;
                        $post_content = ob_get_contents();
                        ob_end_clean();
                        // if there's a cart link shortcode in the post, replace the shortcode with one that includes the event_id
                        if (preg_match("/ESPRESSO_CART_LINK/", $post_content)) { $post_content = preg_replace('/ESPRESSO_CART_LINK/', 'ESPRESSO_CART_LINK event_id=' . $event_id, $post_content); }
                    }

                    $my_post = array();

                    $sql = " SELECT * FROM " . EVENTS_DETAIL_TABLE;
                    $sql .= " WHERE id = '" . $event_id . "' ";
                    $wpdb->get_results($wpdb->prepare($sql));
                    $post_id = $wpdb->last_result[0]->post_id;


                    $post_type = $_REQUEST['espresso_post_type'];

                    if ($post_id > 0)
                        $my_post['ID'] = $post_id;

                    $my_post['post_title'] = esc_html($_REQUEST['event']);
                    $my_post['post_content'] = $post_content;
                    $my_post['post_status'] = 'publish';
					$my_post['post_author'] = !empty($_REQUEST['user']) ? $_REQUEST['user'] : '';
          			$my_post['post_category'] = !empty($_REQUEST['post_category']) ? $_REQUEST['post_category'] : '';
            		$my_post['tags_input'] = !empty($_REQUEST['post_tags']) ? $_REQUEST['post_tags'] : '';
           			$my_post['post_type'] = !empty($post_type) ? $post_type : 'post';
					
					
                    //print_r($my_post);
                    // Insert the post into the database


                    if ($post_id > 0) {
                        $post_id = wp_update_post($my_post);
                        update_post_meta($post_id, 'event_id', $event_id);
                        update_post_meta($post_id, 'event_identifier', $event_identifier);
                        update_post_meta($post_id, 'event_start_date', $start_date);
                        update_post_meta($post_id, 'event_end_date', $end_date);
                        update_post_meta($post_id, 'event_location', $event_location);
						update_post_meta($post_id, 'event_thumbnail_url', $event_meta['event_thumbnail_url']);
                        update_post_meta($post_id, 'virtual_url', $virtual_url);
                        update_post_meta($post_id, 'virtual_phone', $virtual_phone);
                        //
                        update_post_meta($post_id, 'event_address', $address);
                        update_post_meta($post_id, 'event_address2', $address2);
                        update_post_meta($post_id, 'event_city', $city);
                        update_post_meta($post_id, 'event_state', $state);
                        update_post_meta($post_id, 'event_country', $country);
                        update_post_meta($post_id, 'event_phone', $phone);
                        update_post_meta($post_id, 'venue_title', $venue_title);
                        update_post_meta($post_id, 'venue_url', $venue_url);
                        update_post_meta($post_id, 'venue_phone', $venue_phone);
                        update_post_meta($post_id, 'venue_image', $venue_image);
                        update_post_meta($post_id, 'event_externalURL', $externalURL);
                        update_post_meta($post_id, 'event_reg_limit', $reg_limit);
                        update_post_meta($post_id, 'event_start_time', time_to_24hr($start_time));
                        update_post_meta($post_id, 'event_end_time', time_to_24hr($end_time));
                        update_post_meta($post_id, 'event_registration_start', $registration_start);
                        update_post_meta($post_id, 'event_registration_end', $registration_end);
                        update_post_meta($post_id, 'event_registration_startT', $registration_startT);
                        update_post_meta($post_id, 'event_registration_endT', $registration_endT);
                        //update_post_meta( $post_id, 'timezone_string', $timezone_string );
                    } else {
                        $post_id = wp_insert_post($my_post);
                        add_post_meta($post_id, 'event_id', $event_id);
                        add_post_meta($post_id, 'event_identifier', $event_identifier);
                        add_post_meta($post_id, 'event_start_date', $start_date);
                        add_post_meta($post_id, 'event_end_date', $end_date);
                        add_post_meta($post_id, 'event_location', $event_location);
						add_post_meta($post_id, 'event_thumbnail_url', $event_meta['event_thumbnail_url']);
                        add_post_meta($post_id, 'virtual_url', $virtual_url);
                        add_post_meta($post_id, 'virtual_phone', $virtual_phone);
                        //
                        add_post_meta($post_id, 'event_address', $address);
                        add_post_meta($post_id, 'event_address2', $address2);
                        add_post_meta($post_id, 'event_city', $city);
                        add_post_meta($post_id, 'event_state', $state);
                        add_post_meta($post_id, 'event_country', $country);
                        add_post_meta($post_id, 'event_phone', $phone);
                        add_post_meta($post_id, 'venue_title', $venue_title);
                        add_post_meta($post_id, 'venue_url', $venue_url);
                        add_post_meta($post_id, 'venue_phone', $venue_phone);
                        add_post_meta($post_id, 'venue_image', $venue_image);
                        add_post_meta($post_id, 'event_externalURL', $externalURL);
                        add_post_meta($post_id, 'event_reg_limit', $reg_limit);
                        add_post_meta($post_id, 'event_start_time', time_to_24hr($start_time));
                        add_post_meta($post_id, 'event_end_time', time_to_24hr($end_time));
                        add_post_meta($post_id, 'event_registration_start', $registration_start);
                        add_post_meta($post_id, 'event_registration_end', $registration_end);
                        add_post_meta($post_id, 'event_registration_startT', $registration_startT);
                        add_post_meta($post_id, 'event_registration_endT', $registration_endT);
                        //add_post_meta( $post_id, 'timezone_string', $timezone_string );
                    }

                    // Store the POST ID so it can be displayed on the edit page
                    $sql = array('post_id' => $post_id, 'post_type' => $post_type);
                    $sql_data = array('%d', '%s');
                    $update_id = array('id' => $event_id);
                    $wpdb->prepare($wpdb->update(EVENTS_DETAIL_TABLE, $sql, $update_id, $sql_data, array('%d')));

                    break;
            }
        }
        ?>
        <div id="message" class="updated fade"><p><strong><?php _e('Event details updated for', 'event_espresso'); ?> <a href="<?php echo espresso_reg_url($event_id); ?>" target="_blank"><?php echo stripslashes_deep($_REQUEST['event']) ?> for <?php echo date("m/d/Y", strtotime($start_date)); ?></a>.</strong></p></div>
        
        <?php
			/*
			 * Added for seating chart addon
			 */
			if ( isset($seating_chart_result) && $seating_chart_result === false ){
				echo '<p>Failed to associate new seating chart with this event. (Seats from current seating chart might have been used by some attendees)</p>';
			}
    }

    /*
     * With the recursion of this function, additional recurring events will be updated
     */
    if (isset($recurrence_dates) && count($recurrence_dates) > 0 && $_POST['recurrence_apply_changes_to'] > 1) {
        //$recurrence_dates = array_shift($recurrence_dates); //Remove the first item from the array since it will be added after this recursion
        foreach ($recurrence_dates as $r_d) {

            if ($r_d['event_id'] != '' && count($r_d) > 2) {
                update_event(
                        array(
                            'event_id' => $r_d['event_id'],
                            'event_identifier' => $r_d['event_identifier'],
                            'recurrence_id' => $r_d['recurrence_id'],
                            'recurrence_start_date' => $r_d['start_date'],
                            'recurrence_event_end_date' => $r_d['event_end_date'],
                            'registration_start' => $r_d['registration_start'],
                            'registration_end' => $r_d['registration_end'],
                            'visible_on' => $r_d['visible_on']
                ));
            }
        }
    }
    /*
     * End recursion, as part of recurring events.
     */
}
