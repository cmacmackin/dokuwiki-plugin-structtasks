<?php

namespace dokuwiki\plugin\structtasks\test;

use dokuwiki\plugin\struct\meta\AccessTable;
use dokuwiki\plugin\struct\test\mock\Assignments;
use dokuwiki\plugin\structtasks\meta\AssignedNotifier;

use DokuWikiTest;

/**
 * Tests action handler for structtasks plugin
 *
 * @group plugin_structtasks
 * @group plugins
 */

class action_plugin_structtasks_test extends StructtasksTest {

    protected $pluginsEnabled = array('structtasks', 'struct', 'sqlite');
    
    function testPageSaveNoAction() {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $this->loadSchemaJSON('valid', '', 100);
        $action = plugin_load('action', 'structtasks');
        $action->loadConfig();
        $notifier = $this->createMock(AssignedNotifier::class);
        $notifier->expects($this->never())->method('sendMessage');
        $action->notifiers = [$notifier];
        saveWikiText('some:page', 'test page content', 'saved for testing');
    }

    function testNoSchema() {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = '';
        $this->loadSchemaJSON('valid', '', 100);
        $action = plugin_load('action', 'structtasks');
        $action->loadConfig();
        $notifier = $this->createMock(AssignedNotifier::class);
        $notifier->expects($this->never())->method('sendMessage');
        $action->notifiers = [$notifier];
        saveWikiText('some:page', 'test page content', 'saved for testing');
    }

    function testPageSaveAction() {
        global $auth;
        $auth->createUser('user1', 'abcdefg', 'Arron Dom Person', 'adperson@example.com');
        $auth->createUser('user2', '123456789', 'Fay Mail', 'user2@example.com');

        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $this->loadSchemaJSON('valid', '', 100);
        $action = plugin_load('action', 'structtasks');
        $action->loadConfig();

        $page = 'some:page';
        $page_title = 'Some Title';
        $old_content = "====== ${page_title} ======\nInitial content";
        $new_content = "====== ${page_title} ======\nNew content";
        $old_data = ['duedate' => '2023-03-27',
                     'assignees' => ['user1'],
                     'status' => 'Ongoing'];
        $new_data = ['duedate' => '2023-04-10',
                     'assignees' => ['user1', 'user2'],
                     'status' => 'Ongoing'];
        $expected_old_data =[
            'duedate' => date_create($old_data['duedate']),
            'assignees' => ['Arron Dom Person <adperson@example.com>'],
            'status' => $old_data['status'],
            'content' => $old_content,
            'duedate_formatted' => '27 Mar 2023',
        ];
        $expected_new_data = [
            'duedate' => date_create($new_data['duedate']),
            'assignees' => ['Arron Dom Person <adperson@example.com>',
                            'Fay Mail <user2@example.com>'],
            'status' => $new_data['status'],
            'content' => $new_content,
            'duedate_formatted' => '10 Apr 2023',
        ];

        $_SERVER['REMOTE_USER'] = 'user1';
        $notifier = $this->createMock(AssignedNotifier::class);
        $notifier->expects($this->once())
                 ->method('sendMessage')
                 ->with($this->equalTo($page),
                        $this->equalTo($page_title),
                        $this->equalTo('Arron Dom Person'),
                        $this->equalTo('Arron Dom Person <adperson@example.com>'),
                        $this->equalTo($expected_new_data),
                        $this->equalTo($expected_old_data)
                 );
        $action->notifiers = [$notifier];

        $access = AccessTable::getPageAccess('valid', $page, time());
        $access->saveData($old_data);
        $this->waitForTick();
        saveWikiText($page, $old_content, 'save 1');
        $assignments = Assignments::getInstance();
        $assignments->assignPageSchema($page, 'valid');

        $this->waitForTick();
        $access = AccessTable::getPageAccess('valid', $page, time());
        $access->saveData($new_data);
        saveWikiText($page, $new_content, 'save 2');
    }
}
