<?php

function event_espresso_timereg_editor($event_id = 0) {
    global $wpdb;
    $time_counter = 1;
    ?>

    <ul id="staticTimeInput">

        <?php
        if ($event_id > 0) {
            $timesx = $wpdb->get_results("SELECT * FROM " . EVENTS_DETAIL_TABLE . " WHERE id = '" . $event_id . "'");
            foreach ($timesx as $timex) {
                echo '<li><p><label for="add-reg-start">' . __('Reg Start Time', 'event_espresso') . '</label> <input size="10"  type="text" id="add-reg-start" name="registration_startT" value="' . event_date_display($timex->registration_startT, get_option('time_format')) . '" /></p><p> <label for="ad-reg-end"> ' . __('Reg End Time', 'event_espresso') . '</label> <input size="10"  type="text" name="registration_endT" value="' . event_date_display($timex->registration_endT, get_option('time_format')) . '"></p></li>';
            }
        } else {
            ?>
            <li>
                <p><label for="add-reg-start"><?php _e('Reg Start Time', 'event_espresso'); ?></label> <input size="10"  type="text" id="add-reg-start" name="registration_startT" /></p>
                <p><label for="registration_endT"> <?php _e('Reg End Time', 'event_espresso'); ?></label><input size="10"  type="text" id="registration_endT" name="registration_endT" /></p>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
}

function event_espresso_time_editor($event_id = 0) {
    global $wpdb, $org_options;
    //$org_options['time_reg_limit'] = 'Y';
    $time_counter = 1;
    //echo get_option('time_format');
    ?>

    <ul id="dynamicTimeInput">

        <?php
        $times = $wpdb->get_results("SELECT * FROM " . EVENTS_START_END_TABLE . " WHERE event_id = '" . $event_id . "' ORDER BY id");
        if ($wpdb->num_rows > 0) {
            foreach ($times as $time) {
                echo '<li><p><label for="add-start-time">' . __('Start', 'event_espresso') . ' ' . $time_counter++ . '</label><input size="10"  type="text" id="add-start-time" name="start_time[]" value="' . event_date_display($time->start_time, get_option('time_format')) . '" /></p><p><label for="add-end-time"> ' . __('End', 'event_espresso') . '</label> <input size="10"  type="text" id="add-end-time" name="end_time[]" value="' . event_date_display($time->end_time, get_option('time_format')) . '"></p>' . ($org_options['time_reg_limit'] == 'Y' ? '<p><label>'.__('Qty', 'event_espresso') . '</label> <input size="3"  type="text" name="time_qty[]" value="' . $time->reg_limit . '"></p>' : '') . '<p><input class="remove-item xtra-time" type="button" value="Remove" onclick="this.parentNode.parentNode.removeChild(this.parentNode);" /></p></li>';
            }
        } else {
            ?>
            <li>
                <p><label for="add-start-time"><?php _e('Start', 'event_espresso'); ?></label> <input size="10"  type="text" id="add-start-time" name="start_time[]" /></p>
                <p><label for="add-end-time"> <?php _e('End', 'event_espresso'); ?></label> <input size="10"  type="text" id="add-end-time" name="end_time[]" /></p> <?php echo (isset($org_options['time_reg_limit']) && $org_options['time_reg_limit'] == 'Y' ? '<p><label>'.__('Qty', 'event_espresso') . '</label> <input size="3"  type="text" name="time_qty[]" /></p>' : '') ?>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
    global $espresso_premium;
    if ($espresso_premium != true)
        return;
    ?>
    <input type="button" class="button" id="add-time" value="<?php _e('Add Additional Time', 'event_espresso'); ?>" onClick="addTimeInput('dynamicTimeInput');">
    <script type="text/javascript">
        //Dynamic form fields
        var counter = <?php echo $time_counter++ ?>;
        function addTimeInput(divName){
            var newdiv = document.createElement('li');
            newdiv.innerHTML = "<p><label for='add-start-time-"+ (counter) +"'><?php _e('Start', 'event_espresso'); ?> " + (counter) + "</label> <input type='text'id='add-start-time-"+ (counter) +"' size='10' name='start_time[]'></p><p><label for='add-end-time-"+ (counter) +"'> <?php _e('End', 'event_espresso'); ?>:</label> <input type='text' id='add-end-time-"+ (counter) +"' size='10' name='end_time[]'></p><?php echo $org_options['time_reg_limit'] == 'Y' ? '<p><label>'.__('Qty', 'event_espresso') . "</label> <input type='text'  size='3' name='time_qty[]'></p>" : ''; ?><p><input class='remove-this xtra-time' id='remove-added-time' type='button' value='Remove' onclick='this.parentNode.parentNode.removeChild(this.parentNode);'/></p>";
            document.getElementById(divName).appendChild(newdiv);
            counter++;
        }
    </script>
    <?php
}

function event_espresso_multi_price_update($event_id) {
    global $wpdb, $org_options;
    $price_counter = 1;
    ?>
    <fieldset>
        <legend><?php _e('Standard Pricing', 'event_espresso'); ?></legend>
        <ul id="dynamicPriceInput">
            <?php
            $prices = $wpdb->get_results("SELECT price_type, event_cost, surcharge, surcharge_type FROM " . EVENTS_PRICES_TABLE . " WHERE event_id = '" . $event_id . "' ORDER BY id");
            if ($wpdb->num_rows > 0) {
                foreach ($prices as $price) {
                    echo '<li><p>';
                    if (!isset($price->price_type))
                        $price->price_type = "General Admission";
                    if (!isset($price->event_cost))
                        $price->event_cost = "0.00";
                    echo '<label for="add-price-type-' . $price_counter++ . '">' . __('Name', 'event_espresso') . ' ' . $price_counter++ . '</label> <input size="10" id="add-price-type' . $price_counter++ . '" type="text" name="price_type[]" value="' . $price->price_type . '" /> ';
                    $org_options['currency_symbol'] = isset($org_options['currency_symbol']) ? $org_options['currency_symbol'] : '';
                    echo '<label for="add-price">' . __('Price', 'event_espresso') . ' ' . $org_options['currency_symbol'] . '</label><input size="5" id="add-price" type="text" name="event_cost[]" value="' . $price->event_cost . '" /></p> ';

                    echo '<p><label for="add-surcharge">' . __('Surcharge', 'event_espresso') . '</label> <input size="5" id="add-surcharge" type="text"  name="surcharge[]" value="' . $price->surcharge . '" /></p> ';
                    echo '<p><label for="surcharge-type">' . __('Surcharge Type', 'event_espresso') . '</label>';
                    ?>
                    <select id="surcharge-type" name="surcharge_type[]">
                        <option value = "flat_rate" <?php selected($price->surcharge_type, 'flat_rate') ?>><?php _e('Flat Rate', 'event_espresso'); ?></option>
                        <option value = "pct" <?php selected($price->surcharge_type, 'pct') ?>><?php _e('Percent', 'event_espresso'); ?></option>
                    </select>

                    <?php
                    echo '</p>';
                    echo '<img class="remove-item" title="' . __('Remove this Price', 'event_espresso') . '" onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Price', 'event_espresso') . '" />';
                    echo '</li>';
                }
            }else {
                ?>
                <li id="add-price-name">

                    <p>
                        <label for="add-price-type-<?php echo $price_counter ?>"><?php _e('Name', 'event_espresso'); ?><?php echo $price_counter ?>:</label>
                        <input size="10" id="add-price-type-<?php echo $price_counter ?>" type="text"  name="price_type[]" value="General Admission">

                        <label for="add-event-cost"><?php _e('Price', 'event_espresso'); ?></label>
                        <input size="5" id="add-event-cost" type="text"  name="event_cost[]" value="0.00">
                    </p>
                    <p>
                        <label for="add-surcharge"><?php _e('Surcharge', 'event_espresso'); ?></label>
                        <input size="5"  type="text"  id="add-surcharge" name="surcharge[]" value="<?php echo $org_options['surcharge'] ?>" >
                    </p>
                    <p>
                        <label for="add-surcharge-type"> <?php _e('Surcharge Type', 'event_espresso'); ?></label>
                        <select id="add-surcharge-type" name="surcharge_type[]">
                            <option value = "flat_rate" <?php selected($org_options['surcharge_type'], 'flat_rate') ?>><?php _e('Flat Rate', 'event_espresso'); ?></option>
                            <option value = "pct" <?php selected($org_options['surcharge_type'], 'pct') ?>><?php _e('Percent', 'event_espresso'); ?></option>
                        </select>
                    </p>
        <?php echo '<img class="remove-item" title="' . __('Remove this Price', 'event_espresso') . '" onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/remove.gif" alt="' . __('Remove Price', 'event_espresso') . '" />'; ?>


                </li>
                <?php
            }
            ?>
        </ul>
        <p>
            (<?php _e('enter 0.00 for free events, enter 2 place decimal i.e.', 'event_espresso'); ?> <?php echo isset($org_options['currency_symbol']) ? $org_options['currency_symbol'] : ''; ?> 7.00)
        </p>
        <?php
        global $espresso_premium;
        if ($espresso_premium != true)
            return;
        ?>
        <p><input class="button" type="button" value="<?php _e('Add A Price', 'event_espresso'); ?>" onClick="addPriceInput('dynamicPriceInput');"></p>
    </fieldset>
    <script type="text/javascript">
        //Dynamic form fields
        var price_counter = <?php echo $price_counter > 1 ? $price_counter - 1 : $price_counter++; ?>;
        function addPriceInput(divName){
            var next_counter = counter_static(price_counter);
            var newdiv =  document.createElement("li");
            newdiv.innerHTML = "<p><label for='add-price-type-" + (next_counter) + "'><?php _e('Name', 'event_espresso'); ?> " + (next_counter) + "</label> <input type='text' size='10' name='price_type[]' /> <label for='add-price" + (next_counter) + "'><?php _e('Price', 'event_espresso'); ?></label> <input id='add-price-" + (next_counter) + "' type='text' size='5' name='event_cost[]' /></p><p><label for='add-surcharge-" + (next_counter) + "' ><?php _e('Surcharge', 'event_espresso'); ?></label> <input size='5' id='add-surcharge-" + (next_counter) + "' type='text'  name='surcharge[]' value='<?php echo $org_options['surcharge'] ?>' /></p> <p><label for='add-surcharge-type-" + (next_counter) + "'><?php _e('Surcharge Type', 'event_espresso'); ?> <select id='add-surcharge-type-" + (next_counter) + "' name='surcharge_type[]'><option value = 'flat_rate' <?php selected($org_options['surcharge_type'], 'flat_rate') ?>><?php _e('Flat Rate', 'event_espresso'); ?></option><option value = 'pct' <?php selected($org_options['surcharge_type'], 'pct') ?>><?php _e('Percent', 'event_espresso'); ?></option></select></p> <?php echo "<img class='remove-item' title='" . __('Remove this Price', 'event_espresso') . "' onclick='this.parentNode.parentNode.removeChild(this.parentNode);' src='" . EVENT_ESPRESSO_PLUGINFULLURL . "images/icons/remove.gif' alt='" . __('Remove Price', 'event_espresso') . '\' />'; ?>";
            document.getElementById(divName).appendChild(newdiv);
            counter++;
        }

        function counter_static(price_counter) {
            if ( typeof counter_static.counter == 'undefined' ) {

                counter_static.counter = price_counter;
            }


            return ++counter_static.counter;
        }
    </script>
    <?php
}

//This function grabs the event categories and outputs checkboxes.
//@param optional $event_id = pass the event id to get the categories assigned to the event.
function event_espresso_get_categories($event_id = 0, $is_fes = false) {
    global $wpdb;
	
	//Don't show manage link if using front-end event submission
	if ( $is_fes == false )
		$manage = '<p><a href="admin.php?page=event_categories" target="_blank">' . __('Manage Categories', 'event_espresso') . '</a></p>';
   
    $sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
    if (function_exists('espresso_member_data')) {
        global $espresso_manager;
        if (isset($espresso_manager['event_manager_share_cats']) && $espresso_manager['event_manager_share_cats'] == 'N') {
            $results = $wpdb->get_results("SELECT wp_user FROM " . EVENTS_DETAIL_TABLE . " WHERE id = '" . $event_id . "'");
            
            $wp_user = ( $results && $wpdb->last_result[0]->wp_user != '' ) ? $wpdb->last_result[0]->wp_user : espresso_member_data('id');
            $sql .= " WHERE ";
            if ($wp_user == 0 || $wp_user == 1) {
                $sql .= " (wp_user = '0' OR wp_user = '1') ";
            } else {
                $sql .= " wp_user = '" . $wp_user . "' ";
            }
        }
    }
	$sql .= " ORDER BY category_name ";
    $event_categories = $wpdb->get_results($sql);
    $num_rows = $wpdb->num_rows;
    if ($num_rows > 0) {
        $html = '';
        foreach ($event_categories as $category) {
            $category_id = $category->id;
            $category_name = $category->category_name;

            $in_event_categories = $wpdb->get_results("SELECT * FROM " . EVENTS_CATEGORY_REL_TABLE . " WHERE event_id='" . $event_id . "' AND cat_id='" . $category_id . "'");
            foreach ($in_event_categories as $in_category) {
                $in_event_category = $in_category->cat_id;
            }
            if(empty($in_event_category)) $in_event_category = '';
            $html .= '<p id="event-category-' . $category_id . '"><label for="in-event-category-' . $category_id . '" class="selectit"><input value="' . $category_id . '" type="checkbox" name="event_category[]" id="in-event-category-' . $category_id . '"' . ($in_event_category == $category_id ? ' checked="checked"' : "" ) . '/> ' . $category_name . "</label></p>";
        }
        $top_div = '';
        $bottom_div = '';
        if ($num_rows > 10) {
            $top_div = '<div style="height:250px;overflow:auto;">';
            $bottom_div = '</div>';
        }

        $html = $top_div . $html . $bottom_div . $manage;
        return $html;
    } else {
        _e('No Categories', 'event_espresso');
        echo $manage;
    }
}

//This function grabs the event categories and outputs a dropdown.
//@param optional $event_id = pass the event id to get the categories assigned to the event.
//@param optional $is_fes = Used for the front-end event submission tool. It hides the "Manage Categories" if true.
function event_espresso_categories_dd($event_id = 0, $is_fes = false) {
    global $wpdb;
	
	//Don't show manage link if using front-end event submission
	if ( $is_fes == false )
		$manage = '<p><a href="admin.php?page=event_categories" target="_blank">' . __('Manage Categories', 'event_espresso') . '</a></p>';
   
    $sql = "SELECT * FROM " . EVENTS_CATEGORY_TABLE;
    if (function_exists('espresso_member_data')) {
        global $espresso_manager;
        if (isset($espresso_manager['event_manager_share_cats']) && $espresso_manager['event_manager_share_cats'] == 'N') {
            $results = $wpdb->get_results("SELECT wp_user FROM " . EVENTS_DETAIL_TABLE . " WHERE id = '" . $event_id . "'");
            
            $wp_user = ( $results && $wpdb->last_result[0]->wp_user != '' ) ? $wpdb->last_result[0]->wp_user : espresso_member_data('id');
            $sql .= " WHERE ";
            if ($wp_user == 0 || $wp_user == 1) {
                $sql .= " (wp_user = '0' OR wp_user = '1') ";
            } else {
                $sql .= " wp_user = '" . $wp_user . "' ";
            }
        }
    }
	$sql .= " ORDER BY category_name ";
    $event_categories = $wpdb->get_results($sql);
    $num_rows = $wpdb->num_rows;
    if ($num_rows > 0) {
        $html = '';
		$html .= '<select name="event_category[]">';
        foreach ($event_categories as $category) {
            $category_id = $category->id;
            $category_name = $category->category_name;

            $in_event_categories = $wpdb->get_results("SELECT * FROM " . EVENTS_CATEGORY_REL_TABLE . " WHERE event_id='" . $event_id . "' AND cat_id='" . $category_id . "'");
            foreach ($in_event_categories as $in_category) {
                $in_event_category = $in_category->cat_id;
            }
            if(empty($in_event_category)) $in_event_category = '';
           // $html .= '<p id="event-category-' . $category_id . '"><label for="in-event-category-' . $category_id . '" class="selectit"><input value="' . $category_id . '" type="checkbox" name="event_category[]" id="in-event-category-' . $category_id . '"' . ($in_event_category == $category_id ? ' checked="checked"' : "" ) . '/> ' . $category_name . "</label></p>";
			
			$html .= '<option value="' . $category_id . '" ' . ($in_event_category == $category_id ? ' selected' : "" ) . '>' . $category_name .'</option>';
			
        }
		$html .= '</select>';
        
        $html = $html . $manage;
        return $html;
    } else {
        _e('No Categories', 'event_espresso');
        echo $manage;
    }
}


function espresso_event_question_groups($question_groups=array(), $add_attendee_question_groups=array(), $event_id=0) {
    global $wpdb, $org_options, $espresso_premium;
    ?>
    <div id="event-questions" class="postbox event-questions-lists">
        <div class="handlediv" title="Click to toggle"><br />
        </div>
        <h3 class="hndle"><span>
                    <?php echo sprintf(__('Event Questions for Primary Attendee', 'event_espresso'), ''); ?>
            </span></h3>
        <div class="inside">
            <p><strong>
                    <?php _e('Question Groups', 'event_espresso'); ?>
                </strong><br />
                <?php _e('Add a pre-populated', 'event_espresso'); ?>
                <a href="admin.php?page=form_groups" target="_blank">
                    <?php _e('group', 'event_espresso'); ?>
                </a>
                <?php _e('of', 'event_espresso'); ?>
                <a href="admin.php?page=form_builder" target="_blank">
            <?php _e('questions', 'event_espresso'); ?>
                </a>
            <?php _e('to your event. The personal information group is required for all events.', 'event_espresso'); ?>
            </p>
            <?php
            $g_limit = $espresso_premium != true ? 'LIMIT 0,2' : '';
            
            $rs_question_groups = array();
            // Get all system question groups regardless of the user id 
			
			$rs_sql ="SELECT qg.* FROM " . EVENTS_QST_GROUP_TABLE . " qg WHERE qg.system_group = 1 ";
			
			// If permission addon is active
            if ( function_exists( 'espresso_member_data' ) ) {
				$rs_sql .= " AND qg.wp_user = '" . espresso_member_data('id') . "' ";
            }else{
				$rs_sql .= " AND (wp_user = '0' OR wp_user = '1') ";
			}
			
			$rs_sql .= " ORDER BY qg.group_order ";
			
            $rs = $wpdb->get_results( $rs_sql );
            if ( count( $rs ) > 0 ) {
                foreach( $rs as $row ) {
                    $rs_question_groups[] = $row;
                }
            }
            
            // If previously question groups were assigned; it is required for event edit form
            if ( count( $question_groups ) > 0 ) {
                $sql  = " SELECT qg.* FROM " . EVENTS_QST_GROUP_TABLE . " qg WHERE qg.system_group <> 1 " ;
                $sql .= " AND qg.id IN ( " . implode( ',', $question_groups ) . " ) ORDER BY qg.group_order ";
                $rs = $wpdb->get_results( $sql );
                if ( count ( $rs ) > 0 ) {
                    foreach( $rs as $row ) {
                        $rs_question_groups[] = $row;
                    }
                }
            }
            
            // Get non system question groups
            $sql  = " SELECT qg.* FROM " . EVENTS_QST_GROUP_TABLE . "  qg WHERE qg.system_group <> 1 ";
            
            // Excluded already existing question groups
            if ( count( $question_groups ) > 0 ) {
                $sql .= " AND qg.id NOT IN ( " . implode( ',', $question_groups ) . " ) ";
            }
            
            
            // If permission addon is active
            if ( function_exists( 'espresso_member_data' ) ) {
               // if (function_exists( 'espresso_is_admin' ) ) {  
                    // If the user doesn't have admin access get only user's own question groups 
                   // if ( espresso_is_admin() !== true ) { 
                        $sql .= " AND wp_user = '" . espresso_member_data('id') . "' ";
                   // }
               // } 
            }else{
				$sql .= " AND (wp_user = '0' OR wp_user = '1') ";
			}
            $sql .= " ORDER BY qg.group_order ";
            $rs = $wpdb->get_results( $sql );
            if ( count( $rs ) > 0 ) {
                foreach( $rs as $row ) {
                    $rs_question_groups[] = $row;
                }
            }
            
            
            /*
            $sql = "SELECT qg.* FROM " . EVENTS_QST_GROUP_TABLE . " qg JOIN " . EVENTS_QST_GROUP_REL_TABLE . " qgr ON qg.id = qgr.group_id ";
            if (function_exists('espresso_member_data')) { 
                $results = $wpdb->get_results("SELECT wp_user FROM " . EVENTS_DETAIL_TABLE . " WHERE id = '" . $event_id . "'");
                $wp_user = ( $results && $wpdb->last_result[0]->wp_user != '' ) ? $wpdb->last_result[0]->wp_user : espresso_member_data('id');
                $sql .= " WHERE ";
                if ($wp_user == 0 || $wp_user == 1) {
                    $sql .= " (wp_user = '0' OR wp_user = '1') ";
                } else {
                    //$sql .= " wp_user = '" . $wp_user . "' ";
					$sql .= " (wp_user = '" . $wp_user . "' OR wp_user = '0' OR wp_user = '1')";
                } 
            }else{
                $sql .= " WHERE wp_user = '0' OR wp_user = '1' ";
			}
            $sql .= " GROUP BY qg.id ORDER BY qg.group_order $g_limit ";
            $q_groups = $wpdb->get_results($sql);
             * 
             */
            
            // If not premium limit to only 2 question groups.
            if ( !$espresso_premium ) 
                $rs_question_groups = array_slice ( $rs_question_groups, 0, 2 );
            
            //$num_rows = $wpdb->num_rows;
            $num_rows = count( $rs_question_groups );
            $html = '';
            if ($num_rows > 0) {
                // foreach ($q_groups as $question_group) {
                foreach ($rs_question_groups as $question_group) {        
                    $question_group_id = $question_group->id;
                    $question_group_description = $question_group->group_description;
                    $group_name = $question_group->group_name;
                    //$checked = $question_group->system_group == 1 ? ' checked="checked" ' : '';
                    $checked = (is_array($question_groups) && array_key_exists($question_group_id, $question_groups)) || ($question_group->system_group == 1) ? ' checked="checked" ' : '';
                    $visibility = $question_group->system_group == 1 ? 'style="visibility:hidden"' : '';
                    $group_id = isset($group_id) ? $group_id : '';
                    $html .= '<p id="event-question-group-' . $question_group_id . '"><input value="' . $question_group_id . '" type="checkbox" ' . $checked . $visibility . ' name="question_groups[' . $question_group_id . ']" ' . $checked . ' /> <a href="admin.php?page=form_groups&amp;action=edit_group&amp;group_id=' . $question_group_id . '" title="edit" target="_blank">' . $group_name . '</a></p>';
                }
                if ($num_rows > 10) {
                    $top_div = '<div style="height:250px;overflow:auto;">';
                    $bottom_div = '</div>';
                } else {
                    $top_div = '';
                    $bottom_div = '';
                }
                $html = $top_div . $html . $bottom_div;
                echo $html;
            } else {
                echo __('There seems to be a problem with your questions. Please contact support@eventespresso.com', 'event_espresso');
            }
            if ($espresso_premium != true)
                echo __('Need more questions?', 'event_espresso') . ' <a href="http://eventespresso.com/download/" target="_blank">' . __('Upgrade Now!', 'event_espresso') . '</a>';
            ?>
        </div>
    </div>
                <?php if ($espresso_premium == true) { ?>
        <div id="event-questions-additional" class="postbox event-questions-lists">
            <div class="handlediv" title="Click to toggle"><br>
            </div>
            <h3 class="hndle"><span>
                        <?php _e('Event Questions for Additional Attendees', 'event_espresso'); ?>
                </span></h3>
            <div class="inside">
                <p><strong>
                        <?php _e('Question Groups', 'event_espresso'); ?>
                    </strong><br />
                    <?php _e('Add a pre-populated', 'event_espresso'); ?>
                    <a href="admin.php?page=form_groups" target="_blank">
                        <?php _e('group', 'event_espresso'); ?>
                    </a>
                    <?php _e('of', 'event_espresso'); ?>
                    <a href="admin.php?page=form_builder" target="_blank">
                <?php _e('questions', 'event_espresso'); ?>
                    </a>
                <?php _e('to your event. The personal information group is required for all events.', 'event_espresso'); ?>
                </p>
                <?php
                // $add_attendee_question_groups = isset($add_attendee_question_groups) ? $add_attendee_question_groups : '';

                reset($rs_question_groups);
                $html = '';
                if ($num_rows > 0) {
                    foreach ($rs_question_groups as $question_group) {
                        $question_group_id = $question_group->id;
                        $question_group_description = $question_group->group_description;
                        $group_name = $question_group->group_name;
                        $checked = (is_array($add_attendee_question_groups) && array_key_exists($question_group_id, $add_attendee_question_groups)) || ($question_group->system_group == 1) ? ' checked="checked" ' : '';

                        $visibility = $question_group->system_group == 1 ? 'style="visibility:hidden"' : '';

                        $html .= '<p id="event-question-group-' . $question_group_id . '"><input value="' . $question_group_id . '" type="checkbox" ' . $visibility . ' name="add_attendee_question_groups[' . $question_group_id . ']" ' . $checked . ' /> <a href="admin.php?page=form_groups&amp;action=edit_group&amp;group_id=' . $question_group_id . '" title="edit" target="_blank">' . $group_name . "</a></p>";
                    }
                    if ($num_rows > 10) {
                        $top_div = '<div style="height:250px;overflow:auto;">';
                        $bottom_div = '</div>';
                    }
                    $html = $top_div . $html . $bottom_div;
                    echo $html;
                } else {
                    echo __('There seems to be a problem with your questions. Please contact support@eventespresso.com', 'event_espresso');
                }

                if ($espresso_premium != true)
                    echo __('Need more questions?', 'event_espresso') . ' <a href="http://eventespresso.com/download/" target="_blank">' . __('Upgrade Now!', 'event_espresso') . '</a>';
                ?>
            </div>
        </div>
        <?php
    }
}
