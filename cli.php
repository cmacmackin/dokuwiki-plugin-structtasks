<?php

use splitbrain\phpcli\Options;
use dokuwiki\plugin\structtasks\meta\Utilities;
use dokuwiki\plugin\structtasks\meta\ReminderNotifier;
use dokuwiki\plugin\structtasks\meta\TodayNotifier;
use dokuwiki\plugin\structtasks\meta\OverdueNotifier;

/**
 * DokuWiki Plugin structtasks (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Chris MacMackin <cmacmackin@gmail.com>
 */
class cli_plugin_structtasks extends \dokuwiki\Extension\CLIPlugin
{

    private $schema;
    private $struct;
    private $util;
    private $dateformat;
    public $testing = false;
    public $notifiers = [];

    /** @inheritDoc */
    protected function setup(Options $options)
    {
        $options->setHelp('Send emails to users that have been assigned to tasks.');
        $options->registerOption('verbose', 'Show progress information', 'v', false);
    }

    /** @inheritDoc */
    public function main(Options $options)
    {
        $this->notify($options->getOpt('verbose'));
    }

    /**
     * Function to run CLI algorithm.
     */
    public function notify($verbose = true) {
        if (!$this->initialise($verbose)) {
            exit(-1);
        }

        $notifiers = $this->createNotifiers(
            array_map('intval', $this->getConf('reminder')),
            (bool)$this->getConf('overdue_reminder')
        );

        $tasks = $this->struct->getPages($this->schema);
        foreach (array_keys($tasks) as $task) {
            if ($verbose) $this->notice(sprintf($this->getLang('msg_processing'), $task));
            $this->processTask($task, $notifiers);
        }
    }
    
    /**
     * Sets up useful properties for the class. Returns true if
     * initialises successfully (e.g., all configurations are valid,
     * necessary plugins available, etc.), false otherwise.
     */
    public function initialise($verbose = true) : bool {
        $this->schema = $this->getConf('schema');
        if ($this->schema == '') return false;
        $this->struct = $this->loadHelper('struct', true);
        if (is_null($this->struct)) return false;
        $this->util = new Utilities($this->struct);
        if (!$this->util->isValidSchema($this->schema)) {
            if ($verbose) $this->error(
                sprintf($this->getLang('msg_invalid_schema'), $this->schema));
            return false;
        }
        $this->dateformat = $this->util->dateFormat($this->schema);
        return true;
    }

    /**
     * Create an array of notifiers based on structtask configurations.
     *
     * @param array<int> $reminder_days  On which days before the due-date
     *                                   to send reminders
     * @param bool       $overdue        Whether to send reminders for
     *                                   overdue tasks
     * @return array<AbstractNotifier>
     */
    function createNotifiers($reminder_days, $overdue) : array {
        if ($this->testing) return $this->notifiers;
        $notifiers = [];
        $getConf = [$this, 'getConf'];
        $getLang = [$this, 'getLang'];
        if (($key = array_search(0, $reminder_days)) !== false) {
            unset($reminder_days[$key]);
            $notifiers[] = new TodayNotifier($getConf, $getLang);
        }
        if (count($reminder_days) != 0) {
            $notifiers[] = new ReminderNotifier($getConf, $getLang, $reminder_days);
        }
        if ($overdue) {
            $notifiers[] = new OverdueNotifier($getConf, $getLang);
        }
        return $notifiers;
    }

    /**
     * Apply notifiers to the specified task
     *
     * @param string                  $task       ID for the task page being processed
     * @param array<AbstractNotifier> $notifiers  Notifiers to use with this task
     */
    function processTask($task, $notifiers) : void {
        $filename = wikiFN($task);
        $rev = @filemtime($filename);
        $content = rawWiki($task);
        $structdata = $this->struct->getData($task, $this->schema)[$this->schema];
        $data = $this->util->formatData($structdata, $this->dateformat);
        $data['content'] = $content;
        $title = p_get_first_heading($task, METADATA_RENDER_USING_SIMPLE_CACHE);
        // Doesn't seem to be a simple way to get editor info for
        // arbitrary pages, so will just leave that blank. It
        // doesn't actually get used in any of the reminder emails
        // anyway.
        $editor_name = '';
        $editor_email = '';
        foreach ($notifiers as $n) {
            $n->sendMessage($task, $title, $editor_name, $editor_email, $data, $data);
        }
    }
}

