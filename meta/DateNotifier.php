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
class DateNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'date';
    public function getNotifiableUsers($page, $editor_email, $new_data, $old_data) {
        // Don't send emails for newly-created pages
        if ($old_data['content'] === '' and $new_data['content'] !== '') return [];
        if ($new_data['duedate'] == $old_data['duedate']) return [];
        return array_filter(
            $new_data['assignees'],
            function ($val) use ($editor_email) {return $val !== $editor_email;}
        );
    }
}
