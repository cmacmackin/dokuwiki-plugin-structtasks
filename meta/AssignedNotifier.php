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
class AssignedNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'assigned';
    public function getNotifiableUsers($page, $editor, $new_data, $old_data) {
        return array_filter(
            array_diff($new_data['assignees'], $old_data['assignees']),
            function ($val) use ($editor) {return $val !== $editor;}
        );
    }
}
