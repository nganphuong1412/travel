</main> <!-- End Content -->

<div class="toast" id="toast"></div>

<script>
/* ===================== GLOBAL HELPERS ===================== */
function esc(s){
    return (s === undefined || s === null) ? '' : String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function uid(){
    return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
}

function slugify(s){
    return (s || '').trim().toLowerCase().normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function money(n){
    n = Number(n) || 0;
    return n.toLocaleString('vi-VN') + ' đ';
}

function fmtDate(d){
    if(!d) return '';
    const dt = new Date(d + 'T00:00:00');
    if(isNaN(dt)) return d;
    return dt.toLocaleDateString('vi-VN', {day: '2-digit', month: '2-digit'});
}

function toast(msg){
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(window._tt);
    window._tt = setTimeout(() => t.classList.remove('show'), 1800);
}

function copyCode(){
    const codeEl = document.getElementById('header-trip-code');
    if (!codeEl) return;
    const code = codeEl.textContent.trim();
    navigator.clipboard?.writeText(code).then(() => toast('Đã chép mã: ' + code)).catch(() => toast('Mã: ' + code));
}

function toggleTripDropdown(){
    const el = document.getElementById('trip-dropdown');
    if (!el) return;
    el.classList.toggle('hidden');
}

function openTripModal(){
    const modal = document.getElementById('trip-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('trip-dropdown')?.classList.add('hidden');
    setTimeout(() => document.getElementById('trip-code-input')?.focus(), 0);
}

function closeTripModal(){
    const modal = document.getElementById('trip-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function toggleSidebar(){
    document.body.classList.toggle('sidebar-open');
}

document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('aside[data-sidebar]');
    if (!sidebar) return;
    if (!document.body.classList.contains('sidebar-open')) return;
    if (sidebar.contains(e.target) || e.target.closest('.mobile-sidebar-toggle')) return;
    document.body.classList.remove('sidebar-open');
});

// Clear the active trip from session.
function switchTrip(){
    window.location.href = 'index.php?route=logout';
}

// Switch the active trip in session to one of the user's other trips.
async function switchToTrip(code, name){
    try {
        const res = await fetch('index.php?route=api/session-trip', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_code: code, trip_name: name })
        });
        if (res.ok) {
            window.location.href = 'index.php?route=group&trip=' + encodeURIComponent(code);
        } else {
            toast('Không thể chuyển sang nhóm này');
        }
    } catch(e) {
        toast('Lỗi kết nối');
    }
}

async function submitTripModal(){
    const codeRaw = document.getElementById('trip-code-input')?.value.trim() || '';
    const tripName = document.getElementById('trip-name-input')?.value.trim() || '';
    if (!codeRaw) {
        toast('Nhập mã nhóm');
        return;
    }

    const code = slugify(codeRaw);
    if (!code) {
        toast('Mã nhóm không hợp lệ');
        return;
    }

    try {
        let tripRes = await fetch('index.php?route=api/trip&code=' + encodeURIComponent(code));
        let tripData = null;

        if (tripRes.status === 404) {
            const createRes = await fetch('index.php?route=api/trip/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, name: tripName || codeRaw })
            });
            const createData = await createRes.json();
            if (!createRes.ok) {
                toast(createData.error || 'Không thể tạo nhóm mới');
                return;
            }
            tripRes = await fetch('index.php?route=api/trip&code=' + encodeURIComponent(code));
        }

        tripData = await tripRes.json();
        if (!tripRes.ok) {
            toast(tripData.error || 'Không tải được thông tin nhóm');
            return;
        }

        const joinRes = await fetch('index.php?route=api/trip/add-member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripData.id, name: <?= json_encode($_SESSION['fullname'] ?? '') ?> })
        });
        if (!joinRes.ok) {
            toast('Không thể tham gia nhóm');
            return;
        }

        const sessionRes = await fetch('index.php?route=api/session-trip', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_code: tripData.code, trip_name: tripData.name })
        });

        if (sessionRes.ok) {
            window.location.href = 'index.php?route=group&trip=' + encodeURIComponent(tripData.code);
        } else {
            toast('Không thể chuyển nhóm');
        }
    } catch (e) {
        console.error(e);
        toast('Lỗi kết nối');
    }
}
</script>
</body>
</html>
