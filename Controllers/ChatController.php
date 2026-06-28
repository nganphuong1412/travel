<?php
namespace Controllers;

use Config\Env;
use Models\Trip;

class ChatController {
    private function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function showChatbot() {
        $pageTitle = 'Tư vấn';
        include __DIR__ . '/../views/chatbot.php';
    }

    public function sendMessage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $message = trim((string)($input['message'] ?? ''));
        if ($message === '') {
            return $this->jsonResponse(['error' => 'Vui lòng nhập nội dung cần tư vấn'], 400);
        }

        $apiKey = Env::get('GROQ_API_KEY', '');
        if ($apiKey === '') {
            return $this->jsonResponse(['error' => 'Thiếu GROQ_API_KEY trong file .env'], 500);
        }

        $tripCode = $_SESSION['trip_code'] ?? '';
        if (($_SESSION['chat_trip_code'] ?? '') !== $tripCode) {
            $_SESSION['chat_trip_code'] = $tripCode;
            $_SESSION['chat_history'] = [];
        }

        $history = $_SESSION['chat_history'] ?? [];
        $history = array_slice($history, -12);

        $systemPrompt = $this->buildSystemPrompt();

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $item) {
            if (empty($item['role']) || !isset($item['text'])) continue;
            $messages[] = [
                'role'    => $item['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => (string)$item['text'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $model = Env::get('GROQ_MODEL', 'openrouter/free');
        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.7,
            'top_p'       => 0.95,
            'max_tokens'  => 500,
        ];

        $reply = $this->callAI($payload, $aiError);
        $source = 'ai';
        if ($reply === '') {
            $reply = $this->buildFallbackReply($message);
            $source = 'fallback';
            if ($aiError) {
                error_log('[ChatController] AI error: ' . $aiError);
            }
        }

        $history[] = ['role' => 'user',      'text' => $message];
        $history[] = ['role' => 'assistant', 'text' => $reply];
        $_SESSION['chat_history'] = array_slice($history, -16);

        return $this->jsonResponse([
            'success'         => true,
            'reply'           => $reply,
            'source'          => $source,
            'fallback_reason' => $source === 'fallback' ? ($aiError ?: 'AI không trả về nội dung') : '',
        ]);
    }

    public function resetChat() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        unset($_SESSION['chat_history'], $_SESSION['chat_trip_code']);
        return $this->jsonResponse(['success' => true]);
    }

    public function testGroq() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $apiKey = Env::get('GROQ_API_KEY', '');
        if ($apiKey === '') {
            return $this->jsonResponse(['success' => false, 'ok' => false, 'error' => 'Thiếu GROQ_API_KEY'], 500);
        }

        $model = Env::get('GROQ_MODEL', 'openrouter/free');
        $payload = [
            'model'       => $model,
            'messages'    => [['role' => 'user', 'content' => 'Reply with exactly: OK']],
            'temperature' => 0,
            'max_tokens'  => 16,
        ];

        $reply = $this->callAI($payload, $aiError);
        if ($reply === '') {
            return $this->jsonResponse([
                'success' => false,
                'ok'      => false,
                'error'   => 'AI không phản hồi',
                'detail'  => $aiError ?: 'Unknown error',
                'model'   => $model,
            ], 502);
        }

        return $this->jsonResponse(['success' => true, 'ok' => true, 'reply' => $reply, 'model' => $model]);
    }

    /**
     * Gọi OpenRouter (primary) → fallback Groq nếu 429.
     */
    private function callAI(array $payload, &$error = null) {
        $error = null;

        // Primary: OpenRouter
        $orKey = Env::get('GROQ_API_KEY', '');
        $reply = $this->httpPost(
            'https://openrouter.ai/api/v1/chat/completions',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $orKey,
                'HTTP-Referer: https://didau-travel-planner.local',
                'X-Title: Didau Travel Planner',
            ],
            $payload,
            $error
        );

        if ($reply !== '') return $reply;

        // Fallback: Groq trực tiếp khi bị rate limit
        $groqKey = Env::get('GROQ_BACKUP_KEY', '');
        if ($groqKey !== '' && str_contains($error ?? '', '429')) {
            $fallback = $payload;
            $fallback['model'] = 'llama-3.3-70b-versatile';
            $reply = $this->httpPost(
                'https://api.groq.com/openai/v1/chat/completions',
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $groqKey,
                ],
                $fallback,
                $error
            );
        }

        return $reply;
    }

    /**
     * HTTP POST helper dùng chung cho mọi provider.
     */
    private function httpPost($url, array $headers, array $payload, &$error = null) {
        $error = null;
        $json  = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST              => true,
                CURLOPT_RETURNTRANSFER    => true,
                CURLOPT_HTTPHEADER        => $headers,
                CURLOPT_POSTFIELDS        => $json,
                CURLOPT_CONNECTTIMEOUT     => 15,
                CURLOPT_TIMEOUT           => 120,
                CURLOPT_NOSIGNAL          => true,
                CURLOPT_ENCODING          => '',
            ]);
            $response  = curl_exec($ch);
            $status    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $status < 200 || $status >= 300) {
                $error = $curlError ?: ('HTTP ' . $status);
                if (is_string($response) && trim($response) !== '') {
                    $decoded = json_decode($response, true);
                    $error = !empty($decoded['error']['message'])
                        ? $decoded['error']['message'] . ' (HTTP ' . $status . ')'
                        : $error . ' - ' . $response;
                }
                return '';
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", $headers) . "\r\n",
                    'content'       => $json,
                    'timeout'       => 120,
                    'ignore_errors' => true,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $error = 'file_get_contents thất bại (kiểm tra allow_url_fopen)';
                return '';
            }
            foreach ($http_response_header ?? [] as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                    $status = (int)$m[1];
                    if ($status < 200 || $status >= 300) {
                        $decoded = json_decode($response, true);
                        $error = !empty($decoded['error']['message'])
                            ? $decoded['error']['message'] . ' (HTTP ' . $status . ')'
                            : 'HTTP ' . $status . ' - ' . $response;
                        return '';
                    }
                    break;
                }
            }
        }

        $data = json_decode($response, true);
        if (!is_array($data)) { $error = 'Invalid JSON response'; return ''; }
        if (!empty($data['error']['message'])) { $error = $data['error']['message']; return ''; }

        return trim($data['choices'][0]['message']['content'] ?? '');
    }

    private function buildSystemPrompt() {
        $tripCode = $_SESSION['trip_code'] ?? '';
        $tripName = $_SESSION['trip_name'] ?? '';
        $fullname = $_SESSION['fullname'] ?? 'người dùng';

        $context = "Bạn là trợ lý tư vấn du lịch tiếng Việt cho ứng dụng lập kế hoạch chuyến đi. "
                 . "Hãy trả lời ngắn gọn, rõ ràng, hữu ích, ưu tiên checklist, lịch trình, chi phí, phương tiện, và gợi ý thực tế.";

        if ($tripCode !== '') {
            $trip = Trip::getByCode($tripCode);
            if ($trip) {
                $context .= " Người dùng đang ở nhóm '{$trip['name']}' (mã {$trip['code']}). "
                          . "Nhóm có " . count($trip['members'] ?? []) . " thành viên, "
                          . count($trip['itinerary'] ?? []) . " ngày lịch trình, "
                          . count($trip['locations'] ?? []) . " địa điểm đã lưu và "
                          . count($trip['expenses'] ?? []) . " khoản chi.";
            } elseif ($tripName !== '') {
                $context .= " Người dùng đang ở nhóm '{$tripName}' (mã {$tripCode}).";
            }
        }

        $context .= " Gọi người dùng là '{$fullname}' khi phù hợp. "
                  . "Nếu câu hỏi liên quan chuyến đi hiện tại, hãy tận dụng bối cảnh nhóm. "
                  . "Nếu thiếu dữ liệu, hãy nói rõ và gợi ý cách bổ sung.";

        return $context;
    }

    private function buildFallbackReply($message) {
        $lower    = function_exists('mb_strtolower') ? mb_strtolower($message, 'UTF-8') : strtolower($message);
        $tripCode = $_SESSION['trip_code'] ?? '';
        $trip     = $tripCode ? Trip::getByCode($tripCode) : null;
        $tripName = $trip['name'] ?? ($_SESSION['trip_name'] ?? 'nhóm hiện tại');

        $parts = ["Mình chưa gọi được AI lúc này, nhưng vẫn hỗ trợ nhanh cho {$tripName}."];

        if (preg_match('/lịch trình|itinerary|ngày/i', $lower)) {
            $days = $trip ? count($trip['itinerary'] ?? []) : 0;
            $parts[] = $days > 0
                ? "Nhóm hiện có {$days} ngày trong lịch trình. Nhịp gợi ý: sáng đi xa, trưa ăn, chiều tham quan, tối nghỉ."
                : "Bắt đầu bằng 1 ngày mẫu: sáng di chuyển, trưa ăn trưa, chiều tham quan, tối kiểm tra chi phí.";
        } elseif (preg_match('/checklist|đồ|mang/i', $lower)) {
            $parts[] = "Checklist cơ bản: giấy tờ, sạc, pin dự phòng, tiền mặt, thuốc, áo khoác, đồ vệ sinh, nước, áo mưa.";
        } elseif (preg_match('/chi phí|tiền|budget/i', $lower)) {
            $parts[] = "Chia chi phí theo nhóm: di chuyển, lưu trú, ăn uống, vé tham quan, dự phòng 10–15%.";
        } elseif (preg_match('/phương tiện|xe|tàu|máy bay/i', $lower)) {
            $parts[] = "Gần thì xe khách/xe riêng, xa thì máy bay. Nhóm đông nên so sánh tổng chi phí và giờ khởi hành.";
        } else {
            $parts[] = "Bạn có thể hỏi về lịch trình, checklist, chi phí, phương tiện hoặc cách tổ chức chuyến đi.";
        }

        if ($trip) {
            $parts[] = "Dữ liệu nhóm: {$tripName}, " . count($trip['members'] ?? []) . " thành viên, " . count($trip['locations'] ?? []) . " địa điểm đã lưu.";
        }

        return implode(' ', $parts);
    }
}
