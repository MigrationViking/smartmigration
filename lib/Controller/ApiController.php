<?php

declare(strict_types=1);

namespace OCA\SmartMigration\Controller;

use OCA\SmartMigration\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
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
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api')]
	public function index(): DataResponse {
		return new DataResponse(
			['message' => 'Hello everyone2!']
		);
	}

	/**
	 * Report app and Nextcloud version
	 *
	 * Lets SMART Migration check compatibility before it starts polling for jobs.
	 *
	 * @return DataResponse<Http::STATUS_OK, array{appId: string, appVersion: string, apiVersion: string, nextcloudVersion: string, nextcloudVersionMajor: int}, array{}>
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
			'nextcloudVersion' => $this->serverVersion->getVersionString(),
			'nextcloudVersionMajor' => $this->serverVersion->getMajorVersion(),
		]);
	}
}
