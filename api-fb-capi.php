<?php
/**
 * Facebook Conversions API (CAPI) - InitiateCheckout
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('FB_PIXEL_ID', '1051805194224329');
define('FB_ACCESS_TOKEN', 'EAAPmwKtLZBdQBSZAZAI6gcBuUesIaGn4hcjE4h4xQlNiUoLNEPZADZCZCCZCZCVTNyvH4stpMJPUFvHQS2wOLLrIBASZCdkWqsBTMZBcHX9P4FE6TJmWZApwcFt2f50H07wJdZCVFOdcfjm3zXXxHNR8Wvmh8dlSJxfyp8VVZADkotJoOJZASdOSRr2hWHOMH8UH0YXQZDZD');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido.']);
    exit();
}

$email = isset($data['email']) ? trim(strtolower($data['email'])) : '';
$phone = isset($data['phone']) ? preg_replace('/\D/', '', $data['phone']) : '';
if (strlen($phone) >= 10 && substr($phone, 0, 2) !== '55') {
    $phone = '55' . $phone; // Adiciona o código do Brasil se não tiver
}

$hashedEmail = $email ? hash('sha256', $email) : '';
$hashedPhone = $phone ? hash('sha256', $phone) : '';

$userData = [
    'client_ip_address' => $_SERVER['REMOTE_ADDR'],
    'client_user_agent' => $_SERVER['HTTP_USER_AGENT']
];

if ($hashedEmail) $userData['em'] = [$hashedEmail];
if ($hashedPhone) $userData['ph'] = [$hashedPhone];

$eventData = [
    'data' => [
        [
            'event_name' => 'InitiateCheckout',
            'event_time' => time(),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'BRL',
                'value' => isset($data['totalAmount']) ? (float)$data['totalAmount'] : 19.90
            ]
        ]
    ]
];

$ch = curl_init("https://graph.facebook.com/v19.0/" . FB_PIXEL_ID . "/events?access_token=" . FB_ACCESS_TOKEN);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($eventData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode(['success' => true, 'response' => json_decode($response, true), 'code' => $httpCode]);
