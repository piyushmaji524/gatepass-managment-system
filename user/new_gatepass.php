<?php
require_once '../includes/config.php';

// Check if user is logged in and has user role
if (!isLoggedIn() || $_SESSION['role'] != 'user') {
    header("Location: ../index.php");
    exit();
}

// Set page title
$page_title = "New Gatepass";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Connect to database
    $conn = connectDB();
    
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
    $item_returnable = isset($_POST['item_returnable']) ? $_POST['item_returnable'] : array();
    
    // Debug returnable items (optional, remove in production)
    // error_log("Returnable items: " . print_r($item_returnable, true));
    
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
    
    // If no errors, proceed with insertion
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Generate unique gatepass number (format: GPXXX where XXX is a random 3-digit number)
            $isUnique = false;
            $gatepass_number = '';
            
            while (!$isUnique) {
                // Generate a random 3-digit number (100-999)
                $random = rand(100, 999);
                $gatepass_number = 'GP' . $random;
                
                // Check if this number already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM gatepasses WHERE gatepass_number = ?");
                $stmt->bind_param("s", $gatepass_number);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // If count is 0, we have a unique number
                if ($result->fetch_assoc()['count'] == 0) {
                    $isUnique = true;
                }
            }
            
            // Insert into gatepasses table
            $stmt = $conn->prepare("
                INSERT INTO gatepasses 
                (gatepass_number, from_location, to_location, material_type, purpose, 
                requested_date, requested_time, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssssi", 
                $gatepass_number, $from_location, $to_location, $material_type, $purpose,
                $requested_date, $requested_time, $_SESSION['user_id']
            );
            $stmt->execute();
            $gatepass_id = $conn->insert_id;
            
            // Insert items
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
                $returnable = in_array((string)$i, $item_returnable) ? 1 : 0; // 1 if checked, 0 otherwise
                
                $stmt->bind_param("isdsi", $gatepass_id, $item_name, $item_quantity, $item_unit, $returnable);
                $stmt->execute();
            }
            
            // Log the action
            logActivity($_SESSION['user_id'], 'GATEPASS_CREATED', "Created gatepass $gatepass_number");
            
            // Commit transaction
            $conn->commit();
            
            // Redirect with success message
            redirectWithMessage("view_gatepass.php?id=$gatepass_id", "Gatepass #$gatepass_number created successfully");
            
        } catch (Exception $e) {
            // Rollback if error
            $conn->rollback();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    $conn->close();
}

// Include header
require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-plus-circle me-2"></i>Create New Gatepass</h2>
        <p class="text-muted">Fill in all the required fields to create a new gatepass request.</p>
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
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="from_location" class="form-label">From Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="from_location" name="from_location" required
                           value="<?php echo isset($_POST['from_location']) ? htmlspecialchars($_POST['from_location']) : ''; ?>">
                    <div class="invalid-feedback">
                        Please enter the source location
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="to_location" class="form-label">To Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="to_location" name="to_location" required
                           value="<?php echo isset($_POST['to_location']) ? htmlspecialchars($_POST['to_location']) : ''; ?>">
                    <div class="invalid-feedback">
                        Please enter the destination location
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="material_type" class="form-label">Material Type <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="material_type" name="material_type" required
                           value="<?php echo isset($_POST['material_type']) ? htmlspecialchars($_POST['material_type']) : ''; ?>">
                    <div class="invalid-feedback">
                        Please enter the type of material
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="requested_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="requested_date" name="requested_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo isset($_POST['requested_date']) ? htmlspecialchars($_POST['requested_date']) : date('Y-m-d'); ?>">
                    <div class="invalid-feedback">
                        Please select a valid date
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="requested_time" class="form-label">Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="requested_time" name="requested_time" required
                           value="<?php echo isset($_POST['requested_time']) ? htmlspecialchars($_POST['requested_time']) : date('H:i'); ?>">
                    <div class="invalid-feedback">
                        Please enter a valid time
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <label for="purpose" class="form-label">Purpose/Remarks</label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="3"><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : ''; ?></textarea>
                </div>
            </div>
              <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Items <span class="text-danger">*</span></label>
                    <div id="itemsContainer">
                        <!-- Item entries will be dynamically added here -->
                        <!-- The first item will be added automatically by JavaScript -->
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="addItemBtn">
                        <i class="fas fa-plus-circle me-1"></i> Add Another Item
                    </button>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Submit Gatepass
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Additional JavaScript
$additional_js = '
<script src="../assets/js/new-gatepass.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // The initial item is now added by new-gatepass.js
    
    // Debug helper for form submission
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", function(e) {
            // Check returnable checkboxes before submission
            const returnableCheckboxes = document.querySelectorAll("[name=\'item_returnable[]\']");
            console.log("Form submission - returnable checkboxes:", returnableCheckboxes.length);
            returnableCheckboxes.forEach(function(checkbox, index) {
                console.log("Checkbox", index, "Value:", checkbox.value, "Checked:", checkbox.checked);
            });
        });
    }
});
</script>
';

// Include footer
require_once '../includes/footer.php';
?>
