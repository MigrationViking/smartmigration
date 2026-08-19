<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Controller;

use OCA\SmartMigration\Db\Job;
use OCA\SmartMigration\Db\JobMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Internal, admin-only endpoint backing the Jobs tab in the Vue UI.
 *
 * This is not the SMART Migration polling API (`/ocs/v2.php/apps/smartmigration/api/v1/...`),
 * which is defined separately and authenticates via app password.
 *
 * @psalm-suppress UnusedClass
 */
class JobsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly JobMapper $jobMapper,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, array{jobs: array<array-key, array<string, mixed>>}, array{}>
	 */
	#[FrontpageRoute(verb: 'GET', url: '/settings/jobs')]
	public function index(): DataResponse {
		$jobs = array_map([$this, 'toApiArray'], $this->jobMapper->findAll());

		return new DataResponse(['jobs' => $jobs]);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array<string, mixed>, array{}>
	 */
	#[FrontpageRoute(verb: 'GET', url: '/settings/jobs/{id}')]
	public function show(int $id): DataResponse {
		try {
			$job = $this->jobMapper->find($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['message' => 'Job not found'], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->toApiArray($job));
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, array<string, mixed>, array{}>
	 */
	#[FrontpageRoute(verb: 'POST', url: '/settings/jobs')]
	public function create(
		string $title,
		?string $description = null,
		?string $group = null,
		string $advancedMode = 'No',
		string $status = 'Hold',
		?int $scheduledDate = null,
		string $recurrence = 'None',
		string $mode = 'Discovery',
		string $sourceType = 'SharePoint Library',
		?string $sourceUrl = null,
		?string $sourceUnc = null,
		?string $sourceUpn = null,
		string $includeSubfolders = 'Yes',
		string $includeVersionHistory = 'Yes',
		?int $fromDate = null,
		?int $toDate = null,
		?int $sizeFrom = null,
		?int $sizeTo = null,
		?string $sourceFileType = null,
		string $versionHistoryScope = '*:*:*',
	): DataResponse {
		$job = new Job();
		$job->setTitle($title);
		$job->setDescription($description);
		$job->setGroupName($group);
		$job->setAdvancedMode($advancedMode);
		$job->setStatus($status);
		$job->setScheduledDate($scheduledDate ?? time());
		$job->setRecurrence($recurrence);
		$job->setMode($mode);
		$job->setSourceType($sourceType);
		$job->setSourceUrl($sourceUrl);
		$job->setSourceUnc($sourceUnc);
		$job->setSourceUpn($sourceUpn);
		$job->setIncludeSubfolders($includeSubfolders);
		$job->setIncludeVersionHistory($includeVersionHistory);
		$job->setFromDate($fromDate);
		$job->setToDate($toDate);
		$job->setSizeFrom($sizeFrom);
		$job->setSizeTo($sizeTo);
		$job->setSourceFileType($sourceFileType);
		$job->setVersionHistoryScope($versionHistoryScope);
		$job->setCreatedAt(time());
		$job->setUpdatedAt(time());
		$job->setCreatedBy($this->userSession->getUser()?->getUID() ?? '');

		$job = $this->jobMapper->insert($job);

		return new DataResponse($this->toApiArray($job));
	}

	/**
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array<string, mixed>, array{}>
	 */
	#[FrontpageRoute(verb: 'PUT', url: '/settings/jobs/{id}')]
	public function update(
		int $id,
		string $title,
		?string $description = null,
		?string $group = null,
		string $advancedMode = 'No',
		string $status = 'Hold',
		?int $scheduledDate = null,
		string $recurrence = 'None',
		string $mode = 'Discovery',
		string $sourceType = 'SharePoint Library',
		?string $sourceUrl = null,
		?string $sourceUnc = null,
		?string $sourceUpn = null,
		string $includeSubfolders = 'Yes',
		string $includeVersionHistory = 'Yes',
		?int $fromDate = null,
		?int $toDate = null,
		?int $sizeFrom = null,
		?int $sizeTo = null,
		?string $sourceFileType = null,
		string $versionHistoryScope = '*:*:*',
	): DataResponse {
		try {
			$job = $this->jobMapper->find($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['message' => 'Job not found'], Http::STATUS_NOT_FOUND);
		}

		$job->setTitle($title);
		$job->setDescription($description);
		$job->setGroupName($group);
		$job->setAdvancedMode($advancedMode);
		$job->setStatus($status);
		$job->setScheduledDate($scheduledDate ?? $job->getScheduledDate());
		$job->setRecurrence($recurrence);
		$job->setMode($mode);
		$job->setSourceType($sourceType);
		$job->setSourceUrl($sourceUrl);
		$job->setSourceUnc($sourceUnc);
		$job->setSourceUpn($sourceUpn);
		$job->setIncludeSubfolders($includeSubfolders);
		$job->setIncludeVersionHistory($includeVersionHistory);
		$job->setFromDate($fromDate);
		$job->setToDate($toDate);
		$job->setSizeFrom($sizeFrom);
		$job->setSizeTo($sizeTo);
		$job->setSourceFileType($sourceFileType);
		$job->setVersionHistoryScope($versionHistoryScope);
		$job->setUpdatedAt(time());

		$job = $this->jobMapper->update($job);

		return new DataResponse($this->toApiArray($job));
	}

	/**
	 * Quick update used by the browse-view table, which only allows editing
	 * Title, Status, Scheduled Date and Group inline.
	 *
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array<string, mixed>, array{}>
	 */
	#[FrontpageRoute(verb: 'PATCH', url: '/settings/jobs/{id}')]
	public function patch(
		int $id,
		?string $title = null,
		?string $status = null,
		?string $group = null,
		?int $scheduledDate = null,
	): DataResponse {
		try {
			$job = $this->jobMapper->find($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['message' => 'Job not found'], Http::STATUS_NOT_FOUND);
		}

		if ($title !== null) {
			$job->setTitle($title);
		}
		if ($status !== null) {
			$job->setStatus($status);
		}
		if ($group !== null) {
			$job->setGroupName($group);
		}
		if ($scheduledDate !== null) {
			$job->setScheduledDate($scheduledDate);
		}
		$job->setUpdatedAt(time());

		$job = $this->jobMapper->update($job);

		return new DataResponse($this->toApiArray($job));
	}

	/**
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array<string, mixed>, array{}>
	 */
	#[FrontpageRoute(verb: 'POST', url: '/settings/jobs/{id}/copy')]
	public function copy(int $id): DataResponse {
		try {
			$source = $this->jobMapper->find($id);
		} catch (DoesNotExistException) {
			return new DataResponse(['message' => 'Job not found'], Http::STATUS_NOT_FOUND);
		}

		$job = new Job();
		$job->setTitle($source->getTitle() . ' (copy)');
		$job->setDescription($source->getDescription());
		$job->setGroupName($source->getGroupName());
		$job->setAdvancedMode($source->getAdvancedMode());
		$job->setStatus('Hold');
		$job->setScheduledDate($source->getScheduledDate());
		$job->setRecurrence($source->getRecurrence());
		$job->setMode($source->getMode());
		$job->setSourceType($source->getSourceType());
		$job->setSourceUrl($source->getSourceUrl());
		$job->setSourceUnc($source->getSourceUnc());
		$job->setSourceUpn($source->getSourceUpn());
		$job->setIncludeSubfolders($source->getIncludeSubfolders());
		$job->setIncludeVersionHistory($source->getIncludeVersionHistory());
		$job->setFromDate($source->getFromDate());
		$job->setToDate($source->getToDate());
		$job->setSizeFrom($source->getSizeFrom());
		$job->setSizeTo($source->getSizeTo());
		$job->setSourceFileType($source->getSourceFileType());
		$job->setVersionHistoryScope($source->getVersionHistoryScope());
		$job->setCreatedAt(time());
		$job->setUpdatedAt(time());
		$job->setCreatedBy($this->userSession->getUser()?->getUID() ?? '');

		$job = $this->jobMapper->insert($job);

		return new DataResponse($this->toApiArray($job));
	}

	/**
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array{}, array{}>
	 */
	#[FrontpageRoute(verb: 'DELETE', url: '/settings/jobs/{id}')]
	public function destroy(int $id): DataResponse {
		try {
			$job = $this->jobMapper->find($id);
		} catch (DoesNotExistException) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$this->jobMapper->delete($job);

		return new DataResponse([]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function toApiArray(Job $job): array {
		return [
			'id' => $job->getId(),
			'title' => $job->getTitle(),
			'description' => $job->getDescription(),
			'group' => $job->getGroupName(),
			'advancedMode' => $job->getAdvancedMode(),
			'status' => $job->getStatus(),
			'scheduledDate' => $job->getScheduledDate(),
			'recurrence' => $job->getRecurrence(),
			'mode' => $job->getMode(),
			'sourceType' => $job->getSourceType(),
			'sourceUrl' => $job->getSourceUrl(),
			'sourceUnc' => $job->getSourceUnc(),
			'sourceUpn' => $job->getSourceUpn(),
			'includeSubfolders' => $job->getIncludeSubfolders(),
			'includeVersionHistory' => $job->getIncludeVersionHistory(),
			'fromDate' => $job->getFromDate(),
			'toDate' => $job->getToDate(),
			'sizeFrom' => $job->getSizeFrom(),
			'sizeTo' => $job->getSizeTo(),
			'sourceFileType' => $job->getSourceFileType(),
			'versionHistoryScope' => $job->getVersionHistoryScope(),
			'result' => $job->getResult(),
			'createdAt' => $job->getCreatedAt(),
			'updatedAt' => $job->getUpdatedAt(),
			'createdBy' => $job->getCreatedBy(),
		];
	}
}
