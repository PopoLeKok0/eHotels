/**
 * e-Hotels Main JavaScript
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize global functionality
    initDateValidation();
    initAlertDismissal();
    initFormValidation();
    initAjaxSearchForm(); // Initialize AJAX search form handling
    // initDropdownToggle(); // Commented out - Bootstrap should handle this automatically
});

/**
 * Initialize AJAX handling for the room search form.
 */
function initAjaxSearchForm() {
    const searchForm = document.getElementById('ajaxSearchForm'); // Target the form with ID 'ajaxSearchForm'
    const resultsContainer = document.getElementById('searchResultsContainer');
    const loadingIndicator = document.getElementById('searchLoadingIndicator');
    const errorContainer = document.getElementById('searchErrorContainer');
    let debounceTimer;

    if (!searchForm || !resultsContainer || !loadingIndicator || !errorContainer) {
        // console.log("Required elements for AJAX search not found on this page.");
        return; // Exit if essential elements aren't present
    }

    // Function to fetch results via AJAX
    const fetchResults = () => {
        clearTimeout(debounceTimer); // Clear previous timer
        loadingIndicator.style.display = 'block'; // Show loading
        errorContainer.innerHTML = ''; // Clear previous errors
        resultsContainer.innerHTML = ''; // Clear previous results

        const formData = new FormData(searchForm);
        const params = new URLSearchParams(formData).toString();
        let searchUrl = searchForm.action;
        // Check if action URL already contains a query string
        if (searchUrl.includes('?')) {
            searchUrl += '&ajax=1'; // Append if other params exist
        } else {
            searchUrl += '?ajax=1'; // Start query string if none exist
        }

        // Construct the final URL: base URL (with ?ajax=1 or &ajax=1 already) + & + other params
        // Ensure 'params' is not empty before adding '&'
        const finalUrl = params ? (searchUrl + '&' + params) : searchUrl;

        fetch(finalUrl, { // <-- FIX HERE: Use the correctly constructed finalUrl
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text(); // Expecting HTML response
        })
        .then(html => {
            resultsContainer.innerHTML = html;
        })
        .catch(error => {
            console.error('AJAX Search Error:', error);
            errorContainer.innerHTML = `<div class="alert alert-danger">Error loading search results: ${error.message}. Please try again later.</div>`;
        })
        .finally(() => {
            loadingIndicator.style.display = 'none'; // Hide loading
        });
    };

    // Debounce function to limit AJAX calls
    const debounceFetch = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchResults, 500); // Wait 500ms after last change
    };

    // Attach event listeners to form fields
    searchForm.querySelectorAll('input, select').forEach(input => {
        // Use 'input' for text fields, 'change' for selects/dates/checkboxes
        const eventType = (input.type === 'text' || input.type === 'number') ? 'input' : 'change';
        input.addEventListener(eventType, debounceFetch);
    });

    // Prevent default form submission (optional, could allow normal submit as fallback)
    searchForm.addEventListener('submit', event => {
        event.preventDefault(); // Prevent full page reload
        fetchResults(); // Fetch immediately on explicit submit
    });

    // Initial fetch on page load if parameters are present (e.g., coming from index.php)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString().length > 0) { // Check if there are any GET params
        fetchResults();
    }
}

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