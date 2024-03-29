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
    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        // Handle case of page creation; struct ends up returning
        // identical metadata in that case, so need to over-ride it
        if ($old_data['content'] == '') $old_data['assignees'] = [];
        return array_filter(
            array_diff($new_data['assignees'], $old_data['assignees']),
            function ($val) use ($editor_email) {return $val !== $editor_email;}
        );
    }
}
