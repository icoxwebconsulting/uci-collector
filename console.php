#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;

AnnotationDriver::registerAnnotationClasses();

$config = new Configuration();
$config->setProxyDir('./cache/doctrine');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir('./cache/doctrine');
$config->setHydratorNamespace('Hydrators');
$config->setMetadataDriverImpl(AnnotationDriver::create('./Collector'));

$connection = new Connection('mongo');
$dm = DocumentManager::create($connection, $config);

$streamHandler = new RotatingFileHandler('logs/collector.log', 10, Logger::DEBUG);

$logger = new Logger('uci');
$logger->pushHandler($streamHandler);

$application = new Application();

$application->add(new SICCollectorCommand($dm, $logger->withName('SIC-COLLECTOR')));
$application->add(new CompanyCollectorCommand($dm, $logger->withName('COMPANY-COLLECTOR')));

$application->run();