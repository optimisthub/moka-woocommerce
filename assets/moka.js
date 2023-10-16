jQuery(document).ready(function () {
    console.info('%cMoka PAY Core Js File loaded, successfully. Version ' + moka_ajax.version, 'background:#3465b1; color:#ffffff; padding:1px 3px;');
    let binCache = false;
    let binXhr = false;
    let binInstallment = 1;

    /* 
    * Bin Cache Clear
    */
    jQuery(document).on('update_checkout', function () {
        binCache = false;
        binXhr = false;
        jQuery('input[name="mokapay-installment').val(binInstallment);
    });

    /**
     * Bin Number Request 
     */
    jQuery(document).on('input', 'input#mokapay-card-number', function (e) {
        let binValue = jQuery(this).val();
        let state = jQuery('#mokapay-current-order-state').val();
        binValue = binValue.replace(/\s/g, '');
        if (binValue.length >= 6 && binValue.substr(0, 6) != binCache) {
            if (typeof binXhr === 'object') {
                binXhr.abort();
            }
            binxhr = jQuery.post(moka_ajax.ajax_url, {
                action: 'optimisthub_ajax',
                method: 'validate_bin',
                binNumber: binValue,
                state: state,
            }, function (response) {
                binXhr = false;
                binInstallment = 1;
                if (response.status === true) {
                    binCache = binValue.substr(0, 6);
                    jQuery('#ajaxify-installment-table').html(response.html);
                }
            }, 'json').fail(function () {
                binXhr = false;
                binInstallment = 1;
                $('input#mokapay-card-number').trigger('change');
            });
        }
    });

    /**
     * Installment Change 
     */
    jQuery(document).on('change', 'input[name="mokapay-installment"]', function (e) {
        binInstallment = jQuery(this).val();
    });

    /**
     * Cancel Subscription
     */

    jQuery('.subscription-cancelManually').click(function (e) {
        e.preventDefault();
        let _thiz = jQuery(this);
        let cancelSubscription = window.confirm(moka_ajax.subscription_confirm);
        if (cancelSubscription) {
            _thiz.hide();
            jQuery('#subscription_ajax_response').html('');
            jQuery.post(moka_ajax.ajax_url, {
                action: 'optimisthub_ajax',
                method: 'cancel_subscription',
                orderId: _thiz.attr('data-order-id'),
            }, function (response) {
                if (response.status === true) {
                    _thiz.remove();
                    jQuery('#subscription_ajax_response').append(
                        $('<p>', {
                            'class': 'message',
                            'text': response.messsage,
                        })
                    );
                    setTimeout(function () {
                        window.location.reload();
                    }, 3e3);
                } else {
                    _thiz.show();
                    jQuery('#subscription_ajax_response').append(
                        $('<p>', {
                            'text': response.messsage,
                        })
                    );
                }
            }, 'json').fail(function () {
                _thiz.show();
                alert(moka_ajax.failed);
            });
        }
    });

});