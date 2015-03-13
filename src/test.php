<?php

include "../vendor/autoload.php";

define("ROOT_DIR", __DIR__);

$tests = array(
    new \Test\MainTest()
);

foreach($tests as $test)
{
    $test->runTest($test);
}