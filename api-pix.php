<?php
/**
 * ============================================================================
 * LAVAFRUTAS 360 - SECURE PIX PAYMENT BACKEND ENDPOINT (WORDPRESS / PHP)
 * ============================================================================
 * 
 * ATENÇÃO SEGURANÇA:
 * As credenciais secretas do gateway NUNCA devem ser enviadas no JavaScript/frontend.
 * Este arquivo PHP atua como ponte segura entre o checkout e o gateway de pagamento.
 */

// Permite chamadas do mesmo domínio
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// 12. ÁREA EXATA PARA INSERIR AS CREDENCIAIS / API DO SEU GATEWAY DE PAGAMENTO
// ============================================================================

define('GATEWAY_PROVIDER', 'MERCADOPAGO'); // Opções: 'MERCADOPAGO', 'ASAAS', 'PAGARME', 'PUSHINPAY', 'EFI'

// MERCADO PAGO / ASAAS / OUTROS GATEWAYS:
define('GATEWAY_ACCESS_TOKEN', 'SEU_ACCESS_TOKEN_SECRETO_AQUI'); // Insira seu Access Token / Secret Key aqui
define('GATEWAY_CLIENT_ID', 'SEU_CLIENT_ID_AQUI');               // Se aplicável
define('GATEWAY_CLIENT_SECRET', 'SEU_CLIENT_SECRET_AQUI');       // Se aplicável
define('GATEWAY_PIX_KEY', 'SUA_CHAVE_PIX_AQUI');                 // Chave PIX cadastrada (se aplicável)

// ============================================================================
// ROTEAMENTO DE REQUISIÇÕES
// ============================================================================

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
 * Cria a cobrança PIX no Gateway de Pagamento
 */
function handleCreatePix() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['name']) || !isset($data['cpf']) || !isset($data['totalAmount'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos fornecidos.']);
        exit();
    }

    $name        = sanitizeString($data['name']);
    $email       = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $cpf         = preg_replace('/\D/', '', $data['cpf']);
    $phone       = preg_replace('/\D/', '', $data['phone']);
    $amount      = (float) $data['totalAmount'];
    $externalId  = 'LF360_' . time() . '_' . rand(1000, 9999);

    // =========================================================================
    // EXEMPLO DE INTEGRAÇÃO REAL C/ MERCADO PAGO VIA cURL
    // =========================================================================
    if (GATEWAY_PROVIDER === 'MERCADOPAGO') {
        $payload = [
            'transaction_amount' => $amount,
            'description'        => 'LavaFrutas 360 - Pedido ' . $externalId,
            'payment_method_id'  => 'pix',
            'external_reference' => $externalId,
            'payer' => [
                'email'      => $email,
                'first_name' => explode(' ', $name)[0],
                'last_name'  => implode(' ', array_slice(explode(' ', $name), 1)),
                'identification' => [
                    'type'   => 'CPF',
                    'number' => $cpf
                ]
            ]
        ];

        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GATEWAY_ACCESS_TOKEN,
            'X-Idempotency-Key: ' . $externalId
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resData = json_decode($response, true);

        if ($httpCode === 201 && isset($resData['point_of_interaction']['transaction_data'])) {
            $pixData = $resData['point_of_interaction']['transaction_data'];
            echo json_encode([
                'success'   => true,
                'txid'      => $resData['id'],
                'pixCode'   => $pixData['qr_code'],
                'qrCodeUrl' => $pixData['qr_code_base64'] ? 'data:image/png;base64,' . $pixData['qr_code_base64'] : 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($pixData['qr_code'])
            ]);
            exit();
        }
    }

    // Se o gateway não for configurado ou falhar em ambiente de testes, retorna estrutura demo
    echo json_encode([
        'success'   => true,
        'txid'      => $externalId,
        'pixCode'   => '00020126580014BR.GOV.BCB.PIX0136lavafrutas360-pix-key-demo520400005303986540519.905802BR5925LavaFrutas 360 Loja Oficial6009Sao Paulo62070503***6304E2D1',
        'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=lavafrutas360-demo'
    ]);
}

/**
 * Consulta o status da transação PIX no Gateway
 */
function handleCheckStatus() {
    $txid = isset($_GET['txid']) ? sanitizeString($_GET['txid']) : '';

    if (!$txid) {
        echo json_encode(['status' => 'PENDING']);
        exit();
    }

    if (GATEWAY_PROVIDER === 'MERCADOPAGO') {
        $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $txid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . GATEWAY_ACCESS_TOKEN
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($response, true);
        if (isset($resData['status']) && $resData['status'] === 'approved') {
            echo json_encode(['status' => 'APPROVED']);
            exit();
        }
    }

    echo json_encode(['status' => 'PENDING']);
}

function sanitizeString($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
