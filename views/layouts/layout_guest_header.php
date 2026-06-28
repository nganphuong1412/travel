<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#53613b">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Đi Đâu' : 'Đi Đâu — Lên kế hoạch du lịch'; ?></title>

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "primary": "#53613b", "matcha": "#53613b", "matcha-dark": "#76845b",
        "espresso": "#321300", "on-primary": "#ffffff",
        "primary-fixed": "#d9e9b8", "outline-variant": "#c6c8bb",
        "on-surface-variant": "#45483e", "surface": "#f6faff",
        "surface-container-low": "#eef4fc", "on-surface": "#161c21"
      },
      borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
      fontFamily: {
        "label-md": ["Inter"], "label-lg": ["Inter"], "display-lg": ["Space Grotesk"],
        "headline-md": ["Space Grotesk"], "body-md": ["Inter"], "body-lg": ["Inter"]
      },
      fontSize: {
        "label-md": ["12px", {"lineHeight":"1.2", "fontWeight":"500"}],
        "label-lg": ["14px", {"lineHeight":"1.2", "letterSpacing":"0.05em", "fontWeight":"600"}],
        "display-lg": ["44px", {"lineHeight":"1.1", "letterSpacing":"-0.02em", "fontWeight":"700"}],
        "headline-md": ["22px", {"lineHeight":"1.4", "fontWeight":"600"}],
        "body-lg": ["16px", {"lineHeight":"1.6", "fontWeight":"400"}],
        "body-md": ["14px", {"lineHeight":"1.5", "fontWeight":"400"}]
      }
    }
  }
}
</script>
<style>
  body { background-color: #f6faff; color: #161c21; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
  .toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
    background: #161c21; color: #fff; font-size: 13px; padding: 10px 18px; border-radius: 20px;
    opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 9999;
  }
  .toast.show { opacity: 1; }
</style>
</head>
<body class="font-body-md text-body-md bg-surface min-h-screen flex flex-col items-center justify-center p-6">
