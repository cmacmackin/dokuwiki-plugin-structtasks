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
 * Notifies a user when they are assigned to a task.
 *
 * @package dokuwiki\plugin\structtasks\meta
 */
class ClosedStatusNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'closedstatus';
    public function getNotifiableUsers($page, $editor, $new_data, $old_data) {
        // Don't send emails for newly-created pages
        if ($old_data['content'] === '' and $new_data['content'] !== '') return [];
        $getConf = $this->getConf;
        $completed_pattern = $getConf('completed');
        $new_closed = preg_match($completed_pattern, $new_data['status']);
        $old_closed = preg_match($completed_pattern, $old_data['status']);
        if (!$new_closed or $old_closed) return [];
        return array_filter(
            $new_data['assignees'],
            function ($val) use ($editor) {return $val !== $editor;}
        );
    }
}
