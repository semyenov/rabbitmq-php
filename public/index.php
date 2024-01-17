<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Publisher;

$publisher = new Publisher('rabbit', 5672, 'guest', 'guest');
$publisher->sendQueries([0 => 'SELECT 1', 1 => 'SELECT 2']);
$publisher->close();