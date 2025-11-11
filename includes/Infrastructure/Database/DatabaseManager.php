<?php

declare(strict_types=1);

/**
 * Database Manager
 *
 * @package RIILSA\Infrastructure\Database
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Database;

/**
 * Database manager for handling database operations
 * 
 * Pattern: Manager Pattern
 * This class manages database schema and migrations
 */
class DatabaseManager
{
    /**
     * WordPress database interface
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;
    
    /**
     * Database version option key
     *
     * @var string
     */
    private const DB_VERSION_KEY = 'riilsa_db_version';
    
    /**
     * Current database schema version
     *
     * @var string
     */
    private const CURRENT_DB_VERSION = '3.1.0';
    
    /**
     * Constructor
     *
     * @param \wpdb $wpdb
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }
    
    /**
     * Initialize database
     *
     * @return void
     */
    public function init(): void
    {
        $installedVersion = get_option(self::DB_VERSION_KEY, '0.0.0');
        
        if (version_compare($installedVersion, self::CURRENT_DB_VERSION, '<')) {
            $this->runMigrations($installedVersion);
        }
    }
    
    /**
     * Create database tables
     *
     * @return void
     */
    public function createTables(): void
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charsetCollate = $this->wpdb->get_charset_collate();
        
        // Newsletter emails table
        $sqlEmailTable = "CREATE TABLE IF NOT EXISTS " . RIILSA_TABLE_EMAIL . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            dependency_id int(11) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            external_id varchar(255) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            subscribed_at datetime NOT NULL,
            confirmed_at datetime DEFAULT NULL,
            unsubscribed_at datetime DEFAULT NULL,
            last_activity_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY dependency_id (dependency_id),
            KEY status (status),
            KEY subscribed_at (subscribed_at)
        ) $charsetCollate;";
        
        // Newsletter email tokens table
        $sqlTokenTable = "CREATE TABLE IF NOT EXISTS " . RIILSA_TABLE_EMAIL_TOKENS . " (
            email varchar(255) NOT NULL,
            token varchar(64) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (email),
            UNIQUE KEY token (token),
            KEY expires_at (expires_at)
        ) $charsetCollate;";
        
        // Newsletter logs table
        $sqlLogsTable = "CREATE TABLE IF NOT EXISTS " . RIILSA_TABLE_NEWSLETTER_LOGS . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            number int(11) NOT NULL,
            id_status int(11) NOT NULL DEFAULT 1,
            status varchar(20) DEFAULT 'draft',
            news_collection text,
            id_news text,
            text_header text,
            html_content longtext,
            scheduled_at datetime DEFAULT NULL,
            sent_at datetime DEFAULT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime DEFAULT NULL,
            statistics longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY number (number),
            KEY id_status (id_status),
            KEY scheduled_at (scheduled_at),
            KEY sent_at (sent_at)
        ) $charsetCollate;";
        
        // Dependency catalog table
        $sqlDependencyTable = "CREATE TABLE IF NOT EXISTS " . RIILSA_TABLE_DEPENDENCY_CATALOG . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            description varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY description (description)
        ) $charsetCollate;";
        
        // Execute table creation
        dbDelta($sqlEmailTable);
        dbDelta($sqlTokenTable);
        dbDelta($sqlLogsTable);
        dbDelta($sqlDependencyTable);
        
        // Update database version
        update_option(self::DB_VERSION_KEY, self::CURRENT_DB_VERSION);
        
        debugLog('Database tables created/updated successfully', 'info');
    }
    
    /**
     * Run database migrations
     *
     * @param string $fromVersion
     * @return void
     */
    private function runMigrations(string $fromVersion): void
    {
        debugLog("Running database migrations from version {$fromVersion}", 'info');
        
        // Run migrations in order
        if (version_compare($fromVersion, '3.0.0', '<')) {
            $this->migrateToV300();
        }
        
        if (version_compare($fromVersion, '3.1.0', '<')) {
            $this->migrateToV310();
        }
        
        // Update version
        update_option(self::DB_VERSION_KEY, self::CURRENT_DB_VERSION);
        
        debugLog('Database migrations completed successfully', 'info');
    }
    
    /**
     * Migrate to version 3.0.0
     *
     * @return void
     */
    private function migrateToV300(): void
    {
        // Create all tables
        $this->createTables();
    }
    
    /**
     * Migrate to version 3.1.0
     *
     * @return void
     */
    private function migrateToV310(): void
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charsetCollate = $this->wpdb->get_charset_collate();
        
        // Add new columns if needed
        $this->addColumnIfNotExists(
            RIILSA_TABLE_EMAIL,
            'external_id',
            'VARCHAR(255) DEFAULT NULL AFTER status'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_EMAIL,
            'ip_address',
            'VARCHAR(45) DEFAULT NULL AFTER external_id'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_EMAIL,
            'user_agent',
            'VARCHAR(255) DEFAULT NULL AFTER ip_address'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_EMAIL,
            'last_activity_at',
            'DATETIME DEFAULT NULL AFTER unsubscribed_at'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_NEWSLETTER_LOGS,
            'html_content',
            'LONGTEXT DEFAULT NULL AFTER text_header'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_NEWSLETTER_LOGS,
            'scheduled_at',
            'DATETIME DEFAULT NULL AFTER html_content'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_NEWSLETTER_LOGS,
            'sent_at',
            'DATETIME DEFAULT NULL AFTER scheduled_at'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_NEWSLETTER_LOGS,
            'date_updated',
            'DATETIME DEFAULT NULL AFTER date_created'
        );
        
        $this->addColumnIfNotExists(
            RIILSA_TABLE_NEWSLETTER_LOGS,
            'statistics',
            'LONGTEXT DEFAULT NULL AFTER date_updated'
        );
    }
    
    /**
     * Add column if it doesn't exist
     *
     * @param string $table
     * @param string $column
     * @param string $definition
     * @return void
     */
    private function addColumnIfNotExists(string $table, string $column, string $definition): void
    {
        $columnExists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table,
            $column
        ));
        
        if (!$columnExists) {
            $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
            $this->wpdb->query($sql);
            
            debugLog("Added column {$column} to table {$table}", 'info');
        }
    }
    
    /**
     * Drop table if exists
     *
     * @param string $tableName
     * @return void
     */
    public function dropTable(string $tableName): void
    {
        $this->wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
    
    /**
     * Truncate table
     *
     * @param string $tableName
     * @return void
     */
    public function truncateTable(string $tableName): void
    {
        $this->wpdb->query("TRUNCATE TABLE {$tableName}");
    }
    
    /**
     * Check if table exists
     *
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        $table = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tableName
        ));
        
        return $table === $tableName;
    }
    
    /**
     * Get database version
     *
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return get_option(self::DB_VERSION_KEY, '0.0.0');
    }
}
