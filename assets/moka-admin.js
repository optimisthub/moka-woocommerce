jQuery(document).ready(function ($) {
    console.log('Moka Pay js loaded.');

    /**
     * Clear stored installments
     */

    $('.js-update-comission-rates').click(function(e){
        let _thiz = $(this);
        _thiz.prop('disabled', true);
        let r = prompt(moka_ajax.update_comission);
        if( r && ( r == 'onay' || r == 'confirmation' ) ){
            $.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action : 'optimisthub_ajax',
                method : 'clear_installment'
            }, function(response) {
                _thiz.prop('disabled', false);
                if(response.data.data.message == 'ok')
                {
                    alert(moka_ajax.success_redirection);
                    setTimeout(function(){
                        window.location.reload();
                    }, 2e3);
                }  
            }, 'json');
        }else{
            _thiz.prop('disabled', false);
        }
    });

    jQuery('.subscription-cancelManually').click(function (e) { 
        e.preventDefault();
        let $orderId = jQuery(this).attr('data-order-id');
        let cancelSubscription = window.confirm(moka_ajax.subscription_confirm);
        if (cancelSubscription) {
            $.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action  : 'optimisthub_ajax',
                method  : 'cancel_subscription',
                orderId : $orderId, 
            }, function(response) {
                if(response.data)
                {
                    if(response.data.data.error) {
                        alert(response.data.data.error);
                    } else { 
                        alert(response.data.data.messsage); 
                        window.location.reload();
                    }
                } 
            }, 'json');
        }  
    });

    $('.moka-admin-dotest').click(function(e){
        let _thiz = $(this);
        _thiz.prop('disabled', true);
        $.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
            action : 'optimisthub_ajax',
            method : 'moka_admin_test'
        }, function(response) {
            if(response.data.data.message != ''){
                alert(response.data.data.messsage);
            }else{
                $('.moka-admin-test-results').prepend(
                    $('<p>',{
                        'text':moka_ajax.installment_test+': '
                    }).append(
                        $('<span>',{
                            'class':'moka-'+(response.data.data.commissioncheck ? 'success' : 'failed'),
                            'text': (response.data.data.commissioncheck ? moka_ajax.success : moka_ajax.failed),
                        })
                    ),
                    $('<p>',{
                        'text':moka_ajax.bin_test+': '
                    }).append(
                        $('<span>',{
                            'class':'moka-'+(response.data.data.bincheck ? 'success' : 'failed'),
                            'text': (response.data.data.bincheck ? moka_ajax.success : moka_ajax.failed),
                        })
                    )
                )
            }
            _thiz.prop('disabled', false);
        }, 'json');
    });
});