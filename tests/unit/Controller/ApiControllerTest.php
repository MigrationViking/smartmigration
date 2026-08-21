<?php

declare(strict_types=1);

namespace Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Controller\ApiController;
use OCA\SmartMigration\Db\Setting;
use OCA\SmartMigration\Db\SettingMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\ServerVersion;
use PHPUnit\Framework\TestCase;

final class ApiControllerTest extends TestCase {
	private SettingMapper $settingMapper;
	private IUserSession $userSession;
	private ApiController $controller;

	protected function setUp(): void {
		$request = $this->createMock(IRequest::class);
		$appManager = $this->createMock(IAppManager::class);
		$serverVersion = $this->createMock(ServerVersion::class);
		$this->settingMapper = $this->createMock(SettingMapper::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new ApiController(
			Application::APP_ID,
			$request,
			$appManager,
			$serverVersion,
			$this->settingMapper,
			$this->userSession,
		);
	}

	public function testGetLicenseReturnsNullsWhenNothingStored(): void {
		$this->settingMapper->method('findFirst')->willReturn(null);

		$data = $this->controller->getLicense()->getData();

		$this->assertNull($data['smServerName']);
		$this->assertNull($data['licenseKey']);
		$this->assertNull($data['expirationDate']);
		$this->assertNull($data['currentSmVersion']);
	}

	public function testGetLicenseReturnsStoredValues(): void {
		$setting = new Setting();
		$setting->setLicenseKey('SMART-1234');
		$setting->setExpirationDate(1800000000);
		$setting->setCurrentSmVersion('2.4.1');
		$setting->setSmServerName('SMART-PROD-01');
		$this->settingMapper->method('findFirst')->willReturn($setting);

		$data = $this->controller->getLicense()->getData();

		$this->assertEquals('SMART-PROD-01', $data['smServerName']);

		$this->assertEquals('SMART-1234', $data['licenseKey']);
		$this->assertEquals(1800000000, $data['expirationDate']);
		$this->assertEquals('2.4.1', $data['currentSmVersion']);
	}

	public function testSetLicenseStoresKeyAndExpiration(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('smartservice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$response = $this->controller->setLicense('SMART-1234', 1800000000);
		$data = $response->getData();

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('SMART-1234', $data['licenseKey']);
		$this->assertEquals(1800000000, $data['expirationDate']);
	}

	public function testSetLicenseAllowsNullExpiration(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$data = $this->controller->setLicense('SMART-1234')->getData();

		$this->assertEquals('SMART-1234', $data['licenseKey']);
		$this->assertNull($data['expirationDate']);
	}

	/** An empty, whitespace-only or omitted key clears the licence. */
	public function testSetLicenseClearsKeyWhenBlank(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$this->assertNull($this->controller->setLicense('   ')->getData()['licenseKey']);
		$this->assertNull($this->controller->setLicense('')->getData()['licenseKey']);
		$this->assertNull($this->controller->setLicense()->getData()['licenseKey']);
	}

	public function testSetLicenseRejectsOverlongKey(): void {
		$this->settingMapper->expects($this->never())->method('getOrCreate');

		$response = $this->controller->setLicense(str_repeat('A', 41));

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testGetSupportContactReturnsNullsWhenNothingStored(): void {
		$this->settingMapper->method('findFirst')->willReturn(null);

		$data = $this->controller->getSupportContact()->getData();

		$this->assertNull($data['supportName']);
		$this->assertNull($data['supportEmail']);
		$this->assertNull($data['supportCompany']);
	}

	public function testGetSupportContactReturnsStoredValues(): void {
		$setting = new Setting();
		$setting->setSupportName('Ida Berg');
		$setting->setSupportEmail('support@migratedms.com');
		$setting->setSupportCompany('MigrateDMS');
		$this->settingMapper->method('findFirst')->willReturn($setting);

		$data = $this->controller->getSupportContact()->getData();

		$this->assertEquals('Ida Berg', $data['supportName']);
		$this->assertEquals('support@migratedms.com', $data['supportEmail']);
		$this->assertEquals('MigrateDMS', $data['supportCompany']);
	}

	public function testSetSupportContactStoresAllThreeFields(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('smartservice');
		$this->userSession->method('getUser')->willReturn($user);

		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$response = $this->controller->setSupportContact(' Ida Berg ', 'support@migratedms.com', 'MigrateDMS');
		$data = $response->getData();

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals('Ida Berg', $data['supportName']);
		$this->assertEquals('support@migratedms.com', $data['supportEmail']);
		$this->assertEquals('MigrateDMS', $data['supportCompany']);
	}

	public function testSetSupportContactTreatsBlankAsNull(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$data = $this->controller->setSupportContact('   ', null, 'MigrateDMS')->getData();

		$this->assertNull($data['supportName']);
		$this->assertNull($data['supportEmail']);
		$this->assertEquals('MigrateDMS', $data['supportCompany']);
	}

	/** All three support fields clear the same way, with an empty or whitespace value. */
	public function testSetSupportContactClearsAllThreeWithBlanks(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$data = $this->controller->setSupportContact('', '   ', '')->getData();

		$this->assertNull($data['supportName']);
		$this->assertNull($data['supportEmail']);
		$this->assertNull($data['supportCompany']);
	}

	public function testSetSupportContactRejectsOverlongField(): void {
		$this->settingMapper->expects($this->never())->method('getOrCreate');

		$response = $this->controller->setSupportContact(null, str_repeat('a', 51));

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertStringContainsString('supportEmail', $response->getData()['message']);
	}

	public function testSetLicenseStoresCurrentSmVersion(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$data = $this->controller->setLicense('SMART-1234', null, ' 2.4.1 ')->getData();

		$this->assertEquals('2.4.1', $data['currentSmVersion']);
	}

	public function testSetLicenseDefaultsCurrentSmVersionToNull(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$data = $this->controller->setLicense('SMART-1234')->getData();

		$this->assertNull($data['currentSmVersion']);
	}

	public function testSetLicenseStoresAndClearsServerName(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$stored = $this->controller->setLicense('SMART-1234', null, null, ' SMART-PROD-01 ')->getData();
		$this->assertEquals('SMART-PROD-01', $stored['smServerName']);

		$this->assertNull($this->controller->setLicense('SMART-1234', null, null, '')->getData()['smServerName']);
		$this->assertNull($this->controller->setLicense('SMART-1234')->getData()['smServerName']);
	}

	public function testSetLicenseRejectsOverlongServerName(): void {
		$this->settingMapper->expects($this->never())->method('getOrCreate');

		$response = $this->controller->setLicense('SMART-1234', null, null, str_repeat('n', 65));

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertStringContainsString('smServerName', $response->getData()['message']);
	}

	/** 0 means "no expiry", so an empty request value does not store 1 Jan 1970. */
	public function testSetLicenseTreatsZeroExpirationAsNull(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$this->assertNull($this->controller->setLicense('SMART-1234', 0)->getData()['expirationDate']);
	}

	/** Sending an empty or whitespace value is a supported way to clear the reported version. */
	public function testSetLicenseTreatsBlankCurrentSmVersionAsNull(): void {
		$this->userSession->method('getUser')->willReturn(null);
		$this->settingMapper->method('getOrCreate')->willReturn(new Setting());
		$this->settingMapper->method('update')
			->willReturnCallback(static fn (Setting $setting) => $setting);

		$this->assertNull($this->controller->setLicense('SMART-1234', null, '')->getData()['currentSmVersion']);
		$this->assertNull($this->controller->setLicense('SMART-1234', null, '   ')->getData()['currentSmVersion']);
	}

	public function testSetLicenseRejectsOverlongCurrentSmVersion(): void {
		$this->settingMapper->expects($this->never())->method('getOrCreate');

		$response = $this->controller->setLicense('SMART-1234', null, str_repeat('9', 21));

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertStringContainsString('currentSmVersion', $response->getData()['message']);
	}
}
