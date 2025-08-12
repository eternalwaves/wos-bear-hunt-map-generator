import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { TemplateLoader } from '../../utils/templateLoader.js';
import { PasswordResetViewLogic } from './logic/PasswordResetView.js';

export class PasswordResetView extends LitElement {
  static properties = {
    email: { type: String },
    newPassword: { type: String },
    confirmPassword: { type: String },
    resetToken: { type: String },
    step: { type: String }, // 'request' or 'reset'
    errorMessage: { type: String },
    successMessage: { type: String }
  };

  constructor() {
    super();
    this.email = '';
    this.newPassword = '';
    this.confirmPassword = '';
    this.resetToken = '';
    this.step = 'request'; // Default to request step
    this.errorMessage = '';
    this.successMessage = '';
    this.logic = new PasswordResetViewLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`/* Default styles - will be overridden by loaded CSS */ :host { display: block; }`;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
    this.logic.initializeFromURL();
  }

  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/auth/templates/PasswordResetView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/auth/styles/PasswordResetView.css');
    this.staticStyles = css`${this.cssString}`;
  }

  render() {
    if (this.step === 'reset') {
      return this._renderResetForm();
    }
    return this._renderRequestForm();
  }

  _renderRequestForm() {
    return html`
      <div class="auth-container">
        <div class="auth-card">
          <h2>Reset Password</h2>
          
          ${this.errorMessage ? html`
            <div class="error-message">${this.errorMessage}</div>
          ` : ''}
          
          ${this.successMessage ? html`
            <div class="success-message">${this.successMessage}</div>
          ` : ''}
          
          <form @submit=${this._onRequestSubmit}>
            <div class="form-group">
              <label for="email">Email Address</label>
              <input 
                type="email" 
                id="email"
                .value=${this.email}
                @input=${this._onEmailChange}
                required
              >
            </div>
            
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
          </form>
          
          <div class="auth-links">
            <a href="login.html">Back to Login</a>
          </div>
        </div>
      </div>
    `;
  }

  _renderResetForm() {
    return html`
      <div class="auth-container">
        <div class="auth-card">
          <h2>Set New Password</h2>
          
          ${this.errorMessage ? html`
            <div class="error-message">${this.errorMessage}</div>
          ` : ''}
          
          ${this.successMessage ? html`
            <div class="success-message">${this.successMessage}</div>
          ` : ''}
          
          <form @submit=${this._onResetSubmit}>
            <div class="form-group">
              <label for="new-password">New Password</label>
              <input 
                type="password" 
                id="new-password"
                .value=${this.newPassword}
                @input=${this._onNewPasswordChange}
                required
                minlength="8"
              >
            </div>
            
            <div class="form-group">
              <label for="confirm-password">Confirm New Password</label>
              <input 
                type="password" 
                id="confirm-password"
                .value=${this.confirmPassword}
                @input=${this._onConfirmPasswordChange}
                required
                minlength="8"
              >
            </div>
            
            <button type="submit" class="btn btn-primary">Reset Password</button>
          </form>
          
          <div class="auth-links">
            <a href="login.html">Back to Login</a>
          </div>
        </div>
      </div>
    `;
  }

  _onEmailChange(event) {
    this.logic.onEmailChange(event);
  }

  _onNewPasswordChange(event) {
    this.logic.onNewPasswordChange(event);
  }

  _onConfirmPasswordChange(event) {
    this.logic.onConfirmPasswordChange(event);
  }

  async _onRequestSubmit(event) {
    await this.logic.onRequestSubmit(event);
  }

  async _onResetSubmit(event) {
    await this.logic.onResetSubmit(event);
  }
}

customElements.define('password-reset-view', PasswordResetView);
