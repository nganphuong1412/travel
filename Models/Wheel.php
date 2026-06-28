<?php
namespace Models;

use Config\Database;

class Wheel {
    public static function getByTripId($tripId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM wheel_options WHERE trip_id = ? ORDER BY id ASC");
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public static function add($tripId, $text) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO wheel_options (trip_id, text) VALUES (?, ?)");
        $stmt->execute([$tripId, $text]);
        return $db->lastInsertId();
    }

    public static function delete($tripId, $optionId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM wheel_options WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $optionId]);
    }

    public static function clearAll($tripId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM wheel_options WHERE trip_id = ?");
        return $stmt->execute([$tripId]);
    }
}
