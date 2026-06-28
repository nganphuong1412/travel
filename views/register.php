<?php include __DIR__ . '/layouts/layout_guest_header.php'; ?>

<style>
@keyframes shimmerMove {
  0% { transform: translateX(-130%) skewX(-18deg); opacity: 0; }
  15% { opacity: .55; }
  50% { opacity: .45; }
  100% { transform: translateX(140%) skewX(-18deg); opacity: 0; }
}

@keyframes panelBreath {
  0%, 100% { transform: translate3d(0, 0, 0); }
  50% { transform: translate3d(0, -3px, 0); }
}

.flip-stage {
  perspective: 1600px;
}

.flip-card {
  position: relative;
  height: 100%;
  min-height: 620px;
  transform-style: preserve-3d;
  transition: transform .9s cubic-bezier(.2,.72,.18,1), box-shadow .35s ease;
  box-shadow: 0 16px 34px rgba(49, 19, 0, .10), 0 4px 10px rgba(49, 19, 0, .06);
}

.flip-card.is-flipped {
  transform: rotateY(180deg);
}

.flip-face {
  position: absolute;
  inset: 0;
  border-radius: 1.5rem;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  overflow: hidden;
}

.flip-front {
  background:
    linear-gradient(135deg, rgba(238,245,220,.70), rgba(255,251,242,.56)),
    url('đki.jpg') center/cover no-repeat;
}

.flip-front::before {
  content: "";
  position: absolute;
  inset: 0;
  background:
    linear-gradient(to bottom right, rgba(255,255,255,.42), rgba(255,255,255,0) 42%),
    radial-gradient(circle at top right, rgba(255,255,255,.25), transparent 28%);
}

.flip-back {
  transform: rotateY(180deg);
  background:
    radial-gradient(circle at 20% 20%, rgba(255,255,255,.16), transparent 28%),
    radial-gradient(circle at 80% 18%, rgba(255,255,255,.08), transparent 22%),
    linear-gradient(160deg, #20261a, #596743);
}

.flip-back::before {
  content: "";
  position: absolute;
  top: -20%;
  left: -45%;
  width: 40%;
  height: 140%;
  pointer-events: none;
  background: linear-gradient(120deg, transparent, rgba(255,255,255,.45), transparent);
  animation: shimmerMove 7s ease-in-out infinite;
}

.landmark-photo {
  box-shadow: 0 20px 50px rgba(0, 0, 0, .22);
}

.register-form-panel {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(248,251,245,.98));
  border: 1px solid rgba(198, 200, 187, .9);
  box-shadow: 0 14px 36px rgba(49, 19, 0, .08);
  animation: panelBreath 9s ease-in-out infinite;
}

.register-form-panel::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 1rem;
  pointer-events: none;
  background:
    radial-gradient(circle at top left, rgba(213, 232, 173, .45), transparent 42%),
    radial-gradient(circle at bottom right, rgba(255, 255, 255, .7), transparent 38%);
  opacity: .85;
}

.register-form-panel::after {
  content: "";
  position: absolute;
  top: -20%;
  left: -45%;
  width: 40%;
  height: 140%;
  pointer-events: none;
  background: linear-gradient(120deg, transparent, rgba(255,255,255,.52), transparent);
  transform: skewX(-18deg);
  animation: shimmerMove 7s ease-in-out infinite;
}

.register-form-panel > * {
  position: relative;
  z-index: 1;
}
</style>

<div class="w-full max-w-5xl">
  <div class="grid gap-6 lg:grid-cols-[1fr_1.05fr] items-stretch">
    <div class="flip-stage h-full min-h-[620px]">
      <div id="destinationFlipCard" class="flip-card cursor-pointer border border-outline-variant rounded-3xl" onclick="toggleDestinationCard()">
        <div class="flip-face flip-front p-8">
          <div class="absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(rgba(49,19,0,.35) 1px, transparent 1px); background-size: 22px 22px;"></div>
          <div class="relative z-10 h-full flex flex-col justify-between">
            <div>
              <p class="text-[12px] uppercase tracking-[0.3em] text-[#243016] font-label-md font-semibold">Tạo tài khoản</p>
              <h1 class="font-display-lg text-[#241000] text-[40px] leading-tight mt-3 drop-shadow-[0_1px_2px_rgba(255,255,255,.55)]">Bắt đầu hành trình của bạn</h1>
              <p class="text-[15px] text-[#302414] leading-7 mt-4 max-w-[34ch] drop-shadow-[0_1px_1px_rgba(255,255,255,.45)]">
                Tạo tài khoản để lưu nhóm, lịch trình, chi phí và các địa điểm đã ghé qua. Sau khi đăng ký, bạn sẽ vào thẳng hệ thống.
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <span class="px-3 py-2 rounded-full border border-outline-variant bg-white/90 text-[13px] text-[#2f381f] shadow-sm">Lưu nhóm</span>
              <span class="px-3 py-2 rounded-full border border-outline-variant bg-white/90 text-[13px] text-[#2f381f] shadow-sm">Lưu lịch trình</span>
              <span class="px-3 py-2 rounded-full border border-outline-variant bg-white/90 text-[13px] text-[#2f381f] shadow-sm">Đồng bộ dữ liệu</span>
            </div>
            <div class="mt-4 text-[12px] text-[#2f381f]/80 uppercase tracking-[0.25em]">
              Bấm vào thẻ để mở một điểm đến ngẫu nhiên
            </div>
          </div>
        </div>

        <div id="destinationBack" class="flip-face flip-back p-5 sm:p-6">
          <div class="relative z-10 h-full flex flex-col">
            <div class="flex items-center justify-between text-white/85 text-[12px] uppercase tracking-[0.28em]">
              <span>Điểm đến ngẫu nhiên</span>
              <span class="material-symbols-outlined text-[18px]">travel_explore</span>
            </div>
            <div class="mt-4 rounded-[1.75rem] overflow-hidden border border-white/15 landmark-photo h-[500px] bg-black/10 relative">
              <img id="landmarkImage" alt="Địa danh" class="w-full h-full object-cover" src="" onerror="this.onerror=null;this.src='đki.jpg';" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/78 via-black/22 to-transparent"></div>
              <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6 text-white">
                <p id="landmarkName" class="text-[12px] uppercase tracking-[0.3em] text-white/80 font-label-md"></p>
                <h2 id="landmarkTitle" class="font-display-lg text-[32px] leading-tight mt-2"></h2>
                <div class="mt-5 inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/14 backdrop-blur-md border border-white/15 text-[12px] text-white/90">
                  <span class="material-symbols-outlined text-[16px]">travel_explore</span>
                  <span>Bấm lại để đổi địa danh khác</span>
                </div>
              </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-white/75 text-[12px]">
              <span>Nhấn vào thẻ để quay lại mặt trước</span>
              <span class="material-symbols-outlined text-[18px]">rotate_right</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="register-form-panel rounded-2xl p-6 h-full">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-10 h-10 rounded-full bg-matcha text-white flex items-center justify-center shadow-md shadow-matcha/20">
          <span class="material-symbols-outlined">person_add</span>
        </div>
        <div>
          <h2 class="font-headline-md text-[24px] text-on-surface leading-tight">Đăng ký tài khoản</h2>
          <p class="text-[13px] text-on-surface-variant">Điền thông tin để tạo tài khoản mới</p>
        </div>
      </div>

      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Tên hiển thị</label>
          <input id="reg-fullname" placeholder="VD: Nguyễn Văn A" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-white/85 focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all shadow-sm">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Tên đăng nhập</label>
          <input id="reg-username" placeholder="VD: nguyenvana" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-white/85 focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all shadow-sm">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Mật khẩu</label>
          <input id="reg-password" type="password" placeholder="Nhập mật khẩu" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-white/85 focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all shadow-sm">
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Nhập lại mật khẩu</label>
          <input id="reg-password-confirm" type="password" placeholder="Nhập lại mật khẩu" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-white/85 focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all shadow-sm">
        </div>

        <button onclick="handleRegister()" class="w-full py-3.5 bg-matcha text-white font-label-lg rounded-lg hover:opacity-90 transition-all active:scale-[0.98] shadow-md uppercase tracking-wider text-[14px] flex items-center justify-center gap-2">
          <span class="material-symbols-outlined">person_add</span>
          Tạo tài khoản
        </button>

        <a href="index.php?route=home" class="w-full py-3.5 border border-outline-variant text-on-surface font-label-lg rounded-lg hover:border-matcha hover:text-matcha transition-all active:scale-[0.98] shadow-sm uppercase tracking-wider text-[14px] flex items-center justify-center gap-2">
          <span class="material-symbols-outlined">login</span>
          Quay lại đăng nhập
        </a>
      </div>
    </div>
  </div>
</div>

<script>
const LANDMARKS = [
    {
        name: 'Paris, France',
        title: 'Thành phố Ánh sáng',
        image: 'https://source.unsplash.com/featured/1200x1600/?paris,eiffel,tower,cityscape'
    },
    {
        name: 'Kyoto, Japan',
        title: 'Mùa tĩnh lặng',
        image: 'https://source.unsplash.com/featured/1200x1600/?kyoto,temple,garden,landscape'
    },
    {
        name: 'Hạ Long, Việt Nam',
        title: 'Kỳ quan giữa biển',
        image: 'https://source.unsplash.com/featured/1200x1600/?halong-bay,vietnam,sea,islands'
    },
    {
        name: 'Santorini, Greece',
        title: 'Bầu trời và biển xanh',
        image: 'https://source.unsplash.com/featured/1200x1600/?santorini,greek,island,sea'
    },
    {
        name: 'Bali, Indonesia',
        title: 'Nhịp sống mềm',
        image: 'https://source.unsplash.com/featured/1200x1600/?bali,indonesia,beach,palm'
    }
];

let isFlipped = false;
let currentLandmark = null;

function pickLandmark() {
    currentLandmark = LANDMARKS[Math.floor(Math.random() * LANDMARKS.length)];
    document.getElementById('landmarkName').textContent = currentLandmark.name;
    document.getElementById('landmarkTitle').textContent = currentLandmark.title;
    document.getElementById('landmarkImage').src = currentLandmark.image;
}

function toggleDestinationCard() {
    const card = document.getElementById('destinationFlipCard');
    isFlipped = !isFlipped;
    card.classList.toggle('is-flipped', isFlipped);
    if (isFlipped) {
        pickLandmark();
    }
}

async function handleRegister() {
    const fullname = document.getElementById('reg-fullname').value.trim();
    const username = document.getElementById('reg-username').value.trim();
    const password = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-password-confirm').value;

    if (!fullname) { toast('Nhập tên hiển thị'); return; }
    if (!username) { toast('Nhập tên đăng nhập'); return; }
    if (!password) { toast('Nhập mật khẩu'); return; }
    if (password.length < 6) { toast('Mật khẩu nên có ít nhất 6 ký tự'); return; }
    if (password !== confirmPassword) { toast('Mật khẩu nhập lại không khớp'); return; }

    try {
        const res = await fetch('index.php?route=api/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fullname, username, password })
        });
        const data = await res.json();
        if (!res.ok) {
            toast(data.error || 'Đăng ký thất bại');
            return;
        }

        toast('Đăng ký thành công');
        window.location.href = 'index.php?route=group';
    } catch (e) {
        console.error(e);
        toast('Đã có lỗi xảy ra, vui lòng thử lại');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    pickLandmark();
});
</script>

<?php include __DIR__ . '/layouts/layout_guest_footer.php'; ?>
