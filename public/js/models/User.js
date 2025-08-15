export class User {
  constructor(
    username,
    email,
    passwordHash,
    isMaster = false,
    isApproved = false,
    isActive = true,
    emailVerified = false,
    emailVerificationToken = null,
    passwordResetToken = null,
    passwordResetExpires = null,
    otpSecret = null,
    otpEnabled = true,
    lastLogin = null,
    id = null,
    createdAt = null,
    updatedAt = null
  ) {
    this.username = username;
    this.email = email;
    this.passwordHash = passwordHash;
    this.isMaster = isMaster;
    this.isApproved = isApproved;
    this.isActive = isActive;
    this.emailVerified = emailVerified;
    this.emailVerificationToken = emailVerificationToken;
    this.passwordResetToken = passwordResetToken;
    this.passwordResetExpires = passwordResetExpires;
    this.otpSecret = otpSecret;
    this.otpEnabled = otpEnabled;
    this.lastLogin = lastLogin;
    this.id = id ?? 0;
    this.createdAt = createdAt ?? new Date();
    this.updatedAt = updatedAt ?? new Date();
  }

  // Getters
  getId() { return this.id; }
  getUsername() { return this.username; }
  getEmail() { return this.email; }
  getPasswordHash() { return this.passwordHash; }
  isMaster() { return this.isMaster; }
  isApproved() { return this.isApproved; }
  isActive() { return this.isActive; }
  isEmailVerified() { return this.emailVerified; }
  getEmailVerificationToken() { return this.emailVerificationToken; }
  getPasswordResetToken() { return this.passwordResetToken; }
  getPasswordResetExpires() { return this.passwordResetExpires; }
  getOtpSecret() { return this.otpSecret; }
  isOtpEnabled() { return this.otpEnabled; }
  getLastLogin() { return this.lastLogin; }
  getCreatedAt() { return this.createdAt; }
  getUpdatedAt() { return this.updatedAt; }

  // Setters
  setId(id) { this.id = id; }
  setUsername(username) { this.username = username; }
  setEmail(email) { this.email = email; }
  setPasswordHash(passwordHash) { this.passwordHash = passwordHash; }
  setMaster(isMaster) { this.isMaster = isMaster; }
  setApproved(isApproved) { this.isApproved = isApproved; }
  setActive(isActive) { this.isActive = isActive; }
  setEmailVerified(emailVerified) { this.emailVerified = emailVerified; }
  setEmailVerificationToken(token) { this.emailVerificationToken = token; }
  setPasswordResetToken(token) { this.passwordResetToken = token; }
  setPasswordResetExpires(expires) { this.passwordResetExpires = expires; }
  setOtpSecret(secret) { this.otpSecret = secret; }
  setOtpEnabled(enabled) { this.otpEnabled = enabled; }
  setLastLogin(lastLogin) { this.lastLogin = lastLogin; }
  setCreatedAt(createdAt) { this.createdAt = createdAt; }
  setUpdatedAt(updatedAt) { this.updatedAt = updatedAt; }

  // Business logic methods
  canLogin() {
    return this.isActive && this.isApproved;
  }

  needsOtp() {
    return this.otpEnabled && !this.isMaster;
  }

  isPasswordResetTokenValid() {
    if (!this.passwordResetToken || !this.passwordResetExpires) {
      return false;
    }
    return new Date() < new Date(this.passwordResetExpires);
  }

  isEmailVerificationTokenValid() {
    return !!this.emailVerificationToken;
  }

  toArray() {
    return {
      id: this.id,
      username: this.username,
      email: this.email,
      isMaster: this.isMaster,
      isApproved: this.isApproved,
      isActive: this.isActive,
      emailVerified: this.emailVerified,
      otpEnabled: this.otpEnabled,
      lastLogin: this.lastLogin,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt
    };
  }

  toArrayForAdmin() {
    return {
      ...this.toArray(),
      emailVerificationToken: this.emailVerificationToken,
      passwordResetToken: this.passwordResetToken,
      passwordResetExpires: this.passwordResetExpires,
      otpSecret: this.otpSecret
    };
  }
} 