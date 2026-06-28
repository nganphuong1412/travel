<?php
namespace Controllers;

use Models\Trip;
use Models\Expense;
use Models\Checklist;

class TripController {
    // Helper to output JSON
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Render group view
    public function showGroup() {
        $pageTitle = "Lập kế hoạch du lịch Nhóm";
        include __DIR__ . '/../views/group.php';
    }

    // API: Load full trip by code
    public function getTrip() {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            return $this->jsonResponse(['error' => 'Thiếu mã chuyến đi'], 400);
        }
        $trip = Trip::getByCode($code);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }
        return $this->jsonResponse($trip);
    }

    // API: Create a new trip
    public function createTrip() {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $name = $input['name'] ?? '';

        if (!$code || !$name) {
            return $this->jsonResponse(['error' => 'Vui lòng điền mã chuyến đi và tên chuyến đi'], 400);
        }

        // Check if code already exists
        $existing = Trip::getByCode($code);
        if ($existing) {
            return $this->jsonResponse(['error' => 'Mã chuyến đi này đã được sử dụng'], 400);
        }

        $id = Trip::create($code, $name);
        if ($id) {
            if (isset($_SESSION['user_id'])) {
                Trip::linkUserToTrip($_SESSION['user_id'], $id);
            }
            return $this->jsonResponse(['success' => true, 'id' => $id]);
        }
        return $this->jsonResponse(['error' => 'Không thể tạo chuyến đi'], 500);
    }

    // API: Add member
    public function addMember() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $name = $input['name'] ?? '';

        if (!$tripId || !$name) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::addMember($tripId, $name);

        // If the person is logged in, remember that this trip belongs to
        // their account so the sidebar can list it under "Nhóm của bạn".
        if (isset($_SESSION['user_id'])) {
            Trip::linkUserToTrip($_SESSION['user_id'], $tripId);
        }

        return $this->jsonResponse(['success' => true]);
    }

    // API: List trips the current logged-in user has joined (for sidebar)
    public function getMyTrips() {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return $this->jsonResponse([]);
        }
        $trips = Trip::getTripsForUser($userId);
        return $this->jsonResponse($trips);
    }

    // API: Remove member
    public function removeMember() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $name = $input['name'] ?? '';

        if (!$tripId || !$name) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::removeMember($tripId, $name);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Add Day
    public function addDay() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $date = $input['date'] ?? '';
        $label = $input['label'] ?? '';

        if (!$tripId || !$date) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        $id = Trip::addDay($tripId, $date, $label);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete Day
    public function deleteDay() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $dayId = $input['day_id'] ?? '';

        if (!$tripId || !$dayId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::deleteDay($tripId, $dayId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Add Activity
    public function addActivity() {
        $input = json_decode(file_get_contents('php://input'), true);
        $dayId = $input['day_id'] ?? '';
        $time = $input['time'] ?? '';
        $title = $input['title'] ?? '';
        $location = $input['location'] ?? '';
        $note = $input['note'] ?? '';
        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;

        if (!$dayId || !$title) {
            return $this->jsonResponse(['error' => 'Tên hoạt động không được trống'], 400);
        }

        $id = Trip::addActivity($dayId, $time, $title, $location, $note, $lat, $lng);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete Activity
    public function deleteActivity() {
        $input = json_decode(file_get_contents('php://input'), true);
        $dayId = $input['day_id'] ?? '';
        $activityId = $input['activity_id'] ?? '';

        if (!$dayId || !$activityId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::deleteActivity($dayId, $activityId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Add Expense
    public function addExpense() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $desc = $input['desc'] ?? '';
        $amount = $input['amount'] ?? 0;
        $date = $input['date'] ?? '';
        $payer = $input['payer'] ?? '';

        if (!$tripId || !$desc || !$amount || !$payer) {
            return $this->jsonResponse(['error' => 'Vui lòng điền đủ thông tin chi phí'], 400);
        }

        $id = Expense::add($tripId, $desc, $amount, $date, $payer);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete Expense
    public function deleteExpense() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $expenseId = $input['expense_id'] ?? '';

        if (!$tripId || !$expenseId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Expense::delete($tripId, $expenseId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Add Checklist Item (Group or Personal)
    public function addChecklist() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $text = $input['text'] ?? '';
        $username = $input['username'] ?? null;

        if (!$tripId || !$text) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        $id = Checklist::add($tripId, $text, 0, $username);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Get Personal Checklist Items
    public function getPersonalChecklist() {
        $code = $_GET['code'] ?? '';
        $username = $_GET['username'] ?? '';

        if (!$code || !$username) {
            return $this->jsonResponse(['error' => 'Thiếu thông tin yêu cầu'], 400);
        }

        $trip = Trip::getByCode($code);
        if (!$trip) {
            return $this->jsonResponse(['error' => 'Không tìm thấy chuyến đi'], 404);
        }

        $items = Checklist::getPersonal($trip['id'], $username);
        
        // Format to match JS expectation
        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'id' => (string)$item['id'],
                'text' => $item['item_text'],
                'checked' => (bool)$item['is_checked']
            ];
        }

        return $this->jsonResponse($formatted);
    }

    // API: Delete Checklist Item
    public function deleteChecklist() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $itemId = $input['item_id'] ?? '';

        if (!$tripId || !$itemId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Checklist::delete($tripId, $itemId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Toggle Checklist Item
    public function toggleChecklist() {
        $input = json_decode(file_get_contents('php://input'), true);
        $itemId = $input['item_id'] ?? '';
        $checked = $input['checked'] ?? false;

        if (!$itemId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Checklist::toggle($itemId, $checked);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Add Location
    public function addLocation() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $name = $input['name'] ?? '';
        $lat = $input['lat'] ?? 0;
        $lng = $input['lng'] ?? 0;

        if (!$tripId || !$name || !$lat || !$lng) {
            return $this->jsonResponse(['error' => 'Dữ liệu vị trí không hợp lệ'], 400);
        }

        $id = Trip::addLocation($tripId, $name, $lat, $lng);
        return $this->jsonResponse(['success' => true, 'id' => $id]);
    }

    // API: Delete Location
    public function deleteLocation() {
        $input = json_decode(file_get_contents('php://input'), true);
        $tripId = $input['trip_id'] ?? '';
        $locId = $input['loc_id'] ?? '';

        if (!$tripId || !$locId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::deleteLocation($tripId, $locId);
        return $this->jsonResponse(['success' => true]);
    }

    // API: Update Location Note
    public function updateLocationNote() {
        $input = json_decode(file_get_contents('php://input'), true);
        $locId = $input['loc_id'] ?? '';
        $note = $input['note'] ?? '';

        if (!$locId) {
            return $this->jsonResponse(['error' => 'Dữ liệu không hợp lệ'], 400);
        }

        Trip::updateLocationNote($locId, $note);
        return $this->jsonResponse(['success' => true]);
    }
}
