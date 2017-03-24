{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content'}
	<div class="container">
		<h1>{$PAYMENT_CANCEL}</h1>
		<a class="btn" href="{url controller=checkout action=shippingPayment sTarget=checkout}">{$USE_DIFFERENT_PAYMENT_METHOD}</a>
	</div>
{/block}
