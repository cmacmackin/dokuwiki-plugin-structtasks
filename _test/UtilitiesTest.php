<?php

namespace dokuwiki\plugin\structtasks\test;

use DokuWikiTest;

use dokuwiki\plugin\struct\meta\SchemaImporter;
use dokuwiki\plugin\struct\test\StructTest;
use dokuwiki\plugin\structtasks\meta\Utilities;

class StructtasksTest extends StructTest {
   /**
     * Create some useful properties.
     */
    public function setUp(): void {
        parent::setUp();
        $this->util = new Utilities(plugin_load('helper', 'struct'));
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
}


/**
 * Tests for Utilities::isValidSchema.
 *
 * @group plugin_structtasks
 * @group plugins
 */

class utilities_isvalid_plugin_structtasks_test extends StructtasksTest {

    function validSchemas() {
        return [['valid'], ['valid2']];
    }

    function invalidSchemas() {
        return [['badassignees'], ['baddate'], ['badstatus'],
                ['missingassignees'], ['missingdate'], ['missingstatus'],
                ['multidate'], ['multistatus']];
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
}


/**
 * Tests of the getMetadata method on the Utilities classes.
 *
 * @group plugin_structtasks
 * @group plugins
 */
class utilities_metadata_plugin_structtasks_test extends StructtasksTest {
    
    public function setUp(): void {
        parent::setUp();
        global $auth;
        global $conf;
        $conf['plugin']['structtasks']['schema'] = 'valid';
        $auth->createUser('user1', 'abcdefg', 'Arron Dom Person', 'adperson@example.com');
        $auth->createUser('user2', '123456789', 'Fay Mail', 'user2@example.com');
        
        $this->loadSchemaJSON('valid', '', 100);
        $this->loadSchemaJSON('baddate', '', 100);
        $this->rev1 = time() - 1;
        $this->rev2 = time();
        $this->old_metadata = ['duedate' => '2023-03-26',
                               'assignees' => ['user1'],
                               'status' => 'Ongoing'];
        $this->new_metadata = ['duedate' => '2023-04-10',
                               'assignees' => ['user1', 'user2'],
                               'status' => 'Ongoing'];
        $this->saveData('some:page', 'valid',
                        $this->old_metadata,
                        $this->rev1);
        $this->saveData('some:page', 'valid',
                        $this->new_metadata,
                        $this->rev2);
        saveWikiText('another:page', 'page without a task', 'saved for testing');
     }

    function testGetMetadata() {
        list($old_data, $new_data, $valid) = $this->util->getMetadata('some:page', $this->rev1, $this->rev2);
        $this->assertTrue($valid);
        foreach ($this->old_metadata as $key => $val) {
            $this->assertEquals($old_data[$key], $val);
        }
        foreach ($this->new_metadata as $key => $val) {
            $this->assertEquals($new_data[$key], $val);
        }
    }

    function invalidMetadataProvider() {
        return [
            'No data for page' => ['another:page', 'valid'],
            'Page does not exist' => ['not:a:page', 'valid'],
            'Unsuitable schema, no data' => ['another:page', 'baddate'],
            'Unsuitable schema, not assigned' => ['some:page', 'baddate'],
            'Schema does not exist' => ['another:page', 'does_not_exist'],
        ];
    }

    /**
     * @dataProvider invalidMetadataProvider
     */
    function testGetMetadataInvalid($page, $schema) {
        global $conf;
        $conf['plugin']['structtasks']['schema'] = $schema;
        list($old_data, $new_data, $valid) = $this->util->getMetadata($page, $this->rev1, $this->rev2);
        $this->assertNull($old_data);
        $this->assertNull($new_data);
        $this->assertFalse($valid);
    }
}


/**
 * Tests the remaining methods on the Utilities classes, which don't
 * require setting up the database.
 *
 * @group plugin_structtasks
 * @group plugins
 */
class utilities_simple_plugin_structtakss_test extends \DokuWikiTest {

    function setUp() : void {
        parent::setUp();
        global $auth;
        $this->util = new Utilities(plugin_load('helper', 'struct'));
        $auth->createUser('user1', 'abcdefg', 'Arron Dom Person', 'adperson@example.com');
        $auth->createUser('user2', '123456789', 'Fay Mail', 'user2@example.com');
        $auth->createUser('user3', 'asdkfjdl', '', 'some@mail.com');
    }

    function testGetUserEmail() {
        $this->assertEquals('Arron Dom Person <adperson@example.com>',
                            $this->util->getUserEmail('user1'));
        $this->assertEquals('Fay Mail <user2@example.com>',
                            $this->util->getUserEmail('user2'));
        $this->assertEquals('some@mail.com',
                            $this->util->getUserEmail('user3'));
        $this->assertEquals('', $this->util->getUserEmail('DoesNotExist'));
        $this->assertEquals('raw.email@address.org',
                            $this->util->getUserEmail('raw.email@address.org'));
        $this->assertEquals('user2@example.com',
                            $this->util->getUserEmail('user2@example.com'));
    }

    function testAssigneesToEmails(/*$assignees*/) {
        $allEmails = $this->util->assigneesToEmails([
            'user1', 'user2', 'user3', 'DoesNotExist', 'raw.email@address.org', 'user2@example.com'
        ]);
        $expected = ['Arron Dom Person <adperson@example.com>',
                     'Fay Mail <user2@example.com>',
                     'some@mail.com',
                     'raw.email@address.org',
                     'user2@example.com',
        ];
        $this->assertEquals($expected, $allEmails);
    }

    // function testGetOldData() {
    // }

    // function testGetNewData() {
    // }
}
