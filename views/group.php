<?php include __DIR__ . '/layouts/layout_header.php'; ?>

<?php $initialSection = $pageSection ?? 'itinerary'; ?>

<!-- Tab navigation pills -->
<div class="flex gap-2 mb-lg border-b border-outline-variant pb-0 flex-wrap hidden" id="tab-pills">
  <button class="tab-pill" data-tab="itinerary" onclick="setTab('itinerary')">
    <span class="material-symbols-outlined text-[18px]">calendar_today</span> Lịch trình
  </button>
  <button class="tab-pill" data-tab="budget" onclick="setTab('budget')">
    <span class="material-symbols-outlined text-[18px]">payments</span> Chi phí
  </button>
  <button class="tab-pill" data-tab="checklist" onclick="setTab('checklist')">
    <span class="material-symbols-outlined text-[18px]">fact_check</span> Checklist
  </button>
  <button class="tab-pill" data-tab="map" onclick="setTab('map')">
    <span class="material-symbols-outlined text-[18px]">map</span> Bản đồ
  </button>
</div>

<style>
  .tab-pill {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 18px; font-size: 14px; font-weight: 600; color: #45483e;
    border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all .15s;
  }
  .tab-pill:hover { color: #53613b; }
  .tab-pill.active { color: #53613b; border-bottom-color: #53613b; }
  #map { height: 320px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #c6c8bb; }
  .timeline-line { position: absolute; left: 23px; top: 32px; bottom: 32px; width: 2px; background: #c6c8bb; }
</style>

<div id="tab-content" class="max-w-[1100px] w-full"></div>

<script>
/* ===================== STATE ===================== */
const State = {
    code: <?= json_encode($_SESSION['trip_code'] ?? '') ?>,
    myName: <?= json_encode($_SESSION['fullname'] ?? '') ?>,
    trip: null,
    tab: <?= json_encode($initialSection) ?>,
    map: null,
    markers: [],
    searchResults: [],
    routeLayer: null,
    routeInfo: null,
    routeFrom: null,
    routeTo: null,
    routePanelOpen: false,
    mapFocus: null,
    mapDayIndex: 0
};
if (window.location.hash && ['itinerary','budget','checklist','map'].includes(window.location.hash.slice(1))) {
    State.tab = window.location.hash.slice(1);
}
if (!['itinerary','budget','checklist','map'].includes(State.tab)) State.tab = 'itinerary';

/* ===================== LOAD & REFRESH DATA ===================== */
async function loadTripData() {
    if (!State.code) {
        document.getElementById('tab-content').innerHTML = `<div class="bg-white border border-dashed border-outline-variant rounded-xl p-10 text-center text-on-surface-variant">
          Bạn chưa chọn nhóm nào. Hãy bấm <b>Thêm / đổi nhóm</b> ở header để nhập mã nhóm.
        </div>`;
        openTripModal();
        return false;
    }
    try {
        const res = await fetch(`index.php?route=api/trip&code=${encodeURIComponent(State.code)}`);
        if (res.ok) {
            State.trip = await res.json();
            return true;
        } else {
            toast('Không tải được dữ liệu chuyến đi');
            return false;
        }
    } catch (e) {
        console.error(e);
        toast('Lỗi kết nối cơ sở dữ liệu');
        return false;
    }
}

async function refreshAndRender() {
    const success = await loadTripData();
    if (success) setTab(State.tab);
}

if (!State.code) {
    openTripModal();
}

/* ===================== TAB CONTROL ===================== */
function setTab(tab) {
    State.tab = tab;
    history.replaceState(null, '', '#' + tab);
    document.querySelectorAll('.tab-pill').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));

    if (tab === 'itinerary') renderItinerary();
    if (tab === 'budget') renderBudget();
    if (tab === 'checklist') renderChecklist();
    if (tab === 'map') renderMapTab();
}

/* ===================== ITINERARY ===================== */
function renderItinerary() {
    const days = [...State.trip.itinerary].sort((a,b) => (a.date || '').localeCompare(b.date || ''));
    let html = `<div class="flex justify-between items-center mb-6">
      <h2 class="font-headline-md text-headline-md text-primary">Lịch trình chi tiết</h2>
      <button onclick="refreshAndRender()" class="text-matcha font-label-lg flex items-center gap-1 hover:underline">
        <span class="material-symbols-outlined text-[18px]">refresh</span> Làm mới
      </button>
    </div>`;

    if (days.length === 0) {
        html += `<div class="bg-surface-container-lowest border border-dashed border-outline-variant rounded-xl p-12 text-center text-on-surface-variant">
          Chưa có ngày nào trong lịch trình.<br>Thêm ngày đầu tiên của chuyến đi nhé.
        </div>`;
    }

    days.forEach((day, idx) => {
        const items = [...day.items].sort((a,b) => (a.time || '').localeCompare(b.time || ''));
        html += `<div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm mb-gutter">
          <div class="p-6 border-b border-outline-variant flex justify-between items-center bg-surface-container/30">
            <div>
              <h3 class="font-headline-md text-primary text-[17px]">Ngày ${idx+1}${day.label ? ' · ' + esc(day.label) : ''}</h3>
              <p class="font-label-md text-on-surface-variant mt-0.5">${fmtDate(day.date)}</p>
            </div>
            <button onclick="deleteDay('${day.id}')" class="text-error font-label-md hover:underline">Xóa ngày ✕</button>
          </div>
          <div class="p-6 relative">`;

        if (items.length > 0) {
            html += `<div class="timeline-line"></div><div class="space-y-6 relative">`;
            items.forEach((it, i) => {
                html += `<div class="flex gap-6 group">
                  <div class="w-4 h-4 rounded-full ${i === 0 ? 'bg-matcha ring-4 ring-matcha/10' : 'bg-outline-variant'} mt-1.5 z-10 flex-shrink-0"></div>
                  <div class="flex-1 bg-surface-container-low p-4 rounded-lg border border-outline-variant hover:border-matcha/30 transition-all">
                    <div class="flex justify-between mb-2 items-start">
                      <span class="font-data-mono text-matcha bg-primary-fixed px-2 py-0.5 rounded text-[12px] font-bold">${esc(it.time || '--:--')}</span>
                      <button onclick="deleteItem('${day.id}','${it.id}')" class="text-on-surface-variant opacity-0 group-hover:opacity-100 hover:text-error transition-all">✕</button>
                    </div>
                    <h4 class="font-label-lg text-[15px] mb-1 text-on-surface">${esc(it.title)}</h4>
                    ${it.location ? `<p class="text-body-md text-on-surface-variant text-[13px]">📍 ${esc(it.location)}</p>` : ''}
                    ${it.note ? `<p class="text-body-md text-on-surface-variant text-[13px] mt-1">${esc(it.note)}</p>` : ''}
                  </div>
                </div>`;
            });
            html += `</div>`;
        } else {
            html += `<p class="text-on-surface-variant text-[13px] py-2">Chưa có hoạt động nào trong ngày này.</p>`;
        }

        html += `<button onclick="toggleItemForm('${day.id}')" class="mt-6 w-full py-3 border border-dashed border-outline rounded-lg text-matcha hover:bg-matcha/5 transition-all flex items-center justify-center gap-2 font-label-lg uppercase tracking-widest text-[12px]">
            <span class="material-symbols-outlined">add</span> Thêm hoạt động
          </button>
          <div id="itemform-${day.id}" class="hidden mt-4 bg-primary-fixed/20 border border-matcha/20 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-col gap-1">
              <label class="font-label-md text-on-surface-variant uppercase">Giờ</label>
              <input type="time" id="it-time-${day.id}" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
            </div>
            <div class="flex flex-col gap-1">
              <label class="font-label-md text-on-surface-variant uppercase">Hoạt động</label>
              <input id="it-title-${day.id}" placeholder="VD: Ăn sáng, tham quan..." class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
            </div>
            <div class="flex flex-col gap-1">
              <label class="font-label-md text-on-surface-variant uppercase">Địa điểm (không bắt buộc)</label>
              <input id="it-loc-${day.id}" placeholder="VD: Chợ Đà Lạt" oninput="clearItemCoords('${day.id}')" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
            </div>
            <div class="flex flex-col gap-1">
              <label class="font-label-md text-on-surface-variant uppercase">Ghi chú (không bắt buộc)</label>
              <input id="it-note-${day.id}" placeholder="Lưu ý thêm..." class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
            </div>
            <input type="hidden" id="it-lat-${day.id}">
            <input type="hidden" id="it-lng-${day.id}">
            <div class="col-span-1 md:col-span-2 flex justify-end gap-2 mt-1">
              <button onclick="toggleItemForm('${day.id}')" class="px-4 py-2 border border-outline-variant rounded-lg font-label-lg text-on-surface-variant">Hủy</button>
              <button onclick="addItem('${day.id}')" class="px-4 py-2 bg-matcha text-white rounded-lg font-label-lg">Lưu</button>
            </div>
          </div>
        </div></div>`;
    });

    html += `<button onclick="toggleDayForm()" class="w-full py-3 border border-dashed border-outline rounded-lg text-matcha hover:bg-matcha/5 transition-all flex items-center justify-center gap-2 font-label-lg uppercase tracking-widest text-[12px] mb-4">
        <span class="material-symbols-outlined">add</span> Thêm ngày mới
      </button>
      <div id="dayform" class="hidden bg-primary-fixed/20 border border-matcha/20 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Ngày</label>
          <input type="date" id="day-date" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
        </div>
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Tên ngày (không bắt buộc)</label>
          <input id="day-label" placeholder="VD: Khám phá trung tâm" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
        </div>
        <div class="col-span-1 md:col-span-2 flex justify-end gap-2">
          <button onclick="toggleDayForm()" class="px-4 py-2 border border-outline-variant rounded-lg font-label-lg text-on-surface-variant">Hủy</button>
          <button onclick="addDay()" class="px-4 py-2 bg-matcha text-white rounded-lg font-label-lg">Lưu</button>
        </div>
      </div>`;

    document.getElementById('tab-content').innerHTML = html;
}

function toggleDayForm() { document.getElementById('dayform').classList.toggle('hidden'); }
function toggleItemForm(dayId) { document.getElementById('itemform-' + dayId).classList.toggle('hidden'); }

async function addDay() {
    const date = document.getElementById('day-date').value;
    const label = document.getElementById('day-label').value.trim();
    if (!date) { toast('Chọn ngày trước nhé'); return; }
    try {
        const res = await fetch('index.php?route=api/trip/add-day', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, date, label })
        });
        if (res.ok) { toast('Đã thêm ngày'); await refreshAndRender(); }
        else toast('Lỗi khi thêm ngày');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function deleteDay(id) {
    if (!confirm('Bạn có chắc muốn xóa ngày này và toàn bộ hoạt động bên trong?')) return;
    try {
        const res = await fetch('index.php?route=api/trip/delete-day', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, day_id: id })
        });
        if (res.ok) { toast('Đã xóa ngày'); await refreshAndRender(); }
        else toast('Lỗi khi xóa ngày');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function addItem(dayId) {
    const time = document.getElementById('it-time-' + dayId).value;
    const title = document.getElementById('it-title-' + dayId).value.trim();
    const location = document.getElementById('it-loc-' + dayId).value.trim();
    const note = document.getElementById('it-note-' + dayId).value.trim();
    let lat = document.getElementById('it-lat-' + dayId)?.value || '';
    let lng = document.getElementById('it-lng-' + dayId)?.value || '';
    if (!title) { toast('Nhập tên hoạt động'); return; }
    try {
        if (location && (!lat || !lng)) {
            const geo = await geocodePlace(location);
            if (geo) {
                lat = geo.lat;
                lng = geo.lng;
            }
        }
        const res = await fetch('index.php?route=api/trip/add-activity', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ day_id: dayId, time, title, location, note, lat, lng })
        });
        if (res.ok) { toast('Đã thêm hoạt động'); await refreshAndRender(); }
        else toast('Lỗi khi thêm hoạt động');
    } catch(e) { toast('Lỗi kết nối'); }
}

function clearItemCoords(dayId) {
    const lat = document.getElementById('it-lat-' + dayId);
    const lng = document.getElementById('it-lng-' + dayId);
    if (lat) lat.value = '';
    if (lng) lng.value = '';
}

async function deleteItem(dayId, itemId) {
    try {
        const res = await fetch('index.php?route=api/trip/delete-activity', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ day_id: dayId, activity_id: itemId })
        });
        if (res.ok) { toast('Đã xóa hoạt động'); await refreshAndRender(); }
        else toast('Lỗi khi xóa hoạt động');
    } catch(e) { toast('Lỗi kết nối'); }
}

/* ===================== BUDGET ===================== */
function renderBudget() {
    const members = State.trip.members;
    const expenses = [...State.trip.expenses].sort((a,b) => (b.date || '').localeCompare(a.date || ''));
    const total = expenses.reduce((s,e) => s + Number(e.amount || 0), 0);
    const per = members.length ? total / members.length : 0;

    let html = `<div class="flex justify-between items-center mb-6">
      <h2 class="font-headline-md text-headline-md text-primary">Chi phí chuyến đi</h2>
      <button onclick="refreshAndRender()" class="text-matcha font-label-lg flex items-center gap-1 hover:underline">
        <span class="material-symbols-outlined text-[18px]">refresh</span> Làm mới
      </button>
    </div>`;

    html += `<div class="grid grid-cols-12 gap-gutter mb-gutter">
      <div class="col-span-12 md:col-span-6 bg-matcha text-white rounded-xl p-6 shadow-sm">
        <p class="font-label-lg uppercase tracking-widest text-[11px] text-white/70 mb-2">Tổng chi phí</p>
        <p class="font-display-md text-[32px] font-bold">${money(total)}</p>
      </div>
      <div class="col-span-12 md:col-span-6 bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
        <p class="font-label-lg uppercase tracking-widest text-[11px] text-on-surface-variant mb-2">Mỗi người nên chi</p>
        <p class="font-display-md text-[32px] font-bold text-primary">${money(per)}</p>
      </div>
    </div>`;

    html += `<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-gutter">
      <p class="font-label-md text-on-surface-variant uppercase tracking-widest mb-3">Thành viên (${members.length})</p>
      <div class="flex flex-wrap gap-2 mb-3">
        ${members.map(m => `<span class="inline-flex items-center gap-2 bg-primary-fixed text-matcha rounded-full px-3 py-1.5 text-[13px] font-label-lg">
          ${esc(m)}<button onclick="removeMember('${esc(m).replace(/'/g,"\\'")}')" class="text-matcha/70 hover:text-error">✕</button>
        </span>`).join('') || '<span class="text-[13px] text-on-surface-variant">Chưa có thành viên</span>'}
      </div>
      <div class="flex gap-2">
        <input id="new-member" placeholder="Tên thành viên mới" class="flex-1 border border-outline-variant rounded-lg px-3 py-2 text-[14px] bg-surface-container-low">
        <button onclick="addMember()" class="px-4 py-2 bg-matcha text-white rounded-lg font-label-lg">Thêm</button>
      </div>
    </div>`;

    if (members.length > 0) {
        html += `<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-gutter">
          <p class="font-label-md text-on-surface-variant uppercase tracking-widest mb-3">Số dư</p>`;
        members.forEach(m => {
            const paid = expenses.filter(e => e.payer === m).reduce((s,e) => s + Number(e.amount || 0), 0);
            const bal = paid - per;
            html += `<div class="flex justify-between items-center py-2 border-t border-outline-variant first:border-t-0">
              <span class="text-[14px]">${esc(m)}</span>
              <span class="font-data-mono font-bold text-[14px] ${bal >= 0 ? 'text-primary' : 'text-error'}">${bal >= 0 ? '+' : ''}${money(bal)}</span>
            </div>`;
        });
        html += `<p class="text-[11px] text-on-surface-variant mt-3">Số dương = được nhận lại · số âm = cần trả thêm</p></div>`;
    }

    html += `<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-6">
      <p class="font-label-md text-on-surface-variant uppercase tracking-widest mb-3">Danh sách chi tiêu</p>`;
    if (expenses.length === 0) {
        html += `<p class="text-[13px] text-on-surface-variant py-2">Chưa có chi phí nào được ghi nhận.</p>`;
    }
    expenses.forEach(e => {
        html += `<div class="flex justify-between items-center py-3 border-t border-outline-variant first:border-t-0">
          <div><p class="font-label-lg text-[14px]">${esc(e.desc)}</p><p class="text-[12px] text-on-surface-variant">${esc(e.payer)} · ${fmtDate(e.date)}</p></div>
          <div class="flex items-center gap-3"><span class="font-data-mono font-bold text-[14px]">${money(e.amount)}</span>
          <button onclick="deleteExpense('${e.id}')" class="text-on-surface-variant hover:text-error">✕</button></div>
        </div>`;
    });
    html += `</div>`;

    html += `<button onclick="toggleExpenseForm()" class="w-full py-3 border border-dashed border-outline rounded-lg text-matcha hover:bg-matcha/5 transition-all flex items-center justify-center gap-2 font-label-lg uppercase tracking-widest text-[12px] mb-4">
        <span class="material-symbols-outlined">add</span> Thêm chi phí
      </button>
      <div id="expform" class="hidden bg-primary-fixed/20 border border-matcha/20 rounded-lg p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="col-span-1 md:col-span-2 flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Mô tả</label>
          <input id="exp-desc" placeholder="VD: Vé máy bay, ăn tối..." class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
        </div>
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Số tiền (đ)</label>
          <input type="number" id="exp-amount" placeholder="0" step="1000" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
        </div>
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Ngày</label>
          <input type="date" id="exp-date" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
        </div>
        <div class="col-span-1 md:col-span-2 flex flex-col gap-1">
          <label class="font-label-md text-on-surface-variant uppercase">Người trả</label>
          <select id="exp-payer" class="px-3 py-2 border border-outline-variant rounded-lg text-[14px] bg-white">
            ${members.map(m => `<option value="${esc(m)}">${esc(m)}</option>`).join('')}
          </select>
        </div>
        <div class="col-span-1 md:col-span-2 flex justify-end gap-2">
          <button onclick="toggleExpenseForm()" class="px-4 py-2 border border-outline-variant rounded-lg font-label-lg text-on-surface-variant">Hủy</button>
          <button onclick="addExpense()" class="px-4 py-2 bg-matcha text-white rounded-lg font-label-lg">Lưu</button>
        </div>
      </div>`;

    document.getElementById('tab-content').innerHTML = html;
}

function toggleExpenseForm() {
    if (State.trip.members.length === 0) { toast('Thêm thành viên trước khi ghi chi phí'); return; }
    document.getElementById('expform').classList.toggle('hidden');
}

async function addMember() {
    const v = document.getElementById('new-member').value.trim();
    if (!v) return;
    try {
        const res = await fetch('index.php?route=api/trip/add-member', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, name: v })
        });
        if (res.ok) { toast('Đã thêm thành viên'); await refreshAndRender(); }
        else toast('Lỗi khi thêm thành viên');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function removeMember(m) {
    if (!confirm(`Xóa thành viên "${m}" khỏi chuyến đi?`)) return;
    try {
        const res = await fetch('index.php?route=api/trip/remove-member', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, name: m })
        });
        if (res.ok) { toast('Đã xóa thành viên'); await refreshAndRender(); }
        else toast('Lỗi khi xóa thành viên');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function addExpense() {
    const desc = document.getElementById('exp-desc').value.trim();
    const amount = Number(document.getElementById('exp-amount').value);
    const date = document.getElementById('exp-date').value || new Date().toISOString().slice(0,10);
    const payer = document.getElementById('exp-payer').value;
    if (!desc || !amount) { toast('Nhập mô tả và số tiền'); return; }
    try {
        const res = await fetch('index.php?route=api/trip/add-expense', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, desc, amount, date, payer })
        });
        if (res.ok) { toast('Đã ghi nhận chi phí'); await refreshAndRender(); }
        else toast('Lỗi khi thêm chi phí');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function deleteExpense(id) {
    try {
        const res = await fetch('index.php?route=api/trip/delete-expense', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, expense_id: id })
        });
        if (res.ok) { toast('Đã xóa chi phí'); await refreshAndRender(); }
        else toast('Lỗi khi xóa chi phí');
    } catch(e) { toast('Lỗi kết nối'); }
}

/* ===================== CHECKLIST ===================== */
const COMMON_ITEMS = ['Hộ chiếu / CCCD', 'Sạc điện thoại', 'Tiền mặt', 'Thuốc cá nhân', 'Đồ vệ sinh cá nhân', 'Quần áo đổi', 'Kính/áo chống nắng', 'Sạc dự phòng'];

function renderChecklist() {
    const items = State.trip.checklist;
    const done = items.filter(i => i.checked).length;
    const pct = items.length ? Math.round(done / items.length * 100) : 0;

    let html = `<div class="flex justify-between items-center mb-6">
      <h2 class="font-headline-md text-headline-md text-primary">Checklist nhóm</h2>
      <button onclick="refreshAndRender()" class="text-matcha font-label-lg flex items-center gap-1 hover:underline">
        <span class="material-symbols-outlined text-[18px]">refresh</span> Làm mới
      </button>
    </div>`;

    html += `<div class="bg-primary-fixed/30 border border-matcha/10 rounded-xl p-5 mb-gutter">
      <div class="flex justify-between text-[13px] mb-2"><span>Đã chuẩn bị</span><span class="font-data-mono font-bold">${done}/${items.length}</span></div>
      <div class="h-2 bg-white/60 rounded-full overflow-hidden"><div class="h-full bg-matcha rounded-full" style="width:${pct}%"></div></div>
    </div>`;

    html += `<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm mb-4">`;
    if (items.length === 0) {
        html += `<p class="text-[13px] text-on-surface-variant py-2">Chưa có món nào trong checklist.</p>`;
    }
    items.forEach(it => {
        html += `<div class="flex items-center gap-3 py-2.5 border-t border-outline-variant first:border-t-0 group">
          <button onclick="toggleCheck('${it.id}', ${!it.checked})" class="w-5 h-5 rounded border flex items-center justify-center text-[12px] flex-shrink-0 ${it.checked ? 'bg-matcha border-matcha text-white' : 'border-outline-variant'}">${it.checked ? '✓' : ''}</button>
          <span class="flex-1 text-[14px] ${it.checked ? 'line-through text-on-surface-variant' : ''}">${esc(it.text)}</span>
          <button onclick="deleteCheck('${it.id}')" class="text-on-surface-variant opacity-0 group-hover:opacity-100 hover:text-error">✕</button>
        </div>`;
    });
    html += `</div>`;

    html += `<button onclick="addCommonItems()" class="w-full py-3 border border-dashed border-outline rounded-lg text-matcha hover:bg-matcha/5 transition-all flex items-center justify-center gap-2 font-label-lg uppercase tracking-widest text-[12px] mb-4">
        <span class="material-symbols-outlined">playlist_add</span> Thêm gợi ý đồ cần mang
      </button>
      <div class="flex gap-2">
        <input id="new-check" placeholder="Thêm món đồ..." onkeydown="if(event.key==='Enter')addCheckItem()" class="flex-1 border border-outline-variant rounded-lg px-3 py-2.5 text-[14px] bg-surface-container-low">
        <button onclick="addCheckItem()" class="px-4 py-2.5 bg-matcha text-white rounded-lg font-label-lg">Thêm</button>
      </div>`;

    document.getElementById('tab-content').innerHTML = html;
}

async function addCheckItem() {
    const v = document.getElementById('new-check').value.trim();
    if (!v) return;
    try {
        const res = await fetch('index.php?route=api/trip/checklist/add', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, text: v })
        });
        if (res.ok) { toast('Đã thêm món đồ'); await refreshAndRender(); }
        else toast('Lỗi khi thêm đồ');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function addCommonItems() {
    const existing = new Set(State.trip.checklist.map(i => i.text));
    try {
        for (const t of COMMON_ITEMS) {
            if (!existing.has(t)) {
                await fetch('index.php?route=api/trip/checklist/add', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ trip_id: State.trip.id, text: t })
                });
            }
        }
        toast('Đã thêm các gợi ý');
        await refreshAndRender();
    } catch(e) { toast('Lỗi kết nối'); }
}

async function toggleCheck(id, checked) {
    try {
        const res = await fetch('index.php?route=api/trip/checklist/toggle', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: id, checked })
        });
        if (res.ok) await refreshAndRender();
        else toast('Lỗi cập nhật');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function deleteCheck(id) {
    try {
        const res = await fetch('index.php?route=api/trip/checklist/delete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, item_id: id })
        });
        if (res.ok) { toast('Đã xóa món đồ'); await refreshAndRender(); }
        else toast('Lỗi khi xóa đồ');
    } catch(e) { toast('Lỗi kết nối'); }
}

/* ===================== MAP ===================== */
function renderMapTab() {
    const days = [...(State.trip?.itinerary || [])].sort((a,b) => (a.date || '').localeCompare(b.date || ''));
    if (!State.mapDayIndex || State.mapDayIndex >= days.length) State.mapDayIndex = 0;
    const day = days[State.mapDayIndex] || null;
    const items = day ? [...(day.items || [])].sort((a,b) => (a.time || '').localeCompare(b.time || '')) : [];
    const geolocated = items.filter(i => Number.isFinite(Number(i.lat)) && Number.isFinite(Number(i.lng)));
    let html = `<div class="flex justify-between items-center mb-6">
      <h2 class="font-headline-md text-headline-md text-primary">Bản đồ theo lịch trình</h2>
      <button onclick="refreshAndRender()" class="text-matcha font-label-lg flex items-center gap-1 hover:underline">
        <span class="material-symbols-outlined text-[18px]">refresh</span> Làm mới
      </button>
    </div>`;

    html += `<div class="mb-4 flex flex-wrap gap-2">`;
    days.forEach((d, idx) => {
      const active = idx === State.mapDayIndex;
      html += `<button type="button" onclick="setMapDay(${idx})" class="px-4 py-2 rounded-full border text-[13px] font-label-lg ${active ? 'bg-matcha text-white border-matcha' : 'bg-white text-on-surface-variant border-outline-variant'}">
        Day ${idx + 1}
      </button>`;
    });
    html += `</div>`;

    html += `<div class="grid grid-cols-1 xl:grid-cols-[1.5fr_0.9fr] gap-4">
      <div class="rounded-xl overflow-hidden border border-outline-variant shadow-sm bg-white">
        <div id="itinerary-map" style="height: 560px;"></div>
      </div>
      <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-outline-variant">
          <p class="text-[12px] uppercase tracking-widest text-on-surface-variant font-label-md">DAY ${State.mapDayIndex + 1}</p>
          <h3 class="font-headline-md text-[24px] text-on-surface mt-1">${esc(day?.label || day?.date || 'Chưa có ngày')}</h3>
          <p class="text-[13px] text-on-surface-variant mt-2">${geolocated.length ? `${geolocated.length} hoạt động có tọa độ` : 'Chưa có hoạt động nào có tọa độ để hiển thị trên bản đồ'}</p>
        </div>
        <div class="max-h-[500px] overflow-y-auto custom-scrollbar p-4 space-y-3">
          ${items.length ? items.map((it, idx) => `
            <button type="button" onclick="focusMapActivity(${idx})" class="w-full text-left rounded-xl border border-outline-variant p-4 hover:border-matcha hover:bg-matcha/5 transition-all ${Number.isFinite(Number(it.lat)) && Number.isFinite(Number(it.lng)) ? '' : 'opacity-75'}">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-full bg-primary-fixed text-matcha flex items-center justify-center font-bold">${idx + 1}</div>
                  <div class="min-w-0">
                    <p class="text-[12px] text-on-surface-variant uppercase tracking-wide">${esc(it.time || '--:--')}</p>
                    <p class="font-label-lg text-[15px] text-on-surface truncate">${esc(it.title)}</p>
                  </div>
                </div>
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">${Number.isFinite(Number(it.lat)) && Number.isFinite(Number(it.lng)) ? 'location_on' : 'hide_source'}</span>
              </div>
              ${it.location ? `<p class="text-[13px] text-on-surface-variant mt-2">📍 ${esc(it.location)}</p>` : ''}
              ${it.note ? `<p class="text-[13px] text-on-surface-variant mt-1 line-clamp-2">${esc(it.note)}</p>` : ''}
              ${!(Number.isFinite(Number(it.lat)) && Number.isFinite(Number(it.lng))) ? `<p class="text-[12px] text-amber-700 mt-2">Chưa có vị trí</p>` : ''}
            </button>
          `).join('') : '<p class="text-[13px] text-on-surface-variant">Chưa có hoạt động cho ngày này.</p>'}
        </div>
      </div>
    </div>`;

    document.getElementById('tab-content').innerHTML = html;
    initMap();
}

function initMap() {
    const el = document.getElementById('itinerary-map');
    if (!el) return;
    if (State.map) { State.map.remove(); State.map = null; }
    State.map = L.map('itinerary-map', { zoomControl: true, attributionControl: false }).setView([16.0544, 108.2022], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(State.map);
    State.mapMarkers = [];
    State.mapLines = [];

    const days = [...(State.trip?.itinerary || [])].sort((a,b) => (a.date || '').localeCompare(b.date || ''));
    const day = days[State.mapDayIndex] || null;
    const items = day ? [...(day.items || [])].sort((a,b) => (a.time || '').localeCompare(b.time || '')) : [];
    const points = [];

    items.forEach((it, idx) => {
        if (!Number.isFinite(Number(it.lat)) || !Number.isFinite(Number(it.lng))) return;
        const latlng = [Number(it.lat), Number(it.lng)];
        points.push(latlng);
        const marker = L.marker(latlng, {
            icon: L.divIcon({
                className: 'didau-number-marker',
                html: `<div style="background:#53613b;color:#fff;border:2px solid #fff;box-shadow:0 8px 18px rgba(0,0,0,.18);width:30px;height:30px;border-radius:9999px;display:flex;align-items:center;justify-content:center;font-weight:700">${idx + 1}</div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(State.map);
        marker.bindPopup(`<div style="min-width:220px"><strong>${esc(it.title)}</strong><br><small>${esc(it.time || '--:--')}</small><br><button type="button" onclick="focusMapActivity(${idx})" style="margin-top:8px;padding:6px 10px;border:1px solid #5a6b3d;border-radius:8px;color:#5a6b3d;background:#fff;cursor:pointer">Chi tiết</button></div>`);
        State.mapMarkers.push(marker);
    });

    if (points.length > 1) {
        const line = L.polyline(points, { color: '#53613b', weight: 5, opacity: 0.85 }).addTo(State.map);
        State.map.fitBounds(line.getBounds(), { padding: [40, 40] });
    } else if (points.length === 1) {
        State.map.setView(points[0], 14);
    }
}

function setMapDay(idx) {
    State.mapDayIndex = idx;
    renderMapTab();
}

function focusMapActivity(idx) {
    const days = [...(State.trip?.itinerary || [])].sort((a,b) => (a.date || '').localeCompare(b.date || ''));
    const day = days[State.mapDayIndex] || null;
    const items = day ? [...(day.items || [])].sort((a,b) => (a.time || '').localeCompare(b.time || '')) : [];
    const item = items[idx];
    if (!item || !State.map) return;
    if (Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng))) {
        State.map.setView([Number(item.lat), Number(item.lng)], 15);
    }
    toast(`${item.time || '--:--'} · ${item.title}`);
}

function flyTo(lat, lng) {
    if (State.map) State.map.setView([lat, lng], 15);
    document.getElementById('map').scrollIntoView({behavior: 'smooth', block: 'center'});
}

async function searchPlace() {
    const q = document.getElementById('map-search').value.trim();
    if (!q) return;
    document.getElementById('search-results').innerHTML = '<p class="text-[13px] text-on-surface-variant py-2">Đang tìm...</p>';
    try {
        const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(q));
        const data = await res.json();
        State.searchResults = data;
        if (!data.length) {
            document.getElementById('search-results').innerHTML = '<p class="text-[13px] text-on-surface-variant py-2">Không tìm thấy kết quả.</p>';
            return;
        }
        let html = '<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-3">';
        data.forEach((d, i) => {
            html += `<div class="flex justify-between items-center gap-2 py-2 border-t border-outline-variant first:border-t-0">
              <span class="text-[13px] flex-1">${esc(d.display_name)}</span>
              <button onclick="addSearchResult(${i})" class="px-3 py-1.5 border border-matcha text-matcha rounded-lg text-[12px] font-label-lg flex-shrink-0">+ Thêm</button>
            </div>`;
        });
        html += '</div>';
        document.getElementById('search-results').innerHTML = html;
    } catch(e) {
        document.getElementById('search-results').innerHTML = '<p class="text-[13px] text-error py-2">Lỗi khi tìm kiếm, thử lại nhé.</p>';
    }
}

async function addSearchResult(i) {
    const d = State.searchResults[i];
    if (!d) return;
    const name = d.display_name.split(',')[0];
    try {
        const res = await fetch('index.php?route=api/trip/location/add', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, name, lat: Number(d.lat), lng: Number(d.lon) })
        });
        if (res.ok) {
            document.getElementById('map-search').value = '';
            document.getElementById('search-results').innerHTML = '';
            State.mapFocus = { name, lat: Number(d.lat), lng: Number(d.lon) };
            toast('Đã thêm địa điểm');
            await refreshAndRender();
        } else toast('Lỗi khi lưu vị trí');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function deleteLocation(id) {
    try {
        const res = await fetch('index.php?route=api/trip/location/delete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: State.trip.id, loc_id: id })
        });
        if (res.ok) { toast('Đã xóa địa điểm'); await refreshAndRender(); }
        else toast('Lỗi khi xóa');
    } catch(e) { toast('Lỗi kết nối'); }
}

async function updateLocNote(id, val) {
    try {
        await fetch('index.php?route=api/trip/location/update-note', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ loc_id: id, note: val })
        });
    } catch(e) { console.error('Lỗi lưu ghi chú bản đồ', e); }
}

function getLocationById(id) {
    return (State.trip?.locations || []).find(l => String(l.id) === String(id));
}

function clearRoute() {
    State.routeFrom = null;
    State.routeTo = null;
    State.routeInfo = null;
    State.routePanelOpen = false;
    if (State.routeLayer) {
        State.routeLayer.remove();
        State.routeLayer = null;
    }
    renderMapTab();
}

function toggleRoutePanel() {
    State.routePanelOpen = !State.routePanelOpen;
    const panel = document.getElementById('route-panel');
    if (panel) panel.classList.toggle('hidden');
}

function openRouteInGoogleMaps() {
    if (!State.routeFrom || !State.routeTo) return;
    const from = `${State.routeFrom.lat},${State.routeFrom.lng}`;
    const to = `${State.routeTo.lat},${State.routeTo.lng}`;
    window.open(`https://www.google.com/maps/dir/${from}/${to}`, '_blank', 'noopener');
}

function clearRouteCoords(which) {
    if (which === 'from') {
        State.routeFrom = null;
    }
    if (which === 'to') {
        State.routeTo = null;
    }
}

function getGoogleMapEmbedSrc(target) {
    if (!target) {
        return 'https://www.google.com/maps?output=embed';
    }
    const lat = Number(target.lat);
    const lng = Number(target.lng);
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        return `https://www.google.com/maps?q=${encodeURIComponent(lat + ',' + lng)}&z=15&output=embed`;
    }
    const name = target.name || target.label || '';
    return `https://www.google.com/maps?q=${encodeURIComponent(name)}&output=embed`;
}

function focusGoogleMapOnLocation(loc) {
    if (!loc) return;
    State.mapFocus = {
        name: loc.name || loc.label || '',
        lat: loc.lat,
        lng: loc.lng
    };
    const frame = document.getElementById('google-map-frame');
    if (frame) frame.src = getGoogleMapEmbedSrc(State.mapFocus);
}

function fillCurrentLocation() {
    if (!navigator.geolocation) {
        toast('Trình duyệt không hỗ trợ vị trí hiện tại');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => {
            State.routeFrom = {
                label: 'Vị trí hiện tại',
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };
            const fromEl = document.getElementById('route-from');
            if (fromEl) fromEl.value = 'Vị trí hiện tại';
        },
        () => toast('Không lấy được vị trí hiện tại, hãy cho phép quyền định vị')
    );
}

function fillCurrentLocationSilent() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
        pos => {
            State.routeFrom = {
                label: 'Vị trí hiện tại',
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };
            const fromEl = document.getElementById('route-from');
            if (fromEl && !fromEl.value.trim()) fromEl.value = 'Vị trí hiện tại';
        },
        () => {}
    );
}

function routeToLocation(id) {
    const dest = getLocationById(id);
    if (!dest) return;
    State.routeTo = dest;
    focusGoogleMapOnLocation(dest);
    const toEl = document.getElementById('route-to');
    if (toEl) toEl.value = dest.name || '';
    if (!State.routeFrom || !State.routeFrom.lat || !State.routeFrom.lng) fillCurrentLocationSilent();
}

function fillRouteDestination(id) {
    const dest = getLocationById(id);
    if (!dest) return;
    State.routeTo = dest;
    focusGoogleMapOnLocation(dest);
    const toEl = document.getElementById('route-to');
    if (toEl) toEl.value = dest.name || '';

    const routeBox = document.querySelector('.bg-surface-container-lowest.border.border-outline-variant.rounded-xl.p-4.shadow-sm.mb-4');
    if (routeBox) {
        routeBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    toast('Đã chọn điểm đến');
}

async function drawRoute(from, to, rerender = true) {
    if (!State.map || !from || !to) return;
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=full&geometries=geojson&steps=true`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.routes || !data.routes.length) {
            toast('Không tìm được tuyến đường');
            return;
        }

        if (State.routeLayer) {
            State.routeLayer.remove();
            State.routeLayer = null;
        }

        const route = data.routes[0];
        State.routeLayer = L.geoJSON(route.geometry, {
            style: {
                color: '#5a6b3d',
                weight: 5,
                opacity: 0.9
            }
        }).addTo(State.map);

        const start = L.marker([from.lat, from.lng]).addTo(State.map).bindPopup('Điểm xuất phát');
        const end = L.marker([to.lat, to.lng]).addTo(State.map).bindPopup(esc(to.name || 'Điểm đến'));
        State.markers.push(start, end);

        const bounds = L.geoJSON(route.geometry).getBounds();
        State.map.fitBounds(bounds, { padding: [40, 40] });

        State.routeInfo = {
            distanceText: formatDistance(route.distance),
            durationText: formatDuration(route.duration)
        };

        if (rerender) renderMapTab();
    } catch (e) {
        console.error(e);
        toast('Lỗi khi tải tuyến đường');
    }
}

async function buildRouteFromForm() {
    const fromText = document.getElementById('route-from')?.value.trim();
    const toText = document.getElementById('route-to')?.value.trim();
    if (!fromText || !toText) {
        toast('Nhập đủ điểm đi và điểm đến nhé');
        return;
    }

    let from = State.routeFrom && State.routeFrom.lat && State.routeFrom.lng ? State.routeFrom : null;
    let to = State.routeTo && State.routeTo.lat && State.routeTo.lng ? State.routeTo : null;

    if (!from || (from.label && from.label !== fromText)) {
        if (fromText === 'Vị trí hiện tại' && navigator.geolocation) {
            from = await getCurrentLocationCoords();
        } else {
            from = await geocodePlace(fromText);
        }
    }
    if (!to || (to.name && to.name !== toText)) {
        const saved = getLocationByIdByName(toText);
        to = saved || await geocodePlace(toText);
    }

    if (!from || !to) {
        toast('Không xác định được điểm đi/đến');
        return;
    }
    State.routeFrom = from;
    State.routeTo = to;
    State.routeInfo = await estimateRouteInfo(from, to);
    State.routePanelOpen = true;
    renderMapTab();
}

async function estimateRouteInfo(from, to) {
    try {
        if (!from?.lat || !from?.lng || !to?.lat || !to?.lng) return null;
        const url = `https://router.project-osrm.org/route/v1/driving/${from.lng},${from.lat};${to.lng},${to.lat}?overview=false&steps=false`;
        const res = await fetch(url);
        const data = await res.json();
        const route = data?.routes?.[0];
        if (!route) return null;
        return {
            distanceText: formatDistance(route.distance),
            durationText: formatDuration(route.duration)
        };
    } catch (e) {
        return null;
    }
}

function getLocationByIdByName(name) {
    const normalized = slugify(name);
    return (State.trip?.locations || []).find(l => slugify(l.name) === normalized) || null;
}

function getCurrentLocationCoords() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(null);
        navigator.geolocation.getCurrentPosition(
            pos => resolve({
                label: 'Vị trí hiện tại',
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            }),
            () => resolve(null)
        );
    });
}

async function geocodePlace(query) {
    try {
        const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query));
        const data = await res.json();
        if (!data || !data.length) return null;
        const d = data[0];
        return {
            label: query,
            name: query,
            lat: Number(d.lat),
            lng: Number(d.lon)
        };
    } catch (e) {
        return null;
    }
}

function formatDistance(meters) {
    if (!meters && meters !== 0) return '';
    if (meters >= 1000) return (meters / 1000).toFixed(meters >= 10000 ? 0 : 1) + ' km';
    return Math.round(meters) + ' m';
}

function formatDuration(seconds) {
    if (!seconds && seconds !== 0) return '';
    const mins = Math.round(seconds / 60);
    if (mins < 60) return mins + ' phút';
    const hrs = Math.floor(mins / 60);
    const rem = mins % 60;
    return rem ? `${hrs} giờ ${rem} phút` : `${hrs} giờ`;
}

/* ===================== INITIALIZE ===================== */
async function init() {
    if (State.code) {
        await refreshAndRender();
    } else {
        document.getElementById('tab-content').innerHTML = `<div class="bg-white border border-dashed border-outline-variant rounded-xl p-10 text-center text-on-surface-variant">
          Bạn chưa chọn nhóm nào. Hãy bấm <b>Thêm / đổi nhóm</b> để nhập mã nhóm.
        </div>`;
    }
}
init();

// Allow sidebar links like index.php?route=group#budget to switch tabs
window.addEventListener('hashchange', () => {
    const h = window.location.hash.slice(1);
    if (['itinerary','budget','checklist','map'].includes(h)) setTab(h);
});
</script>

<?php include __DIR__ . '/layouts/layout_footer.php'; ?>
