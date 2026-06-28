<?php
namespace Models;

use Config\Database;

class User {
    public static function getByUsername($username) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public static function getByFullname($fullname) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(fullname) = LOWER(?) LIMIT 1");
        $stmt->execute([$fullname]);
        return $stmt->fetch();
    }

    public static function create($username, $fullname, $password = null, $avatar = null) {
        $db = Database::getConnection();
        $passwordHash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        $stmt = $db->prepare("INSERT INTO users (username, fullname, password_hash, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $fullname, $passwordHash, $avatar]);
        return $db->lastInsertId();
    }

    public static function update($userId, $fullname, $password = null, $avatar = null) {
        $db = Database::getConnection();
        if ($password) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET fullname = ?, password_hash = ?, avatar = ? WHERE id = ?");
            return $stmt->execute([$fullname, $passwordHash, $avatar, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE users SET fullname = ?, avatar = ? WHERE id = ?");
            return $stmt->execute([$fullname, $avatar, $userId]);
        }
    }
}
