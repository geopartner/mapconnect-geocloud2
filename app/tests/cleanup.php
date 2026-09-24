<?php
/**
 * Cleanup script for codecept API tests
 * 
 * This script removes all test databases and users created by codecept API tests.
 * It should be run after tests complete to clean up the database.
 * 
 * Usage:
 *   php app/tests/cleanup.php
 *   OR
 *   php app/tests/cleanup.php --dry-run  # See what would be deleted without deleting
 *   OR
 *   php app/tests/cleanup.php --keep-days=7  # Keep users created in last 7 days
 */

// Setup autoloader
spl_autoload_register(function ($className) {
    $file = "/var/www/geocloud2/" . strtr($className, '\\', '/') . ".php";
    if (is_readable($file)) {
        require_once $file;
    }
});

use app\inc\Connection;

class TestCleanup
{
    private $dryRun = false;
    private $keepDays = 0;
    private $connection;
    private $pdo;
    private $deletedCount = 0;
    private $deletedDatabases = [];
    private $errors = [];

    // Test user name patterns
    private $testPatterns = [
        'test super user',
        'database test super user',
        'another database test super user',
        'second another database test super user',
        'test sub user',
    ];

    public function __construct()
    {
        // Users are stored in the "mapcentia" database
        $this->connection = new Connection(database: 'mapcentia');
        $this->connectToDatabase();
    }

    private function connectToDatabase()
    {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $this->connection->host,
                $this->connection->port,
                $this->connection->database
            );

            $this->pdo = new \PDO($dsn, $this->connection->user, $this->connection->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }

    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    }

    public function setKeepDays($days)
    {
        $this->keepDays = (int)$days;
    }

    public function run()
    {
        $this->log("Starting cleanup of test databases and users...");
        $this->log("Dry run: " . ($this->dryRun ? "YES" : "NO"));
        if ($this->keepDays > 0) {
            $this->log("Keep users created in last {$this->keepDays} days");
        }
        $this->log("");

        // Cleanup databases
        $this->log("=== Cleaning up test databases ===");
        $this->cleanupTestDatabases();

        // Cleanup users
        $this->log("\n=== Cleaning up test users ===");
        $testUsers = $this->findTestUsers();
        if (empty($testUsers)) {
            $this->log("No test users found to delete.");
        } else {
            $this->log("Found " . count($testUsers) . " test user(s):");
            foreach ($testUsers as $user) {
                $this->log("  - {$user['screenname']} ({$user['email']})");
            }
            $this->log("");
            foreach ($testUsers as $user) {
                $this->deleteUser($user);
            }
        }

        // Summary
        $this->log("\n=== Cleanup Summary ===");
        $this->log("Databases deleted: " . count($this->deletedDatabases));
        $this->log("Users deleted: " . $this->deletedCount);
        if (!empty($this->errors)) {
            $this->log("Errors: " . count($this->errors));
            foreach ($this->errors as $e) {
                $this->log("  - $e");
            }
        }
        if ($this->dryRun) {
            $this->log("\nDRY RUN: No changes were made.");
        }
    }

    private function cleanupTestDatabases()
    {
        try {
            $query = "SELECT datname FROM pg_database 
                     WHERE datname NOT IN ('postgres', 'template0', 'template1', 'mapcentia')
                     ORDER BY datname";
            
            $databases = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($databases as $row) {
                if ($this->isTestDatabase($row['datname'])) {
                    $this->dropDatabase($row['datname']);
                }
            }
        } catch (\Exception $e) {
            $this->error("Failed to query databases: " . $e->getMessage());
        }
    }

    private function isTestDatabase($dbName)
    {
        return $this->matchesTestPattern($dbName);
    }

    private function dropDatabase($database)
    {
        try {
            if (!$this->dryRun) {
                // Terminate connections first
                $query = "SELECT pg_terminate_backend(pid) FROM pg_stat_activity 
                         WHERE datname = :dbname AND pid <> pg_backend_pid()";
                $this->pdo->prepare($query)->execute([':dbname' => $database]);
                usleep(100000);
                
                // Drop the database
                $this->pdo->exec("DROP DATABASE IF EXISTS \"" . str_replace('"', '""', $database) . "\"");
            }
            $this->deletedDatabases[] = $database;
            $this->log(($this->dryRun ? "→ " : "✓ ") . "Dropped database: $database");
        } catch (\Exception $e) {
            $this->error("Failed to drop database $database: " . $e->getMessage());
        }
    }

    private function findTestUsers()
    {
        try {
            $query = "SELECT screenname, email FROM users ORDER BY screenname";
            $users = $this->pdo->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            return array_filter($users, fn($u) => 
                $this->isTestUser($u['screenname'], $u['email']) && 
                $this->shouldDeleteUser($u['screenname'])
            );
        } catch (\Exception $e) {
            $this->error("Failed to query users: " . $e->getMessage());
            return [];
        }
    }

    private function isTestUser($screenname, $email)
    {
        return $this->matchesTestPattern($screenname) || $this->matchesTestPattern($email);
    }

    private function matchesTestPattern($value)
    {
        $valueLower = strtolower($value);

        foreach ($this->testPatterns as $pattern) {
            $pattern = strtolower(str_replace(' ', '_', $pattern));
            if (stripos($valueLower, $pattern) !== false) {
                return true;
            }
        }

        return preg_match('/_\d{10}$/', $value) === 1;
    }

    private function shouldDeleteUser($screenname)
    {
        if ($this->keepDays <= 0) {
            return true;
        }
        
        if (preg_match('/(\d{10,})/', $screenname, $matches)) {
            $keepUntil = time() - ($this->keepDays * 24 * 60 * 60);
            return (int)$matches[1] < $keepUntil;
        }
        return true;
    }

    private function deleteUser($user)
    {
        try {
            if (!$this->dryRun) {
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE screenname = :screenname");
                $stmt->execute([':screenname' => $user['screenname']]);
            }
            $this->deletedCount++;
            $this->log(($this->dryRun ? "→ " : "✓ ") . "Deleted user: {$user['screenname']}");
        } catch (\Exception $e) {
            $this->error("Failed to delete user {$user['screenname']}: " . $e->getMessage());
        }
    }

    private function log($message)
    {
        echo $message . "\n";
    }

    private function error($message)
    {
        echo "ERROR: " . $message . "\n";
        $this->errors[] = $message;
    }
}

// Parse arguments and run cleanup
$dryRun = in_array('--dry-run', $argv);
$keepDays = 0;

foreach ($argv as $arg) {
    if (strpos($arg, '--keep-days=') === 0) {
        $keepDays = (int)substr($arg, strlen('--keep-days='));
    }
}

$cleanup = new TestCleanup();
$cleanup->setDryRun($dryRun);
$cleanup->setKeepDays($keepDays);
$cleanup->run();
