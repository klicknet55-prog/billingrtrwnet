{include file="sections/header.tpl"}

<div class="panel panel-primary">
    <div class="panel-heading">Select Payment Channel</div>
    <div class="panel-body">
        {foreach $channels as $channel}
            <a class="btn btn-default btn-block"
               href="{$_url}order/buy/{$path}/ipaymu/{$channel['id']}">
                {$channel['name']}
            </a>
        {/foreach}
    </div>
</div>

{include file="sections/footer.tpl"}