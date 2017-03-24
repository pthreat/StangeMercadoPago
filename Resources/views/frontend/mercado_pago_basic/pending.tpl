{extends file='frontend/index/index.tpl'}

{block name='frontend_index_content'}
	<div class="container">
		<h1>{$PAYMENT_PENDING}</h1>
		<a class="btn" href="{url controller=front action=index}">{$CONTINUE_SHOPPING}</a>
	</div>
{/block}
