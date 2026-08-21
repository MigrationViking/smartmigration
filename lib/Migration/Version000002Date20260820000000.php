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
 * Creates the smartmig_settings table: a single row holding app configuration
 * and status, starting with the SMART Migration license.
 *
 * @psalm-suppress UnusedClass
 */
class Version000002Date20260820000000 extends SimpleMigrationStep {
	/**
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$tableName = 'smartmig_settings';
		if (!$schema->hasTable($tableName)) {
			$table = $schema->createTable($tableName);

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);

			// License
			$table->addColumn('license_key', Types::STRING, [
				'notnull' => false,
				'length' => 40,
			]);
			// Unix seconds, like every other date in this app.
			$table->addColumn('expiration_date', Types::BIGINT, [
				'notnull' => false,
			]);

			// Bookkeeping
			$table->addColumn('created_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('updated_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('created_by', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);

			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}
