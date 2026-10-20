/**
 * MediDonate - Main JavaScript with validation
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Auto-dismiss alerts ----
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000);
    });

    // ---- Confirm actions ----
    document.querySelectorAll('[data-confirm]').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ---- Validation functions ----
    function validateField(field) {
        const value = field.value.trim();
        const type = field.getAttribute('type') || field.tagName.toLowerCase();
        const errors = [];
        let isValid = true;

        // Required
        if (field.hasAttribute('required') && !value) {
            errors.push('This field is required.');
            isValid = false;
        }

        // Email
        if (type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push('Enter a valid email address.');
            isValid = false;
        }

        // Phone (optional)
        if (field.id === 'phone' && value && !/^[0-9+\-\(\)\s]{7,20}$/.test(value)) {
            errors.push('Enter a valid phone number.');
            isValid = false;
        }

        // Password (min 8)
        if (field.id === 'password' || field.id === 'new' || field.name === 'password') {
            if (value && value.length < 8) {
                errors.push('Password must contain at least 8 characters.');
                isValid = false;
            }
        }

        // Confirm password
        if (field.id === 'confirm_password' || field.id === 'confirm' || field.name === 'confirm_password') {
            const pwdField = document.getElementById('password') || document.getElementById('new');
            if (pwdField && value !== pwdField.value) {
                errors.push('Passwords do not match.');
                isValid = false;
            }
        }

        // Quantity (positive integer)
        if (field.id === 'quantity' || field.name === 'quantity') {
            const num = parseInt(value);
            if (value && (isNaN(num) || num < 1)) {
                errors.push('Quantity must be greater than 0.');
                isValid = false;
            }
            // Max for request form
            const max = parseInt(field.getAttribute('max'));
            if (max && num > max) {
                errors.push('Quantity cannot exceed available quantity.');
                isValid = false;
            }
        }

        // Expiry date
        if (field.id === 'expiry_date') {
            const today = new Date();
            const exp = new Date(value);
            if (value) {
                if (isNaN(exp.getTime())) {
                    errors.push('Invalid date.');
                    isValid = false;
                } else if (exp < today) {
                    errors.push('This medicine is expired and cannot be donated.');
                    isValid = false;
                } else {
                    // Check if within 30 days -> warning
                    const diffDays = (exp - today) / (1000 * 60 * 60 * 24);
                    const warningEl = document.getElementById('expiryWarning');
                    if (warningEl) {
                        if (diffDays <= 30) {
                            warningEl.style.display = 'block';
                        } else {
                            warningEl.style.display = 'none';
                        }
                    }
                }
            }
        }

        // Manufacturing date <= expiry date
        if (field.id === 'manufacturing_date') {
            const expiry = document.getElementById('expiry_date');
            if (expiry && value && expiry.value) {
                if (new Date(value) > new Date(expiry.value)) {
                    errors.push('Manufacturing date cannot be after expiry date.');
                    isValid = false;
                }
            }
        }

        return { isValid, errors };
    }

    function showFieldError(field, errors) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.innerHTML = errors.join('<br>');
        }
        // Remove green tick
        const tick = field.parentElement.querySelector('.valid-tick');
        if (tick) tick.remove();
    }

    function showFieldSuccess(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        // Remove existing tick
        const parent = field.parentElement;
        let tick = parent.querySelector('.valid-tick');
        if (!tick) {
            tick = document.createElement('span');
            tick.className = 'valid-tick text-success ms-1';
            tick.innerHTML = '✓';
            parent.appendChild(tick);
        }
        // Remove error message
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.innerHTML = '';
        }
    }

    function resetField(field) {
        field.classList.remove('is-invalid', 'is-valid');
        const tick = field.parentElement.querySelector('.valid-tick');
        if (tick) tick.remove();
        const feedback = field.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.innerHTML = '';
        }
    }

    // ---- Attach validation to forms ----
    document.querySelectorAll('form').forEach(function(form) {
        if (form.hasAttribute('novalidate')) {
            // Validate on submit
            form.addEventListener('submit', function(e) {
                let allValid = true;
                const inputs = this.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    if (input.hasAttribute('required') || input.type === 'email' || input.id === 'password' || input.id === 'confirm_password' || input.id === 'expiry_date' || input.id === 'quantity' || input.id === 'phone') {
                        const result = validateField(input);
                        if (!result.isValid) {
                            allValid = false;
                            showFieldError(input, result.errors);
                        } else if (input.value.trim() !== '') {
                            showFieldSuccess(input);
                        } else {
                            resetField(input);
                        }
                    }
                });
                if (!allValid) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Scroll to first error
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) firstError.focus();
                }
            });

            // Validate on blur (except submit buttons)
            form.querySelectorAll('input, select, textarea').forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') || this.type === 'email' || this.id === 'password' || this.id === 'confirm_password' || this.id === 'expiry_date' || this.id === 'quantity' || this.id === 'phone') {
                        if (this.value.trim() !== '') {
                            const result = validateField(this);
                            if (!result.isValid) {
                                showFieldError(this, result.errors);
                            } else {
                                showFieldSuccess(this);
                            }
                        } else {
                            resetField(this);
                        }
                    }
                });
                // Live feedback for password, confirm, expiry, quantity
                if (input.id === 'password' || input.id === 'confirm_password' || input.id === 'expiry_date' || input.id === 'quantity' || input.id === 'confirm') {
                    input.addEventListener('input', function() {
                        if (this.value.trim() !== '') {
                            const result = validateField(this);
                            if (!result.isValid) {
                                showFieldError(this, result.errors);
                            } else {
                                showFieldSuccess(this);
                            }
                        } else {
                            resetField(this);
                        }
                    });
                }
            });
        }
    });

    // ---- Password strength indicator (optional) ----
    const pwdInput = document.getElementById('password');
    if (pwdInput) {
        const strengthEl = document.createElement('small');
        strengthEl.id = 'passwordStrength';
        strengthEl.className = 'd-block mt-1';
        pwdInput.parentElement.appendChild(strengthEl);
        pwdInput.addEventListener('input', function() {
            const val = this.value;
            if (val.length === 0) {
                strengthEl.textContent = '';
                return;
            }
            let score = 0;
            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;
            const levels = ['Weak', 'Fair', 'Good', 'Strong'];
            const classes = ['text-danger', 'text-warning', 'text-info', 'text-success'];
            strengthEl.textContent = 'Strength: ' + levels[score];
            strengthEl.className = 'd-block mt-1 ' + (classes[score] || 'text-secondary');
        });
    }

    // ---- Notification polling (unchanged) ----
    // (Keep existing polling code here)
});

/**
 * MediDonate - Main JavaScript
 * Provides UI enhancements, form validation, AJAX notifications,
 * and interactive components.
 */

document.addEventListener('DOMContentLoaded', function() {

    // ---- Auto-dismiss alerts after 6 seconds ----
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 6000);
    });

    // ---- Confirm actions with modal ----
    const confirmLinks = document.querySelectorAll('[data-confirm]');
    confirmLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ---- Form validation (client-side) ----
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });

    // ---- Password strength indicator (optional) ----
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            const strength = document.getElementById('passwordStrength');
            if (!strength) return;
            if (val.length === 0) {
                strength.textContent = '';
                strength.className = '';
                return;
            }
            let score = 0;
            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;
            const levels = ['Weak', 'Fair', 'Good', 'Strong'];
            const classes = ['text-danger', 'text-warning', 'text-info', 'text-success'];
            strength.textContent = 'Strength: ' + levels[score];
            strength.className = classes[score] || 'text-secondary';
        });
    }

    // ---- Notification count update (AJAX poll) ----
    const notifBadge = document.getElementById('notifBadge');
    const notifBadgeSmall = document.getElementById('notifBadgeSmall');
    if (notifBadge || notifBadgeSmall) {
        // Poll every 30 seconds
        setInterval(function() {
            fetch('../ajax/notif-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        if (notifBadge) notifBadge.textContent = data.count;
                        if (notifBadgeSmall) notifBadgeSmall.textContent = data.count;
                    }
                })
                .catch(err => console.log('Notification poll error:', err));
        }, 30000);
    }

    // ---- Mark all notifications read ----
    const markReadBtn = document.getElementById('markAllRead');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch('../ajax/mark-read.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (notifBadge) notifBadge.textContent = '0';
                        if (notifBadgeSmall) notifBadgeSmall.textContent = '0';
                        // Reload page to reflect read state
                        location.reload();
                    }
                });
        });
    }

    // ---- Image preview for uploads ----
    const imageInputs = document.querySelectorAll('input[type="file"][data-preview]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const previewContainer = document.getElementById(previewId);
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail m-1';
                        img.style.maxWidth = '120px';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    });

    // ---- Tooltip initialization ----
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));

    // ---- Popover initialization ----
    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    popovers.forEach(el => new bootstrap.Popover(el));

    // ---- Print receipt ----
    const printBtn = document.getElementById('printReceipt');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    // ---- Dynamic filter on tables (client side) ----
    const filterInputs = document.querySelectorAll('.table-filter');
    filterInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector(this.dataset.table);
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });

    // ---- CSRF token auto-add to forms (if missing) ----
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) {
        const token = csrfMeta.getAttribute('content');
        document.querySelectorAll('form').forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = token;
                form.prepend(input);
            }
        });
    }

    // ---- Expiry date validation (must be > today) ----
    const expiryInput = document.querySelector('input[name="expiry_date"]');
    if (expiryInput) {
        const today = new Date().toISOString().split('T')[0];
        expiryInput.setAttribute('min', today);
    }

    console.log('MediDonate JS loaded successfully.');
});