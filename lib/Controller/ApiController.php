<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Db\SettingMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\ServerVersion;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IAppManager $appManager,
		private readonly ServerVersion $serverVersion,
		private readonly SettingMapper $settingMapper,
		private readonly IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Report app and Nextcloud version
	 *
	 * Lets SMART Migration check compatibility before it starts polling for jobs.
	 *
	 * @return DataResponse<Http::STATUS_OK, array{appId: string, appVersion: string, apiVersion: string, requiredSmartVersion: string, nextcloudVersion: string, nextcloudVersionMajor: int}, array{}>
	 *
	 * 200: Version info returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/version')]
	public function version(): DataResponse {
		return new DataResponse([
			'appId' => Application::APP_ID,
			'appVersion' => $this->appManager->getAppVersion(Application::APP_ID),
			'apiVersion' => 'v1',
			'requiredSmartVersion' => Application::REQUIRED_SMART_VERSION,
			'nextcloudVersion' => $this->serverVersion->getVersionString(),
			'nextcloudVersionMajor' => $this->serverVersion->getMajorVersion(),
		]);
	}

	/**
	 * Read the stored SMART Migration license and reported server identity
	 *
	 * @return DataResponse<Http::STATUS_OK, array{smServerName: ?string, licenseKey: ?string, expirationDate: ?int, currentSmVersion: ?string}, array{}>
	 *
	 * 200: License returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/settings/license')]
	public function getLicense(): DataResponse {
		$setting = $this->settingMapper->findFirst();

		return new DataResponse([
			'smServerName' => $setting?->getSmServerName(),
			'licenseKey' => $setting?->getLicenseKey(),
			'expirationDate' => $setting?->getExpirationDate(),
			'currentSmVersion' => $setting?->getCurrentSmVersion(),
		]);
	}

	/**
	 * Store the SMART Migration license key and its expiration
	 *
	 * SMART Migration writes this after the customer activates a licence; the
	 * Settings tab only ever displays it. Both fields are replaced on every call,
	 * so send them together.
	 *
	 * @param string|null $smServerName Name the SMART Migration server calls itself, max 64 characters, or null/empty to clear it
	 * @param string|null $licenseKey The licence key, max 40 characters, or null/empty to clear it
	 * @param int|null $expirationDate Expiry as unix seconds, or null/0 if it does not expire
	 * @param string|null $currentSmVersion Version the SMART Migration server reports about itself, max 20 characters
	 *
	 * @return DataResponse<Http::STATUS_OK, array{smServerName: ?string, licenseKey: ?string, expirationDate: ?int, currentSmVersion: ?string}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: License stored
	 * 400: Server name, license key or version too long
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/settings/license')]
	public function setLicense(
		?string $licenseKey = null,
		?int $expirationDate = null,
		?string $currentSmVersion = null,
		?string $smServerName = null,
	): DataResponse {
		$smServerName = $smServerName === null ? null : trim($smServerName);
		// The column is 64 characters; reject rather than silently truncating.
		if ($smServerName !== null && mb_strlen($smServerName) > 64) {
			return new DataResponse(
				['message' => 'smServerName must not exceed 64 characters'],
				Http::STATUS_BAD_REQUEST,
			);
		}

		$licenseKey = $licenseKey === null ? null : trim($licenseKey);

		// The column is 40 characters; reject rather than silently truncating.
		if ($licenseKey !== null && mb_strlen($licenseKey) > 40) {
			return new DataResponse(
				['message' => 'licenseKey must not exceed 40 characters'],
				Http::STATUS_BAD_REQUEST,
			);
		}

		// Nextcloud's dispatcher casts an empty request value to 0 for an int parameter
		// (nullability is discarded by the reflector), so an empty expirationDate would
		// otherwise store 1 Jan 1970. Treat 0 as "no expiry", which is what it means.
		if ($expirationDate === 0) {
			$expirationDate = null;
		}

		$currentSmVersion = $currentSmVersion === null ? null : trim($currentSmVersion);
		// The column is 20 characters; reject rather than silently truncating.
		if ($currentSmVersion !== null && mb_strlen($currentSmVersion) > 20) {
			return new DataResponse(
				['message' => 'currentSmVersion must not exceed 20 characters'],
				Http::STATUS_BAD_REQUEST,
			);
		}

		$setting = $this->settingMapper->getOrCreate($this->userSession->getUser()?->getUID() ?? '');
		$setting->setSmServerName($smServerName === '' ? null : $smServerName);
		$setting->setLicenseKey($licenseKey === '' ? null : $licenseKey);
		$setting->setExpirationDate($expirationDate);
		$setting->setCurrentSmVersion($currentSmVersion === '' ? null : $currentSmVersion);
		$setting->setUpdatedAt(time());
		$setting = $this->settingMapper->update($setting);

		return new DataResponse([
			'smServerName' => $setting->getSmServerName(),
			'licenseKey' => $setting->getLicenseKey(),
			'expirationDate' => $setting->getExpirationDate(),
			'currentSmVersion' => $setting->getCurrentSmVersion(),
		]);
	}

	/**
	 * Read the stored support contact
	 *
	 * @return DataResponse<Http::STATUS_OK, array{supportName: ?string, supportEmail: ?string, supportCompany: ?string}, array{}>
	 *
	 * 200: Support contact returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/settings/support')]
	public function getSupportContact(): DataResponse {
		$setting = $this->settingMapper->findFirst();

		return new DataResponse([
			'supportName' => $setting?->getSupportName(),
			'supportEmail' => $setting?->getSupportEmail(),
			'supportCompany' => $setting?->getSupportCompany(),
		]);
	}

	/**
	 * Store the support contact shown on the Support tab
	 *
	 * Who the customer calls when a migration goes wrong. SMART Migration writes
	 * this; the UI only ever displays it. All three fields are replaced on every
	 * call, so send them together.
	 *
	 * @param string|null $supportName Contact person, max 50 characters
	 * @param string|null $supportEmail Contact email address, max 50 characters
	 * @param string|null $supportCompany Contact company, max 50 characters
	 *
	 * @return DataResponse<Http::STATUS_OK, array{supportName: ?string, supportEmail: ?string, supportCompany: ?string}, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{message: string}, array{}>
	 *
	 * 200: Support contact stored
	 * 400: A field exceeds 50 characters
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1/settings/support')]
	public function setSupportContact(
		?string $supportName = null,
		?string $supportEmail = null,
		?string $supportCompany = null,
	): DataResponse {
		$fields = [
			'supportName' => $supportName,
			'supportEmail' => $supportEmail,
			'supportCompany' => $supportCompany,
		];

		foreach ($fields as $name => $value) {
			$value = $value === null ? null : trim($value);
			// The columns are 50 characters; reject rather than silently truncating.
			if ($value !== null && mb_strlen($value) > 50) {
				return new DataResponse(
					['message' => $name . ' must not exceed 50 characters'],
					Http::STATUS_BAD_REQUEST,
				);
			}
			$fields[$name] = ($value === null || $value === '') ? null : $value;
		}

		$setting = $this->settingMapper->getOrCreate($this->userSession->getUser()?->getUID() ?? '');
		$setting->setSupportName($fields['supportName']);
		$setting->setSupportEmail($fields['supportEmail']);
		$setting->setSupportCompany($fields['supportCompany']);
		$setting->setUpdatedAt(time());
		$setting = $this->settingMapper->update($setting);

		return new DataResponse([
			'supportName' => $setting->getSupportName(),
			'supportEmail' => $setting->getSupportEmail(),
			'supportCompany' => $setting->getSupportCompany(),
		]);
	}
}
