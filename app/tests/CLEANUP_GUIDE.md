# GeoCloud2 API Test Cleanup Guide

This guide explains how to clean up databases and users created by the codecept API tests.

## Automatic Cleanup Script

### Basic Usage

After running API tests, you can clean up test artifacts with:

```bash
php app/tests/cleanup.php
```

### Options

#### Dry Run Mode
See what would be deleted without actually deleting:

```bash
php app/tests/cleanup.php --dry-run
```

#### Keep Recent Users
Keep users created in the last N days (useful for ongoing debugging):

```bash
php app/tests/cleanup.php --keep-days=7
```

Combine options:
```bash
php app/tests/cleanup.php --dry-run --keep-days=7
```

### What Gets Cleaned

The script automatically identifies and removes:

1. **Test Databases** - PostgreSQL databases created for test users matching these patterns:
   - Database names containing "test super user", "database test super user", etc.
   - Database names ending with timestamp patterns (e.g., `username_1234567890`)

2. **Test Users** - User records matching these patterns:
   - Names containing "test super user", "database test super user", etc.
   - Email addresses containing "test" or "database" followed by timestamps
   - Email addresses matching test@example.com patterns

## In-Test Cleanup

If you want to clean up within individual test classes, the Api helper provides methods:

### Example in Test Class

```php
class DatabaseManagementCest
{
    private $userAuthCookie;
    private $userId;
    private $subUserId;
    
    // ... test methods ...
    
    /**
     * Cleanup after all tests in this class
     */
    public function _after(ApiTester $I)
    {
        // Only cleanup if you have admin access
        if (!empty($this->userAuthCookie)) {
            $I->cleanupTestUsers([
                $this->subUserId,
                $this->userId
            ], $this->userAuthCookie);
        }
    }
}
```

## Recommended Workflow

### For Local Development
```bash
# Run tests
./vendor/bin/codecept run api

# Dry run to see what would be deleted
php app/tests/cleanup.php --dry-run

# Actually run cleanup
php app/tests/cleanup.php
```

### For CI/CD Pipelines
```bash
# Run tests (may fail)
./vendor/bin/codecept run api || true

# Always clean up, even if tests failed
php app/tests/cleanup.php
```

### For Debugging Failed Tests
```bash
# Keep users created in last 7 days for debugging
php app/tests/cleanup.php --keep-days=7

# Remove only older test artifacts
php app/tests/cleanup.php --keep-days=1
```

## How Test Users Are Identified

The cleanup script looks for users matching these patterns:

1. **In screenname (user ID)**:
   - `test super user`
   - `database test super user`
   - `another database test super user`
   - `second another database test super user`
   - `test sub user`

2. **In email**:
   - Pattern: `{pattern}test{timestamp}@example.com`
   - Examples: `databasetest1234567890@example.com`

3. **Email patterns with timestamps**:
   - Any email matching `*test*@example.com` format

## What Happens When Users Are Deleted

1. Schemas owned by the user (named after the screenname) are dropped with CASCADE
2. The user record is deleted from the `users` table
3. Any configurations or permissions associated with the user are cascade-deleted

## Troubleshooting

### Script Can't Connect to Database
- Ensure PostgreSQL is running
- Check connection parameters in `.env` or `app/conf/Connection.php`
- Verify database credentials are correct

### Permission Denied When Deleting
- The script runs as the PostgreSQL user configured in the application
- Ensure this user has permission to drop schemas and delete from the users table

### Users Still Remain After Cleanup
1. Check if they match the test patterns (run with `--dry-run`)
2. Users not matching patterns won't be deleted
3. Manually add patterns to the `$testPatterns` array if needed

## Manual Cleanup via SQL

If the script doesn't work, you can clean up manually:

```bash
# Connect to the database
psql -h localhost -U postgres geocloud2

# Find test users
SELECT screenname, email FROM users WHERE email LIKE '%test%@example.com' OR screenname LIKE '%test%';

# Delete specific user and schema
DROP SCHEMA IF EXISTS "username" CASCADE;
DELETE FROM users WHERE screenname = 'username';
```

## Adding Cleanup to New Test Classes

When creating new test classes that generate users/databases:

1. Track created usernames in instance variables
2. Implement `_after()` method to call `$I->cleanupTestUsers()`
3. Or rely on the automatic cleanup script after tests complete

Example:
```php
class YourNewCest
{
    private $testUsernames = [];
    
    public function testSomething(ApiTester $I)
    {
        // Create test user
        $username = 'testuser_' . time();
        $this->testUsernames[] = $username;
        // ... rest of test ...
    }
    
    public function _after(ApiTester $I)
    {
        $I->cleanupTestUsers($this->testUsernames, $this->authCookie);
    }
}
```
