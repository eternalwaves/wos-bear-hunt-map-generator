// PasswordResetView Logic
export class PasswordResetViewLogic {
  constructor(component) {
    this.component = component;
  }

  async onSubmit() {
    this.component.loading = true;
    this.component.error = '';
    this.component.success = '';

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(this.component.email)) {
      this.component.error = 'Please enter a valid email address.';
      this.component.loading = false;
      return;
    }

    try {
      const response = await fetch('/auth.php?action=reset_password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: this.component.email
        })
      });

      const data = await response.json();

      if (response.ok) {
        this.component.success = 'Password reset instructions have been sent to your email address.';
        this.component.email = '';
      } else {
        this.component.error = data.error || 'Failed to send reset instructions. Please try again.';
      }
    } catch (error) {
      console.error('Password reset error:', error);
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
}
