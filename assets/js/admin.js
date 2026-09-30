/**
 * PawPal - Admin Panel Core Operations & AJAX Handlers
 */

// Toggle sidebar on mobile responsive views
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function (event) {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.querySelector('.admin-toggle-btn');
    
    if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('show')) {
        if (!sidebar.contains(event.target) && event.target !== toggleBtn && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    }
});

// Image Upload Preview helper
function setupImagePreview(inputId, previewId) {
    const fileInput = document.getElementById(inputId);
    const previewContainer = document.getElementById(previewId);
    
    if (!fileInput || !previewContainer) return;
    
    const previewImg = previewContainer.querySelector('img');
    const previewText = previewContainer.querySelector('.upload-text');
    
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function (e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'inline-block';
                }
                if (previewText) {
                    previewText.style.display = 'none';
                }
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Confirm Actions (Safe deletes, etc)
function confirmDelete(message) {
    return confirm(message || "Are you sure you want to delete this item? This action cannot be undone.");
}

// AJAX Adoption Requests approval/rejection toggle
$(document).ready(function () {
    // Auto initialize upload previews on edit/add forms
    setupImagePreview('pet_image', 'pet_image_preview');
    setupImagePreview('item_image', 'item_image_preview');

    $('.btn-action-adoption').on('click', function (e) {
        e.preventDefault();
        
        const $button = $(this);
        const adoptionId = $button.data('id');
        const action = $button.data('action'); // 'approve' or 'reject'
        const $row = $button.closest('tr');
        const $badge = $row.find('.adoption-status-badge');
        
        if (!confirm(`Are you sure you want to ${action} this adoption request?`)) {
            return;
        }
        
        // Show spinner / disable button
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        
        $.ajax({
            url: 'adoption_requests.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ajax_action: true,
                id: adoptionId,
                status_action: action
            },
            success: function (response) {
                if (response.success) {
                    // Update the status badge visual
                    if (action === 'approve') {
                        $badge.removeClass('status-pending status-rejected')
                              .addClass('status-approved')
                              .html('<i class="bi bi-check-circle-fill"></i> Approved');
                        // Fade out action buttons since it's now finalized
                        $row.find('.action-buttons-cell').html('<span class="text-success small fw-semibold">Approved</span>');
                    } else {
                        $badge.removeClass('status-pending status-approved')
                              .addClass('status-rejected')
                              .html('<i class="bi bi-x-circle-fill"></i> Rejected');
                        // Fade out action buttons
                        $row.find('.action-buttons-cell').html('<span class="text-danger small fw-semibold">Rejected</span>');
                    }
                    
                    // Show a transient alert on screen
                    showToast('success', response.message);
                } else {
                    $button.prop('disabled', false);
                    // Reset button icon
                    if (action === 'approve') {
                        $button.html('<i class="bi bi-check"></i> Approve');
                    } else {
                        $button.html('<i class="bi bi-x"></i> Reject');
                    }
                    showToast('danger', response.message || 'Operation failed');
                }
            },
            error: function () {
                $button.prop('disabled', false);
                if (action === 'approve') {
                    $button.html('<i class="bi bi-check"></i> Approve');
                } else {
                    $button.html('<i class="bi bi-x"></i> Reject');
                }
                showToast('danger', 'A system error occurred. Please try again.');
            }
        });
    });
});

// Dynamic Floating Toast Alert
function showToast(type, message) {
    // Check if container exists, create if not
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-warning');
    
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    $(toastContainer).append(toastHtml);
    
    // Auto remove after 4 seconds
    setTimeout(function () {
        $(`#${toastId}`).fadeOut(400, function() { $(this).remove(); });
    }, 4000);
}

// Chart.js helper functions for reports
function initLineChart(canvasId, labels, dataPoints, datasetLabel, themeColor) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: datasetLabel,
                data: dataPoints,
                borderColor: themeColor || '#4f46e5',
                backgroundColor: (themeColor || '#4f46e5') + '15',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        font: {
                            family: 'Outfit'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Outfit'
                        }
                    }
                }
            }
        }
    });
}

function initDoughnutChart(canvasId, labels, dataPoints, backgroundColors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataPoints,
                backgroundColor: backgroundColors || [
                    '#4f46e5', // Primary
                    '#f43f5e', // Accent
                    '#10b981', // Success
                    '#f59e0b', // Warning
                    '#06b6d4', // Info
                    '#8b5cf6'  // Purple
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            family: 'Outfit',
                            size: 11
                        },
                        boxWidth: 12,
                        padding: 15
                    }
                }
            },
            cutout: '65%'
        }
    });
}
