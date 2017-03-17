<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$bootstrap	=	realpath(__DIR__.'/../../../../tests/Functional/bootstrap.php');

if(!$bootstrap){

	throw new \RuntimeException("Could not find Shopware's functional test bootstrap.php file");

}

$autoload	=	realpath(__DIR__.'/../vendor/autoload.php');

if(!$autoload){

	throw new \RuntimeException("Composer autoload.php file not found, perhaps you forgot to run composer install?");

}

$baseTestFile	=	realpath(__DIR__.'/Base.php');

if(!$baseTestFile){

	throw new \RuntimeException("Base test file not found!");

}

require $bootstrap;
require $autoload;
require $baseTestFile;
