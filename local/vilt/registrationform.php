<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   local_vilt
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$meetingid = required_param('meetingid', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot . '/local/vilt/registrationform.php', ['meetingid' => $meetingid]);
$PAGE->set_title('Registration Form');
$meetingrecord = $DB->get_record_sql("SELECT w.* FROM {webexactivity} w WHERE w.id = $meetingid");
$PAGE->set_heading($meetingrecord->name . ' Registration Form');
$PAGE->set_pagelayout('admin');
$PAGE->requires->css(new moodle_url('/local/vilt/css/userregister.css'));
$PAGE->requires->js(new moodle_url('/local/vilt/amd/src/jquery.js'));
$PAGE->requires->js(new moodle_url('/local/vilt/amd/src/create_meeting.js'));
$admins = get_admin();

if (isset($_POST['submit'])) {
	$user = (object)$_POST;

	$returnurl = new moodle_url('/local/vilt/registrationform.php', array('meetingid' => $user->meetingid));
	// Check password toward the password policy.
	if (!check_password_policy($user->password, $errmsg, $user)) {
		// $DB->delete_records('timer', ['email' => $user->email]);
		$DB->execute("DELETE FROM {timer} WHERE email = '$user->email'");
		redirect($returnurl, 'Invalid Password', null, \core\output\notification::NOTIFY_ERROR);
	}
	//   $examfields = exam_fields($user->meetingid);
	//   $fields = explode(',',$examfields->examsetupfields);
	if ($chekdupemail = $DB->record_exists('user', ['auth' => 'email', 'email' => $user->email])) {
		// $DB->delete_records('timer', ['email' => $user->email]);
		$DB->execute("DELETE FROM {timer} WHERE email = '$user->email'");
		redirect($returnurl, 'User is not created due to email already exists!', null, \core\output\notification::NOTIFY_ERROR);
	}
	if ($otpvalidation = $DB->get_record_sql("SELECT * FROM {timer} where meetingid = ? and email = ?", array($user->meetingid, $user->email))) {
		$time         = time();
		$otpvalue = $otpvalidation->otp;
		if ($otpvalue != $user->otp) {
			// $DB->delete_records('timer', ['email' => $user->email]);
			$DB->execute("DELETE FROM {timer} WHERE email = '$user->email'");
			redirect($returnurl, 'User is not created due to Invalid OTP!', null, \core\output\notification::NOTIFY_ERROR);
		}
	} else {
		// $DB->delete_records('timer', ['email' => $user->email]);
		$DB->execute("DELETE FROM {timer} WHERE email = '$user->email'");
		redirect($returnurl, 'User is not created due to Invalid OTP!', null, \core\output\notification::NOTIFY_ERROR);
	}
	$viltrecord = $DB->get_record('viltrecord', ['webexid' => $user->meetingid]);
	$user->password = hash_internal_user_password($user->password);

	$user->confirmed   = 0;
    $user->lang        = current_language();
    $user->firstaccess = 0;
    $user->timecreated = time();
    $user->mnethostid  = $CFG->mnet_localhost_id;
    $user->secret      = random_string(15);
    $user->auth        = 'email';
    $user->username     = $user->username;
 
	$user->id = user_create_user($user, false, false);

	$request = new stdClass();
	$request->userid = $user->id;
	$request->companyid = $viltrecord->companyid;
	$request->courseid = $viltrecord->courseid;
	$request->status = 'pending';
	$request->meetingid = $user->meetingid;
	$request->timecreated = time();
	$request->timemodified = time();
	$insert = $DB->insert_record('meeting_requests', $request);
	if ($insert) {
		$admins = get_admin();
		// var_dump($admins);
		$fromuser = core_user::get_noreply_user();

		email_to_user($admins, $fromuser, 'Meeting Request', 'Meeting Request is come. Please check in list.');
		// die;
		// foreach($admins as $admin) {
		// 	$touser = $DB->get_record('user', ['id' => $admin]);
		
		// }
		// $DB->execute('timer', ['email' => $user->email]);
		$DB->execute("DELETE FROM {timer} WHERE email = '$user->email'");
		redirect($returnurl, 'Request Send Successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
	}

}

echo $OUTPUT->header();

$cancelurl = new moodle_url('/local/vilt/meetingoverview.php', ['id' => $meetingrecord->course]);
$fields = get_required_fields();
$customfieldids = $DB->get_field('registration_fields', 'profilefields', ['meetingid' => $meetingid]);
$customfields = $DB->get_records_sql_menu("SELECT uif.shortname, uif.name FROM {user_info_field} uif WHERE uif.id IN ($customfieldids)");
$fields = $fields + $customfields;
if ($meetingid) {
	$form = '<form action="registrationform.php?meetingid=' . $meetingid . '" name="form_register" method="post" id="form_register">
			<legend>' . format_string($meetingrecord->name) . ' Registration Form</legend><br>
			<div class="form-row">';

	foreach ($fields as $key => $field) {
		$placeholdername = $key;
		$type = $key == 'email' ? 'email' : 'text';
		$addclass = $field == 'email' ? 'emailverify' : '';
		$form .=    '<div class="form-group col-md-4">
				  <label for="reg_' . $key . '">' . $field . '<span style="color:red">*</span></label>
				  <input type="' . $type . '" class="form-control ' . $addclass . '" name="' . $key . '" id="reg_' . $key . '" placeholder="Enter ' . $placeholdername . '" required/>';
		if ($key == 'password') {
			$form .= '</div>';
			$form .=    '<div class="form-group col-md-8" style="font-size:12px; background-color: #fffdce; margin-top: 11px;padding: 10px;">
					  The password must have at least 8 characters, at least 1 digit(s), at least 1 lower case letter(s), at least 1 upper case letter(s), at least 1 non-alphanumeric character(s) such as as *, -, or #
				   </div>';
		} else {
			$form .= '</div>';
		}
	}
	'</div>';

	$form .= '<div class="row w-100" id="otp-sec">
	<div class="col-lg-12">
	<div class="form-row form-inline felement mt-2" data-fieldtype="button" >
			  <div class="form-group col-md-12">
				  <label for="reg_otp">OTP Number<span style="color:red">*</span></label>
				  <input type="text" class="form-control" name="otp" id="id_otp" style="margin: 0 20px;" placeholder="Enter OTP" required>
				  
					<button class="btn btn-secondary ml-0" name="verify" id="id_verify" type="button">
					Send OTP
					</button>
					
				   <div class="downmsg"></div>
			  </div>
				<div class="downtimer"></div> <br>
			</div>
				<div class="form-row mt-2">
					<div class="form-group col-md-12">
						<p>I hereby declare that the details furnished are true and correct to the best of my knowledge and belief and I undertake to inform you of any changes therein, immediately. In case any of the above information is found to be false or untrue or misleading or mis-representing, I am aware that I may be held liable for it.*</p>
						<input type="checkbox" name="condition" id="condition" required>
						<span> &nbsp; I agree</span>
					</div>
				</div>
				<div class="form-row mt-2">
					<div class="form-group col-md-12">
						<input type="hidden" class="form-control" name="meetingid" id="meetingid" value=' . $meetingid . '>	
						<div class="pull-right">
						<input type="submit" name="submit" class="btn btn-primary" value="Create Account" id="reg_submit" />
						<button class="btn btn-secondary"><a href="' . $cancelurl . '">Cancel</a></button>
					</div>	
				</div>
				</div>
				</div>
				</form>';

	echo $form;
}

?>

<?php

echo $OUTPUT->footer();
?>