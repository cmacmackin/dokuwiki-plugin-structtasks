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
     *   - @DUEIN@
     *
     * Note: DUEIN is the number of days before the task is due or the
     * number of days elapsed since the due-date.
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

    abstract function getNotifiableUsers($page, $editor_email, $new_data, $old_data);

    static protected function timeFromLastMidnight($date) {
        $today = date_create();
        $today->setTime(0, 0);
        $date->setTime(0, 0);
        // For some reason, diff always seems to return absolute
        // value, so just handling that manually here
        $diff = $date->diff($today, true);
        $factor = ($today < $date) ? 1 : -1;
        return [$factor * $diff->y, $factor * $diff->m, $factor * $diff->d];
    }
    
    /**
     * Works out how many days until the due-date (or since the
     * due-date, as appropriate) and returns it in a nicely-formatted
     * string.
     */
    static function dueIn($duedate) {
        if (is_null($duedate)) {
            return '';
        }
        list($y, $m, $d) = array_map('abs', self::timeFromLastMidnight($duedate));
        $components = [];
        if ($y != 0) {
            $val = "{$y} year";
            if ($y > 1) $val .= 's';
            $components[] = $val;
        }
        if ($m != 0) {
            $val = "{$m} month";
            if ($m > 1) $val .= 's';
            $components[] = $val;
        }
        if ($d != 0) {
            $val = "{$d} day";
            if ($d > 1) $val .= 's';
            $components[] = $val;
        }
        switch (count($components)) {
        case 0:
            return '0 days';
        case 1:
            return $components[0];
        case 2:
            return $components[0] . ' and ' . $components[1];
        case 3:
            return $components[0] . ', ' . $components[1] . ', and ' . $components[2];
        default:
            throw new Exception("Invalid number of date components");
        }
    }

    /**
     * Returns true if the regular expression for closed tasks matches
     * $status.
     */
    function isCompleted($status) {
        $getConf = $this->getConf;
        $completed_pattern = $getConf('completed');
        return preg_match($completed_pattern, $status);
    }
    
    /**
     * (Possibly) send a message for revisions to the given page, if
     * necessary. $old_data and $new_data are associative arrays with
     * the following keys:
     *
     *    - content: The page content.
     *    - duedate: A DateTime object specifying when the task is
     *      due. If no date was specified for this page, will be NULL. 
     *    - duedate_formatted: A string with the due-date formatted
     *      according to the struct schema. Empty if no date specified.
     *    - assignees: An array of email addresses for the people this
     *      task has been assigned to.
     *    - status: The completion status of the task.
     */
    public function sendMessage($page_id, $page_title, $editor, $editor_email, $new_data,
                                $old_data, $mailer = NULL) {
        if (is_null($mailer)) $mailer = new \Mailer();
        $notifiable_users = $this->getNotifiableUsers($page_id, $editor_email, $new_data, $old_data);
        if (count($notifiable_users) == 0) return;
        global $conf;
        $getLang = $this->getLang;
        $url = wl($page_id, [], true);
        $text_subs = [
            'TITLE' => $page_title,
            'TITLELINK' => "\"${page_title}\" <${url}>",
            'EDITURL' => wl($page_id, ['do' => 'edit'], true, '&'),
            'EDITOR' => $editor,
            'STATUS' => $new_data['status'],
            'PREVSTATUS' => $old_data['status'],
            'DUEDATE' => $new_data['duedate_formatted'],
            'PREVDUEDATE' => $old_data['duedate_formatted'],
            'WIKINAME' => $conf['title'],
            'DUEIN' => $this->dueIn($new_data['duedate']),
        ];
        $html_subs = [
            'TITLELINK' => "&ldquo;<a href=\"${url}\">${page_title}</a>&rdquo;",
            'EDITURL' => "<a href=\"{$text_subs['EDITURL']}\">edit the page</a>"
        ];
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
