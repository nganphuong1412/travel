<?php
namespace Models;

use Config\Database;
use PDO;

class Trip {
    public static function getByCode($code) {
        $db = Database::getConnection();

        // 1. Get trip details
        $stmt = $db->prepare("SELECT * FROM trips WHERE code = ?");
        $stmt->execute([$code]);
        $tripRow = $stmt->fetch();

        if (!$tripRow) {
            return null;
        }

        $tripId = $tripRow['id'];

        // 2. Get members
        $stmt = $db->prepare("SELECT name FROM trip_members WHERE trip_id = ?");
        $stmt->execute([$tripId]);
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 3. Get itinerary
        $stmt = $db->prepare("SELECT * FROM itinerary_days WHERE trip_id = ? ORDER BY day_date ASC");
        $stmt->execute([$tripId]);
        $dayRows = $stmt->fetchAll();

        $itinerary = [];
        foreach ($dayRows as $dayRow) {
            $dayId = $dayRow['id'];
            
            // Get items for this day
            $itemStmt = $db->prepare("SELECT * FROM itinerary_items WHERE day_id = ? ORDER BY item_time ASC");
            $itemStmt->execute([$dayId]);
            $itemRows = $itemStmt->fetchAll();

            $items = [];
            foreach ($itemRows as $itemRow) {
                $items[] = [
                    'id' => (string)$itemRow['id'],
                    'time' => $itemRow['item_time'] ? substr($itemRow['item_time'], 0, 5) : '',
                    'title' => $itemRow['title'],
                    'location' => $itemRow['location'] ?? '',
                    'lat' => isset($itemRow['lat']) ? (float)$itemRow['lat'] : null,
                    'lng' => isset($itemRow['lng']) ? (float)$itemRow['lng'] : null,
                    'note' => $itemRow['note'] ?? ''
                ];
            }

            $itinerary[] = [
                'id' => (string)$dayId,
                'date' => $dayRow['day_date'],
                'label' => $dayRow['label'] ?? '',
                'items' => $items
            ];
        }

        // 4. Get expenses
        $stmt = $db->prepare("SELECT * FROM expenses WHERE trip_id = ? ORDER BY expense_date DESC, id DESC");
        $stmt->execute([$tripId]);
        $expenseRows = $stmt->fetchAll();
        
        $expenses = [];
        foreach ($expenseRows as $exp) {
            $expenses[] = [
                'id' => (string)$exp['id'],
                'desc' => $exp['description'],
                'amount' => (float)$exp['amount'],
                'date' => $exp['expense_date'],
                'payer' => $exp['payer_name']
            ];
        }

        // 5. Get checklist (Group only)
        $stmt = $db->prepare("SELECT * FROM checklist_items WHERE trip_id = ? AND username IS NULL ORDER BY id ASC");
        $stmt->execute([$tripId]);
        $checkRows = $stmt->fetchAll();

        $checklist = [];
        foreach ($checkRows as $item) {
            $checklist[] = [
                'id' => (string)$item['id'],
                'text' => $item['item_text'],
                'checked' => (bool)$item['is_checked']
            ];
        }

        // 6. Get locations
        $stmt = $db->prepare("SELECT * FROM saved_locations WHERE trip_id = ? ORDER BY id ASC");
        $stmt->execute([$tripId]);
        $locRows = $stmt->fetchAll();

        $locations = [];
        foreach ($locRows as $loc) {
            $locations[] = [
                'id' => (string)$loc['id'],
                'name' => $loc['name'],
                'lat' => (float)$loc['lat'],
                'lng' => (float)$loc['lng'],
                'note' => $loc['note'] ?? ''
            ];
        }

        // Return structured trip object exactly matching JS expectations
        return [
            'id' => (string)$tripId,
            'code' => $tripRow['code'],
            'name' => $tripRow['name'],
            'createdAt' => strtotime($tripRow['created_at']) * 1000,
            'members' => $members,
            'itinerary' => $itinerary,
            'expenses' => $expenses,
            'checklist' => $checklist,
            'locations' => $locations
        ];
    }

    public static function create($code, $name) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO trips (code, name) VALUES (?, ?)");
        $stmt->execute([$code, $name]);
        return $db->lastInsertId();
    }

    public static function addMember($tripId, $name) {
        $db = Database::getConnection();
        // Ignore if member already exists
        $stmt = $db->prepare("INSERT IGNORE INTO trip_members (trip_id, name) VALUES (?, ?)");
        return $stmt->execute([$tripId, $name]);
    }

    // Record that a logged-in user has joined/created a trip (used to populate
    // the sidebar's "Nhóm của bạn" list with the user's real trips).
    public static function linkUserToTrip($userId, $tripId) {
        if (!$userId || !$tripId) {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT IGNORE INTO user_trips (user_id, trip_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $tripId]);
    }

    // Fetch the list of trips a given user belongs to (most recently joined first),
    // for sidebar display. Returns lightweight rows: id, code, name, member_count.
    public static function getTripsForUser($userId) {
        if (!$userId) {
            return [];
        }
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT t.id, t.code, t.name,
                    (SELECT COUNT(*) FROM trip_members tm WHERE tm.trip_id = t.id) AS member_count
             FROM trips t
             INNER JOIN user_trips ut ON ut.trip_id = t.id
             WHERE ut.user_id = ?
             ORDER BY ut.joined_at DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        // If the user has no linked trips yet, backfill legacy membership rows
        // from trip_members and try one more time.
        if (empty($rows)) {
            $userStmt = $db->prepare("SELECT fullname, username FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([$userId]);
            $userRow = $userStmt->fetch();
            if ($userRow) {
                self::syncUserTripsByMemberName($userId, $userRow['fullname'] ?? '');
                self::syncUserTripsByMemberName($userId, $userRow['username'] ?? '');
            }

            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
        }

        $trips = [];
        foreach ($rows as $row) {
            $trips[] = [
                'id' => (string)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'memberCount' => (int)$row['member_count'],
            ];
        }
        return $trips;
    }

    // Pick one trip for a user so login can land them on a real trip page.
    public static function getDefaultTripForUser($userId) {
        $trips = self::getTripsForUser($userId);
        if (empty($trips)) {
            return null;
        }
        shuffle($trips);
        return $trips[0];
    }

    private static function normalizeLookupValue($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value);
        return $value;
    }

    // Backfill user_trips from legacy trip_members rows where the member name
    // matches the user's display name. This helps old accounts recover their
    // joined trips after logout/login.
    public static function syncUserTripsByMemberName($userId, $memberName) {
        if (!$userId || !$memberName) {
            return 0;
        }

        $db = Database::getConnection();
        $stmt = $db->query("SELECT DISTINCT trip_id, name FROM trip_members");
        $rows = $stmt ? $stmt->fetchAll() : [];

        $targets = [
            self::normalizeLookupValue($memberName),
        ];

        $userStmt = $db->prepare("SELECT fullname, username FROM users WHERE id = ? LIMIT 1");
        $userStmt->execute([$userId]);
        $userRow = $userStmt->fetch();
        if ($userRow) {
            $targets[] = self::normalizeLookupValue($userRow['fullname'] ?? '');
            $targets[] = self::normalizeLookupValue($userRow['username'] ?? '');
        }

        $targets = array_values(array_filter(array_unique($targets)));
        if (empty($targets)) {
            return 0;
        }

        $tripIds = [];
        foreach ($rows as $row) {
            $normalizedName = self::normalizeLookupValue($row['name'] ?? '');
            if ($normalizedName !== '' && in_array($normalizedName, $targets, true)) {
                $tripIds[] = $row['trip_id'];
            }
        }

        $tripIds = array_values(array_unique($tripIds));

        $count = 0;
        foreach ($tripIds as $tripId) {
            if (self::linkUserToTrip($userId, $tripId)) {
                $count++;
            }
        }

        return $count;
    }

    public static function removeMember($tripId, $name) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM trip_members WHERE trip_id = ? AND name = ?");
        return $stmt->execute([$tripId, $name]);
    }

    public static function addDay($tripId, $date, $label) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO itinerary_days (trip_id, day_date, label) VALUES (?, ?, ?)");
        $stmt->execute([$tripId, $date, $label]);
        return $db->lastInsertId();
    }

    public static function deleteDay($tripId, $dayId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM itinerary_days WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $dayId]);
    }

    public static function addActivity($dayId, $time, $title, $location, $note, $lat = null, $lng = null) {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO itinerary_items (day_id, item_time, title, location, lat, lng, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $dayId,
            $time ?: null,
            $title,
            $location ?: null,
            $lat !== null && $lat !== '' ? $lat : null,
            $lng !== null && $lng !== '' ? $lng : null,
            $note ?: null
        ]);
        return $db->lastInsertId();
    }

    public static function deleteActivity($dayId, $activityId) {
        $db = Database::getConnection();
        // Secure check to ensure the item belongs to the day
        $stmt = $db->prepare("DELETE FROM itinerary_items WHERE day_id = ? AND id = ?");
        return $stmt->execute([$dayId, $activityId]);
    }

    public static function addLocation($tripId, $name, $lat, $lng, $note = '') {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO saved_locations (trip_id, name, lat, lng, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tripId, $name, $lat, $lng, $note]);
        return $db->lastInsertId();
    }

    public static function deleteLocation($tripId, $locId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM saved_locations WHERE trip_id = ? AND id = ?");
        return $stmt->execute([$tripId, $locId]);
    }

    public static function updateLocationNote($locId, $note) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE saved_locations SET note = ? WHERE id = ?");
        return $stmt->execute([$note, $locId]);
    }
}
