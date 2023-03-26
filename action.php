<?php
/**
 * DokuWiki Plugin structtasks (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 */

use dokuwiki\plugin\structtasks\meta\Utilities;

class action_plugin_structtasks extends \dokuwiki\Extension\ActionPlugin
{

    private $notifiers = array();

    public function __constructor() {
        // Insantiate the Notifier objects
        $this->util = new Utilities();
    }
    
    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
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
        // Check if schema assigned to this page
        $newMetaData = $struct->getData($event->id, $this->getConf('schema'), $event->newRevision);
        // If no struct data assigned, then do nothing
        if (count($newMetaData) == 0) {
            return;
        }

        $title = p_get_first_heading($event->id, METADATA_RENDER_USING_SIMPLE_CACHE);

        // Fetch struct data from before and after the edit
        // FIXME: Make sure to consider page creation; should basically make old metadata empty, but the helper plugin might not do that for me.

        // Work out what changes have been made
        $new_data = array(
            'content' => '',
            'duedate' => '',
            'assignees' => [],
            'status' => '',
        );
        $old_data = array();
        
        /* Send email to assignees that WEREN'T THE EDITOR if
         *   - They have been newly assigned to task
         *   - They have been removed from the task
         *   - Someone else has removed THEMSELVES from the task
         *   - The due date has changed
         *   - The task status changes
         *   - The task page is deleted
         *   - Someone has updated the task?
         */

        // Subscribe new assignees to the page?

        // Unsubscribe any assignees that have been removed?
    }
}

