<?php
// ============================================================
// Layout Header â€” Matcha Theme (sidebar + topbar), data-driven.
// Included by every authenticated view. Expects $pageTitle and
// an active PHP session with username/trip_code already set.
// Paired with layout_footer.php which closes <main>/<body>/<html>.
// ============================================================
use Models\Trip;

$currentRoute = $_GET['route'] ?? 'group';

// Pull real data for the sidebar: current trip + member count.
$__layoutTrip = null;
$__layoutMyTrips = [];
if (isset($_SESSION['trip_code'])) {
    $__layoutTrip = Trip::getByCode($_SESSION['trip_code']);
}
if (isset($_SESSION['user_id'])) {
    $__layoutMyTrips = Trip::getTripsForUser($_SESSION['user_id']);
}

$__memberCount = $__layoutTrip ? count($__layoutTrip['members']) : 0;
$__fullname = $_SESSION['fullname'] ?? 'KhÃ¡ch';

// Initials for the avatar fallback (e.g. "LÃª Minh QuÃ¢n" -> "LQ")
if (!function_exists('didau_initials')) {
    function didau_initials($name) {
        $parts = preg_split('/\s+/', trim($name));
        $parts = array_filter($parts);
        if (empty($parts)) return '?';

        // Prefer mbstring for correct UTF-8 handling (Vietnamese names),
        // but fall back gracefully if the extension isn't enabled so this
        // never causes a fatal error on minimal PHP installs.
        $hasMb = function_exists('mb_substr');
        $take1 = function ($s) use ($hasMb) {
            return $hasMb ? mb_substr($s, 0, 1, 'UTF-8') : substr($s, 0, 1);
        };
        $upper = function ($s) use ($hasMb) {
            return $hasMb ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
        };

        if (count($parts) === 1) return $upper($take1(reset($parts)));
        $first = $take1(reset($parts));
        $last = $take1(end($parts));
        return $upper($first . $last);
    }
}

$navItems = [
    ['route' => 'itinerary', 'icon' => 'calendar_today',  'label' => 'Lịch trình'],
    ['route' => 'budget',    'icon' => 'payments',        'label' => 'Chi phí'],
    ['route' => 'checklist', 'icon' => 'fact_check',      'label' => 'Checklist'],
    ['route' => 'map',       'icon' => 'map',             'label' => 'Bản đồ'],
    ['route' => 'transport', 'icon' => 'directions_bus',  'label' => 'Tra cứu'],
    ['route' => 'advice',    'icon' => 'psychology',      'label' => 'Tư vấn'],
    ['route' => 'wheel',     'icon' => 'casino',          'label' => 'Random Wheel'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#53613b">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' â€” Äi ÄÃ¢u' : 'Äi ÄÃ¢u â€” LÃªn káº¿ hoáº¡ch du lá»‹ch'; ?></title>

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<!-- Leaflet (used by the map tab on the group dashboard) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "on-tertiary": "#ffffff", "tertiary-fixed-dim": "#fcb78e", "secondary-fixed": "#e9e3c3",
        "surface-variant": "#dde3ea", "primary-container": "#6c7a51", "on-background": "#161c21",
        "primary-fixed-dim": "#bdcc9e", "on-primary-fixed": "#141f02", "inverse-primary": "#bdcc9e",
        "tertiary": "#834f2e", "tertiary-container": "#9f6744", "on-surface-variant": "#45483e",
        "error": "#ba1a1a", "surface-container": "#e9eef6", "secondary-container": "#e6e1c0",
        "on-primary": "#ffffff", "inverse-surface": "#2b3137", "on-secondary-container": "#666349",
        "secondary": "#625f45", "primary-fixed": "#d9e9b8", "background": "#f6faff",
        "surface-container-lowest": "#ffffff", "on-secondary": "#ffffff", "surface": "#f6faff",
        "surface-dim": "#d5dbe2", "on-primary-fixed-variant": "#3f4b27", "surface-container-low": "#eef4fc",
        "on-tertiary-container": "#fffbff", "tertiary-fixed": "#ffdbc8", "surface-container-high": "#e3e9f0",
        "on-surface": "#161c21", "on-secondary-fixed-variant": "#4a482f", "error-container": "#ffdad6",
        "on-secondary-fixed": "#1e1c08", "outline": "#76786d", "on-error-container": "#93000a",
        "surface-tint": "#56633d", "surface-bright": "#f6faff", "secondary-fixed-dim": "#ccc7a8",
        "on-tertiary-fixed": "#321300", "primary": "#53613b", "outline-variant": "#c6c8bb",
        "on-primary-container": "#faffe8", "on-tertiary-fixed-variant": "#6a3b1b",
        "surface-container-highest": "#dde3ea", "on-error": "#ffffff", "inverse-on-surface": "#ecf1f9",
        "matcha": "#53613b", "matcha-dark": "#76845b", "espresso": "#321300", "oat-milk": "#D8D3B3"
      },
      borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
      spacing: {
        "margin-desktop": "80px", xs: "4px", base: "8px", sm: "12px", md: "24px", lg: "40px",
        xl: "64px", gutter: "24px", "margin-mobile": "16px", "sidebar-width": "280px",
        "stack-lg": "32px", "stack-md": "16px", "stack-sm": "8px", "container-max": "1440px"
      },
      fontFamily: {
        "label-md": ["Inter"], "label-lg": ["Inter"], "display-md": ["Space Grotesk"],
        "headline-lg": ["Space Grotesk"], "body-lg": ["Inter"], "display-lg": ["Space Grotesk"],
        "headline-md": ["Space Grotesk"], "body-md": ["Inter"], "data-mono": ["JetBrains Mono"]
      },
      fontSize: {
        "label-md": ["12px", {"lineHeight":"1.2", "fontWeight":"500"}],
        "label-lg": ["14px", {"lineHeight":"1.2", "letterSpacing":"0.05em", "fontWeight":"600"}],
        "display-md": ["36px", {"lineHeight":"1.2", "letterSpacing":"-0.01em", "fontWeight":"600"}],
        "headline-lg": ["32px", {"lineHeight":"1.3", "fontWeight":"600"}],
        "body-lg": ["18px", {"lineHeight":"1.6", "fontWeight":"400"}],
        "display-lg": ["48px", {"lineHeight":"1.1", "letterSpacing":"-0.02em", "fontWeight":"700"}],
        "headline-md": ["24px", {"lineHeight":"1.4", "fontWeight":"500"}],
        "body-md": ["16px", {"lineHeight":"1.5", "fontWeight":"400"}]
      }
    }
  }
}
</script>
<style>
  body { background-color: #f6faff; color: #161c21; }
  html { -webkit-text-size-adjust: 100%; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
  .custom-scrollbar::-webkit-scrollbar { width: 4px; }
  .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
  .custom-scrollbar::-webkit-scrollbar-thumb { background: #c6c8bb; border-radius: 10px; }
  .mobile-sidebar-toggle { display: none; }
  .toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: #161c21; color: #fff; font-size: 13px; padding: 10px 18px; border-radius: 20px;
    opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 9999;
  }
  .toast.show { opacity: 1; }
  @media (max-width: 1024px) {
    .mobile-sidebar-toggle { display: inline-flex; }
    aside[data-sidebar] {
      transform: translateX(-100%);
      transition: transform .22s ease;
      width: min(86vw, 320px);
      z-index: 60;
    }
    body.sidebar-open aside[data-sidebar] { transform: translateX(0); }
    body.sidebar-open::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.35);
      z-index: 50;
    }
    header[data-topbar] { left: 0 !important; padding-left: 16px; padding-right: 16px; }
    main#content { margin-left: 0 !important; padding: 16px !important; }
  }
  @media (max-width: 640px) {
    header[data-topbar] { min-height: 64px; height: auto; }
    header[data-topbar] .font-display-lg { font-size: 22px; }
    .modal-card { width: min(100%, 420px); }
  }
</style>
</head>
<body class="font-body-md text-body-md overflow-x-hidden bg-surface">

<!-- Side Navigation Bar -->
<aside data-sidebar class="w-sidebar-width h-screen fixed left-0 top-0 border-r border-white/10 flex flex-col p-md z-30 bg-matcha-dark">
  <div class="mb-lg">
    <h1 class="font-display-lg text-headline-md font-bold mb-1 text-white truncate">
      <?= htmlspecialchars($__layoutTrip['name'] ?? 'Nhóm Đi Đâu') ?>
    </h1>
    <p class="font-label-md text-white/70"><?= $__memberCount ?> thành viên</p>
  </div>

  <nav class="flex-1 space-y-2 overflow-y-auto custom-scrollbar pr-2">
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = $currentRoute === $item['route']; ?>
      <a href="index.php?route=<?= $item['route'] ?>"
         class="flex items-center gap-3 p-3 rounded-lg transition-colors group <?= $isActive ? 'bg-white/20 text-white' : 'text-white/80 hover:bg-white/10' ?>">
        <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
        <span class="font-label-lg"><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="pt-md border-t border-white/10 mt-md">
    <button type="button" onclick="openTripModal()" class="w-full flex items-center justify-center gap-2 p-3 mb-md border-2 border-dashed border-white/30 text-white font-bold rounded-lg hover:bg-white/5 transition-all">
      <span class="material-symbols-outlined">add</span>
      <span class="font-label-lg uppercase text-[12px]">Thêm / đổi nhóm</span>
    </button>
    <div class="mt-md relative">
      <a href="index.php?route=profile" class="flex items-center gap-3 p-2 pr-12 bg-white/10 rounded-lg border border-white/10 hover:bg-white/15 transition-all group">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold bg-white/20 border border-white/20 flex-shrink-0 text-[13px]">
          <?= htmlspecialchars(didau_initials($__fullname)) ?>
        </div>
        <div class="overflow-hidden min-w-0">
          <p class="font-label-lg text-white truncate"><?= htmlspecialchars($__fullname) ?></p>
          <p class="text-[11px] text-white/60 truncate font-data-mono"><?= isset($_SESSION['trip_code']) ? htmlspecialchars($_SESSION['trip_code']) : '' ?></p>
        </div>
      </a>
      <a href="index.php?route=logout" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center bg-white/10 border border-white/10 text-white/90 hover:bg-white/20 hover:text-white transition-all" title="Đăng xuất" aria-label="Đăng xuất">
        <span class="material-symbols-outlined text-[18px]">logout</span>
      </a>
    </div>
  </div>
</aside>

<!-- Top App Bar -->
<header data-topbar class="h-16 fixed top-0 right-0 left-[280px] border-b border-outline-variant/30 z-20 flex justify-between items-center px-margin-desktop shadow-sm bg-surface">
  <div class="flex items-center gap-md">
    <button type="button" class="mobile-sidebar-toggle p-2 rounded-lg border border-outline-variant text-on-surface-variant" onclick="toggleSidebar()">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <h2 class="font-display-lg text-headline-md font-bold text-primary"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Äi ÄÃ¢u' ?></h2>
  </div>
  <div class="flex items-center gap-3 relative">
    <?php if (isset($_SESSION['trip_code'])): ?>
    <div class="relative">
      <button type="button" onclick="toggleTripDropdown()" class="hidden md:flex items-center gap-2 font-data-mono text-[12px] bg-surface-container-low border border-outline-variant rounded-full px-3 py-1.5 text-on-surface-variant hover:border-matcha transition-colors">
        <span id="header-trip-code"><?= htmlspecialchars($_SESSION['trip_code']) ?></span>
        <span class="material-symbols-outlined text-[16px]">keyboard_arrow_down</span>
      </button>
      <div id="trip-dropdown" class="hidden absolute right-0 mt-2 w-64 bg-white border border-outline-variant rounded-xl shadow-xl overflow-hidden z-50">
        <div class="px-4 py-3 border-b border-outline-variant bg-surface-container-lowest">
          <p class="font-label-md text-on-surface-variant uppercase tracking-wider mb-1">Đang ở nhóm</p>
          <p class="font-headline-md text-[15px] text-on-surface truncate"><?= htmlspecialchars($__layoutTrip['name'] ?? '') ?></p>
        </div>
        <div class="max-h-56 overflow-y-auto custom-scrollbar">
          <?php if (!empty($__layoutMyTrips)): ?>
            <?php foreach ($__layoutMyTrips as $t): ?>
              <?php $isCurrentTrip = isset($_SESSION['trip_code']) && $_SESSION['trip_code'] === $t['code']; ?>
              <button type="button" onclick="switchToTrip('<?= htmlspecialchars($t['code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>')"
                class="w-full text-left px-4 py-3 flex items-center justify-between gap-3 hover:bg-surface-container-low <?= $isCurrentTrip ? 'bg-primary-fixed/40' : '' ?>">
                <span class="flex items-center gap-2 min-w-0">
                  <span class="material-symbols-outlined text-[16px] text-matcha">explore</span>
                  <span class="font-label-md text-on-surface truncate"><?= htmlspecialchars($t['name']) ?></span>
                </span>
                <span class="text-[11px] text-on-surface-variant font-data-mono"><?= htmlspecialchars($t['code']) ?></span>
              </button>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="px-4 py-4 text-[13px] text-on-surface-variant">Chưa có nhóm nào.</div>
          <?php endif; ?>
        </div>
        <div class="p-3 border-t border-outline-variant bg-surface-container-lowest">
          <button type="button" onclick="openTripModal()" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg border border-dashed border-outline-variant text-matcha hover:bg-matcha/5 transition-colors">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Thêm / đổi nhóm
          </button>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</header>

<!-- Main Content Area -->
<main class="ml-sidebar-width mt-16 p-margin-desktop min-h-screen" id="content">

<!-- Trip Modal -->
<div id="trip-modal" class="fixed inset-0 bg-black/40 z-50 hidden items-center justify-center p-4">
  <div class="bg-white w-full max-w-md rounded-2xl border border-outline-variant shadow-2xl overflow-hidden modal-card">
    <div class="p-5 border-b border-outline-variant bg-surface-container-lowest">
      <h3 class="font-headline-md text-[20px] text-on-surface">Thêm / đổi nhóm</h3>
      <p class="text-[13px] text-on-surface-variant mt-1">Nhập mã nhóm để chuyển sang nhóm có sẵn, hoặc tạo nhóm mới nếu mã chưa tồn tại.</p>
    </div>
    <div class="p-5 space-y-4">
      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Mã nhóm</label>
        <input id="trip-code-input" placeholder="VD: hp" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-surface-container-low focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all">
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Tên nhóm mới (nếu tạo mới)</label>
        <input id="trip-name-input" placeholder="VD: Háº£i PhÃ²ng" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-surface-container-low focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all">
      </div>
    </div>
    <div class="p-5 pt-0 flex gap-3 justify-end">
      <button type="button" onclick="closeTripModal()" class="px-4 py-2.5 rounded-lg border border-outline-variant text-on-surface-variant">Hủy</button>
      <button type="button" onclick="submitTripModal()" class="px-4 py-2.5 rounded-lg bg-matcha text-white">Xác nhận</button>
    </div>
  </div>
</div>

