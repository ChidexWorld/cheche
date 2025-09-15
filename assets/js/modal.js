// Simple Modal JavaScript
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.style.zIndex = '9999';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        hideModal(e.target.id);
    }

    if (e.target.classList.contains('modal-close')) {
        e.preventDefault();
        const modal = e.target.closest('.modal');
        if (modal) {
            hideModal(modal.id);
        }
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'flex') {
                hideModal(modal.id);
            }
        });
    }
});

// Handle enrollment
function handleEnrollment(courseId) {
    fetch('../api/enroll.php?course_id=' + courseId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Enrollment failed. Please try again.');
    });
}

// Handle unenrollment
function handleUnenrollment(courseId) {
    fetch('../api/unenroll.php?course_id=' + courseId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Unenrollment failed. Please try again.');
    });
}