<?php
/**
 * Rol Debug Script
 */

require_once 'config.php';
require_once 'includes/Auth.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Rol Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>🔍 ROL DEBUG</h1>";

try {
    $auth = new Auth($pdo);
    $userId = 5; // superadmin
    
    echo "<h2>Test 1: Direct SQL Query</h2>";
    $stmt = $pdo->prepare("
        SELECT r.role_code 
        FROM USER_ROLES ur 
        JOIN ROLES r ON ur.role_id = r.id 
        WHERE ur.user_id = ? AND r.is_active = 1
    ");
    $stmt->execute([$userId]);
    
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        echo "Role Code: " . $row['role_code'] . "\n";
    }
    echo "</pre>";
    
    echo "<h2>Test 2: getUserRoles() Method</h2>";
    $roles = $auth->getUserRoles($userId);
    
    echo "<pre>";
    echo "Return Type: " . gettype($roles) . "\n";
    echo "Count: " . count($roles) . "\n";
    print_r($roles);
    echo "</pre>";
    
    echo "<h2>Test 3: All Roles</h2>";
    $allRolesStmt = $pdo->query("SELECT * FROM ROLES");
    $allRoles = $allRolesStmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Active</th></tr>";
    foreach ($allRoles as $role) {
        echo "<tr>";
        echo "<td>{$role['id']}</td>";
        echo "<td>{$role['role_name']}</td>";
        echo "<td>{$role['role_code']}</td>";
        echo "<td>" . ($role['is_active'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Test 4: User Roles</h2>";
    $userRolesStmt = $pdo->query("
        SELECT ur.*, r.role_name, r.role_code 
        FROM USER_ROLES ur 
        JOIN ROLES r ON ur.role_id = r.id
    ");
    $userRoles = $userRolesStmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Role ID</th><th>Role Name</th><th>Role Code</th></tr>";
    foreach ($userRoles as $ur) {
        echo "<tr>";
        echo "<td>{$ur['id']}</td>";
        echo "<td>{$ur['user_id']}</td>";
        echo "<td>{$ur['role_id']}</td>";
        echo "<td>{$ur['role_name']}</td>";
        echo "<td>{$ur['role_code']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Test 5: hasPermission Test</h2>";
    $testPermissions = ['user.view', 'user.create', 'import.view', 'config.edit'];
    
    echo "<ul>";
    foreach ($testPermissions as $perm) {
        $has = $auth->hasPermission($perm, $userId);
        echo "<li class='" . ($has ? 'success' : 'error') . "'>";
        echo "$perm: " . ($has ? '✅ OK' : '❌ YOK');
        echo "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
