<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\SmartMigration\AppInfo\Application::APP_ID, OCA\SmartMigration\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\SmartMigration\AppInfo\Application::APP_ID, OCA\SmartMigration\AppInfo\Application::APP_ID . '-main');

?>

<div id="smartmigration"></div>
