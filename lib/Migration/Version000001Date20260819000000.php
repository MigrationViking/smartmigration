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
 * Creates the smartmig_jobs table.
 *
 * @psalm-suppress UnusedClass
 */
class Version000001Date20260819000000 extends SimpleMigrationStep {
	/**
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$tableName = 'smartmig_jobs';
		if (!$schema->hasTable($tableName)) {
			$table = $schema->createTable($tableName);

			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);

			// Job
			$table->addColumn('title', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('description', Types::STRING, [
				'notnull' => false,
				'length' => 2500,
			]);
			$table->addColumn('group_name', Types::STRING, [
				'notnull' => false,
				'length' => 50,
			]);
			$table->addColumn('advanced_mode', Types::STRING, [
				'notnull' => true,
				'length' => 3,
				'default' => 'No',
			]);
			$table->addColumn('status', Types::STRING, [
				'notnull' => true,
				'length' => 10,
				'default' => 'Hold',
			]);
			$table->addColumn('scheduled_date', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('recurrence', Types::STRING, [
				'notnull' => true,
				'length' => 10,
				'default' => 'None',
			]);

			// Action
			$table->addColumn('mode', Types::STRING, [
				'notnull' => true,
				'length' => 15,
				'default' => 'Discovery',
			]);

			// Source
			$table->addColumn('source_type', Types::STRING, [
				'notnull' => true,
				'length' => 20,
				'default' => 'SharePoint Library',
			]);
			$table->addColumn('source_url', Types::STRING, [
				'notnull' => false,
				'length' => 500,
			]);
			$table->addColumn('source_unc', Types::STRING, [
				'notnull' => false,
				'length' => 1500,
			]);
			$table->addColumn('source_upn', Types::STRING, [
				'notnull' => false,
				'length' => 100,
			]);
			$table->addColumn('include_subfolders', Types::STRING, [
				'notnull' => true,
				'length' => 3,
				'default' => 'Yes',
			]);
			$table->addColumn('include_version_history', Types::STRING, [
				'notnull' => true,
				'length' => 3,
				'default' => 'Yes',
			]);
			$table->addColumn('from_date', Types::BIGINT, [
				'notnull' => false,
			]);
			$table->addColumn('to_date', Types::BIGINT, [
				'notnull' => false,
			]);
			$table->addColumn('size_from', Types::INTEGER, [
				'notnull' => false,
				'length' => 6,
			]);
			$table->addColumn('size_to', Types::INTEGER, [
				'notnull' => false,
				'length' => 6,
			]);
			$table->addColumn('source_file_type', Types::STRING, [
				'notnull' => false,
				'length' => 500,
			]);
			$table->addColumn('version_history_scope', Types::STRING, [
				'notnull' => true,
				'length' => 30,
				'default' => '*:*:*',
			]);

			// Result
			$table->addColumn('result', Types::STRING, [
				'notnull' => false,
				'length' => 2500,
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
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['status'], 'smartmig_jobs_status');
			$table->addIndex(['mode'], 'smartmig_jobs_mode');
		}

		return $schema;
	}
}
