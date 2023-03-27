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
        if ($schema == '') return false;
        $schemas_found = $this->struct->getSchema($schema);
        $s = $schemas_found[$schema];
        if ($s->getTimeStamp() == 0) {
            msg("Schema '${schema}', needed for structtasks, does not exist.", -1);
            return false;
        }
        $col_names = ['duedate', 'assignees', 'status'];
        $col_types = [
            'duedate' => [Date::class, DateTime::class],
            'assignees' => [User::class, Mail::class],
            'status' => [DropDown::class, Text::class]
        ];
        $accepts_multi = [
            'duedate' => false,
            'assignees' => true,
            'status' => false,
        ];
        $columns = [];
        $valid = true;
        foreach ($col_types as $name => $types) {
            $col = $s->findColumn($name);
            if ($col === false) {
                msg("structtasks schema '$schema' has no column '$name'.", -1);
                $valid = false;
            } else {
                $coltype = get_class($col->getType());
                if (!in_array($coltype, $types)) {
                    msg("Column '${name}' of structtasks schema '$schema' has invalid type ${coltype}", -1);
                    $valid = false;
                }
                if ($accepts_multi[$name] xor $col->isMulti()) {
                    msg("Column '${name}' of structtasks schema '$schema' must not accept multiple values; change the configurations", -1);
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
        global $conf;
        $schema = $conf['plugin']['structtasks']['schema'];
        if (!$this::isValidSchema($schema)) {
            return [NULL, NULL, false];
        }
        $old_data = $this->struct->getData($id, null, $old_rev);
        $new_data = $this->struct->getData($id, null, $new_rev);
        if (!array_key_exists($schema, $old_data) or !array_key_exists($schema, $new_data)) {
            return [NULL, NULL, false];
        }   
        return [$old_data[$schema], $new_data[$schema], true];
    }
    
    /**
     * Return a string with the real name and email address of $user,
     * suitable for using to send them an email.
     */
    function getUserEmail($user) {
        global $auth;
        $userData = $auth->getUserData($user, false);
        if ($userData === false) {
            if (preg_match('/\w+@\w+\.\w+/', $user)) {
                return $user;
            } else {
                return '';
            }
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
        if (!is_array($assignees)) {
            $assignees = [$assignees];
        }
        return array_values(array_filter(array_map([$this, 'getUserEmail'], $assignees)));
    }

    /**
     * Creates the $old_data array to be passed to
     * AbstractNotifier::sendMessage
     */
    function getOldData($eventdata, $structdata) {
        return [
            'content' => $eventdata['oldContent'],
            'duedate' => $structdata['duedate'],
            'assignees' => $this->assigneesToEmails($structdata['assignees']),
            'status' => $structdata['status'],
        ];
    }

    /**
     * Creates the $new_data array to be passed to
     * AbstractNotifier::sendMessage
     */
    function getNewData($eventdata, $structdata) {
        return [
            'content' => $eventdata['newContent'],
            'duedate' => $structdata['duedate'],
            'assignees' => $this->assigneesToEmails($structdata['assignees']),
            'status' => $structdata['status'],
        ];
    }

}
