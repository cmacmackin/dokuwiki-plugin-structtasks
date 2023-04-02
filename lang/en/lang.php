<?php
/**
 * English language file for structtasks plugin
 *
 * @author Chris MacMackin <cmacmackin@gmail.com>
 */

$lang['assigned_subject'] = 'You\'v been assigned task "@TITLE@"';
$lang['assigned_text'] = 'You have been assigned the task @TITLELINK@ on @WIKINAME@ by @EDITOR@. This task should be completed by @DUEDATE@. Be sure to mark the task as completed and edit the page to reflect the outcome once you are finished: @EDITURL@.';
$lang['assigned_html'] = <<<'END'
<h1>@WIKINAME@ Task Assignment</h1>
You have been assigned the task <strong>@TITLELINK@</strong> by @EDITOR@.
This task should be completed by @DUEDATE@. Be sure to mark the task as
completed and <a href="@EDITURL@">edit the page</a> to reflect the outcome
once you are finished.
END;

$lang['removed_subject'] = 'You are no longer assigned task "@TITLE@"';
$lang['removed_text'] = '@EDITOR@ has unassigned you from task @TITLELINK@. You are no longer responsible for its completion and will receive no further emails about it.';
$lang['removed_html'] = <<<'END'
<h1>@WIKINAME@ Task Removal</h1>
@EDITOR@ has unassigned you from task @TITLELINK@. You are no longer
responsible for its completion and will receive no further emails about it.
END;

$lang['self_removal_subject'] = '@EDITOR@ has unassigned themselves from task "@TITLE@"';
$lang['self_removal_text'] = 'Please be aware that @EDITOR@ has removed themselves from the task @TITLELINK@, to which you are assigned on @WIKINAME@.';
$lang['self_removal_html'] = <<<'END'
<h1>@WIKINAME@ Task Update </h1>
Please be aware that @EDITOR@ has removed themselves from the task @TITLELINK@,
to which you are assigned.
END;

$lang['date_subject'] = 'The due-date has changed for task "@TITLE@"';
$lang['date_text'] = '@EDITOR@ has changed the due-date for task @TITLELINK@ on @WIKINAME@. It must now be completed by @DUEDATE@. The previous due-date was @PREVDUEDATE@. Be sure to mark the task as completed and edit the page to reflect the outcome once you are finished: @EDITURL@.';
$lang['date_html'] = <<<'END'
<h1>@WIKINAME@ Task Due-Date Change</h1>
@EDITOR@ has changed the due-date for task @TITLELINK@. <strong>It must now be
completed by @DUEDATE@.</strong> The previous due-date was @PREVDUEDATE@. Be
sure to mark the task as completed and <a href="@EDITURL@">edit the page</a> to
reflect the outcome once you are finished.
END;

$lang['openstatus_subject'] = 'Task "@TITLE@" has been marked "@STATUS@"';
$lang['openstatus_text'] = '@EDITOR@ has changed task @TITLELINK@ on @WIKINAME@ from "@PREVSTATUS@" to "@STATUS@". It is due on @DUEDATE@. Be sure to mark the task as completed and edit the page to reflect the outcome once you are finished: @EDITURL@.';
$lang['openstatus_text'] = <<<'END'
<h1>@WIKINAME@ Task @STATUS@</h1>
@EDITOR@ has changed task @TITLELINK@ from "@PREVSTATUS@" to "@STATUS@". It is
due on @DUEDATE@. Be sure to mark the task as completed and
<a href="@EDITURL@">edit the page</a> to reflect the outcome once you are
finished.
END;

$lang['closedstatus_subject'] = 'Task "@TITLE@" has been marked "@STATUS@"';
$lang['closedstatus_text'] = '@EDITOR@ has changed task @TITLELINK@ on @WIKINAME@ from "@PREVSTATUS@" to "@STATUS@". It is no long considered active and you will receive no further messages about it.';
$lang['closedstatus_text'] = <<<'END'
<h1>@WIKINAME@ Task @STATUS@</h1>
@EDITOR@ has changed task @TITLELINK@ from "@PREVSTATUS@" to "@STATUS@". It is
no longer considered active and you will receive no further emails about it.
END;

$lang['deleted_subject'] = 'Task "@TITLE@" has been deleted';
$lang['deleted_text'] = '@EDITOR@ has deleted the task @TITLELINK@ on @WIKINAME@. No further action is required.';
$lang['deleted_text'] = <<<'END'
<h1>@WIKINAME@ Task Deleted</h1>
@EDITOR@ has deleted task @TITLELINK@. No further action is required.
END;

$lang['reminder_subject'] = 'Reminder: task "@TITLE@" due in @DUEIN@';
$lang['reminder_text'] = 'Your task @TITLELINK@ on @WIKINAME@ is due in @DUEIN@, on @DUEDATE@. Be sure to mark the task as completed and edit the page to reflect the outcome once you are finished: @EDITURL@.';
$lang['reminder_text'] = <<<'END'
<h1>@WIKINAME@ Task Due in @DUEIN@</h1>
Your task @TITLELINK@ on @WIKINAME@ is due in @DUEIN@, on @DUEDATE@. Be
sure to mark the task as completed and <a href="@EDITURL@">edit the page</a>
to reflect the outcome once you are finished.
END;

$lang['today_subject'] = 'Reminder: Task "@TITLE@" due today!';
$lang['today_text'] = 'Your task @TITLELINK@ on @WIKINAME@ is due today! Please finish it. Once done, be sure to mark the task as completed and edit the page to reflect the outcome: @EDITURL@.';
$lang['today_text'] = <<<'END'
<h1>@WIKINAME@ Task Due Today</h1>
Your task @TITLELINK@ on @WIKINAME@ is due today! Please finish it. Once done,
be sure to mark the task as completed and <a href="@EDITURL@">edit the page</a>
to reflect the outcome.
END;

$lang['overdue_subject'] = 'Your task "@TITLE@" is overdue!';
$lang['overdue_text'] = 'Your task @TITLELINK@ on @WIKINAME@ is @DUEIN@ days overdue! Complete it right away and update the page to reflect the outcome: @EDITURL@.';
$lang['overdue_text'] = <<<'END'
<h1>@WIKINAME@ Task Overdue!</h1>
Your task @TITLELINK@ on @WIKINAME@ is due today! <strong>Complete it right
away</strong> and <a href="@EDITURL@">update the page</a> to reflect the
outcome.
END;

$lang['msg_invalid_schema'] = 'Schema "%s" is invalid for use with structtasks';
$lang['msg_handling_schema'] = 'Sending reminders for tasks in schema "%s"';
$lang['msg_processing'] = 'Processing notifications for task with ID %s';
$lang['msg_today_notifier'] = 'Sending notifications for tasks due today';
$lang['msg_reminder_notifier'] = 'Sending noticiations for tasks due in %s days';
$lang['msg_overdue_notifier'] = 'Sending notifications for overdue tasks';
$lang['msg_no_auth'] = 'Could not load authentication plugin to access user data';
