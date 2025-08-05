# Database Migration Scripts

This directory contains database migration scripts for the BH (Burning Heroes) application.

## Initial Setup

### 1. Create Initial Tables

Run the initial migration to set up all database tables:

```bash
php scripts/create_initial_tables.php
```

This script creates the following tables:
- `users` - User management and authentication
- `maps` - Map metadata and configuration
- `map_versions` - Map versioning with weighted criteria
- `map_traps` - Trap objects on maps
- `map_misc_objects` - Miscellaneous objects on maps
- `map_furnaces` - Furnace objects with gear and charm data

### 2. Migrate JSON Data (Optional)

If you have existing JSON data to migrate:

```bash
php scripts/migrate_json_to_db.php
```

This will import data from `storage/objects.json` into the database.

## Migration Scripts

### `create_initial_tables.php`
- **Purpose**: Creates all initial database tables with proper relationships and indexes
- **When to run**: First time setup, or when setting up a new environment
- **Safe to run multiple times**: Yes (uses `CREATE TABLE IF NOT EXISTS`)

### `migrate_json_to_db.php`
- **Purpose**: Migrates data from JSON file to database
- **When to run**: After initial tables are created, when you have JSON data to import
- **Safe to run multiple times**: No (will create duplicate maps)

### `add_furnace_gear_columns_migration.php`
- **Purpose**: Adds gear level and charm columns to existing furnace tables
- **When to run**: If you have an existing database without gear columns
- **Safe to run multiple times**: Yes (checks if columns exist first)

### `update_tables_to_utf8mb4.php`
- **Purpose**: Updates existing tables to use utf8mb4 character set
- **When to run**: If you have existing tables with different character sets
- **Safe to run multiple times**: Yes

### `fix_furnace_status_defaults.php`
- **Purpose**: Fixes furnace status defaults in existing data
- **When to run**: If you have existing furnace data with incorrect status values
- **Safe to run multiple times**: Yes

### `fix_missing_furnace_ids.php`
- **Purpose**: Fixes missing furnace IDs in existing data
- **When to run**: If you have existing furnace data without proper IDs
- **Safe to run multiple times**: Yes

### `check_furnace_ids.php`
- **Purpose**: Checks for duplicate or missing furnace IDs
- **When to run**: To verify data integrity
- **Safe to run multiple times**: Yes (read-only)

## Database Schema

### Users Table
- User authentication and management
- Supports OTP, email verification, password reset
- Master user and approval system

### Maps Table
- Map metadata (name, cell size)
- Version control support
- Timestamps for tracking

### Map Versions Table
- Version control for maps
- JSON storage for weighted criteria
- Foreign key relationship to maps

### Map Objects Tables
- `map_traps`: Trap objects with position data
- `map_misc_objects`: Miscellaneous objects with size and name
- `map_furnaces`: Furnace objects with complete gear and charm data

## Running Migrations

1. Ensure your database configuration is correct in your `.env` file
2. Make sure your `.env` file has the correct database credentials
3. Run migrations from the project root directory
4. Check the output for any errors

## Troubleshooting

### Common Issues

1. **Database connection failed**
   - Check your database configuration
   - Ensure the database server is running
   - Verify credentials in `.env` file

2. **Permission denied**
   - Ensure the database user has CREATE TABLE permissions
   - Check file permissions for the scripts directory

3. **Character set issues**
   - Run `update_tables_to_utf8mb4.php` if you have existing tables
   - Ensure your database supports utf8mb4

4. **Foreign key constraints**
   - Tables are created in the correct order to avoid constraint issues
   - If you have existing data, ensure referential integrity

### Getting Help

If you encounter issues:
1. Check the error messages in the script output
2. Verify your database configuration
3. Ensure all required dependencies are installed
4. Check the database logs for additional error details 