{extends file="frontend/index/index.tpl"}

{block name="frontend_index_content"}
	<script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
    <script type="text/javascript" src="https://secure.mlstatic.com/org-img/checkout/custom/1.0/checkout.js"></script>

    <h1><a href="http://developers.mercadopago.com/documentacion/checkout-personalizado-avanzado?lang=es_AR">Checkout personalizado (avanzado)</a></h1>

    <form action="" method="post" id="form-pagar-mp">
      <input id="amount" type="hidden" value="100"/>
      <p>N&uacute;mero de Tarjeta: <input data-checkout="cardNumber" type="text"/></p>
      <p>C&oacute;digo de Seguridad: <input data-checkout="securityCode" type="text"/></p>
      <p>Mes de Expiraci&oacute;n: <input data-checkout="cardExpirationMonth" type="text"/></p>
      <p>A&ntilde;o de Expiraci&oacute;n: <input data-checkout="cardExpirationYear" type="text"/></p>
      <p>Titular de la Tarjeta: <input data-checkout="cardholderName" type="text"/></p>
      <p>N&uacute;mero de Documento: <input data-checkout="docNumber" type="text"/></p>
      
      <input data-checkout="docType" type="hidden" value="DNI"/>
      <p id="issuersField">Bancos: <select id="issuersOptions"></select>
      <p>Cuotas: <select id="installmentsOption"></select>
      <p><input type="submit" value="Realizar Pago"></p>
    </form>

    <script type="text/javascript">
      /* Reemplaza por tu public_key */
      Checkout.setPublishableKey("TEST-675c3aa8-044f-42b1-9605-0685eb403354");

      $("input[data-checkout='cardNumber']").bind("keyup",function(){
        var bin = $(this).val().replace(/ /g, '').replace(/-/g, '').replace(/\./g, '');
        if (bin.length == 6){
          Checkout.getPaymentMethod(bin,setPaymentMethodInfo);
        }
      });

      // Establece la información del medio de pago obtenido
      function setPaymentMethodInfo(status, result){
        $.each(result, function(p, r){
            $.each(r.labels, function(pos, label){
                if (label == "recommended_method") {
                    Checkout.getInstallments(r.id ,parseFloat($("#amount").val()), setInstallmentInfo);
                    Checkout.getCardIssuers(r.id,showIssuers);
                    return;
                }
            });
        });
      };

      // Muestra las cuotas disponibles en el div 'installmentsOption'
      function setInstallmentInfo(status, installments){
          var html_options = "";
          for(i=0; installments && i<installments.length; i++){
              html_options += "<option value='"+installments[i].installments+"'>"+installments[i].installments +" de "+installments[i].share_amount+" ("+installments[i].total_amount+")</option>";
          };
          $("#installmentsOption").html(html_options);
        };

      function showIssuers(status, issuers){
        var i,options="<select data-checkout='cardIssuerId'><option value='-1'>Elige...</option>";
        for(i=0; issuers && i<issuers.length;i++){
          options+="<option value='"+issuers[i].id+"'>"+issuers[i].name +" </option>";
        }
        options+="</select>";
        if(issuers.length>0){
          $("#issuersOptions").html(options);
        }else{
          $("#issuersOptions").html("");
          $("#issuersField").hide();
        }
      };

      $("#issuersOptions").change(function(){
          var bin = $("input[data-checkout='cardNumber']").val().replace(/ /g, '').replace(/-/g, '').replace(/\./g, '').slice(0, 6);
          Checkout.getInstallmentsByIssuerId(bin,this.value,parseFloat($("#amount").val()),setInstallmentInfo);
      });

      $("#form-pagar-mp").submit(function( event ) {
          var $form = $(this);
          Checkout.createToken($form, mpResponseHandler);
          event.preventDefault();
          return false;
      });

      var mpResponseHandler = function(status, response) {
        var $form = $('#form-pagar-mp');

        if (response.error) {
          alert("ocurri&oacute; un error: "+JSON.stringify(response));
        } else {
          var card_token_id = response.id;
          $form.append($('<input type="hidden" id="card_token_id" name="card_token_id"/>').val(card_token_id));
          alert("card_token_id: "+card_token_id);
          $form.get(0).submit();
        }
      }
    </script>
{/block}
