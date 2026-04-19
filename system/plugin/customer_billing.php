<?php

register_menu("Tagihan Pelanggan", true, "customer_billing", 'AFTER_CUSTOMERS', 'ion ion-cash', '', '');

/*
|--------------------------------------------------------------------------
| AUTO CREATE tbl_agent_customers (relasi agent-customer)
|--------------------------------------------------------------------------
*/
try {
    $db = ORM::get_db();
    $db->exec("
        CREATE TABLE IF NOT EXISTS `tbl_agent_customers` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id`    INT NOT NULL,
            `customer_id` INT NOT NULL,
            `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uc_agent_customer` (`agent_id`, `customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (Exception $e) {
    // silent – table may already exist
}

/*
|--------------------------------------------------------------------------
| HELPER: ambil customer_id yang di-assign ke agent tertentu
|--------------------------------------------------------------------------
*/
function customer_billing_get_agent_customer_ids($agentId)
{
    try {
        $rows = ORM::for_table('tbl_agent_customers')
            ->where('agent_id', (int) $agentId)
            ->find_array();
        return array_column($rows, 'customer_id');
    } catch (Exception $e) {
        return [];
    }
}

function customer_billing_format_bill_name($fieldName)
{
    if (preg_match('/^(\d{2})_(\d{4})\s+Bill$/', $fieldName, $m)) {
        $monthNum = (int) $m[1];
        $year = $m[2];
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        if (isset($months[$monthNum])) {
            return 'Tagihan ' . $months[$monthNum] . ' ' . $year;
        }
    }

    return str_replace(' Bill', '', $fieldName);
}

function customer_billing_paid_by_expiration($expiration)
{
    $expYm = date('Y-m', strtotime($expiration));
    $currentYm = date('Y-m');
    return $expYm > $currentYm;
}

function customer_billing_count_arrears_months($customerId)
{
    $months = 0;
    $attrs = User::getAttributes('Bill', $customerId);

    foreach ($attrs as $value) {
        if (strpos($value, ':') === false) {
            if ((float) $value > 0) {
                $months++;
            }
            continue;
        }

        list($cost, $remaining) = explode(':', $value);
        $remaining = (int) $remaining;
        if ((float) $cost > 0 && $remaining > 0) {
            $months += $remaining;
        }
    }

    return $months;
}

function customer_billing_paid_this_month($customerId)
{
    $endOfCurrentMonth = date('Y-m-t');
    $paidCount = ORM::for_table('tbl_user_recharges')
        ->where('customer_id', $customerId)
        ->where_gt('expiration', $endOfCurrentMonth)
        ->count();

    return $paidCount > 0;
}

function customer_billing_current_month_label()
{
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    $monthNum = (int) date('n');
    return $months[$monthNum] . ' ' . date('Y');
}

function customer_billing_is_expired($date, $time = '00:00:00')
{
    $ts = strtotime(trim((string) $date) . ' ' . trim((string) $time));
    if ($ts === false) {
        return false;
    }
    return $ts <= time();
}

function customer_billing_assert_customer_access($customerId, $admin)
{
    if ($admin['user_type'] !== 'Agent') {
        return;
    }

    $agentCustomerIds = customer_billing_get_agent_customer_ids($admin['id']);
    if (!in_array((int) $customerId, array_map('intval', $agentCustomerIds), true)) {
        r2(getUrl('plugin/customer_billing'), 'e', 'Anda tidak punya akses ke customer ini');
    }
}

function customer_billing_issue_request_token()
{
    if (!isset($_SESSION['customer_billing_request_tokens']) || !is_array($_SESSION['customer_billing_request_tokens'])) {
        $_SESSION['customer_billing_request_tokens'] = [];
    }

    $now = time();
    foreach ($_SESSION['customer_billing_request_tokens'] as $token => $createdAt) {
        if (!is_int($createdAt) || ($now - $createdAt) > 1800) {
            unset($_SESSION['customer_billing_request_tokens'][$token]);
        }
    }

    $token = bin2hex(random_bytes(16));
    $_SESSION['customer_billing_request_tokens'][$token] = $now;
    return $token;
}

function customer_billing_consume_request_token($token)
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    if (!isset($_SESSION['customer_billing_request_tokens']) || !is_array($_SESSION['customer_billing_request_tokens'])) {
        return false;
    }

    if (!isset($_SESSION['customer_billing_request_tokens'][$token])) {
        return false;
    }

    unset($_SESSION['customer_billing_request_tokens'][$token]);
    return true;
}

function customer_billing()
{
    global $ui, $routes, $admin;

    _admin();

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin', 'Agent', 'Sales'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
        exit;
    }

    $ui->assign('_title', 'Tagihan Pelanggan');
    $ui->assign('_system_menu', 'customer_billing');
    $ui->assign('_admin', $admin);

    $action = isset($routes[2]) ? $routes[2] : 'list';

    switch ($action) {

        // ── Halaman manajemen assignment agent ──────────────────────────────
        case 'agents':
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
                exit;
            }

            $agents = ORM::for_table('tbl_users')
                ->where('user_type', 'Agent')
                ->order_by_asc('fullname')
                ->find_array();

            foreach ($agents as &$ag) {
                $ag['customer_count'] = ORM::for_table('tbl_agent_customers')
                    ->where('agent_id', $ag['id'])
                    ->count();
            }
            unset($ag);

            $ui->assign('page_mode', 'agents');
            $ui->assign('agents', $agents);
            $ui->display('customer_billing_dashboard.tpl');
            break;

        // ── Halaman pilih customer untuk satu agent ─────────────────────────
        case 'agent_assign':
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
                exit;
            }

            $agentId = isset($routes[3]) ? (int) $routes[3] : 0;
            $agent   = ORM::for_table('tbl_users')
                ->where('id', $agentId)
                ->where('user_type', 'Agent')
                ->find_one();

            if (!$agent) {
                r2(getUrl('plugin/customer_billing/agents'), 'e', 'Agent tidak ditemukan');
            }

            $assignedIds = customer_billing_get_agent_customer_ids($agentId);
            $assignedIdSet = array_flip($assignedIds);

            $allCustomers = ORM::for_table('tbl_customers')
                ->where_not_equal('status', 'Banned')
                ->order_by_asc('fullname')
                ->find_array();

            foreach ($allCustomers as &$cust) {
                $cust['is_assigned'] = isset($assignedIdSet[$cust['id']]) ? 1 : 0;
            }
            unset($cust);

            $ui->assign('page_mode', 'agent_assign');
            $ui->assign('agent', $agent);
            $ui->assign('all_customers', $allCustomers);
            $ui->assign('assigned_count', count($assignedIds));
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->assign('request_token', customer_billing_issue_request_token());
            $ui->display('customer_billing_dashboard.tpl');
            break;

        // ── Simpan bulk assignment ──────────────────────────────────────────
        case 'agent_save':
            if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
                _alert(Lang::T('You do not have permission to access this page'), 'danger', 'dashboard');
                exit;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                r2(getUrl('plugin/customer_billing/agents'), 'e', 'Invalid request');
            }
            if (!Csrf::check(_post('csrf_token'))) {
                r2(getUrl('plugin/customer_billing/agents'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
            }
            if (!customer_billing_consume_request_token(_post('request_token'))) {
                r2(getUrl('plugin/customer_billing/agents'), 'e', 'Request sudah diproses atau kadaluarsa. Silakan ulangi dari halaman terbaru.');
            }

            $agentId = (int) _post('agent_id');
            $agent   = ORM::for_table('tbl_users')
                ->where('id', $agentId)
                ->where('user_type', 'Agent')
                ->find_one();
            if (!$agent) {
                r2(getUrl('plugin/customer_billing/agents'), 'e', 'Agent tidak ditemukan');
            }

            $newIds = isset($_POST['customer_ids']) ? $_POST['customer_ids'] : [];
            $newIds = array_values(array_filter(array_unique(array_map('intval', $newIds))));

            ORM::for_table('tbl_agent_customers')
                ->where('agent_id', $agentId)
                ->delete_many();

            foreach ($newIds as $cid) {
                $rec              = ORM::for_table('tbl_agent_customers')->create();
                $rec->agent_id    = $agentId;
                $rec->customer_id = $cid;
                $rec->save();
            }

            _log('[' . $admin['username'] . ']: Simpan assignment ' . count($newIds) . ' customer ke agent #' . $agentId, $admin['user_type'], $admin['id']);
            r2(getUrl('plugin/customer_billing/agents'), 's', count($newIds) . ' customer berhasil di-assign ke agent ' . $agent['fullname']);
            break;

        case 'detail':
            $customerId = isset($routes[3]) ? (int) $routes[3] : 0;
            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if (!$customer) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Customer not found'));
            }
            customer_billing_assert_customer_access($customerId, $admin);

            $recharges = ORM::for_table('tbl_user_recharges')
                ->where('customer_id', $customerId)
                ->order_by_desc('id')
                ->find_many();

            $rechargeRows = [];
            $paymentOptions = [];
            $paymentOptionsTotal = 0;
            foreach ($recharges as $recharge) {
                $plan = ORM::for_table('tbl_plans')->find_one($recharge['plan_id']);
                $paidByExpiration = customer_billing_paid_by_expiration($recharge['expiration']);
                $isExpired = customer_billing_is_expired($recharge['expiration'], $recharge['time']);
                $paidThisMonth = $paidByExpiration;

                $rechargeRows[] = [
                    'id' => $recharge['id'],
                    'namebp' => $recharge['namebp'],
                    'routers' => $recharge['routers'],
                    'status' => $recharge['status'],
                    'expiration' => $recharge['expiration'],
                    'time' => $recharge['time'],
                    'paid_this_month' => $paidThisMonth,
                    'paid_by_expiration' => $paidByExpiration,
                    'is_expired' => $isExpired,
                    'can_pay' => !$paidByExpiration,
                    'plan_id' => $recharge['plan_id'],
                    'plan_price' => $plan ? (float) $plan['price'] : 0,
                    'validity_unit' => $plan ? $plan['validity_unit'] : ''
                ];

                if (!$paidByExpiration && $plan && (float) $plan['price'] > 0) {
                    $paymentOptions[] = [
                        'type' => 'package',
                        'recharge_id' => $recharge['id'],
                        'name' => 'package:' . $recharge['id'],
                        'display_name' => 'Tagihan ' . customer_billing_current_month_label() . ' - Paket ' . $recharge['namebp'],
                        'cost' => (float) $plan['price'],
                        'remaining' => 1,
                        'source' => 'Paket bulan ini'
                    ];
                    $paymentOptionsTotal += (float) $plan['price'];
                }
            }

            $billAttributes = User::getAttributes('Bill', $customerId);
            list($bills, $additionalCost) = User::getBills($customerId);
            $parsedBills = [];
            foreach ($billAttributes as $fieldName => $rawValue) {
                $monthsRemaining = 0;
                $cost = 0;
                if (strpos($rawValue, ':') === false) {
                    $cost = (float) $rawValue;
                    if ($cost > 0) {
                        $monthsRemaining = 1;
                    }
                } else {
                    list($costPart, $remainingPart) = explode(':', $rawValue);
                    $cost = (float) $costPart;
                    $monthsRemaining = (int) $remainingPart;
                }

                if ($cost > 0 && $monthsRemaining > 0) {
                    $billRow = [
                        'name' => $fieldName,
                        'display_name' => customer_billing_format_bill_name($fieldName),
                        'cost' => $cost,
                        'remaining' => $monthsRemaining
                    ];
                    $parsedBills[] = $billRow;
                    $paymentOptions[] = [
                        'type' => 'bill',
                        'name' => $fieldName,
                        'display_name' => $billRow['display_name'],
                        'cost' => $cost,
                        'remaining' => $monthsRemaining,
                        'source' => 'Additional bill'
                    ];
                    $paymentOptionsTotal += $cost;
                }
            }

            $ui->assign('page_mode', 'detail');
            $ui->assign('customer', $customer);
            $ui->assign('recharges', $rechargeRows);
            $ui->assign('payment_options', $paymentOptions);
            $ui->assign('payment_options_total', $paymentOptionsTotal);
            $ui->assign('parsed_bills', $parsedBills);
            $ui->assign('bills', $bills);
            $ui->assign('additional_cost', $additionalCost);
            $ui->assign('arrears_months', customer_billing_count_arrears_months($customerId));
            $ui->assign('csrf_token', Csrf::generateAndStoreToken());
            $ui->assign('request_token', customer_billing_issue_request_token());
            $ui->display('customer_billing_dashboard.tpl');
            break;

        case 'nunggak':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid request method'));
            }
            if (!Csrf::check(_post('csrf_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
            }
            if (!customer_billing_consume_request_token(_post('request_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', 'Request sudah diproses atau kadaluarsa. Silakan ulangi dari halaman terbaru.');
            }

            $customerId = (int) _post('customer_id');
            $rechargeId = (int) _post('recharge_id');

            if ($customerId <= 0 || $rechargeId <= 0) {
                r2(getUrl('plugin/customer_billing'), 'e', 'Parameter tidak valid');
            }

            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if (!$customer) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Customer not found'));
            }
            customer_billing_assert_customer_access($customerId, $admin);

            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('id', $rechargeId)
                ->where('customer_id', $customerId)
                ->find_one();

            if (!$recharge) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Billing not found'));
            }

            if (!customer_billing_is_expired($recharge['expiration'], $recharge['time'])) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Paket belum expired, tombol nunggak belum aktif');
            }

            $plan = ORM::for_table('tbl_plans')->find_one($recharge['plan_id']);
            if (!$plan) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Plan paket tidak ditemukan');
            }

            $planPrice = (float) $plan['price'];
            $billMonthYear = date('m_Y', strtotime($recharge['expiration'] . ' ' . $recharge['time']));
            $billFieldName = $billMonthYear . ' Bill';
            $billDisplayName = customer_billing_format_bill_name($billFieldName);

            $gateway = 'nunggak';
            $channel = $admin['fullname'];
            $note = 'Admin ' . $admin['fullname'] . ' (' . $admin['user_type'] . ')' . "\n";

            $invoice = '';
            $additionalBillAdded = false;
            $db = ORM::get_db();
            try {
                $db->beginTransaction();

                // Lock all Bill rows for this customer while doing temporary clear/recharge/restore.
                $stmt = $db->prepare("SELECT field_name, field_value FROM tbl_customers_fields WHERE customer_id = ? AND field_name LIKE ? FOR UPDATE");
                $stmt->execute([$customerId, '%Bill']);
                $lockedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $originalBillAttrs = [];
                foreach ($lockedRows as $lockedRow) {
                    $originalBillAttrs[$lockedRow['field_name']] = $lockedRow['field_value'];
                }

                $hasSameBill = false;
                if (isset($originalBillAttrs[$billFieldName])) {
                    $existingBillValue = (string) $originalBillAttrs[$billFieldName];
                    if (strpos($existingBillValue, ':') === false) {
                        $hasSameBill = ((float) $existingBillValue) > 0;
                    } else {
                        list($existingCost, $existingRemaining) = explode(':', $existingBillValue, 2);
                        $hasSameBill = ((float) $existingCost) > 0 && ((int) $existingRemaining) > 0;
                    }
                }

                // Nunggak hanya update expired + invoice, jangan ikut melunasi additional bill aktif.
                foreach ($originalBillAttrs as $fieldName => $fieldValue) {
                    if (strpos($fieldValue, ':') === false) {
                        User::setAttribute($fieldName, '0', $customerId);
                    } else {
                        User::setAttribute($fieldName, '0:0', $customerId);
                    }
                }

                $invoice = Package::rechargeUser(
                    $customerId,
                    $recharge['routers'],
                    $recharge['plan_id'],
                    $gateway,
                    $channel,
                    $note
                );

                if (!$invoice) {
                    throw new Exception(Lang::T('Failed to process nunggak'));
                }

                foreach ($originalBillAttrs as $fieldName => $fieldValue) {
                    User::setAttribute($fieldName, $fieldValue, $customerId);
                }

                if (!$hasSameBill && $planPrice > 0) {
                    User::setAttribute($billFieldName, $planPrice . ':1', $customerId);
                    $additionalBillAdded = true;
                }

                $db->commit();
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Proses nunggak gagal: ' . $e->getMessage());
            }

            $transaction = ORM::for_table('tbl_transactions')
                ->where('invoice', $invoice)
                ->find_one();

            if (!$transaction) {
                $transaction = ORM::for_table('tbl_transactions')
                    ->where('user_id', $customerId)
                    ->order_by_desc('id')
                    ->find_one();
            }

            if (!$transaction) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Transaction not found'));
            }

            $extraNote = $additionalBillAdded
                ? 'Nunggak ' . $billDisplayName . ' : ' . Lang::moneyFormat($planPrice)
                : 'Nunggak ' . $billDisplayName ;

            $rawNote = trim((string) $transaction['note']);
            $transaction->note = $rawNote === '' ? $extraNote : ($rawNote . "\n" . $extraNote);
            $transaction->save();

            Package::createInvoice($transaction);
            _log('[' . $admin['username'] . ']: Nunggak customer ' . $customer['username'] . ' via plugin Customer Billing', $admin['user_type'], $admin['id']);
            $ui->display('admin/plan/invoice.tpl');
            break;

        case 'pay':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid request method'));
            }
            if (!Csrf::check(_post('csrf_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
            }
            if (!customer_billing_consume_request_token(_post('request_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', 'Request sudah diproses atau kadaluarsa. Silakan ulangi dari halaman terbaru.');
            }

            $customerId = (int) _post('customer_id');
            $rechargeIds = isset($_POST['recharge_ids']) ? $_POST['recharge_ids'] : [];
            $selectedBillNames = isset($_POST['bill_names']) ? $_POST['bill_names'] : [];

            if (!is_array($rechargeIds)) {
                $rechargeIds = [];
            }
            if (!is_array($selectedBillNames)) {
                $selectedBillNames = [];
            }

            $rechargeIds = array_values(array_unique(array_map('intval', $rechargeIds)));
            $selectedBillNames = array_values(array_unique(array_map('trim', $selectedBillNames)));

            if (count($rechargeIds) === 0) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Pilih minimal 1 tagihan paket yang akan dibayar');
            }

            if (count($rechargeIds) > 1) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Saat ini pembayaran hanya bisa 1 paket per proses');
            }

            $rechargeId = (int) $rechargeIds[0];

            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if (!$customer) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Customer not found'));
            }
            customer_billing_assert_customer_access($customerId, $admin);

            $recharge = ORM::for_table('tbl_user_recharges')
                ->where('id', $rechargeId)
                ->where('customer_id', $customerId)
                ->find_one();

            if (!$recharge) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Billing not found'));
            }

            $paidThisMonth = customer_billing_paid_by_expiration($recharge['expiration']);

            if ($paidThisMonth) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Tagihan paket ini sudah dibayar karena expiration sudah melewati bulan ini');
            }

            $originalBillAttrs = User::getAttributes('Bill', $customerId);
            foreach ($originalBillAttrs as $fieldName => $fieldValue) {
                if (!in_array($fieldName, $selectedBillNames, true)) {
                    if (strpos($fieldValue, ':') === false) {
                        User::setAttribute($fieldName, '0', $customerId);
                    } else {
                        User::setAttribute($fieldName, '0:0', $customerId);
                    }
                }
            }

            $gateway = 'Agent';
            $channel = $admin['fullname'];
            $note = 'Bayar tagihan melalui ' . $admin['fullname'] . ' (' . $admin['user_type'] . ')' . "\n";

            $invoice = Package::rechargeUser(
                $customerId,
                $recharge['routers'],
                $recharge['plan_id'],
                $gateway,
                $channel,
                $note
            );

            $currentBillAttrs = User::getAttributes('Bill', $customerId);
            foreach ($originalBillAttrs as $fieldName => $fieldValue) {
                if (in_array($fieldName, $selectedBillNames, true)) {
                    if (isset($currentBillAttrs[$fieldName])) {
                        User::setAttribute($fieldName, $currentBillAttrs[$fieldName], $customerId);
                    } else {
                        User::setAttribute($fieldName, $fieldValue, $customerId);
                    }
                } else {
                    User::setAttribute($fieldName, $fieldValue, $customerId);
                }
            }

            if (!$invoice) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Failed to process payment'));
            }

            $transaction = ORM::for_table('tbl_transactions')
                ->where('invoice', $invoice)
                ->find_one();

            if (!$transaction) {
                $transaction = ORM::for_table('tbl_transactions')
                    ->where('user_id', $customerId)
                    ->order_by_desc('id')
                    ->find_one();
            }

            if (!$transaction) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Transaction not found'));
            }

            // Rewrite raw bill field names in note before generating invoice
            $plan = ORM::for_table('tbl_plans')->find_one($recharge['plan_id']);
            $planName = $plan ? trim($plan['name_plan']) : '';
            $rawNote = str_replace("\r", "", (string) $transaction['note']);
            $noteLines = explode("\n", $rawNote);
            $cleanLines = [];
            foreach ($noteLines as $line) {
                if (strpos($line, ' : ') !== false) {
                    list($k, $v) = explode(' : ', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if (preg_match('/^\d{2}_\d{4}\s+Bill$/', $k)) {
                        $k = customer_billing_format_bill_name($k);
                    } elseif ($planName !== '' && $k === $planName) {
                        $k = 'Tagihan ' . customer_billing_current_month_label();
                    }
                    $cleanLines[] = $k . ' : ' . $v;
                } else {
                    $trimmed = trim($line);
                    if ($trimmed !== '' && strpos($trimmed, 'Tagihan:') === false) {
                        $cleanLines[] = $line;
                    }
                }
            }
            $transaction->note = implode("\n", $cleanLines);
            $transaction->save();
            Package::createInvoice($transaction);
            _log('[' . $admin['username'] . ']: Bayar tagihan customer ' . $customer['username'] . ' via plugin Customer Billing', $admin['user_type'], $admin['id']);
            $ui->display('admin/plan/invoice.tpl');
            break;

        case 'pay_additional':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid request method'));
            }
            if (!Csrf::check(_post('csrf_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Invalid or Expired CSRF Token') . '.');
            }
            if (!customer_billing_consume_request_token(_post('request_token'))) {
                r2(getUrl('plugin/customer_billing'), 'e', 'Request sudah diproses atau kadaluarsa. Silakan ulangi dari halaman terbaru.');
            }

            $customerId = (int) _post('customer_id');
            $selectedBillNames = isset($_POST['bill_names']) ? $_POST['bill_names'] : [];

            if (!is_array($selectedBillNames)) {
                $selectedBillNames = [];
            }

            $selectedBillNames = array_values(array_unique(array_map('trim', $selectedBillNames)));

            if (count($selectedBillNames) === 0) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Pilih minimal 1 additional bill yang akan dibayar');
            }

            $customer = ORM::for_table('tbl_customers')->find_one($customerId);
            if (!$customer) {
                r2(getUrl('plugin/customer_billing'), 'e', Lang::T('Customer not found'));
            }
            customer_billing_assert_customer_access($customerId, $admin);

            list($activeBills, $activeAdditionalCost) = User::getBills($customerId);
            if ($activeAdditionalCost <= 0 || count($activeBills) === 0) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Tidak ada additional bill aktif untuk dibayar');
            }

            $selectedBills = [];
            $totalPrice = 0;
            foreach ($selectedBillNames as $billName) {
                if (isset($activeBills[$billName]) && (float) $activeBills[$billName] > 0) {
                    $selectedBills[$billName] = (float) $activeBills[$billName];
                    $totalPrice += (float) $activeBills[$billName];
                }
            }

            if (count($selectedBills) === 0 || $totalPrice <= 0) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Additional bill yang dipilih tidak valid atau sudah lunas');
            }

            $latestRecharge = ORM::for_table('tbl_user_recharges')
                ->where('customer_id', $customerId)
                ->order_by_desc('id')
                ->find_one();

            if (!$latestRecharge) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', 'Tidak ditemukan data paket customer untuk referensi transaksi');
            }

            $note = 'Bayar tagihan melalui ' . $admin['fullname'] . ' (' . $admin['user_type'] . ')' . "\n";
            foreach ($selectedBills as $billName => $billCost) {
                $note .= "\n" . customer_billing_format_bill_name($billName) . ' : ' . Lang::moneyFormat($billCost);
            }

            $transaction = ORM::for_table('tbl_transactions')->create();
            $transaction->invoice = 'INV-' . Package::_raid();
            $transaction->username = $customer['username'];
            $transaction->user_id = $customerId;
            $transaction->plan_name = 'Additional Bill';
            $transaction->price = $totalPrice;
            $transaction->recharged_on = date('Y-m-d');
            $transaction->recharged_time = date('H:i:s');
            $transaction->expiration = date('Y-m-d');
            $transaction->time = date('H:i:s');
            $transaction->method = 'Agent - ' . $admin['fullname'];
            $transaction->routers = $latestRecharge['routers'];
            $transaction->type = in_array($latestRecharge['type'], ['Hotspot', 'PPPOE', 'Balance']) ? $latestRecharge['type'] : 'Hotspot';
            $transaction->note = $note;
            $transaction->admin_id = $admin['id'];
            $transaction->save();

            if (!$transaction['id']) {
                r2(getUrl('plugin/customer_billing/detail/' . $customerId), 'e', Lang::T('Failed to process payment'));
            }

            User::billsPaid($selectedBills, $customerId);
            Package::createInvoice($transaction);
            _log('[' . $admin['username'] . ']: Bayar additional bill customer ' . $customer['username'] . ' via plugin Customer Billing', $admin['user_type'], $admin['id']);
            $ui->display('admin/plan/invoice.tpl');
            break;

        default:
            $customerQuery = ORM::for_table('tbl_customers')
                ->where_not_equal('status', 'Banned')
                ->order_by_asc('fullname');

            // Agent hanya melihat customer yang di-assign kepadanya
            if ($admin['user_type'] === 'Agent') {
                $agentCustomerIds = customer_billing_get_agent_customer_ids($admin['id']);
                // Jika tidak ada assignment, tampilkan kosong (ID 0 tidak pernah ada)
                $customerQuery->where_in('id', !empty($agentCustomerIds) ? $agentCustomerIds : [0]);
            }

            $customers = $customerQuery->find_many();

            $rows = [];
            $expiredRows = [];
            $no = 1;
            $expiredNo = 1;
            $planPriceCache = [];
            $allRouters = [];
            foreach ($customers as $customer) {
                $paidThisMonth = customer_billing_paid_this_month($customer['id']);

                $customerRecharges = ORM::for_table('tbl_user_recharges')
                    ->where('customer_id', $customer['id'])
                    ->order_by_desc('id')
                    ->find_many();

                if (count($customerRecharges) === 0) {
                    continue;
                }

                $packageTypes = [];
                $planNames = [];
                $planPrices = [];
                $routersForRow = [];
                $latestExpiredAt = null;
                $expiredPackageCount = 0;
                foreach ($customerRecharges as $customerRecharge) {
                    $pkgType = trim((string) $customerRecharge['type']);
                    if ($pkgType !== '' && !in_array($pkgType, $packageTypes, true)) {
                        $packageTypes[] = $pkgType;
                    }

                    $planName = trim((string) $customerRecharge['namebp']);
                    if ($planName !== '' && !in_array($planName, $planNames, true)) {
                        $planNames[] = $planName;
                    }

                    $planId = (int) $customerRecharge['plan_id'];
                    if (!isset($planPriceCache[$planId])) {
                        $planData = ORM::for_table('tbl_plans')
                            ->select('price')
                            ->find_one($planId);
                        $planPriceCache[$planId] = $planData ? (float) $planData['price'] : 0;
                    }

                    $priceLabel = Lang::moneyFormat($planPriceCache[$planId]);
                    if (!in_array($priceLabel, $planPrices, true)) {
                        $planPrices[] = $priceLabel;
                    }

                    $routerVal = trim((string) $customerRecharge['routers']);
                    if ($routerVal !== '' && !in_array($routerVal, $routersForRow, true)) {
                        $routersForRow[] = $routerVal;
                    }

                    if (customer_billing_is_expired($customerRecharge['expiration'], $customerRecharge['time'])) {
                        $expiredPackageCount++;
                        $expiredAt = strtotime($customerRecharge['expiration'] . ' ' . $customerRecharge['time']);
                        if ($latestExpiredAt === null || $expiredAt > $latestExpiredAt) {
                            $latestExpiredAt = $expiredAt;
                        }
                    }
                }

                $packageTypeLabel = count($packageTypes) > 0 ? implode(', ', $packageTypes) : '-';
                $planNameLabel = count($planNames) > 0 ? implode(', ', $planNames) : '-';
                $planPriceLabel = count($planPrices) > 0 ? implode(', ', $planPrices) : '-';

                sort($routersForRow);
                foreach ($routersForRow as $r) {
                    if (!in_array($r, $allRouters, true)) {
                        $allRouters[] = $r;
                    }
                }

                $rows[] = [
                    'no' => $no++,
                    'id' => $customer['id'],
                    'username' => $customer['username'],
                    'fullname' => $customer['fullname'],
                    'package_type' => $packageTypeLabel,
                    'plan_name' => $planNameLabel,
                    'plan_price' => $planPriceLabel,
                    'paid_this_month' => $paidThisMonth,
                    'unpaid_this_month' => $paidThisMonth ? 0 : 1,
                    'arrears_months' => customer_billing_count_arrears_months($customer['id']),
                    'routers' => implode(', ', $routersForRow),
                ];

                if ($expiredPackageCount > 0) {
                    $expiredRows[] = [
                        'no' => $expiredNo++,
                        'id' => $customer['id'],
                        'username' => $customer['username'],
                        'fullname' => $customer['fullname'],
                        'package_type' => $packageTypeLabel,
                        'plan_name' => $planNameLabel,
                        'routers' => implode(', ', $routersForRow),
                        'expired_package_count' => $expiredPackageCount,
                        'latest_expired_at' => $latestExpiredAt,
                        'arrears_months' => customer_billing_count_arrears_months($customer['id']),
                    ];
                }
            }

            sort($allRouters);
            $ui->assign('page_mode', 'list');
            $ui->assign('month_label', date('F Y'));
            $ui->assign('rows', $rows);
            $ui->assign('expired_rows', $expiredRows);
            $ui->assign('all_routers', $allRouters);
            $ui->display('customer_billing_dashboard.tpl');
            break;
    }
}
