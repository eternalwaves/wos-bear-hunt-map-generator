import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { TemplateLoader } from '../../utils/templateLoader.js';
import { LoginViewLogic } from './logic/LoginView.js';
import { User } from '../../models/User.js';

export class LoginView extends LitElement {
  static properties = {
    loading: { type: Boolean },
    error: { type: String },
    showOtp: { type: Boolean },
    username: { type: String },
    password: { type: String },
    otpCode: { type: String }
  };

  constructor() {
    super();
    this.loading = false;
    this.error = '';
    this.showOtp = false;
    this.username = '';
    this.password = '';
    this.otpCode = '';
    this.logic = new LoginViewLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`
    /* Default styles - will be overridden by loaded CSS */
    :host {
      display: block;
    }
  `;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
  }

  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/auth/templates/LoginView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/auth/styles/LoginView.css');
    this.staticStyles = css`${unsafeCSS(this.cssString)}`;
  }

  render() {
    return html`
      <div class="login-container">
        <div class="login-header">
          <h1 class="login-title">Login</h1>
          <p class="login-subtitle">Sign in to your account</p>
        </div>

        <form @submit=${this._onSubmit}>
          <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input 
              type="text" 
              id="username"
              class="form-input"
              .value=${this.username}
              @input=${this._onUsernameChange}
              placeholder="Enter your username"
              required
              ?disabled=${this.loading}
            >
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input 
              type="password" 
              id="password"
              class="form-input"
              .value=${this.password}
              @input=${this._onPasswordChange}
              placeholder="Enter your password"
              required
              ?disabled=${this.loading}
            >
          </div>

          ${this.error ? html`
            <div class="error-message">
              ${this.error}
            </div>
          ` : ''}

          <button 
            type="submit" 
            class="btn btn-primary"
            ?disabled=${this.loading}
          >
            ${this.loading ? 'Signing in...' : 'Sign In'}
          </button>
        </form>

        ${this.showOtp ? html`
          <div class="otp-section">
            <div class="otp-title">Two-Factor Authentication</div>
            <div class="otp-help">
              Please enter the OTP code sent to your email address.
            </div>
            
            <form @submit=${this._onOtpSubmit}>
              <div class="form-group">
                <label class="form-label" for="otp">OTP Code</label>
                <input 
                  type="text" 
                  id="otp"
                  class="form-input"
                  .value=${this.otpCode}
                  @input=${this._onOtpChange}
                  placeholder="Enter OTP code"
                  required
                  ?disabled=${this.loading}
                  maxlength="6"
                >
              </div>

              <button 
                type="submit" 
                class="btn btn-primary"
                ?disabled=${this.loading}
              >
                ${this.loading ? 'Verifying...' : 'Verify OTP'}
              </button>
            </form>
          </div>
        ` : ''}

        <div class="login-footer">
          <a href="/reset-password.php" class="footer-link">Forgot Password?</a>
          <br>
          <a href="/register.html" class="footer-link">Don't have an account? Register</a>
        </div>
      </div>
    `;
  }

  _onUsernameChange(event) {
    this.logic.onUsernameChange(event);
  }

  _onPasswordChange(event) {
    this.logic.onPasswordChange(event);
  }

  _onOtpChange(event) {
    this.logic.onOtpChange(event);
  }

  async _onSubmit(event) {
    this.logic.onSubmit(event);
  }

  async _onOtpSubmit(event) {
    this.logic.onOtpSubmit(event);
  }
}

customElements.define('login-view', LoginView); 