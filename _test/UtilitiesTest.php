<?php

namespace dokuwiki\plugin\structtasks\test;

use DokuWikiTest;

use dokuwiki\plugin\struct\meta\SchemaImporter;
use dokuwiki\plugin\structtasks\meta\Utilities;

/**
 * Tests of the methods on the Utilities classes for the structtasks
 * plugin. These perform miscellaneous tasks related to processing
 * page and struct data.
 *
 * @group plugin_structtasks
 * @group plugins
 */

class utilities_plugin_structtasks_test extends DokuWikiTest {

    /** @var array alway enable the needed plugins */
    protected $pluginsEnabled = array('struct', 'sqlite');

    /**
     * Create some useful properties.
     */
    public function setUp(): void {
        parent::setUp();
        $this->util = new Utilities(plugin_load('helper', 'struct'));
    }

    /**
     * Clear the struct database.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        /** @var \helper_plugin_struct_db $db */
        $db = plugin_load('helper', 'struct_db');
        $db->resetDB();
    }

    /**
     * Creates a schema from one of the available schema files
     *
     * @param string $schema
     * @param string $json base name of the JSON file optional, defaults to $schema
     * @param int $rev allows to create schemas back in time
     * @param bool $lookup create as a lookup schema
     */
    protected function loadSchemaJSON($schema, $json = '', $rev = 0)
    {
        if (!$json) $json = $schema;
        $file = __DIR__ . "/json/$json.struct.json";
        if (!file_exists($file)) {
            throw new \RuntimeException("$file does not exist");
        }

        $json = file_get_contents($file);
        $importer = new SchemaImporter($schema, $json);

        if (!$importer->build($rev)) {
            throw new \RuntimeException("build of $schema from $file failed");
        }
    }

    function validSchemas() {
        return [['valid'], ['valid2']];
    }

    function invalidSchemas() {
        return [['badassignees'], ['baddate'], ['badstatus'],
                ['missingassignees'], ['missingdate'], ['missingstatus']];
    }

    /**
     * @dataProvider validSchemas
     */
    function testIsValidSchema($schema) {
        $this->assertFalse($this->util->isValidSchema($schema));
        $this->loadSchemaJSON($schema);
        $this->assertTrue($this->util->isValidSchema($schema));
    }

    /**
     * @dataProvider invalidSchemas
     */    
    function testIsInvalidSchema($schema) {
        $this->assertFalse($this->util->isValidSchema($schema));
        $this->loadSchemaJSON($schema);
        $this->assertFalse($this->util->isValidSchema($schema));
    }
    
    function testGetMetadata() {
    }

    function testGetUserEmail(/*$assignee*/) {
    }

    function testAssigneesToEmails(/*$assignees*/) {
    }

    function testGetOldData() {
    }

    function testGetNewData() {
    }
}
