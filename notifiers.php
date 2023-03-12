<?php
/**
 * DokuWiki Plugin structtasks
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 *
 */

/**
 * Abstract base class to handle sending emails about changes to task
 * state. Each subclass will provide a function which returns a list
 * of users to be notified (empty if no notification is required) and
 * template text for the email message.
 *
 */
abstract class AbstractNotifier
{
    /**
     * First part of keys in the localisation files, corresponding to
     * content of emails. The following keys are expected to be
     * present: `PREFIX_subject` (email subject), `PREFIX_text`
     * (plain-text email content), and `PREFIX_html` (HTML email
     * content).
     *
     * The text of these localisations can make use of the following
     * macros:
     *
     *   - @TITLE@
     *   - @TITLELINK@
     *   - @EDITURL@
     *   - @EDITOR@
     *   - @STATUS@
     *   - @PREVSTATUS@
     *   - @DUEDATE@
     *   - @PREVDUEDATE@
     *   - @WIKINAME@
     *
     */
    const lank_key_prefix = 'DUMMY';

    /**
     * Callable to get configurations for this plugin.
     */
    protected $getConf;
    /**
     * Callable to get text in current language.
     */
    protected $getLang;

    public function __constructor(callable $getConf, callable $getLang) {
        $this->getConf = $getConf;
        $this->getLang = $getLang;
    }

    abstract function getNotifiableUsers($page, $editor, $new_data, $old_data);
    public function sendMessage($page, $editor, $new_data, $old_data, $edit_message) {
    }
}


/**
 * Notifies a user when they are assigned to a task.
 */
class AssignedNotifier extends AbstractNotifier
{
    const lang_key_prefix = 'assigned';
    public function getNotifiableUsers($page, $editor, $new_data, $old_data) {
    }
}
