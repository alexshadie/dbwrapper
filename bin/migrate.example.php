<?php

require __DIR__ . '/../vendor/autoload.php';

$command = $argv[1] ?? "migrate";

$logger = new \Psr\Log\NullLogger();

$migrator = new \alexshadie\dbwrapper\DBMigrator(
    'mysql://localhost/dbname',
    'user',
    'password',
    __DIR__ . "/__db/",
    $logger
);

switch ($command) {
    case "migrate":
        $migrator->migrate();
        break;

    case "initialize":
        $migrator->initialize();
        break;

    case "create":
        $migrator->create();
        break;

    default:
        $logger->critical("Unknown arg. Valid args: migrate, initialize, create");
}
