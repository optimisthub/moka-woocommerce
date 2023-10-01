jQuery(document).ready(function ($) {
    console.log('Moka Pay js loaded.');

    $('.js-update-comission-rates').click(function (e) {
        e.preventDefault();
        let _thiz = $(this);
        let r = prompt(moka_ajax.update_comission);
        if (r && (r == 'onay' || r == 'confirmation')) {
            _thiz.prop('disabled', true);
            $.post(moka_ajax.ajax_url, {
                action: 'optimisthub_ajax',
                method: 'clear_installment'
            }, function (response) {
                _thiz.prop('disabled', false);
                if (response.status === true) {
                    alert(moka_ajax.success_redirection);
                    setTimeout(function () {
                        window.location.reload();
                    }, 2e3);
                }
            }, 'json').fail(function () {
                _thiz.prop('disabled', false);
                alert(moka_ajax.failed);
            });
        }
    });

    $('.subscription-cancelManually').click(function (e) {
        e.preventDefault();
        let _thiz = $(this);
        let cancelSubscription = window.confirm(moka_ajax.subscription_confirm);
        if (cancelSubscription) {
            _thiz.hide();
            $.post(moka_ajax.ajax_url, {
                action: 'optimisthub_ajax',
                method: 'cancel_subscription',
                orderId: _thiz.attr('data-order-id'),
            }, function (response) {
                if (response.status === true) {
                    _thiz.remove();
                    alert(response.messsage);
                    setTimeout(function () {
                        window.location.reload(true);
                    }, 2e3);
                } else {
                    _thiz.show();
                    alert(response.messsage);
                }
            }, 'json').fail(function () {
                _thiz.show();
                alert(moka_ajax.failed);
            });
        }
    });

    $('.moka-admin-dotest').click(function (e) {
        e.preventDefault();
        let _thiz = $(this);
        _thiz.prop('disabled', true);
        $.post(moka_ajax.ajax_url, {
            action: 'optimisthub_ajax',
            method: 'moka_test'
        }, function (response) {
            _thiz.prop('disabled', false);
            if (response.status === true) {
                $('.moka-admin-test-results').prepend(
                    $('<p>', {
                        'text': moka_ajax.installment_test + ': '
                    }).append(
                        $('<span>', {
                            'class': 'moka-' + (response.commissioncheck ? 'success' : 'failed'),
                            'text': (response.commissioncheck ? moka_ajax.success : moka_ajax.failed),
                        })
                    ),
                    $('<p>', {
                        'text': moka_ajax.bin_test + ': '
                    }).append(
                        $('<span>', {
                            'class': 'moka-' + (response.bincheck ? 'success' : 'failed'),
                            'text': (response.bincheck ? moka_ajax.success : moka_ajax.failed),
                        })
                    ),
                    $('<p>', {
                        'text': moka_ajax.remote_test + ': '
                    }).append(
                        $('<span>', {
                            'class': 'moka-' + (response.remote ? 'success' : 'failed'),
                            'text': (response.remote ? moka_ajax.success : moka_ajax.failed),
                        })
                    ),
                    $('<hr>')
                )
            } else {
                alert(response.messsage);
            }
        }, 'json').fail(function () {
            _thiz.prop('disabled', false);
            alert(moka_ajax.failed);
        });
    });

    $('.moka-admin-savesettings').click(function (e) {
        e.preventDefault();
        $('button[name="save"]').click();
    });

    if ($('td label[for="woocommerce_mokapay_debugMode"]').length) {
        if ($('#woocommerce_mokapay_debugMode').is(':checked')) {
            $('td label[for="woocommerce_mokapay_debugMode"]').parent().append(
                $('<button>', {
                    'type': 'button',
                    'class': 'mokapay-debug-download',
                    'text': moka_ajax.download_debug,
                }),
                $('<span>', {
                    'text': ' ',
                }),
                $('<button>', {
                    'type': 'button',
                    'class': 'mokapay-debug-clear',
                    'text': moka_ajax.clear_debug,
                })
            );
            $(document).on('click', '.mokapay-debug-download', function (e) {
                e.preventDefault();
                let _thiz = $(this);
                _thiz.prop('disabled', true);
                $.post(moka_ajax.ajax_url, {
                    action: 'optimisthub_ajax',
                    method: 'debug_download'
                }, function (response) {
                    if (response.status === true) {
                        $.when($('td label[for="woocommerce_mokapay_debugMode"]').parent().append(
                            $('<a>', {
                                'href': response.file,
                                'download': response.filename,
                                'style': 'display:none',
                                'id': 'mokapay-debug-downloadfile',
                            })
                        )).then(function () {
                            $('#mokapay-debug-downloadfile')[0].click();
                            setTimeout(function () {
                                _thiz.prop('disabled', false);
                                $('#mokapay-debug-downloadfile').remove();
                            }, 1e3)
                        });
                    } else {
                        _thiz.prop('disabled', false);
                        alert(response.message);
                    }
                }, 'json').fail(function () {
                    _thiz.prop('disabled', false);
                    alert(moka_ajax.failed);
                });
            });
            $(document).on('click', '.mokapay-debug-clear', function (e) {
                e.preventDefault();
                let _thiz = $(this);
                _thiz.prop('disabled', true);
                $.post(moka_ajax.ajax_url, {
                    action: 'optimisthub_ajax',
                    method: 'debug_clear'
                }, function (response) {
                    _thiz.prop('disabled', false);
                    alert(response.message);
                }, 'json').fail(function () {
                    _thiz.prop('disabled', false);
                    alert(moka_ajax.failed);
                });
            });
        }
    }
});