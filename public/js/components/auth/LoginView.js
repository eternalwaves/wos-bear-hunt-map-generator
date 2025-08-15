import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { LoginViewLogic } from './logic/LoginView.js';
import { LoginViewTemplate } from './templates/LoginViewTemplate.js';
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
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .login-container {
      max-width: 400px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-title {
      font-size: 28px;
      font-weight: 600;
      color: #333;
      margin: 0 0 10px 0;
    }
    
    .login-subtitle {
      color: #666;
      margin: 0;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
      font-size: 14px;
    }
    
    .form-input {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e1e5e9;
      border-radius: 6px;
      font-size: 16px;
      transition: border-color 0.3s ease;
      background: #f8f9fa;
    }
    
    .form-input:focus {
      outline: none;
      border-color: #667eea;
      background: white;
    }
    
    .form-input:disabled {
      background: #f1f3f4;
      cursor: not-allowed;
    }
    
    .btn {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      margin-bottom: 20px;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-primary:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .error-message {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 14px;
      text-align: center;
    }
    
    .otp-section {
      margin-top: 20px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 6px;
      border: 1px solid #e1e5e9;
    }
    
    .otp-section h3 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 18px;
    }
    
    .otp-section p {
      margin: 0 0 20px 0;
      color: #666;
      font-size: 14px;
    }
    
    .form-links {
      text-align: center;
    }
    
    .form-links a {
      color: #667eea;
      text-decoration: none;
      font-size: 14px;
      margin: 0 10px;
      transition: color 0.3s ease;
    }
    
    .form-links a:hover {
      color: #764ba2;
      text-decoration: underline;
    }
  `;

  render() {
    return LoginViewTemplate(this);
  }

  _onUsernameChange(event) {
    this.username = event.target.value;
  }

  _onPasswordChange(event) {
    this.password = event.target.value;
  }

  _onOtpChange(event) {
    this.otpCode = event.target.value;
  }

  async _onSubmit(event) {
    event.preventDefault();
    await this.logic.onSubmit();
  }

  async _onOtpSubmit(event) {
    event.preventDefault();
    await this.logic.onOtpSubmit();
  }

  _onForgotPassword(event) {
    event.preventDefault();
    this.logic.onForgotPassword();
  }

  _onRegister(event) {
    event.preventDefault();
    this.logic.onRegister();
  }
}

customElements.define('login-view', LoginView); 