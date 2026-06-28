<?php
namespace Controllers;

use Models\User;
use Models\Trip;

class UserController {
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function normalizeUsername($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
        $value = trim($value, '_');
        return $value;
    }

    private function setUserSession(array $user) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['user_id'] = $user['id'];
    }

    private function setDefaultTripSession($userId) {
        $trip = Trip::getDefaultTripForUser($userId);
        if (!$trip) {
            unset($_SESSION['trip_code'], $_SESSION['trip_name']);
            return;
        }

        $_SESSION['trip_code'] = $trip['code'];
        $_SESSION['trip_name'] = $trip['name'];
    }

    // Render profile page
    public function showProfile() {
        $pageTitle = "Hồ sơ người dùng";
        // Check if user is logged in
        $username = $_SESSION['username'] ?? '';
        $user = null;
        if ($username) {
            $user = User::getByUsername($username);
        }
        include __DIR__ . '/../views/profile.php';
    }

    // API: Join or register user session
    public function loginOrRegister() {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $password = (string)($input['password'] ?? '');

        if (!$name) {
            return $this->jsonResponse(['error' => 'T?n kh?ng ???c ?? tr?ng'], 400);
        }
        if ($password === '') {
            return $this->jsonResponse(['error' => 'M?t kh?u kh?ng ???c ?? tr?ng'], 400);
        }

        $username = $this->normalizeUsername($name);
        if ($username === '') {
            return $this->jsonResponse(['error' => 'T?n ??ng nh?p kh?ng h?p l?'], 400);
        }

        $user = User::getByUsername($username);
        if (!$user) {
            $user = User::getByFullname($name);
        }
        if (!$user) {
            return $this->jsonResponse(['error' => 'T?i kho?n kh?ng t?n t?i, vui l?ng ??ng k? tr??c'], 404);
        }

        if (!empty($user['password_hash'])) {
            if (!password_verify($password, $user['password_hash'])) {
                return $this->jsonResponse(['error' => 'T?n ho?c m?t kh?u kh?ng ??ng'], 401);
            }
        } else {
            User::update($user['id'], $user['fullname'], $password, $user['avatar'] ?? null);
            $user['password_hash'] = 'set';
        }

        $this->setUserSession($user);
        Trip::syncUserTripsByMemberName($user['id'], $user['fullname']);
        Trip::syncUserTripsByMemberName($user['id'], $user['username']);
        if (!empty($_SESSION['trip_code'])) {
            $trip = Trip::getByCode($_SESSION['trip_code']);
            if ($trip) {
                Trip::linkUserToTrip($user['id'], $trip['id']);
            }
        }
        $this->setDefaultTripSession($user['id']);

        return $this->jsonResponse(['success' => true, 'user' => $user]);
    }

    // API: Register a new account
    public function registerAccount() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $fullname = trim((string)($input['fullname'] ?? ''));
        $username = trim((string)($input['username'] ?? ''));
        $password = (string)($input['password'] ?? '');

        if ($fullname === '') {
            return $this->jsonResponse(['error' => 'Tên hiển thị không được để trống'], 400);
        }
        if ($username === '') {
            return $this->jsonResponse(['error' => 'Tên đăng nhập không được để trống'], 400);
        }
        if ($password === '') {
            return $this->jsonResponse(['error' => 'Mật khẩu không được để trống'], 400);
        }

        $username = $this->normalizeUsername($username);
        if ($username === '') {
            return $this->jsonResponse(['error' => 'Tên đăng nhập không hợp lệ'], 400);
        }

        $existing = User::getByUsername($username);
        if ($existing) {
            return $this->jsonResponse(['error' => 'Tên đăng nhập đã tồn tại'], 409);
        }

        $userId = User::create($username, $fullname, $password);
        $user = [
            'id' => $userId,
            'username' => $username,
            'fullname' => $fullname,
        ];

        $this->setUserSession($user);
        Trip::syncUserTripsByMemberName($user['id'], $user['fullname']);
        Trip::syncUserTripsByMemberName($user['id'], $user['username']);
        if (!empty($_SESSION['trip_code'])) {
            $trip = Trip::getByCode($_SESSION['trip_code']);
            if ($trip) {
                Trip::linkUserToTrip($user['id'], $trip['id']);
            }
        }
        $this->setDefaultTripSession($user['id']);

        return $this->jsonResponse(['success' => true, 'user' => $user]);
    }

    // API: Update user profile
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse(['error' => 'Chưa đăng nhập'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $fullname = $input['fullname'] ?? '';
        $password = $input['password'] ?? '';
        $avatar = $input['avatar'] ?? null;

        if (!$fullname) {
            return $this->jsonResponse(['error' => 'Tên không được để trống'], 400);
        }

        $success = User::update($_SESSION['user_id'], $fullname, $password ?: null, $avatar);
        if ($success) {
            $_SESSION['fullname'] = $fullname;
            return $this->jsonResponse(['success' => true]);
        }
        return $this->jsonResponse(['error' => 'Không thể cập nhật hồ sơ'], 500);
    }
}
