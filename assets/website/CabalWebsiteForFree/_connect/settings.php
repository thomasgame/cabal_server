<?php
function website_env($name, $default) {
    $value = getenv($name);
    return $value === false ? $default : $value;
}

define('TittleWeb', website_env('WEBSITE_TITLE', 'CABAL ONLINE'));
define('CABALNAME1', website_env('WEBSITE_NAME_PRIMARY', 'CABAL'));
define('CABALNAME2', website_env('WEBSITE_NAME_SECONDARY', 'ONLINE'));
define('Title1', CABALNAME1);
define('Title2', CABALNAME2);
define('logo', website_env('WEBSITE_LOGO', 'images/logo-vertu.png'));
define('NewServer', website_env('WEBSITE_SERVER_LABEL', 'New Server'));
define('Download', website_env('WEBSITE_DOWNLOAD_LABEL', 'Download Now!'));

define('WEB_MAINTENANCE', (int) website_env('WEBSITE_MAINTENANCE', 0));
define('WEB_SCHED', website_env('WEBSITE_MAINTENANCE_UNTIL', 'January 24, 2026 15:30:00'));

define('DB_ACC', website_env('WEBSITE_DB_ACCOUNT', 'Account'));
define('DB_CSH', website_env('WEBSITE_DB_CASH', 'CabalCash'));
define('DB_GAME', website_env('WEBSITE_DB_GAME', 'Server01'));
define('DB_NETC', website_env('WEBSITE_DB_NETCAFE', 'NetcafeBilling'));
define('DB_EVENT', website_env('WEBSITE_DB_EVENT', 'EventData'));
define('DB_SITE', website_env('WEBSITE_DB_SITE', 'reygie'));
