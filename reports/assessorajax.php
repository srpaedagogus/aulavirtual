<?php
require_once('../../../config.php');

$courseid 	   = required_param('id', PARAM_INT);        // course id
$cmid 		   = required_param('tid', PARAM_INT);     	
$modality      = required_param('mode', PARAM_ALPHA);

global $CFG, $DB;
require_once($CFG->dirroot . '/blocks/evalcomix/assessment/lib.php');
require_once($CFG->dirroot . '/blocks/evalcomix/classes/evalcomix_tasks.php');
require_once($CFG->dirroot . '/blocks/evalcomix/classes/evalcomix_modes.php');
	
$context = get_context_instance(CONTEXT_COURSE, $courseid);	

$assessor_users = array();
if($task = evalcomix_tasks::fetch(array('instanceid' => $cmid))){
	$params2 = array('taskid' => $task->id);
	$assessments = evalcomix_assessments::fetch_all($params2);
	if($assessments){
		foreach($assessments as $assessment){
			if($modality == 'teacher'){
				if(has_capability('block/evalcomix:edit', $context, $assessment->assessorid)){
					$assessor_users[] = $assessment->assessorid;
				}
			}
			else{
				if(!has_capability('block/evalcomix:edit', $context, $assessment->assessorid)){
					$assessor_users[] = $assessment->assessorid;
				}
			}
		}
	}
}

if(empty($assessor_users)){
	$output = html_writer::start_tag('div', array('style' => 'font-style:italic'));
	$output .= get_string('nostudentselfassessed', 'block_evalcomix');
	$output .= html_writer::end_tag('div');
}
else{
	$num_users = 0;
	$output = html_writer::start_tag('table');
	$users = array_unique($assessor_users);
	foreach($users as $userid){
		if($user = $DB->get_record('user', array('id' => $userid))){
			$output .= html_writer::start_tag('tr');
			$output .= html_writer::start_tag('td', array('style' => 'margin:0;padding:0'));
			$output .= html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'assessors', 'value' => $user->id, 'onclick' => "doWork('users', '". $CFG->wwwroot."/blocks/evalcomix/reports/userajax.php?id=".$courseid."&mode=".$modality."&a=".$userid."&tid=".$cmid."', 'one=1');var el=document.getElementById('submit');el.disabled=false"));
			$output .= html_writer::end_tag('td');
			$output .= html_writer::start_tag('td', array('style' => 'margin:0;padding:0'));
			$output .= html_writer::start_tag('label', array('for' => 'assessor_'. $num_users));
			$output .= $user->lastname .', '.$user->firstname;
			$output .= html_writer::end_tag('label');
			$output .= html_writer::end_tag('td');
			$output .= html_writer::end_tag('tr');
			$output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'assessorname_'. $num_users, 'value' => $user->firstname));
			$output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'assessorsurname_'. $num_users, 'value' => $user->lastname));
			++$num_users;
		}
	}

	$output .= html_writer::end_tag('table');
}
echo $output;
