import { html } from 'https://esm.sh/lit@2.7.0';

export function PasswordResetViewTemplate(component) {
  return html`
    <div class="password-reset-container">
      <div class="password-reset-header">
        <h1 class="password-reset-title">Reset Password</h1>
        <p class="password-reset-subtitle">Enter your email to receive reset instructions</p>
      </div>

      <form @submit=${component._onSubmit}>
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input 
            type="email" 
            id="email"
            class="form-input"
            .value=${component.email}
            @input=${component._onEmailChange}
            placeholder="Enter your email address"
            required
            ?disabled=${component.loading}
          >
        </div>

        ${component.error ? html`
          <div class="error-message">
            ${component.error}
          </div>
        ` : ''}

        ${component.success ? html`
          <div class="success-message">
            ${component.success}
          </div>
        ` : ''}

        <button 
          type="submit" 
          class="btn btn-primary"
          ?disabled=${component.loading}
        >
          ${component.loading ? 'Sending...' : 'Send Reset Link'}
        </button>
      </form>

      <div class="form-links">
        <a href="#" @click=${component._onLogin}>Back to Login</a>
      </div>
    </div>
  `;
}
