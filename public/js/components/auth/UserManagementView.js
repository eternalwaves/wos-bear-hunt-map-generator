import { LitElement, html, css } from 'https://esm.sh/lit@2.7.0';
import { UserManagementViewLogic } from './logic/UserManagementView.js';
import { UserManagementViewTemplate } from './templates/UserManagementViewTemplate.js';

export class UserManagementView extends LitElement {
  static properties = {
    users: { type: Array },
    loading: { type: Boolean },
    error: { type: String },
    success: { type: String },
    showAddForm: { type: Boolean },
    newUser: { type: Object }
  };

  constructor() {
    super();
    this.users = [];
    this.loading = false;
    this.error = '';
    this.success = '';
    this.showAddForm = false;
    this.newUser = { username: '', email: '', role: '' };
    this.logic = new UserManagementViewLogic(this);
  }

  static styles = css`
    :host {
      display: block;
    }
    
    .user-management-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .user-management-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .user-management-title {
      font-size: 28px;
      font-weight: 600;
      color: #333;
      margin: 0 0 10px 0;
    }
    
    .user-management-subtitle {
      color: #666;
      margin: 0;
    }
    
    .user-controls {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      justify-content: center;
    }
    
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-danger {
      background: #dc3545;
      color: white;
    }
    
    .btn-small {
      padding: 6px 12px;
      font-size: 14px;
    }
    
    .btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
    
    .success-message {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 14px;
      text-align: center;
    }
    
    .users-table {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    
    th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
    }
    
    tr:hover {
      background-color: #f8f9fa;
    }
    
    .no-users {
      text-align: center;
      color: #6c757d;
      font-style: italic;
      padding: 40px;
    }
    
    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
      text-transform: uppercase;
    }
    
    .status-badge.active {
      background: #d4edda;
      color: #155724;
    }
    
    .status-badge.inactive {
      background: #f8d7da;
      color: #721c24;
    }
    
    .status-badge.pending {
      background: #fff3cd;
      color: #856404;
    }
    
    .actions {
      display: flex;
      gap: 8px;
    }
    
    .add-user-form {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-top: 20px;
    }
    
    .add-user-form h3 {
      margin: 0 0 20px 0;
      color: #333;
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
    
    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }
  `;

  render() {
    return UserManagementViewTemplate(this);
  }

  _onAddUser(event) {
    event.preventDefault();
    this.logic.onAddUser();
  }

  _onRefresh(event) {
    event.preventDefault();
    this.logic.onRefresh();
  }

  _onEditUser(user) {
    this.logic.onEditUser(user);
  }

  _onDeleteUser(user) {
    this.logic.onDeleteUser(user);
  }

  _onNewUsernameChange(event) {
    this.newUser.username = event.target.value;
  }

  _onNewEmailChange(event) {
    this.newUser.email = event.target.value;
  }

  _onNewRoleChange(event) {
    this.newUser.role = event.target.value;
  }

  async _onAddUserSubmit(event) {
    event.preventDefault();
    await this.logic.onAddUserSubmit();
  }

  _onCancelAdd(event) {
    event.preventDefault();
    this.logic.onCancelAdd();
  }
}

customElements.define('user-management-view', UserManagementView);
