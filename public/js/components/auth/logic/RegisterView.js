// RegisterView Logic
export class RegisterViewLogic {
  constructor(component) {
    this.component = component;
  }

  async onSubmit() {
    this.component.loading = true;
    this.component.error = '';

    // Validate password match
    if (this.component.password !== this.component.confirmPassword) {
      this.component.error = 'Passwords do not match.';
      this.component.loading = false;
      return;
    }

    // Validate password strength
    const passwordError = this._validatePassword(this.component.password);
    if (passwordError) {
      this.component.error = passwordError;
      this.component.loading = false;
      return;
    }

    try {
      const response = await fetch('/auth.php?action=register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: this.component.username,
          email: this.component.email,
          password: this.component.password
        })
      });

      const data = await response.json();

      if (response.ok) {
        // Registration successful
        this.component.dispatchEvent(new CustomEvent('register-success', {
          detail: { user: data.user }
        }));
      } else {
        this.component.error = data.error || 'Registration failed. Please try again.';
      }
    } catch (error) {
      console.error('Registration error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }

  onLogin() {
    this.component.dispatchEvent(new CustomEvent('navigate', {
      detail: { route: 'login' }
    }));
  }

  _validatePassword(password) {
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
}
