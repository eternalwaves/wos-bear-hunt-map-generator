export class LoginViewLogic {
  constructor(component) {
    this.component = component;
  }

  async onSubmit() {
    this.component.loading = true;
    this.component.error = '';

    try {
      const response = await fetch('/auth.php?action=login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: this.component.username,
          password: this.component.password
        })
      });

      const data = await response.json();

      if (response.ok) {
        if (data.requires_otp) {
          this.component.showOtp = true;
          this.component.loading = false;
        } else {
          // Login successful
          this.component.dispatchEvent(new CustomEvent('login-success', {
            detail: { user: data.user }
          }));
        }
      } else {
        this.component.error = data.error || 'Login failed. Please try again.';
      }
    } catch (error) {
      console.error('Login error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }

  async onOtpSubmit() {
    this.component.loading = true;
    this.component.error = '';

    try {
      const response = await fetch('/auth.php?action=verify_otp', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          username: this.component.username,
          otp_code: this.component.otpCode
        })
      });

      const data = await response.json();

      if (response.ok) {
        // OTP verification successful
        this.component.dispatchEvent(new CustomEvent('login-success', {
          detail: { user: data.user }
        }));
      } else {
        this.component.error = data.error || 'Invalid OTP code. Please try again.';
      }
    } catch (error) {
      console.error('OTP verification error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }

  onForgotPassword() {
    this.component.dispatchEvent(new CustomEvent('navigate', {
      detail: { route: 'password-reset' }
    }));
  }

  onRegister() {
    this.component.dispatchEvent(new CustomEvent('navigate', {
      detail: { route: 'register' }
    }));
  }
}
