<?php
/**
 * @package    block_evalcomix
 * @copyright  2010 onwards EVALfor Research Group {@link http://evalfor.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Daniel Cabeza Sánchez <daniel.cabeza@uca.es>, Juan Antonio Caballero Hernández <juanantonio.caballero@uca.es>
 */
 
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_heading('evalcomix_heading', get_string('adminheader', 'block_evalcomix'),
                       get_string('admindescription', 'block_evalcomix')));
	
	// Server URL
	$settings->add(new admin_setting_configtext('evalcomix_serverurl', get_string('serverurl', 'block_evalcomix'),
                       get_string('serverurlinfo', 'block_evalcomix'), ''));
					
	 // Validation button

    $html = html_writer::script('', $CFG->wwwroot.'/blocks/evalcomix/validate.js');
    $html .=  html_writer::tag('p', get_string('validationinfo', 'block_evalcomix')); 
    $html .=  html_writer::start_tag('div', array('style' => 'text-align:center;padding-top: 15px; padding-bottom:5px;'));
    $html .=  html_writer::start_tag('span', array('id' => 'validatebutton', 'class' => 'yui-button yui-link-button'));
    $html .=  html_writer::tag('a', get_string('validationbutton', 'block_evalcomix'), 
		array('id' => 'validatebtn', 
			'name' => 'validatebtn', 
			'href' => 'javascript:validate();'));
			//'href' => 'javascript:location.href="../blocks/evalcomix/verify.php?u="+document.getElementById("id_s__evalcomix_serverurl").value'));
    $html .= html_writer::end_tag('span');
    $html .= html_writer::end_tag('div');
    

    $settings->add(new admin_setting_heading('lamslesson_validation', get_string('validationheader', 'block_evalcomix'),
                       $html));
	
}