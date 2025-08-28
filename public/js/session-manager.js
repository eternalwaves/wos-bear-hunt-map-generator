/**
 * Secure Session Manager
 * Handles authentication state without using localStorage for security
 */
class SecureSessionManager {
    constructor() {
        this.isAuthenticated = false;
        this.userInfo = null;
        this.sessionCheckPromise = null;
    }

    /**
     * Initialize session manager and check current authentication status
     */
    async initialize() {
        try {
            console.log('Initializing session manager...');
            
            // Check if user is authenticated via server-side session
            const response = await fetch('auth.php?action=check_session', {
                method: 'GET',
                credentials: 'include', // Include cookies
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('Session check response status:', response.status);
            console.log('Session check response headers:', response.headers);

            if (response.ok) {
                const data = await response.json();
                console.log('Session check response data:', data);
                
                if (data.status === 'success' && data.data.authenticated) {
                    this.isAuthenticated = true;
                    this.userInfo = data.data.user;
                    console.log('User authenticated:', this.userInfo);
                    return true;
                }
            }
            
            this.isAuthenticated = false;
            this.userInfo = null;
            console.log('User not authenticated');
            return false;
        } catch (error) {
            console.error('Session check failed:', error);
            this.isAuthenticated = false;
            this.userInfo = null;
            return false;
        }
    }

    /**
     * Check if user is authenticated (cached result)
     */
    isUserAuthenticated() {
        return this.isAuthenticated;
    }

    /**
     * Get current user info
     */
    getUserInfo() {
        return this.userInfo;
    }

    /**
     * Perform login with secure session handling
     */
    async login(username, password) {
        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Login failed');
            }

            // Check if OTP is required
            if (result.data.needs_otp) {
                return {
                    success: true,
                    needsOtp: true,
                    userId: result.data.user_id
                };
            }

            // Login successful, update session state
            this.isAuthenticated = true;
            this.userInfo = result.data.user;

            return {
                success: true,
                needsOtp: false,
                user: result.data.user
            };
        } catch (error) {
            throw new Error(error.message || 'Login failed');
        }
    }

    /**
     * Verify OTP and complete login
     */
    async verifyOtp(userId, otpCode) {
        try {
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('user_id', userId);
            formData.append('otp_code', otpCode);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'OTP verification failed');
            }

            // OTP verified, update session state
            this.isAuthenticated = true;
            this.userInfo = result.data.user;

            return {
                success: true,
                user: result.data.user
            };
        } catch (error) {
            throw new Error(error.message || 'OTP verification failed');
        }
    }

    /**
     * Logout user and clear session
     */
    async logout() {
        try {
            const response = await fetch('auth.php?action=logout', {
                method: 'POST',
                credentials: 'include'
            });

            // Clear local session state regardless of server response
            this.isAuthenticated = false;
            this.userInfo = null;

            return true;
        } catch (error) {
            console.error('Logout error:', error);
            // Clear local session state even if server logout fails
            this.isAuthenticated = false;
            this.userInfo = null;
            return true;
        }
    }

    /**
     * Make authenticated API requests
     */
    async makeAuthenticatedRequest(url, options = {}) {
        if (!this.isAuthenticated) {
            throw new Error('User not authenticated');
        }

        const defaultOptions = {
            credentials: 'include', // Include cookies
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });

        // If we get a 401, the session has expired
        if (response.status === 401) {
            this.isAuthenticated = false;
            this.userInfo = null;
            window.location.href = 'login.html';
            throw new Error('Session expired');
        }

        return response;
    }

    /**
     * Register new user
     */
    async register(username, email, password) {
        try {
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Registration failed');
            }

            return {
                success: true,
                message: result.message || 'Registration successful! Please check your email to verify your account.'
            };
        } catch (error) {
            throw new Error(error.message || 'Registration failed');
        }
    }

    /**
     * Request password reset
     */
    async requestPasswordReset(email) {
        try {
            const formData = new FormData();
            formData.append('action', 'request_password_reset');
            formData.append('email', email);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Password reset request failed');
            }

            return {
                success: true,
                message: result.message || 'Password reset email sent! Please check your email.'
            };
        } catch (error) {
            throw new Error(error.message || 'Password reset request failed');
        }
    }

    /**
     * Reset password with token
     */
    async resetPassword(token, newPassword) {
        try {
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('token', token);
            formData.append('new_password', newPassword);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Password reset failed');
            }

            return {
                success: true,
                message: result.message || 'Password reset successful! You can now log in with your new password.'
            };
        } catch (error) {
            throw new Error(error.message || 'Password reset failed');
        }
    }

    /**
     * Get all users (for admin users)
     */
    async getAllUsers() {
        try {
            const response = await fetch('auth.php?action=get_all_users', {
                method: 'GET',
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to get users');
            }

            return result.data;
        } catch (error) {
            console.error('Failed to get users:', error);
            throw error;
        }
    }

    /**
     * Approve user (for admin users)
     */
    async approveUser(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'approve_user');
            formData.append('user_id', userId);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to approve user');
            }

            return { success: true };
        } catch (error) {
            console.error('Failed to approve user:', error);
            throw error;
        }
    }

    /**
     * Deactivate user (for admin users)
     */
    async deactivateUser(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'deactivate_user');
            formData.append('user_id', userId);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to deactivate user');
            }

            return { success: true };
        } catch (error) {
            console.error('Failed to deactivate user:', error);
            throw error;
        }
    }

    /**
     * Delete user (for admin users)
     */
    async deleteUser(userId) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to delete user');
            }

            return { success: true };
        } catch (error) {
            console.error('Failed to delete user:', error);
            throw error;
        }
    }

    /**
     * Update username (for all users)
     */
    async updateUsername(userId, newUsername, currentPassword) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_username');
            formData.append('user_id', userId);
            formData.append('new_username', newUsername);
            formData.append('current_password', currentPassword);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to update username');
            }

            return { success: true, message: result.message };
        } catch (error) {
            console.error('Failed to update username:', error);
            throw error;
        }
    }

    /**
     * Update password (for all users)
     */
    async updatePassword(userId, currentPassword, newPassword) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_password');
            formData.append('user_id', userId);
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);

            const response = await fetch('auth.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to update password');
            }

            return { success: true, message: result.message };
        } catch (error) {
            console.error('Failed to update password:', error);
            throw error;
        }
    }

    /**
     * Check if user has admin privileges
     */
    isAdmin() {
        return this.userInfo && this.userInfo.is_master;
    }

    /**
     * Redirect to login if not authenticated
     */
    requireAuth() {
        if (!this.isAuthenticated) {
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }

    /**
     * Redirect to login if not admin
     */
    requireAdmin() {
        if (!this.requireAuth()) {
            return false;
        }
        if (!this.isAdmin()) {
            window.location.href = 'index.html';
            return false;
        }
        return true;
    }

    /**
     * Refresh session data from server
     */
    async refreshSession() {
        try {
            const response = await fetch('auth.php?action=check_session', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.status === 'success' && data.data.authenticated) {
                    this.userInfo = data.data.user;
                    return true;
                }
            }
            return false;
        } catch (error) {
            console.error('Session refresh failed:', error);
            return false;
        }
    }
}

// Create global session manager instance
window.sessionManager = new SecureSessionManager(); 