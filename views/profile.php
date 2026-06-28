<?php
use Models\Trip;
include __DIR__ . '/layouts/layout_header.php';

// $user is provided by UserController::showProfile()
$fullname = $user['fullname'] ?? ($_SESSION['fullname'] ?? 'Khách');
$username = $user['username'] ?? ($_SESSION['username'] ?? '');
$joinedAt = !empty($user['created_at']) ? date('m/Y', strtotime($user['created_at'])) : '';

// Real stats: how many trips this user has joined, and members in current trip
$myTrips = isset($_SESSION['user_id']) ? Trip::getTripsForUser($_SESSION['user_id']) : [];
$tripCount = count($myTrips);
$currentTrip = isset($_SESSION['trip_code']) ? Trip::getByCode($_SESSION['trip_code']) : null;
$memberCount = $currentTrip ? count($currentTrip['members']) : 0;
?>

<div class="max-w-[1100px] mx-auto">
  <!-- Profile Hero Section -->
  <section class="grid grid-cols-12 gap-gutter mb-lg">
    <!-- User Card -->
    <div class="col-span-12 lg:col-span-4 bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden relative shadow-sm">
      <div class="h-32 bg-matcha relative">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;"></div>
      </div>
      <div class="px-8 pb-8 -mt-16 relative text-center">
        <div class="w-32 h-32 rounded-full border-4 border-surface-container-lowest mx-auto mb-4 bg-matcha flex items-center justify-center text-white font-display-lg text-[36px] font-bold shadow-lg">
          <?= htmlspecialchars(didau_initials($fullname)) ?>
        </div>
        <h3 class="font-headline-md text-headline-md text-on-surface mb-1"><?= htmlspecialchars($fullname) ?></h3>
        <p class="text-on-surface-variant mb-4">@<?= htmlspecialchars($username) ?><?= $joinedAt ? ' • Tham gia ' . htmlspecialchars($joinedAt) : '' ?></p>
        <div class="flex justify-center gap-2 mb-6">
          <span class="px-3 py-1 bg-primary-fixed text-matcha font-label-md rounded-full border border-matcha/10 uppercase">Đi Đâu Explorer</span>
        </div>
      </div>
    </div>

    <!-- Stats Bento -->
    <div class="col-span-12 lg:col-span-8 grid grid-cols-3 gap-gutter">
      <div class="col-span-3 md:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col justify-center items-center text-center shadow-sm">
        <span class="material-symbols-outlined text-matcha text-[40px] mb-2" style="font-variation-settings: 'FILL' 1;">explore</span>
        <p class="font-display-lg text-headline-lg text-espresso"><?= $tripCount ?></p>
        <p class="font-label-lg text-on-surface-variant">Chuyến đi đã tham gia</p>
      </div>
      <div class="col-span-3 md:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col justify-center items-center text-center shadow-sm">
        <span class="material-symbols-outlined text-matcha text-[40px] mb-2" style="font-variation-settings: 'FILL' 1;">group</span>
        <p class="font-display-lg text-headline-lg text-espresso"><?= $memberCount ?></p>
        <p class="font-label-lg text-on-surface-variant">Thành viên nhóm hiện tại</p>
      </div>
      <div class="col-span-3 md:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col justify-center items-center text-center shadow-sm">
        <span class="material-symbols-outlined text-matcha text-[40px] mb-2" style="font-variation-settings: 'FILL' 1;">stars</span>
        <p class="font-display-lg text-headline-lg text-espresso"><?= htmlspecialchars($currentTrip['code'] ?? '—') ?></p>
        <p class="font-label-lg text-on-surface-variant">Mã chuyến đi hiện tại</p>
      </div>

      <!-- Edit Profile Form -->
      <div class="col-span-3 bg-white border border-outline-variant rounded-xl p-8 shadow-sm">
        <h4 class="font-label-lg text-matcha mb-4 uppercase tracking-wider">Chỉnh sửa hồ sơ</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <label class="font-label-md text-on-surface-variant uppercase">Tên hiển thị</label>
            <input id="prof-fullname" value="<?= htmlspecialchars($fullname) ?>" class="px-3 py-2.5 border border-outline-variant rounded-lg text-[14px] focus:border-matcha focus:ring-1 focus:ring-matcha outline-none">
          </div>
          <div class="flex flex-col gap-1">
            <label class="font-label-md text-on-surface-variant uppercase">Mật khẩu mới (để trống nếu không đổi)</label>
            <input type="password" id="prof-password" placeholder="••••••••" class="px-3 py-2.5 border border-outline-variant rounded-lg text-[14px] focus:border-matcha focus:ring-1 focus:ring-matcha outline-none">
          </div>
        </div>
        <button onclick="updateProfile()" class="mt-4 px-6 py-2.5 bg-matcha text-white font-label-lg rounded-lg hover:opacity-90 transition-all active:scale-95 shadow-md uppercase tracking-wider text-[13px]">Lưu thay đổi</button>
      </div>
    </div>
  </section>

  <!-- Bottom Content -->
  <section class="grid grid-cols-12 gap-gutter">
    <!-- My Trips List -->
    <div class="col-span-12 lg:col-span-8">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-headline-md text-headline-md text-on-surface">Các chuyến đi của bạn</h3>
      </div>
      <div class="space-y-gutter" id="my-trips-list">
        <?php if (empty($myTrips)): ?>
          <div class="bg-surface-container-lowest border border-dashed border-outline-variant rounded-xl p-10 text-center text-on-surface-variant">
            Bạn chưa tham gia chuyến đi nào. <a href="index.php?route=home" class="text-matcha font-bold">Tạo / tham gia ngay</a>
          </div>
        <?php else: foreach ($myTrips as $t): ?>
          <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex items-center justify-between group cursor-pointer hover:shadow-md transition-all duration-300"
               onclick="switchToTrip('<?= htmlspecialchars($t['code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>')">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-lg bg-matcha/10 flex items-center justify-center text-matcha">
                <span class="material-symbols-outlined text-[28px]">explore</span>
              </div>
              <div>
                <h4 class="font-headline-md text-[17px] text-on-surface group-hover:text-matcha transition-colors"><?= htmlspecialchars($t['name']) ?></h4>
                <p class="text-body-md text-on-surface-variant font-data-mono text-[12px] mt-1">Mã: <?= htmlspecialchars($t['code']) ?> · <?= $t['memberCount'] ?> thành viên</p>
              </div>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-matcha transition-colors">chevron_right</span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Personal Checklist Sidebar -->
    <div class="col-span-12 lg:col-span-4">
      <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm h-full">
        <div class="bg-primary-fixed p-6 border-b-2 border-dashed border-outline-variant relative">
          <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-matcha">inventory</span>
            <h3 class="font-headline-md text-[18px] text-espresso font-bold uppercase tracking-wider">Checklist cá nhân</h3>
          </div>
          <p class="font-label-md text-on-surface-variant uppercase">Đồ dùng cá nhân của bạn</p>
          <div class="mt-3 flex items-center justify-between text-[12.5px]">
            <span class="text-on-surface-variant">Đã chuẩn bị</span>
            <span class="font-data-mono" id="personal-progress-text">0/0</span>
          </div>
          <div class="h-1.5 bg-white/50 rounded-full mt-1 overflow-hidden"><div class="h-full bg-matcha" id="personal-progress-bar" style="width:0%"></div></div>
        </div>
        <div class="p-6">
          <div class="space-y-4 mb-6" id="personal-checklist-items">
            <!-- Loaded dynamically -->
          </div>
          <div class="relative">
            <input id="new-personal-check" type="text" placeholder="Thêm món đồ mới..." onkeydown="if(event.key==='Enter')addPersonalCheckItem()"
              class="w-full pl-4 pr-12 py-3 border-2 border-dashed border-outline-variant rounded-lg bg-surface text-body-md focus:border-matcha focus:ring-0 outline-none transition-all">
            <button onclick="addPersonalCheckItem()" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-matcha hover:bg-matcha/5 rounded-md transition-colors">
              <span class="material-symbols-outlined">add_circle</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
/* ===================== PROFILE UPDATE ===================== */
async function updateProfile() {
    const fullname = document.getElementById('prof-fullname').value.trim();
    const password = document.getElementById('prof-password').value;

    if (!fullname) {
        toast('Tên hiển thị không được bỏ trống');
        return;
    }

    try {
        const res = await fetch('index.php?route=api/profile/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fullname: fullname, password: password })
        });
        const data = await res.json();
        if (res.ok) {
            toast('Đã cập nhật hồ sơ thành công!');
            setTimeout(() => window.location.reload(), 900);
        } else {
            toast(data.error || 'Cập nhật thất bại');
        }
    } catch(e) {
        console.error(e);
        toast('Lỗi kết nối cơ sở dữ liệu');
    }
}

/* ===================== PERSONAL CHECKLIST (real data via API) ===================== */
const ProfileState = {
    code: <?= json_encode($_SESSION['trip_code'] ?? '') ?>,
    username: <?= json_encode($_SESSION['username'] ?? '') ?>,
    tripId: <?= json_encode($currentTrip['id'] ?? null) ?>,
    items: []
};

async function loadPersonalChecklist() {
    if (!ProfileState.code || !ProfileState.username) return;
    try {
        const res = await fetch(`index.php?route=api/trip/checklist/personal&code=${encodeURIComponent(ProfileState.code)}&username=${encodeURIComponent(ProfileState.username)}`);
        if (res.ok) {
            ProfileState.items = await res.json();
            renderPersonalChecklist();
        }
    } catch(e) {
        console.error(e);
    }
}

function renderPersonalChecklist() {
    const items = ProfileState.items;
    const done = items.filter(i => i.checked).length;
    const pct = items.length ? Math.round(done / items.length * 100) : 0;

    document.getElementById('personal-progress-text').textContent = `${done}/${items.length}`;
    document.getElementById('personal-progress-bar').style.width = `${pct}%`;

    const container = document.getElementById('personal-checklist-items');
    if (items.length === 0) {
        container.innerHTML = `<p class="text-[13.5px] text-on-surface-variant">Chưa có món nào. Thêm món đầu tiên nhé.</p>`;
        return;
    }

    let html = '';
    items.forEach(it => {
        html += `<div class="flex items-center gap-3 group">
          <button onclick="togglePersonalCheck('${it.id}', ${!it.checked})" class="w-5 h-5 rounded border ${it.checked ? 'bg-matcha border-matcha text-white' : 'border-outline-variant'} flex items-center justify-center text-[12px] flex-shrink-0">
            ${it.checked ? '✓' : ''}
          </button>
          <span class="flex-1 text-[14px] ${it.checked ? 'line-through text-on-surface-variant' : ''}">${esc(it.text)}</span>
          <button onclick="deletePersonalCheck('${it.id}')" class="text-on-surface-variant opacity-0 group-hover:opacity-100 hover:text-error text-[16px]">✕</button>
        </div>`;
    });
    container.innerHTML = html;
}

async function addPersonalCheckItem() {
    const input = document.getElementById('new-personal-check');
    const v = input.value.trim();
    if (!v || !ProfileState.tripId) return;
    try {
        const res = await fetch('index.php?route=api/trip/checklist/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: ProfileState.tripId, text: v, username: ProfileState.username })
        });
        if (res.ok) {
            input.value = '';
            await loadPersonalChecklist();
        } else {
            toast('Lỗi khi thêm');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function togglePersonalCheck(id, checked) {
    try {
        const res = await fetch('index.php?route=api/trip/checklist/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: id, checked: checked })
        });
        if (res.ok) await loadPersonalChecklist();
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function deletePersonalCheck(id) {
    if (!ProfileState.tripId) return;
    try {
        const res = await fetch('index.php?route=api/trip/checklist/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: ProfileState.tripId, item_id: id })
        });
        if (res.ok) await loadPersonalChecklist();
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

loadPersonalChecklist();
</script>

<?php include __DIR__ . '/layouts/layout_footer.php'; ?>
