<?php
define('IS_LOCAL', isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'localhost'));

if (IS_LOCAL) {
    define('BASE_URL', 'http://localhost/ITECA_SumativeAssessment');
    define('DB_HOST', '127.0.0.1');
    define('DB_NAME', 'queue_stander');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('BASE_URL', 'https://queue-stand.infinityfree.me');
    define('DB_HOST', 'sql208.infinityfree.com');
    define('DB_NAME', 'if0_42081203_queue_stand');
    define('DB_USER', 'if0_42081203');
    define('DB_PASS', 'Ehvgta5k');
}
