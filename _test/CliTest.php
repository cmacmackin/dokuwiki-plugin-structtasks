<?php

namespace dokuwiki\plugin\structtasks\test;

use splitbrain\phpcli\Options;
use dokuwiki\plugin\struct\meta\AccessTable;
use dokuwiki\plugin\struct\test\mock\Assignments;
use dokuwiki\plugin\structtasks\meta\ReminderNotifier;
use dokuwiki\plugin\structtasks\meta\TodayNotifier;
use dokuwiki\plugin\structtasks\meta\OverdueNotifier;

use DokuWikiTest;

/**
 * Tests CLI-related methods for the structtasks plugin
 *
 * @group plugin_structtasks
 * @group plugins
 */

class cli_plugin_structtasks_test extends StructtasksTest {
    protected $pluginsEnabled = array('structtasks', 'struct', 'sqlite');

    function testInitialiseSuccess() {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $this->loadSchemaJSON('valid', '', 100);
        $cli = plugin_load('cli', 'structtasks');
        $this->assertTrue($cli->initialise());
    }

    function testInitialiseInvalidSchema() {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'badstatus';
        $this->loadSchemaJSON('badstatus', '', 100);
        $cli = plugin_load('cli', 'structtasks');
        $this->assertFalse($cli->initialise(false));
    }

    function testInitialiseMissingSchema() {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $cli = plugin_load('cli', 'structtasks');
        $this->assertFalse($cli->initialise(false));
    }

    function testInitialiseNoSchema() {
        global $conf;
        $cli = plugin_load('cli', 'structtasks');
        $this->assertFalse($cli->initialise());
    }

    function testCreateNotifiers() {
        $cli = plugin_load('cli', 'structtasks');

        $n = $cli->createNotifiers([3, 2, 1, 0], true);
        $this->assertEquals(3, count($n));
        $this->assertInstanceOf(TodayNotifier::class, $n[0]);
        $this->assertInstanceOf(ReminderNotifier::class, $n[1]);
        $this->assertEquals([3, 2, 1], $n[1]->getDaysBefore());
        $this->assertInstanceOf(OverdueNotifier::class, $n[2]);

        $n = $cli->createNotifiers([0], true);
        $this->assertEquals(2, count($n));
        $this->assertInstanceOf(TodayNotifier::class, $n[0]);
        $this->assertInstanceOf(OverdueNotifier::class, $n[1]);

        $n = $cli->createNotifiers([2, 1], false);
        $this->assertEquals(1, count($n));
        $this->assertInstanceOf(ReminderNotifier::class, $n[0]);
        $this->assertEquals([2, 1], $n[0]->getDaysBefore());

        $n = $cli->createNotifiers([], false);
        $this->assertEquals(0, count($n));
    }

    function testProcessTask() {
        global $auth;
        $auth->createUser('user1', 'abcdefg', 'Some One', 'so@example.com');

        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $this->loadSchemaJSON('valid', '', 100);
        $cli = plugin_load('cli', 'structtasks');
        $this->assertTrue($cli->initialise());

        $page = 'some:page';
        $page_title = 'Some Title';
        $content = "====== ${page_title} ======\nInitial content";
        $data = ['duedate' => '2023-03-27',
                 'assignees' => ['user1'],
                 'status' => 'Ongoing'];
        $expected_data =[
            'duedate' => date_create($data['duedate']),
            'assignees' => ['Some One <so@example.com>'],
            'status' => $data['status'],
            'content' => $content,
            'duedate_formatted' => '27 Mar 2023',
        ];
        $access = AccessTable::getPageAccess('valid', $page, time());
        $access->saveData($data);
        saveWikiText($page, $content, 'save 1');

        $notifier = $this->createMock(TodayNotifier::class);
        $notifier->expects($this->once())
                 ->method('sendMessage')
                 ->with($this->equalTo($page),
                        $this->equalTo($page_title),
                        $this->equalTo(''),
                        $this->equalTo(''),
                        $this->equalTo($expected_data),
                        $this->equalTo($expected_data)
                 );

        $assignments = Assignments::getInstance();
        $assignments->assignPageSchema($page, 'valid');

        $cli->processTask($page, [$notifier]);
    }

    function testRunCli() {
        global $auth;
        $auth->createUser('user1', 'abcdefg', 'Some One', 'so@example.com');

        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $this->loadSchemaJSON('valid', '', 100);
        $cli = plugin_load('cli', 'structtasks');
        $this->assertTrue($cli->initialise());

        $assignments = Assignments::getInstance();
        $data = ['duedate' => '2023-03-27',
                 'assignees' => ['user1'],
                 'status' => 'Ongoing'];

        $page = [];
        $page_title = [];
        $content = [];
        $expected_data = [];

        $page[] = 'some:page';
        $page_title[] = 'Some Title';
        $content[] = "====== {$page_title[0]} ======\nInitial content";
        $expected_data[] =[
            'duedate' => date_create($data['duedate']),
            'assignees' => ['Some One <so@example.com>'],
            'status' => $data['status'],
            'content' => $content[0],
            'duedate_formatted' => '27 Mar 2023',
        ];
        $access = AccessTable::getPageAccess('valid', $page[0], time());
        $access->saveData($data);
        saveWikiText($page[0], $content[0], 'save 1');
        $assignments->assignPageSchema($page[0], 'valid');

        $page[] = 'another:page';
        $page_title[] = 'Another Title';
        $content[] = "====== {$page_title[1]} ======\nDifferent content";
        $expected_data[] =[
            'duedate' => date_create($data['duedate']),
            'assignees' => ['Some One <so@example.com>'],
            'status' => $data['status'],
            'content' => $content[1],
            'duedate_formatted' => '27 Mar 2023',
        ];
        $access = AccessTable::getPageAccess('valid', $page[1], time());
        $access->saveData($data);
        saveWikiText($page[1], $content[1], 'save 1');
        $assignments->assignPageSchema($page[1], 'valid');

        for ($i = 0; $i < 2; $i++) {
            $notifier = $this->createMock(TodayNotifier::class);
            $notifier->expects($this->exactly(2))
                     ->method('sendMessage')
                     ->withConsecutive([$this->equalTo($page[1]),
                                        $this->equalTo($page_title[1]),
                                        $this->equalTo(''),
                                        $this->equalTo(''),
                                        $this->equalTo($expected_data[1]),
                                        $this->equalTo($expected_data[1])],
                                       [$this->equalTo($page[0]),
                                        $this->equalTo($page_title[0]),
                                        $this->equalTo(''),
                                        $this->equalTo(''),
                                        $this->equalTo($expected_data[0]),
                                        $this->equalTo($expected_data[0])]
                     );
            $cli->notifiers[] = $notifier;
        }
        $cli->testing = true;

        $cli->notify(false);
    }
}
