/**
 * Doctor Availability Sidebar - HTML AJAX version
 */

/**
 * Open the sidebar and load initial content
 */
function openAvailabilitySidebar() {
    const sidebar = document.getElementById('availability-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.add('open');
        overlay.classList.add('active');

        // Load initial empty form or default View
        // We pass empty params to just get the form first
        fetchContent('');
    }
}

/**
 * Close the sidebar
 */
function closeAvailabilitySidebar() {
    const sidebar = document.getElementById('availability-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    }
}

/**
 * Submit form data via AJAX (triggered on change)
 */
function submitAvailabilityForm() {
    const doctorId = document.getElementById('sidebar_doctor_id').value;
    const date = document.getElementById('sidebar_date').value;

    fetchContent(`doctor_id=${doctorId}&date=${date}`);
}

/**
 * Fetch HTML content from backend
 */
function fetchContent(queryParams) {
    const contentDiv = document.getElementById('sidebar-content');
    if (!contentDiv) return;

    // Optional: Add loading spinner if needed, but for quick updates might flicker
    // contentDiv.innerHTML = '<p class="w3-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</p>';

    // Standard fetch for HTML content
    fetch('fetch_availability.php?' + queryParams)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            contentDiv.innerHTML = html;
        })
        .catch(err => {
            console.error('Error fetching availability:', err);
            contentDiv.innerHTML = '<div class="w3-panel w3-red"><p>Erreur lors du chargement des disponibilités. Veuillez réessayer.</p></div>';
        });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function () {
    // Close sidebar when clicking overlay
    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeAvailabilitySidebar);
    }
});

