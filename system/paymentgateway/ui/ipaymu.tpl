{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/ipaymu" >
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">IPAYMU</div>
                <div class="panel-body">

                    <div class="form-group">
                        <label class="col-md-2 control-label">Virtual Account (VA)</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" 
                                   name="ipaymu_va" 
                                   placeholder="1179000xxxx"
                                   value="{$_c['ipaymu_va']}">
                            <a href="https://my.ipaymu.com" target="_blank" class="help-block">
                                Ambil VA di Dashboard iPaymu
                            </a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">API Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" 
                                   name="ipaymu_api_key" 
                                   placeholder="xxxxxxxxxxxxxxxx"
                                   value="{$_c['ipaymu_api_key']}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">Mode</label>
                        <div class="col-md-6">
                            <select name="ipaymu_mode" class="form-control">
                                <option value="sandbox" {if $_c['ipaymu_mode'] == 'sandbox'}selected{/if}>Sandbox</option>
                                <option value="production" {if $_c['ipaymu_mode'] == 'production'}selected{/if}>Production</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label">{Lang::T('Url Callback Proyek')}</label>
                        <div class="col-md-6">
                            <input type="text" readonly class="form-control"
                                   onclick="this.select()"
                                   value="{$_url}callback/ipaymu">
                            <a href="https://my.ipaymu.com" target="_blank" class="help-block">
                                Set URL ini di Dashboard iPaymu → Notify URL
                            </a>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">
                                {Lang::T('Save Change')}
                            </button>
                        </div>
                    </div>

<pre>/ip hotspot walled-garden
add dst-host=my.ipaymu.com
add dst-host=*.ipaymu.com</pre>

<small class="form-text text-muted">
{Lang::T('Set Telegram Bot to get any error and notification')}
</small>

                </div>
            </div>
        </div>
    </div>
</form>

{include file="sections/footer.tpl"}