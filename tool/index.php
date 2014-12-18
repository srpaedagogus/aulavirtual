<?php /** * @package    block_evalcomix * @copyright  2010 onwards EVALfor Research Group {@link http://evalfor.net/} * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later * @author     Daniel Cabeza Sánchez <daniel.cabeza@uca.es>, Juan Antonio Caballero Hernández <juanantonio.caballero@uca.es> */ require_once('../../../config.php');require_once('../configeval.php');require_once('../assessment/lib.php');require_once($CFG->dirroot . '/lib/accesslib.php');$courseid 	   = required_param('id', PARAM_INT);        // course id$tid 	   = optional_param('tool', 0, PARAM_INT);        // course id$sorttool 	   = optional_param('sorttool', '', PARAM_TEXT);        // course idsortitemid=lastname	$edit 	   = optional_param('edit', '', PARAM_ALPHANUM);        // tool id to be uploadedif (!$course = $DB->get_record('course', array('id' => $courseid))) {	print_error('nocourseid');}global $OUTPUT, $USER;	$PAGE->set_url(new moodle_url('/blocks/evalcomix/tool/index.php', array('id' => $courseid)));$PAGE->set_pagelayout('incourse');// Print the header$PAGE->navbar->add('evalcomix', new moodle_url('../assessment/index.php?id='.$courseid));$buttons = null;require_login($course);$context = context_course::instance($course->id);//require_capability('block/evalcomix:edit', $context);require_capability('moodle/grade:viewhidden', $context);print_grade_page_head($course->id, 'report', 'grader', null, false, $buttons, false);if (ob_get_level() == 0){ 	ob_start();}				echo '					<center>		<div><img src="'. $CFG->wwwroot . EVXLOGOROOT .'" width="230" alt="EvalCOMIX"/></div><br>		<div><input type="button" style="color:#333333" value="'. get_string('assesssection', 'block_evalcomix').'" onclick="location.href=\''. $CFG->wwwroot .'/blocks/evalcomix/assessment/index.php?id='.$courseid .'\'"/></div><br>	</center>';echo "	<noscript>		<div style='color: #f00;'>".get_string('alertjavascript', 'block_evalcomix')."</div>	</noscript>\n";	if (has_capability('moodle/block:edit',$context, $USER->id)){	//if the login user is an editing teacher	$editing = true;}else{	$editing = false;}echo '	<script type="text/javascript">		function urledit(u, nombre, edit){			win2 = window.open(u, nombre, "menubar=0,location=0,scrollbars,resizable,width=780,height=500");			checkChildedit(edit);		}		function checkChildedit(edit) {			if (win2.closed) {			 window.location.replace("'.$CFG->wwwroot .'/blocks/evalcomix/tool/index.php?id='.$courseid.'&edit=" + edit);						/*window.location.href = "'.$CFG->wwwroot .'/blocks/evalcomix/tool/index.php?id='.$courseid.'&edit=" + edit;*/		}		else setTimeout("checkChildedit(\'"+edit+"\')",1);		}	</script>';include_once($CFG->dirroot .'/blocks/evalcomix/javascript/popup.php');include_once($CFG->dirroot .'/blocks/evalcomix/classes/evalcomix_tool.php');include_once($CFG->dirroot .'/blocks/evalcomix/classes/evalcomix.php');include_once($CFG->dirroot .'/blocks/evalcomix/classes/webservice_evalcomix_client.php');if($tid){	$tooldelete = evalcomix_tool::fetch(array('id' => $tid));	if($tooldelete){		$response = webservice_evalcomix_client::get_ws_deletetool($tooldelete->idtool);		$tooldelete->delete();	}}if(isset($edit) && $edit != '' && $edit != 'undefined'){	$tool = evalcomix_tool::fetch(array('idtool' => $edit));	//llamada para obtener datos y actualizar. Por lo general	$response = webservice_evalcomix_client::get_ws_list_tool($course->id, $tool->idtool);	if($response != false){		$tool->type = $response->type;		$tool->title = $response->title;		$tool->update();	}}if(!$environment = evalcomix::fetch(array('courseid' => $course->id))){	$environment = new evalcomix('', $courseid, 'evalcomix');	$environment->insert();}$tools = evalcomix_tool::fetch_all(array('evxid' => $environment->id));$toollist = array();if($tools){	foreach($tools as $tool){		if($tool->type == 'tmp'){				//llamada para obtener datos y actualizar. Por lo general			$response = webservice_evalcomix_client::get_ws_list_tool($course->id, $tool->idtool);			if($response != false){				$tool->type = $response->type;				$tool->title = $response->title;				$tool->update();				array_push($toollist, $tool);			}			else{				$result = $tool->delete();			}		}		else{			array_push($toollist, $tool);		}	}}if($sorttool == 'title'){	usort($toollist, 'cmp_type_tool');	usort($toollist, 'cmp_title_tool');}elseif($sorttool == 'type'){	usort($toollist, 'cmp_title_tool');	usort($toollist, 'cmp_type_tool');}//$array = sort_object_array($tools, 'title');print_r($array);exit;$lang = current_language();$url_create = webservice_evalcomix_client::get_ws_createtool(null, $lms = MOODLE_NAME, $course->id, $lang.'_utf8');$counttool = count($toollist);echo '	<center>		<div style="font-weight: bold; margin-bottom:0.5em">			<h5> '. $OUTPUT->help_icon('whatis', 'block_evalcomix') . get_string('counttool', 'block_evalcomix') .':  '. $counttool .'</h5>		</div>		<div>			<table style="width:80%;text-align:left;border-color:#146C84;background-color:#fff" border=1>				<tr style="color:#00f; font-weight: bold; text-align:center">					<td style="background-color:inherit"><a href="index.php?id='.$courseid.'&sorttool=title">'. get_string('title', 'block_evalcomix') .'</a></td>					<td style="background-color:inherit"><a href="index.php?id='.$courseid.'&sorttool=type">'. get_string('type', 'block_evalcomix') .'</a></td>					<td style="padding:0.2em;background-color:inherit;">';					if ($editing){	//if the login user is an editing teacher					echo '						<input type="button" value="'. get_string('newtool', 'block_evalcomix') .'" onclick="urledit(\''. $url_create .'\', \'wincreate\');">';}					echo '					</td>				</tr>';foreach($toollist as $tool){	//$url_view = webservice_evalcomix_client::get_ws_viewtool($tool->idtool);	$url_view = '../assessment/assessment_form.php?id='.$course->id.'&t='.$tool->idtool.'&mode=view&vt=1';	//$url_open = 'edit_form.php?id='.$course->id.'&t='.$tool->idtool;	$url_open = webservice_evalcomix_client::get_ws_createtool($tool->idtool, $lms = MOODLE_NAME, $course->id, $lang.'_utf8', 'open');	echo '				<tr>									<td style="border:1px solid #146C84; padding-left:0.6em">'. $tool->title.'</td>					<td style="border:1px solid #146C84;padding-left:0.5em;text-align:center;">'. get_string($tool->type, 'block_evalcomix') .'</td>					<td style="border:1px solid #146C84;text-align:center;"><input type="image" style="border:0; width:20px" src="'. $CFG->wwwroot.'/blocks/evalcomix/images/lupa.gif" title="'. get_string('view','block_evalcomix').'" alt="'. get_string('view','block_'.blockname).'" width="20"  onclick="url(\''. $url_view .'\', \'win1\')">';		if ($editing) {			echo '			<input type="image" style="border:0; width:20px" src="'. $CFG->wwwroot.'/blocks/evalcomix/images/edit.png" title="'. get_string('open', 'block_evalcomix') .'" alt="'. get_string('open', 'block_'.blockname) .'" width="20"  onclick="urledit(\''. $url_open .'\', \'win_open\', \''.$tool->idtool.'\');">						<input type="image" style="border:0; width:20px" src="'. $CFG->wwwroot.'/blocks/evalcomix/images/delete.png" title="'. get_string('delete','block_evalcomix').'" alt="'. get_string('delete','block_'.blockname).'" width="20" value="'.$tool->id.'" onclick="if(confirm(\'¿Está seguro que desea eliminar el instrumento?\'))location.href=\'index.php?id='.$courseid.'&tool='.$tool->id.'\';">';	}						echo '				</td>				</tr>	';}echo '			</table>		</div>	</center>';ob_flush();flush();$newgrades = webservice_evalcomix_client::get_assessments_modified(array('tools' => $toollist));if(!empty($newgrades)){	include_once($CFG->dirroot .'/blocks/evalcomix/classes/evalcomix_assessments.php');	include_once($CFG->dirroot .'/blocks/evalcomix/classes/evalcomix_tasks.php');	include_once($CFG->dirroot .'/blocks/evalcomix/classes/evalcomix_grades.php');	$tasks = evalcomix_tasks::get_tasks_by_courseid($courseid);	$toolids = array();	foreach($tasks as $task){		if($assessments = evalcomix_assessments::fetch_all(array('taskid' => $task->id))){			foreach($assessments as $assessment){				$activity = $task->instanceid;				$module = evalcomix_tasks::get_type_task($activity);								$mode = grade_report_evalcomix::get_type_evaluation($assessment->studentid, $courseid);				$str = $courseid . '_' . $module . '_' . $activity . '_' . $assessment->studentid . '_' . $assessment->assessorid . '_' . $mode . '_' . MOODLE_NAME;				$assessmentid = md5($str);				if(isset($newgrades[$assessmentid])){					$grade = $newgrades[$assessmentid]->grade;					$toolids[] = $newgrades[$assessmentid]->toolid;					$assessment->grade = $grade;					$assessment->update();					if($evalcomix_grade = evalcomix_grades::fetch(array('courseid' => $courseid, 'cmid' => $task->instanceid, 'userid' => $assessment->studentid))){						$params = array('cmid' => $task->instanceid, 'userid' => $assessment->studentid, 'courseid' => $courseid);						$finalgrade = evalcomix_grades::get_finalgrade_user_task($params);						if($finalgrade !== null){							$evalcomix_grade->finalgrade = $finalgrade;							$evalcomix_grade->update();						}								}				}			}		}	}		webservice_evalcomix_client::set_assessments_modified(array('toolids' => $toolids));}echo $OUTPUT->footer();