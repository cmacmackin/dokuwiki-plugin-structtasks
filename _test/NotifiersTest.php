<?php

namespace dokuwiki\plugin\structtasks\test;

use DateInterval;
use DokuWikiTest;

use dokuwiki\plugin\structtasks\meta\AssignedNotifier;
use dokuwiki\plugin\structtasks\meta\ClosedStatusNotifier;
use dokuwiki\plugin\structtasks\meta\DateNotifier;
use dokuwiki\plugin\structtasks\meta\DeletedNotifier;
use dokuwiki\plugin\structtasks\meta\OpenStatusNotifier;
use dokuwiki\plugin\structtasks\meta\RemovedNotifier;
use dokuwiki\plugin\structtasks\meta\ReminderNotifier;
use dokuwiki\plugin\structtasks\meta\SelfRemovalNotifier;
use dokuwiki\plugin\structtasks\meta\TodayNotifier;
use dokuwiki\plugin\structtasks\meta\OverdueNotifier;


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
@DUEIN@
@WIKINAME@';
    private $expected_subject;
    private $text_replacements;
    private $html_replacements;
    const email_text = 'Text string to be sent as message body.';
    const email_html = 'Formatted HTML to be sent as message body.';
    const page_id = 'some:page_id';
    const page_title = 'Task Title';
    const editor = 'Some User';
    const editor_email = 'Some User <some.user@example.com>';
    private $new_data;

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

    public function provideDateIntervals() {
        return [
            [date_create()->add(new DateInterval('P2D')), '2 days'],
            [date_create()->sub(new DateInterval('P2D')), '2 days'],
            [date_create()->add(new DateInterval('P1D')), '1 day'],
            [date_create()->add(new DateInterval('P1Y20D')), '1 year and 20 days'],
            [date_create()->add(new DateInterval('P2M0D')), '2 months'],
            [date_create()->add(new DateInterval('P2Y2M2D')), '2 years, 2 months, and 2 days'],
            [date_create()->add(new DateInterval('P10Y1M')), '10 years and 1 month'],
        ];
    }

    /**
     * @dataProvider provideDateIntervals
     */
    function testDueIn($date, $expected) {
        $notifier = new AssignedNotifier(
            [$this, 'fakeGetConf'], $this->getLangCallback('assigned'));
        $this->assertEquals($expected, $notifier->dueIn($date));
    }

    public function provideNotifiers() {
        $date1 = date_create('2023-03-19');
        $date1->setTime(0, 0);
        $date2 = date_create('2023-03-12 12:00');
        $new_data = [
            'content' => '====== Task Title ======
Brief updated description of the task.',
            'duedate' => $date1,
            'duedate_formatted' => '19 Mar 2023',
            'assignees' => ['User One <user1@thing.com>', 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'Some User <some.user@example.com>'],
            'status' => 'Complete',
        ];
        $old_data = [
            'content' => '====== Old Title ======
Brief description of the task.',
            'duedate' => $date2,
            'duedate_formatted' => '12 March 2023 12:00',
            'assignees' => [ 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'User Four <u4@thingy.co.uk', 'Some User <some.user@example.com>'],
            'status' => 'Ongoing',
        ];
        $new_data2 = array_replace($new_data, ['duedate' => clone $new_data['duedate']]);
        $old_data2 = array_replace($old_data, ['duedate' => clone $old_data['duedate']]);
        $all_but_editor = array_slice($new_data['assignees'], 0, -1);
        $empty_data = ['content' => '', 'duedate' => date_create(''),
                       'duedate_formatted' => '', 'assignees' => [], 'status' => ''];
        $today = date_create();
        $yesterday = date_create()->sub(new DateInterval('P1D'));
        $tomorrow = date_create()->add(new DateInterval('P1D'));

        return [
            'AssignedNotifier' => [
                AssignedNotifier::class, [$new_data['assignees'][0]],
                $new_data, $old_data, 'assigned'
            ],
            'RemovedNotifier' => [
                RemovedNotifier::class, [$old_data['assignees'][2]],
                $new_data, $old_data, 'removed'
            ],
            'DateNotifier' => [
                DateNotifier::class, $all_but_editor, $new_data,
                $old_data, 'date'
            ],
            'OpenStatusNotifier' => [
                OpenStatusNotifier::class, $all_but_editor,
                array_replace($new_data, ['status' => 'Ongoing']),
                array_replace($old_data, ['status' => 'Completed']),
                'openstatus'
            ],
            'ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, $all_but_editor, $new_data,
                $old_data, 'closedstatus'
            ],
            'SelfRemovalNotifier' => [
                SelfRemovalNotifier::class, $all_but_editor,
                array_replace($new_data, ['assignees' => $all_but_editor]),
                $old_data, 'self_removal'
            ],
            'DeletedNotifier' => [
                DeletedNotifier::class,
                array_slice($old_data['assignees'], 0, -1), $empty_data,
                $old_data, 'deleted'
            ],
            'Not AssignedNotifier' => [
                AssignedNotifier::class, [], $new_data,
                $new_data2, 'assigned'
            ],
            'Not RemovedNotifier' => [
                RemovedNotifier::class, [], $new_data,
                $new_data2, 'removed'
            ],
            'Not DateNotifier' => [
                DateNotifier::class, [], $new_data,
                $new_data2, 'date'
            ],
            'Not ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, [], $new_data,
                $new_data2, 'closedstatus'
            ],
            'Not OpenStatusNotifier' => [
                OpenStatusNotifier::class, [], $old_data,
                $old_data2, 'openstatus'
            ],
            'Not SelfRemovalNotifier' => [
                SelfRemovalNotifier::class, [], $old_data,
                $new_data, 'self_removal'
            ],
            'Not SelfRemovalNotifier 2' => [
                SelfRemovalNotifier::class, [],
                array_replace($old_data, ['assignees' => $all_but_editor]),
                array_replace($new_data, ['assignees' => $all_but_editor]),
                'self_removal'
            ],
            'Not DeletedNotifier' => [
                DeletedNotifier::class, [], $new_data,
                $old_data, 'deleted'
            ],
            'Not DeletedNotifier 2' => [
                DeletedNotifier::class, [], $empty_data, $new_data, 'deleted'
            ],
            'New page AssignedNotifier' => [
                AssignedNotifier::class, $all_but_editor,
                $new_data, $empty_data, 'assigned'
            ],
            'New page DateNotifier' => [
                DateNotifier::class, [], $new_data,
                $empty_data, 'date'
            ],
            'New page ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, [], $new_data,
                $empty_data, 'closedstatus'
            ],
            'New page OpenStatusNotifier' => [
                OpenStatusNotifier::class, [], $old_data,
                $empty_data, 'openstatus'
            ],
            'Delete page RemovedNotifier' => [
                RemovedNotifier::class, [],
                $empty_data, $new_data, 'removed'
            ],
            'Delete page SelfRemovalNotifier' => [
                SelfRemovalNotifier::class, [],
                $empty_data, $old_data, 'selfremoval'
            ],
            'Delete page DateNotifier' => [
                DateNotifier::class, [], $empty_data, $new_data,
                'date'
            ],
            'Delete page ClosedStatusNotifier' => [
                ClosedStatusNotifier::class, [], $empty_data, $old_data,
                'closedstatus'
            ],
            'Delete page OpenStatusNotifier' => [
                OpenStatusNotifier::class, [], $empty_data, $new_data,
                'openstatus'
            ],
            'ReminderNotifier' => [
                ReminderNotifier::class, $old_data['assignees'],
                array_replace($old_data, ['duedate' => $tomorrow]),
                array_replace($old_data, ['duedate' => $tomorrow]),
                'reminder'
            ],
            'Closed ReminderNotifier' => [
                ReminderNotifier::class, [],
                array_replace($new_data, ['duedate' => $tomorrow]),
                array_replace($new_data, ['duedate' => $tomorrow]),
                'reminder'
            ],
            'Not ReminderNotifier' => [
                ReminderNotifier::class, [], $old_data, $old_data,
                'reminder'
            ],
            'No Date ReminderNotifier' => [
                ReminderNotifier::class, [],
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                'reminder'
            ],
            'TodayNotifier' => [
                TodayNotifier::class, $old_data['assignees'],
                array_replace($old_data, ['duedate' => $today]),
                array_replace($old_data, ['duedate' => clone $today]),
                'today'
            ],            
            'Closed TodayNotifier' => [
                TodayNotifier::class, [],
                array_replace($new_data, ['duedate' => $today]),
                array_replace($new_data, ['duedate' => $today]),
                'today'
            ],            
            'Not TodayNotifier' => [
                TodayNotifier::class, [], $old_data, $old_data, 'today'
            ],
            'No Date TodayNotifier' => [
                TodayNotifier::class, [],
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                'today'
            ],
            'OverdueNotifier' => [
                OverdueNotifier::class, $old_data['assignees'], $old_data,
                $old_data2, 'overdue'
            ],
            'Closed OverdueNotifier' => [
                OverdueNotifier::class, [], $new_data, $new_data, 'overdue'
            ],
            'Not OverdueNotifier' => [
                OverdueNotifier::class, [],
                array_replace($old_data, ['duedate' => $tomorrow]),
                array_replace($old_data, ['duedate' => $tomorrow]),
                'overdue'
            ],
            'No Date OverdueNotifier' => [
                OverdueNotifier::class, [],
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                array_replace($old_data, ['duedate' => null, 'duedate_formatted' => '']),
                'overdue'
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
                'TITLELINK' => '"' . $this::page_title . "\" <${url}>",
                'EDITURL' => "${url}&do=edit",
                'EDITOR' => $this::editor,
                'STATUS' => $new_data['status'],
                'PREVSTATUS' => $old_data['status'],
                'DUEDATE' => $new_data['duedate_formatted'],
                'PREVDUEDATE' => $old_data['duedate_formatted'],
                'DUEIN' => $notifier::dueIn($new_data['duedate']),
                'WIKINAME' => 'My Test Wiki',
            ];
            $html_replacements = [
                'TITLELINK' => "&ldquo;<a href=\"${url}\">" . $this::page_title . '</a>&rdquo;',
                'EDITURL' => "<a href=\"${url}&do=edit\">edit the page</a>",
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
            $this::editor_email,
            $new_data,
            $old_data,
            $mailer
        );
    }

}
