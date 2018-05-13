<?php
declare(strict_types=1); // on PHP 7+ are standard PHP methods strict to types of given parameters

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(-1);
ini_set('display_errors', '1');

ini_set('xdebug.max_nesting_level', '100');

if (is_readable(__DIR__ . '/tests_config.php')) {
    // const FREE_ACCESS = true; // covered by JUST_TEXT_TESTING
    // const JUST_TEXT_TESTING = true;
    // const NO_EXTERNAL_ANCHORS_WITH_HASH_EXPECTED = true;
    // const NOT_FOR_PLAYERS = true;
    include_once __DIR__ . '/tests_config.php';
    if (\defined('JUST_TEXT_TESTING') && !\defined('FREE_ACCESS')) {
        // free access is a subset of 'just a text'
        define('FREE_ACCESS', JUST_TEXT_TESTING);
    }

}
const DRD_PLUS_RULES_INDEX_FILE_NAME_TO_TEST = __DIR__ . '/../index.php';