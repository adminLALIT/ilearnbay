<?php
namespace enrol_phonepe\event;
defined('MOODLE_INTERNAL') || die();

class phonepe_event extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = 0;
        $this->data['action'] = 'payment';
        $this->data['objecttable'] = 'enrol_phonepe';
    }

    public static function get_name() {
        return get_string('event_phonepe_payment', 'enrol_phonepe');
    }

    public function get_description() {
        return "Phonepe Event";
    }
}
