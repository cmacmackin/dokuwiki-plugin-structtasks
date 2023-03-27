<?php

namespace dokuwiki\plugin\structtasks\test;

use dokuwiki\plugin\struct\meta\AccessTable;
use dokuwiki\plugin\struct\test\mock\Assignments;
use dokuwiki\plugin\structtasks\meta\AssignedNotifier;

use DokuWikiTest;

/**
 * General tests for the structtasks plugin
 *
 * @group plugin_structtasks
 * @group plugins
 */

class action_plugin_structtasks_test extends StructtasksTest {
    // Test does nothing when page not assigned schema
    // Test does nothing when no schema set
    // Test sending correct assortment of notifications
    protected $pluginsEnabled = array('structtasks', 'struct', 'sqlite');

    public function setUp(): void {
        parent::setUp();
    }
    
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
        $expected_old_data = $old_data;
        $expected_old_data['assignees'] = ['Arron Dom Person <adperson@example.com>'];
        $expected_old_data['content'] = $old_content;
        $expected_new_data = $new_data;
        $expected_new_data['assignees'] = ['Arron Dom Person <adperson@example.com>',
                                           'Fay Mail <user2@example.com>'];
        $expected_new_data['content'] = $new_content;

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
