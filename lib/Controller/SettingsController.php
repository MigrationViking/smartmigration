<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCA\SmartMigration\Db\SettingMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * Internal, admin-only endpoints backing the Settings and Support tabs in the Vue UI.
 *
 * Read-only by design: the licence and the support contact are written by SMART
 * Migration through `PUT /ocs/v2.php/apps/smartmigration/api/v1/settings/...`,
 * never from the UI.
 *
 * @psalm-suppress UnusedClass
 */
class SettingsController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SettingMapper $settingMapper,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, array{smServerName: ?string, licenseKey: ?string, expirationDate: ?int, currentSmVersion: ?string, requiredSmartVersion: string}, array{}>
	 */
	#[FrontpageRoute(verb: 'GET', url: '/settings/license')]
	public function license(): DataResponse {
		$setting = $this->settingMapper->findFirst();

		return new DataResponse([
			'smServerName' => $setting?->getSmServerName(),
			'licenseKey' => $setting?->getLicenseKey(),
			'expirationDate' => $setting?->getExpirationDate(),
			'currentSmVersion' => $setting?->getCurrentSmVersion(),
			'requiredSmartVersion' => Application::REQUIRED_SMART_VERSION,
		]);
	}

	/**
	 * @return DataResponse<Http::STATUS_OK, array{supportName: ?string, supportEmail: ?string, supportCompany: ?string}, array{}>
	 */
	#[FrontpageRoute(verb: 'GET', url: '/settings/support')]
	public function support(): DataResponse {
		$setting = $this->settingMapper->findFirst();

		return new DataResponse([
			'supportName' => $setting?->getSupportName(),
			'supportEmail' => $setting?->getSupportEmail(),
			'supportCompany' => $setting?->getSupportCompany(),
		]);
	}
}
