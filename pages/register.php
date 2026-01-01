<?php
include __DIR__ . '/../layouts/header.php';

if (isLoggedIn()) {
    redirect('dashboard');
}

// Determine current step
$step = $_GET['step'] ?? 'register';
if ($step === 'profile' && !isset($_SESSION['user_id'])) {
    redirect('register');
}
?>

<section class="hero">
    <h1><?php echo __('join_smart_chashi'); ?></h1>
    <p><?php echo __('create_farming_journey'); ?></p>
</section>

<div class="register-container mt-4 mb-4">
    <!-- Progress Steps -->
    <div class="registration-steps">
        <div class="step <?php echo in_array($step, ['register', 'verify', 'profile']) ? 'active' : ''; ?> <?php echo in_array($step, ['verify', 'profile']) ? 'completed' : ''; ?>">
            <div class="step-number">1</div>
            <div class="step-label">Account Info</div>
        </div>
        <div class="step-line <?php echo in_array($step, ['verify', 'profile']) ? 'active' : ''; ?>"></div>
        <div class="step <?php echo in_array($step, ['verify', 'profile']) ? 'active' : ''; ?> <?php echo $step === 'profile' ? 'completed' : ''; ?>">
            <div class="step-number">2</div>
            <div class="step-label">Verify Email</div>
        </div>
        <div class="step-line <?php echo $step === 'profile' ? 'active' : ''; ?>"></div>
        <div class="step <?php echo $step === 'profile' ? 'active' : ''; ?>">
            <div class="step-number">3</div>
            <div class="step-label">Profile Photo</div>
        </div>
    </div>

    <!-- Step 1: Registration Form -->
    <div class="card step-content" id="step1" style="<?php echo $step !== 'register' ? 'display:none;' : ''; ?>">
        <div class="card-header">
            <h3 class="card-title"><?php echo __('create_account'); ?></h3>
        </div>

        <form id="registerForm" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstName"><?php echo __('first_name'); ?> *</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Enter first name" required>
                </div>

                <div class="form-group">
                    <label for="lastName"><?php echo __('last_name'); ?></label>
                    <input type="text" id="lastName" name="lastName" placeholder="Enter last name">
                </div>
            </div>

            <div class="form-group">
                <label for="email"><?php echo __('email_address'); ?> *</label>
                <input type="email" id="email" name="email" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
                <label for="phone"><?php echo __('phone'); ?> *</label>
                <input type="tel" id="phone" name="phone" placeholder="+880 1234 567890" required>
            </div>

            <div class="form-group">
                <label><?php echo __('account_type'); ?> *</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="role" value="farmer" checked required>
                        <span><?php echo __('farmer'); ?></span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="role" value="officer" required>
                        <span><?php echo __('agriculture_officer'); ?></span>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="regPassword"><?php echo __('password'); ?> *</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="regPassword" name="password" placeholder="Min 8 chars" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordField('regPassword', this)" aria-label="Toggle password visibility">
                            <span class="material-icons eye-icon">visibility</span>
                        </button>
                    </div>
                    <small class="password-hint">Must contain: uppercase, lowercase, number, special character</small>
                </div>

                <div class="form-group">
                    <label for="regPasswordConfirm"><?php echo __('confirm_password'); ?> *</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="regPasswordConfirm" name="passwordConfirm" placeholder="Re-enter password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordField('regPasswordConfirm', this)" aria-label="Toggle password visibility">
                            <span class="material-icons eye-icon">visibility</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="agreeTerms" required>
                    <span><?php echo __('agree_terms'); ?></span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-block" id="registerBtn">
                    <span class="btn-text">Continue</span>
                    <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                </button>
            </div>

            <div class="text-center mt-3">
                <p>Already have an account? <a href="<?php echo $base_url; ?>login">Login here</a></p>
            </div>
        </form>
    </div>

    <!-- Step 2: Email Verification -->
    <div class="card step-content" id="step2" style="<?php echo $step !== 'verify' ? 'display:none;' : ''; ?>">
        <div class="card-header">
            <h3 class="card-title">Verify Your Email</h3>
        </div>

        <div class="verification-content">
            <div class="verification-icon">
                <span class="material-icons">mark_email_read</span>
            </div>
            <p class="verification-text">We've sent a 6-digit verification code to:</p>
            <p class="verification-email" id="maskedEmail"></p>
            
            <form id="verifyForm">
                <div class="form-group">
                    <label>Enter Verification Code</label>
                    <div class="code-input-container">
                        <input type="text" class="code-input" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="code-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="code-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="code-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="code-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="code-input" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]">
                    </div>
                    <input type="hidden" id="verificationCode" name="code">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-block" id="verifyBtn">
                        <span class="btn-text">Verify Email</span>
                        <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                    </button>
                </div>
            </form>

            <div class="resend-section">
                <p>Didn't receive the code?</p>
                <button type="button" class="btn-link" id="resendCodeBtn">Resend Code</button>
                <p class="resend-timer" id="resendTimer" style="display:none;">Resend available in <span id="timerCount">60</span>s</p>
            </div>
        </div>
    </div>

    <!-- Step 3: Profile Photo -->
    <div class="card step-content" id="step3" style="<?php echo $step !== 'profile' ? 'display:none;' : ''; ?>">
        <div class="card-header">
            <h3 class="card-title">Add Profile Photo</h3>
        </div>

        <form id="profileForm" enctype="multipart/form-data">
            <div class="profile-photo-section">
                <div class="photo-preview-container">
                    <div class="photo-preview" id="photoPreview">
                        <span class="material-icons">person</span>
                    </div>
                    <label for="profilePhoto" class="photo-upload-btn">
                        <span class="material-icons">add_a_photo</span>
                    </label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" style="display:none;">
                </div>
                <p class="photo-hint">Add a profile photo <strong>(required)</strong></p>
                <p class="photo-formats">Supported: JPG, PNG, GIF (Max 5MB)</p>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-block" id="completeBtn">
                    <span class="btn-text">Complete Registration</span>
                    <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Benefits Card -->
    <div class="card mt-3" id="benefitsCard" style="<?php echo $step !== 'register' ? 'display:none;' : ''; ?>">
        <div class="card-header">
            <h3 class="card-title">Why Join Smart Chashi?</h3>
        </div>
        <div style="padding: 1.5rem;">
            <ul class="benefits-list">
                <li><span class="material-icons">smartphone</span> Free mobile-friendly platform</li>
                <li><span class="material-icons">smart_toy</span> AI-powered farming advice</li>
                <li><span class="material-icons">agriculture</span> Connect with other farmers</li>
                <li><span class="material-icons">chat</span> Chat with Chashi Bhai (AI assistant)</li>
                <li><span class="material-icons">bug_report</span> Disease detection with AI</li>
                <li><span class="material-icons">cloud</span> Real-time weather & alerts</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Password toggle function
function togglePasswordField(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('.eye-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}

// Store registration data
let registrationData = {};

// Registration form submission
document.getElementById('registerForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('registerBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    // Validate passwords match
    const password = document.getElementById('regPassword').value;
    const passwordConfirm = document.getElementById('regPasswordConfirm').value;
    
    if (password !== passwordConfirm) {
        App.showAlert('Passwords do not match', 'danger');
        return;
    }
    
    // Show loading
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    
    const data = {
        action: 'send_code',
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        role: document.querySelector('input[name="role"]:checked').value,
        password: password
    };
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            registrationData = data;
            document.getElementById('maskedEmail').textContent = result.email;
            
            // Show verification step
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
            document.getElementById('benefitsCard').style.display = 'none';
            
            // Update progress
            document.querySelectorAll('.registration-steps .step')[0].classList.add('completed');
            document.querySelectorAll('.registration-steps .step')[1].classList.add('active');
            document.querySelectorAll('.registration-steps .step-line')[0].classList.add('active');
            
            // Focus first code input
            document.querySelector('.code-input').focus();
            startResendTimer();
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

// Code input handling
const codeInputs = document.querySelectorAll('.code-input');
codeInputs.forEach((input, index) => {
    input.addEventListener('input', function(e) {
        const value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value;
        
        if (value && index < codeInputs.length - 1) {
            codeInputs[index + 1].focus();
        }
        
        // Update hidden input
        let fullCode = '';
        codeInputs.forEach(inp => fullCode += inp.value);
        document.getElementById('verificationCode').value = fullCode;
        
        // Auto-submit when complete
        if (fullCode.length === 6) {
            document.getElementById('verifyForm').dispatchEvent(new Event('submit'));
        }
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            codeInputs[index - 1].focus();
        }
    });
    
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        pastedData.split('').forEach((char, i) => {
            if (codeInputs[i]) {
                codeInputs[i].value = char;
            }
        });
        document.getElementById('verificationCode').value = pastedData;
        if (pastedData.length === 6) {
            document.getElementById('verifyForm').dispatchEvent(new Event('submit'));
        }
    });
});

// Verify form submission
document.getElementById('verifyForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const code = document.getElementById('verificationCode').value;
    if (code.length !== 6) {
        App.showAlert('Please enter a valid 6-digit code', 'danger');
        return;
    }
    
    const btn = document.getElementById('verifyBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify_code', code: code })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            
            // Show profile step
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'block';
            
            // Update progress
            document.querySelectorAll('.registration-steps .step')[1].classList.add('completed');
            document.querySelectorAll('.registration-steps .step')[2].classList.add('active');
            document.querySelectorAll('.registration-steps .step-line')[1].classList.add('active');
        } else {
            App.showAlert(result.message, 'danger');
            // Clear code inputs
            codeInputs.forEach(inp => inp.value = '');
            codeInputs[0].focus();
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

// Resend code
let resendTimer = null;
function startResendTimer() {
    let seconds = 60;
    document.getElementById('resendCodeBtn').style.display = 'none';
    document.getElementById('resendTimer').style.display = 'block';
    document.getElementById('timerCount').textContent = seconds;
    
    resendTimer = setInterval(() => {
        seconds--;
        document.getElementById('timerCount').textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(resendTimer);
            document.getElementById('resendCodeBtn').style.display = 'inline';
            document.getElementById('resendTimer').style.display = 'none';
        }
    }, 1000);
}

document.getElementById('resendCodeBtn')?.addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Sending...';
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend_code' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            startResendTimer();
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Resend Code';
    }
});

// Profile photo preview
document.getElementById('profilePhoto')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            App.showAlert('File size must be less than 5MB', 'danger');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview">';
        };
        reader.readAsDataURL(file);
    }
});

// Profile form submission
document.getElementById('profileForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Check if photo is selected
    const photoInput = document.getElementById('profilePhoto');
    if (!photoInput.files[0]) {
        App.showAlert('Please upload a profile photo', 'danger');
        return;
    }
    
    await completeProfile();
});

async function completeProfile() {
    const btn = document.getElementById('completeBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    
    const formData = new FormData();
    formData.append('action', 'complete_profile');
    
    const photoInput = document.getElementById('profilePhoto');
    if (photoInput.files[0]) {
        formData.append('profile_photo', photoInput.files[0]);
    }
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '<?php echo $base_url; ?>' + result.redirect;
            }, 1500);
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
}



// Check if coming back to profile step
<?php if ($step === 'profile'): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.registration-steps .step')[0].classList.add('completed');
    document.querySelectorAll('.registration-steps .step')[1].classList.add('completed');
    document.querySelectorAll('.registration-steps .step')[2].classList.add('active');
    document.querySelectorAll('.registration-steps .step-line')[0].classList.add('active');
    document.querySelectorAll('.registration-steps .step-line')[1].classList.add('active');
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
