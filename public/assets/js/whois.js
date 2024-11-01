function WhoisCheck(){
	if ( jQuery("#domain").val().length < 2 ) {
		alert(WhoisAjax.enter_domain);
		return false;
	}
	return true;
}

jQuery(document).ready(function(){
    jQuery("#whoissubmit").click(function(e){
        e.preventDefault();
        if ( WhoisCheck() ) {
        	whoispost();
        }
    });
});

function whoispost() {
	jQuery('#whoissubmit').attr("disabled", true);
	jQuery("#whois_result").html('');
	jQuery("#whois_work").slideToggle(500);
			console.log('success');
	jQuery.post(
		WhoisAjax.ajaxurl, 
		jQuery("#whois").serialize(), 
			function(data) {
					console.log(data); // why is this 0
				if (data.success) {
					console.log('success!');
					jQuery("#whois_result").html(data.msg);
					jQuery("#whois_work").slideToggle(500);
					jQuery('#whoissubmit').attr("disabled", false);
				}
	});
}
