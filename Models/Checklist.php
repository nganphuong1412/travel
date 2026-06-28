<?php
namespace Models;

use Config\Database;
use PDO;

class Checklist {
    public static function getPersonal($tripId, $username) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM checklist_items WHERE trip_id = ? AND username = ? ORDER BY id ASC");
        $stmt->execute([$tripId, $username]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function add($tripId, $itemText, $isChecked = 0, $username = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO checklist_items (trip_id, item_text, is_checked, username) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tripId, $itemText, $isChecked ? 1 : 0, $username]);
        return $db->lastInsertId();
    }

    public static function delete($tripId, $itemId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM checklist_items WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $itemId]);
    }

    public static function toggle($itemId, $isChecked) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE checklist_items SET is_checked = ? WHERE id = ?");
        return $stmt->execute([$isChecked ? 1 : 0, $itemId]);
    }
}
