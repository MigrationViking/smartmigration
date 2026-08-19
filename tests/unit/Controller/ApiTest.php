<?php

declare(strict_types=1);

namespace Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Controller\ApiController;
use OCP\App\IAppManager;
use OCP\IRequest;
use OCP\ServerVersion;
use PHPUnit\Framework\TestCase;

final class ApiTest extends TestCase {
	private IAppManager $appManager;
	private ServerVersion $serverVersion;
	private ApiController $controller;

	protected function setUp(): void {
		$request = $this->createMock(IRequest::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);

		$this->controller = new ApiController(
			Application::APP_ID,
			$request,
			$this->appManager,
			$this->serverVersion,
		);
	}

	public function testIndex(): void {
		$this->assertEquals('Hello everyone2!', $this->controller->index()->getData()['message']);
	}

	public function testVersion(): void {
		$this->appManager->method('getAppVersion')
			->with(Application::APP_ID)
			->willReturn('0.1.0');
		$this->serverVersion->method('getVersionString')->willReturn('34.0.3.1');
		$this->serverVersion->method('getMajorVersion')->willReturn(34);

		$data = $this->controller->version()->getData();

		$this->assertEquals(Application::APP_ID, $data['appId']);
		$this->assertEquals('0.1.0', $data['appVersion']);
		$this->assertEquals('v1', $data['apiVersion']);
		$this->assertEquals('34.0.3.1', $data['nextcloudVersion']);
		$this->assertEquals(34, $data['nextcloudVersionMajor']);
	}
}
