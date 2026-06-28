<?php include __DIR__ . '/layouts/layout_guest_header.php'; ?>

<div class="w-full max-w-md">
  <div class="flex items-baseline gap-2 mb-8">
    <span class="font-display-lg text-matcha">Đi Đâu</span>
    <span class="text-on-surface-variant text-[14px]">đăng nhập · chọn nhóm · lập kế hoạch</span>
  </div>

  <div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm flex flex-col gap-4">
    <div class="flex flex-col gap-1.5">
      <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Tên đăng nhập</label>
      <input id="inp-name" placeholder="VD: nguyet" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-surface-container-low focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all">
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="font-label-md text-on-surface-variant uppercase tracking-wide">Mật khẩu</label>
      <input id="inp-password" type="password" placeholder="Nhập mật khẩu" class="px-4 py-3 border border-outline-variant rounded-lg text-[15px] bg-surface-container-low focus:border-matcha focus:ring-1 focus:ring-matcha outline-none transition-all">
    </div>
    <button onclick="handleLogin()" class="w-full py-3.5 bg-matcha text-white font-label-lg rounded-lg hover:opacity-90 transition-all active:scale-[0.98] shadow-md uppercase tracking-wider text-[14px] flex items-center justify-center gap-2">
      <span class="material-symbols-outlined">login</span>
      Đăng nhập
    </button>
    <a href="index.php?route=register" class="w-full py-3.5 border border-outline-variant text-on-surface font-label-lg rounded-lg hover:border-matcha hover:text-matcha transition-all active:scale-[0.98] shadow-sm uppercase tracking-wider text-[14px] flex items-center justify-center gap-2">
      <span class="material-symbols-outlined">person_add</span>
      Đăng ký tài khoản
    </a>
    <p class="text-[13px] text-on-surface-variant leading-relaxed">
      Dùng tên và mật khẩu để vào hệ thống. Sau khi đăng nhập, bạn chọn hoặc đổi nhóm bằng nút <b class="text-on-surface">Thêm / đổi nhóm</b> ở trang chính.
    </p>
  </div>
</div>

<script>
async function handleLogin() {
    const name = document.getElementById('inp-name').value.trim();
    const password = document.getElementById('inp-password').value;

    if (!name) { toast('Nhập tên đăng nhập'); return; }
    if (!password) { toast('Nhập mật khẩu'); return; }

    try {
        const res = await fetch('index.php?route=api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, password })
        });
        const data = await res.json();
        if (!res.ok) {
            toast(data.error || 'Đăng nhập thất bại');
            return;
        }

        window.location.href = 'index.php?route=group';
    } catch (e) {
        console.error(e);
        toast('Đã có lỗi xảy ra, vui lòng thử lại');
    }
}
</script>

<?php include __DIR__ . '/layouts/layout_guest_footer.php'; ?>
