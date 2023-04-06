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
 * Notifies a user when they are removed from a task.
 *
 * @package dokuwiki\plugin\structtasks\meta
 */
class RemovedNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'removed';
    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        if ($new_data['content'] === '') return [];
        return array_filter(
            array_diff($old_data['assignees'], $new_data['assignees']),
            function ($val) use ($editor_email) {return $val !== $editor_email;}
        );
    }
}
