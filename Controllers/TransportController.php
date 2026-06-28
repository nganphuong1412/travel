<?php
namespace Controllers;

use Models\Transport;
use Models\Trip;

class TransportController {
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Render transport planner page
    public function showTransport() {
        $pageTitle = "Phương tiện di chuyển";
        include __DIR__ . '/../views/transport.php';
    }

    // API: Get transport items
    public function getTransports() {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            return $this->jsonResponse(['error' => 'Thiếu mã chuyến đi'], 400);
        }

        $trip = Trip::getByCode($code);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }

        $transports = Transport::getByTripId($trip['id']);
        
        // Map database transport records to JSON response
        $formatted = [];
        foreach ($transports as $t) {
            $formatted[] = [
                'id' => (string)$t['id'],
                'type' => $t['type'],
                'provider' => $t['provider'] ?? '',
                'departure_place' => $t['departure_place'],
                'arrival_place' => $t['arrival_place'],
                'departure_time' => $t['departure_time'],
                'arrival_time' => $t['arrival_time'] ?? '',
                'ticket_code' => $t['ticket_code'] ?? '',
                'price' => (float)$t['price'],
                'note' => $t['note'] ?? ''
            ];
        }

        return $this->jsonResponse($formatted);
    }

    // API: Add transport item
    public function addTransport() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripCode = $input['trip_code'] ?? '';
        $type = $input['type'] ?? '';
        $provider = $input['provider'] ?? '';
        $departurePlace = $input['departure_place'] ?? '';
        $arrivalPlace = $input['arrival_place'] ?? '';
        $departureTime = $input['departure_time'] ?? '';
        $arrivalTime = $input['arrival_time'] ?? '';
        $ticketCode = $input['ticket_code'] ?? '';
        $price = $input['price'] ?? 0;
        $note = $input['note'] ?? '';

        if (!$tripCode || !$type || !$departurePlace || !$arrivalPlace || !$departureTime) {
            return $this->jsonResponse(['error' => 'Vui lòng nhập đủ thông tin bắt buộc'], 400);
        }

        $trip = Trip::getByCode($tripCode);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }

        $id = Transport::add($trip['id'], $type, $provider, $departurePlace, $arrivalPlace, $departureTime, $arrivalTime, $ticketCode, $price, $note);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete transport item
    public function deleteTransport() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripCode = $input['trip_code'] ?? '';
        $transportId = $input['transport_id'] ?? '';

        if (!$tripCode || !$transportId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        $trip = Trip::getByCode($tripCode);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }

        Transport::delete($trip['id'], $transportId);
        return $this->jsonResponse(['success' => true]);
    }
}
