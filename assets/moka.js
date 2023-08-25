jQuery(document).ready(function () {
    console.info('Moka PAY Core Js File loaded, successfully. Version 3.7.5');
    let binCache = '';

    /**
     * Bin Number Request 
     */
    jQuery(document).on('blur keyup click change','input#mokapay-card-number', function( e ) {  
        let binValue = jQuery(this).val();
        let total = jQuery('#mokapay-current-order-total').val();
        binValue = binValue.replace(/\s/g, '');
        if(binValue.length >= 6 && binValue.substr(0, 6) != binCache) {
            binCache = binValue.substr(0, 6);
            jQuery.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action : 'optimisthub_ajax',
                method : 'validate_bin',
                binNumber : binValue,
                total : total,
            }, function(response) {
                jQuery('#ajaxify-installment-table').html(response.data.data.renderedHtml); 
            }, 'json');
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
            jQuery.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action  : 'optimisthub_ajax',
                method  : 'cancel_subscription',
                orderId : $orderId, 
            }, function(response){
                if(response.data) {
                    if(response.data.data.error) {
                        jQuery('#subscription_ajax_response').html(`<p>${response.data.data.error}</p>`);
                    } else {
                        jQuery('#subscription_ajax_response').html(`<p class="message">${response.data.data.messsage}</p>`);
                        setTimeout(function(){
                            window.location.reload();
                        }, 3e3);
                    }
                }
            }, 'json');
        }  
    });
 
});