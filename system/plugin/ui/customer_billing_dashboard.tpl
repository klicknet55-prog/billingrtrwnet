{include file="sections/header.tpl"}

<style>
@media (max-width: 767px) {
    .cb-mobile-sticky-name {
        overflow-x: auto;
        position: relative;
    }

    .cb-mobile-sticky-name table {
        min-width: 860px;
    }

    .cb-mobile-sticky-name table th:nth-child(2),
    .cb-mobile-sticky-name table td:nth-child(2) {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #fff;
        box-shadow: 3px 0 0 rgba(0, 0, 0, 0.06);
    }

    .cb-mobile-sticky-name table thead th:nth-child(2) {
        z-index: 4;
        background: #f6f9fc;
    }
}
</style>

{if $page_mode eq 'detail'}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">Detail Tagihan Customer</div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama:</strong> {$customer.fullname}</p>
                        <p><strong>Username:</strong> {$customer.username}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>No HP:</strong> {$customer.phonenumber}</p>
                        <p><strong>Total Nunggak:</strong> {$arrears_months} bulan</p>
                    </div>
                </div>
                <a href="{$_url}plugin/customer_billing" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-warning panel-hovered panel-stacked mb30">
            <div class="panel-heading">TAGIHAN</div>
            <div class="panel-body">
                {if count($payment_options) > 0}
                <p class="text-muted">Centang tagihan yang ingin dibayar. Tagihan bulan ini diambil dari data paket pada tbl_user_recharges.</p>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Pilih</th>
                            <th>Sumber</th>
                            <th>Nama Tagihan</th>
                            <th>Nominal / Bulan</th>
                            <th>Sisa Bulan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $payment_options as $bill}
                        <tr>
                            <td>
                                {if $bill.type eq 'package'}
                                <input type="checkbox" class="cb-package" value="{$bill.recharge_id}">
                                {else}
                                <input type="checkbox" class="cb-bill" value="{$bill.name}">
                                {/if}
                            </td>
                            <td>{$bill.source}</td>
                            <td>{$bill.display_name}</td>
                            <td>{Lang::moneyFormat($bill.cost)}</td>
                            <td>{$bill.remaining}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
                <p><strong>Total tagihan aktif:</strong> {Lang::moneyFormat($payment_options_total)}</p>
                <button type="button" class="btn btn-primary cb-action-btn" onclick="submitSelectedPayment({$customer.id})">
                    <i class="fa fa-money"></i> Bayar Tagihan Terpilih
                </button>
                {else}
                <div class="alert alert-info">Tidak ada tagihan aktif.</div>
                {/if}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-success panel-hovered panel-stacked mb30">
            <div class="panel-heading">Tagihan Paket</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Paket</th>
                                <th>Router</th>
                                <th>Status Paket</th>
                                <th>Status Bayar</th>
                                <th>Expired</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $recharges as $idx => $item}
                            <tr>
                                <td>{$idx+1}</td>
                                <td>{$item.namebp}</td>
                                <td>{$item.routers}</td>
                                <td>
                                    {if $item.status eq 'on'}
                                    <span class="label label-success">Aktif</span>
                                    {else}
                                    <span class="label label-default">{$item.status}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $item.paid_by_expiration}
                                    <span class="label label-success">Sudah</span>
                                    {else}
                                    <span class="label label-danger">Belum</span>
                                    {/if}
                                </td>
                                <td>{Lang::dateAndTimeFormat($item.expiration, $item.time)}</td>
                                <td>
                                    {if !$item.can_pay}
                                    <span class="text-muted"><i class="fa fa-lock"></i> Sudah dibayar</span>
                                    {else}
                                    <button type="button" class="btn btn-xs btn-success cb-action-btn"
                                        onclick="submitPayment({$item.id}, {$customer.id}, this)">
                                        <i class="fa fa-credit-card"></i> Bayar
                                    </button>
                                    {if $item.is_expired}
                                    <button type="button" class="btn btn-xs btn-warning cb-action-btn"
                                        onclick="submitNunggak({$item.id}, {$customer.id})">
                                        <i class="fa fa-exclamation-triangle"></i> Nunggak
                                    </button>
                                    {else}
                                    <button type="button" class="btn btn-xs btn-warning" disabled title="Tombol nunggak aktif saat paket expired">
                                        <i class="fa fa-exclamation-triangle"></i> Nunggak
                                    </button>
                                    {/if}
                                    {/if}
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info">
                    Klik <strong>Bayar</strong> pada baris paket untuk membayar paket tertentu. Atau gunakan checklist TAGIHAN untuk membayar tagihan paket bulan ini dan additional bill sekaligus.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var cbSubmissionInProgress = false;
var cbCsrfToken = '{$csrf_token|escape:'javascript'}';
var cbRequestToken = '{$request_token|escape:'javascript'}';

function cbLockActionButtons() {
    var buttons = document.querySelectorAll('.cb-action-btn');
    buttons.forEach(function (btn) {
        btn.disabled = true;
        if (!btn.getAttribute('data-original-text')) {
            btn.setAttribute('data-original-text', btn.innerHTML);
        }
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
    });
}

function cbGuardSubmit() {
    if (cbSubmissionInProgress) {
        alert('Permintaan sedang diproses. Mohon tunggu...');
        return false;
    }
    cbSubmissionInProgress = true;
    cbLockActionButtons();
    return true;
}

function submitPayment(rechargeId, customerId) {
    if (!confirm('Proses pembayaran tagihan paket ini beserta additional bill yang dipilih?')) {
        return;
    }
    if (!cbGuardSubmit()) {
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '{$_url}plugin/customer_billing/pay';
    form.style.display = 'none';

    function addField(name, value) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value;
        form.appendChild(inp);
    }

    addField('customer_id', customerId);
    addField('recharge_ids[]', rechargeId);
    addField('csrf_token', cbCsrfToken);
    addField('request_token', cbRequestToken);

    var billCheckboxes = document.querySelectorAll('.cb-bill:checked');
    billCheckboxes.forEach(function (cb) {
        addField('bill_names[]', cb.value);
    });

    document.body.appendChild(form);
    form.submit();
}

function submitNunggak(rechargeId, customerId) {
    if (!confirm('Proses nunggak: update expired, buat invoice gateway nunggak, dan tambah additional bill jika belum ada?')) {
        return;
    }
    if (!cbGuardSubmit()) {
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '{$_url}plugin/customer_billing/nunggak';
    form.style.display = 'none';

    function addField(name, value) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value;
        form.appendChild(inp);
    }

    addField('customer_id', customerId);
    addField('recharge_id', rechargeId);
    addField('csrf_token', cbCsrfToken);
    addField('request_token', cbRequestToken);

    document.body.appendChild(form);
    form.submit();
}

function submitSelectedPayment(customerId) {
    var packageCheckboxes = document.querySelectorAll('.cb-package:checked');
    var billCheckboxes = document.querySelectorAll('.cb-bill:checked');
    if (packageCheckboxes.length === 0 && billCheckboxes.length === 0) {
        alert('Pilih minimal 1 tagihan');
        return;
    }

    if (packageCheckboxes.length > 1) {
        alert('Saat ini pembayaran checklist hanya bisa untuk 1 tagihan paket per proses');
        return;
    }

    if (!confirm('Proses pembayaran tagihan yang dipilih?')) {
        return;
    }
    if (!cbGuardSubmit()) {
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = packageCheckboxes.length > 0 ? '{$_url}plugin/customer_billing/pay' : '{$_url}plugin/customer_billing/pay_additional';
    form.style.display = 'none';

    function addField(name, value) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = value;
        form.appendChild(inp);
    }

    addField('customer_id', customerId);
    addField('csrf_token', cbCsrfToken);
    addField('request_token', cbRequestToken);
    packageCheckboxes.forEach(function (cb) {
        addField('recharge_ids[]', cb.value);
    });
    billCheckboxes.forEach(function (cb) {
        addField('bill_names[]', cb.value);
    });

    document.body.appendChild(form);
    form.submit();
}
</script>

{elseif $page_mode eq 'list'}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">
                Data Tagihan Pelanggan - {$month_label}
                {if in_array($_admin['user_type'], ['SuperAdmin','Admin'])}
                <a href="{$_url}plugin/customer_billing/agents" class="btn btn-xs btn-warning pull-right">
                    <i class="fa fa-users"></i> Kelola Agent
                </a>
                {/if}
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom:15px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cari Customer</label>
                            <input type="text" class="form-control" id="cb-search" placeholder="Nama / Username...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status Tagihan</label>
                            <select class="form-control" id="cb-status">
                                <option value="">-- Semua Status --</option>
                                <option value="lunas">Lunas</option>
                                <option value="belum">Belum Bayar</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Router</label>
                            <select class="form-control" id="cb-router">
                                <option value="">-- Semua Router --</option>
                                {foreach $all_routers as $router}
                                <option value="{$router|lower}">{$router}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Nunggak</label>
                            <select class="form-control" id="cb-nunggak">
                                <option value="">-- Semua --</option>
                                <option value="ya">Ada Nunggak</option>
                                <option value="tidak">Tidak Nunggak</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-default btn-sm" id="cb-reset">Reset</button>
                        </div>
                    </div>
                </div>
                <p><small class="text-muted" id="cb-count"></small></p>
                <div class="table-responsive cb-mobile-sticky-name">
                    <table class="table table-bordered table-hover table-striped" id="billing-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name Customer</th>
                                <th>Router</th>
                                <th>Jenis Paket</th>
                                <th>Paket/Plan</th>
                                <th>Harga Plan</th>
                                <th>Status Tagihan</th>
                                <th>Nunggak (bulan)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $rows as $row}
                            <tr data-fullname="{$row.fullname|lower}"
                                data-username="{$row.username|lower}"
                                data-status="{if $row.paid_this_month}lunas{else}belum{/if}"
                                data-routers="{$row.routers|lower}"
                                data-nunggak="{$row.arrears_months}">
                                <td>{$row.no}</td>
                                <td>
                                    <strong>{$row.fullname}</strong><br>
                                    <small>{$row.username}</small>
                                </td>
                                <td>{$row.routers}</td>
                                <td>{$row.package_type}</td>
                                <td>{$row.plan_name}</td>
                                <td>{$row.plan_price}</td>
                                <td>
                                    {if $row.paid_this_month}
                                    <span class="label label-success">Lunas</span>
                                    {else}
                                    <span class="label label-danger">Belum Bayar</span>
                                    {/if}
                                </td>
                                <td>{$row.arrears_months}</td>
                                <td>
                                    <a href="{$_url}plugin/customer_billing/detail/{$row.id}" class="btn btn-xs btn-info">
                                        <i class="fa fa-search"></i> Detail & Bayar
                                    </a>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>

                <hr>

                <h4 style="margin-top:10px;">Customer Expired</h4>
                <p><small class="text-muted">Daftar customer yang memiliki minimal 1 paket sudah expired.</small></p>
                <div class="table-responsive cb-mobile-sticky-name">
                    <table class="table table-bordered table-hover table-striped" id="billing-expired-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name Customer</th>
                                <th>Router</th>
                                <th>Jenis Paket</th>
                                <th>Paket/Plan</th>
                                <th>Jumlah Paket Expired</th>
                                <th>Expired Terakhir</th>
                                <th>Nunggak (bulan)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {if count($expired_rows) > 0}
                                {foreach $expired_rows as $row}
                                <tr>
                                    <td>{$row.no}</td>
                                    <td>
                                        <strong>{$row.fullname}</strong><br>
                                        <small>{$row.username}</small>
                                    </td>
                                    <td>{$row.routers}</td>
                                    <td>{$row.package_type}</td>
                                    <td>{$row.plan_name}</td>
                                    <td><span class="label label-danger">{$row.expired_package_count}</span></td>
                                    <td>
                                        {if $row.latest_expired_at}
                                            {date($_c['date_format'], $row.latest_expired_at)} {date('H:i:s', $row.latest_expired_at)}
                                        {else}
                                            -
                                        {/if}
                                    </td>
                                    <td>{$row.arrears_months}</td>
                                    <td>
                                        <a href="{$_url}plugin/customer_billing/detail/{$row.id}" class="btn btn-xs btn-info">
                                            <i class="fa fa-search"></i> Detail & Bayar
                                        </a>
                                    </td>
                                </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Tidak ada customer expired.</td>
                                </tr>
                            {/if}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var searchIn = document.getElementById('cb-search');
    var statusIn = document.getElementById('cb-status');
    var routerIn = document.getElementById('cb-router');
    var nunggakIn = document.getElementById('cb-nunggak');
    var resetBtn = document.getElementById('cb-reset');
    var tbody = document.querySelector('#billing-table tbody');
    var countEl = document.getElementById('cb-count');
    var rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];

    function applyFilter() {
        var search = searchIn.value.toLowerCase().trim();
        var status = statusIn.value;
        var router = routerIn.value;
        var nunggak = nunggakIn.value;
        var visible = 0;

        rows.forEach(function (tr) {
            var fullname = tr.getAttribute('data-fullname') || '';
            var username = tr.getAttribute('data-username') || '';
            var trStatus = tr.getAttribute('data-status') || '';
            var trRouters = tr.getAttribute('data-routers') || '';
            var trNunggak = parseInt(tr.getAttribute('data-nunggak') || '0', 10);

            var show = true;

            if (search && fullname.indexOf(search) === -1 && username.indexOf(search) === -1) {
                show = false;
            }
            if (status && trStatus !== status) {
                show = false;
            }
            if (router && trRouters.indexOf(router) === -1) {
                show = false;
            }
            if (nunggak === 'ya' && trNunggak <= 0) {
                show = false;
            }
            if (nunggak === 'tidak' && trNunggak > 0) {
                show = false;
            }

            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) {
            countEl.textContent = 'Menampilkan ' + visible + ' dari ' + rows.length + ' customer';
        }
    }

    searchIn.addEventListener('input', applyFilter);
    statusIn.addEventListener('change', applyFilter);
    routerIn.addEventListener('change', applyFilter);
    nunggakIn.addEventListener('change', applyFilter);
    resetBtn.addEventListener('click', function () {
        searchIn.value = '';
        statusIn.value = '';
        routerIn.value = '';
        nunggakIn.value = '';
        applyFilter();
    });

    applyFilter();
})();
</script>
{/if}

{* ─── MODE: agents list ─────────────────────────────────────────── *}
{if $page_mode eq 'agents'}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">
                <i class="fa fa-users"></i> Manajemen Assignment Customer per Agent
                <a href="{$_url}plugin/customer_billing" class="btn btn-xs btn-default pull-right">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="panel-body">
                {if count($agents) > 0}
                <table class="table table-bordered table-hover table-striped">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Agent</th>
                            <th>Username</th>
                            <th>Customer Assigned</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $agents as $idx => $ag}
                        <tr>
                            <td>{$idx+1}</td>
                            <td><strong>{$ag.fullname}</strong></td>
                            <td>{$ag.username}</td>
                            <td>
                                <span class="badge">{$ag.customer_count} customer</span>
                            </td>
                            <td>
                                <a href="{$_url}plugin/customer_billing/agent_assign/{$ag.id}"
                                    class="btn btn-sm btn-info">
                                    <i class="fa fa-pencil"></i> Kelola
                                </a>
                            </td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
                {else}
                <div class="alert alert-warning">
                    Tidak ada user Agent. Tambahkan agent di
                    <a href="{$_url}settings/users">Settings &rsaquo; Users</a>.
                </div>
                {/if}
            </div>
        </div>
    </div>
</div>
{/if}

{* ─── MODE: agent_assign (pilih customer untuk agent) ──────────── *}
{if $page_mode eq 'agent_assign'}
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">
                <i class="fa fa-user"></i>
                Assign Customer ke Agent: <strong>{$agent.fullname}</strong>
                <a href="{$_url}plugin/customer_billing/agents" class="btn btn-xs btn-default pull-right">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
                <span class="badge pull-right" id="selected-count" style="margin-right:8px;">{$assigned_count} dipilih</span>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" id="aa-filter" class="form-control"
                                placeholder="Filter nama / username / nomor HP...">
                        </div>
                    </div>
                    <div class="col-md-6 text-right" style="padding-top:4px;">
                        <button type="button" class="btn btn-xs btn-default" onclick="aaSelectAll()">
                            <i class="fa fa-check-square-o"></i> Pilih Semua
                        </button>
                        <button type="button" class="btn btn-xs btn-default" onclick="aaDeselectAll()">
                            <i class="fa fa-square-o"></i> Batal Semua
                        </button>
                        <span class="text-muted" style="margin-left:8px;">
                            Tampil: <span id="aa-visible">{count($all_customers)}</span>
                        </span>
                    </div>
                </div>

                <form method="post" action="{$_url}plugin/customer_billing/agent_save">
                    <input type="hidden" name="agent_id" value="{$agent.id}">
                    <input type="hidden" name="csrf_token" value="{$csrf_token}">
                    <input type="hidden" name="request_token" value="{$request_token}">
                    <div style="max-height:430px; overflow-y:auto; border:1px solid #ddd; border-radius:4px;">
                        <table class="table table-bordered table-hover" style="margin:0;" id="aa-table">
                            <thead style="position:sticky;top:0;background:#ecf0f1;z-index:1;">
                                <tr>
                                    <th style="width:40px;text-align:center;">
                                        <input type="checkbox" id="aa-cb-all" onchange="aaToggleVisible(this.checked)">
                                    </th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>No. HP</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $all_customers as $cust}
                                <tr class="aa-row {if $cust.is_assigned}success{/if}"
                                    data-name="{$cust.fullname|lower}"
                                    data-username="{$cust.username|lower}"
                                    data-phone="{$cust.phonenumber|lower}">
                                    <td style="text-align:center;vertical-align:middle;">
                                        <input type="checkbox" class="aa-cb"
                                            name="customer_ids[]"
                                            value="{$cust.id}"
                                            {if $cust.is_assigned}checked{/if}>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <strong>{$cust.fullname}</strong>
                                        {if $cust.is_assigned}
                                        <span class="label label-success" style="margin-left:4px;">assigned</span>
                                        {/if}
                                    </td>
                                    <td style="vertical-align:middle;">{$cust.username}</td>
                                    <td style="vertical-align:middle;">{$cust.phonenumber}</td>
                                    <td style="vertical-align:middle;">
                                        {if $cust.status eq 'Active'}
                                        <span class="label label-success">Active</span>
                                        {else}
                                        <span class="label label-default">{$cust.status}</span>
                                        {/if}
                                    </td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:12px;">
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('Simpan? Customer yang tidak dicentang akan dilepas dari agent ini.')">
                            <i class="fa fa-save"></i> Simpan
                            (<span id="aa-btn-count">{$assigned_count}</span> customer)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var filterIn = document.getElementById('aa-filter');
    var rows     = Array.from(document.querySelectorAll('#aa-table tbody tr.aa-row'));
    var visEl    = document.getElementById('aa-visible');
    var selEl    = document.getElementById('selected-count');
    var btnEl    = document.getElementById('aa-btn-count');

    function updateCounts() {
        var vis = rows.filter(function(r){ return r.style.display !== 'none'; }).length;
        var sel = document.querySelectorAll('.aa-cb:checked').length;
        if (visEl) visEl.textContent = vis;
        if (selEl) selEl.textContent = sel + ' dipilih';
        if (btnEl) btnEl.textContent = sel;
    }

    filterIn.addEventListener('input', function () {
        var q = filterIn.value.toLowerCase().trim();
        rows.forEach(function (tr) {
            var ok = !q ||
                tr.getAttribute('data-name').indexOf(q) !== -1 ||
                tr.getAttribute('data-username').indexOf(q) !== -1 ||
                tr.getAttribute('data-phone').indexOf(q) !== -1;
            tr.style.display = ok ? '' : 'none';
        });
        updateCounts();
    });

    document.querySelectorAll('.aa-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('tr').className = 'aa-row' + (cb.checked ? ' success' : '');
            updateCounts();
        });
    });

    window.aaToggleVisible = function (checked) {
        rows.forEach(function (tr) {
            if (tr.style.display !== 'none') {
                var cb = tr.querySelector('.aa-cb');
                if (cb) { cb.checked = checked; tr.className = 'aa-row' + (checked ? ' success' : ''); }
            }
        });
        updateCounts();
    };
    window.aaSelectAll   = function () { document.getElementById('aa-cb-all').checked = true;  aaToggleVisible(true); };
    window.aaDeselectAll = function () { document.getElementById('aa-cb-all').checked = false; aaToggleVisible(false); };

    updateCounts();
})();
</script>
{/if}

{include file="sections/footer.tpl"}
