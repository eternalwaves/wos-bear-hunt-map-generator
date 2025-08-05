// Global variables
let currentUserId = null;
let sessionToken = null;

// Initialize secure session manager
let sessionManager = null;

// Initialize session manager when DOM is loaded
document.addEventListener('DOMContentLoaded', async function() {
    // Load the session manager script
    const script = document.createElement('script');
    script.src = 'js/session-manager.js';
    script.onload = async function() {
        sessionManager = window.sessionManager;
        await sessionManager.initialize();
        
        // Check if user is already logged in
        if (sessionManager.isUserAuthenticated()) {
            // User is already logged in, redirect to main application
            window.location.href = 'index.html';
        }
    };
    document.head.appendChild(script);
});

// Form switching
function showForm(formId) {
    // Hide all forms
    document.querySelectorAll('.auth-form').forEach(form => {
        form.classList.remove('active');
    });
    
    // Show the selected form
    document.getElementById(formId).classList.add('active');
    
    // Clear any messages
    clearMessages();
}

// Message handling
function showMessage(message, type = 'info') {
    clearMessages();
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    const activeForm = document.querySelector('.auth-form.active');
    activeForm.insertBefore(messageDiv, activeForm.firstChild);
}

function clearMessages() {
    document.querySelectorAll('.message').forEach(msg => msg.remove());
}

// Form validation
function validatePassword(password) {
    if (password.length < 8) {
        return 'Password must be at least 8 characters long';
    }
    
    if (!/[A-Z]/.test(password)) {
        return 'Password must contain at least one uppercase letter';
    }
    
    if (!/[a-z]/.test(password)) {
        return 'Password must contain at least one lowercase letter';
    }
    
    if (!/[0-9]/.test(password)) {
        return 'Password must contain at least one number';
    }
    
    if (!/[^A-Za-z0-9]/.test(password)) {
        return 'Password must contain at least one special character';
    }
    
    // Check for common weak passwords
    const commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein'];
    if (commonPasswords.includes(password.toLowerCase())) {
        return 'Password is too common. Please choose a stronger password';
    }
    
    return null;
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return 'Please enter a valid email address';
    }
    return null;
}

// API calls
async function makeApiCall(action, data = {}) {
    try {
        const formData = new FormData();
        formData.append('action', action);
        
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }
        
        const response = await fetch('auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'An error occurred');
        }
        
        return result;
    } catch (error) {
        throw new Error(error.message || 'Network error');
    }
}

// Login form handling
document.getElementById('loginFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showMessage('Please fill in all fields', 'error');
        return;
    }
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span>Logging in...';
    
    try {
        if (!sessionManager) {
            throw new Error('Session manager not initialized');
        }

        const result = await sessionManager.login(username, password);
        
        if (result.needsOtp) {
            currentUserId = result.userId;
            showForm('otpForm');
            showMessage('Please check your email for the OTP code', 'info');
        } else {
            // Login successful, redirect to main application
            window.location.href = 'index.html';
        }
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// OTP form handling
document.getElementById('otpFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const otpCode = document.getElementById('otpCode').value.trim();
    
    if (!otpCode) {
        showMessage('Please enter the OTP code', 'error');
        return;
    }
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span>Verifying...';
    
    try {
        if (!sessionManager) {
            throw new Error('Session manager not initialized');
        }

        await sessionManager.verifyOtp(currentUserId, otpCode);
        
        // Redirect to main application
        window.location.href = 'index.html';
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Register form handling
document.getElementById('registerFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const username = document.getElementById('registerUsername').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validation
    if (!username || !email || !password || !confirmPassword) {
        showMessage('Please fill in all fields', 'error');
        return;
    }
    
    const emailError = validateEmail(email);
    if (emailError) {
        showMessage(emailError, 'error');
        return;
    }
    
    const passwordError = validatePassword(password);
    if (passwordError) {
        showMessage(passwordError, 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span>Registering...';
    
    try {
        if (!sessionManager) {
            throw new Error('Session manager not initialized');
        }

        const result = await sessionManager.register(username, email, password);
        
        showMessage(result.message, 'success');
        
        // Clear form
        this.reset();
        
        // Switch back to login form after a delay
        setTimeout(() => {
            showForm('loginForm');
        }, 3000);
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Reset password form handling
document.getElementById('resetPasswordFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('resetEmail').value.trim();
    
    if (!email) {
        showMessage('Please enter your email address', 'error');
        return;
    }
    
    const emailError = validateEmail(email);
    if (emailError) {
        showMessage(emailError, 'error');
        return;
    }
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span>Sending...';
    
    try {
        if (!sessionManager) {
            throw new Error('Session manager not initialized');
        }

        const result = await sessionManager.requestPasswordReset(email);
        
        showMessage(result.message, 'success');
        
        // Clear form
        this.reset();
        
        // Switch back to login form after a delay
        setTimeout(() => {
            showForm('loginForm');
        }, 3000);
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// New password form handling
document.getElementById('newPasswordFormElement').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmNewPassword = document.getElementById('confirmNewPassword').value;
    
    // Get token from URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (!token) {
        showMessage('Invalid reset link', 'error');
        return;
    }
    
    if (!newPassword || !confirmNewPassword) {
        showMessage('Please fill in all fields', 'error');
        return;
    }
    
    const passwordError = validatePassword(newPassword);
    if (passwordError) {
        showMessage(passwordError, 'error');
        return;
    }
    
    if (newPassword !== confirmNewPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    const button = this.querySelector('button');
    const originalText = button.textContent;
    button.disabled = true;
    button.innerHTML = '<span class="loading"></span>Resetting...';
    
    try {
        if (!sessionManager) {
            throw new Error('Session manager not initialized');
        }

        const result = await sessionManager.resetPassword(token, newPassword);
        
        showMessage(result.message, 'success');
        
        // Clear form
        this.reset();
        
        // Switch to login form after a delay
        setTimeout(() => {
            showForm('loginForm');
        }, 3000);
    } catch (error) {
        showMessage(error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Check if we're on a password reset page
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (token) {
        showForm('newPasswordForm');
    }
});

 