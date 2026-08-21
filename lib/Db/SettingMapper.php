<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;

/**
 * The settings table holds a single row. Reads return null when it has never
 * been written, so the UI can render an unlicensed state without a write.
 *
 * @extends QBMapper<Setting>
 */
class SettingMapper extends QBMapper {
	/**
	 * @psalm-suppress PossiblyUnusedMethod Instantiated via dependency injection.
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'smartmig_settings', Setting::class);
	}

	/**
	 * The settings row, or null when nothing has been stored yet.
	 *
	 * Ordering by id keeps this deterministic if a race ever produces a second row.
	 *
	 * @throws Exception
	 */
	public function findFirst(): ?Setting {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->orderBy('id', 'ASC')
			->setMaxResults(1);

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * The settings row, inserting an empty one if it does not exist yet.
	 *
	 * Write path only — do not call this to render a read-only view.
	 *
	 * @throws Exception
	 */
	public function getOrCreate(string $uid = ''): Setting {
		$setting = $this->findFirst();
		if ($setting !== null) {
			return $setting;
		}

		$now = time();
		$setting = new Setting();
		$setting->setCreatedAt($now);
		$setting->setUpdatedAt($now);
		$setting->setCreatedBy($uid);

		return $this->insert($setting);
	}
}
