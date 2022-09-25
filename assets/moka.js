jQuery.ajaxSetup({cache: false});

jQuery(document).ready(function () {
    console.info('Moka PAY Core Js File loaded, successfully.');
    
    /**
     * Bin Number Request 
     */
    jQuery(document).on('keyup','input#mokapay-card-number',function( e ) {  
        let binValue = jQuery(this).val();
        let total = jQuery('#mokapay-current-order-total').val();
        binValue = binValue.replace(/\s/g, '');
        if(binValue.length >= 6) {
            jQuery.ajax({
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
                    jQuery('#ajaxify-installment-table').html('');
                    jQuery('#ajaxify-installment-table').html(response.data.data.renderedHtml); 
                }
            });
        } 
    });
 
});