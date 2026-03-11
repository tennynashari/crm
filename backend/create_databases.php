<?php

/**
 * Script untuk create databases untuk multi-tenant setup
 * Run: php create_databases.php
 */

$host = 'localhost';
$port = '5432';
$user = 'postgres';
$password = 'postgres123';
$defaultDb = 'postgres'; // Connect ke default postgres database dulu

try {
    // Connect ke PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$defaultDb";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to PostgreSQL\n";
    
    // Create crm_master database
    echo "\nCreating crm_master database...\n";
    try {
        $pdo->exec("CREATE DATABASE crm_master OWNER postgres");
        echo "✓ crm_master database created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ crm_master database already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Create crm_ecogreen database
    echo "\nCreating crm_ecogreen database...\n";
    try {
        $pdo->exec("CREATE DATABASE crm_ecogreen OWNER postgres");
        echo "✓ crm_ecogreen database created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ crm_ecogreen database already exists\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n═══════════════════════════════════\n";
    echo "   Databases created successfully!\n";
    echo "═══════════════════════════════════\n";
    echo "Next steps:\n";
    echo "1. php artisan migrate --database=master --path=database/migrations/master\n";
    echo "2. php artisan tenant:create ecogreen andhia@ecogreen.id \"andhia123@@\"\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. PostgreSQL is running\n";
    echo "2. Credentials are correct (user: postgres, password: postgres123)\n";
    echo "3. Port 5432 is accessible\n";
    exit(1);
}
