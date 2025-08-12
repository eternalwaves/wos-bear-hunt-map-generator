import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { TemplateLoader } from '../../utils/templateLoader.js';
import { RegisterViewLogic } from './logic/RegisterView.js';

export class RegisterView extends LitElement {
  static properties = {
    username: { type: String },
    email: { type: String },
    password: { type: String },
    confirmPassword: { type: String },
    errorMessage: { type: String },
    successMessage: { type: String }
  };

  constructor() {
    super();
    this.username = '';
    this.email = '';
    this.password = '';
    this.confirmPassword = '';
    this.errorMessage = '';
    this.successMessage = '';
    this.logic = new RegisterViewLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`/* Default styles - will be overridden by loaded CSS */ :host { display: block; }`;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
  }

  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/auth/templates/RegisterView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/auth/styles/RegisterView.css');
    this.staticStyles = css`${this.cssString}`;
  }

  render() {
    return html`
      <div class="auth-container">
        <div class="auth-card">
          <h2>Create Account</h2>
          
          ${this.errorMessage ? html`
            <div class="error-message">${this.errorMessage}</div>
          ` : ''}
          
          ${this.successMessage ? html`
            <div class="success-message">${this.successMessage}</div>
          ` : ''}
          
          <form @submit=${this._onSubmit}>
            <div class="form-group">
              <label for="username">Username</label>
              <input 
                type="text" 
                id="username"
                .value=${this.username}
                @input=${this._onUsernameChange}
                required
                minlength="3"
                maxlength="50"
              >
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input 
                type="email" 
                id="email"
                .value=${this.email}
                @input=${this._onEmailChange}
                required
              >
            </div>
            
            <div class="form-group">
              <label for="password">Password</label>
              <input 
                type="password" 
                id="password"
                .value=${this.password}
                @input=${this._onPasswordChange}
                required
                minlength="8"
              >
            </div>
            
            <div class="form-group">
              <label for="confirm-password">Confirm Password</label>
              <input 
                type="password" 
                id="confirm-password"
                .value=${this.confirmPassword}
                @input=${this._onConfirmPasswordChange}
                required
                minlength="8"
              >
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
          </form>
          
          <div class="auth-links">
            <a href="login.html">Already have an account? Sign in</a>
          </div>
        </div>
      </div>
    `;
  }

  _onUsernameChange(event) {
    this.logic.onUsernameChange(event);
  }

  _onEmailChange(event) {
    this.logic.onEmailChange(event);
  }

  _onPasswordChange(event) {
    this.logic.onPasswordChange(event);
  }

  _onConfirmPasswordChange(event) {
    this.logic.onConfirmPasswordChange(event);
  }

  async _onSubmit(event) {
    await this.logic.onSubmit(event);
  }
}

customElements.define('register-view', RegisterView);
