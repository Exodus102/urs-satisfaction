<?php

// This file now loads environment variables from the .env file
// and defines them as constants for the rest of the application.

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('RESEND_API_KEY', $_ENV['RESEND_API_KEY']);
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS']);
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME']);
