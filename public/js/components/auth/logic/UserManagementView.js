// UserManagementView Logic
export class UserManagementViewLogic {
  constructor(component) {
    this.component = component;
  }

  async loadUsers() {
    this.component.loading = true;
    this.component.error = '';

    try {
      const response = await fetch('/auth.php?action=get_users', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const data = await response.json();

      if (response.ok) {
        this.component.users = data.users || [];
      } else {
        this.component.error = data.error || 'Failed to load users.';
      }
    } catch (error) {
      console.error('Load users error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }

  onAddUser() {
    this.component.showAddForm = true;
    this.component.newUser = { username: '', email: '', role: '' };
  }

  onRefresh() {
    this.loadUsers();
  }

  onEditUser(user) {
    // Implement edit user functionality
    console.log('Edit user:', user);
  }

  onDeleteUser(user) {
    if (confirm(`Are you sure you want to delete user "${user.username}"?`)) {
      this._deleteUser(user);
    }
  }

  async onAddUserSubmit() {
    this.component.loading = true;
    this.component.error = '';

    try {
      const response = await fetch('/auth.php?action=add_user', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(this.component.newUser)
      });

      const data = await response.json();

      if (response.ok) {
        this.component.success = 'User added successfully.';
        this.component.showAddForm = false;
        this.component.newUser = { username: '', email: '', role: '' };
        await this.loadUsers(); // Refresh the list
      } else {
        this.component.error = data.error || 'Failed to add user.';
      }
    } catch (error) {
      console.error('Add user error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }

  onCancelAdd() {
    this.component.showAddForm = false;
    this.component.newUser = { username: '', email: '', role: '' };
  }

  async _deleteUser(user) {
    this.component.loading = true;
    this.component.error = '';

    try {
      const response = await fetch('/auth.php?action=delete_user', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: user.id })
      });

      const data = await response.json();

      if (response.ok) {
        this.component.success = 'User deleted successfully.';
        await this.loadUsers(); // Refresh the list
      } else {
        this.component.error = data.error || 'Failed to delete user.';
      }
    } catch (error) {
      console.error('Delete user error:', error);
      this.component.error = 'Network error. Please check your connection.';
    } finally {
      this.component.loading = false;
    }
  }
}

