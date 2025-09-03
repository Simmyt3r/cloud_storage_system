// Cloud Storage System JavaScript

// Function to show modal
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "block";
    }
}

// Function to hide modal
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "none";
    }
}

// Close modal when clicking on the close button
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('close')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.style.display = "none";
        }
    }
});

// Close modal when clicking outside of it
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(modal => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });
});

// Function to confirm deletion
function confirmDelete(formId, itemName) {
    if (confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
        document.getElementById(formId).submit();
    }
}

// Function to toggle password visibility
function togglePasswordVisibility(inputId, buttonId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(buttonId);
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleButton.textContent = "Hide";
    } else {
        passwordInput.type = "password";
        toggleButton.textContent = "Show";
    }
}

// Function to validate form
function validateForm(formId, requiredFields) {
    const form = document.getElementById(formId);
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field.value.trim()) {
            field.style.borderColor = "red";
            isValid = false;
        } else {
            field.style.borderColor = "#ddd";
        }
    });
    
    return isValid;
}

// Function to format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Initialize file size formatting on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.file-size').forEach(element => {
        const bytes = parseInt(element.textContent);
        if (!isNaN(bytes)) {
            element.textContent = formatFileSize(bytes);
        }
    });
});

// Function to show folder password modal
function showFolderPasswordModal(folderId) {
    const modal = document.getElementById('folderPasswordModal');
    if (modal) {
        document.getElementById('folder_id').value = folderId;
        modal.style.display = "block";
    }
}