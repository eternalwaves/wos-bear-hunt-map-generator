import { html } from 'https://esm.sh/lit@2.7.0';

export function UserManagementViewTemplate(component) {
  return html`
    <div class="user-management-container">
      <div class="user-management-header">
        <h1 class="user-management-title">User Management</h1>
        <p class="user-management-subtitle">Manage user accounts and permissions</p>
      </div>

      <div class="user-controls">
        <button class="btn btn-secondary" @click=${component._onAddUser}>
          Add New User
        </button>
        <button class="btn btn-secondary" @click=${component._onRefresh}>
          Refresh List
        </button>
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

      <div class="users-table">
        <table>
          <thead>
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${component.users.length === 0 ? html`
              <tr>
                <td colspan="5" class="no-users">
                  No users found
                </td>
              </tr>
            ` : component.users.map(user => html`
              <tr>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>
                  <span class="status-badge ${user.status}">
                    ${user.status}
                  </span>
                </td>
                <td class="actions">
                  <button class="btn btn-small" @click=${() => component._onEditUser(user)}>
                    Edit
                  </button>
                  <button class="btn btn-small btn-danger" @click=${() => component._onDeleteUser(user)}>
                    Delete
                  </button>
                </td>
              </tr>
            `)}
          </tbody>
        </table>
      </div>

      ${component.showAddForm ? html`
        <div class="add-user-form">
          <h3>Add New User</h3>
          <form @submit=${component._onAddUserSubmit}>
            <div class="form-group">
              <label class="form-label" for="newUsername">Username</label>
              <input 
                type="text" 
                id="newUsername"
                class="form-input"
                .value=${component.newUser.username}
                @input=${component._onNewUsernameChange}
                placeholder="Enter username"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="newEmail">Email</label>
              <input 
                type="email" 
                id="newEmail"
                class="form-input"
                .value=${component.newUser.email}
                @input=${component._onNewEmailChange}
                placeholder="Enter email"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label" for="newRole">Role</label>
              <select 
                id="newRole"
                class="form-input"
                .value=${component.newUser.role}
                @change=${component._onNewRoleChange}
                required
              >
                <option value="">Select role</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
              </select>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary">
                Add User
              </button>
              <button type="button" class="btn btn-secondary" @click=${component._onCancelAdd}>
                Cancel
              </button>
            </div>
          </form>
        </div>
      ` : ''}
    </div>
  `;
}
