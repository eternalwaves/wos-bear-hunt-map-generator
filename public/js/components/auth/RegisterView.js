import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { RegisterViewLogic } from './logic/RegisterView.js';
import { RegisterViewTemplate } from './templates/RegisterViewTemplate.js';
import { User } from '../../models/User.js';

export class RegisterView extends LitElement {
  static properties = {
    loading: { type: Boolean },
    error: { type: String },
    username: { type: String },
    email: { type: String },
    password: { type: String },
    confirmPassword: { type: String }
  };

  constructor() {
    super();
    this.loading = false;
    this.error = '';
    this.username = '';
    this.email = '';
    this.password = '';
    this.confirmPassword = '';
    this.logic = new RegisterViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .register-container {
      max-width: 400px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .register-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .register-title {
      font-size: 28px;
      font-weight: 600;
      color: #333;
      margin: 0 0 10px 0;
    }
    
    .register-subtitle {
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
    return RegisterViewTemplate(this);
  }

  _onUsernameChange(event) {
    this.username = event.target.value;
  }

  _onEmailChange(event) {
    this.email = event.target.value;
  }

  _onPasswordChange(event) {
    this.password = event.target.value;
  }

  _onConfirmPasswordChange(event) {
    this.confirmPassword = event.target.value;
  }

  async _onSubmit(event) {
    event.preventDefault();
    await this.logic.onSubmit();
  }

  _onLogin(event) {
    event.preventDefault();
    this.logic.onLogin();
  }
}

customElements.define('register-view', RegisterView);
