{extends file="frontend/index/index.tpl"}

{block name="frontend_index_content"}
	<style>
		#payment{
			overflow-x:hidden;
			width:75%;
			height:100vh;
			display:block;
			border:1px solid #ccc;
			border-radius:5px;
			margin-top:0.5em;
			margin-left:0.5em;
		}
	</style>

	<iframe name="MP-Checkout" id="payment" src="{$gatewayUrl}" frameborder="0"></iframe>

{/block}
