$ = jQuery;
$.ajaxSetup({cache: false});

$(document).ready(function () {
    console.log('Moka Pay js loaded.');
    
    /**
     * Bin Number Request 
     */
    $(document).on('keyup','input#mokapay-card-number',function( e ) {  
        let binValue = $(this).val();
        let total = $('#mokapay-current-order-total').val();
        binValue = binValue.replace(/\s/g, '');
        if(binValue.length >= 6) {
            $.ajax({
                method: "POST",
                dataType: "json",
                url: moka_ajax.ajax_url,
                data: {
                    action : 'optimisthub_ajax',
                    method : 'validate_bin',
                    binNumber : binValue,
                    total : total 
                },
                success: function(response){
                    $('#ajaxify-installment-table').html('');
                    $('#ajaxify-installment-table').html(response.data.data.renderedHtml); 
                }
            });
        } 
    });
 
});