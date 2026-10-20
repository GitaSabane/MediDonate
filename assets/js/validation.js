/**
 * MediDonate - Client-side validation system
 * Uses data attributes for declarative validation
 */

(function() {
    'use strict';

    // ---- Validation Rules ----
    const validators = {
        required: function(value) {
            return value && value.trim().length > 0 ? true : 'This field is required.';
        },
        email: function(value) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(value) ? true : 'Please enter a valid email address.';
        },
        phone: function(value) {
            const digits = value.replace(/\D/g, '');
            return /^[6-9]\d{9}$/.test(digits) ? true : 'Enter a valid 10-digit Indian mobile number.';
        },
        password: function(value) {
            if (value.length < 8) return 'Password must be at least 8 characters.';
            if (!/[A-Z]/.test(value)) return 'Password must have at least one uppercase letter.';
            if (!/[a-z]/.test(value)) return 'Password must have at least one lowercase letter.';
            if (!/[0-9]/.test(value)) return 'Password must have at least one number.';
            if (!/[^A-Za-z0-9]/.test(value)) return 'Password must have at least one special character.';
            return true;
        },
        confirm: function(value, form) {
            const pwd = form.querySelector('input[name="password"], input[name="new_password"]');
            if (!pwd) return true;
            return value === pwd.value ? true : 'Passwords do not match.';
        },
        name: function(value) {
            if (!value || value.trim().length < 2) return 'Name must be at least 2 characters.';
            if (value.trim().length > 100) return 'Name is too long.';
            if (!/^[a-zA-Z\s\-\'\.]+$/.test(value.trim())) {
                return 'Name may only contain letters, spaces, hyphens, apostrophes, and dots.';
            }
            return true;
        },
        quantity: function(value) {
            const num = Number(value);
            if (!Number.isInteger(num) || num < 1) return 'Quantity must be a positive integer.';
            return true;
        },
        date: function(value) {
            if (!value) return 'Date is required.';
            const ts = Date.parse(value);
            if (isNaN(ts)) return 'Please enter a valid date.';
            return true;
        },
        expiry: function(value) {
            const result = validators.date(value);
            if (result !== true) return result;
            const today = new Date();
            today.setHours(0,0,0,0);
            const exp = new Date(value);
            if (exp < today) return 'This medicine has already expired and cannot be donated.';
            return true;
        },
        file: function(value, input) {
            if (!input.files || input.files.length === 0) return 'Please select a file.';
            const file = input.files[0];
            const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!allowed.includes(file.type)) {
                return 'Only JPG, JPEG, PNG, and WEBP images are allowed.';
            }
            if (file.size > 5 * 1024 * 1024) {
                return 'File size must not exceed 5 MB.';
            }
            return true;
        },
        select: function(value, input) {
            if (!value || value === '') return 'Please select an option.';
            return true;
        }
    };

    // ---- Apply validation to a single field ----
    function validateField(input) {
        const rules = input.dataset.validation ? input.dataset.validation.split(' ') : [];
        let error = null;
        for (let rule of rules) {
            if (validators[rule]) {
                const result = validators[rule](input.value, input.form);
                if (result !== true) {
                    error = result;
                    break;
                }
            }
        }
        // Also check custom validation attributes
        const min = input.getAttribute('min');
        const max = input.getAttribute('max');
        if (input.type === 'number') {
            const val = Number(input.value);
            if (!isNaN(val)) {
                if (min !== null && val < Number(min)) error = `Value must be at least ${min}.`;
                else if (max !== null && val > Number(max)) error = `Value must not exceed ${max}.`;
            }
        }
        if (input.type === 'date') {
            const minDate = input.getAttribute('min');
            if (minDate && input.value && input.value < minDate) {
                error = 'Date cannot be earlier than ' + minDate;
            }
        }
        // Set feedback
        const feedback = input.parentElement.querySelector('.invalid-feedback, .valid-feedback');
        const container = input.closest('.mb-3, .mb-2, .col, .row') || input.parentElement;
        if (error) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedback) {
                feedback.textContent = error;
                feedback.style.display = 'block';
            } else {
                // Create feedback if missing
                const div = document.createElement('div');
                div.className = 'invalid-feedback';
                div.textContent = error;
                container.appendChild(div);
            }
            input.setAttribute('aria-invalid', 'true');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (feedback) {
                feedback.style.display = 'block';
                feedback.textContent = input.dataset.validMessage || 'Looks good!';
            }
            input.setAttribute('aria-invalid', 'false');
            return true;
        }
    }

    // ---- Validate entire form ----
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('[data-validation]');
        inputs.forEach(input => {
            if (!validateField(input)) isValid = false;
        });
        // Also check any required fields without data-validation attribute? We'll rely on data-validation.
        return isValid;
    }

    // ---- Real-time event binding ----
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('[data-validation]');
            inputs.forEach(input => {
                // On blur: full validation
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                // On input/change: live feedback (only if not invalid? we can still update)
                input.addEventListener('input', function() {
                    // For password strength, we can update separately
                    if (this.dataset.validation && this.dataset.validation.includes('password')) {
                        updatePasswordStrength(this);
                    }
                    // We could validate but we'll keep to blur for less annoyance; but we can show
                    // we can re-validate on input but not show error until blur.
                });
                // On change (for selects, files)
                input.addEventListener('change', function() {
                    validateField(this);
                    if (this.type === 'file') {
                        previewFile(this);
                    }
                });
            });

            // Submit handler
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (validateForm(this)) {
                    // Disable submit button to prevent double submission
                    const btn = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
                    }
                    this.submit();
                } else {
                    // Scroll to first error
                    const firstError = this.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.focus();
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });

        // Password strength meter
        function updatePasswordStrength(input) {
            const val = input.value;
            const container = input.closest('.mb-3') || input.parentElement;
            let strengthEl = container.querySelector('.password-strength');
            if (!strengthEl) {
                strengthEl = document.createElement('div');
                strengthEl.className = 'password-strength mt-1 small';
                container.appendChild(strengthEl);
            }
            if (val.length === 0) {
                strengthEl.textContent = '';
                return;
            }
            let score = 0;
            if (val.length >= 8) score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const levels = ['Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['text-danger', 'text-warning', 'text-info', 'text-success'];
            strengthEl.textContent = 'Strength: ' + levels[score];
            strengthEl.className = 'password-strength mt-1 small ' + (colors[score] || '');
        }

        // Password show/hide toggle
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<i class="bi bi-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<i class="bi bi-eye"></i>';
                }
            });
        });

        // File preview
        function previewFile(input) {
            const previewContainer = document.getElementById(input.dataset.preview);
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            if (input.files) {
                Array.from(input.files).forEach(file => {
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
        }

        // Expiry warning detection (live)
        document.querySelectorAll('input[name="expiry_date"]').forEach(input => {
            input.addEventListener('change', function() {
                const val = this.value;
                if (!val) return;
                const today = new Date();
                today.setHours(0,0,0,0);
                const exp = new Date(val);
                const diff = (exp - today) / (1000*60*60*24);
                let msg = '';
                let cls = '';
                const feedback = this.parentElement.querySelector('.expiry-warning');
                if (diff < 0) {
                    msg = '⚠ This medicine has expired and cannot be donated.';
                    cls = 'text-danger';
                } else if (diff <= 30) {
                    msg = '⚠ This medicine expires within 30 days and may require additional verification.';
                    cls = 'text-warning';
                } else {
                    msg = '✓ Expiry date is valid.';
                    cls = 'text-success';
                }
                if (!feedback) {
                    const div = document.createElement('div');
                    div.className = 'expiry-warning mt-1 small';
                    this.parentElement.appendChild(div);
                }
                const el = this.parentElement.querySelector('.expiry-warning');
                el.textContent = msg;
                el.className = 'expiry-warning mt-1 small ' + cls;
            });
        });

    });

})();