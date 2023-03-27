<?php

namespace dokuwiki\plugin\structtasks\test;

use DokuWikiTest;
use dokuwiki\plugin\structtasks\meta\Utilities;


/**
 * Tests for Utilities::isValidSchema.
 *
 * @group plugin_structtasks
 * @group plugins
 */

class utilities_isvalid_plugin_structtasks_test extends StructtasksTest {
    /**
     * Create some useful properties.
     */
    public function setUp(): void {
        parent::setUp();
        $this->util = new Utilities(plugin_load('helper', 'struct'));
    }

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
        $this->util = new Utilities(plugin_load('helper', 'struct'));
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
        $this->struct_data = ['duedate' => '2023-04-10',
                                 'assignees' => ['user1', 'user2'],
                                 'status' => 'Ongoing'];
        $this->event_data = ['id' => 'some:page',
                             'file' => 'path/to/some/page.txt',
                             'revertFrom' => false,
                             'oldRevision' => 1000,
                             'newRevision' => 2000,
                             'newContent' => 'Some new text.',
                             'oldContent' => 'Some old text.',
                             'summary' => 'updated wording',
                             'contentChanged' => true,
                             'changeInfo' => '',
                             'changeType' => DOKU_CHANGE_TYPE_EDIT,
                             'sizechange' => 14,
        ];
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

        $this->assertEquals(['Fay Mail <user2@example.com>'],
                            $this->util->assigneesToEmails('user2'));
    }

    function testGetOldData() {
        $data = $this->util->getOldData($this->event_data, $this->struct_data);
        $this->assertEquals('Ongoing', $data['status']);
        $this->assertEquals(
            ['Arron Dom Person <adperson@example.com>', 'Fay Mail <user2@example.com>'],
            $data['assignees']
        );
        $this->assertEquals('2023-04-10', $data['duedate']);
        $this->assertEquals('Some old text.', $data['content']);
    }

    function testGetNewData() {
        $data = $this->util->getNewData($this->event_data, $this->struct_data);
        $this->assertEquals('Ongoing', $data['status']);
        $this->assertEquals(
            ['Arron Dom Person <adperson@example.com>', 'Fay Mail <user2@example.com>'],
            $data['assignees']
        );
        $this->assertEquals('2023-04-10', $data['duedate']);
        $this->assertEquals('Some new text.', $data['content']);
    }
}
