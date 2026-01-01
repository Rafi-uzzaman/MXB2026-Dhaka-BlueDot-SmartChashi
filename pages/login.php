<?php
include __DIR__ . '/../layouts/header.php';

if (isLoggedIn()) {
    redirect('dashboard');
}
?>

<section class="hero">
    <h1><?php echo __('login_to_smart_chashi'); ?></h1>
    <p><?php echo __('access_dashboard'); ?></p>
</section>

<div class="auth-container mt-4 mb-4">
    <div class="auth-form">
        <!-- Login Form -->
        <div class="card" id="loginCard">
            <div class="card-header">
                <h3 class="card-title"><?php echo __('login'); ?></h3>
            </div>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="email"><?php echo __('email_address'); ?> / Phone</label>
                    <input type="text" id="email" name="email" placeholder="your@email.com or phone" required>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo __('password'); ?></label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="" required>
                        <button type="button" class="password-toggle" data-target="password" aria-label="Toggle password visibility">
                            <span class="material-icons eye-icon">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-block" id="loginBtn">
                        <span class="btn-text"><?php echo __('login'); ?></span>
                        <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="#" class="forgot-password-link" id="forgotPasswordLink">Forgot Password?</a>
                </div>

                <div class="text-center mt-3">
                    <p><?php echo __('no_account'); ?> <a href="<?php echo $base_url; ?>register"><?php echo __('register_here'); ?></a></p>
                </div>
            </form>
        </div>

        <!-- Forgot Password Form -->
        <div class="card" id="forgotPasswordCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Reset Password</h3>
            </div>

            <!-- Step 1: Enter Email -->
            <div id="forgotStep1">
                <form id="forgotEmailForm">
                    <p class="form-description">Enter your email or phone number to receive a password reset code.</p>
                    
                    <div class="form-group">
                        <label for="forgotEmail">Email / Phone</label>
                        <input type="text" id="forgotEmail" name="emailOrPhone" placeholder="your@email.com or phone" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block" id="sendResetCodeBtn">
                            <span class="btn-text">Send Reset Code</span>
                            <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="btn-link" id="backToLoginLink">Back to Login</a>
                    </div>
                </form>
            </div>

            <!-- Step 2: Verify Code -->
            <div id="forgotStep2" style="display: none;">
                <div class="verification-content">
                    <div class="verification-icon">
                        <span class="material-icons">lock_reset</span>
                    </div>
                    <p class="verification-text">Enter the 6-digit code sent to:</p>
                    <p class="verification-email" id="forgotMaskedEmail"></p>
                    
                    <form id="forgotVerifyForm">
                        <div class="form-group">
                            <div class="code-input-container">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="0" inputmode="numeric">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="1" inputmode="numeric">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="2" inputmode="numeric">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="3" inputmode="numeric">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="4" inputmode="numeric">
                                <input type="text" class="code-input forgot-code" maxlength="1" data-index="5" inputmode="numeric">
                            </div>
                            <input type="hidden" id="forgotVerificationCode">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-block" id="verifyResetCodeBtn">
                                <span class="btn-text">Verify Code</span>
                                <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                            </button>
                        </div>
                    </form>

                    <div class="resend-section">
                        <p>Didn't receive the code?</p>
                        <button type="button" class="btn-link" id="resendResetCodeBtn">Resend Code</button>
                        <p class="resend-timer" id="resetResendTimer" style="display:none;">Resend in <span id="resetTimerCount">60</span>s</p>
                    </div>
                </div>
            </div>

            <!-- Step 3: New Password -->
            <div id="forgotStep3" style="display: none;">
                <form id="newPasswordForm">
                    <p class="form-description">Create a new password for your account.</p>
                    
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="newPassword" name="password" placeholder="Min 8 characters" required>
                            <button type="button" class="password-toggle" data-target="newPassword" aria-label="Toggle">
                                <span class="material-icons eye-icon">visibility</span>
                            </button>
                        </div>
                        <small class="password-hint">Must contain: uppercase, lowercase, number, special character</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmNewPassword">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmNewPassword" name="confirmPassword" placeholder="Re-enter password" required>
                            <button type="button" class="password-toggle" data-target="confirmNewPassword" aria-label="Toggle">
                                <span class="material-icons eye-icon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block" id="resetPasswordBtn">
                            <span class="btn-text">Reset Password</span>
                            <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Email Verification (for unverified users) -->
        <div class="card" id="verificationCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Verify Your Email</h3>
            </div>

            <div class="verification-content">
                <div class="verification-icon">
                    <span class="material-icons">mark_email_read</span>
                </div>
                <p class="verification-text">Your email is not verified. We've sent a code to:</p>
                <p class="verification-email" id="loginMaskedEmail"></p>
                
                <form id="loginVerifyForm">
                    <div class="form-group">
                        <div class="code-input-container">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="0" inputmode="numeric">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="1" inputmode="numeric">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="2" inputmode="numeric">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="3" inputmode="numeric">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="4" inputmode="numeric">
                            <input type="text" class="code-input login-code" maxlength="1" data-index="5" inputmode="numeric">
                        </div>
                        <input type="hidden" id="loginVerificationCode">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block" id="loginVerifyBtn">
                            <span class="btn-text">Verify Email</span>
                            <span class="btn-loader" style="display:none;"><span class="material-icons spinning">sync</span></span>
                        </button>
                    </div>
                </form>

                <div class="resend-section">
                    <button type="button" class="btn-link" id="loginResendCodeBtn">Resend Code</button>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-features">
        <h3><?php echo __('new_to_smart_chashi'); ?></h3>
        <p><?php echo __('join_thousands'); ?></p>
        
        <div class="mt-4">
            <h4><span class="material-icons" style="vertical-align: middle;">star</span> <?php echo __('key_features'); ?></h4>
            <ul class="features-list">
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">agriculture</span> <?php echo __('crops'); ?></li>
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">bug_report</span> <?php echo __('disease_detection'); ?></li>
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">chat</span> <?php echo __('chat'); ?></li>
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">wb_sunny</span> <?php echo __('weather'); ?></li>
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">shopping_cart</span> <?php echo __('marketplace'); ?></li>
                <li><span class="material-icons" style="vertical-align: middle; font-size: 20px;">people</span> <?php echo __('community'); ?></li>
            </ul>
        </div>

        <div class="mt-4">
            <a href="<?php echo $base_url; ?>register" class="btn btn-secondary btn-block"><?php echo __('create_account'); ?></a>
        </div>
    </div>
</div>

<script>


// Login form
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('loginBtn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-flex';
    
    const data = {
        action: 'login',
        email: document.getElementById('email').value,
        password: document.getElementById('password').value
    };
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        
        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '<?php echo $base_url; ?>' + result.redirect;
            }, 1000);
        } else if (result.needs_verification) {
            App.showAlert(result.message, 'warning');
            document.getElementById('loginMaskedEmail').textContent = result.email;
            document.getElementById('loginCard').style.display = 'none';
            document.getElementById('verificationCard').style.display = 'block';
            
            // Send verification code
            await sendLoginVerificationCode();
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        console.error('Login error:', error);
        App.showAlert(error.message || 'An error occurred. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

// Send verification code for login
async function sendLoginVerificationCode() {
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend_code' })
        });
        const result = await response.json();
        if (result.success) {
            App.showAlert('Verification code sent!', 'success');
        }
    } catch (error) {
        console.error('Error sending code:', error);
    }
}

// Login verification code inputs
setupCodeInputs('.login-code', 'loginVerificationCode', 'loginVerifyForm');

document.getElementById('loginVerifyForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const code = document.getElementById('loginVerificationCode').value;
    if (code.length !== 6) {
        App.showAlert('Please enter a valid 6-digit code', 'danger');
        return;
    }
    
    const btn = document.getElementById('loginVerifyBtn');
    setButtonLoading(btn, true);
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify_code', code: code })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert('Email verified! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = '<?php echo $base_url; ?>dashboard';
            }, 1500);
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        setButtonLoading(btn, false);
    }
});

document.getElementById('loginResendCodeBtn')?.addEventListener('click', async function() {
    this.disabled = true;
    this.textContent = 'Sending...';
    await sendLoginVerificationCode();
    this.disabled = false;
    this.textContent = 'Resend Code';
});

// Forgot Password Flow
document.getElementById('forgotPasswordLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('loginCard').style.display = 'none';
    document.getElementById('forgotPasswordCard').style.display = 'block';
});

document.getElementById('backToLoginLink')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('forgotPasswordCard').style.display = 'none';
    document.getElementById('loginCard').style.display = 'block';
    resetForgotPasswordForm();
});

function resetForgotPasswordForm() {
    document.getElementById('forgotStep1').style.display = 'block';
    document.getElementById('forgotStep2').style.display = 'none';
    document.getElementById('forgotStep3').style.display = 'none';
    document.getElementById('forgotEmailForm').reset();
    // Reset flags
    isVerifyingResetCode = false;
    // Clear code inputs
    document.querySelectorAll('.forgot-code').forEach(inp => inp.value = '');
    document.getElementById('forgotVerificationCode').value = '';
}

// Step 1: Send reset code
document.getElementById('forgotEmailForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('sendResetCodeBtn');
    setButtonLoading(btn, true);
    
    const emailOrPhone = document.getElementById('forgotEmail').value;
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send_code', emailOrPhone: emailOrPhone })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            document.getElementById('forgotMaskedEmail').textContent = result.email;
            document.getElementById('forgotStep1').style.display = 'none';
            document.getElementById('forgotStep2').style.display = 'block';
            document.querySelector('.forgot-code').focus();
            startResetResendTimer();
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        setButtonLoading(btn, false);
    }
});

// Forgot password code inputs - NO auto-submit, handle manually
const forgotCodeInputs = document.querySelectorAll('.forgot-code');
forgotCodeInputs.forEach((input, index) => {
    input.addEventListener('input', function(e) {
        const value = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = value;
        
        if (value && index < forgotCodeInputs.length - 1) {
            forgotCodeInputs[index + 1].focus();
        }
        
        // Update hidden input
        let fullCode = '';
        forgotCodeInputs.forEach(inp => fullCode += inp.value);
        document.getElementById('forgotVerificationCode').value = fullCode;
        
        // Auto-verify when 6 digits (call function directly, not form submit)
        if (fullCode.length === 6 && !isVerifyingResetCode) {
            verifyForgotPasswordCode();
        }
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            forgotCodeInputs[index - 1].focus();
        }
    });
    
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
        pastedData.split('').forEach((char, i) => {
            if (forgotCodeInputs[i]) forgotCodeInputs[i].value = char;
        });
        document.getElementById('forgotVerificationCode').value = pastedData;
        if (pastedData.length === 6 && !isVerifyingResetCode) {
            verifyForgotPasswordCode();
        }
    });
});

// Step 2: Verify reset code
let isVerifyingResetCode = false;

async function verifyForgotPasswordCode() {
    if (isVerifyingResetCode) return;
    
    const code = document.getElementById('forgotVerificationCode').value;
    if (code.length !== 6) {
        App.showAlert('Please enter a valid 6-digit code', 'danger');
        return;
    }
    
    isVerifyingResetCode = true;
    const btn = document.getElementById('verifyResetCodeBtn');
    setButtonLoading(btn, true);
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'verify_code', code: code })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            // Show step 3
            document.getElementById('forgotStep2').style.display = 'none';
            document.getElementById('forgotStep3').style.display = 'block';
        } else {
            App.showAlert(result.message, 'danger');
            document.querySelectorAll('.forgot-code').forEach(inp => inp.value = '');
            document.getElementById('forgotVerificationCode').value = '';
            document.querySelectorAll('.forgot-code')[0]?.focus();
            isVerifyingResetCode = false;
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
        isVerifyingResetCode = false;
    } finally {
        setButtonLoading(btn, false);
    }
}

// Form submit handler (for button click)
document.getElementById('forgotVerifyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    verifyForgotPasswordCode();
});

// Resend reset code
let resetResendTimer = null;
function startResetResendTimer() {
    let seconds = 60;
    document.getElementById('resendResetCodeBtn').style.display = 'none';
    document.getElementById('resetResendTimer').style.display = 'block';
    document.getElementById('resetTimerCount').textContent = seconds;
    
    resetResendTimer = setInterval(() => {
        seconds--;
        document.getElementById('resetTimerCount').textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(resetResendTimer);
            document.getElementById('resendResetCodeBtn').style.display = 'inline';
            document.getElementById('resetResendTimer').style.display = 'none';
        }
    }, 1000);
}

document.getElementById('resendResetCodeBtn')?.addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Sending...';
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'resend_code' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            startResetResendTimer();
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

// Step 3: Reset password
document.getElementById('newPasswordForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const password = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    
    if (password !== confirmPassword) {
        App.showAlert('Passwords do not match', 'danger');
        return;
    }
    
    const btn = document.getElementById('resetPasswordBtn');
    setButtonLoading(btn, true);
    
    try {
        const response = await fetch('<?php echo $base_url; ?>ajax/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'reset_password', 
                password: password,
                confirmPassword: confirmPassword
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            App.showAlert(result.message, 'success');
            setTimeout(() => {
                window.location.href = '<?php echo $base_url; ?>login';
            }, 2000);
        } else {
            App.showAlert(result.message, 'danger');
        }
    } catch (error) {
        App.showAlert('An error occurred. Please try again.', 'danger');
    } finally {
        setButtonLoading(btn, false);
    }
});

// Helper functions
let forgotCodeVerified = false; // Flag to prevent re-submission after success

function setupCodeInputs(selector, hiddenInputId, formId) {
    const codeInputs = document.querySelectorAll(selector);
    codeInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;
            
            if (value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }
            
            let fullCode = '';
            codeInputs.forEach(inp => fullCode += inp.value);
            document.getElementById(hiddenInputId).value = fullCode;
            
            // Only auto-submit if not already verified (for forgot password)
            if (fullCode.length === 6 && !forgotCodeVerified) {
                document.getElementById(formId).dispatchEvent(new Event('submit'));
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
                if (codeInputs[i]) codeInputs[i].value = char;
            });
            document.getElementById(hiddenInputId).value = pastedData;
            if (pastedData.length === 6 && !forgotCodeVerified) {
                document.getElementById(formId).dispatchEvent(new Event('submit'));
            }
        });
    });
}

function setButtonLoading(btn, loading) {
    const btnText = btn.querySelector('.btn-text');
    const btnLoader = btn.querySelector('.btn-loader');
    
    btn.disabled = loading;
    btnText.style.display = loading ? 'none' : 'inline';
    btnLoader.style.display = loading ? 'inline-flex' : 'none';
}

// Password toggle functionality
document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('.eye-icon');
        
        if (input && input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else if (input) {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
