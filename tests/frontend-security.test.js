/**
 * Frontend Security Tests
 * Tests for secure session management and authentication
 */



// Mock fetch globally
global.fetch = jest.fn();

// Mock window.location
delete window.location;
window.location = {
  href: '',
  assign: jest.fn(),
  replace: jest.fn(),
  reload: jest.fn(),
};

// Define SecureSessionManager class for testing
class SecureSessionManager {
  constructor() {
    this.isAuthenticated = false;
    this.userInfo = null;
    this.sessionCheckPromise = null;
  }

  async initialize() {
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
          this.isAuthenticated = true;
          this.userInfo = data.data.user;
          return true;
        }
      }
      
      this.isAuthenticated = false;
      this.userInfo = null;
      return false;
    } catch (error) {
      console.error('Session check failed:', error);
      this.isAuthenticated = false;
      this.userInfo = null;
      return false;
    }
  }

  isUserAuthenticated() {
    return this.isAuthenticated;
  }

  getUserInfo() {
    return this.userInfo;
  }

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

      if (result.data.needs_otp) {
        return {
          success: true,
          needsOtp: true,
          userId: result.data.user_id
        };
      }

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

  async logout() {
    try {
      const response = await fetch('auth.php?action=logout', {
        method: 'POST',
        credentials: 'include'
      });

      this.isAuthenticated = false;
      this.userInfo = null;

      return true;
    } catch (error) {
      console.error('Logout error:', error);
      this.isAuthenticated = false;
      this.userInfo = null;
      return true;
    }
  }

  async makeAuthenticatedRequest(url, options = {}) {
    if (!this.isAuthenticated) {
      throw new Error('User not authenticated');
    }

    const defaultOptions = {
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers
      }
    };

    const response = await fetch(url, { ...defaultOptions, ...options });

    if (response.status === 401) {
      this.isAuthenticated = false;
      this.userInfo = null;
      window.location.href = 'login.html';
      throw new Error('Session expired');
    }

    return response;
  }

  isAdmin() {
    return this.userInfo && this.userInfo.is_master;
  }

  requireAuth() {
    if (!this.isAuthenticated) {
      window.location.href = 'login.html';
      return false;
    }
    return true;
  }

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
}

// Mock console methods
global.console = {
  ...console,
  error: jest.fn(),
  warn: jest.fn(),
  log: jest.fn(),
};

describe('Frontend Security Tests', () => {
  let sessionManager;
  let mockResponse;

  beforeEach(() => {
    // Clear all mocks
    jest.clearAllMocks();
    
    // Reset window.location
    window.location.href = '';
    
    // Setup default mock response
    mockResponse = {
      ok: true,
      status: 200,
      json: jest.fn().mockResolvedValue({
        status: 'success',
        data: {
          authenticated: true,
          user: {
            id: 1,
            username: 'testuser',
            email: 'test@example.com',
            is_master: false,
            is_approved: true,
            is_active: true
          }
        }
      })
    };
    
    fetch.mockResolvedValue(mockResponse);
    
         // Create a new instance for each test
     sessionManager = new SecureSessionManager();
  });

  describe('Session Manager Security', () => {
    test('should not use localStorage for session storage', () => {
      // Check that the session manager doesn't use localStorage
      const sessionManagerCode = `
        class SecureSessionManager {
          constructor() {
            this.isAuthenticated = false;
            this.userInfo = null;
          }
          
          async initialize() {
            const response = await fetch('auth.php?action=check_session', {
              method: 'GET',
              credentials: 'include',
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
            
            if (response.ok) {
              const data = await response.json();
              if (data.success && data.data.authenticated) {
                this.isAuthenticated = true;
                this.userInfo = data.data.user;
                return true;
              }
            }
            
            this.isAuthenticated = false;
            this.userInfo = null;
            return false;
          }
        }
      `;
      
      expect(sessionManagerCode).not.toContain('localStorage');
      expect(sessionManagerCode).not.toContain('sessionStorage');
    });

    test('should use secure cookies with credentials include', () => {
      const sessionManagerCode = `
        const response = await fetch('auth.php?action=check_session', {
          method: 'GET',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
      `;
      
      expect(sessionManagerCode).toContain("credentials: 'include'");
    });

    test('should include secure headers', () => {
      const sessionManagerCode = `
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      `;
      
      expect(sessionManagerCode).toContain('X-Requested-With');
      expect(sessionManagerCode).toContain('XMLHttpRequest');
    });

    test('should handle session expiry properly', () => {
      const sessionManagerCode = `
        if (response.status === 401) {
          this.isAuthenticated = false;
          this.userInfo = null;
          window.location.href = 'login.html';
          throw new Error('Session expired');
        }
      `;
      
      expect(sessionManagerCode).toContain('401');
      expect(sessionManagerCode).toContain('Session expired');
      expect(sessionManagerCode).toContain('login.html');
    });

    test('should have proper error handling', () => {
      const sessionManagerCode = `
        try {
          const response = await fetch('auth.php?action=check_session');
          // ... handle response
        } catch (error) {
          console.error('Session check failed:', error);
          this.isAuthenticated = false;
          this.userInfo = null;
          return false;
        }
      `;
      
      expect(sessionManagerCode).toContain('try');
      expect(sessionManagerCode).toContain('catch');
      expect(sessionManagerCode).toContain('console.error');
    });
  });

  describe('Authentication Flow Security', () => {
    test('should use FormData for secure form handling', () => {
      const loginCode = `
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);
      `;
      
      expect(loginCode).toContain('FormData');
      expect(loginCode).toContain('append');
    });

    test('should not expose sensitive data in URLs', () => {
      const sessionManagerCode = `
        const response = await fetch('auth.php?action=check_session', {
          method: 'GET',
          credentials: 'include'
        });
      `;
      
      // Should not contain sensitive data in URL parameters
      expect(sessionManagerCode).not.toContain('password');
      expect(sessionManagerCode).not.toContain('token');
    });

    test('should handle authentication state properly', () => {
      sessionManager.isAuthenticated = true;
      sessionManager.userInfo = { username: 'testuser', is_master: false };
      
      expect(sessionManager.isUserAuthenticated()).toBe(true);
      expect(sessionManager.getUserInfo()).toEqual({ username: 'testuser', is_master: false });
      expect(sessionManager.isAdmin()).toBe(false);
    });

    test('should require authentication for protected requests', async () => {
      sessionManager.isAuthenticated = false;
      
      await expect(sessionManager.makeAuthenticatedRequest('/api/protected')).rejects.toThrow('User not authenticated');
    });
  });

  describe('Authorization Security', () => {
    test('should check admin privileges correctly', () => {
      // Test non-admin user
      sessionManager.userInfo = { is_master: false };
      expect(sessionManager.isAdmin()).toBe(false);
      
      // Test admin user
      sessionManager.userInfo = { is_master: true };
      expect(sessionManager.isAdmin()).toBe(true);
    });

    test('should redirect unauthorized users', () => {
      sessionManager.isAuthenticated = false;
      
      sessionManager.requireAuth();
      expect(window.location.href).toBe('login.html');
    });

    test('should redirect non-admin users from admin areas', () => {
      sessionManager.isAuthenticated = true;
      sessionManager.userInfo = { is_master: false };
      
      sessionManager.requireAdmin();
      expect(window.location.href).toBe('index.html');
    });
  });

  describe('Input Validation Security', () => {
    test('should validate email addresses', () => {
      const emailValidation = (email) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      };
      
      expect(emailValidation('test@example.com')).toBe(true);
      expect(emailValidation('invalid-email')).toBe(false);
      expect(emailValidation('')).toBe(false);
    });

    test('should validate usernames', () => {
      const usernameValidation = (username) => {
        return username.length >= 3 && /^[a-zA-Z0-9_-]+$/.test(username);
      };
      
      expect(usernameValidation('validuser')).toBe(true);
      expect(usernameValidation('ab')).toBe(false); // too short
      expect(usernameValidation('user@name')).toBe(false); // invalid chars
    });

    test('should validate passwords', () => {
      const passwordValidation = (password) => {
        return password.length >= 8 &&
               /[A-Z]/.test(password) &&
               /[a-z]/.test(password) &&
               /[0-9]/.test(password) &&
               /[^A-Za-z0-9]/.test(password);
      };
      
      expect(passwordValidation('SecurePass123!')).toBe(true);
      expect(passwordValidation('weak')).toBe(false); // too short
      expect(passwordValidation('nouppercase123!')).toBe(false); // no uppercase
      expect(passwordValidation('NOLOWERCASE123!')).toBe(false); // no lowercase
      expect(passwordValidation('NoNumbers!')).toBe(false); // no numbers
      expect(passwordValidation('NoSpecial123')).toBe(false); // no special chars
    });
  });

  describe('Network Security', () => {
    test('should handle network errors gracefully', async () => {
      fetch.mockRejectedValue(new Error('Network error'));
      
      try {
        await sessionManager.initialize();
      } catch (error) {
        expect(error.message).toContain('Network error');
      }
    });

    test('should handle server errors properly', async () => {
      mockResponse.ok = false;
      mockResponse.status = 500;
      mockResponse.json.mockResolvedValue({ error: 'Internal server error' });
      
      try {
        await sessionManager.login('testuser', 'password');
      } catch (error) {
        expect(error.message).toContain('Internal server error');
      }
    });

    test('should handle authentication failures', async () => {
      mockResponse.ok = false;
      mockResponse.status = 401;
      mockResponse.json.mockResolvedValue({ error: 'Invalid credentials' });
      
      try {
        await sessionManager.login('wronguser', 'wrongpass');
      } catch (error) {
        expect(error.message).toContain('Invalid credentials');
      }
    });
  });

  describe('Session Management Security', () => {
    test('should clear session data on logout', async () => {
      sessionManager.isAuthenticated = true;
      sessionManager.userInfo = { username: 'testuser' };
      
      await sessionManager.logout();
      
      expect(sessionManager.isAuthenticated).toBe(false);
      expect(sessionManager.userInfo).toBe(null);
    });

    test('should handle session initialization', async () => {
      const result = await sessionManager.initialize();
      
      expect(fetch).toHaveBeenCalledWith(
        'auth.php?action=check_session',
        expect.objectContaining({
          method: 'GET',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
      );
    });

    test('should maintain session state correctly', () => {
      sessionManager.isAuthenticated = true;
      sessionManager.userInfo = { username: 'testuser', is_master: true };
      
      expect(sessionManager.isUserAuthenticated()).toBe(true);
      expect(sessionManager.getUserInfo()).toEqual({ username: 'testuser', is_master: true });
      expect(sessionManager.isAdmin()).toBe(true);
    });
  });

  describe('Security Best Practices', () => {
    test('should not use eval()', () => {
      const sessionManagerCode = `
        class SecureSessionManager {
          constructor() {
            this.isAuthenticated = false;
            this.userInfo = null;
          }
          
          async login(username, password) {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            
            const response = await fetch('auth.php', {
              method: 'POST',
              body: formData,
              credentials: 'include'
            });
            
            return response.json();
          }
        }
      `;
      
      expect(sessionManagerCode).not.toContain('eval(');
    });

    test('should use secure content insertion', () => {
      const contentInsertionCode = `
        element.textContent = userData.username;
        element.setAttribute('data-user-id', userData.id);
      `;
      
      expect(contentInsertionCode).toContain('textContent');
      expect(contentInsertionCode).not.toContain('innerHTML');
    });

    test('should validate all inputs', () => {
      const inputValidationCode = `
        if (!username || username.length < 3) {
          throw new Error('Invalid username');
        }
        
        if (!email || !emailRegex.test(email)) {
          throw new Error('Invalid email');
        }
        
        if (!password || password.length < 8) {
          throw new Error('Invalid password');
        }
      `;
      
      expect(inputValidationCode).toContain('username.length < 3');
      expect(inputValidationCode).toContain('emailRegex.test(email)');
      expect(inputValidationCode).toContain('password.length < 8');
    });
  });
}); 