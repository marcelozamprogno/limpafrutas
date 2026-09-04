<?php
/**
 * ============================================================================
 * LAVAFRUTAS 360 - SECURE PIX PAYMENT BACKEND ENDPOINT (INVICTUS PAY V2)
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// CONFIGURAÇÕES DA INVICTUS PAY
// ============================================================================
define('INVICTUS_API_TOKEN', '29032003m');

// IMPORTANTE: Insira aqui a URL correta da API da Invictus Pay para gerar o PIX.
define('INVICTUS_API_URL', 'https://api.invictuspayv2.com.br/api/transactions'); // <--- ALTERE AQUI SE DER ERRO

$action = isset($_GET['action']) ? $_GET['action'] : 'create_pix';

if ($action === 'create_pix' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleCreatePix();
} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit();
}

function handleCreatePix() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['name']) || !isset($data['cpf'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos fornecidos.']);
        exit();
    }

    // =========================================================================
    // ESTRUTURA DE DADOS GENÉRICA PARA INVICTUS PAY (API)
    // =========================================================================
    $payload = [
        'customer' => [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'document' => preg_replace('/\D/', '', $data['cpf']),
            'phone'    => preg_replace('/\D/', '', $data['phone'])
        ],
        'payment_method' => 'pix',
        'amount'         => (float) $data['totalAmount'],
        // Offer Hash da sua loja na Invictus
        'offer_hash'     => 'off_01m1q3vsv084pmkekf1jzmtf8e' 
    ];

    $ch = curl_init(INVICTUS_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Api-Key: ' . INVICTUS_API_TOKEN
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    // Se o HTTP code for 200/201 (Sucesso)
    if ($httpCode >= 200 && $httpCode < 300 && $resData) {
        // Tentamos extrair o QR Code dependendo de como a Invictus retorna (qr_code, qrcode, ou pix_code)
        $pixString = isset($resData['qr_code']) ? $resData['qr_code'] : (isset($resData['pix_code']) ? $resData['pix_code'] : null);
        
        if ($pixString) {
            echo json_encode([
                'success'   => true,
                'txid'      => isset($resData['transaction_id']) ? $resData['transaction_id'] : uniqid(),
                'pixCode'   => $pixString,
                'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($pixString)
            ]);
            exit();
        }
    }

    // Retorna falso para cair no "Fallback Demo" no JavaScript e a tela não ficar travada
    echo json_encode([
        'success'   => false,
        'message'   => 'Erro na API da Invictus. Verifique a URL do Endpoint.',
        'debug'     => $resData,
        'httpCode'  => $httpCode
    ]);
}
?>
