<?php

namespace dokuwiki\plugin\structtasks\test;

use DokuWikiTest;

use dokuwiki\plugin\structtasks\meta\AssignedNotifier;
use dokuwiki\plugin\structtasks\meta\ClosedStatusNotifier;
use dokuwiki\plugin\structtasks\meta\DateNotifier;
use dokuwiki\plugin\structtasks\meta\DeletedNotifier;
use dokuwiki\plugin\structtasks\meta\OpenStatusNotifier;
use dokuwiki\plugin\structtasks\meta\RemovedNotifier;
use dokuwiki\plugin\structtasks\meta\SelfRemovalNotifier;


/**
 * Tests of the "notifier" classes for the structtasks plugin. These
 * examin the changes that have been made and will send out emails
 * accordingly. Here, the mailer class is mocked so we can check it is
 * being called correctly.
 *
 * @group plugin_structtasks
 * @group plugins
 */

class notifiers_plugin_structtasks_test extends DokuWikiTest {

    const subject = 'Check substitutions:
@TITLE@
@TITLELINK@
@EDITURL@
@EDITOR@
@STATUS@
@PREVSTATUS@
@DUEDATE@
@PREVDUEDATE@
@WIKINAME@';
    private $expected_subject;
    private $text_replacements;
    private $html_replacements;
    const email_text = 'Text string to be sent as message body.';
    const email_html = 'Formatted HTML to be sent as message body.';
    const page_id = 'some:page_id';
    const page_title = 'Task Title';
    const editor = 'Some User <some.user@example.com>';
    const new_data = [
        'content' => '====== Task Title ======
Brief updated description of the task.',
        'duedate' => '2023/03/19',
        'assignees' => ['User One <user1@thing.com>', 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'Some User <some.user@example.com>'],
        'status' => 'Complete',
    ];
    const old_data = [
        'content' => '====== Old Title ======
Brief description of the task.',
        'duedate' => '2023/03/12',
        'assignees' => [ 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'User Four <u4@thingy.co.uk', 'Some User <some.user@example.com>'],
        'status' => 'Ongoing',
    ];

    const defaultSettings = [
        'schema' => '',
        'reminder' => array('1', '0'),
        'overdue_reminder' => 1,
        'completed' => '/^(completed?|closed|cancelled|finished)$/i'
    ];

    static function fakeGetConf($key) {
        return self::defaultSettings[$key];
    }
    
    private function getLangCallback($expected_key) {
        return function ($key) use ($expected_key){
            if ($key == $expected_key . '_subject') {
                return $this::subject;
            }
            else if ($key == $expected_key . '_text') {
                return $this::email_text;
            }
            else if ($key == $expected_key . '_html') {
                return $this::email_html;
            }
            else {
                throw new \Exception("Unexpected argument: ${key}");
            }
        };
    }

    public function provideNotifiers() {
        $all_but_editor = array_slice($this::new_data['assignees'], 0, -1);

        return [
            'AssignedNotifier' => [
                AssignedNotifier::class, [$this::new_data['assignees'][0]],
                $this::new_data, $this::old_data, 'assigned'
            ],
            'RemovedNotifier' => [
                RemovedNotifier::class, [$this::old_data['assignees'][2]],
                $this::new_data, $this::old_data, 'removed'
            ],
            'DateNotifier' => [
                DateNotifier::class, $all_but_editor, $this::new_data,
                $this::old_data, 'date'
            ],
            'OpenStatusNotifier' => [
                OpenStatusNotifier::class, $all_but_editor,
                array_replace($this::new_data, ['status' => 'Ongoing']),
                array_replace($this::old_data, ['status' => 'Completed']),
                'openstatus'
            ],
            'ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, $all_but_editor, $this::new_data,
                $this::old_data, 'closedstatus'
            ],
            'SelfRemovalNotifier' => [
                SelfRemovalNotifier::class, $all_but_editor,
                array_replace($this::new_data, ['assignees' => $all_but_editor]),
                $this::old_data, 'self_removal'
            ],
            'DeletedNotifier' => [
                DeletedNotifier::class,
                array_slice($this::old_data['assignees'], 0, -1),
                ['content' => '', 'duedate' => '', 'assignees' => [], 'status' => ''],
                $this::old_data,
                'deleted'
            ],
            'Not AssignedNotifier' => [
                AssignedNotifier::class, [], $this::new_data,
                $this::new_data, 'assigned'
            ],
            'Not RemovedNotifier' => [
                RemovedNotifier::class, [], $this::new_data,
                $this::new_data, 'removed'
            ],
            'Not DateNotifier' => [
                DateNotifier::class, [], $this::new_data,
                $this::new_data, 'date'
            ],
            'Not ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, [], $this::new_data,
                $this::new_data, 'closedstatus'
            ],
            'Not OpenStatusNotifier' => [
                OpenStatusNotifier::class, [], $this::old_data,
                $this::old_data, 'openstatus'
            ],
            'Not SelfRemovalNotifier' => [
                SelfRemovalNotifier::class, [], $this::old_data,
                $this::new_data, 'self_removal'
            ],
            'Not SelfRemovalNotifier 2' => [
                SelfRemovalNotifier::class, [],
                array_replace($this::old_data, ['assignees' => $all_but_editor]),
                array_replace($this::new_data, ['assignees' => $all_but_editor]),
                'self_removal'
            ],
            'Not DeletedNotifier' => [
                SelfRemovalNotifier::class, [], $this::new_data,
                $this::old_data, 'deleted'
            ],
            'Not DeletedNotifier 2' => [
                DeletedNotifier::class, [],
                ['content' => '', 'duedate' => '', 'assignees' => [],
                 'status' => ''],
                $this::new_data,
                'deleted'
            ],
        ];
    }

    /**
     * @dataProvider provideNotifiers
     */
    public function testNotifiers($notifier, $recipients, $new_data, $old_data, $key) {
        $calls = count($recipients);
        $mailer = $this->createMock(\Mailer::class);
        $mailer->expects($this->exactly($calls))
               ->method('to')
               ->withConsecutive(...array_chunk(array_map([$this, 'equalTo'], $recipients), 1));
        if ($calls > 0) {
            $url = DOKU_URL . DOKU_SCRIPT . '?id=' . $this::page_id;
            $text_replacements = [
                'TITLE' => $this::page_title,
                'TITLELINK' => $this::page_title . " <${url}>",
                'EDITURL' => "${url}&do=edit",
                'EDITOR' => $this::editor,
                'STATUS' => $new_data['status'],
                'PREVSTATUS' => $old_data['status'],
                'DUEDATE' => $new_data['duedate'],
                'PREVDUEDATE' => $old_data['duedate'],
                'WIKINAME' => 'My Test Wiki',
            ];
            $html_replacements = [
                'TITLELINK' => "<a href=\"${url}\">" . $this::page_title . '</a>'
            ];
            $expected_subject = "Check substitutions:";
            foreach($text_replacements as $t) {
                $expected_subject .= "\n$t";
            }
            $mailer->expects($this->once())
                   ->method('setBody')
                   ->with($this->equalTo($this::email_text),
                          $this->equalTo($text_replacements),
                          $this->equalTo($html_replacements),
                          $this->equalTo($this::email_html));
            $mailer->expects($this->exactly($calls))
                   ->method('subject')
                   ->with($this->equalTo($expected_subject));
        } else {
            $mailer->expects($this->never())
                   ->method('setBody');
            $mailer->expects($this->never())
                   ->method('subject');
        }
        $mailer->expects($this->exactly($calls))->method('send')->with();
        $n = new $notifier([$this, 'fakeGetConf'], $this->getLangCallback($key));
        $n->sendMessage(
            $this::page_id,
            $this::page_title,
            $this::editor,
            $new_data,
            $old_data,
            $mailer
        );
    }

}
