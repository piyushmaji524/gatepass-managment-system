/**
 * Copyright protection script for Gunayatan Gatepass System
 * Developed by Piyush Maji
 */

document.addEventListener('DOMContentLoaded', function() {
    // Disable right-click on copyright page
    if (document.querySelector('.copyright-notice')) {
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            showCopyrightAlert();
            return false;
        });
        
        // Disable keyboard shortcuts for copy
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 's' || e.key === 'u')) {
                e.preventDefault();
                showCopyrightAlert();
                return false;
            }
        });
        
        // Disable text selection
        document.querySelectorAll('.copyright-notice p, .copyright-notice h5').forEach(element => {
            element.style.userSelect = 'none';
            element.style.webkitUserSelect = 'none';
            element.style.msUserSelect = 'none';
            element.style.mozUserSelect = 'none';
        });
    }
    
    // Function to display copyright warning
    function showCopyrightAlert() {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'position-fixed top-0 start-0 p-3';
        alertDiv.style.zIndex = '9999';
        
        const toastDiv = document.createElement('div');
        toastDiv.className = 'toast show align-items-center text-white bg-danger';
        toastDiv.setAttribute('role', 'alert');
        toastDiv.setAttribute('aria-live', 'assertive');
        toastDiv.setAttribute('aria-atomic', 'true');
        
        const toastContent = document.createElement('div');
        toastContent.className = 'd-flex';
        
        const toastBody = document.createElement('div');
        toastBody.className = 'toast-body';
        toastBody.innerHTML = '<i class="fas fa-shield-alt me-2"></i> <strong>Copyright Protected</strong>: Copying content from this page is not allowed.';
        
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close btn-close-white me-2 m-auto';
        closeButton.setAttribute('data-bs-dismiss', 'toast');
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.onclick = function() {
            document.body.removeChild(alertDiv);
        };
        
        toastContent.appendChild(toastBody);
        toastContent.appendChild(closeButton);
        toastDiv.appendChild(toastContent);
        alertDiv.appendChild(toastDiv);
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (document.body.contains(alertDiv)) {
                document.body.removeChild(alertDiv);
            }
        }, 5000);
    }
    
    // Add a hidden watermark to the page
    const watermark = document.createElement('div');
    watermark.style.position = 'fixed';
    watermark.style.top = '0';
    watermark.style.left = '0';
    watermark.style.width = '100%';
    watermark.style.height = '100%';
    watermark.style.opacity = '0.05';
    watermark.style.pointerEvents = 'none';
    watermark.style.zIndex = '1000';
    watermark.style.background = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\' viewBox=\'0 0 200 200\'%3E%3Ctext x=\'0\' y=\'50\' transform=\'rotate(-45 100 100)\' fill=\'%23000\' font-size=\'16\' opacity=\'0.5\'%3EPiyush Maji © Copyright%3C/text%3E%3C/svg%3E") repeat';
    
    if (document.querySelector('.copyright-notice')) {
        document.body.appendChild(watermark);
    }
});
