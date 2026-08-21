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
 * Adds the support contact columns to smartmig_settings: who the customer calls
 * when a migration goes wrong. Written by SMART Migration, displayed read-only
 * on the Support tab.
 *
 * @psalm-suppress UnusedClass
 */
class Version000003Date20260821000000 extends SimpleMigrationStep {
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

		if (!$table->hasColumn('support_name')) {
			$table->addColumn('support_name', Types::STRING, [
				'notnull' => false,
				'length' => 50,
			]);
		}
		if (!$table->hasColumn('support_email')) {
			$table->addColumn('support_email', Types::STRING, [
				'notnull' => false,
				'length' => 50,
			]);
		}
		if (!$table->hasColumn('support_company')) {
			$table->addColumn('support_company', Types::STRING, [
				'notnull' => false,
				'length' => 50,
			]);
		}

		return $schema;
	}
}
