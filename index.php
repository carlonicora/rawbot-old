<?php declare(strict_types=1);
require_once 'vendor/autoload.php';

use carlonicora\minimalism\bootstrapper;
use carlonicora\rawbot\configurations;

$bootstrapper = new bootstrapper(configurations::class);
$controller = $bootstrapper->loadController('index');
$controller->render();

exit;