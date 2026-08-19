<?php

declare(strict_types=1);

namespace Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Controller\JobsController;
use OCA\SmartMigration\Db\Job;
use OCA\SmartMigration\Db\JobMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

final class JobsControllerTest extends TestCase {
	private JobMapper $jobMapper;
	private IUserSession $userSession;
	private JobsController $controller;

	protected function setUp(): void {
		$request = $this->createMock(IRequest::class);
		$this->jobMapper = $this->createMock(JobMapper::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new JobsController(
			Application::APP_ID,
			$request,
			$this->jobMapper,
			$this->userSession,
		);
	}

	public function testIndexReturnsJobsAsArrays(): void {
		$job = new Job();
		$job->setTitle('Discover LibA');

		$this->jobMapper->method('findAll')->willReturn([$job]);

		$data = $this->controller->index()->getData();

		$this->assertCount(1, $data['jobs']);
		$this->assertEquals('Discover LibA', $data['jobs'][0]['title']);
		$this->assertEquals('Discovery', $data['jobs'][0]['mode']);
	}

	public function testCreateSetsCreatedByFromSession(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->jobMapper->method('insert')
			->willReturnCallback(static fn (Job $job) => $job);

		$data = $this->controller->create(title: 'Discover LibA')->getData();

		$this->assertEquals('alice', $data['createdBy']);
		$this->assertEquals('Discovery', $data['mode']);
		$this->assertEquals('Hold', $data['status']);
	}

	public function testShowReturnsNotFoundForMissingJob(): void {
		$this->jobMapper->method('find')
			->willThrowException($this->createMock(DoesNotExistException::class));

		$response = $this->controller->show(999);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testPatchOnlyUpdatesProvidedFields(): void {
		$job = new Job();
		$job->setTitle('Original title');
		$job->setStatus('Hold');

		$this->jobMapper->method('find')->willReturn($job);
		$this->jobMapper->method('update')
			->willReturnCallback(static fn (Job $job) => $job);

		$data = $this->controller->patch(1, status: 'Ready')->getData();

		$this->assertEquals('Original title', $data['title']);
		$this->assertEquals('Ready', $data['status']);
	}

	public function testPatchUpdatesGroup(): void {
		$job = new Job();
		$job->setTitle('Discover LibA');
		$job->setGroupName('Group 1');

		$this->jobMapper->method('find')->willReturn($job);
		$this->jobMapper->method('update')
			->willReturnCallback(static fn (Job $job) => $job);

		$data = $this->controller->patch(1, group: 'Group 2')->getData();

		$this->assertEquals('Group 2', $data['group']);
	}

	public function testPatchUpdatesScheduledDate(): void {
		$job = new Job();
		$job->setTitle('Discover LibA');
		$job->setScheduledDate(1000);

		$this->jobMapper->method('find')->willReturn($job);
		$this->jobMapper->method('update')
			->willReturnCallback(static fn (Job $job) => $job);

		$data = $this->controller->patch(1, scheduledDate: 2000)->getData();

		$this->assertEquals(2000, $data['scheduledDate']);
	}

	public function testCopyResetsStatusToHold(): void {
		$source = new Job();
		$source->setTitle('Discover LibA');
		$source->setStatus('Finished');

		$this->jobMapper->method('find')->willReturn($source);
		$this->jobMapper->method('insert')
			->willReturnCallback(static fn (Job $job) => $job);

		$data = $this->controller->copy(1)->getData();

		$this->assertEquals('Discover LibA (copy)', $data['title']);
		$this->assertEquals('Hold', $data['status']);
	}
}
