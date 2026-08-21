<?php

declare(strict_types=1);

namespace OCA\SmartMigration\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'smartmigration';

	/**
	 * The minimum SMART Migration version this app's API contract works with.
	 *
	 * Maintained by hand — bump it when a change here needs a newer SMART
	 * Migration. Reported by `GET /api/v1/version` so SMART Migration can check
	 * itself before it starts polling. Max 10 characters.
	 */
	public const REQUIRED_SMART_VERSION = '7.96';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
	}
}
