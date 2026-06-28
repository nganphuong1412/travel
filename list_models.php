<?php
/**
 * Standalone diagnostic script — chạy file này độc lập (không qua router của
 * app) để gọi trực tiếp Gemini's ListModels endpoint và in ra TOÀN BỘ danh
 * sách model mà API key của bạn thực sự có quyền dùng, kèm các phương thức
 * (generateContent, v.v.) mà mỗi model hỗ trợ.
 *
 * Cách dùng:
 *   1. Copy file này vào cùng thư mục với .env (hoặc sửa đường dẫn $envPath).
 *   2. Chạy: php list_models.php
 *   3. Đọc danh sách model trả về, chọn 1 tên có "generateContent" trong
 *      supportedGenerationMethods, rồi cập nhật GEMINI_MODEL trong .env
 *      thành đúng tên đó (không có tiền tố "models/").
 */

$envPath = __DIR__ . '/.env';
$apiKey = '';

if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, 'GEMINI_API_KEY') === 0) {
            $parts = explode('=', $line, 2);
            $apiKey = trim($parts[1] ?? '', " \t\"'");
            break;
        }
    }
}

if ($apiKey === '') {
    fwrite(STDERR, "Không tìm thấy GEMINI_API_KEY trong $envPath. Hãy sửa biến \$apiKey trực tiếp trong file này.\n");
    exit(1);
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models?pageSize=100';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['x-goog-api-key: ' . $apiKey],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

echo "HTTP status: $status\n";
if ($curlErr) {
    echo "cURL error: $curlErr\n";
}
echo "----------------------------------------\n";

if ($response === false) {
    echo "Không nhận được phản hồi từ Google.\n";
    exit(1);
}

$data = json_decode($response, true);

if ($status !== 200 || !isset($data['models'])) {
    echo "Phản hồi thô từ Google (không phải danh sách model hợp lệ):\n";
    echo $response . "\n";
    exit(1);
}

echo "Tìm thấy " . count($data['models']) . " model khả dụng cho API key này:\n\n";

foreach ($data['models'] as $model) {
    $name = str_replace('models/', '', $model['name'] ?? '?');
    $methods = $model['supportedGenerationMethods'] ?? [];
    $supportsGenerate = in_array('generateContent', $methods) ? '✅ generateContent' : '❌ không hỗ trợ generateContent';
    echo "- $name  ($supportsGenerate)\n";
}

echo "\n----------------------------------------\n";
echo "==> Hãy chọn 1 tên model có ✅ generateContent ở trên, dùng đúng tên đó\n";
echo "    (không kèm 'models/') cho GEMINI_MODEL trong file .env.\n";