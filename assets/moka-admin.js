jQuery(document).ready(function ($) {
    console.log('Moka Pay js loaded.');

    /**
     * Clear stored installments
     */

    $('.js-update-comission-rates').click(function(e){
        var r = prompt("Bu işlemi yaptığınızda, girmiş olduğunuz taksit verilerinin tamamı silinir. Ve Moka Pay sunucularından güncel olanları üzerine yazılır. Ve işlem geri alınamaz. Devam etmek için lütfen alttaki alana 'onay' yazıp işleme devam ediniz.Aksi halde işlemniz devam etmeyecektir.");
        if(r && r == 'onay'){
            $.post(moka_ajax.ajax_url + '?_=' + Date.now(), {
                action : 'optimisthub_ajax',
                method : 'clear_installment'
            }, function(response) {
                if(response.data.data.message == 'ok')
                {
                    alert('İşleminiz başarılı bir şekilde tamamlandı. 2 Saniye içerisinde sayfa yenilecektir.')
                    setTimeout(function(){
                        window.location.reload();
                    }, 2e3);
                }  
            }, 'json');
        }
    });

    jQuery('.subscription-cancelManually').click(function (e) { 
        e.preventDefault();
        let $orderId = jQuery(this).attr('data-order-id');
        var cancelSubscription = window.confirm("Onaylıyor iseniz, aboneliğiniz iptal edilecek ve ödemesi yenilenmeyecek.Ancak; aboneliğinizi üyelik sonlanma tarihine dek kullanmaya devam edebileceksiniz.");
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
});