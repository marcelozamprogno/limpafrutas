<?php
/**
 * ============================================================================
 * LAVAFRUTAS 360 - SECURE PIX PAYMENT BACKEND (INVICTUS PAY V2)
 * ============================================================================
 * Documentação: https://app.invictuspayv2.com.br/docs/transacoes
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
// CONFIGURAÇÕES DA INVICTUS PAY V2
// ============================================================================
define('INVICTUS_API_KEY', '29032003m');
define('INVICTUS_API_URL', 'https://api.invictuspayv2.com.br/api/v1/transactions');
define('INVICTUS_OFFER_HASH', 'off_01m1q3vsv084pmkekf1jzmtf8e');

$action = isset($_GET['action']) ? $_GET['action'] : 'create_pix';

if ($action === 'create_pix' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleCreatePix();
} elseif ($action === 'check_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    handleCheckStatus();
} else {
    echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    exit();
}

/**
 * Cria a transação PIX na Invictus Pay V2
 */
function handleCreatePix() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['name']) || !isset($data['cpf'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos fornecidos.']);
        exit();
    }

    // Valor em CENTAVOS (ex: R$ 19,90 = 1990)
    $amountCents = (int) round(((float) $data['totalAmount']) * 100);

    // =========================================================================
    // PAYLOAD CONFORME DOCUMENTAÇÃO INVICTUS PAY V2
    // =========================================================================
    $payload = [
        'amount'        => $amountCents,
        'paymentMethod' => 'pix',
        'customer'      => [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'document' => preg_replace('/\D/', '', $data['cpf']),
            'phone'    => preg_replace('/\D/', '', $data['phone'])
        ],
        'items' => [
            [
                'offer_hash' => INVICTUS_OFFER_HASH,
                'quantity'   => isset($data['quantity']) ? (int) $data['quantity'] : 1,
                'amount'     => $amountCents
            ]
        ],
        'pix' => [
            'expirationInSeconds' => 1800  // 30 minutos
        ]
    ];

    $ch = curl_init(INVICTUS_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Api-Key: ' . INVICTUS_API_KEY
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    // Se a requisição teve sucesso (HTTP 200/201)
    if ($httpCode >= 200 && $httpCode < 300 && $resData) {

        // Busca o código PIX "copia e cola" nos campos possíveis da resposta
        $pixCode = findNestedValue($resData, ['qr_code', 'qrcode', 'pix_code', 'pix_qr_code', 'emv', 'brcode', 'copy_paste']);
        // Busca o QR Code (imagem) nos campos possíveis da resposta
        $qrImage = findNestedValue($resData, ['qr_code_url', 'qrcode_url', 'qr_code_image', 'qrcode_image', 'qr_image']);
        // Busca o ID da transação
        $txid = findNestedValue($resData, ['id', 'transaction_id', 'txid', 'hash']);

        if ($pixCode) {
            echo json_encode([
                'success'   => true,
                'txid'      => $txid ?: uniqid('inv_'),
                'pixCode'   => $pixCode,
                'qrCodeUrl' => $qrImage ?: 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($pixCode)
            ]);
            exit();
        }
    }

    // Se chegou aqui, retorna erro com debug para facilitar a identificação
    echo json_encode([
        'success'   => false,
        'message'   => 'Erro ao gerar PIX na Invictus Pay.',
        'debug'     => $resData,
        'httpCode'  => $httpCode
    ]);
}

/**
 * Consulta o status de uma transação
 */
function handleCheckStatus() {
    $txid = isset($_GET['txid']) ? $_GET['txid'] : '';
    if (!$txid) {
        echo json_encode(['status' => 'PENDING']);
        exit();
    }

    $ch = curl_init(INVICTUS_API_URL . '/' . $txid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'X-Api-Key: ' . INVICTUS_API_KEY
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($response, true);

    if ($resData) {
        $status = findNestedValue($resData, ['status', 'payment_status']);
        // Mapeia os status possíveis da Invictus para APPROVED/PENDING
        if ($status && in_array(strtolower($status), ['approved', 'paid', 'completed', 'confirmed'])) {
            echo json_encode(['status' => 'APPROVED']);
            exit();
        }
    }

    echo json_encode(['status' => 'PENDING']);
}

/**
 * Busca recursivamente um valor em um array associativo por múltiplas chaves possíveis
 */
function findNestedValue($data, $keys) {
    if (!is_array($data)) return null;
    
    foreach ($keys as $key) {
        // Busca no nível raiz
        if (isset($data[$key]) && !empty($data[$key])) {
            return $data[$key];
        }
        // Busca um nível abaixo (ex: data.pix.qr_code)
        foreach ($data as $value) {
            if (is_array($value) && isset($value[$key]) && !empty($value[$key])) {
                return $value[$key];
            }
        }
    }
    return null;
}
?>

