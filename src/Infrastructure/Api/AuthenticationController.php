<?php

namespace App\Infrastructure\Api;

use App\Application\Service\AuthenticationService;
use App\Application\Exception\AuthenticationException;
use App\Application\Exception\ValidationException;

class AuthenticationController
{
    private AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function handleRequest(): void
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'register':
                    $this->handleRegister();
                    break;
                case 'login':
                    $this->handleLogin();
                    break;
                case 'verify_otp':
                    $this->handleVerifyOtp();
                    break;
                case 'verify_email':
                    $this->handleVerifyEmail();
                    break;
                case 'request_password_reset':
                    $this->handleRequestPasswordReset();
                    break;
                case 'reset_password':
                    $this->handleResetPassword();
                    break;
                case 'approve_user':
                    $this->handleApproveUser();
                    break;
                case 'deactivate_user':
                    $this->handleDeactivateUser();
                    break;
                case 'delete_user':
                    $this->handleDeleteUser();
                    break;
                case 'get_pending_approvals':
                    $this->handleGetPendingApprovals();
                    break;
                case 'get_all_users':
                    $this->handleGetAllUsers();
                    break;
                case 'check_session':
                    $this->handleCheckSession();
                    break;
                case 'logout':
                    $this->handleLogout();
                    break;
                case 'update_username':
                    $this->handleUpdateUsername();
                    break;
                case 'update_password':
                    $this->handleUpdatePassword();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                    break;
            }
        } catch (AuthenticationException $e) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
        }
    }

    private function handleRegister(): void
    {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            throw new ValidationException('Username, email, and password are required');
        }

        $user = $this->authService->register($username, $email, $password);

        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful. Please check your email to verify your account.'
        ]);
    }

    private function handleLogin(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new ValidationException('Username and password are required');
        }

        $result = $this->authService->login($username, $password);



        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'needs_otp' => $result['needs_otp'],
                'user' => [
                    'id' => $result['user']->getId(),
                    'username' => $result['user']->getUsername(),
                    'email' => $result['user']->getEmail(),
                    'is_master' => $result['user']->isMaster()
                ]
            ]
        ]);
    }

    private function handleVerifyOtp(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $otpCode = $_POST['otp_code'] ?? '';

        if (empty($userId) || empty($otpCode)) {
            throw new ValidationException('User ID and OTP code are required');
        }

        $result = $this->authService->verifyOtp($userId, $otpCode);



        echo json_encode([
            'status' => 'success',
            'message' => 'OTP verified successfully',
            'data' => [
                'user' => [
                    'id' => $result['user']->getId(),
                    'username' => $result['user']->getUsername(),
                    'email' => $result['user']->getEmail(),
                    'is_master' => $result['user']->isMaster()
                ]
            ]
        ]);
    }

    private function handleVerifyEmail(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            throw new ValidationException('Verification token is required');
        }

        $this->authService->verifyEmail($token);

        echo json_encode([
            'status' => 'success',
            'message' => 'Email verified successfully'
        ]);
    }

    private function handleRequestPasswordReset(): void
    {
        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            throw new ValidationException('Email is required');
        }

        $this->authService->requestPasswordReset($email);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset email sent'
        ]);
    }

    private function handleResetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            throw new ValidationException('Token and new password are required');
        }

        $this->authService->resetPassword($token, $newPassword);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset successful'
        ]);
    }

    private function handleApproveUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);

        if (empty($userId)) {
            throw new ValidationException('User ID is required');
        }

        $this->authService->approveUser($userId);

        echo json_encode([
            'status' => 'success',
            'message' => 'User approved successfully'
        ]);
    }

    private function handleDeactivateUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);

        if (empty($userId)) {
            throw new ValidationException('User ID is required');
        }

        $this->authService->deactivateUser($userId);

        echo json_encode([
            'status' => 'success',
            'message' => 'User deactivated successfully'
        ]);
    }

    private function handleDeleteUser(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);

        if (empty($userId)) {
            throw new ValidationException('User ID is required');
        }

        $this->authService->deleteUser($userId);

        echo json_encode([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    private function handleGetPendingApprovals(): void
    {
        $users = $this->authService->getPendingApprovals();

        $userData = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $users);

        echo json_encode([
            'status' => 'success',
            'data' => $userData
        ]);
    }

    private function handleGetAllUsers(): void
    {
        $users = $this->authService->getAllUsers();

        $userData = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'is_master' => $user->isMaster(),
                'is_approved' => $user->isApproved(),
                'is_active' => $user->isActive(),
                'email_verified' => $user->isEmailVerified(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'last_login' => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null
            ];
        }, $users);

        echo json_encode([
            'status' => 'success',
            'data' => $userData
        ]);
    }

    private function handleLogout(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear session data
        $_SESSION = array();

        // Destroy the session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    private function handleCheckSession(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }



        // Check if user is authenticated via session
        if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            $user = $this->authService->getUserInfo($_SESSION['user_id']);
            if ($user) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'authenticated' => true,
                        'user' => [
                            'id' => $user->getId(),
                            'username' => $user->getUsername(),
                            'email' => $user->getEmail(),
                            'is_master' => $user->isMaster()
                        ]
                    ]
                ]);
                return;
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'authenticated' => false,
                'user' => null
            ]
        ]);
    }

    private function handleUpdateUsername(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newUsername = $_POST['new_username'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';

        if (empty($userId) || empty($newUsername) || empty($currentPassword)) {
            throw new ValidationException('User ID, new username, and current password are required');
        }

        $this->authService->updateUsername($userId, $newUsername, $currentPassword);

        echo json_encode([
            'status' => 'success',
            'message' => 'Username updated successfully'
        ]);
    }

    private function handleUpdatePassword(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
            throw new ValidationException('User ID, current password, and new password are required');
        }

        $this->authService->updatePassword($userId, $currentPassword, $newPassword);

        echo json_encode([
            'status' => 'success',
            'message' => 'Password updated successfully'
        ]);
    }
} 