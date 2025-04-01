/**
 * e-Hotels Main JavaScript
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize global functionality
    initDateValidation();
    initAlertDismissal();
    initFormValidation();
    // initDropdownToggle(); // Commented out - Bootstrap should handle this automatically
});

/**
 * Initialize date validation for booking forms
 */
function initDateValidation() {
    const startDateInputs = document.querySelectorAll('input[type="date"][name="start_date"]');
    const endDateInputs = document.querySelectorAll('input[type="date"][name="end_date"]');
    
    // Apply validation to all date input pairs
    startDateInputs.forEach(startDateInput => {
        // Find the corresponding end date input (usually the next date input)
        const form = startDateInput.closest('form');
        const endDateInput = form ? form.querySelector('input[type="date"][name="end_date"]') : null;
        
        if (endDateInput) {
            // Set minimum end date based on start date
            startDateInput.addEventListener('change', function() {
                if (startDateInput.value) {
                    const minEndDate = new Date(startDateInput.value);
                    minEndDate.setDate(minEndDate.getDate() + 1);
                    endDateInput.min = minEndDate.toISOString().split('T')[0];
                    
                    // If end date is now invalid, update it
                    if (endDateInput.value && new Date(endDateInput.value) <= new Date(startDateInput.value)) {
                        const newEndDate = new Date(startDateInput.value);
                        newEndDate.setDate(newEndDate.getDate() + 1);
                        endDateInput.value = newEndDate.toISOString().split('T')[0];
                    }
                }
            });
            
            // Apply immediately if values exist
            if (startDateInput.value) {
                const event = new Event('change');
                startDateInput.dispatchEvent(event);
            }
        }
    });
}

/**
 * Initialize alert dismissal after timeout
 */
function initAlertDismissal() {
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsDismiss = new bootstrap.Alert(alert);
            bsDismiss.close();
        }, 5000);
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    // Add validation for forms with class 'needs-validation'
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Format a price as currency
 * @param {number} price - The price to format
 * @returns {string} The formatted price
 */
function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2
    }).format(price);
}

/**
 * Calculate total price for a booking
 * @param {number} pricePerNight - The price per night
 * @param {string} startDate - The start date (YYYY-MM-DD)
 * @param {string} endDate - The end date (YYYY-MM-DD)
 * @returns {number} The total price
 */
function calculateTotalPrice(pricePerNight, startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const nights = Math.round((end - start) / (1000 * 60 * 60 * 24));
    return pricePerNight * nights;
}

/**
 * Update the displayed total price in a booking form
 * @param {HTMLElement} priceElement - The element showing the price
 * @param {number} pricePerNight - The price per night
 * @param {HTMLInputElement} startDateInput - The start date input
 * @param {HTMLInputElement} endDateInput - The end date input
 */
function updateTotalPrice(priceElement, pricePerNight, startDateInput, endDateInput) {
    if (startDateInput.value && endDateInput.value) {
        const totalPrice = calculateTotalPrice(
            pricePerNight,
            startDateInput.value,
            endDateInput.value
        );
        priceElement.textContent = formatPrice(totalPrice);
    }
}

/**
 * Display a confirmation message before form submission
 * @param {HTMLFormElement} form - The form element
 * @param {string} message - The confirmation message
 * @returns {boolean} True if confirmed, false otherwise
 */
function confirmSubmission(form, message) {
    return confirm(message);
}

/**
 * Handle AJAX form submission
 * @param {HTMLFormElement} form - The form element
 * @param {function} successCallback - Function to call on success
 */
function submitFormAjax(form, successCallback) {
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: form.method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof successCallback === 'function') {
                successCallback(data);
            }
        } else {
            alert(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request');
    });
}

/**
 * Initialize dropdown toggle functionality
 * COMMENTED OUT - Bootstrap should handle this automatically via data-bs-toggle
 */
/*
function initDropdownToggle() {
    // Enable all dropdowns
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}
*/ 