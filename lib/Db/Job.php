<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string|null getDescription()
 * @method void setDescription(?string $description)
 * @method string|null getGroupName()
 * @method void setGroupName(?string $groupName)
 * @method string getAdvancedMode()
 * @method void setAdvancedMode(string $advancedMode)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method int getScheduledDate()
 * @method void setScheduledDate(int $scheduledDate)
 * @method string getRecurrence()
 * @method void setRecurrence(string $recurrence)
 * @method string getMode()
 * @method void setMode(string $mode)
 * @method string getSourceType()
 * @method void setSourceType(string $sourceType)
 * @method string|null getSourceUrl()
 * @method void setSourceUrl(?string $sourceUrl)
 * @method string|null getSourceUnc()
 * @method void setSourceUnc(?string $sourceUnc)
 * @method string|null getSourceUpn()
 * @method void setSourceUpn(?string $sourceUpn)
 * @method string getIncludeSubfolders()
 * @method void setIncludeSubfolders(string $includeSubfolders)
 * @method string getIncludeVersionHistory()
 * @method void setIncludeVersionHistory(string $includeVersionHistory)
 * @method int|null getFromDate()
 * @method void setFromDate(?int $fromDate)
 * @method int|null getToDate()
 * @method void setToDate(?int $toDate)
 * @method int|null getSizeFrom()
 * @method void setSizeFrom(?int $sizeFrom)
 * @method int|null getSizeTo()
 * @method void setSizeTo(?int $sizeTo)
 * @method string|null getSourceFileType()
 * @method void setSourceFileType(?string $sourceFileType)
 * @method string getVersionHistoryScope()
 * @method void setVersionHistoryScope(string $versionHistoryScope)
 * @method string|null getResult()
 * @method void setResult(?string $result)
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
class Job extends Entity {
	protected string $title = '';
	protected ?string $description = null;
	protected ?string $groupName = null;
	protected string $advancedMode = 'No';
	protected string $status = 'Hold';
	protected int $scheduledDate = 0;
	protected string $recurrence = 'None';
	protected string $mode = 'Discovery';
	protected string $sourceType = 'SharePoint Library';
	protected ?string $sourceUrl = null;
	protected ?string $sourceUnc = null;
	protected ?string $sourceUpn = null;
	protected string $includeSubfolders = 'Yes';
	protected string $includeVersionHistory = 'Yes';
	protected ?int $fromDate = null;
	protected ?int $toDate = null;
	protected ?int $sizeFrom = null;
	protected ?int $sizeTo = null;
	protected ?string $sourceFileType = null;
	protected string $versionHistoryScope = '*:*:*';
	protected ?string $result = null;
	protected int $createdAt = 0;
	protected int $updatedAt = 0;
	protected string $createdBy = '';

	public function __construct() {
		$this->addType('title', Types::STRING);
		$this->addType('description', Types::STRING);
		$this->addType('groupName', Types::STRING);
		$this->addType('advancedMode', Types::STRING);
		$this->addType('status', Types::STRING);
		$this->addType('scheduledDate', Types::BIGINT);
		$this->addType('recurrence', Types::STRING);
		$this->addType('mode', Types::STRING);
		$this->addType('sourceType', Types::STRING);
		$this->addType('sourceUrl', Types::STRING);
		$this->addType('sourceUnc', Types::STRING);
		$this->addType('sourceUpn', Types::STRING);
		$this->addType('includeSubfolders', Types::STRING);
		$this->addType('includeVersionHistory', Types::STRING);
		$this->addType('fromDate', Types::BIGINT);
		$this->addType('toDate', Types::BIGINT);
		$this->addType('sizeFrom', Types::INTEGER);
		$this->addType('sizeTo', Types::INTEGER);
		$this->addType('sourceFileType', Types::STRING);
		$this->addType('versionHistoryScope', Types::STRING);
		$this->addType('result', Types::STRING);
		$this->addType('createdAt', Types::BIGINT);
		$this->addType('updatedAt', Types::BIGINT);
		$this->addType('createdBy', Types::STRING);
	}
}
