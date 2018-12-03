<?php


namespace alexshadie\dbwrapper;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DBMigrator implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    /** @var DBInterface */
    private $mysql;
    private $migrationPath;
    private $host;
    private $dbname;
    private $user;
    private $password;

    public function __construct(
        string $dsn,
        string $user,
        string $password,
        string $migrationPath,
        ?LoggerInterface $logger
    )
    {
        $this->setLogger($logger ?? new NullLogger());
        $this->migrationPath = $migrationPath;

        $m = [];
        preg_match("!mysql:host=(\w+);dbname=(\w+)(;|$)!Uism", $dsn, $m);
        $this->host = $m[1];
        $this->dbname = $m[2];
        $this->user = $user;
        $this->password = $password;

        $this->mysql = new Mysql(new \PDO($dsn, $user, $password));
    }

    public function migrate()
    {
        $this->logger->info("Started migrating");
        $allMigrations = $this->getMigrationList();
        $appliedMigrations = $this->getAppliedMigrations();

        $newMigrations = array_diff($allMigrations, $appliedMigrations);

        if (!$newMigrations) {
            $this->logger->info("No new migrations");
        }
        foreach ($newMigrations as $file) {
            echo "Importing {$file}\n";
            $this->importFile($file);
        }

        file_put_contents($this->migrationPath . ".checksum.md5", $this->getMigrationChecksum());
    }

    public function initialize()
    {
        $this->logger->info("Creating initial migration structure");
        $allMigrations = $this->getMigrationList();
        $appliedMigrations = $this->getAppliedMigrations();

        if (count($appliedMigrations)) {
            throw new \Exception("Already initialized");
        }

        $newMigrations = array_diff($allMigrations, $appliedMigrations);
        if (!$newMigrations) {
            $this->logger->info("No new migrations");
        }
        foreach ($newMigrations as $file) {
            $this->insertFile($file);
        }
    }

    public function create()
    {
        $fname = "migration." . date("YmdHis") . ".sql";
        file_put_contents($this->migrationPath . $fname, "");
        $this->logger->info("Created new migration {$fname}");
    }

    public function importFile($filename)
    {
        $cmd = "MYSQL_PWD={$this->password} mysql -hlocalhost -u{$this->user} -D{$this->dbname} < {$this->migrationPath}{$filename}";
        exec($cmd, $out, $retval);

        if ($retval !== 0) {
            $this->logger->critical("Cannot import file {$filename}");
            die();
        }

        $this->mysql->insert('migrations', [
            'name' => $filename,
            'applied' => date('Y-m-d H:i:s', time()),
        ]);
    }

    public function insertFile($filename)
    {
        $this->mysql->insert('migrations', [
            'name' => $filename,
            'applied' => date('Y-m-d H:i:s', time()),
        ]);
    }

    public function getMigrationChecksum(): string
    {
        $out = "";
        exec("cat {$this->migrationPath}migration.*.sql | md5sum", $out);
        $out = trim(join($out));
        return $out;
    }

    public function getExistingMigrationChecksum(): string
    {
        if (!is_file($this->migrationPath . ".checksum.md5")) {
            return "";
        }
        return file_get_contents($this->migrationPath . ".checksum.md5");
    }

    public function getMigrationList(): array
    {
        $it = new \DirectoryIterator($this->migrationPath);
        $migrations = [];
        foreach ($it as $file) {
            if ($file->isDot()) {
                continue;
            }
            if (strpos($file->getFilename(), 'migration.') !== 0) {
                continue;
            }
            $this->logger->debug("Found {$file->getFilename()}");
            $migrations[] = $file->getFilename();
        }
        sort($migrations);
        return $migrations;
    }

    public function getAppliedMigrations(): array
    {
        $sql = "SELECT `name` FROM `migrations`";
        try {
            $list = $this->mysql->queryAll($sql);
            $result = [];
            foreach ($list as $item) {
                $result[] = $item['name'];
            }
            return $result;
        } catch (\Exception $e) {
            if ($e->getCode() === 1146) {
                $this->createMigrationsTable();
                return [];
            }
            throw $e;
        }
    }

    private function createMigrationsTable(): void
    {
        $this->logger->info("Creating migrations table");
        $sql = "CREATE TABLE `migrations` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NULL,
                  `applied` TIMESTAMP NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE INDEX `name_UNIQUE` (`name` ASC))";
        $this->mysql->query($sql);
    }
}