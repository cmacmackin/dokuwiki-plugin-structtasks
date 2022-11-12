<?php
/**
 * Options for the structtasks plugin
 *
 * @author Chris MacMackin <cmacmackin@gmail.com>
 */


$meta['schema'] = array('string');
$meta['reminder'] = array('multicheckbox', '_choices' => array('28', '14', '7', '6', '5', '4', '3', '2', '1', '0'));
$meta['overdue_reminder'] = array('onoff');
$meta['completed'] = array('regex');
