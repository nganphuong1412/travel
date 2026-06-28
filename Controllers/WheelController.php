<?php
namespace Controllers;

use Models\Trip;
use Models\Wheel;

class WheelController {
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Render Random Wheel page
    public function showWheel() {
        $pageTitle = "Random Wheel";
        include __DIR__ . '/../views/wheel.php';
    }

    // API: Get wheel options for a trip
    public function getOptions() {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            return $this->jsonResponse(['error' => 'Thiếu mã chuyến đi'], 400);
        }
        $trip = Trip::getByCode($code);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }
        $options = Wheel::getByTripId($trip['id']);

        $formatted = [];
        foreach ($options as $o) {
            $formatted[] = [
                'id' => (string)$o['id'],
                'text' => $o['text'],
            ];
        }
        return $this->jsonResponse($formatted);
    }

    // API: Add a wheel option
    public function addOption() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $text = trim($input['text'] ?? '');

        if (!$tripId || !$text) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        $id = Wheel::add($tripId, $text);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete a wheel option
    public function deleteOption() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $optionId = $input['option_id'] ?? '';

        if (!$tripId || !$optionId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Wheel::delete($tripId, $optionId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Clear all wheel options for a trip
    public function clearOptions() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';

        if (!$tripId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Wheel::clearAll($tripId);
        return $this->jsonResponse(['success' => true]);
    }
}
