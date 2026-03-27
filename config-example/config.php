<?php

// Konfiguracja aplikacji

// Ustawienia podstawowe
define('BASE_URL', 'http://localhost/php-backend-vue3-frontend-one-repo/');
define('BASE_PATH', '/php-backend-vue3-frontend-one-repo/api'); // remove this path from path used in app, remember to update .htaccess accordingly

// Ustawienia email
define('CONFIRM_EMAIL_URL', 'http://localhost/php-backend-vue3-frontend-one-repo/email/confirm/');
define('EMAIL_FROM', 'admin@example.pl');

// Ustawienia uploadu plików
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500 MB

// Ustawienia bazy danych
define('DB_HOST', 'localhost');
define('DB_NAME', 'myapp');
define('DB_USER', 'root');
define('DB_PASS', '');

// Ustawienia aplikacji
define('APP_NAME', 'MYAPP API');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, production

// Ustawienia bezpieczeństwa
define('API_KEY_REQUIRED', false);
define('CORS_ENABLED', true);

// Ustawienia debugowania
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);

// Timezone
date_default_timezone_set('Europe/Warsaw');

// Error reporting w zależności od środowiska
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
