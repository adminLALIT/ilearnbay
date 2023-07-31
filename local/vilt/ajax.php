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

require_once("../../config.php");
global $DB;
$email = $_POST['email'];
$mail = $_POST['mail'];
$meetingid = $_POST['meetingid'];
$fromuser = core_user::get_noreply_user();
$json = [];
if ($DB->record_exists('user', ['email' => $email])) {    // check if email exists
    $json['exist'] = true;
    echo json_encode($json);
} else {

    $mail = get_mailer();
    $otp = rand(1111, 9999);
    $mail->addAddress($email); //To address
    $mail->setFrom($fromuser->email); //form address
    //$mail->setFrom("muthu@whitehouseit.com"); //form address
    $mail->Subject = "OTP For Registration in meeting."; // Subject
    $mail->Body    = "<p>Dear User,</p><p>Use the below mentioned OTP to confirm your Registration</p> 
                       <p>OTP        : $otp</p>
                       <p>Note: Please note that this OTP is valid for 5 minutes only.</p>
                       <p>Regards,</p><p>Skillsda LMS Team.</p>";
    $mail->isHTML(true);
    $mailsend = true;
    if ($mail->send()) {
        $insertrecord = new stdClass();
        $insertrecord->email = $email;
        $insertrecord->otp = $otp;
        $insertrecord->meetingid = $meetingid;
        $insertrecord->timecreated = time();
        $DB->insert_record('timer', $insertrecord, $returnid = true);
        $json['send'] = true;
    }
    else {
        $json['failed'] = true;
    }
    echo json_encode($json);
}
