<?php
namespace Models;

use Config\Database;

class Transport {
    public static function getByTripId($tripId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transports WHERE trip_id = ? ORDER BY departure_time ASC");
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public static function add($tripId, $type, $provider, $departurePlace, $arrivalPlace, $departureTime, $arrivalTime, $ticketCode, $price, $note) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO transports (trip_id, type, provider, departure_place, arrival_place, departure_time, arrival_time, ticket_code, price, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tripId,
            $type,
            $provider ?: null,
            $departurePlace,
            $arrivalPlace,
            $departureTime,
            $arrivalTime ?: null,
            $ticketCode ?: null,
            $price ?: 0,
            $note ?: null
        ]);
        return $db->lastInsertId();
    }

    public static function delete($tripId, $transportId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM transports WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $transportId]);
    }
}
