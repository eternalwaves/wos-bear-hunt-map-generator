import { html } from 'https://esm.sh/lit@2.7.0';

export function RegisterViewTemplate(component) {
  return html`
    <div class="register-container">
      <div class="register-header">
        <h1 class="register-title">Create Account</h1>
        <p class="register-subtitle">Join us today</p>
      </div>

      <form @submit=${component._onSubmit}>
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input 
            type="text" 
            id="username"
            class="form-input"
            .value=${component.username}
            @input=${component._onUsernameChange}
            placeholder="Choose a username"
            required
            ?disabled=${component.loading}
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input 
            type="email" 
            id="email"
            class="form-input"
            .value=${component.email}
            @input=${component._onEmailChange}
            placeholder="Enter your email"
            required
            ?disabled=${component.loading}
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input 
            type="password" 
            id="password"
            class="form-input"
            .value=${component.password}
            @input=${component._onPasswordChange}
            placeholder="Create a strong password"
            required
            ?disabled=${component.loading}
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="confirmPassword">Confirm Password</label>
          <input 
            type="password" 
            id="confirmPassword"
            class="form-input"
            .value=${component.confirmPassword}
            @input=${component._onConfirmPasswordChange}
            placeholder="Confirm your password"
            required
            ?disabled=${component.loading}
          >
        </div>

        ${component.error ? html`
          <div class="error-message">
            ${component.error}
          </div>
        ` : ''}

        <button 
          type="submit" 
          class="btn btn-primary"
          ?disabled=${component.loading}
        >
          ${component.loading ? 'Creating Account...' : 'Create Account'}
        </button>
      </form>

      <div class="form-links">
        <a href="#" @click=${component._onLogin}>Already have an account? Sign in</a>
      </div>
    </div>
  `;
}
