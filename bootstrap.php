<?php
require_once "vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array(__DIR__.'/src');
$isDevMode = true;

$conn = array(
    'driver'    =>  'pdo_mysql',
    'user'      =>  'root',
    'password'  =>  '123456',
    'dbname'    =>  'doctrine_test',
);

$config = Setup::createAnnotationMetadataConfiguration($paths,$isDevMode);
$entityManager = EntityManager::create($conn,$config);
