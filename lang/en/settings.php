<?php
/**
 * english language file for structtasks plugin
 *
 * @author Chris MacMackin <cmacmackin@gmail.com>
 */

// keys need to match the config setting name
$lang['schema'] = 'Name of Struct schema continaing task data; must contain fields "duedate", "assignees", and "status"';
$lang['reminder'] = 'Send email reminders to assignees this number of days before a task is due to be completed';
$lang['overdue_reminder'] = 'Email reminders to assignees every day for tasks that are overdue';
$lang['completed'] = '(Case-insensitive) regular expression applied to the "status" column of the schema to determine when a task is completed or otherwise no longer active';
