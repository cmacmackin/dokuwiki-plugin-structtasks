<?php
/**
 * DokuWiki Plugin structtasks
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 *
 */

namespace dokuwiki\plugin\structtasks\meta;

use dokuwiki\plugin\struct\types\Date;
use dokuwiki\plugin\struct\types\DateTime;
use dokuwiki\plugin\struct\types\User;
use dokuwiki\plugin\struct\types\Mail;
use dokuwiki\plugin\struct\types\Dropdown;
use dokuwiki\plugin\struct\types\Text;

/**
 * Class with various useful static methods. 
 */
class Utilities
{
    protected $struct;

    /**
     *  Pass in an instance of the struct_helper plugin when building this class;
     */
    public function __construct($helper) {
        $this->struct = $helper;
    }

    
    /**
     * Tests whether the specified schema meets the requirements for
     * describing tasks.
     */
    function isValidSchema($schema) {
        $schemas_found = $this->struct->getSchema($schema);
        $s = $schemas_found[$schema];
        if ($s->getTimeStamp() == 0) {
            msg("structtasks schema '${schema}' not found.", -1);
            return false;
        }
        //var_dump($schemas_found);
        $col_names = ['duedate', 'assignees', 'status'];
        $col_types = [
            'duedate' => [Date::class, DateTime::class],
            'assignees' => [User::class, Mail::class],
            'status' => [DropDown::class, Text::class]
        ];
        $columns = [];
        $valid = true;
        foreach ($col_types as $name => $types) {
            $col = $s->findColumn($name);
            if ($col === false) {
                msg("structtasks schema '$schema' has no column '$name'.");
                $valid = false;
            } else {
                $coltype = $col->getType()::class;
                if (!in_array($coltype, $types)) {
                    msg("Column '${name}' of structtasks schema '$schema' has invalid type ${coltype}");
                    $valid = false;
                }
            }
        }
        return $valid;
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
