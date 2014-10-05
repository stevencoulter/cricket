<?php


require_once("entry.php");

use cricket\core\Dispatcher;

$dispatcher = new Dispatcher(null, null, null, array(
    '../lib/cricket' => '../lib/cricket',
));
$dispatcher->dispatchRequest();

