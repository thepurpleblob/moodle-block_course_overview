<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_block_course_overview_upgrade($oldversion, $block) {
    global $CFG;

    // Implement pagination
    if ($oldversion < 2018070400.2) {
        set_config('setmaxcourses', 10, 'block_course_overview');
        set_config('setmaxcoursesmax', 50, 'block_course_overview');
        upgrade_block_savepoint(true, 2018070400.2, 'course_overview', true);
    }

    return true;
}
