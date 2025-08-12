import { html } from 'https://esm.sh/lit@2.7.0';

export function LoginViewTemplate(component) {
  return html`
    <div class="login-container">
      <div class="login-header">
        <h1 class="login-title">Login</h1>
        <p class="login-subtitle">Sign in to your account</p>
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
            placeholder="Enter your username"
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
            placeholder="Enter your password"
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
          ${component.loading ? 'Signing in...' : 'Sign In'}
        </button>
      </form>

      ${component.showOtp ? html`
        <div class="otp-section">
          <h3>Two-Factor Authentication</h3>
          <p>Please enter the verification code sent to your device.</p>
          
          <form @submit=${component._onOtpSubmit}>
            <div class="form-group">
              <label class="form-label" for="otp">Verification Code</label>
              <input 
                type="text" 
                id="otp"
                class="form-input"
                .value=${component.otpCode}
                @input=${component._onOtpChange}
                placeholder="Enter 6-digit code"
                required
                ?disabled=${component.loading}
                maxlength="6"
              >
            </div>

            <button 
              type="submit" 
              class="btn btn-primary"
              ?disabled=${component.loading}
            >
              ${component.loading ? 'Verifying...' : 'Verify'}
            </button>
          </form>
        </div>
      ` : ''}

      <div class="form-links">
        <a href="#" @click=${component._onForgotPassword}>Forgot Password?</a>
        <a href="#" @click=${component._onRegister}>Create Account</a>
      </div>
    </div>
  `;
}
