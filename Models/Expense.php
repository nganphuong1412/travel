<?php
namespace Models;

use Config\Database;

class Expense {
    public static function add($tripId, $description, $amount, $date, $payerName) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO expenses (trip_id, description, amount, expense_date, payer_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tripId, $description, $amount, $date, $payerName]);
        return $db->lastInsertId();
    }

    public static function delete($tripId, $expenseId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM expenses WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $expenseId]);
    }
}
