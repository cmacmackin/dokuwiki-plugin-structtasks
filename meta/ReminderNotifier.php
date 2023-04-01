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
 * Notifies assignees before a task comes due
 *
 * @package dokuwiki\plugin\structtasks\meta
 */
class ReminderNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'reminder';

    private $days_before;

    /**
     * Constructor allows you to specify how many days before the
     * deadline the reminder should be sent.
     */
    public function __construct(callable $getConf, callable $getLang,
                                int $days_before = 1) {
        parent::__construct($getConf, $getLang);
        $this->days_before = $days_before;
    }

    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        // FIXME: if $days_before is more than one month then this
        // won't be very accurate
        $time_remaining = $this->timeFromLastMidnight($new_data['duedate']);
        $days = $time_remaining[0] * 365 + $time_remaining[1] * 31 + $time_remaining[2];
        if ($days == $this->days_before) {
            return $new_data['assignees'];
        }
        return [];
    }
}
