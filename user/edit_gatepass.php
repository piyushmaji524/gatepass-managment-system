<?php
require_once '../includes/config.php';

// Check if user is logged in and has user role
if (!isLoggedIn() || $_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectWithMessage("my_gatepasses.php", "Invalid gatepass ID", "danger");
}

$gatepass_id = (int)$_GET['id'];

// Connect to database
$conn = connectDB();

// Get gatepass details and check if it can be edited
$stmt = $conn->prepare("
    SELECT * FROM gatepasses
    WHERE id = ? AND created_by = ? AND status = 'pending'
");
$stmt->bind_param("ii", $gatepass_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// If no gatepass found, not owned by this user, or not editable
if ($result->num_rows !== 1) {
    $conn->close();
    redirectWithMessage("my_gatepasses.php", "Gatepass not found, cannot be edited, or you don't have permission", "danger");
}

$gatepass = $result->fetch_assoc();

// Get gatepass items
$stmt = $conn->prepare("SELECT * FROM gatepass_items WHERE gatepass_id = ?");
$stmt->bind_param("i", $gatepass_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Store items in array
$items = array();
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $from_location = sanitizeInput($_POST['from_location']);
    $to_location = sanitizeInput($_POST['to_location']);
    $material_type = sanitizeInput($_POST['material_type']);
    $purpose = sanitizeInput($_POST['purpose']);
    $requested_date = sanitizeInput($_POST['requested_date']);
    $requested_time = sanitizeInput($_POST['requested_time']);
      // Validate item arrays
    $item_names = $_POST['item_name'];
    $item_quantities = $_POST['item_quantity'];
    $item_units = $_POST['item_unit'];
    $item_ids = isset($_POST['item_id']) ? $_POST['item_id'] : array();
    $item_returnable = isset($_POST['item_returnable']) ? $_POST['item_returnable'] : array();
    
    $errors = array();
    
    // Basic validation
    if (empty($from_location)) $errors[] = "From location is required";
    if (empty($to_location)) $errors[] = "To location is required";
    if (empty($material_type)) $errors[] = "Material type is required";
    if (empty($requested_date)) $errors[] = "Date is required";
    if (empty($requested_time)) $errors[] = "Time is required";
    if (empty($item_names) || count($item_names) < 1) $errors[] = "At least one item is required";
    
    // Date validation
    $current_date = date('Y-m-d');
    if ($requested_date < $current_date) {
        $errors[] = "Requested date cannot be in the past";
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update gatepasses table
            $stmt = $conn->prepare("
                UPDATE gatepasses 
                SET from_location = ?, to_location = ?, material_type = ?, purpose = ?,
                    requested_date = ?, requested_time = ?
                WHERE id = ? AND created_by = ? AND status = 'pending'
            ");
            $stmt->bind_param(
                "ssssssii", 
                $from_location, $to_location, $material_type, $purpose,
                $requested_date, $requested_time, $gatepass_id, $_SESSION['user_id']
            );
            $stmt->execute();
            
            // If no rows affected, something went wrong
            if ($stmt->affected_rows <= 0 && $stmt->error) {
                throw new Exception("Failed to update gatepass: " . $stmt->error);
            }
            
            // Delete all existing items
            $stmt = $conn->prepare("DELETE FROM gatepass_items WHERE gatepass_id = ?");
            $stmt->bind_param("i", $gatepass_id);
            $stmt->execute();
              // Insert new/updated items
            $stmt = $conn->prepare("
                INSERT INTO gatepass_items 
                (gatepass_id, item_name, quantity, unit, is_returnable)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            for ($i = 0; $i < count($item_names); $i++) {
                // Skip empty entries
                if (empty($item_names[$i])) continue;
                  $item_name = sanitizeInput($item_names[$i]);
                $item_quantity = floatval($item_quantities[$i]);
                $item_unit = sanitizeInput($item_units[$i]);
                $is_returnable = in_array((string)$i, $item_returnable) ? 1 : 0;
                
                $stmt->bind_param("isdsi", $gatepass_id, $item_name, $item_quantity, $item_unit, $is_returnable);
                $stmt->execute();
            }
            
            // Log the action
            logActivity($_SESSION['user_id'], 'GATEPASS_EDITED', "Edited gatepass " . $gatepass['gatepass_number']);
            
            // Commit transaction
            $conn->commit();
            
            // Redirect with success message
            redirectWithMessage("view_gatepass.php?id=$gatepass_id", "Gatepass #" . $gatepass['gatepass_number'] . " updated successfully");
            
        } catch (Exception $e) {
            // Rollback if error
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Set page title
$page_title = "Edit Gatepass #" . $gatepass['gatepass_number'];

// Include header
require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-edit me-2"></i>Edit Gatepass #<?php echo htmlspecialchars($gatepass['gatepass_number']); ?></h2>
        <p class="text-muted">Update your gatepass request details below.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $gatepass_id); ?>" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="from_location" class="form-label">From Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="from_location" name="from_location" required
                           value="<?php echo htmlspecialchars($gatepass['from_location']); ?>">
                    <div class="invalid-feedback">
                        Please enter the source location
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="to_location" class="form-label">To Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="to_location" name="to_location" required
                           value="<?php echo htmlspecialchars($gatepass['to_location']); ?>">
                    <div class="invalid-feedback">
                        Please enter the destination location
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="material_type" class="form-label">Material Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="material_type" name="material_type" required
                           value="<?php echo htmlspecialchars($gatepass['material_type']); ?>">
                    <div class="invalid-feedback">
                        Please enter the type of material
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="requested_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="requested_date" name="requested_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($gatepass['requested_date']); ?>">
                    <div class="invalid-feedback">
                        Please select a valid date
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="requested_time" class="form-label">Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="requested_time" name="requested_time" required
                           value="<?php echo htmlspecialchars($gatepass['requested_time']); ?>">
                    <div class="invalid-feedback">
                        Please enter a valid time
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <label for="purpose" class="form-label">Purpose/Remarks</label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="3"><?php echo htmlspecialchars($gatepass['purpose']); ?></textarea>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Items <span class="text-danger">*</span></label>
                    <div id="itemsContainer">
                        <?php foreach ($items as $index => $item): ?>                        <div class="item-entry" id="item-<?php echo $index + 1; ?>">
                            <span class="remove-item"><i class="fas fa-times"></i></span>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="item_name_<?php echo $index + 1; ?>" class="form-label">Item Name</label>
                                    <input type="text" class="form-control" id="item_name_<?php echo $index + 1; ?>" name="item_name[]" required value="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="item_quantity_<?php echo $index + 1; ?>" class="form-label">Quantity</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="item_quantity_<?php echo $index + 1; ?>" name="item_quantity[]" required value="<?php echo htmlspecialchars($item['quantity']); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="item_unit_<?php echo $index + 1; ?>" class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="item_unit_<?php echo $index + 1; ?>" name="item_unit[]" required value="<?php echo htmlspecialchars($item['unit']); ?>">
                                </div>                                <div class="col-md-3 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="item_returnable_<?php echo $index; ?>" name="item_returnable[]" value="<?php echo $index; ?>" <?php echo (isset($item['is_returnable']) && $item['is_returnable'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="item_returnable_<?php echo $index; ?>">
                                            Returnable
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="addItemBtn">
                        <i class="fas fa-plus-circle me-1"></i> Add Another Item
                    </button>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-between">
                <a href="view_gatepass.php?id=<?php echo $gatepass_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Gatepass
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Close connection
$conn->close();

// Additional JavaScript for debugging
$additional_js = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    console.log("Edit gatepass page loaded");
    
    // Log all returnable checkboxes for debugging
    const returnableCheckboxes = document.querySelectorAll("[name=\'item_returnable[]\']");
    returnableCheckboxes.forEach(function(checkbox) {
        console.log("Checkbox ID:", checkbox.id, "Value:", checkbox.value, "Checked:", checkbox.checked);
    });
});
</script>
';

// Include footer
require_once '../includes/footer.php';
?>
