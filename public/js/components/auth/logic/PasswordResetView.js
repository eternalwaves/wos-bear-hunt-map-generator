// PasswordResetView Logic
export class PasswordResetViewLogic {
  constructor(component) {
    this.component = component;
  }

  initializeFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    if (token) {
      this.component.resetToken = token;
      this.component.step = 'reset';
    }
  }

  onEmailChange(event) {
    this.component.email = event.target.value;
    this.component.errorMessage = '';
  }

  onNewPasswordChange(event) {
    this.component.newPassword = event.target.value;
    this.component.errorMessage = '';
  }

  onConfirmPasswordChange(event) {
    this.component.confirmPassword = event.target.value;
    this.component.errorMessage = '';
  }

  async onRequestSubmit(event) {
    event.preventDefault();
    
    // Clear previous messages
    this.component.errorMessage = '';
    this.component.successMessage = '';
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.component.email)) {
      this.component.errorMessage = 'Please enter a valid email address';
      return;
    }
    
    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'request_password_reset',
          email: this.component.email
        })
      });

      const result = await response.json();
      
      if (result.success) {
        this.component.successMessage = 'Password reset link has been sent to your email address.';
        this.component.email = '';
      } else {
        this.component.errorMessage = result.message || 'Failed to send reset link';
      }
    } catch (error) {
      console.error('Password reset request error:', error);
      this.component.errorMessage = 'An error occurred. Please try again.';
    }
  }

  async onResetSubmit(event) {
    event.preventDefault();
    
    // Clear previous messages
    this.component.errorMessage = '';
    this.component.successMessage = '';
    
    // Validate passwords match
    if (this.component.newPassword !== this.component.confirmPassword) {
      this.component.errorMessage = 'Passwords do not match';
      return;
    }
    
    // Validate password strength
    if (this.component.newPassword.length < 8) {
      this.component.errorMessage = 'Password must be at least 8 characters long';
      return;
    }
    
    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'reset_password',
          token: this.component.resetToken,
          new_password: this.component.newPassword
        })
      });

      const result = await response.json();
      
      if (result.success) {
        this.component.successMessage = 'Password has been reset successfully! Redirecting to login...';
        
        // Clear form
        this.component.newPassword = '';
        this.component.confirmPassword = '';
        
        // Redirect to login after a delay
        setTimeout(() => {
          window.location.href = 'login.html';
        }, 3000);
      } else {
        this.component.errorMessage = result.message || 'Failed to reset password';
      }
    } catch (error) {
      console.error('Password reset error:', error);
      this.component.errorMessage = 'An error occurred. Please try again.';
    }
  }
}
