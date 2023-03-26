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
class SelfRemovalNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'self_removal';
    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        if (in_array($editor_email, $old_data['assignees']) and
            !in_array($editor_email, $new_data['assignees'])) {
            return array_filter(
                $new_data['assignees'],
                function ($val) use ($editor_email) {return $val !== $editor_email;}
            );
        } else {
            return [];
        }
    }
}
