// Custom JavaScript for the new gatepass page
document.addEventListener('DOMContentLoaded', function() {
    console.log("New Gatepass page loaded");
    
    // Initialize first item with returnable checkbox
    function initializeNewGatepass() {
        const itemsContainer = document.getElementById('itemsContainer');
        if (!itemsContainer) {
            console.error("Items container not found");
            return;
        }

        // Only add a new item if the container is empty
        if (itemsContainer.children.length === 0) {
            console.log("Adding initial item with returnable checkbox");
            
            const itemHtml = `
                <div class="item-entry" id="item-1">
                    <span class="remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></span>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="item_name_1" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="item_name_1" name="item_name[]" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="item_quantity_1" class="form-label">Quantity</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="item_quantity_1" name="item_quantity[]" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="item_unit_1" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="item_unit_1" name="item_unit[]" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="item_returnable_1" name="item_returnable[]" value="0">
                                <label class="form-check-label" for="item_returnable_1">
                                    Returnable
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
        }
    }
    
    // Call the initialization function
    initializeNewGatepass();
    
    // Add debug information
    setTimeout(() => {
        const returnableCheckboxes = document.querySelectorAll('input[name="item_returnable[]"]');
        console.log("Returnable checkboxes found: " + returnableCheckboxes.length);
        returnableCheckboxes.forEach((checkbox, idx) => {
            console.log(`Checkbox ${idx}: ID=${checkbox.id}, Value=${checkbox.value}`);
        });
    }, 500);
});
