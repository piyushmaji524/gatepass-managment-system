<?php
require_once '../includes/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || $_SESSION['role'] != 'security') {
    header("Location: ../index.php");
    exit();
}

// Connect to database
$conn = connectDB();

$gatepass = null;
$items = null;

// Check if ID or gatepass_number is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gatepass_id = (int)$_GET['id'];
    
    // Get gatepass details
    $stmt = $conn->prepare("
        SELECT g.*, 
               admin.name as admin_name, 
               creator.name as creator_name
        FROM gatepasses g
        LEFT JOIN users admin ON g.admin_approved_by = admin.id
        LEFT JOIN users creator ON g.created_by = creator.id
        WHERE g.id = ? AND g.status = 'approved_by_admin'
    ");
    $stmt->bind_param("i", $gatepass_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no gatepass found or not in approved_by_admin status
    if ($result->num_rows !== 1) {
        $conn->close();
        redirectWithMessage("search_gatepass.php", "Gatepass not found or not ready for verification", "danger");
    }
    
    $gatepass = $result->fetch_assoc();
    
    // Get gatepass items
    $stmt = $conn->prepare("SELECT * FROM gatepass_items WHERE gatepass_id = ?");
    $stmt->bind_param("i", $gatepass_id);
    $stmt->execute();
    $items = $stmt->get_result();
} 
elseif (isset($_GET['gatepass_number']) && !empty($_GET['gatepass_number'])) {
    $gatepass_number = sanitizeInput($_GET['gatepass_number']);
    
    // Prepend "GP" if not already included
    if (substr($gatepass_number, 0, 2) !== "GP") {
        $gatepass_number = "GP" . $gatepass_number;
    }
    
    // Get gatepass details by gatepass_number
    $stmt = $conn->prepare("
        SELECT g.*, 
               admin.name as admin_name, 
               creator.name as creator_name
        FROM gatepasses g
        LEFT JOIN users admin ON g.admin_approved_by = admin.id
        LEFT JOIN users creator ON g.created_by = creator.id
        WHERE g.gatepass_number = ?
    ");
    $stmt->bind_param("s", $gatepass_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no gatepass found
    if ($result->num_rows !== 1) {
        $conn->close();
        redirectWithMessage("search_gatepass.php", "Gatepass not found with number: " . $gatepass_number, "danger");
    }
    
    $gatepass = $result->fetch_assoc();
    
    // Get gatepass items
    $stmt = $conn->prepare("SELECT * FROM gatepass_items WHERE gatepass_id = ?");
    $stmt->bind_param("i", $gatepass['id']);
    $stmt->execute();
    $items = $stmt->get_result();
    
    // Check if the gatepass is in the correct status for verification
    if ($gatepass['status'] === 'pending') {
        $conn->close();
        redirectWithMessage("view_gatepass.php?id=" . $gatepass['id'], "This gatepass is still pending approval from an admin", "warning");
    } elseif ($gatepass['status'] === 'declined') {
        $conn->close();
        redirectWithMessage("view_gatepass.php?id=" . $gatepass['id'], "This gatepass has been declined", "danger");
    } elseif ($gatepass['status'] === 'approved_by_security') {
        $conn->close();
        redirectWithMessage("view_gatepass.php?id=" . $gatepass['id'], "This gatepass has already been verified", "info");
    }
} 
else {
    $conn->close();
    redirectWithMessage("search_gatepass.php", "No gatepass identifier provided", "danger");
}

// Handle form submission (when security verifies the gatepass)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify') {
        // Update gatepass status
        $stmt = $conn->prepare("
            UPDATE gatepasses 
            SET status = 'approved_by_security', 
                security_approved_by = ?,
                security_approved_at = NOW()
            WHERE id = ? AND status = 'approved_by_admin'
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $gatepass['id']);
        $stmt->execute();
        
        // Check if update was successful
        if ($stmt->affected_rows > 0) {
            // Log the action
            logActivity($_SESSION['user_id'], 'GATEPASS_VERIFIED', "Security verified gatepass " . $gatepass['gatepass_number']);
            
            // Redirect with success message
            redirectWithMessage("download_pdf.php?id=" . $gatepass['id'], "Gatepass #" . $gatepass['gatepass_number'] . " verified successfully", "success");
        } else {
            // If update failed
            redirectWithMessage("view_gatepass.php?id=" . $gatepass['id'], "Failed to verify gatepass. It may have been processed already.", "danger");
        }
    } elseif ($_POST['action'] === 'decline') {
        // Get decline reason
        $decline_reason = sanitizeInput($_POST['decline_reason']);
        
        if (empty($decline_reason)) {
            redirectWithMessage("verify_gatepass.php?id=" . $gatepass['id'], "Please provide a reason for declining the gatepass", "warning");
        }
        
        // Update gatepass status
        $stmt = $conn->prepare("
            UPDATE gatepasses 
            SET status = 'declined', 
                declined_by = ?,
                declined_at = NOW(),
                decline_reason = ?
            WHERE id = ? AND status = 'approved_by_admin'
        ");
        $stmt->bind_param("isi", $_SESSION['user_id'], $decline_reason, $gatepass['id']);
        $stmt->execute();
        
        // Check if update was successful
        if ($stmt->affected_rows > 0) {
            // Log the action
            logActivity($_SESSION['user_id'], 'GATEPASS_DECLINED', "Security declined gatepass " . $gatepass['gatepass_number'] . ": " . $decline_reason);
            
            // Redirect with success message
            redirectWithMessage("search_gatepass.php", "Gatepass #" . $gatepass['gatepass_number'] . " has been declined", "success");
        } else {
            // If update failed
            redirectWithMessage("verify_gatepass.php?id=" . $gatepass['id'], "Failed to decline gatepass. It may have been processed already.", "danger");
        }
    }
}

// Set page title
$page_title = "Verify Gatepass #" . $gatepass['gatepass_number'];

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-check-circle me-2"></i>Verify Gatepass</h1>
        <a href="search_gatepass.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Search
        </a>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Gatepass #<?php echo htmlspecialchars($gatepass['gatepass_number']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>From Location:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['from_location']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>To Location:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['to_location']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Material Type:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['material_type']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Requested Date:</strong></p>
                            <p class="border-bottom pb-2">
                                <?php echo formatDateTime($gatepass['requested_date'] . ' ' . $gatepass['requested_time']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Created By:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['creator_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Created At:</strong></p>
                            <p class="border-bottom pb-2"><?php echo formatDateTime($gatepass['created_at']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Admin Approved By:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['admin_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Admin Approved At:</strong></p>
                            <p class="border-bottom pb-2"><?php echo formatDateTime($gatepass['admin_approved_at']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <p class="mb-1"><strong>Purpose:</strong></p>
                            <p class="border-bottom pb-2"><?php echo htmlspecialchars($gatepass['purpose']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="mb-3">Gatepass Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $count = 1; while ($item = $items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4 border-success">
                <div class="card-header text-white bg-success">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Verification Actions</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> Verify this gatepass to allow the items to leave the premises.
                    </div>
                    
                    <div class="d-grid gap-3">
                        <form action="" method="post">
                            <input type="hidden" name="action" value="verify">
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-check me-2"></i>Verify Gatepass
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#declineModal">
                            <i class="fas fa-times me-2"></i>Decline Gatepass
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Decline Modal -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="declineModalLabel">Decline Gatepass</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="decline">
                    
                    <div class="mb-3">
                        <label for="decline_reason" class="form-label">Reason for Declining</label>
                        <textarea class="form-control" id="decline_reason" name="decline_reason" rows="4" required></textarea>
                        <div class="form-text">Please provide a clear reason why this gatepass is being declined.</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The gatepass will be marked as declined.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>