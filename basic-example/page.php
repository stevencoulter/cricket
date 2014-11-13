<?php


require_once("entry.php");

use cricket\core\Dispatcher;

$dispatcher = new Dispatcher(null, null, null, array(
    '../lib/cricket' => '..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'cricket',
));
$dispatcher->dispatchRequest();

