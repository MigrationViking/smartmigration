<?php

declare(strict_types=1);

namespace Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Controller\SettingsController;
use OCA\SmartMigration\Db\Setting;
use OCA\SmartMigration\Db\SettingMapper;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

final class SettingsControllerTest extends TestCase {
	private SettingMapper $settingMapper;
	private SettingsController $controller;

	protected function setUp(): void {
		$request = $this->createMock(IRequest::class);
		$this->settingMapper = $this->createMock(SettingMapper::class);

		$this->controller = new SettingsController(
			Application::APP_ID,
			$request,
			$this->settingMapper,
		);
	}

	public function testLicenseReturnsNullsWhenNothingStored(): void {
		$this->settingMapper->method('findFirst')->willReturn(null);

		$data = $this->controller->license()->getData();

		$this->assertNull($data['smServerName']);
		$this->assertNull($data['licenseKey']);
		$this->assertNull($data['expirationDate']);
		$this->assertNull($data['currentSmVersion']);
		$this->assertEquals(Application::REQUIRED_SMART_VERSION, $data['requiredSmartVersion']);
	}

	public function testLicenseReturnsStoredValues(): void {
		$setting = new Setting();
		$setting->setLicenseKey('SMART-1234');
		$setting->setExpirationDate(1800000000);
		$setting->setCurrentSmVersion('2.4.1');
		$setting->setSmServerName('SMART-PROD-01');
		$this->settingMapper->method('findFirst')->willReturn($setting);

		$data = $this->controller->license()->getData();

		$this->assertEquals('SMART-PROD-01', $data['smServerName']);

		$this->assertEquals('SMART-1234', $data['licenseKey']);
		$this->assertEquals(1800000000, $data['expirationDate']);
		$this->assertEquals('2.4.1', $data['currentSmVersion']);
		$this->assertEquals(Application::REQUIRED_SMART_VERSION, $data['requiredSmartVersion']);
	}

	public function testSupportReturnsNullsWhenNothingStored(): void {
		$this->settingMapper->method('findFirst')->willReturn(null);

		$data = $this->controller->support()->getData();

		$this->assertNull($data['supportName']);
		$this->assertNull($data['supportEmail']);
		$this->assertNull($data['supportCompany']);
	}

	public function testSupportReturnsStoredValues(): void {
		$setting = new Setting();
		$setting->setSupportName('Ida Berg');
		$setting->setSupportEmail('support@migratedms.com');
		$setting->setSupportCompany('MigrateDMS');
		$this->settingMapper->method('findFirst')->willReturn($setting);

		$data = $this->controller->support()->getData();

		$this->assertEquals('Ida Berg', $data['supportName']);
		$this->assertEquals('support@migratedms.com', $data['supportEmail']);
		$this->assertEquals('MigrateDMS', $data['supportCompany']);
	}

	/** The Settings and Support tabs are display-only; writing goes through the OCS API. */
	public function testControllerExposesNoWriteMethod(): void {
		$methods = get_class_methods(SettingsController::class);

		$this->assertContains('license', $methods);
		$this->assertContains('support', $methods);
		$this->assertNotContains('setLicense', $methods);
		$this->assertNotContains('setSupportContact', $methods);
	}
}
