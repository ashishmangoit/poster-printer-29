require([
        "jquery"
    ], function ($) {
    	$( document ).ready(function() {    		
    		//$('input[type="radio"]').trigger("change");
    		//$('input[name=postcode]').val("");
    		$('#qbmspayments').prop("checked", true);
    		//onStoreLocation('s_method_freeshipping');
    		
		    $(document).on('click', 'input:radio', function() { 
	      		var selected_radio = this.id;
	      		onStoreLocation(selected_radio);	      		
			});
			
			function onStoreLocation(selected_radio){

	     	if(selected_radio == 's_method_freeshipping_freeshipping' || selected_radio == 's_method_freeshipping'){
	 			$('input[name="postcode"]').val(window.zipCodeId);
	 			$('input[name="postcode"]').attr('disabled','true');
	 			$('input[name="street[0]"]').val(window.streetAddress2);
	 			$('input[name="street[0]"]').attr('disabled','true');
	 			$('input[name="street[1]"]').attr('disabled','true');
	 			$('input[name="city"]').val(window.cityName);
	 			$('input[name="city"]').attr('disabled','true');
	 			$('select[name="country_id"] option[value="'+window.countryId+'"]').attr("selected","true");
	 			$('select[name="country_id"]').attr('disabled','true');
	 			$('select[name="region_id"] option[value="'+window.regionId+'"]').attr("selected","true");
	 			$('select[name="region_id"]').attr('disabled','true');
	 			$('input[name="postcode"]').trigger("change");
	 			$('select[name="region_id"]').trigger("change");
	 			$('select[name="country_id"]').trigger("change");
	 			$('input[name="city"]').trigger("change");
	 			$('.action-show-popup').trigger("click");
	 			$('input[name="street[0]"]').trigger("change");
	     	}else{
	     		$('input[name="postcode"]').removeAttr('disabled');
	 			$('input[name="street[0]"]').removeAttr('disabled');
	 			$('input[name="street[1]"]').removeAttr('disabled');
	 			$('input[name="city"]').removeAttr('disabled');
	 			$('select[name="country_id"]').removeAttr('disabled');
	 			$('select[name="region_id"]').removeAttr('disabled');
	 			/*$('input[name="postcode"]').val('');
	 			$('input[name="street[0]"]').val('');
	 			$('input[name="city"]').val('');	*/ 			
	 			$('select[name="region_id"] option[value=""]').attr("selected","true");
	     	}
	    }
	});
      
});  
