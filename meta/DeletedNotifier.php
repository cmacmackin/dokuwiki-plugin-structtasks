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
    public function getNotifiableUsers($page, $editor, $new_data, $old_data) {
        $getConf = $this->getConf;
        $completed_pattern = $getConf('completed');
        $old_closed = preg_match($completed_pattern, $old_data['status']);
        echo($old_closed);
        if (!$old_closed and $new_data['content'] == '') {
            return array_filter(
                $old_data['assignees'],
                function ($val) use ($editor) {return $val !== $editor;}
            );
        } else {
            return [];
        }
    }
}
