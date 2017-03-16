<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$bootstrap	=	realpath(__DIR__.'/../../../../../tests/Functional/bootstrap.php');

if(!$bootstrap){

	throw new \RuntimeException("Could not find Shopware's functional test bootstrap.php file");

}

require $bootstrap;
