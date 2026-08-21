<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Adds sm_server_name to smartmig_settings: the name the remote SMART Migration
 * server calls itself. Written by SMART Migration, displayed read-only on the
 * Settings tab.
 *
 * @psalm-suppress UnusedClass
 */
class Version000005Date20260821000002 extends SimpleMigrationStep {
	/**
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$tableName = 'smartmig_settings';
		if (!$schema->hasTable($tableName)) {
			return null;
		}

		$table = $schema->getTable($tableName);

		if (!$table->hasColumn('sm_server_name')) {
			$table->addColumn('sm_server_name', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
		}

		return $schema;
	}
}
