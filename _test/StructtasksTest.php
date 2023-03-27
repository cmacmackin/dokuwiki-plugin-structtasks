<?php

namespace dokuwiki\plugin\structtasks\test;

use dokuwiki\plugin\struct\meta\SchemaImporter;
use dokuwiki\plugin\struct\test\StructTest;

/**
 * Override the loadSchemaJSON method so it looks in the correct
 * directory
 */
class StructtasksTest extends StructTest {

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
