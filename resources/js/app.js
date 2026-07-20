// Import Bootstrap and its dependencies
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap';

// Import jQuery (required by Bootstrap and DataTables)
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

// Import DataTables and Bootstrap integration
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';

// Import Alpine.js for lightweight reactivity
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Initialize DataTables globally
window.DataTable = require('datatables.net-bs5');

// Global utility functions
window.showAlert = function(message, type = 'info', duration = 5000) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const alertContainer = document.getElementById('alert-container') || document.body;
    const div = document.createElement('div');
    div.innerHTML = alertHtml;
    alertContainer.insertBefore(div.firstElementChild, alertContainer.firstChild);
    
    if (duration > 0) {
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) alert.remove();
        }, duration);
    }
};

// CSRF token setup for AJAX requests
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': token
        }
    });
}

console.log('NexusOS Frontend Stack Initialized');
