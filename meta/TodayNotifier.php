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
 * Notifies assignees that a task is due today
 *
 * @package dokuwiki\plugin\structtasks\meta
 */
class TodayNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'today';

    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        if (is_null($new_data['duedate'])) return [];
        $time_remaining = $this->timeFromLastMidnight($new_data['duedate']);
        $days = $time_remaining[0] * 365 + $time_remaining[1] * 31 + $time_remaining[2];
        if ($days == 0) {
            return $new_data['assignees'];
        }
        return [];
    }
}
