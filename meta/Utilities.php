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
 * Class with various useful static methods. 
 */
class Utilities
{
    public function __constructor() {
        $this->struct = $this->loadHelper('struct', true);
    }

    
    /**
     * Tests whether the specified schema meets the requirements for
     * describing tasks.
     */
    function isValidSchema($schema) {
        
    }
    
    /**
     * Safely fetches the old and new struct metadata for this
     * event. Also returns a flag indicating if this page is even a
     * valid task for which notifications could be sent.
     */
    function getMetadata($id, $old_rev, $new_rev) {
    }
    
    /**
     * Return a string with the real name and email address of $user,
     * suitable for using to send them an email.
     */
    function getUserEmail($user) {
        global $auth;
        $userData = $auth->getUserData($user, false);
        if ($userData === false) {
            return false;
        }
        $realname = $userData['name'];
        $email = $userData['mail'];
        if ($realname === '') return $email;
        if (strpos($userData['name'], ',')) $realname = '"' . $realname;
        return "${realname} <${email}>";
    }

    /**
     * Convert a list of usernames into a list of formatted email
     * addresses.
     */
    function assigneesToEmails($assignees) {
        return array_filter(array_map([$this, 'getUserEmail'], $assignees), strlen);
    }

    /**
     * Creates the $old_data array to be passed to
     * AbstractNotifier::sendMessage
     */
    function getOldData($eventdata, $structdata) {
    }

    /**
     * Creates the $new_data array to be passed to
     * AbstractNotifier::sendMessage
     */
    function getNewData($eventdata, $structdata) {
        $newMetaData = $this->struct->getData($event->id, $this->getConf('schema'), $event->newRevision);
    }

}
