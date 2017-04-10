<?php

define('DEBUG',1);
define('WEBSITE',1);

include 'class.Bootstrap.php';

$bootstrap  =  new Bootstrap("config.php");

$app = $bootstrap->getApp();
$app->setConfig([ 'db' => ['host'=>'localhost', 'user'=>'www', 'pass'=>'junewind','db'=>'jwarp'] ]);
$debug = $app->getDebugger();

$db = $app->getDB();

$db->setLogFunction(
    function() {
        call_user_func_array( [ App::Debug(), 'log'], func_get_args() );
    }
);

$db->query("select * from jwarp.posts limit 10");

$db->query("select * from jwarp.posts limit 1");

$db->query("select 1 from DUAL");



Bootstrap::Goodbye();


