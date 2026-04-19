{include file="sections/header.tpl"}
<div class="row">
    <div class="col-md-5">
        <div class="panel panel-primary panel-hovered mb20">
            <div class="panel-heading">Discount Management</div>
            <div class="panel-body">
                <form method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-md-4 control-label">Nama Diskon</label>
                        <div class="col-md-8">
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Promo Midtrans" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Tipe Diskon</label>
                        <div class="col-md-8">
                            <select name="type" class="form-control">
                                <option value="percent">Percent (%)</option>
                                <option value="amount">Nominal</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Nilai</label>
                        <div class="col-md-8">
                            <input type="number" name="value" class="form-control" step="0.01" min="0.01" placeholder="Masukkan nilai diskon" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Gateway</label>
                        <div class="col-md-8">
                            <select name="gateway" class="form-control">
                                <option value="">All Gateways</option>
                                {foreach $gateways as $gateway}
                                    <option value="{$gateway}">{ucwords($gateway)}</option>
                                {/foreach}
                            </select>
                            <p class="help-block">Kosongkan jika diskon berlaku untuk semua gateway.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-4 control-label">Plan</label>
                        <div class="col-md-8">
                            <select name="plan_id" class="form-control">
                                <option value="0">All Plans</option>
                                {foreach $plans as $plan}
                                    <option value="{$plan.id}">{$plan.name_plan} - {$plan.type} ({$plan.routers})</option>
                                {/foreach}
                            </select>
                            <p class="help-block">Pilih plan tertentu jika diskon hanya berlaku untuk plan itu.</p>
                        </div>
                    </div>

                    <div class="form-group mb0">
                        <div class="col-md-8 col-md-offset-4">
                            <button type="submit" class="btn btn-primary btn-block">Tambah Discount</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="panel panel-default panel-hovered">
            <div class="panel-heading">Daftar Discount</div>
            <div class="panel-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Gateway</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $discounts as $d}
                            <tr>
                                <td>{$d->name}</td>
                                <td>{if $d->type == 'percent'}Percent{else}Nominal{/if}</td>
                                <td>{if $d->type == 'percent'}{$d->value}%{else}{Lang::moneyFormat($d->value)}{/if}</td>
                                <td>{$d->gateway_name}</td>
                                <td>{$d->plan_name}</td>
                                <td>
                                    {if $d->status == 1}
                                        <span class="label label-success">Aktif</span>
                                    {else}
                                        <span class="label label-default">Nonaktif</span>
                                    {/if}
                                </td>
                                <td>
                                    <a href="{$app_url}?_route=plugin/discount_toggle&id={$d->id}" class="btn btn-xs btn-warning">Toggle</a>
                                    <a href="{$app_url}?_route=plugin/discount_delete&id={$d->id}" class="btn btn-xs btn-danger"
                                        onclick="return confirm('Hapus discount ini?');">Hapus</a>
                                </td>
                            </tr>
                        {foreachelse}
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada discount.</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}