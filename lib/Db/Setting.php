<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * A single row of app configuration and status.
 *
 * @method string|null getLicenseKey()
 * @method void setLicenseKey(?string $licenseKey)
 * @method int|null getExpirationDate()
 * @method void setExpirationDate(?int $expirationDate)
 * @method string|null getSmServerName()
 * @method void setSmServerName(?string $smServerName)
 * @method string|null getCurrentSmVersion()
 * @method void setCurrentSmVersion(?string $currentSmVersion)
 * @method string|null getSupportName()
 * @method void setSupportName(?string $supportName)
 * @method string|null getSupportEmail()
 * @method void setSupportEmail(?string $supportEmail)
 * @method string|null getSupportCompany()
 * @method void setSupportCompany(?string $supportCompany)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 * @method string getCreatedBy()
 * @method void setCreatedBy(string $createdBy)
 *
 * @psalm-suppress PossiblyUnusedProperty Accessed only through Entity's magic getters/setters.
 * @psalm-suppress PropertyNotSetInConstructor $id is initialized by the parent Entity class.
 */
class Setting extends Entity {
	protected ?string $licenseKey = null;
	/** Unix seconds, like every other date in this app. */
	protected ?int $expirationDate = null;
	/** Name the remote SMART Migration server calls itself. */
	protected ?string $smServerName = null;
	/** Version the remote SMART Migration server reports about itself. */
	protected ?string $currentSmVersion = null;
	/** Support contact, written by SMART Migration and shown read-only on the Support tab. */
	protected ?string $supportName = null;
	protected ?string $supportEmail = null;
	protected ?string $supportCompany = null;
	protected int $createdAt = 0;
	protected int $updatedAt = 0;
	protected string $createdBy = '';

	public function __construct() {
		$this->addType('licenseKey', Types::STRING);
		$this->addType('expirationDate', Types::BIGINT);
		$this->addType('smServerName', Types::STRING);
		$this->addType('currentSmVersion', Types::STRING);
		$this->addType('supportName', Types::STRING);
		$this->addType('supportEmail', Types::STRING);
		$this->addType('supportCompany', Types::STRING);
		$this->addType('createdAt', Types::BIGINT);
		$this->addType('updatedAt', Types::BIGINT);
		$this->addType('createdBy', Types::STRING);
	}
}
