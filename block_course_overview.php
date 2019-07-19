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
 * Course overview block
 *
 * @package    block_course_overview
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Course overview block
 *
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_course_overview extends block_base {
    /**
     * If this is passed as mynumber then showallcourses, irrespective of limit by user.
     */
    const SHOW_ALL_COURSES = -2;

    /**
     * Block initialization
     */
    public function init() {
        $this->title = get_string('title', 'block_course_overview');
    }

    /**
     * Return contents of course_overview block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $USER, $CFG, $DB, $SESSION;
        
        require_once($CFG->dirroot.'/blocks/course_overview/locallib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot .'/course/externallib.php');
        require_once($CFG->dirroot .'/course/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_course_overview');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $isediting = $this->page->user_is_editing();

        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0 && optional_param('sesskey', '', PARAM_RAW) && confirm_sesskey()) {
            block_course_overview_update_mynumber($updatemynumber);
        }

        profile_load_custom_fields($USER);

        // Check if favourite added/removed.
        $favourite = optional_param('favourite', 0, PARAM_INT);
        if ($favourite) {
            block_course_overview_add_favourite($favourite);
        }
        $unfavourite = optional_param('unfavourite', 0, PARAM_INT);
        if ($unfavourite) {
            block_course_overview_remove_favourite($unfavourite);
        }

        // Check if sortorder updated.
        $soparam = optional_param('sortorder', -1, PARAM_INT);
        if ($soparam == -1) {
            $sortorder = block_course_overview_get_sortorder();
        } else {
            $sortorder = $soparam;
            block_course_overview_update_sortorder($sortorder);
        }
        
        // Get list of courses and sort order.
        $courses_sortorder = block_course_overview_get_all_courses();

        // Get data for favourites and course tab.
        $tabs = array();
        $ftab = new stdClass;
        $ftab->tab = 'favourites';
        list($ftab->sortedcourses, $ftab->totalcourses) = block_course_overview_get_sorted_courses($courses_sortorder['courses'], $courses_sortorder['sortorder'], true);
        $ftab->overviews = block_course_overview_get_overviews($ftab->sortedcourses);
        $ctab = new stdClass;
        $ctab->tab = 'courses';
        list($ctab->sortedcourses, $ctab->totalcourses)
            = block_course_overview_get_sorted_courses($courses_sortorder['courses'], $courses_sortorder['sortorder'], false, $config->keepfavourites, array_keys($ftab->sortedcourses));
        $rtab = new stdClass;
        $rtab->tab = 'recentlyaccessed';
        list($rtab->sortedcourses, $rtab->totalcourses)
            = block_course_overview_get_recent_courses($USER->id, 10);
        /**
         * Show icons in course tab
         */
        if(isset($config->showupdatesincourselist) && intval($config->showupdatesincourselist))
            $ctab->overviews = block_course_overview_get_overviews($ctab->sortedcourses);
        else
            $ctab->overviews = $ftab->overviews;
        $tabs = array(
            'favourites' => $ftab,
            'courses' => $ctab,
            'recentlyaccessed' => $rtab
        );

        // Get list of favourites.
        $favourites = array_keys($ftab->sortedcourses);

        // Default tab. One with something in it or favourites.
        echo '<br><br>';
        echo 'Last tab: ' . get_user_preferences('courseoverview_defaulttab', '#favourites');
        echo '<br><br>';
        if ($ftab->totalcourses || $config->defaulttab == BLOCKS_COURSE_OVERVIEW_FAVOURITE_VIEW) {
            $tab = 'favourites';
        } else {
            $tab = 'courses';
        }

        $renderer = $this->page->get_renderer('block_course_overview');
        // Render block.
        $main = new block_course_overview\output\main($config, $tabs, $isediting, $tab, $sortorder, $favourites);
        $this->content->text .= $renderer->render($main);
        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

    /**
     * Sets block header to be hidden or visible
     *
     * @return bool if true then header will be visible.
     */
    public function hide_header() {
        return false;
    }
}
