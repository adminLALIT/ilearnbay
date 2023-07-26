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
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    local_vilt
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/vilt/locallib.php');

$courseid = required_param('id', PARAM_INT);
$companyid = optional_param('company', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$_SESSION['companyid'] = $companyid;
$lp = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url('/local/vilt/adduser.php', ['id'=>$courseid, 'company'=>$companyid]);
$PAGE->set_heading('Assign User to Meeting');
$PAGE->set_title('Assign User');
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/local/vilt/trainingdata.php');
}
echo $OUTPUT->single_button($returnurl, 'Back');
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}

echo $OUTPUT->heading(get_string('assignto', 'local_vilt', format_string($lp->fullname)));


// // Get the user_selector we will need.
$potentialuserselector = new lp_candidate_selector('addselect', array('lpid'=>$lp->id, 'companyid' => $companyid, 'accesscontext'=>$context));
$existinguserselector = new lp_existing_selector('removeselect', array('lpid'=>$lp->id, 'accesscontext'=>$context));

// Process incoming user assignments to the cohort

if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialuserselector->get_selected_users();
  
    if (!empty($userstoassign)) {
        foreach ($userstoassign as $adduser) {
            lp_add_member($lp->id, $adduser->id);
        }

        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

// Process removing user assignments to the cohort
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $existinguserselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            lp_remove_member($lp->id, $removeuser->id);
        }
        $potentialuserselector->invalidate_selected_users();
        $existinguserselector->invalidate_selected_users();
    }
}

// Print the form.
?>
<form id="assignform" method="post" action="<?php echo $PAGE->url ?>"><div>
  <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
  <input type="hidden" name="returnurl" value="<?php echo $returnurl->out_as_local_url() ?>" />

  <table summary="" class="generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php print_string('currentusers', 'cohort'); ?></label></p>
          <?php
           $existinguserselector->display() 
           ?>
      </td>
      <td id="buttonscell">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.s(get_string('add')); ?>" title="<?php p(get_string('add')); ?>" /><br />
          </div>

          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo s(get_string('remove')).'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php p(get_string('remove')); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php print_string('potusers', 'cohort'); ?></label></p>
          <?php $potentialuserselector->display() ?>
      </td>
    </tr>
    <tr><td colspan="3" id='backcell'>
      <input type="submit" name="cancel" value="<?php p(get_string('cancel', 'local_vilt')); ?>" />
    </td></tr>
  </table>
</div></form>
<?php

echo $OUTPUT->footer();