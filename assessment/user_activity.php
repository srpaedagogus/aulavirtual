<?php
/**
 * @package    block_evalcomix
 * @copyright  2010 onwards EVALfor Research Group {@link http://evalfor.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Daniel Cabeza Sánchez <daniel.cabeza@uca.es>, Juan Antonio Caballero Hernández <juanantonio.caballero@uca.es>
 */
 
require('../../../config.php');
if(file_exists($CFG->dirroot.'/report/outline/locallib.php')){
	require_once($CFG->dirroot.'/report/outline/locallib.php');
}
require_once($CFG->dirroot.'/course/lib.php');

$userid   = required_param('id', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$modid     = required_param('mod', PARAM_INT);

$user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
require_login($course->id);
$url = new moodle_url('/blocks/evalcomix/assessment/user_activity.php', array('userid'=>$userid,'courseid'=>$courseid));
$PAGE->set_url($url);

//$coursecontext   = context_course::instance($course->id);

$PAGE->set_pagelayout('popup');

//Catch the mods
$modinfo = get_fast_modinfo($course->id);
$mods = $modinfo->get_cms();
//Identify the concrete mod by $modid
$mod = $mods[$modid];

$title = $user->username .get_string('studentwork2','block_evalcomix'). $mod->name;
$PAGE->set_title($title);
		
echo $OUTPUT->header();

echo '<h1>'. $title.'</h1>';

global $CFG, $DB;

$instance = $DB->get_record("$mod->modname", array("id"=>$mod->instance));
$libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";

if (file_exists($libfile)) {
	require_once($libfile);
	
	$user_complete = $mod->modname."_user_complete";
	if (function_exists($user_complete)) {	
		$context = context_module::instance($mod->id);
	
		$image = $OUTPUT->pix_icon('icon', $mod->modfullname, 'mod_'.$mod->modname, array('class'=>'icon'));
		echo "<h3>$image $mod->modfullname: ".
			 "<a href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">".
			 format_string($instance->name,true)."</a></h3>";

		ob_start();
		
		//if ($mod->modname == 'assign' and $USER->id != $user->id and !has_capability('block/evalcomix:edit',$context)){
		if ($mod->modname == 'assign' and $USER->id != $user->id and !has_capability('moodle/grade:viewhidden',$context)){
			require_once($CFG->dirroot . '/mod/assign/locallib.php');
			$context = context_module::instance($mod->id);
			$assignment = new assign($context, $mod, $course);
			$submission = get_user_submission($assignment, $user->id); 
			$sid = null;
			if($submission){
				$sid = $submission->id;
			}
			
			$teamsubmission = null;
			if ($assignment->get_instance()->teamsubmission) {
				$teamsubmission = $assignment->get_group_submission($user->id, 0, false);
				if(isset($teamsubmission->id)){
					$sid = $teamsubmission->id;
				}
				/*$submissiongroup = $assignment->get_submission_group($user->id);
				$groupid = 0;
				if ($submissiongroup) {
					$groupid = $submissiongroup->id;
				}
				$notsubmitted = $this->get_submission_group_members_who_have_not_submitted($groupid, false);*/
			}
		
			if(isset($sid)){
				$tree = new assign_files($context, $sid,'submission_files', 'assignsubmission_file');
				$args =  htmllize_tree($tree, $tree->dir);
				if(isset($args) && !empty($args)){
					echo '<ul>';
					foreach($args as $arg){
						$relativepath = assignsubmission_file_pluginfile($course, $tree->cm, $tree->context, 'submission_files', $arg, 1);
						$fullpath = 'pluginfile.php/' . $relativepath;
						echo '<li><div><a href="'. $fullpath . '">'. $arg[1] .'</a></div></li>';
					}
					echo '</ul>';
				}
				if($record = $DB->get_record('assignsubmission_onlinetext', array('assignment'=>$assignment->get_instance()->id, 'submission'=>$sid))){
					print_r($record->onlinetext);
				}
			}
		}
		elseif($mod->modname == 'assignment'){
			require_once($CFG->dirroot . '/mod/assignment/locallib.php');
			$context = context_module::instance($mod->id);
			$assignment = new assignment_base($mod->id);
			$type = $assignment->assignment->assignmenttype;
			
			$submission = get_user_submission_old($assignment, $user->id);
			if(isset($submission->id)){
				$fs = get_file_storage();

				if ($files = $fs->get_area_files($context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
					$countfiles = count($files)." ".get_string("uploadedfiles", "assignment");
					$output = '';
					foreach ($files as $file) {
						$countfiles .= "; ".$file->get_filename();
						$filename = $file->get_filename();
						$mimetype = $file->get_mimetype();
						$path = file_encode_url('pluginfile.php', '/'.$context->id.'/mod_assignment/submission/'.$submission->id.'/'.$filename);
						$output .= '<a href="'.$path.'" >'.$OUTPUT->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon')).s($filename).'</a><br>';
					}
					echo $output;
				}				
			}
			$type = $assignment->assignment->assignmenttype;
			if($type == 'online'){
				require_once("$CFG->dirroot/mod/assignment/type/online/assignment.class.php");				
				$assignment_online = new assignment_online($modid);
				echo $assignment_online->get_submission($userid)->data1;
			}
		}
		elseif($mod->modname == 'workshop'){
			$context = context_module::instance($mod->id);
			require_once($CFG->dirroot . '/mod/workshop/locallib.php');
			$workshop   = new workshop($instance, $mod, $course);
			$submission = $workshop->get_submission_by_author($user->id);
			
			if(is_object($submission)){
				$content = format_text($submission->content, $submission->contentformat, array('overflowdiv'=>true));
				$content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id,
                                                        'mod_workshop', 'submission_content', $submission->id);
				print_r($content);
			
				$fs     = get_file_storage();
				$ctx    = $context;
				$files  = $fs->get_area_files($ctx->id, 'mod_workshop', 'submission_attachment', $submission->id);
				echo '<ul>';
				foreach ($files as $file) {
					if ($file->is_directory()) {
						continue;
					}

					$filepath   = $file->get_filepath();
					$filename   = $file->get_filename();
					$fileurl    = file_encode_url($CFG->wwwroot . '/blocks/evalcomix/assessment/pluginfile.php',
										'/' . $ctx->id . '/mod_workshop/submission_attachment/' . $submission->id . $filepath . $filename, false);
					
					$type       = $file->get_mimetype();
					//$image      = $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file), 'moodle', array('class' => 'icon'));

					$linkhtml   = html_writer::link($fileurl, $image) . substr($filepath, 1) . html_writer::link($fileurl, $filename);
					
					echo '<li>' . $linkhtml . '</li>';
					$linktxt    = "$filename [$fileurl]";
				}
				echo '</ul>';
			}
		}
		else {
			echo "<ul>";
			$user_complete($course, $user, $mod, $instance);
		
			echo "</ul>";
		}
		$output = ob_get_contents();
		ob_end_clean();

		if (str_replace(' ', '', $output) != '<ul></ul>') {
			echo $output;
		}
	}	
}

echo "</table>";

echo '</div>';  // content
echo '</div>';  // section

echo $OUTPUT->close_window_button();
echo $OUTPUT->footer();

	
function get_user_submission($assignment, $userid) {
	global $DB, $USER;

	if (!$userid) {
		$userid = $USER->id;
	}
	
	// if the userid is not null then use userid
	$submission = $DB->get_record('assign_submission', array('assignment'=>$assignment->get_instance()->id, 'userid'=>$userid));

	if ($submission) {
		return $submission;
	}
		
	return false;
}


function assignsubmission_file_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $USER, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    //require_login($course, false, $cm);
    $itemid = (int)array_shift($args);
    $record = $DB->get_record('assign_submission', array('id'=>$itemid), 'userid, assignment', MUST_EXIST);
    
    if (!$assign = $DB->get_record('assign', array('id'=>$cm->instance))) {
        return false;
    }

    if ($assign->id != $record->assignment) {
        return false;
    }

    
    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/assignsubmission_file/submission_files/$itemid/$relativepath";
	return $fullpath;
}

function htmllize_tree(assign_files $tree, $dir) {
	global $CFG;
			
	if (empty($dir['files'])) {
		return '';
	}
		
	$result = array();
	foreach ($dir['files'] as $file) {
		$filename = $file->get_filename();
			
		$arg = explode('pluginfile.php/', ltrim($file->fileurl));
		$aux = explode('/', $arg[1]);
		$aux2 = explode ('?', $aux[4]);
		$args = array($aux[3], $filename);
		$result[] = $args;
	}
		
	return $result;
}

function get_user_submission_old($assignment, $userid) {
	global $DB, $USER;

	if (!$userid) {
		$userid = $USER->id;
	}
	// if the userid is not null then use userid
	$submission = $DB->get_record('assignment_submissions', array('assignment'=>$assignment->assignment->id, 'userid'=>$userid));
	
	if ($submission) {
		return $submission;
	}
		
	return false;
}
