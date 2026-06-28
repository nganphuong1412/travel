<?php include __DIR__ . '/layouts/layout_header.php'; ?>

<style>
  #wheel-canvas { transition: transform 4s cubic-bezier(0.15, 0, 0.15, 1); }
  .perforated-edge {
    background-image: radial-gradient(circle, transparent 70%, #c6c8bb 75%);
    background-size: 12px 12px; background-position: -6px 0; height: 12px; width: 100%;
  }
  @keyframes pulse-matcha {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255,255,255,0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0); }
  }
  .animate-pulse-custom { animation: pulse-matcha 2s infinite; }
</style>

<div class="max-w-[1200px] mx-auto">
  <div class="grid grid-cols-12 gap-gutter">
    <!-- Left: Wheel Section -->
    <div class="col-span-12 lg:col-span-7 flex flex-col items-center justify-center min-h-[600px]">
      <div class="relative w-full max-w-[460px] aspect-square flex items-center justify-center">
        <div class="absolute inset-0 rounded-full border-[12px] border-surface-container-highest shadow-lg z-0"></div>
        <div class="absolute inset-2 rounded-full border-[2px] border-outline-variant border-dashed z-0"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-4 z-20">
          <span class="material-symbols-outlined text-primary text-[48px]" style="font-variation-settings: 'FILL' 1;">arrow_drop_down</span>
        </div>
        <canvas class="relative z-10 w-full h-full" height="460" id="wheel-canvas" width="460"></canvas>
        <div class="absolute inset-0 flex items-center justify-center z-20">
          <button id="spin-btn" class="w-24 h-24 rounded-full bg-matcha text-white font-display-lg text-[20px] shadow-xl border-4 border-surface animate-pulse-custom hover:scale-105 active:scale-95 transition-transform flex flex-col items-center justify-center">
            <span>QUAY</span>
            <span class="material-symbols-outlined text-[20px]">play_arrow</span>
          </button>
        </div>
      </div>
      <div class="mt-stack-lg text-center min-h-[40px]">
        <p class="font-headline-lg text-headline-lg text-matcha opacity-0 transition-opacity duration-500" id="result-text"></p>
      </div>
    </div>

    <!-- Right: Options List -->
    <div class="col-span-12 lg:col-span-5">
      <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden flex flex-col h-full max-h-[640px] shadow-sm">
        <div class="p-6 bg-surface-container-lowest">
          <div class="flex justify-between items-start mb-2">
            <h3 class="font-headline-md text-headline-md text-matcha">Danh sách lựa chọn</h3>
            <span class="font-label-lg text-on-surface-variant" id="option-count">0 lựa chọn</span>
          </div>
          <p class="font-body-md text-on-surface-variant">Nhập các địa điểm cả nhóm đang phân vân để "nhân phẩm" quyết định.</p>
        </div>
        <div class="perforated-edge"></div>
        <div class="p-6 flex-grow flex flex-col gap-4 overflow-y-auto bg-surface custom-scrollbar">
          <div class="flex gap-2">
            <input id="option-input" type="text" placeholder="Thêm quán ăn, địa điểm..." onkeydown="if(event.key==='Enter')addOption()"
              class="flex-grow border-outline-variant border rounded-lg p-3 font-body-md focus:ring-2 focus:ring-matcha focus:border-matcha transition-all outline-none bg-surface-container-low">
            <button id="add-option" onclick="addOption()" class="bg-secondary text-on-secondary px-4 rounded-lg flex items-center justify-center hover:bg-secondary/80 transition-colors">
              <span class="material-symbols-outlined">add</span>
            </button>
          </div>
          <ul class="space-y-2" id="options-list">
            <!-- Loaded dynamically -->
          </ul>
        </div>
        <div class="p-6 border-t border-outline-variant bg-surface-container-lowest flex items-center justify-between">
          <button id="clear-all" onclick="clearAllOptions()" class="font-label-lg text-error hover:underline transition-all">Xóa tất cả</button>
          <button onclick="copyCode()" class="bg-matcha text-white px-6 py-2 rounded-full font-label-lg shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">share</span>
            CHIA SẺ MÃ
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-on-surface/60 backdrop-blur-sm z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
  <div class="bg-surface p-stack-lg rounded-2xl max-w-md w-full text-center shadow-2xl relative overflow-hidden border border-outline-variant">
    <div class="absolute top-0 left-0 w-full h-2 bg-matcha"></div>
    <div class="w-20 h-20 bg-matcha/10 rounded-full flex items-center justify-center mx-auto mb-6">
      <span class="material-symbols-outlined text-matcha text-[40px]">restaurant</span>
    </div>
    <h4 class="font-headline-lg text-headline-lg mb-2 text-on-surface">Chốt rồi nhé!</h4>
    <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">Lựa chọn tiếp theo của nhóm là:<br><span class="text-matcha font-bold text-[24px]" id="modal-winner"></span></p>
    <button id="close-modal" onclick="closeWheelModal()" class="w-full py-3 border-2 border-outline-variant rounded-lg font-label-lg text-matcha hover:bg-surface-container transition-all">QUAY LẠI</button>
  </div>
</div>

<script>
/* ===================== STATE ===================== */
const WheelState = {
    code: <?= json_encode($_SESSION['trip_code'] ?? '') ?>,
    tripId: null,
    options: [],
    isSpinning: false,
    currentRotation: 0
};

const canvas = document.getElementById('wheel-canvas');
const ctx = canvas.getContext('2d');
const colors = ["#53613b", "#834f2e", "#D8D3B3", "#76845b", "#6c7a51"];

/* ===================== LOAD ===================== */
async function loadTripId() {
    if (WheelState.tripId) return WheelState.tripId;
    try {
        const res = await fetch(`index.php?route=api/trip&code=${encodeURIComponent(WheelState.code)}`);
        if (res.ok) {
            const trip = await res.json();
            WheelState.tripId = trip.id;
            return trip.id;
        }
    } catch(e) { console.error(e); }
    return null;
}

async function loadOptions() {
    try {
        const res = await fetch(`index.php?route=api/wheel&code=${encodeURIComponent(WheelState.code)}`);
        if (res.ok) {
            WheelState.options = await res.json();
            renderOptions();
            drawWheel();
        } else {
            toast('Không tải được danh sách lựa chọn');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

function renderOptions() {
    const list = document.getElementById('options-list');
    document.getElementById('option-count').textContent = `${WheelState.options.length} lựa chọn`;

    if (WheelState.options.length === 0) {
        list.innerHTML = `<li class="text-center text-on-surface-variant text-[13px] py-6">Chưa có lựa chọn nào. Thêm vài địa điểm để bắt đầu quay nhé!</li>`;
        return;
    }

    let html = '';
    WheelState.options.forEach((o, i) => {
        const dotColor = colors[i % colors.length];
        html += `<li class="flex items-center justify-between p-3 border border-outline-variant rounded-lg hover:bg-surface-container transition-colors group">
          <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full" style="background:${dotColor}"></div>
            <span class="font-body-md">${esc(o.text)}</span>
          </div>
          <span onclick="deleteOption('${o.id}')" class="material-symbols-outlined text-on-surface-variant opacity-0 group-hover:opacity-100 cursor-pointer hover:text-error transition-all">delete</span>
        </li>`;
    });
    list.innerHTML = html;
}

/* ===================== CRUD ===================== */
async function addOption() {
    const input = document.getElementById('option-input');
    const v = input.value.trim();
    if (!v) return;

    const tripId = await loadTripId();
    if (!tripId) { toast('Không xác định được chuyến đi'); return; }

    try {
        const res = await fetch('index.php?route=api/wheel/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripId, text: v })
        });
        if (res.ok) {
            input.value = '';
            await loadOptions();
        } else {
            toast('Lỗi khi thêm lựa chọn');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function deleteOption(id) {
    const tripId = await loadTripId();
    if (!tripId) return;
    try {
        const res = await fetch('index.php?route=api/wheel/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripId, option_id: id })
        });
        if (res.ok) await loadOptions();
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function clearAllOptions() {
    if (WheelState.options.length === 0) return;
    if (!confirm('Xóa tất cả lựa chọn trong vòng quay?')) return;
    const tripId = await loadTripId();
    if (!tripId) return;
    try {
        const res = await fetch('index.php?route=api/wheel/clear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripId })
        });
        if (res.ok) {
            toast('Đã xóa tất cả lựa chọn');
            await loadOptions();
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

/* ===================== WHEEL DRAWING & SPIN ===================== */
function drawWheel() {
    const options = WheelState.options;
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = centerX - 20;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (options.length === 0) {
        ctx.beginPath();
        ctx.fillStyle = '#e9eef6';
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fill();
        ctx.fillStyle = '#76786d';
        ctx.font = '14px Inter';
        ctx.textAlign = 'center';
        ctx.fillText('Thêm lựa chọn để quay', centerX, centerY);
        return;
    }

    const sliceAngle = (2 * Math.PI) / options.length;
    options.forEach((option, i) => {
        const angle = i * sliceAngle;
        const fillColor = colors[i % colors.length];

        ctx.beginPath();
        ctx.fillStyle = fillColor;
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, angle, angle + sliceAngle);
        ctx.closePath();
        ctx.fill();

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(angle + sliceAngle / 2);
        ctx.textAlign = 'right';
        ctx.fillStyle = (fillColor === "#D8D3B3") ? '#161c21' : '#ffffff';
        ctx.font = 'bold 14px Inter';
        // Truncate long text to avoid overflow
        let label = option.text;
        if (label.length > 18) label = label.slice(0, 16) + '…';
        ctx.fillText(label, radius - 30, 5);
        ctx.restore();
    });
}

function spin() {
    if (WheelState.isSpinning) return;
    if (WheelState.options.length === 0) {
        toast('Hãy thêm ít nhất 1 lựa chọn trước khi quay');
        return;
    }
    if (WheelState.options.length === 1) {
        toast('Cần ít nhất 2 lựa chọn để quay cho công bằng nhé');
    }

    WheelState.isSpinning = true;
    document.getElementById('result-text').classList.add('opacity-0');

    const extraSpins = 5 + Math.random() * 5;
    const spinAmount = extraSpins * 2 * Math.PI;
    WheelState.currentRotation += spinAmount;

    canvas.style.transform = `rotate(${WheelState.currentRotation}rad)`;

    setTimeout(() => {
        WheelState.isSpinning = false;
        const options = WheelState.options;
        const actualRotation = WheelState.currentRotation % (2 * Math.PI);
        const sliceAngle = (2 * Math.PI) / options.length;

        const pointerAngle = (3 * Math.PI / 2 - actualRotation) % (2 * Math.PI);
        const correctedAngle = pointerAngle < 0 ? pointerAngle + 2 * Math.PI : pointerAngle;
        const index = Math.floor(correctedAngle / sliceAngle);

        const winner = options[index] ? options[index].text : '???';

        const resultEl = document.getElementById('result-text');
        resultEl.innerHTML = `🎉 Đi thôi: <span class="underline font-bold">${esc(winner)}</span>`;
        resultEl.classList.remove('opacity-0');

        document.getElementById('modal-winner').textContent = winner;
        const modal = document.getElementById('success-modal');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }, 4000);
}

function closeWheelModal() {
    document.getElementById('success-modal').classList.add('opacity-0', 'pointer-events-none');
}

document.getElementById('spin-btn').addEventListener('click', spin);

/* ===================== INIT ===================== */
loadTripId();
loadOptions();
</script>

<?php include __DIR__ . '/layouts/layout_footer.php'; ?>
