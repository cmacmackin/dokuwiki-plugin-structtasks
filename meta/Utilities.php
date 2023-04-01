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
use dokuwiki\plugin\struct\types\Dropdown;
use dokuwiki\plugin\struct\types\User;
use dokuwiki\plugin\struct\types\Mail;
use dokuwiki\plugin\struct\types\Text;

/**
 * Class with various useful static methods. 
 */
class Utilities
{
    protected $struct;
    /// Date-time formata for the due-date, extracted from the
    /// most-recently validated schema and cached here.
    protected $duedate_format;

    /**
     *  Pass in an instance of the struct_helper plugin when building this class;
     * @param mixed $helper
     */
    public function __construct(private $helper) {
        $this->struct = $helper;
    }

    /**
     * Tests whether the specified schema meets the requirements for
     * describing tasks.
     * @return bool
     * @param mixed $schema
     */
    function isValidSchema($schema): bool {
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
            'status' => [Dropdown::class, Text::class]
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
        if ($valid) $this->duedate_format = $s->findColumn('duedate')->getType()->getConfig()['format'];
        return $valid;
    }
    
    /**
     * Safely fetches the old and new struct metadata for this
     * event. Also returns a flag indicating if this page is even a
     * valid task for which notifications could be sent.
     */
    function getMetadata($id, $schema, $old_rev, $new_rev) {
        if (!$this::isValidSchema($schema)) {
            return [NULL, NULL, false];
        }
        $old_data = $this->struct->getData($id, null, $old_rev);
        $new_data = $this->struct->getData($id, null, $new_rev);
        if (!array_key_exists($schema, $old_data) or !array_key_exists($schema, $new_data)) {
            return [NULL, NULL, false];
        }
        $old_data[$schema]['date_format'] = $this->duedate_format;
        $new_data[$schema]['date_format'] = $this->duedate_format;
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
            if (mail_isvalid($user)) {
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
     * @return array
     * @param mixed $eventdata
     * @param mixed $structdata
     */
    function getOldData($eventdata, $structdata): array {
        $d = date_create($structdata['duedate']);
        return [
            'content' => $eventdata['oldContent'],
            'duedate' => $d,
            'duedate_formatted' => $d->format($structdata['date_format']),
            'assignees' => $this->assigneesToEmails($structdata['assignees']),
            'status' => $structdata['status'],
        ];
    }

    /**
     * Creates the $new_data array to be passed to
     * AbstractNotifier::sendMessage
     * @return array
     * @param mixed $eventdata
     * @param mixed $structdata
     */
    function getNewData($eventdata, $structdata): array {
        $d = date_create($structdata['duedate']);
        return [
            'content' => $eventdata['newContent'],
            'duedate' => $d,
            'duedate_formatted' => $d->format($structdata['date_format']),
            'assignees' => $this->assigneesToEmails($structdata['assignees']),
            'status' => $structdata['status'],
        ];
    }

}
