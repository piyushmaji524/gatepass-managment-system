<?php
require_once '../includes/config.php';

// Check if user is logged in and has security role
if (!isLoggedIn() || $_SESSION['role'] != 'security') {
    header("Location: ../index.php");
    exit();
}

// Connect to database
$conn = connectDB();

// Get all returnable items that haven't been returned yet
$stmt = $conn->prepare("
    SELECT i.*, 
           g.gatepass_number,
           g.created_at as gatepass_date,
           g.from_location,
           g.to_location,
           u.name as created_by_name
    FROM gatepass_items i
    JOIN gatepasses g ON i.gatepass_id = g.id
    JOIN users u ON g.created_by = u.id
    WHERE i.is_returnable = 1 
    AND (i.returned = 0 OR i.returned IS NULL)
    AND g.status = 'approved_by_security'
    ORDER BY g.created_at DESC
");
$stmt->execute();
$pending_returns = $stmt->get_result();

// Process item return
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return_item') {
    if (isset($_POST['item_id']) && is_numeric($_POST['item_id']) && isset($_POST['gatepass_id']) && is_numeric($_POST['gatepass_id'])) {
        $item_id = (int)$_POST['item_id'];
        $gatepass_id = (int)$_POST['gatepass_id'];
        
        // Update the item as returned
        $stmt = $conn->prepare("
            UPDATE gatepass_items 
            SET returned = 1, 
                return_date = NOW(), 
                returned_by = ? 
            WHERE id = ? AND is_returnable = 1
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $item_id);
        $stmt->execute();
          if ($stmt->affected_rows > 0) {
            // Get gatepass details including creator and admin info
            $stmt = $conn->prepare("
                SELECT g.*, 
                       i.item_name, i.quantity, i.unit,
                       creator.name as creator_name, creator.email as creator_email,
                       admin.name as admin_name, admin.email as admin_email
                FROM gatepasses g
                JOIN gatepass_items i ON i.gatepass_id = g.id AND i.id = ?
                JOIN users creator ON g.created_by = creator.id
                LEFT JOIN users admin ON g.admin_approved_by = admin.id
                WHERE g.id = ?
            ");
            $stmt->bind_param("ii", $item_id, $gatepass_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $gatepass_data = $result->fetch_assoc();
            
            // Log the action
            logActivity($_SESSION['user_id'], 'ITEM_RETURNED', "Marked item #$item_id as returned for gatepass " . $gatepass_data['gatepass_number']);
              // Prepare data for email
            $email_gatepass_data = array(
                'gatepass_number' => $gatepass_data['gatepass_number'],
                'from_location' => $gatepass_data['from_location'],
                'to_location' => $gatepass_data['to_location'],
                'material_type' => $gatepass_data['material_type'],
                'status' => 'ITEM RETURNED',
                'returned_item_name' => $gatepass_data['item_name'],
                'returned_quantity' => $gatepass_data['quantity'],
                'returned_unit' => $gatepass_data['unit'],
                'return_date' => date('d M Y, h:i A')
            );
            
            // Send email notification to the user who created the gatepass
            $subject = "Item Returned: Gatepass #" . $gatepass_data['gatepass_number'];
            $message = "The following item has been returned and marked as received: <br><br>";
            $message .= "<strong>Item:</strong> " . $gatepass_data['item_name'] . "<br>";
            $message .= "<strong>Quantity:</strong> " . $gatepass_data['quantity'] . " " . $gatepass_data['unit'] . "<br>";
            $message .= "<strong>Return Date:</strong> " . date('d M Y, h:i A') . "<br><br>";
            $message .= "This item is now confirmed as returned to the premises.";
            $action_url = APP_URL . "/user/view_gatepass.php?id=" . $gatepass_id;
            $action_text = "View Gatepass";
            
            sendEmailNotification(
                $gatepass_data['creator_email'],
                $gatepass_data['creator_name'],
                $subject,
                $message,
                $email_gatepass_data,
                $action_url,
                $action_text
            );
            
            // Send email notification to the admin who approved the gatepass (if available)
            if (!empty($gatepass_data['admin_email'])) {
                $admin_subject = "Item Returned: Gatepass #" . $gatepass_data['gatepass_number'];
                $admin_message = "An item from a gatepass you approved has been returned: <br><br>";
                $admin_message .= "<strong>Item:</strong> " . $gatepass_data['item_name'] . "<br>";
                $admin_message .= "<strong>Quantity:</strong> " . $gatepass_data['quantity'] . " " . $gatepass_data['unit'] . "<br>";
                $admin_message .= "<strong>Created By:</strong> " . $gatepass_data['creator_name'] . "<br>";
                $admin_message .= "<strong>Return Date:</strong> " . date('d M Y, h:i A') . "<br><br>";
                $admin_message .= "This item is now confirmed as returned to the premises.";
                $admin_action_url = APP_URL . "/admin/view_gatepass.php?id=" . $gatepass_id;
                
                sendEmailNotification(
                    $gatepass_data['admin_email'],
                    $gatepass_data['admin_name'],
                    $admin_subject,
                    $admin_message,
                    $email_gatepass_data,
                    $admin_action_url,
                    $action_text
                );
            }
            
            // Redirect to refresh the page
            redirectWithMessage("pending_returns.php", "Item marked as returned successfully", "success");
        } else {
            // If update failed
            redirectWithMessage("pending_returns.php", "Failed to mark item as returned. It may not be returnable or already returned.", "danger");
        }
    }
}

// Set page title
$page_title = "Pending Returnable Items";

// Include header
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-exchange-alt me-2"></i>Pending Returnable Items</h2>
    <a href="dashboard.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Items Pending Return</h5>
            <div>
                <form class="input-group input-group-sm" style="max-width: 300px;" id="search-form">
                    <input type="text" class="form-control" placeholder="Search items..." id="search-input">
                    <button class="btn btn-outline-secondary" type="button" id="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="returnable-items-table">
                <thead class="table-light">
                    <tr>
                        <th>Gatepass #</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>From → To</th>
                        <th>Date Out</th>
                        <th>Days Out</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_returns->num_rows > 0): ?>
                        <?php while ($item = $pending_returns->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="verified_gatepasses.php?id=<?php echo $item['gatepass_id']; ?>">
                                        <?php echo htmlspecialchars($item['gatepass_number']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo htmlspecialchars($item['from_location']); ?> → <?php echo htmlspecialchars($item['to_location']); ?></td>
                                <td><?php echo date('d M Y', strtotime($item['gatepass_date'])); ?></td>
                                <td>
                                    <?php
                                    $days_out = round((time() - strtotime($item['gatepass_date'])) / (60 * 60 * 24));
                                    echo $days_out;
                                    if ($days_out > 30) {
                                        echo ' <span class="badge rounded-pill bg-danger">Overdue</span>';
                                    } elseif ($days_out > 15) {
                                        echo ' <span class="badge rounded-pill bg-warning">Attention</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['created_by_name']); ?></td>
                                <td>
                                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Mark this item as returned?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="gatepass_id" value="<?php echo $item['gatepass_id']; ?>">
                                        <input type="hidden" name="action" value="return_item">
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark as Returned">
                                            <i class="fas fa-check-circle"></i> Return
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p>No pending returnable items found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple search functionality
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const table = document.getElementById('returnable-items-table');
    const rows = table.querySelectorAll('tbody tr');
    
    function performSearch() {
        const query = searchInput.value.toLowerCase();
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if(text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    if(searchButton) {
        searchButton.addEventListener('click', performSearch);
    }
    
    if(searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if(e.key === 'Enter') {
                performSearch();
            }
        });
    }
});
</script>

<?php
// Close the database connection
$conn->close();

// Include footer
include '../includes/footer.php';
?>
