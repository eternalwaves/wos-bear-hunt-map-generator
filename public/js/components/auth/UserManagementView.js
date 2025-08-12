import { LitElement, html, css, unsafeCSS } from 'https://esm.sh/lit@2.7.0';
import { TemplateLoader } from '../../utils/templateLoader.js';
import { UserManagementViewLogic } from './logic/UserManagementView.js';

export class UserManagementView extends LitElement {
  static properties = {
    users: { type: Array },
    loading: { type: Boolean },
    errorMessage: { type: String },
    successMessage: { type: String }
  };

  constructor() {
    super();
    this.users = [];
    this.loading = false;
    this.errorMessage = '';
    this.successMessage = '';
    this.logic = new UserManagementViewLogic(this);
    this.templateString = '';
    this.cssString = '';
  }

  static styles = css`/* Default styles - will be overridden by loaded CSS */ :host { display: block; }`;

  async connectedCallback() {
    super.connectedCallback();
    await this._loadTemplates();
    await this.logic.loadUsers();
  }

  async _loadTemplates() {
    this.templateString = await TemplateLoader.loadTemplate('/js/components/auth/templates/UserManagementView.html');
    this.cssString = await TemplateLoader.loadCSS('/js/components/auth/styles/UserManagementView.css');
    this.staticStyles = css`${this.cssString}`;
  }

  render() {
    return html`
      <div class="user-management-container">
        <div class="header">
          <h1>User Management</h1>
          <p>Manage user accounts and permissions</p>
        </div>
        
        ${this.errorMessage ? html`
          <div class="error-message">${this.errorMessage}</div>
        ` : ''}
        
        ${this.successMessage ? html`
          <div class="success-message">${this.successMessage}</div>
        ` : ''}
        
        ${this.loading ? html`
          <div class="loading">Loading users...</div>
        ` : html`
          <div class="users-table">
            <table>
              <thead>
                <tr>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Role</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${this.users.length === 0 ? html`
                  <tr>
                    <td colspan="6" class="empty-message">No users found</td>
                  </tr>
                ` : this.users.map(user => html`
                  <tr>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>
                      <span class="status ${user.email_verified ? 'verified' : 'unverified'}">
                        ${user.email_verified ? 'Verified' : 'Unverified'}
                      </span>
                    </td>
                    <td>
                      <span class="role ${user.is_master ? 'master' : 'user'}">
                        ${user.is_master ? 'Master' : 'User'}
                      </span>
                    </td>
                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    <td class="actions">
                      <button 
                        class="btn btn-small ${user.email_verified ? 'btn-secondary' : 'btn-primary'}"
                        @click=${() => this._toggleEmailVerification(user)}
                        title="${user.email_verified ? 'Unverify Email' : 'Verify Email'}"
                      >
                        ${user.email_verified ? 'Unverify' : 'Verify'}
                      </button>
                      <button 
                        class="btn btn-small ${user.is_master ? 'btn-secondary' : 'btn-warning'}"
                        @click=${() => this._toggleMasterRole(user)}
                        title="${user.is_master ? 'Remove Master Role' : 'Grant Master Role'}"
                      >
                        ${user.is_master ? 'Remove Master' : 'Make Master'}
                      </button>
                      <button 
                        class="btn btn-small btn-danger"
                        @click=${() => this._deleteUser(user)}
                        title="Delete User"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                `)}
              </tbody>
            </table>
          </div>
        `}
      </div>
    `;
  }

  _toggleEmailVerification(user) {
    this.logic.toggleEmailVerification(user);
  }

  _toggleMasterRole(user) {
    this.logic.toggleMasterRole(user);
  }

  _deleteUser(user) {
    this.logic.deleteUser(user);
  }
}

customElements.define('user-management-view', UserManagementView);
