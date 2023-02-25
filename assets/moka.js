jQuery.ajaxSetup({cache: false});

jQuery(document).ready(function () {
    console.info('Moka PAY Core Js File loaded, successfully. Version 3.7.0);
    
    /**
     * Bin Number Request 
     */
    jQuery(document).on('blur keyup click change','input#mokapay-card-number',function( e ) {  
        e.preventDefault();
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

    /**
     * Cancel Subscription
     */

    jQuery('.subscription-cancelManually').click(function (e) { 
        e.preventDefault();
        let $orderId = jQuery(this).attr('data-order-id');
        var cancelSubscription = window.confirm("Onaylıyor iseniz, aboneliğiniz iptal edilecek ve ödemesi yenilenmeyecek.Ancak; aboneliğinizi üyelik sonlanma tarihine dek kullanmaya devam edebileceksiniz.");
        if (cancelSubscription) {
            jQuery.ajax({
                method: "POST",
                dataType: "json",
                url: moka_ajax.ajax_url,
                data: {
                    action  : 'optimisthub_ajax',
                    method  : 'cancel_subscription',
                    orderId : $orderId, 
                },
                success: function(response){
                    if(response.data)
                    {
                        if(response.data.data.error) {
                            jQuery('#subscription_ajax_response').html(`<p>${response.data.data.error}</p>`);
                        } else {
                            jQuery('#subscription_ajax_response').html(`<p class="message">${response.data.data.messsage}</p>`);
                            setTimeout(function(){
                                window.location.reload();
                            },3000);
                        }
                    }
                }
            });
        }  
    });
 
});