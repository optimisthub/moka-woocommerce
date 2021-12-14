$ = jQuery;
$.ajaxSetup({cache: false});

$(document).ready(function () {
    console.log('Moka Pay js loaded.');
    
    /**
     * Bin Number Request 
     */
    $(document).on('keyup','input#mokapay-card-number',function( e ) {  
        let binValue = $(this).val();
        binValue = binValue.replace(/\s/g, '');
        if(binValue.length == 6) {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: moka_ajax.ajax_url,
                data: {
                    action : 'optimisthub_ajax',
                    method : 'validate_bin',
                    binNumber : binValue,
                },
                success: function(response){
                    $('#ajaxify-installment-table').html('');
                    $('#ajaxify-installment-table').html(response.data.data.renderedHtml);
                    /*
                    $('#ajaxify-installment-table').append('Maksimum Taksit' + response.data.data.MaxInstallmentNumber + '<br>');
                    $('#ajaxify-installment-table').append('Kart' + response.data.data.CardName + '<br>');
                    $('#ajaxify-installment-table').append('Banka' + response.data.data.BankName + '<br>');
                    $('#ajaxify-installment-table').append('Grup' + response.data.data.GroupName + '<br>');
                    $('#ajaxify-installment-table').append('<img src="'+response.data.data.CardTemplate+'">');*/
                }
            });
        } 
    });
 
});