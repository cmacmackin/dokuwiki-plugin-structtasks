<?php
/**
 * DokuWiki Plugin structtasks (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 */

use dokuwiki\plugin\structtasks\meta\Utilities;
use dokuwiki\plugin\structtasks\meta\AssignedNotifier;
use dokuwiki\plugin\structtasks\meta\ClosedStatusNotifier;
use dokuwiki\plugin\structtasks\meta\DateNotifier;
use dokuwiki\plugin\structtasks\meta\DeletedNotifier;
use dokuwiki\plugin\structtasks\meta\OpenStatusNotifier;
use dokuwiki\plugin\structtasks\meta\RemovedNotifier;
use dokuwiki\plugin\structtasks\meta\SelfRemovalNotifier;

class action_plugin_structtasks extends \dokuwiki\Extension\ActionPlugin
{

    public $notifiers = array();

    private $util;

    public function __constructor() {
        // Insantiate the Notifier objects
        $this->util = new Utilities();
        $getConf = [$this, 'getConf'];
        $getLang = [$this, 'getLang'];
        $this->notifiers = [
            new AssignedNotifier($getConf, $getLang),
            new ClosedStatusNotifier($getConf, $getLang),
            new DateNotifier($getConf, $getLang),
            new DeletedNotifier($getConf, $getLang),
            new OpenStatusNotifier($getConf, $getLang),
            new RemovedNotifier($getConf, $getLang),
            new SelfRemovalNotifier($getConf, $getLang),
        ];
    }
    
    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller): void
    {
        // This must run AFTER the Struct plugin has updated the metadata
        $controller->register_hook('COMMON_WIKIPAGE_SAVE', 'AFTER', $this, 'handle_common_wikipage_save', null, 3999);
   
    }

    /**
     * Event handler for notifying task assignees of task changes or creation
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  optional parameter passed when event was registered
     * @return void
     */
    public function handle_common_wikipage_save(Doku_Event $event, $param)
    {
        global $conf;
        $schema = $conf['plugin']['structtasks']['schema'];
        if ($schema == '') return false;
        $struct = $this->loadHelper('struct', true);
        if (is_null($struct)) return;
        $util = new Utilities($struct);

        $id = $event->data['id'];
        list($old_structdata, $new_structdata, $valid) = $util->getMetadata(
            $id, $schema, $event->data['oldRevision'], $event->data['newRevision']
        );
        if (!$valid) return;

        global $INPUT;
        global $auth;
        $title = p_get_first_heading($id, METADATA_RENDER_USING_SIMPLE_CACHE);
        $editor_id = $INPUT->server->str('REMOTE_USER');
        $userData = $auth->getUserData($editor_id, false);
        $editor_name = ($userData !== false and $userData['name'] !== '') ?
                     $userData['name'] : $editor_id;
        $editor_email = $util->getUserEmail($editor_id);
        $new_data = $util->getNewData($event->data, $new_structdata);
        $old_data = $util->getOldData($event->data, $old_structdata);

        foreach ($this->notifiers as $notifier) {
            $notifier->sendMessage($id, $title, $editor_name, $editor_email, $new_data, $old_data);
        }
    }
}
