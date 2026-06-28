<?php include __DIR__ . '/layouts/layout_header.php'; ?>

<?php
$tripName = $_SESSION['trip_name'] ?? ($_SESSION['trip_code'] ?? 'nhóm hiện tại');
$tripCode = $_SESSION['trip_code'] ?? '';
?>

<div class="max-w-[1200px] mx-auto">
  <section class="grid grid-cols-12 gap-gutter">
    <div class="col-span-12 lg:col-span-4">
      <div class="sticky top-24 bg-surface-container-lowest border border-outline-variant rounded-2xl overflow-hidden shadow-sm">
        <div class="p-6 bg-primary-fixed border-b border-outline-variant">
          <p class="text-[12px] uppercase tracking-[0.25em] text-on-surface-variant font-label-md">Tư vấn du lịch</p>
          <h1 class="font-headline-lg text-[28px] text-espresso mt-2">Chatbot hỗ trợ chuyến đi</h1>
          <p class="text-[14px] text-on-surface-variant mt-3 leading-6">
            Gợi ý lịch trình, chi phí, phương tiện, checklist và mẹo di chuyển cho nhóm <b><?= htmlspecialchars($tripName) ?></b>.
          </p>
        </div>
        <div class="p-6 space-y-3">
          <p class="text-[13px] text-on-surface-variant">
            Khóa Gemini được giữ ở backend qua <code class="px-1.5 py-0.5 rounded bg-surface-container-low">.env</code>.
          </p>
          <div class="flex flex-wrap gap-2">
            <button type="button" onclick="sendPrompt('Gợi ý lịch trình 3 ngày cho nhóm tôi')" class="px-3 py-2 rounded-full border border-outline-variant text-[13px] hover:border-matcha hover:text-matcha transition-colors">Lịch trình 3 ngày</button>
            <button type="button" onclick="sendPrompt('Gợi ý checklist đi du lịch đầy đủ')" class="px-3 py-2 rounded-full border border-outline-variant text-[13px] hover:border-matcha hover:text-matcha transition-colors">Checklist</button>
            <button type="button" onclick="sendPrompt('Tư vấn cách chia chi phí hợp lý cho nhóm')" class="px-3 py-2 rounded-full border border-outline-variant text-[13px] hover:border-matcha hover:text-matcha transition-colors">Chi phí</button>
            <button type="button" onclick="sendPrompt('Tư vấn phương tiện đi lại phù hợp')" class="px-3 py-2 rounded-full border border-outline-variant text-[13px] hover:border-matcha hover:text-matcha transition-colors">Phương tiện</button>
          </div>
          <div class="rounded-xl border border-dashed border-outline-variant p-4 bg-white">
            <p class="text-[12px] uppercase tracking-wider text-on-surface-variant font-label-md mb-2">Nhóm hiện tại</p>
            <p class="font-headline-md text-[16px] text-on-surface"><?= htmlspecialchars($tripName) ?></p>
            <p class="text-[12px] text-on-surface-variant font-data-mono mt-1"><?= htmlspecialchars($tripCode) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-8">
      <div class="bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-sm overflow-hidden flex flex-col min-h-[74vh]">
        <div class="px-6 py-4 border-b border-outline-variant bg-white flex items-center justify-between">
          <div>
            <p class="text-[12px] uppercase tracking-widest text-on-surface-variant font-label-md">Hỏi nhanh</p>
            <h2 class="font-headline-md text-[20px] text-primary">Trợ lý tư vấn cho chuyến đi</h2>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" onclick="testGemini()" class="px-3 py-2 rounded-lg border border-matcha text-[13px] text-matcha hover:bg-matcha/5">Kiểm tra Gemini</button>
            <button type="button" onclick="resetChat()" class="px-3 py-2 rounded-lg border border-outline-variant text-[13px] text-on-surface-variant hover:border-matcha hover:text-matcha">Làm mới</button>
          </div>
        </div>

        <div class="px-6 pt-4">
          <div id="gemini-status" class="hidden rounded-xl border px-4 py-3 text-[13px]"></div>
        </div>

        <div id="chat-list" class="flex-1 p-6 space-y-4 overflow-y-auto custom-scrollbar bg-surface">
        </div>

        <div class="p-4 border-t border-outline-variant bg-white">
          <div class="flex gap-2">
            <textarea id="chat-input" rows="2" placeholder="Nhập câu hỏi của bạn..." class="flex-1 resize-none border border-outline-variant rounded-xl px-4 py-3 text-[14px] bg-surface-container-low focus:border-matcha focus:ring-1 focus:ring-matcha outline-none"></textarea>
            <button type="button" onclick="sendCurrentMessage()" class="px-5 py-3 rounded-xl bg-matcha text-white font-label-lg min-w-[110px]">Gửi</button>
          </div>
          <p class="text-[12px] text-on-surface-variant mt-2">Enter để gửi, Shift + Enter để xuống dòng.</p>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
const ChatState = {
  messages: [
    {
      role: 'assistant',
      text: 'Mình là trợ lý tư vấn chuyến đi. Hãy hỏi mình về lịch trình, chi phí, checklist, phương tiện hoặc cách tổ chức chuyến đi của nhóm bạn.'
    }
  ],
  sending: false,
  fallbackActive: false,
  fallbackReason: '',
  geminiTest: null
};

function escapeHtml(text) {
  return (text === undefined || text === null) ? '' : String(text).replace(/[&<>"']/g, c => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[c]));
}

function localFallbackReply(message) {
  const text = (message || '').toLowerCase();
  const tripName = <?= json_encode($tripName) ?>;
  const currentTrip = <?= json_encode($tripCode) ?>;
  let reply = `Mình chưa gọi được Gemini lúc này, nhưng vẫn hỗ trợ nhanh cho ${tripName}.`;

  if (text.includes('lịch trình') || text.includes('itinerary') || text.includes('ngày')) {
    reply += ' Bạn có thể chia chuyến đi theo nhịp: sáng di chuyển, trưa ăn, chiều tham quan, tối nghỉ hoặc sinh hoạt nhóm.';
  } else if (text.includes('checklist') || text.includes('đồ') || text.includes('do') || text.includes('mang theo')) {
    reply += ' Checklist cơ bản: giấy tờ, sạc, pin dự phòng, tiền mặt, thuốc cá nhân, áo khoác mỏng, đồ vệ sinh cá nhân, nước uống và áo mưa nếu cần.';
  } else if (text.includes('chi phí') || text.includes('chi phi') || text.includes('tiền') || text.includes('tien') || text.includes('budget')) {
    reply += ' Gợi ý chia chi phí: tách theo nhóm lớn như di chuyển, lưu trú, ăn uống, vé tham quan và quỹ dự phòng 10-15%.';
  } else if (text.includes('phương tiện') || text.includes('phuong tien') || text.includes('đi lại') || text.includes('di lai') || text.includes('máy bay') || text.includes('may bay') || text.includes('xe') || text.includes('tàu') || text.includes('tau')) {
    reply += ' Chọn phương tiện theo quãng đường: gần thì xe khách/xe riêng, xa thì máy bay. Nếu đi nhóm đông, nên so sánh tổng chi phí và giờ khởi hành.';
  } else {
    reply += ' Bạn có thể hỏi mình về lịch trình, checklist, chi phí, phương tiện hoặc cách tổ chức chuyến đi.';
  }

  if (currentTrip) {
    reply += ` Nhóm hiện tại: ${tripName} (${currentTrip}).`;
  }

  return reply;
}

function renderMessages() {
  const list = document.getElementById('chat-list');
  if (!list) return;

  let html = '';
  if (ChatState.fallbackActive) {
    html += `
      <div class="flex justify-center">
        <div class="max-w-[92%] inline-flex flex-col gap-1 px-3 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 text-[12px] font-label-md">
          <div class="inline-flex items-center gap-2">
            <span class="material-symbols-outlined text-[14px]">info</span>
            Đang dùng trả lời dự phòng
          </div>
          ${ChatState.fallbackReason ? `<div class="text-[11px] text-amber-700/90 leading-5">${escapeHtml(ChatState.fallbackReason)}</div>` : ''}
        </div>
      </div>`;
  }
  ChatState.messages.forEach(item => {
    const isUser = item.role === 'user';
    html += `
      <div class="flex ${isUser ? 'justify-end' : 'justify-start'}">
        <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm ${isUser ? 'bg-matcha text-white' : 'bg-white border border-outline-variant text-on-surface'}">
          <p class="text-[13px] leading-6 whitespace-pre-wrap">${escapeHtml(item.text)}</p>
        </div>
      </div>`;
  });

  if (ChatState.sending) {
    html += `
      <div class="flex justify-start">
        <div class="max-w-[85%] rounded-2xl px-4 py-3 bg-white border border-outline-variant text-on-surface">
          <p class="text-[13px] italic text-on-surface-variant">Đang suy nghĩ...</p>
        </div>
      </div>`;
  }

  list.innerHTML = html;
  list.scrollTop = list.scrollHeight;

  renderGeminiStatus();
}

function renderGeminiStatus() {
  const box = document.getElementById('gemini-status');
  if (!box) return;
  const status = ChatState.geminiTest;
  if (!status) {
    box.classList.add('hidden');
    box.textContent = '';
    box.className = 'hidden rounded-xl border px-4 py-3 text-[13px]';
    return;
  }

  box.classList.remove('hidden');
  if (status.ok === null) {
    box.className = 'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-800';
    box.innerHTML = `
      <div class="font-label-lg mb-1">Đang kiểm tra Gemini...</div>
      <div class="text-[12px]">Vui lòng chờ vài giây.</div>
    `;
  } else if (status.ok) {
    box.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-800';
    box.innerHTML = `
      <div class="font-label-lg mb-1">Gemini đang hoạt động</div>
      <div class="text-[12px]">Model: ${escapeHtml(status.model || '')} · Phản hồi thử: ${escapeHtml(status.reply || '')}</div>
    `;
  } else {
    box.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-800';
    box.innerHTML = `
      <div class="font-label-lg mb-1">Không gọi được Gemini</div>
      <div class="text-[12px] leading-5">Model: ${escapeHtml(status.model || '')}</div>
      <div class="text-[12px] leading-5 mt-1">Lý do: ${escapeHtml(status.detail || status.error || 'Không rõ')}</div>
    `;
  }
}

async function sendCurrentMessage() {
  const input = document.getElementById('chat-input');
  const message = input.value.trim();
  if (!message || ChatState.sending) return;

  ChatState.messages.push({ role: 'user', text: message });
  input.value = '';
  ChatState.sending = true;
  renderMessages();

  try {
    const res = await fetch('index.php?route=api/chatbot/send', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message })
    });
    const data = await res.json();
    if (res.ok) {
      ChatState.fallbackActive = data.source === 'fallback';
      ChatState.fallbackReason = data.fallback_reason || '';
      ChatState.messages.push({ role: 'assistant', text: data.reply || localFallbackReply(message) });
    } else {
      ChatState.fallbackActive = true;
      ChatState.fallbackReason = data.error || 'Gemini không phản hồi';
      ChatState.messages.push({ role: 'assistant', text: localFallbackReply(message) });
    }
  } catch (e) {
    ChatState.fallbackActive = true;
    ChatState.fallbackReason = 'Lỗi kết nối tới backend';
    ChatState.messages.push({ role: 'assistant', text: localFallbackReply(message) });
  } finally {
    ChatState.sending = false;
    renderMessages();
  }
}

async function testGemini() {
  ChatState.geminiTest = { ok: null };
  renderGeminiStatus();
  try {
    const res = await fetch('index.php?route=api/chatbot/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    const data = await res.json();
    ChatState.geminiTest = {
      ok: !!data.ok,
      model: data.model || '',
      reply: data.reply || '',
      detail: data.detail || '',
      error: data.error || ''
    };
  } catch (e) {
    ChatState.geminiTest = {
      ok: false,
      error: 'Lỗi kết nối',
      detail: 'Không thể gọi tới backend kiểm tra Gemini'
    };
  } finally {
    renderGeminiStatus();
  }
}

function sendPrompt(text) {
  const input = document.getElementById('chat-input');
  input.value = text;
  sendCurrentMessage();
}

function resetChat() {
  fetch('index.php?route=api/chatbot/reset', { method: 'POST' }).finally(() => {
    ChatState.fallbackActive = false;
    ChatState.fallbackReason = '';
    ChatState.geminiTest = null;
    ChatState.messages = [
      {
        role: 'assistant',
        text: 'Mình là trợ lý tư vấn chuyến đi. Hãy hỏi mình về lịch trình, chi phí, checklist, phương tiện hoặc cách tổ chức chuyến đi của nhóm bạn.'
      }
    ];
    renderMessages();
  });
}

document.getElementById('chat-input')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendCurrentMessage();
  }
});

renderMessages();
</script>

<?php include __DIR__ . '/layouts/layout_footer.php'; ?>
