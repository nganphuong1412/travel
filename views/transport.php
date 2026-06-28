<?php include __DIR__ . '/layouts/layout_header.php'; ?>

<style>
  .glass-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(8px); border: 1px solid #c6c8bb; }
</style>

<div class="max-w-[1100px] mx-auto">
  <section class="mb-xl">
    <h2 class="font-display-lg text-display-lg text-primary mb-xs">Phương tiện di chuyển</h2>
    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">Lưu lại vé máy bay, tàu hỏa, xe khách của chuyến đi để cả nhóm cùng xem, không lo thất lạc thông tin.</p>
  </section>

  <section class="mb-xl">
    <div class="glass-card p-lg rounded-xl">
      <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
        <div>
          <h3 class="font-headline-md text-headline-md text-primary">Tra cứu nhanh</h3>
          <p class="text-[13px] text-on-surface-variant">Nhập điểm đi, điểm đến và ngày để mở kết quả tìm kiếm thật trên Google và các trang bán vé.</p>
        </div>
        <span class="text-[12px] uppercase tracking-wider text-on-surface-variant">Không scrape dữ liệu trực tiếp</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <input id="search-from" placeholder="Điểm đi, vd: Hà Nội" class="px-4 py-3 border border-outline-variant rounded-lg bg-surface-container-low outline-none focus:border-primary">
        <input id="search-to" placeholder="Điểm đến, vd: Đà Lạt" class="px-4 py-3 border border-outline-variant rounded-lg bg-surface-container-low outline-none focus:border-primary">
        <input id="search-date" type="date" class="px-4 py-3 border border-outline-variant rounded-lg bg-surface-container-low outline-none focus:border-primary">
      </div>

      <div class="flex flex-wrap gap-2">
        <button type="button" onclick="openQuickSearch('google')" class="px-4 py-2.5 rounded-full bg-primary text-white font-label-lg">Google</button>
        <button type="button" onclick="openQuickSearch('google_flights')" class="px-4 py-2.5 rounded-full border border-outline-variant font-label-lg">Google Flights</button>
        <button type="button" onclick="openQuickSearch('vexere')" class="px-4 py-2.5 rounded-full border border-outline-variant font-label-lg">Vexere</button>
        <button type="button" onclick="openQuickSearch('skyscanner')" class="px-4 py-2.5 rounded-full border border-outline-variant font-label-lg">Skyscanner</button>
        <button type="button" onclick="openQuickSearch('12go')" class="px-4 py-2.5 rounded-full border border-outline-variant font-label-lg">12Go</button>
      </div>
    </div>
  </section>

  <section class="mb-xl">
    <div class="glass-card p-lg rounded-xl flex flex-col gap-md">
      <div class="flex gap-md border-b border-outline-variant pb-base mb-base flex-wrap">
        <button data-type="Máy bay" onclick="selectType(this)" class="type-btn flex items-center gap-xs text-primary font-bold border-b-2 border-primary pb-base px-base">
          <span class="material-symbols-outlined">flight</span> Máy bay
        </button>
        <button data-type="Tàu hỏa" onclick="selectType(this)" class="type-btn flex items-center gap-xs text-on-surface-variant font-label-lg pb-base px-base hover:text-primary transition-colors">
          <span class="material-symbols-outlined">train</span> Tàu hỏa
        </button>
        <button data-type="Xe khách" onclick="selectType(this)" class="type-btn flex items-center gap-xs text-on-surface-variant font-label-lg pb-base px-base hover:text-primary transition-colors">
          <span class="material-symbols-outlined">directions_bus</span> Xe khách
        </button>
        <button data-type="Ô tô" onclick="selectType(this)" class="type-btn flex items-center gap-xs text-on-surface-variant font-label-lg pb-base px-base hover:text-primary transition-colors">
          <span class="material-symbols-outlined">directions_car</span> Ô tô
        </button>
        <button data-type="Xe máy" onclick="selectType(this)" class="type-btn flex items-center gap-xs text-on-surface-variant font-label-lg pb-base px-base hover:text-primary transition-colors">
          <span class="material-symbols-outlined">two_wheeler</span> Xe máy
        </button>
      </div>
      <input type="hidden" id="tr-type" value="Máy bay">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Điểm đi</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">location_on</span>
            <input id="tr-dep-place" class="w-full pl-10 pr-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="VD: Hà Nội (HAN)" type="text">
          </div>
        </div>
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Điểm đến</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">pin_drop</span>
            <input id="tr-arr-place" class="w-full pl-10 pr-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all" placeholder="VD: Đà Lạt (DLI)" type="text">
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Giờ khởi hành</label>
          <input id="tr-dep-time" type="datetime-local" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none">
        </div>
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Giờ đến (không bắt buộc)</label>
          <input id="tr-arr-time" type="datetime-local" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Đơn vị vận hành</label>
          <input id="tr-provider" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none" placeholder="VD: Vietjet Air...">
        </div>
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Mã đặt chỗ / Mã vé</label>
          <input id="tr-ticket" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none" placeholder="VD: VJ123456">
        </div>
        <div class="flex flex-col gap-xs">
          <label class="font-label-md text-on-surface-variant px-xs">Chi phí (đ)</label>
          <input id="tr-price" type="number" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none" placeholder="0">
        </div>
      </div>

      <div class="flex flex-col gap-xs">
        <label class="font-label-md text-on-surface-variant px-xs">Ghi chú thêm</label>
        <input id="tr-note" class="w-full px-base py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:border-primary outline-none" placeholder="VD: Cổng số 3, ký gửi 20kg...">
      </div>

      <div class="mt-base">
        <button onclick="addTransport()" class="w-full bg-primary text-on-primary py-4 rounded-xl font-headline-md flex items-center justify-center gap-base hover:shadow-lg transition-all active:scale-[0.98]">
          <span class="material-symbols-outlined">save</span>
          Lưu chặng di chuyển
        </button>
      </div>
    </div>
  </section>

  <section class="space-y-md">
    <div class="flex justify-between items-end mb-base">
      <h3 class="font-headline-md text-primary">Vé &amp; chặng đã lưu</h3>
      <button onclick="loadTransports()" class="flex items-center gap-xs font-label-lg px-base py-2 rounded-full border border-outline-variant hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-sm">refresh</span> Làm mới
      </button>
    </div>
    <div id="transport-list-container" class="space-y-md"></div>
  </section>
</div>

<script>
const TransportState = {
    code: <?= json_encode($_SESSION['trip_code'] ?? '') ?>,
    items: []
};

const TYPE_ICONS = {
    'Máy bay': 'flight',
    'Tàu hỏa': 'train',
    'Xe khách': 'directions_bus',
    'Ô tô': 'directions_car',
    'Xe máy': 'two_wheeler'
};

function selectType(btn) {
    document.querySelectorAll('.type-btn').forEach(b => {
        b.classList.remove('text-primary', 'font-bold', 'border-b-2', 'border-primary');
        b.classList.add('text-on-surface-variant', 'font-label-lg');
    });
    btn.classList.add('text-primary', 'font-bold', 'border-b-2', 'border-primary');
    btn.classList.remove('text-on-surface-variant', 'font-label-lg');
    document.getElementById('tr-type').value = btn.dataset.type;
}

function buildSearchQuery() {
    const from = document.getElementById('search-from').value.trim();
    const to = document.getElementById('search-to').value.trim();
    const date = document.getElementById('search-date').value;
    return { from, to, date };
}

function openQuickSearch(provider) {
    const { from, to, date } = buildSearchQuery();
    const query = [from, to, date].filter(Boolean).join(' ');
    if (!query) {
        toast('Nhập điểm đi hoặc điểm đến trước nhé');
        return;
    }

    const urls = {
        google: 'https://www.google.com/search?q=' + encodeURIComponent(query + ' vé xe vé máy bay vé tàu'),
        google_flights: 'https://www.google.com/search?q=' + encodeURIComponent('Google Flights ' + query),
        vexere: 'https://www.google.com/search?q=' + encodeURIComponent('site:vexere.com ' + query),
        skyscanner: 'https://www.google.com/search?q=' + encodeURIComponent('site:skyscanner.com ' + query),
        '12go': 'https://www.google.com/search?q=' + encodeURIComponent('site:12go.asia ' + query),
    };

    const url = urls[provider];
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
}

async function loadTransports() {
    try {
        const res = await fetch(`index.php?route=api/transport&code=${encodeURIComponent(TransportState.code)}`);
        if (res.ok) {
            TransportState.items = await res.json();
            renderTransports();
        } else {
            toast('Không tải được danh sách di chuyển');
        }
    } catch(e) {
        console.error(e);
        toast('Lỗi kết nối');
    }
}

function renderTransports() {
    const container = document.getElementById('transport-list-container');
    const items = TransportState.items;
    if (items.length === 0) {
        container.innerHTML = `<div class="glass-card rounded-xl p-10 text-center text-on-surface-variant">Chưa có thông tin vé hay chặng di chuyển nào được lưu.<br>Thêm chặng đầu tiên ở form bên trên nhé.</div>`;
        return;
    }

    let html = '';
    items.forEach(t => {
        const icon = TYPE_ICONS[t.type] || 'directions_car';
        const depTime = new Date(t.departure_time).toLocaleString('vi-VN', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'});
        const arrTime = t.arrival_time ? new Date(t.arrival_time).toLocaleString('vi-VN', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'}) : '';
        html += `<div class="glass-card p-md rounded-xl hover:border-primary transition-colors group">
          <div class="flex flex-wrap md:flex-nowrap items-center justify-between gap-md">
            <div class="flex items-center gap-md flex-1">
              <div class="w-16 h-16 rounded-lg bg-surface-container flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-primary text-4xl">${icon}</span>
              </div>
              <div class="flex flex-col">
                <span class="font-label-lg text-primary">${esc(t.type)}${t.provider ? ' · ' + esc(t.provider) : ''}</span>
                ${t.ticket_code ? `<span class="text-xs text-on-surface-variant font-data-mono">Mã vé: ${esc(t.ticket_code)}</span>` : ''}
                <div class="mt-base flex items-center gap-sm flex-wrap">
                  <span class="font-headline-md text-on-surface">${esc(t.departure_place)}</span>
                  <div class="flex items-center gap-xs px-base">
                    <div class="h-[1px] w-8 bg-outline-variant"></div>
                    <span class="material-symbols-outlined text-on-surface-variant text-sm">${icon}</span>
                    <div class="h-[1px] w-8 bg-outline-variant"></div>
                  </div>
                  <span class="font-headline-md text-on-surface">${esc(t.arrival_place)}</span>
                </div>
                <span class="text-xs text-on-surface-variant mt-xs">Đi: ${depTime}${arrTime ? ' · Đến: ' + arrTime : ''}</span>
                ${t.note ? `<span class="text-xs text-on-surface-variant mt-xs">📝 ${esc(t.note)}</span>` : ''}
              </div>
            </div>
            <div class="flex flex-col items-end gap-xs w-full md:w-auto">
              ${t.price > 0 ? `<span class="font-display-md text-primary">${money(t.price)}</span>` : ''}
              <button onclick="deleteTransport('${t.id}')" class="text-error font-label-md opacity-0 group-hover:opacity-100 transition-opacity hover:underline">Xóa</button>
            </div>
          </div>
        </div>`;
    });
    container.innerHTML = html;
}

async function addTransport() {
    const type = document.getElementById('tr-type').value;
    const provider = document.getElementById('tr-provider').value.trim();
    const departure_place = document.getElementById('tr-dep-place').value.trim();
    const arrival_place = document.getElementById('tr-arr-place').value.trim();
    const departure_time = document.getElementById('tr-dep-time').value;
    const arrival_time = document.getElementById('tr-arr-time').value;
    const ticket_code = document.getElementById('tr-ticket').value.trim();
    const price = Number(document.getElementById('tr-price').value);
    const note = document.getElementById('tr-note').value.trim();

    if (!departure_place || !arrival_place || !departure_time) {
        toast('Vui lòng nhập điểm đi, điểm đến và giờ khởi hành');
        return;
    }

    try {
        const res = await fetch('index.php?route=api/transport/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                trip_code: TransportState.code, type, provider,
                departure_place, arrival_place, departure_time, arrival_time,
                ticket_code, price, note
            })
        });

        if (res.ok) {
            toast('Đã lưu thông tin chặng đi');
            ['tr-provider','tr-dep-place','tr-arr-place','tr-dep-time','tr-arr-time','tr-ticket','tr-price','tr-note'].forEach(id => {
                document.getElementById(id).value = '';
            });
            await loadTransports();
        } else {
            const data = await res.json();
            toast(data.error || 'Lỗi khi lưu thông tin');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function deleteTransport(id) {
    if (!confirm('Xóa chặng vận chuyển này khỏi lịch trình?')) return;
    try {
        const res = await fetch('index.php?route=api/transport/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_code: TransportState.code, transport_id: id })
        });
        if (res.ok) {
            toast('Đã xóa thông tin chặng đi');
            await loadTransports();
        } else {
            toast('Lỗi khi xóa');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

loadTransports();
</script>

<?php include __DIR__ . '/layouts/layout_footer.php'; ?>
