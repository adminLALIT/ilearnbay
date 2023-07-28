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
 * @package   local_recommendation
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function is_curator()
{
    global $DB, $USER;

    if ($check = $DB->record_exists('role', ['shortname' => 'curator'])) {
        $curatorrole = $DB->get_field('role', 'id', ['shortname' => 'curator']);
        if (user_has_role_assignment($USER->id, $curatorrole)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_student()
{
    global $DB, $USER;

    if ($check = $DB->record_exists('role', ['shortname' => 'student'])) {
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        if (user_has_role_assignment($USER->id, $studentrole)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_companyuser()
{
    global $DB, $USER;

    if ($check = $DB->record_exists('company_users', ['userid' => $USER->id, 'managertype' => 0])) {
        if ($check) {
            return true;
        }
        else {
            return false;
        }
    }
    else {
        return false;
    } 
}
function get_vimeo_content($query)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, "https://v1.nocodeapi.com/vikasverma/vimeo/SgoRgOIyBuLXDCWf/search?q=$query&page=1&perPage=20");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_POSTFIELDS, '{}');
    curl_setopt(
        $curl,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json'
        )
    );
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function get_youtube_content($query)
{

    $apikey = get_config('youtube', 'apikey');
    $googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . $query . '&maxResults=' . MAX_RESULTS . '&key=' . $apikey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    curl_close($ch);
    return $response;
}

