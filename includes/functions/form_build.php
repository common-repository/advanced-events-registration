<?php

if (!function_exists('event_form_build')) {

	function event_form_build($question, $answer = "", $event_id = null, $multi_reg = 0, $extra = array(), $class = 'my_class', $disabled = '') {
		if ($question->admin_only == 'Y' && empty($extra['admin_only'])) {
			return;
		}
		$required = '';
		$attendee_number = isset($extra['attendee_number']) ? $extra['attendee_number'] : '\' + (attendee_num+2) + \'';
		$price_id = isset($extra['price_id']) ? $extra['price_id'] : 0;
		$multi_name_adjust = $multi_reg == 1 ? "[$event_id][$price_id][$attendee_number]" : '';

		if (!empty($extra["x_attendee"])) {
			$field_name = ($question->system_name != '') ? "x_attendee_" . $question->system_name . "[\' + attendee_num + \']" : "x_attendee_" . $question->question_type . '_' . $question->id . '[\' + attendee_num + \']';
			$email_validate = $question->system_name == 'email' ? 'email' : '';
			$question->system_name = "x_attendee_" . $question->system_name . "[\' + attendee_num + \']";
			//$question->required = 'N';
		} else {
			$field_name = ($question->system_name != '') ? $question->system_name : $question->question_type . '_' . $question->id;
			$email_validate = $question->system_name == 'email' ? 'email' : '';
		}

		/**
		 * Temporary client side email validation solution by Abel, will be replaced in the next version with a full validation suite.
		 */


		if ($question->required == "Y") {
			$required = ' title="' . $question->required_text . '" class="required ' . $email_validate . ' ' . $class . '"';
			$required_label = "<em>*</em>";
			$legend = '<legend class="event_form_field required">' . $question->question . '<em>*</em></legend>';
		} else {
			$required = 'class="' . $class . '"';
			$legend = '<legend class="event_form_field">' . $question->question . '</legend>';
		}
		if (is_array($answer) && array_key_exists($event_id, $answer) && $attendee_number === 1) {
			$answer = empty($answer[$event_id]['event_attendees'][$price_id][$attendee_number][$field_name]) ? '' : $answer[$event_id]['event_attendees'][$price_id][$attendee_number][$field_name];
		}

		$required_label = isset($required_label) ? $required_label : '';

		$label = '<label for="' . $field_name . '">' . $question->question . $required_label . '</label> ';
		//If the members addon is installed, get the users information if available
		if ( function_exists('espresso_members_installed') && espresso_members_installed() == true ) {
			global $current_user;
			global $user_email;
			require_once(EVENT_ESPRESSO_MEMBERS_DIR . "user_vars.php"); //Load Members functions
			$userid = $current_user->ID;
		}

		$html = '';
		switch ($question->question_type) {
			case "TEXT" :
				if (defined('EVENT_ESPRESSO_MEMBERS_DIR') && (empty($_REQUEST['event_admin_reports']) || $_REQUEST['event_admin_reports'] != 'add_new_attendee')) {
					if (!empty($question->system_name)) {
						switch ($question->system_name) {
							case $question->system_name == 'fname':
								if ($attendee_number === 1)
									$answer = $current_user->first_name;
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'lname':
								if ($attendee_number === 1)
									$answer = $current_user->last_name;
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'email':
								if ($attendee_number === 1)
									$answer = $user_email;
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'address':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_address', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'city':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_city', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'state':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_state', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'zip':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_zip', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'phone':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_phone', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
							case $question->system_name == 'country':
								if ($attendee_number === 1)
									$answer = esc_attr(get_user_meta($userid, 'event_espresso_country', true));
								
								$html .= $answer == '' ? '' : '<input name="' . $question->system_name . $multi_name_adjust . '" type="hidden" value="' . $answer . '" class="' . $class . '" />';
								break;
						}
					}
				}

				if (is_array($answer)) $answer = '';
				if ($answer == '') $disabled = '';
				$html .= '<p class="event_form_field">' . $label;
				
				$html .= '<input type="text" ' . $required . ' id="' . $field_name . '-' . $event_id . '-' . $price_id . '-' . $attendee_number . '"  name="' . $field_name . $multi_name_adjust . '" size="40" value="' . $answer . '" ' . $disabled . ' /></p>';
				break;
			case "TEXTAREA" :
				if (is_array($answer)) $answer = '';
				$html .= '<p class="event_form_field event-quest-group-textarea">' . $label;
				$html .= '<textarea id=""' . $required . ' name="' . $field_name . $multi_name_adjust . '"  cols="30" rows="5">' . $answer . '</textarea></p>';
				break;
			case "SINGLE" :
				$values = explode(",", $question->response);
				$answers = explode(",", $answer);
				$html .= '<fieldset class="single-radio">';
				$html .= $legend;

				$html .= '<ul class="event_form_field">';
				foreach ($values as $key => $value) {
					//old way $checked = in_array ( $value, $answers ) ? ' checked="checked"' : "";
					$value = trim($value);
					$checked = ( $value == $answer ) ? ' checked="checked"' : "";
					$html .= '<li><label for="SINGLE_' . $question->id . '_' . $key . '_' . $attendee_number . '" class="' . $class . '"><input id="SINGLE_' . $question->id . '_' . $key . '_' . $attendee_number . '" ' . $required . ' name="' . $field_name . $multi_name_adjust . '"  type="radio" value="' . $value . '" ' . $checked . ' /> ' . $value . '</label></li>';
					//echo $label;
				}
				$html .= '</ul>';
				$html .= '</fieldset>';
				break;
			case "MULTIPLE" :
				$values = explode(",", $question->response);
				$answers = explode(",", $answer);
				$html .= '<fieldset class="multi-checkbox">';
				$html .= $legend;
				//$html .= '</p>';
				$html .= '<ul class="event_form_field">';
				foreach ($values as $key => $value) {
					$value = trim($value);
					$checked = (is_array($answer) && in_array($value, $answer)) ? ' checked="checked"' : "";
					//$checked = ( $value == $answer ) ? ' checked="checked"' : "";
					/* 	$html .= "<label><input type=\"checkbox\"$required id=\"MULTIPLE_$question->id_$key\" name=\"MULTIPLE_$question->id_$key\"  value=\"$value\"$checked /> $value</label><br/>\n"; */
					$html .= '<li><label for="' . str_replace(' ', '', $value) . '-' . $event_id . '_' . $attendee_number . '" class="' . $class . '"><input id="' . str_replace(' ', '', $value) . '-' . $event_id . '_' . $attendee_number . '" ' . $required . 'name="' . $field_name . $multi_name_adjust . '[]"  type="checkbox" value="' . $value . '" ' . $checked . '/> ' . $value . '</label></li>';
				}
				$html .= '</ul>';
				$html .= '</fieldset>';
				break;
			case "DROPDOWN" :
				$dd_type = $question->system_name == 'state' ? 'name="state"' : 'name="' . $field_name . $multi_name_adjust . '"';
				$values = explode(",", $question->response);
				$answers = explode(",", $answer);
				$html .= '<p class="event_form_field" class="' . $class . '">' . $label;
				$html .= '<select ' . $dd_type . ' ' . $required . ' id="DROPDOWN_' . $question->id . '-' . $event_id . '-' . $price_id . '-' . $attendee_number . '">';
				$html .= '<option value="">' . __('Select One', 'event_espresso') . "</option>";
				foreach ($values as $key => $value) {
					$value = trim($value);
					//$checked = in_array ( $value, $answers ) ? ' selected=" selected"' : "";
					$selected = ( $value == $answer ) ? ' selected="selected"' : "";
					$html .= '<option value="' . $value . '" ' . $selected . '> ' . $value . '</option>';
				}
				$html .= "</select>";
				$html .= '</p>';
				break;
			default :
				break;
		}
		if (is_numeric($attendee_number)) $attendee_number++;
		return $html;
	}

}

function event_form_build_edit($question, $edits, $show_admin_only = false, $class = 'my_class') {
	$required = '';
	/* if ($question->required == "Y") {
	  $required = ' class="required"';
	  } */

	/**
	 * Temporary client side email validation solution by Abel, will be replaced
	 * in the next version with a full validation suite.
	 */
	$email_validate = $question->system_name == 'email' ? 'email' : '';

	if ($question->required == "Y") {
		$required = ' title="' . $question->required_text . '" class="required ' . $email_validate . ' ' . $class . '"';
		$required_label = "<em>*</em>";
	} else {
		$required = 'class="' . $class . '"';
	}
	$required_label = isset($required_label) ? $required_label : '';

	//echo '<p>$required = '.$required.'</p>';
	//echo "<pre>".print_r($question,true)."</pre>";
	//echo '<p>id = '.$question->id.'</p>';
	//echo '<p>q_id = '.$question->q_id.'</p>';
	//Sometimes the answer id is passed as the question id, so we need to make sure that we get the right question id.

	$answer_id = $question->id;
	//echo $answer_id;

	if (isset($question->q_id))
		$question->id = $question->q_id;
	if ($question->admin_only == 'Y' && $show_admin_only == false) {
		return;
	}
	$field_name = ($question->system_name != '') ? $question->system_name : 'TEXT_' . $question->id;
	echo '<label for="' . $field_name . '">' . $question->question . $required_label . '</label><br>';
	switch ($question->question_type) {
		case "TEXT" :
			echo '<input type="text" ' . $required . ' id="' . $field_name . '"  name="' . $field_name . '" size="40"  value="' . $edits . '" />';
			break;
		case "TEXTAREA" :
			echo '<textarea id="TEXTAREA_' . $question->id . '" ' . $required . ' name="TEXTAREA_' . $question->id . '"  cols="30" rows="5">' . $edits . '</textarea>';
			break;
		case "SINGLE" :
			$values = explode(",", $question->response);
			$answers = explode(",", $edits);
			echo '<ul>';
			foreach ($values as $key => $value) {
				$checked = in_array(trim($value), $answers) ? ' checked="checked"' : "";
				echo '<li><input id="SINGLE_' . $question->id . '_' . $key . '" ' . $required . ' name="SINGLE_' . $question->id . '"  type="radio" value="' . trim($value) . '" ' . $checked . '/> ' . trim($value) . '</li>';
			}
			echo "</ul>";
			break;
		case "MULTIPLE" :
			$values = explode(",", $question->response);
			$answers = explode(",", $edits);
			//echo '<p>New True ID= '.$question->id.'</p>';
			echo '<ul>';
			foreach ($values as $key => $value) {
				$checked = in_array(trim($value), $answers) ? " checked=\"checked\"" : "";
				/* 	echo "<label><input type=\"checkbox\"$required id=\"MULTIPLE_$question->id_$key\" name=\"MULTIPLE_$question->id_$key\"  value=\"$value\"$checked /> $value</label><br/>\n"; */
				echo '<li><input id="' . trim($value) . '" ' . $required . ' name="MULTIPLE_' . $question->id . '[]"  type="checkbox" value="' . trim($value) . '" ' . $checked . '/> ' . trim($value) . '</li>';
			}
			echo "</ul>";
			//echo '<input name="'.$answer_id.'" type="hidden" value="'.$answer_id.'" />';
			break;
		case "DROPDOWN" :
			$dd_type = $question->system_name == 'state' ? 'name="state"' : 'name="DROPDOWN_' . $question->id . '"';
			$values = explode(",", $question->response);
			//$answers = explode ( ",", $edits );
			echo '<select ' . $dd_type . ' ' . $required . ' ' . $required . ' id="DROPDOWN_' . $question->id . '"  />';
			echo '<option value="' . $edits . '">' . $edits . '</option>';
			foreach ($values as $key => $value) {
				//$checked = in_array ( $value, $answers ) ? " selected =\" selected\"" : "";
				echo '<option value="' . trim($value) . '" /> ' . trim($value) . '</option>';
			}
			echo "</select>";
			break;
		default :
			break;
	}
}
