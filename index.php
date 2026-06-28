<?php
// Start Session
session_start();

require_once __DIR__ . '/Config/Env.php';
\Config\Env::load(__DIR__ . '/.env');
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Sync database schema on boot so schema.sql changes can be applied without
// manually rerunning imports in development.
try {
    $syncEnabled = \Config\Env::get('AUTO_SCHEMA_SYNC', '1');
    if ((string)$syncEnabled !== '0') {
        $pdo = \Config\Database::getConnection();
        \Config\SchemaSync::syncFromFile($pdo, __DIR__ . '/schema.sql');
    }
} catch (\Throwable $e) {
    error_log('[SchemaSync] ' . $e->getMessage());
}

// 1. Class Autoloader
spl_autoload_register(function ($class) {
    // Replace namespace separator with directory separator
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $classPath . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Routing Setup
$route = $_GET['route'] ?? '';

// Helper function to output JSON directly
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Session Authentication Filter
// If user has not logged in, they must be redirected to home,
// except for public auth/trip lookup endpoints.
$publicRoutes = ['', 'home', 'register', 'api/login', 'api/register', 'api/trip', 'api/trip/create', 'api/trip/add-member', 'api/session-trip'];
$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn && !in_array($route, $publicRoutes)) {
    // If not logged in, force redirect to home
    header('Location: index.php?route=home');
    exit;
}

// If already logged in and going to a guest auth page, redirect to group dashboard
if ($isLoggedIn && ($route === '' || $route === 'home' || $route === 'register')) {
    header('Location: index.php?route=group');
    exit;
}

// Allow a direct trip code handoff in the URL as a fallback, so the group
// page can recover even if the session-trip fetch is flaky.
if ($isLoggedIn && isset($_GET['trip']) && $_GET['trip'] !== '') {
    $tripCode = trim((string)$_GET['trip']);
    $trip = \Models\Trip::getByCode($tripCode);
    if ($trip) {
        $_SESSION['trip_code'] = $trip['code'];
        $_SESSION['trip_name'] = $trip['name'];
    }
}

// If the user is logged in but there is no active trip in session, pick one
// of their trips so the app always opens on a real trip dashboard.
if ($isLoggedIn && empty($_SESSION['trip_code'])) {
    $defaultTrip = \Models\Trip::getDefaultTripForUser($_SESSION['user_id']);
    if ($defaultTrip) {
        $_SESSION['trip_code'] = $defaultTrip['code'];
        $_SESSION['trip_name'] = $defaultTrip['name'];
    }
}

// 4. Front Controller Routing Logic
switch ($route) {
    case '':
    case 'home':
        $pageTitle = "Đăng nhập — Lập kế hoạch du lịch";
        include 'views/home.php';
        break;

    case 'register':
        $pageTitle = "Đăng ký tài khoản — Lập kế hoạch du lịch";
        include 'views/register.php';
        break;

    case 'group':
    case 'itinerary':
        $pageTitle = "Lịch trình";
        $pageSection = 'itinerary';
        include 'views/group.php';
        break;

    case 'budget':
        $pageTitle = "Chi phí";
        $pageSection = 'budget';
        include 'views/group.php';
        break;

    case 'checklist':
        $pageTitle = "Checklist";
        $pageSection = 'checklist';
        include 'views/group.php';
        break;

    case 'map':
        $pageTitle = "Bản đồ";
        $pageSection = 'map';
        include 'views/group.php';
        break;

    case 'advice':
        $controller = new Controllers\ChatController();
        $controller->showChatbot();
        break;


    case 'personal':
        // The personal-checklist page has been merged into the profile page
        // in the new Matcha design; redirect old links/bookmarks there.
        header('Location: index.php?route=profile');
        exit;

    case 'profile':
        $controller = new Controllers\UserController();
        $controller->showProfile();
        break;

    case 'transport':
        $controller = new Controllers\TransportController();
        $controller->showTransport();
        break;

    case 'wheel':
        $controller = new Controllers\WheelController();
        $controller->showWheel();
        break;

    case 'logout':
        // Clear the authenticated session completely so the app returns to login
        // instead of landing back on the group screen.
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['fullname']);
        unset($_SESSION['trip_code']);
        unset($_SESSION['trip_name']);
        header('Location: index.php?route=home');
        exit;

    // --- API Session Routes ---
    case 'api/session-trip':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $_SESSION['trip_code'] = trim((string)($input['trip_code'] ?? ''));
        $_SESSION['trip_name'] = trim((string)($input['trip_name'] ?? ''));

        if (isset($_SESSION['user_id']) && $_SESSION['trip_code'] !== '') {
            $trip = \Models\Trip::getByCode($_SESSION['trip_code']);
            if ($trip) {
                \Models\Trip::linkUserToTrip($_SESSION['user_id'], $trip['id']);
            }
        }
        jsonResponse(['success' => true]);
        break;

    // --- API User Routes ---
    case 'api/login':
        $controller = new Controllers\UserController();
        $controller->loginOrRegister();
        break;

    case 'api/register':
        $controller = new Controllers\UserController();
        $controller->registerAccount();
        break;

    case 'api/profile/update':
        $controller = new Controllers\UserController();
        $controller->updateProfile();
        break;

    case 'api/chatbot/send':
        $controller = new Controllers\ChatController();
        $controller->sendMessage();
        break;

    case 'api/chatbot/reset':
        $controller = new Controllers\ChatController();
        $controller->resetChat();
        break;

    case 'api/chatbot/test':
        $controller = new Controllers\ChatController();
        $controller->testGemini();
        break;


    // --- API Trip & Itinerary Routes ---
    case 'api/trip':
        $controller = new Controllers\TripController();
        $controller->getTrip();
        break;

    case 'api/trip/create':
        $controller = new Controllers\TripController();
        $controller->createTrip();
        break;

    case 'api/trip/add-member':
        $controller = new Controllers\TripController();
        $controller->addMember();
        break;

    case 'api/trip/my-trips':
        $controller = new Controllers\TripController();
        $controller->getMyTrips();
        break;

    case 'api/trip/remove-member':
        $controller = new Controllers\TripController();
        $controller->removeMember();
        break;

    case 'api/trip/add-day':
        $controller = new Controllers\TripController();
        $controller->addDay();
        break;

    case 'api/trip/delete-day':
        $controller = new Controllers\TripController();
        $controller->deleteDay();
        break;

    case 'api/trip/add-activity':
        $controller = new Controllers\TripController();
        $controller->addActivity();
        break;

    case 'api/trip/delete-activity':
        $controller = new Controllers\TripController();
        $controller->deleteActivity();
        break;

    // --- API Expense Routes ---
    case 'api/trip/add-expense':
        $controller = new Controllers\TripController();
        $controller->addExpense();
        break;

    case 'api/trip/delete-expense':
        $controller = new Controllers\TripController();
        $controller->deleteExpense();
        break;

    // --- API Checklist Routes ---
    case 'api/trip/checklist/add':
        $controller = new Controllers\TripController();
        $controller->addChecklist();
        break;

    case 'api/trip/checklist/delete':
        $controller = new Controllers\TripController();
        $controller->deleteChecklist();
        break;

    case 'api/trip/checklist/toggle':
        $controller = new Controllers\TripController();
        $controller->toggleChecklist();
        break;

    case 'api/trip/checklist/personal':
        $controller = new Controllers\TripController();
        $controller->getPersonalChecklist();
        break;

    // --- API Location Routes ---
    case 'api/trip/location/add':
        $controller = new Controllers\TripController();
        $controller->addLocation();
        break;

    case 'api/trip/location/delete':
        $controller = new Controllers\TripController();
        $controller->deleteLocation();
        break;

    case 'api/trip/location/update-note':
        $controller = new Controllers\TripController();
        $controller->updateLocationNote();
        break;

    // --- API Transport Routes ---
    case 'api/transport':
        $controller = new Controllers\TransportController();
        $controller->getTransports();
        break;

    case 'api/transport/add':
        $controller = new Controllers\TransportController();
        $controller->addTransport();
        break;

    case 'api/transport/delete':
        $controller = new Controllers\TransportController();
        $controller->deleteTransport();
        break;

    // --- API Random Wheel Routes ---
    case 'api/wheel':
        $controller = new Controllers\WheelController();
        $controller->getOptions();
        break;

    case 'api/wheel/add':
        $controller = new Controllers\WheelController();
        $controller->addOption();
        break;

    case 'api/wheel/delete':
        $controller = new Controllers\WheelController();
        $controller->deleteOption();
        break;

    case 'api/wheel/clear':
        $controller = new Controllers\WheelController();
        $controller->clearOptions();
        break;

    default:
        // Page not found
        header("HTTP/1.0 404 Not Found");
        echo "<h1>404 Không Tìm Thấy</h1>";
        echo "<p>Trang bạn yêu cầu không tồn tại.</p>";
        break;
}
