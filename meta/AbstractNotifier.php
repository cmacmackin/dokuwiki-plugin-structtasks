<?php
/**
 * DokuWiki Plugin structtasks
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 *
 */

namespace dokuwiki\plugin\structtasks\meta;

/**
 * Abstract base class to handle sending emails about changes to task
 * state. Each subclass will provide a function which returns a list
 * of users to be notified (empty if no notification is required) and
 * template text for the email message.
 *
 * @package dokuwiki\plugin\structtasks\meta
 *
 */
abstract class AbstractNotifier
{
    /**
     * First part of keys in the localisation files, corresponding to
     * content of emails. The following keys are expected to be
     * present: `PREFIX_subject` (email subject), `PREFIX_text`
     * (plain-text email content), and `PREFIX_html` (HTML email
     * content).
     *
     * The text of these localisations can make use of the following
     * macros:
     *
     *   - @TITLE@
     *   - @TITLELINK@
     *   - @EDITURL@
     *   - @EDITOR@
     *   - @STATUS@
     *   - @PREVSTATUS@
     *   - @DUEDATE@
     *   - @PREVDUEDATE@
     *   - @WIKINAME@
     *
     */
    const lang_key_prefix = 'DUMMY';

    /**
     * Callable to get configurations for this plugin.
     */
    protected $getConf;
    /**
     * Callable to get text in current language.
     */
    protected $getLang;

    public function __construct(callable $getConf, callable $getLang) {
        $this->getConf = $getConf;
        $this->getLang = $getLang;
    }

    abstract function getNotifiableUsers($page, $editor, $new_data, $old_data);

    public function sendMessage($page_id, $page_title, $editor, $new_data,
                                $old_data, $mailer = new Mailer()) {
        $notifiable_users = $this->getNotifiableUsers($page_id, $editor, $new_data, $old_data);
        if (count($notifiable_users) == 0) return;
        global $conf;
        $getLang = $this->getLang;
        $url = wl($page_id, [], true);
        $text_subs = [
            'TITLE' => $page_title,
            'TITLELINK' => "${page_title} <${url}>",
            'EDITURL' => wl($page_id, ['do' => 'edit'], true, '&'),
            'EDITOR' => $editor,
            'STATUS' => $new_data['status'],
            'PREVSTATUS' => $old_data['status'],
            'DUEDATE' => $new_data['duedate'],
            'PREVDUEDATE' => $old_data['duedate'],
            'WIKINAME' => $conf['title'],
        ];
        $html_subs = ['TITLELINK' => "<a href=\"${url}\">${page_title}</a>"];
        $subject = str_replace(
            array_map(function ($x) {return "@$x@";}, array_keys($text_subs)),
            $text_subs,
            $getLang($this::lang_key_prefix . '_subject'));
        $mailer->setBody($getLang($this::lang_key_prefix . '_text'),
                         $text_subs, $html_subs,
                         $getLang($this::lang_key_prefix . '_html'));
        foreach ($notifiable_users as $user) {
            $mailer->to($user);
            $mailer->subject($subject);
            $mailer->send();
        }
    }
}
