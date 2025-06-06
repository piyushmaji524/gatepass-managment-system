<?php
// reports.php - Admin reports generation page
require_once '../includes/config.php';
require_once 'includes/returnable_reports.php';

// Check if user is logged in and is an admin
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Set page title
$page_title = "Reports";

// Initialize database connection
$conn = connectDB();

// Handle report generation
$report_data = array();
$report_type = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // Default to first day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Default to current day

// Process report generation
if ($report_type) {
    try {
        switch ($report_type) {
            case 'daily':
                // Daily gatepass report
                $report_data = getDailyReport($conn, $date_from, $date_to);
                break;
            case 'weekly':
                // Weekly gatepass report
                $report_data = getWeeklyReport($conn, $date_from, $date_to);
                break;
            case 'monthly':
                // Monthly gatepass report
                $report_data = getMonthlyReport($conn, $date_from, $date_to);
                break;
            case 'user':
                // User activity report
                $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
                $report_data = getUserReport($conn, $user_id, $date_from, $date_to);
                break;            case 'status':
                // Status-wise report
                $status = isset($_GET['status']) ? $_GET['status'] : '';
                $report_data = getStatusReport($conn, $status, $date_from, $date_to);
                break;
            case 'returnable':
                // Returnable items report
                $report_data = getReturnableReport($conn, $date_from, $date_to);
                break;
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Report generation error: " . $e->getMessage());
        $_SESSION['flash_message'] = "Error generating report: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Get all users for user report dropdown
$users = array();
$query = "SELECT id, name FROM users WHERE role = 'user' ORDER BY name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Function to get daily report
function getDailyReport($conn, $date_from, $date_to) {
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    $query = "SELECT 
                DATE(created_at) as report_date, 
                COUNT(*) as total_gatepasses,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined,
                (SELECT COUNT(*) FROM gatepass_items gi 
                 JOIN gatepasses g ON gi.gatepass_id = g.id
                 WHERE gi.is_returnable = 1 
                 AND DATE(g.created_at) = DATE(gatepasses.created_at)) as returnable_items,
                (SELECT COUNT(*) FROM gatepass_items gi 
                 JOIN gatepasses g ON gi.gatepass_id = g.id
                 WHERE gi.is_returnable = 1 AND gi.returned = 1 
                 AND DATE(g.created_at) = DATE(gatepasses.created_at)) as returned_items
              FROM gatepasses 
              WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
              GROUP BY DATE(created_at)
              ORDER BY DATE(created_at)";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get weekly report
function getWeeklyReport($conn, $date_from, $date_to) {
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    $query = "SELECT 
                YEARWEEK(created_at, 1) as report_week,
                MIN(DATE(created_at)) as week_start,
                MAX(DATE(created_at)) as week_end, 
                COUNT(*) as total_gatepasses,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
              FROM gatepasses 
              WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
              GROUP BY YEARWEEK(created_at, 1)
              ORDER BY report_week";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get monthly report
function getMonthlyReport($conn, $date_from, $date_to) {
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as report_month,
                DATE_FORMAT(created_at, '%M %Y') as month_name,
                COUNT(*) as total_gatepasses,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined
              FROM gatepasses 
              WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
              GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%M %Y')
              ORDER BY report_month";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get user report
function getUserReport($conn, $user_id, $date_from, $date_to) {
    if (!$user_id) {
        return array();
    }
    
    $user_id = (int)$user_id;
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    $query = "SELECT 
                g.id, g.gatepass_number, g.purpose, g.status, g.created_at, 
                COALESCE(g.admin_approved_at, g.security_approved_at) as approved_at,
                u.name as created_by,
                a.name as approved_by
              FROM gatepasses g
              LEFT JOIN users u ON g.created_by = u.id
              LEFT JOIN users a ON g.admin_approved_by = a.id OR g.security_approved_by = a.id
              WHERE g.created_by = $user_id
              AND DATE(g.created_at) BETWEEN '$date_from' AND '$date_to'
              ORDER BY g.created_at DESC";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Function to get status report
function getStatusReport($conn, $status, $date_from, $date_to) {
    $status = $conn->real_escape_string($status);
    $date_from = $conn->real_escape_string($date_from);
    $date_to = $conn->real_escape_string($date_to);
    
    // Map the simplified status to database status values
    $statusFilter = "";
    if ($status == 'approved') {
        $statusFilter = "g.status IN ('approved_by_admin', 'approved_by_security')";
    } else if ($status == 'pending') {
        $statusFilter = "g.status = 'pending'";
    } else if ($status == 'declined') {
        $statusFilter = "g.status = 'declined'";
    }
    
    $query = "SELECT 
                g.id, g.gatepass_number, g.purpose, g.status, g.created_at, 
                COALESCE(g.admin_approved_at, g.security_approved_at) as approved_at,
                u.name as created_by,
                CASE 
                    WHEN g.admin_approved_by IS NOT NULL THEN admin.name
                    WHEN g.security_approved_by IS NOT NULL THEN security.name
                    ELSE NULL
                END as approved_by
              FROM gatepasses g
              LEFT JOIN users u ON g.created_by = u.id
              LEFT JOIN users admin ON g.admin_approved_by = admin.id
              LEFT JOIN users security ON g.security_approved_by = security.id
              WHERE $statusFilter
              AND DATE(g.created_at) BETWEEN '$date_from' AND '$date_to'
              ORDER BY g.created_at DESC";
              
    $result = $conn->query($query);
    $data = array();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    return $data;
}

// Include header
include '../includes/header.php';
?>

<h1 class="mb-4"><i class="fas fa-chart-bar me-2"></i> Reports</h1>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Generate Reports</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'daily' || !$report_type) ? 'active' : ''; ?>" id="daily-tab" data-bs-toggle="tab" data-bs-target="#daily" type="button" role="tab">Daily</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'weekly') ? 'active' : ''; ?>" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab">Weekly</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'monthly') ? 'active' : ''; ?>" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab">Monthly</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'user') ? 'active' : ''; ?>" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button" role="tab">By User</button>
                    </li>                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'status') ? 'active' : ''; ?>" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab">By Status</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($report_type == 'returnable') ? 'active' : ''; ?>" id="returnable-tab" data-bs-toggle="tab" data-bs-target="#returnable" type="button" role="tab">Returnable Items</button>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="reportTabsContent">
                    <!-- Daily Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'daily' || !$report_type) ? 'show active' : ''; ?>" id="daily" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="daily">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Weekly Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'weekly') ? 'show active' : ''; ?>" id="weekly" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="weekly">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Monthly Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'monthly') ? 'show active' : ''; ?>" id="monthly" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="monthly">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- User Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'user') ? 'show active' : ''; ?>" id="user" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="user">
                            <div class="col-md-4">
                                <label for="user_id" class="form-label">Select User</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">-- Select User --</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                      <!-- Status Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'status') ? 'show active' : ''; ?>" id="status" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="status">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Select Status</label>                                <select class="form-select" id="status" name="status" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                    <option value="declined" <?php echo (isset($_GET['status']) && $_GET['status'] == 'declined') ? 'selected' : ''; ?>>Declined</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Returnable Items Report Tab -->
                    <div class="tab-pane fade <?php echo ($report_type == 'returnable') ? 'show active' : ''; ?>" id="returnable" role="tabpanel">
                        <form action="reports.php" method="get" class="row g-3">
                            <input type="hidden" name="type" value="returnable">
                            <div class="col-md-5">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-5">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($report_type && !empty($report_data)): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <?php 
                    switch ($report_type) {
                        case 'daily':
                            echo 'Daily Report (' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)) . ')';
                            break;
                        case 'weekly':
                            echo 'Weekly Report (' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)) . ')';
                            break;
                        case 'monthly':
                            echo 'Monthly Report (' . date('M Y', strtotime($date_from)) . ' - ' . date('M Y', strtotime($date_to)) . ')';
                            break;
                        case 'user':
                            $user_name = '';
                            foreach ($users as $user) {
                                if ($user['id'] == $_GET['user_id']) {
                                    $user_name = $user['name'];
                                    break;
                                }
                            }
                            echo 'User Report: ' . htmlspecialchars($user_name) . ' (' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)) . ')';
                            break;
                        case 'status':
                            echo 'Status Report: ' . ucfirst($_GET['status']) . ' (' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)) . ')';
                            break;
                    }
                    ?>
                </h5>
                <div>
                    <button class="btn btn-sm btn-outline-primary" onclick="printReport()"><i class="fas fa-print me-1"></i> Print</button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportCSV()"><i class="fas fa-file-csv me-1"></i> Export CSV</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="reportTable">
                    <?php if ($report_type == 'daily'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Gatepasses</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Declined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo date('d M Y (D)', strtotime($row['report_date'])); ?></td>
                                <td><?php echo $row['total_gatepasses']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['declined']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th>Total</th>
                                <th><?php echo array_sum(array_column($report_data, 'total_gatepasses')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'approved')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'pending')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'declined')); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <?php elseif ($report_type == 'weekly'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Period</th>
                                <th>Total Gatepasses</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Declined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo $row['report_week']; ?></td>
                                <td><?php echo date('d M', strtotime($row['week_start'])); ?> - <?php echo date('d M', strtotime($row['week_end'])); ?></td>
                                <td><?php echo $row['total_gatepasses']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['declined']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th colspan="2">Total</th>
                                <th><?php echo array_sum(array_column($report_data, 'total_gatepasses')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'approved')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'pending')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'declined')); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <?php elseif ($report_type == 'monthly'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Gatepasses</th>
                                <th>Approved</th>
                                <th>Pending</th>
                                <th>Declined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo $row['month_name']; ?></td>
                                <td><?php echo $row['total_gatepasses']; ?></td>
                                <td><?php echo $row['approved']; ?></td>
                                <td><?php echo $row['pending']; ?></td>
                                <td><?php echo $row['declined']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <th>Total</th>
                                <th><?php echo array_sum(array_column($report_data, 'total_gatepasses')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'approved')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'pending')); ?></th>
                                <th><?php echo array_sum(array_column($report_data, 'declined')); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                    <?php elseif ($report_type == 'user'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Gatepass #</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Approved By</th>
                                <th>Approved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['gatepass_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td>                                    <?php if ($row['status'] == 'approved_by_admin' || $row['status'] == 'approved_by_security'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Declined</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['approved_by'] ? htmlspecialchars($row['approved_by']) : '-'; ?></td>
                                <td><?php echo $row['approved_at'] ? date('d M Y H:i', strtotime($row['approved_at'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>                    <?php elseif ($report_type == 'status'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Gatepass #</th>
                                <th>Purpose</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Approved By</th>
                                <th>Approved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['gatepass_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['approved_by'] ? htmlspecialchars($row['approved_by']) : '-'; ?></td>
                                <td><?php echo $row['approved_at'] ? date('d M Y H:i', strtotime($row['approved_at'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php elseif ($report_type == 'returnable'): ?>
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>Gatepass #</th>
                                <th>From → To</th>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Return Date</th>
                                <th>Created By</th>
                                <th>Returned By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['gatepass_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['from_location']); ?> → <?php echo htmlspecialchars($row['to_location']); ?></td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td>
                                    <?php if ($row['return_status'] == 'Returned'): ?>
                                        <span class="badge bg-success">Returned</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending Return</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['return_date'] ? date('d M Y H:i', strtotime($row['return_date'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                <td><?php echo $row['returned_by'] ? htmlspecialchars($row['returned_by']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php elseif ($report_type && empty($report_data)): ?>
<div class="alert alert-info">
    No data found for the selected criteria. Please try different parameters.
</div>
<?php endif; ?>

<!-- JavaScript for Print and Export functionality -->
<script>
function printReport() {
    const reportTable = document.getElementById('reportTable').innerHTML;
    const reportTitle = document.querySelector('.card-header h5').textContent;
    
    let printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                h1 { text-align: center; margin-bottom: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <h1>${reportTitle}</h1>
            <div>${reportTable}</div>
            <div class="no-print text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
                <button class="btn btn-secondary" onclick="window.close()">Close</button>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function exportCSV() {
    const table = document.querySelector('table');
    let csv = [];
    let rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Get the text content and clean it
            let text = cols[j].textContent.trim().replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(','));
    }
    
    // Create CSV file and download it
    let reportTitle = document.querySelector('.card-header h5').textContent.replace(/[^a-z0-9]/gi, '_').toLowerCase();
    let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    let downloadLink = document.createElement('a');
    downloadLink.download = reportTitle + '_report.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

<?php include '../includes/footer.php'; ?>
