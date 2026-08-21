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
 * Adds current_sm_version to smartmig_settings: the version the remote SMART
 * Migration server reports about itself. Written by SMART Migration, displayed
 * read-only on the Settings tab.
 *
 * Distinct from Application::REQUIRED_SMART_VERSION, which is the minimum this
 * app is built against and is maintained by hand in code.
 *
 * @psalm-suppress UnusedClass
 */
class Version000004Date20260821000001 extends SimpleMigrationStep {
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

		if (!$table->hasColumn('current_sm_version')) {
			$table->addColumn('current_sm_version', Types::STRING, [
				'notnull' => false,
				'length' => 20,
			]);
		}

		return $schema;
	}
}
