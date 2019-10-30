<?php
define('TARALLO_DB_USER', 'user');
define('TARALLO_DB_PASS', 'pass');
define('TARALLO_DB_DSN', 'mysql:dbname=tarallo;host=127.0.0.1;charset=utf8mb4');
define('TARALLO_CACHE_ENABLED', false); // Set to true to enable FastRoute cache (use in production only, leave false in developement)
define('TARALLO_DEVELOPMENT_ENVIRONMENT', true); // Set to false or delete in production
define('TARALLO_POST_GRACE_TIME', 1800);
define('TARALLO_OIDC_ISSUER', 'https://sso.example.com/auth/realms/master');
define('TARALLO_OIDC_CLIENT_ID', 'tarallo');
define('TARALLO_OIDC_CLIENT_KEY', 'tarallo');
define('TARALLO_OIDC_CLIENT_SECRET', '');
define('TARALLO_OIDC_REFRESH_TOKEN_EXPIRY', 3600);
