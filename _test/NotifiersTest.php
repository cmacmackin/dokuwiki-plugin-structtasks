<?php

namespace dokuwiki\plugin\structtasks\test;

use DokuWikiTest;

use dokuwiki\plugin\structtasks\meta\AssignedNotifier;


/**
 * General tests for the structtasks plugin
 *
 * @group plugin_structtasks
 * @group plugins
 */

class notifiers_plugin_structtasks_test extends DokuWikiTest {
    // Test each notifier. For each one, have some people that were
    // added and removed in the edit and ensure they receive
    // appropriate updates. For most of these I can reuse the same
    // data. Probably just for testing open/close and deletion that I
    // need something different.

    // Use fake language function and conf function. Can double check
    // substitutions work correctly in subject. Use a mock Mailer.

    /** @var array alway enable the needed plugins */
    //protected $pluginsEnabled = array('structtasks', 'struct', 'sqlite');

    const subject = <<<'END'
Check substitutions:
@TITLE@
@TITLELINK@
@EDITURL@
@EDITOR@
@STATUS@
@PREVSTATUS@
@DUEDATE@
@PREVDUEDATE@
@WIKINAME@
END;
    private $expected_subject;
    private $text_replacements;
    private $html_replacements;
    const email_text = 'Text string to be sent as message body.';
    const email_html = 'Formatted HTML to be sent as message body.';
    const page_id = 'some:page_id';
    const page_title = 'Task Title';
    const editor = 'Some User <some.user@example.com>';
    const new_data = [
        'content' => <<<'END'
====== Task Title ======
Brief updated description of the task.
END,
        'duedate' => '2023/03/19',
        'assignees' => ['User One <user1@thing.com>', 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'Some User <some.user@example.com>'],
        'status' => 'Complete',
    ];
    const old_data = [
        'content' => <<<'END'
====== Old Title ======
Brief description of the task.
END,
        'duedate' => '2023/03/12',
        'assignees' => [ 'User 2 <u2@abc.com>', 'Third Guy <guy3@abc.com>', 'User Four <u4@thingy.co.uk', 'Some User <some.user@example.com>'],
        'status' => 'Ongoing',
    ];

    const defaultSettings = [
        'schema' => '',
        'reminder' => array('1', '0'),
        'overdue_reminder' => 1,
        'completed' => '/^(completed?|closed|cancelled|finished)$/'
    ];

    public function initialize() {
        $url = DOKU_URL . DOKU_SCRIPT . '?id=' . $this::page_id;
        $this->text_replacements = [
            'TITLE' => $this::page_title,
            'TITLELINK' => $this::page_title . " <${url}>",
            'EDITURL' => "${url}&do=edit",
            'EDITOR' => $this::editor,
            'STATUS' => $this::new_data['status'],
            'PREVSTATUS' => $this::old_data['status'],
            'DUEDATE' => $this::new_data['duedate'],
            'PREVDUEDATE' => $this::old_data['duedate'],
            'WIKINAME' => 'My Test Wiki',
        ];
        $this->html_replacements = [
            'TITLELINK' => "<a href=\"${url}\">" . $this::page_title . '</a>'
        ];
        $this->expected_subject = <<<END
Check substitutions:
{$this->text_replacements['TITLE']}
{$this->text_replacements['TITLELINK']}
{$this->text_replacements['EDITURL']}
{$this->text_replacements['EDITOR']}
{$this->text_replacements['STATUS']}
{$this->text_replacements['PREVSTATUS']}
{$this->text_replacements['DUEDATE']}
{$this->text_replacements['PREVDUEDATE']}
{$this->text_replacements['WIKINAME']}
END;
    }

    private function makeMockMailer($recipients) {
        $calls = count($recipients);
        $mailer = $this->createMock(\Mailer::class);
        $mailer->expects($this->exactly($calls))
               ->method('to')
               ->withConsecutive(...array_chunk(array_map([$this, 'equalTo'], $recipients), 1));
        $mailer->expects($this->exactly($calls))
               ->method('subject')
               ->with($this->equalTo($this->expected_subject));
        $mailer->expects($this->once())
               ->method('setBody')
               ->with($this->equalTo($this::email_text),
                      $this->equalTo($this->text_replacements),
                      $this->equalTo($this->html_replacements),
                      $this->equalTo($this::email_html));
        $mailer->expects($this->exactly($calls))->method('send')->with();
        return $mailer;
    }

    static function fakeGetConf($key) {
        return defaultSettings[$key];
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
        $this->initialize();
        $assigned_mock_mailer = $this->makeMockMailer([$this::new_data['assignees'][0]]);
        return [
            [AssignedNotifier::class, $assigned_mock_mailer],
        ];
    }

    /**
     * @dataProvider provideNotifiers
     */
    public function testNotifiers($notifier, $mailer) {
        $n = new $notifier([$this, 'fakeGetConf'], $this->getLangCallback('assigned'));
        $n->sendMessage(
            $this::page_id,
            $this::page_title,
            $this::editor,
            $this::new_data,
            $this::old_data,
            $mailer
        );
    }

}
