jQuery(document).ready(function () {
    console.info('Moka PAY Core Js File loaded, successfully. Version ' + moka_ajax.version);
    let binCache = false;

    /* 
    * Bin Cache Clear
    */
    jQuery(document).on('update_checkout', function(){
        binCache = false;
    });
    /**
     * Bin Number Request 
     */
    jQuery(document).on('blur keyup click change','input#mokapay-card-number', function( e ) {  
        let binValue = jQuery(this).val();
        let state = jQuery('#mokapay-current-order-state').val();
        binValue = binValue.replace(/\s/g, '');
        if(binValue.length >= 6 && binValue.substr(0, 6) != binCache) {
            jQuery.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action : 'optimisthub_ajax',
                method : 'validate_bin',
                binNumber : binValue,
                state : state,
            }, function(response) {
                jQuery('#ajaxify-installment-table').html(response.data.data.renderedHtml); 
                if(response.data.data.cardInformation !== false){
                    binCache = binValue.substr(0, 6);
                }
            }, 'json');
        } 
    }); 

    /**
     * Cancel Subscription
     */

    jQuery('.subscription-cancelManually').click(function (e) { 
        e.preventDefault();
        let $orderId = jQuery(this).attr('data-order-id');
        var cancelSubscription = window.confirm(moka_ajax.subscription_confirm);
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