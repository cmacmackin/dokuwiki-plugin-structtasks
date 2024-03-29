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
class DeletedNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'deleted';
    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        $old_closed = $this->isCompleted($old_data['status']);
        if (!$old_closed and $new_data['content'] == '') {
            return array_filter(
                $old_data['assignees'],
                function ($val) use ($editor_email) {return $val !== $editor_email;}
            );
        } else {
            return [];
        }
    }
}
