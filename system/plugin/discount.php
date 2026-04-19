<?php

register_menu("Discount", true, "discount", 'SERVICES', '', '', "");

/*
|--------------------------------------------------------------------------
| AUTO CREATE TABLE
|--------------------------------------------------------------------------
*/
try {
    $db = ORM::get_db();
    $db->exec("
        CREATE TABLE IF NOT EXISTS tbl_discounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            type ENUM('percent','amount') DEFAULT 'percent',
            value DECIMAL(10,2) DEFAULT 0,
            payment_gateway VARCHAR(50) DEFAULT NULL,
            plan_id INT DEFAULT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $columnCheck = $db->query("SHOW COLUMNS FROM tbl_payment_gateway LIKE 'discount_amount'");
    if ($columnCheck && !$columnCheck->fetch()) {
        $db->exec("ALTER TABLE tbl_payment_gateway ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER price");
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| MAIN PAGE
|--------------------------------------------------------------------------
*/
function discount()
{
    global $ui, $config;
    _admin();

    $ui->assign('_title', 'Discount');
    $ui->assign('_system_menu', 'plan');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('No permission'), 'danger', "dashboard");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DATA
    |--------------------------------------------------------------------------
    */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $name = _post('name');
        $type = _post('type'); // percent / amount
        $value = floatval(_post('value'));
        $gateway = trim(_post('gateway'));
        $plan_id = intval(_post('plan_id'));

        if (!$name || $value <= 0) {
            r2($_SERVER['HTTP_REFERER'], 'e', 'Invalid input');
            return;
        }

        $d = ORM::for_table('tbl_discounts')->create();
        $d->name = $name;
        $d->type = $type;
        $d->value = $value;
        $d->payment_gateway = $gateway;
        $d->plan_id = $plan_id;
        $d->status = 1;
        $d->save();

        r2($_SERVER['HTTP_REFERER'], 's', 'Discount berhasil ditambahkan');
    }

    /*
    |--------------------------------------------------------------------------
    | GET DATA
    |--------------------------------------------------------------------------
    */
    $gateways = array_values(array_filter(array_map('trim', explode(',', $config['payment_gateway']))));
    $plans = ORM::for_table('tbl_plans')
        ->select('id')
        ->select('name_plan')
        ->select('routers')
        ->select('type')
        ->where('enabled', '1')
        ->order_by_asc('name_plan')
        ->find_array();

    $discounts = ORM::for_table('tbl_discounts')->find_many();
    $planNames = array();

    foreach ($plans as $plan) {
        $planNames[$plan['id']] = $plan['name_plan'] . ' - ' . $plan['type'] . ' (' . $plan['routers'] . ')';
    }

    foreach ($discounts as $discount) {
        $discount->plan_name = !empty($discount['plan_id']) && isset($planNames[$discount['plan_id']])
            ? $planNames[$discount['plan_id']]
            : 'All Plans';
        $discount->gateway_name = !empty($discount['payment_gateway'])
            ? $discount['payment_gateway']
            : 'All Gateways';
    }

    $ui->assign('gateways', $gateways);
    $ui->assign('plans', $plans);
    $ui->assign('discounts', $discounts);
    $ui->display('discount.tpl');
}

/*
|--------------------------------------------------------------------------
| TOGGLE STATUS
|--------------------------------------------------------------------------
*/
function discount_toggle()
{
    _admin();

    $id = _req('id');

    $d = ORM::for_table('tbl_discounts')->find_one($id);
    if ($d) {
        $d->status = $d->status ? 0 : 1;
        $d->save();
    }

    r2($_SERVER['HTTP_REFERER'], 's', 'Status updated');
}

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
function discount_delete()
{
    _admin();

    $id = _req('id');

    ORM::for_table('tbl_discounts')
        ->where('id', $id)
        ->delete_many();

    r2($_SERVER['HTTP_REFERER'], 's', 'Deleted');
}

/*
|--------------------------------------------------------------------------
| APPLY DISCOUNT (CORE FUNCTION)
|--------------------------------------------------------------------------
*/
function apply_discount($amount, $gateway = null, $plan_id = null)
{
    $discounts = ORM::for_table('tbl_discounts')
        ->where('status', 1)
        ->find_many();

    foreach ($discounts as $d) {

        // filter gateway
        if ($d->payment_gateway && $gateway != $d->payment_gateway) {
            continue;
        }

        // filter plan
        if ($d->plan_id && $plan_id != $d->plan_id) {
            continue;
        }

        if ($d->type == 'percent') {
            $amount -= ($amount * $d->value / 100);
        } else {
            $amount -= $d->value;
        }
    }

    return max($amount, 0);
}