// RegisterView Logic
export class RegisterViewLogic {
  constructor(component) {
    this.component = component;
  }

  onUsernameChange(event) {
    this.component.username = event.target.value;
    this.component.errorMessage = '';
  }

  onEmailChange(event) {
    this.component.email = event.target.value;
    this.component.errorMessage = '';
  }

  onPasswordChange(event) {
    this.component.password = event.target.value;
    this.component.errorMessage = '';
  }

  onConfirmPasswordChange(event) {
    this.component.confirmPassword = event.target.value;
    this.component.errorMessage = '';
  }

  async onSubmit(event) {
    event.preventDefault();
    
    // Clear previous messages
    this.component.errorMessage = '';
    this.component.successMessage = '';
    
    // Validate passwords match
    if (this.component.password !== this.component.confirmPassword) {
      this.component.errorMessage = 'Passwords do not match';
      return;
    }
    
    // Validate password strength
    if (this.component.password.length < 8) {
      this.component.errorMessage = 'Password must be at least 8 characters long';
      return;
    }
    
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
          action: 'register',
          username: this.component.username,
          email: this.component.email,
          password: this.component.password
        })
      });

      const result = await response.json();
      
      if (result.success) {
        this.component.successMessage = 'Account created successfully! Please check your email to verify your account.';
        
        // Clear form
        this.component.username = '';
        this.component.email = '';
        this.component.password = '';
        this.component.confirmPassword = '';
        
        // Redirect to login after a delay
        setTimeout(() => {
          window.location.href = 'login.html';
        }, 3000);
      } else {
        this.component.errorMessage = result.message || 'Registration failed';
      }
    } catch (error) {
      console.error('Registration error:', error);
      this.component.errorMessage = 'An error occurred during registration. Please try again.';
    }
  }
}
