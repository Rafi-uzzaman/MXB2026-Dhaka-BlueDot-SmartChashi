// Smart Chashi - Main JavaScript Application
(function($) {
    'use strict';

    // Configuration
    const config = {
        apiBaseUrl: (typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'api/',
        imageUploadLimit: 50 * 1024 * 1024, // 50MB
    };

    // Global App Object
    const App = {
        init: function() {
            this.setupEventListeners();
            this.setupAjax();
        },

        setupAjax: function() {
            $.ajaxSetup({
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                dataType: 'json',
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    App.showAlert('An error occurred. Please try again.', 'danger');
                }
            });
        },

        setupEventListeners: function() {
            // Login form
            $(document).on('submit', '#loginForm', function(e) {
                e.preventDefault();
                App.handleLogin();
            });

            // Register form
            $(document).on('submit', '#registerForm', function(e) {
                e.preventDefault();
                App.handleRegister();
            });

            // Crop form
            $(document).on('submit', '#cropForm', function(e) {
                e.preventDefault();
                App.handleCropSubmit();
            });

            // Disease detection form
            $(document).on('submit', '#diseaseForm', function(e) {
                e.preventDefault();
                App.handleDiseaseDetection();
            });

            // Chat form
            $(document).on('submit', '#chatForm', function(e) {
                e.preventDefault();
                App.handleChatMessage();
            });

            // Alert close buttons
            $(document).on('click', '.alert-close', function() {
                $(this).closest('.alert').slideUp(200, function() {
                    $(this).remove();
                });
            });

            // Modal close
            $(document).on('click', '.modal-close', function() {
                $(this).closest('.modal').removeClass('show');
            });

            $(document).on('click', '.modal', function(e) {
                if (e.target === this) {
                    $(this).removeClass('show');
                }
            });

            // Delete confirmation
            $(document).on('click', '.btn-delete', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });

            // Auto-detect location
            if (navigator.geolocation) {
                $(document).on('click', '.btn-detect-location', function() {
                    App.detectLocation();
                });
            }

            // Like post
            $(document).on('click', '.btn-like', function() {
                const postId = $(this).data('post-id');
                App.likePost(postId);
            });

            // Password toggle (show/hide)
            $(document).on('click', '.password-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                
                const targetId = $(this).attr('data-target');
                const input = $('#' + targetId);
                const icon = $(this).find('.eye-icon');

                
                if (input.length) {
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.text('visibility_off');
                    } else {
                        input.attr('type', 'password');
                        icon.text('visibility');
                    }
                }
            });
        },

        // ===== Authentication =====
        handleLogin: function() {
            const emailOrPhone = $('#email').val().trim() || $('#emailOrPhone').val().trim();
            const password = $('#password').val();
            const btn = $('#loginBtn');
            const btnText = btn.find('.btn-text');
            const btnLoader = btn.find('.btn-loader');

            if (!emailOrPhone) {
                this.showAlert('Please enter email or phone number.', 'danger');
                return;
            }

            if (password.length < 6) {
                this.showAlert('Password must be at least 6 characters.', 'danger');
                return;
            }

            // Show loading
            btn.prop('disabled', true);
            if (btnText.length) btnText.hide();
            if (btnLoader.length) btnLoader.show();

            const data = {
                action: 'login',
                emailOrPhone: emailOrPhone,
                password: password
            };

            fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'ajax/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    App.showAlert(response.message || 'Login successful!', 'success');
                    setTimeout(() => {
                        window.location.href = (typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + (response.redirect || 'dashboard');
                    }, 1000);
                } else if (response.needs_verification) {
                    App.showAlert(response.message, 'warning');
                    // Show verification UI if available
                    if (typeof showVerificationCard === 'function') {
                        showVerificationCard(response.email);
                    }
                } else {
                    App.showAlert(response.message || 'Login failed.', 'danger');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                App.showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                btn.prop('disabled', false);
                if (btnText.length) btnText.show();
                if (btnLoader.length) btnLoader.hide();
            });
        },

        handleRegister: function() {
            // This is now handled inline in register.php with multi-step flow
            // Keeping for backwards compatibility
            const firstName = $('#firstName').val().trim();
            const lastName = $('#lastName').val().trim();
            const email = $('#email').val().trim();
            const phone = $('#phone').val().trim();
            const password = $('#password').val();
            const passwordConfirm = $('#passwordConfirm').val();
            const role = $('input[name="role"]:checked').val() || 'farmer';
            const btn = $('#registerBtn');
            const btnText = btn.find('.btn-text');
            const btnLoader = btn.find('.btn-loader');

            // Validation
            if (!firstName || firstName.length < 2) {
                this.showAlert('First name must be at least 2 characters.', 'danger');
                return;
            }

            if (!this.validateEmail(email)) {
                this.showAlert('Please enter a valid email address.', 'danger');
                return;
            }

            if (!phone || phone.replace(/\D/g, '').length < 10) {
                this.showAlert('Please enter a valid phone number.', 'danger');
                return;
            }

            if (password.length < 8) {
                this.showAlert('Password must be at least 8 characters.', 'danger');
                return;
            }

            if (!this.validatePasswordStrength(password)) {
                this.showAlert('Password must contain uppercase, lowercase, number, and special character.', 'danger');
                return;
            }

            if (password !== passwordConfirm) {
                this.showAlert('Passwords do not match.', 'danger');
                return;
            }

            // Show loading
            btn.prop('disabled', true);
            if (btnText.length) btnText.hide();
            if (btnLoader.length) btnLoader.show();

            const data = {
                action: 'send_code',
                firstName: firstName,
                lastName: lastName,
                email: email,
                phone: phone,
                password: password,
                role: role
            };

            fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'ajax/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    App.showAlert(response.message, 'success');
                    // Trigger step 2 if function exists
                    if (typeof goToVerificationStep === 'function') {
                        goToVerificationStep(response.email);
                    }
                } else {
                    App.showAlert(response.message || 'Registration failed.', 'danger');
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                App.showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                btn.prop('disabled', false);
                if (btnText.length) btnText.show();
                if (btnLoader.length) btnLoader.hide();
            });
        },

        validatePasswordStrength: function(password) {
            return password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        },

        // ===== Forgot Password =====
        handleForgotPassword: function(emailOrPhone) {
            if (!emailOrPhone) {
                this.showAlert('Please enter email or phone number.', 'danger');
                return Promise.reject('No email/phone provided');
            }

            return fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'ajax/forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_code', emailOrPhone: emailOrPhone })
            })
            .then(response => response.json());
        },

        handleVerifyResetCode: function(code) {
            return fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'ajax/forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'verify_code', code: code })
            })
            .then(response => response.json());
        },

        handleResetPassword: function(password, passwordConfirm) {
            if (password !== passwordConfirm) {
                this.showAlert('Passwords do not match.', 'danger');
                return Promise.reject('Passwords do not match');
            }

            if (!this.validatePasswordStrength(password)) {
                this.showAlert('Password must contain uppercase, lowercase, number, and special character.', 'danger');
                return Promise.reject('Weak password');
            }

            return fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + 'ajax/forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset_password', password: password })
            })
            .then(response => response.json());
        },

        handleResendCode: function(type = 'registration') {
            const endpoint = type === 'forgot' ? 'ajax/forgot-password.php' : 'ajax/auth.php';
            
            return fetch((typeof baseUrl !== 'undefined' ? baseUrl : '/smartcashi/') + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'resend_code' })
            })
            .then(response => response.json());
        },

        // ===== Crop Management =====
        handleCropSubmit: function() {
            const cropName = $('#cropName').val().trim();
            const variety = $('#variety').val().trim();
            const area = $('#area').val();
            const plantedDate = $('#plantedDate').val();
            const expectedHarvest = $('#expectedHarvest').val();

            if (!cropName) {
                this.showAlert('Crop name is required.', 'danger');
                return;
            }

            if (!area || area <= 0) {
                this.showAlert('Please enter a valid area in hectares.', 'danger');
                return;
            }

            const data = {
                action: 'add-crop',
                cropName: cropName,
                variety: variety,
                area: area,
                plantedDate: plantedDate,
                expectedHarvest: expectedHarvest
            };

            $.post(config.apiBaseUrl + 'handler.php', data, function(response) {
                if (response.success) {
                    App.showAlert('Crop added successfully!', 'success');
                    $('#cropForm')[0].reset();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    App.showAlert(response.message || 'Failed to add crop.', 'danger');
                }
            });
        },

        // ===== Disease Detection =====
        handleDiseaseDetection: function() {
            const cropId = $('#cropId').val();
            const image = $('#diseaseImage')[0].files[0];

            if (!cropId) {
                this.showAlert('Please select a crop.', 'danger');
                return;
            }

            if (!image) {
                this.showAlert('Please select an image.', 'danger');
                return;
            }

            if (image.size > config.imageUploadLimit) {
                this.showAlert('Image size must be less than 50MB.', 'danger');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'analyze-disease');
            formData.append('cropId', cropId);
            formData.append('image', image);

            $.ajax({
                url: config.apiBaseUrl + 'handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        App.displayDiseaseResults(response.data);
                        App.showAlert('Disease analysis complete!', 'success');
                    } else {
                        App.showAlert(response.message || 'Analysis failed.', 'danger');
                    }
                }
            });
        },

        displayDiseaseResults: function(data) {
            const resultsHtml = `
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Analysis Results</h3>
                    </div>
                    <div class="card-content">
                        <p><strong>Disease:</strong> ${data.disease || 'No disease detected'}</p>
                        <p><strong>Severity:</strong> <span class="badge badge-${this.getSeverityClass(data.severity)}">${data.severity || 'N/A'}</span></p>
                        <p><strong>Confidence:</strong> ${(data.confidence * 100).toFixed(2)}%</p>
                        ${data.treatment ? `<p><strong>Treatment:</strong> ${data.treatment}</p>` : ''}
                        ${data.recommendations ? `<p><strong>Recommendations:</strong> ${data.recommendations}</p>` : ''}
                    </div>
                </div>
            `;
            $('#diseaseResults').html(resultsHtml).show();
        },

        getSeverityClass: function(severity) {
            switch(severity.toLowerCase()) {
                case 'high': return 'danger';
                case 'medium': return 'warning';
                case 'low': return 'success';
                default: return 'info';
            }
        },

        // ===== Chat =====
        handleChatMessage: function() {
            const message = $('#chatInput').val().trim();
            const language = $('input[name="language"]:checked').val() || 'english';

            if (!message) {
                return;
            }

            // Add user message to chat
            this.addChatMessage(message, 'user');
            $('#chatInput').val('').focus();

            const data = {
                action: 'send-message',
                message: message,
                language: language
            };

            $.post(config.apiBaseUrl + 'handler.php', data, function(response) {
                if (response.success) {
                    App.addChatMessage(response.reply, 'bot');
                } else {
                    App.addChatMessage('Sorry, I could not process your request.', 'bot');
                }
                $('#chatContainer').scrollTop($('#chatContainer')[0].scrollHeight);
            });
        },

        addChatMessage: function(message, sender) {
            const messageHtml = `
                <div class="chat-message chat-${sender}">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `;
            $('#chatContainer').append(messageHtml);
        },

        // ===== Community =====
        likePost: function(postId) {
            const data = {
                action: 'like-post',
                postId: postId
            };

            $.post(config.apiBaseUrl + 'handler.php', data, function(response) {
                if (response.success) {
                    $('[data-post-id="' + postId + '"]').text(response.likes + ' Likes');
                }
            });
        },

        // ===== Location =====
        detectLocation: function() {
            if (!navigator.geolocation) {
                this.showAlert('Geolocation is not supported by your browser.', 'danger');
                return;
            }

            const btn = $('.btn-detect-location');
            btn.prop('disabled', true).text('Detecting...');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    $('#locationLat').val(lat);
                    $('#locationLng').val(lng);

                    App.getReverseGeocoding(lat, lng);
                    btn.prop('disabled', false).text('Detect Location');
                },
                function(error) {
                    App.showAlert('Unable to get your location: ' + error.message, 'danger');
                    btn.prop('disabled', false).text('Detect Location');
                }
            );
        },

        getReverseGeocoding: function(lat, lng) {
            // Placeholder for reverse geocoding using Google Maps API
            // In production, call Google Maps Geocoding API
            this.showAlert('Location detected successfully!', 'success');
        },

        // ===== Utility Functions =====
        validateEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        showAlert: function(message, type = 'info') {
            const alertId = 'toast-' + Date.now();
            const alertHtml = `
                <div class="alert alert-${type}" id="${alertId}">
                    <span>${App.escapeHtml(message)}</span>
                </div>
            `;

            $('body').append(alertHtml);
            const $alert = $('#' + alertId);

            // Auto-remove after 4 seconds (toast style)
            const removeTimer = setTimeout(() => {
                $alert.addClass('removing');
                setTimeout(() => {
                    $alert.remove();
                }, 300); // Wait for animation
            }, 4000);

            // Allow manual dismissal on click
            $alert.on('click', function() {
                clearTimeout(removeTimer);
                $(this).addClass('removing');
                setTimeout(() => {
                    $(this).remove();
                }, 300);
            });
        },

        showModal: function(modalId) {
            $('#' + modalId).addClass('show');
        },

        hideModal: function(modalId) {
            $('#' + modalId).removeClass('show');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        App.init();
    });

    // Export App globally
    window.App = App;

})(jQuery);
