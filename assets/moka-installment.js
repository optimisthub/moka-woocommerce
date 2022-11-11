jQuery('.min--installment--price').click(function (e) {
    jQuery('a[href="#tab-installment_tab"]').trigger('click');
    	
	var position = jQuery('#tab-installment_tab').offset().top;

	jQuery("body, html").animate({
		scrollTop: position
	}  );
});