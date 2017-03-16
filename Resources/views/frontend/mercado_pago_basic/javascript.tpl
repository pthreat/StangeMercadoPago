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

	<script>
		window.addEventListener('load',function(){

			$MPC.openCheckout ({
										url: "{$gatewayUrl}",
										mode: "{$jsMode}",
										onreturn: function(data) {
											// execute_my_onreturn (Sólo modal)
										}
			});

		});
	</script>

	<script type="text/javascript" src="//secure.mlstatic.com/mptools/render.js"></script>

{/block}
