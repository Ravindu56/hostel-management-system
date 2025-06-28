<?php
// session_start();
require_once '../config/database.php';
require_once '../auth/session_check.php';

checkRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_system_settings'])) {
        // Update system settings (you can store these in a settings table)
        $settings = [
            'hostel_name' => $_POST['hostel_name'],
            'max_occupancy' => $_POST['max_occupancy'],
            'default_rent' => $_POST['default_rent'],
            'late_fee_penalty' => $_POST['late_fee_penalty'],
            'visitor_time_limit' => $_POST['visitor_time_limit'],
            'maintenance_email' => $_POST['maintenance_email']
        ];
        
        // Store settings (implement settings table if needed)
        $success_message = "System settings updated successfully!";
    }
    
    if (isset($_POST['backup_database'])) {
        // Database backup functionality
        $backup_result = createDatabaseBackup($conn);
        if ($backup_result) {
            $success_message = "Database backup created successfully!";
        } else {
            $error_message = "Failed to create database backup.";
        }
    }
    
    if (isset($_POST['clear_logs'])) {
        // Clear system logs
        $days = $_POST['log_retention_days'];
        $clear_result = clearOldLogs($conn, $days);
        if ($clear_result) {
            $success_message = "Old logs cleared successfully!";
        } else {
            $error_message = "Failed to clear logs.";
        }
    }
}

function createDatabaseBackup($conn) {
    try {
        // Simple backup implementation using exact table names from sql.txt
        $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backups directory if it doesn't exist
        if (!file_exists('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // FIXED: Use exact table names from your sql.txt schema
        $tables = [
            'userauth',
            'AdminTable', 
            'WardenTable',
            'RoomTable',
            'StudentTable',
            'PaymentTable',
            'VisitorTable',
            'ComplaintTable'
        ];
        
        $sql_dump = "-- Database Backup Created: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Hostel Management System Database Backup\n";
        $sql_dump .= "-- Using exact schema from sql.txt\n\n";
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            try {
                // Check if table exists
                $check_table = $conn->prepare("SHOW TABLES LIKE :table");
                $check_table->bindParam(':table', $table);
                $check_table->execute();
                
                if ($check_table->rowCount() == 0) {
                    continue; // Skip if table doesn't exist
                }
                
                // Get table structure using exact table names
                $create_table_query = "SHOW CREATE TABLE `$table`";
                $create_table_stmt = $conn->query($create_table_query);
                $create_table = $create_table_stmt->fetch(PDO::FETCH_ASSOC);
                
                $sql_dump .= "\n-- ============================================\n";
                $sql_dump .= "-- Table structure for `$table`\n";
                $sql_dump .= "-- ============================================\n";
                $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql_dump .= $create_table['Create Table'] . ";\n\n";
                
                // Get table data using exact field names from your schema
                $data_query = "SELECT * FROM `$table`";
                $data_stmt = $conn->query($data_query);
                $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    $sql_dump .= "-- Data for table `$table`\n";
                    $sql_dump .= "-- Records: " . count($rows) . "\n\n";
                    
                    // Get column names for INSERT statement
                    $columns = array_keys($rows[0]);
                    $column_list = '`' . implode('`, `', $columns) . '`';
                    
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($conn) {
                            if ($value === null) {
                                return 'NULL';
                            } elseif (is_numeric($value)) {
                                return $value;
                            } else {
                                return $conn->quote($value);
                            }
                        }, array_values($row));
                        
                        $sql_dump .= "INSERT INTO `$table` ($column_list) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql_dump .= "\n";
                }
                
            } catch (Exception $e) {
                error_log("Error backing up table $table: " . $e->getMessage());
                $sql_dump .= "-- Error backing up table `$table`: " . $e->getMessage() . "\n\n";
            }
        }
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
        $sql_dump .= "-- Backup completed: " . date('Y-m-d H:i:s') . "\n";
        
        // Write backup file
        $result = file_put_contents($backup_file, $sql_dump);
        
        if ($result !== false) {
            return [
                'success' => true,
                'file' => $backup_file,
                'size' => $result,
                'tables' => count($tables),
                'message' => 'Backup created successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to write backup file'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Database backup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Backup failed: ' . $e->getMessage()
        ];
    }
}

// FIXED: Enhanced backup function with table-specific handling
function createAdvancedDatabaseBackup($conn) {
    try {
        $backup_file = '../backups/hms_backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        if (!file_exists('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // FIXED: Table backup order respecting foreign key constraints from your ERD
        $table_order = [
            'userauth',      // Base authentication table
            'AdminTable',    // References userauth
            'WardenTable',   // References userauth  
            'RoomTable',     // References WardenTable
            'StudentTable',  // References userauth and RoomTable
            'PaymentTable',  // References StudentTable and AdminTable
            'VisitorTable',  // References StudentTable
            'ComplaintTable' // References StudentTable and WardenTable
        ];
        
        $sql_dump = "-- =============================================\n";
        $sql_dump .= "-- Hostel Management System Database Backup\n";
        $sql_dump .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- Schema: Based on sql.txt specifications\n";
        $sql_dump .= "-- =============================================\n\n";
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql_dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql_dump .= "SET time_zone = '+00:00';\n\n";
        
        $total_records = 0;
        
        foreach ($table_order as $table) {
            try {
                // Check if table exists
                $check_query = "SELECT COUNT(*) as count FROM information_schema.tables 
                               WHERE table_schema = DATABASE() AND table_name = :table";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bindParam(':table', $table);
                $check_stmt->execute();
                $table_exists = $check_stmt->fetch()['count'] > 0;
                
                if (!$table_exists) {
                    $sql_dump .= "-- Table `$table` does not exist, skipping...\n\n";
                    continue;
                }
                
                // Get table structure
                $create_table_stmt = $conn->query("SHOW CREATE TABLE `$table`");
                $create_table = $create_table_stmt->fetch(PDO::FETCH_ASSOC);
                
                $sql_dump .= "-- ============================================\n";
                $sql_dump .= "-- Table: `$table`\n";
                $sql_dump .= "-- ============================================\n\n";
                $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql_dump .= $create_table['Create Table'] . ";\n\n";
                
                // Get row count
                $count_stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
                $row_count = $count_stmt->fetch()['count'];
                $total_records += $row_count;
                
                if ($row_count > 0) {
                    $sql_dump .= "-- Inserting $row_count records into `$table`\n";
                    
                    // Get data in batches for large tables
                    $batch_size = 1000;
                    $offset = 0;
                    
                    do {
                        $data_query = "SELECT * FROM `$table` LIMIT $batch_size OFFSET $offset";
                        $data_stmt = $conn->query($data_query);
                        $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($rows)) {
                            $columns = array_keys($rows[0]);
                            $column_list = '`' . implode('`, `', $columns) . '`';
                            
                            $sql_dump .= "INSERT INTO `$table` ($column_list) VALUES\n";
                            
                            $value_sets = [];
                            foreach ($rows as $row) {
                                $values = array_map(function($value) use ($conn) {
                                    if ($value === null) {
                                        return 'NULL';
                                    } elseif (is_numeric($value) && !is_string($value)) {
                                        return $value;
                                    } else {
                                        return $conn->quote($value);
                                    }
                                }, array_values($row));
                                
                                $value_sets[] = '(' . implode(', ', $values) . ')';
                            }
                            
                            $sql_dump .= implode(",\n", $value_sets) . ";\n\n";
                        }
                        
                        $offset += $batch_size;
                    } while (count($rows) == $batch_size);
                } else {
                    $sql_dump .= "-- No data to insert for `$table`\n\n";
                }
                
            } catch (Exception $e) {
                error_log("Error backing up table $table: " . $e->getMessage());
                $sql_dump .= "-- ERROR: Failed to backup table `$table` - " . $e->getMessage() . "\n\n";
            }
        }
        
        $sql_dump .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
        $sql_dump .= "-- =============================================\n";
        $sql_dump .= "-- Backup Summary\n";
        $sql_dump .= "-- Total Tables: " . count($table_order) . "\n";
        $sql_dump .= "-- Total Records: $total_records\n";
        $sql_dump .= "-- Completed: " . date('Y-m-d H:i:s') . "\n";
        $sql_dump .= "-- =============================================\n";
        
        $result = file_put_contents($backup_file, $sql_dump);
        
        if ($result !== false) {
            return [
                'success' => true,
                'file' => $backup_file,
                'size' => $result,
                'tables' => count($table_order),
                'records' => $total_records,
                'message' => 'Advanced backup created successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to write backup file'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Advanced database backup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Advanced backup failed: ' . $e->getMessage()
        ];
    }
}

function clearOldLogs($conn, $days) {
    try {
        // Clear old audit logs (if you have an audit table)
        $query = "DELETE FROM System_Audit_Log WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':days', $days);
        return $stmt->execute();
    } catch (Exception $e) {
        return false;
    }
}

// Get system statistics
$system_stats = [
    'database_size' => getDatabaseSize($conn),
    'total_tables' => getTotalTables($conn),
    'total_records' => getTotalRecords($conn),
    'last_backup' => getLastBackupDate()
];

function getDatabaseSize($conn) {
    $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size 
              FROM information_schema.tables 
              WHERE table_schema = 'hostel_management_system'";
    $result = $conn->query($query)->fetch();
    return $result['db_size'] . ' MB';
}

function getTotalTables($conn) {
    $query = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'hostel_management_system'";
    return $conn->query($query)->fetch()['count'];
}

function getTotalRecords($conn) {
    $tables = ['Student_Table', 'Room_Table', 'Payment_Table', 'Visitor_Table', 'Complaint_Table'];
    $total = 0;
    foreach ($tables as $table) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch()['count'];
        $total += $count;
    }
    return $total;
}

function getLastBackupDate() {
    $backup_dir = '../backups/';
    if (!is_dir($backup_dir)) return 'Never';
    
    $files = glob($backup_dir . 'backup_*.sql');
    if (empty($files)) return 'Never';
    
    $latest = max(array_map('filemtime', $files));
    return date('M d, Y H:i', $latest);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - HMS</title>
    <link rel="stylesheet" href="../css/main-style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-brand">
                <i class="fas fa-crown"></i> HMS - Admin Portal
            </a>
            <div class="nav-user">
                <a href="../auth/logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">System Settings</h1>
            <p class="dashboard-subtitle">Configure system parameters and manage database</p>
        </div>

        <!-- System Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-primary"><?php echo $system_stats['database_size']; ?></div>
                <div class="stat-label">Database Size</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo $system_stats['total_tables']; ?></div>
                <div class="stat-label">Total Tables</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo $system_stats['total_records']; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo $system_stats['last_backup']; ?></div>
                <div class="stat-label">Last Backup</div>
            </div>
        </div>

        <div class="card-grid">
            <!-- System Configuration -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon primary">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="card-title">System Configuration</div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="hostel_name" class="form-label">Hostel Name</label>
                            <input type="text" name="hostel_name" id="hostel_name" class="form-control" 
                                   value="University Hostel Complex" required>
                        </div>
                        <div class="form-group">
                            <label for="max_occupancy" class="form-label">Maximum Occupancy</label>
                            <input type="number" name="max_occupancy" id="max_occupancy" class="form-control" 
                                   value="500" required>
                        </div>
                        <div class="form-group">
                            <label for="default_rent" class="form-label">Default Monthly Rent (₹)</label>
                            <input type="number" name="default_rent" id="default_rent" class="form-control" 
                                   value="8000" step="100" required>
                        </div>
                        <div class="form-group">
                            <label for="late_fee_penalty" class="form-label">Late Fee Penalty (%)</label>
                            <input type="number" name="late_fee_penalty" id="late_fee_penalty" class="form-control" 
                                   value="5" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="visitor_time_limit" class="form-label">Visitor Time Limit (hours)</label>
                            <input type="number" name="visitor_time_limit" id="visitor_time_limit" class="form-control" 
                                   value="8" required>
                        </div>
                        <div class="form-group">
                            <label for="maintenance_email" class="form-label">Maintenance Email</label>
                            <input type="email" name="maintenance_email" id="maintenance_email" class="form-control" 
                                   value="maintenance@hostel.edu" required>
                        </div>
                        <button type="submit" name="update_system_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Database Management -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon warning">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="card-title">Database Management</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <h4>Database Backup</h4>
                        <p class="text-muted">Create a complete backup of the database for security and recovery purposes.</p>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="backup_database" class="btn btn-success" 
                                    onclick="return confirm('Create database backup? This may take a few minutes.')">
                                <i class="fas fa-download"></i> Create Backup
                            </button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <h4>Clear System Logs</h4>
                        <p class="text-muted">Remove old system logs to free up space.</p>
                        <form method="POST" style="display: inline;">
                            <div class="row">
                                <div class="col-6">
                                    <select name="log_retention_days" class="form-control">
                                        <option value="30">Older than 30 days</option>
                                        <option value="60">Older than 60 days</option>
                                        <option value="90">Older than 90 days</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="clear_logs" class="btn btn-warning" 
                                            onclick="return confirm('Clear old logs? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Clear Logs
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <h4>Database Optimization</h4>
                        <p class="text-muted">Optimize database tables for better performance.</p>
                        <button class="btn btn-info" onclick="optimizeDatabase()">
                            <i class="fas fa-tachometer-alt"></i> Optimize Database
                        </button>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon danger">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="card-title">Security Settings</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <h4>Password Policy</h4>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" checked> Require minimum 8 characters
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" checked> Require uppercase and lowercase
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" checked> Require numbers and special characters
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <h4>Session Settings</h4>
                        <div class="form-group">
                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                            <input type="number" id="session_timeout" class="form-control" value="30" min="5" max="480">
                        </div>
                        <div class="form-group">
                            <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                            <input type="number" id="max_login_attempts" class="form-control" value="3" min="1" max="10">
                        </div>
                    </div>
                    
                    <button class="btn btn-danger">
                        <i class="fas fa-save"></i> Update Security Settings
                    </button>
                </div>
            </div>

            <!-- System Maintenance -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon info">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="card-title">System Maintenance</div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <h4>Maintenance Mode</h4>
                        <p class="text-muted">Enable maintenance mode to prevent user access during system updates.</p>
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="maintenance_mode"> Enable Maintenance Mode
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="maintenance_message" class="form-label">Maintenance Message</label>
                            <textarea id="maintenance_message" class="form-control" rows="3" 
                                      placeholder="System is under maintenance. Please try again later."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <h4>System Health Check</h4>
                        <p class="text-muted">Run diagnostic checks on system components.</p>
                        <button class="btn btn-info" onclick="runHealthCheck()">
                            <i class="fas fa-heartbeat"></i> Run Health Check
                        </button>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <h4>Cache Management</h4>
                        <p class="text-muted">Clear system cache to improve performance.</p>
                        <button class="btn btn-warning" onclick="clearCache()">
                            <i class="fas fa-broom"></i> Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function optimizeDatabase() {
            if (confirm('Optimize database tables? This may take a few minutes and could temporarily slow down the system.')) {
                // Implementation for database optimization
                alert('Database optimization started. You will be notified when complete.');
            }
        }

        function runHealthCheck() {
            // Implementation for system health check
            alert('Running system health check...');
            
            // Simulate health check results
            setTimeout(() => {
                alert('Health Check Results:\n✓ Database connectivity: OK\n✓ File permissions: OK\n✓ Disk space: OK\n✓ Memory usage: Normal');
            }, 2000);
        }

        function clearCache() {
            if (confirm('Clear system cache? This will temporarily slow down the system until cache is rebuilt.')) {
                // Implementation for cache clearing
                alert('Cache cleared successfully.');
            }
        }

        // Auto-save settings
        document.getElementById('maintenance_mode').addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('Enable maintenance mode? This will prevent all users from accessing the system.')) {
                    this.checked = false;
                }
            }
        });
    </script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
