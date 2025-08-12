// UserManagementView Logic
export class UserManagementViewLogic {
  constructor(component) {
    this.component = component;
  }

  async loadUsers() {
    this.component.loading = true;
    this.component.errorMessage = '';
    
    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'get_users'
        })
      });

      const result = await response.json();
      
      if (result.success) {
        this.component.users = result.users || [];
      } else {
        this.component.errorMessage = result.message || 'Failed to load users';
      }
    } catch (error) {
      console.error('Error loading users:', error);
      this.component.errorMessage = 'An error occurred while loading users';
    } finally {
      this.component.loading = false;
    }
  }

  async toggleEmailVerification(user) {
    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'toggle_email_verification',
          user_id: user.id,
          verified: !user.email_verified
        })
      });

      const result = await response.json();
      
      if (result.success) {
        // Update the user in the local array
        const userIndex = this.component.users.findIndex(u => u.id === user.id);
        if (userIndex !== -1) {
          this.component.users[userIndex].email_verified = !user.email_verified;
          this.component.requestUpdate();
        }
        
        this.component.successMessage = `Email verification ${user.email_verified ? 'removed' : 'granted'} for ${user.username}`;
        setTimeout(() => {
          this.component.successMessage = '';
          this.component.requestUpdate();
        }, 3000);
      } else {
        this.component.errorMessage = result.message || 'Failed to update email verification';
      }
    } catch (error) {
      console.error('Error toggling email verification:', error);
      this.component.errorMessage = 'An error occurred while updating email verification';
    }
  }

  async toggleMasterRole(user) {
    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'toggle_master_role',
          user_id: user.id,
          is_master: !user.is_master
        })
      });

      const result = await response.json();
      
      if (result.success) {
        // Update the user in the local array
        const userIndex = this.component.users.findIndex(u => u.id === user.id);
        if (userIndex !== -1) {
          this.component.users[userIndex].is_master = !user.is_master;
          this.component.requestUpdate();
        }
        
        this.component.successMessage = `Master role ${user.is_master ? 'removed from' : 'granted to'} ${user.username}`;
        setTimeout(() => {
          this.component.successMessage = '';
          this.component.requestUpdate();
        }, 3000);
      } else {
        this.component.errorMessage = result.message || 'Failed to update master role';
      }
    } catch (error) {
      console.error('Error toggling master role:', error);
      this.component.errorMessage = 'An error occurred while updating master role';
    }
  }

  async deleteUser(user) {
    if (!confirm(`Are you sure you want to delete user "${user.username}"? This action cannot be undone.`)) {
      return;
    }

    try {
      const response = await fetch('/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'delete_user',
          user_id: user.id
        })
      });

      const result = await response.json();
      
      if (result.success) {
        // Remove the user from the local array
        this.component.users = this.component.users.filter(u => u.id !== user.id);
        this.component.requestUpdate();
        
        this.component.successMessage = `User "${user.username}" has been deleted`;
        setTimeout(() => {
          this.component.successMessage = '';
          this.component.requestUpdate();
        }, 3000);
      } else {
        this.component.errorMessage = result.message || 'Failed to delete user';
      }
    } catch (error) {
      console.error('Error deleting user:', error);
      this.component.errorMessage = 'An error occurred while deleting user';
    }
  }
}
