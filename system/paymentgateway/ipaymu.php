<?php

/**
 * PHP Mikrotik Billing
 * Payment Gateway iPaymu v2
 */

function ipaymu_validate_config()
{
    global $config;
    if (empty($config['ipaymu_va']) || empty($config['ipaymu_api_key'])) {
        Message::sendTelegram("iPaymu payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup iPaymu payment gateway."));
    }
}

function ipaymu_show_config()
{
    global $ui;
    $ui->assign('_title', 'iPaymu - Payment Gateway');
    $ui->display('ipaymu.tpl');
}

function ipaymu_save_config()
{
    global $admin;

    foreach (['ipaymu_va','ipaymu_api_key','ipaymu_mode'] as $key) {
        $val = _post($key);
        $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
        if ($d) {
            $d->value = $val;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $key;
            $d->value = $val;
            $d->save();
        }
    }

    _log('[' . $admin['username'] . ']: iPaymu Settings Saved', 'Admin', $admin['id']);
    r2(U . 'paymentgateway/ipaymu', 's', Lang::T('Settings_Saved_Successfully'));
}

function ipaymu_create_transaction($trx, $user)
{
    global $config;

    // =============================
    // PILIH METODE PEMBAYARAN
    // =============================
    if (!isset($_POST['payment_channel'])) {

        echo '
        <style>


.pay-container{
    max-width:520px;
    margin:auto;
    font-family:Arial;
    padding:10px;
}

.pay-title{
    text-align:center;
    font-size:24px;
    font-weight:bold;
    margin-bottom:20px;
}

.pay-card{
    display:flex;
    align-items:center;
    gap:15px;
    padding:20px;
    border-radius:14px;
    border:1px solid #eee;
    margin-bottom:14px;
    background:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    cursor:pointer;
    transition:0.2s;
}

.pay-card:hover{
    transform:scale(1.02);
    border:1px solid #0d6efd;
}

.pay-icon{
    width:55px;
}

.pay-name{
    font-size:20px;
    font-weight:bold;
}

.pay-desc{
    font-size:14px;
    color:#777;
}

button{
    width:100%;
    background:none;
    border:none;
    padding:0;
    text-align:left;
}

/* RESPONSIVE HP */
@media(max-width:600px){

    .pay-title{
        font-size:26px;
    }

    .pay-card{
        padding:24px;
        border-radius:16px;
    }

    .pay-icon{
        width:65px;
    }

    .pay-name{
        font-size:22px;
    }

    .pay-desc{
        font-size:15px;
    }
}

        </style>

        <div class="pay-container">

            <div class="pay-title">
                Pilih Metode Pembayaran
            </div>

            <form method="POST">
		<!--
                <button name="payment_channel" value="va_bri">
                    <div class="pay-card">
                        <img class="pay-icon" src="https://upload.wikimedia.org/wikipedia/commons/2/2e/BRI_2020.svg">
                        <div>
                            <div class="pay-name">BRI Virtual Account</div>
                            <div class="pay-desc">Bayar otomatis via ATM / Mobile Banking</div>
                        </div>
                    </div>
                </button>

                <button name="payment_channel" value="va_bca">
                    <div class="pay-card">
                        <img class="pay-icon" src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg">
                        <div>
                            <div class="pay-name">BCA Virtual Account</div>
                            <div class="pay-desc">Pembayaran instan BCA</div>
                        </div>
                    </div>
                </button>
		-->
                <button name="payment_channel" value="qris_mpm">
                    <div class="pay-card">
                        <img class="pay-icon" src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg">
                        <div>
                            <div class="pay-name">QRIS</div>
                            <div class="pay-desc">Scan QR dengan semua e-wallet</div>
                        </div>
                    </div>
                </button>

            </form>

        </div>
        ';
        exit;
    }
    // =============================
    // PROSES DIRECT PAYMENT
    // =============================
    list($paymentMethod, $paymentChannel) = explode('_', $_POST['payment_channel']);

    $va     = $config['ipaymu_va'];
    $apiKey = $config['ipaymu_api_key'];
    $mode   = $config['ipaymu_mode'] ?? 'sandbox';

    $url = ($mode == 'sandbox')
        ? 'https://sandbox.ipaymu.com/api/v2/payment/direct'
        : 'https://my.ipaymu.com/api/v2/payment/direct';

    $body = [
        'name'           => trim($user['fullname'] ?: 'CUSTOMER'),
        'phone'          => trim($user['phonenumber'] ?: '6281554077474'),
        'email'          => trim($user['email'] ?: 'klicknet55@gmail.com'),
        'amount'         => floatval($trx['price']),
        'notifyUrl'      => U . 'callback/ipaymu',
        'referenceId'    => (string)$trx['id'],
        'paymentMethod'  => trim($paymentMethod),
        'paymentChannel' => trim($paymentChannel)
    ];

    $jsonBody     = json_encode($body, JSON_UNESCAPED_SLASHES);
    $requestBody  = strtolower(hash('sha256', $jsonBody));
    $stringToSign = strtoupper('POST') . ':' . $va . ':' . $requestBody . ':' . $apiKey;
    $signature    = hash_hmac('sha256', $stringToSign, $apiKey);
    $timestamp    = date('YmdHis');

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'va: ' . $va,
        'signature: ' . $signature,
        'timestamp: ' . $timestamp
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        die("CURL ERROR: " . $error);
    }

    $result = json_decode($response, true);

    if (empty($result['Success'])) {
        die("<pre>API ERROR:\n" . $response . "</pre>");
    }

    $data = $result['Data'];

    $d = ORM::for_table('tbl_payment_gateway')
        ->where('id', $trx['id'])
        ->find_one();

    $d->gateway = 'ipaymu';
    $d->gateway_trx_id = $data['TransactionId'];
    $d->payment_method = $paymentMethod;
    $d->payment_channel = $paymentChannel;
    $d->pg_request = json_encode($data);
    $d->expired_date = $data['Expired'];

    if (isset($data['PaymentNo'])) {
        $d->pg_url_payment = $data['PaymentNo'];
    }

    if (isset($data['QrImage'])) {
        $d->pg_url_payment = $data['QrImage'];
    }

    $d->save();

    r2(U . "order/view/" . $trx['id'], 's', "Payment created.");
}

function ipaymu_get_status($trx, $user)
{
    if ($trx['status'] == 2) {
        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction has been paid."));
    } else {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    }
}

function ipaymu_payment_notification()
{
    http_response_code(200);
// DEBUG LOG
    $raw = file_get_contents('php://input');
    file_put_contents(
    __DIR__ . '/ipaymu_callback.log',
    date('Y-m-d H:i:s') . "\nRAW:\n" . $raw . "\n\n",
    FILE_APPEND
);

    $data = json_decode($raw, true);
file_put_contents(
    __DIR__ . '/ipaymu_callback.log',
    "PARSED:\n" . print_r($data,true) . "\n\n",
    FILE_APPEND
);

    if (empty($data)) {
        echo 'OK';
        return;
    }

    $referenceId = $data['reference_id'] ?? '';
    $status      = strtolower($data['status'] ?? '');
    if ($referenceId == '') {
        echo 'OK';
        return;
    }

    $trx = ORM::for_table('tbl_payment_gateway')
        ->where('gateway','ipaymu')
        ->where('id',$referenceId)
        ->find_one();

    if (!$trx) {
        Message::sendTelegram("iPaymu callback: Transaction not found\nRef: ".$referenceId);
        echo 'OK';
        return;
    }

    if ($trx['status'] == 2) {
        echo 'OK';
        return;
    }

    if ($status == 'berhasil' || $status == 'success' || $status == 'paid') {

        $user = ORM::for_table('tbl_customers')->find_one($trx['user_id']);
        if (!$user) {
            Message::sendTelegram("iPaymu callback: User not found");
            echo 'OK';
            return;
        }

        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], 'ipaymu', 'iPaymu')) {
            Message::sendTelegram("iPaymu Recharge Failed\nTrx: ".$trx['id']);
            echo 'OK';
            return;
        }

        $trx->pg_paid_response = json_encode($data);
        $trx->paid_date = date('Y-m-d H:i:s');
        $trx->status = 2;
        $trx->save();

        Message::sendTelegram("iPaymu Payment Success\nUser: ".$user['username']."\nInvoice: ".$trx['id']);
    }

    echo 'OK';
}