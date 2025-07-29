/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var productionCost,qtyDiscount,total_discount='',showSameDay,customSize = 0, width = 0, height = 0,product_id=0,attribute_set,total_price=0,show_final_price=0,final_qty=1;
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui'
], function ($, utils, _, mageTemplate) {
    'use strict';
    var globalOptions = {
        productId: null,
        priceConfig: null,
        prices: {},
        priceTemplate: '<span class="price"><%- data.formatted %></span>',
        optionsSelector: '.product-custom-option'
    };
  
    $.widget('mage.priceBox', {
        options: globalOptions,
        cache: {},

        /**
         * Widget initialisation.
         * Every time when option changed prices also can be changed. So
         * changed options.prices -> changed cached prices -> recalculation -> redraw price box
         */
        _init: function initPriceBox() {
            var box = this.element;            
            box.trigger('updatePrice');
            this.cache.displayPrices = utils.deepClone(this.options.prices);
        },        
        /**
         * Widget creating.
         */
        _create: function createPriceBox() {
            var box = this.element;            
            this._setDefaultsFromPriceConfig();
            this._setDefaultsFromDataSet();              
            box.on('reloadPrice', this.reloadPrice.bind(this));
            box.on('updatePrice', this.onUpdatePrice.bind(this));
        },

        /**
         * Call on event updatePrice. Proxy to updatePrice method.
         * @param {Event} event
         * @param {Object} prices
         */
        onUpdatePrice: function onUpdatePrice(event, prices) {                
            return this.updatePrice(prices);
        },

        /**
         * Updates price via new (or additional values).
         * It expects object like this:
         * -----
         *   "option-hash":
         *      "price-code":
         *         "amount": 999.99999,
         *         ...
         * -----
         * Empty option-hash object or empty price-code object treats as zero amount.
         * @param {Object} newPrices
         */        
        updatePrice: function updatePrice(newPrices) {
          total_discount = '';
            var prices = this.cache.displayPrices,base_price,
                additionalPrice = {},
                pricesCode = [],
                priceValue, origin, finalPrice, optionName,optionTitle;
            
            this.cache.additionalPriceObject = this.cache.additionalPriceObject || {};            
            if (newPrices) {
                $.extend(this.cache.additionalPriceObject, newPrices);
            }

            if (!_.isEmpty(additionalPrice)) {
                pricesCode = _.keys(additionalPrice);
            } else if (!_.isEmpty(prices)) {
                pricesCode = _.keys(prices);
            }
            
            _.each(this.cache.additionalPriceObject, function (additional) {
                  
                if (additional && !_.isEmpty(additional)) {
                    pricesCode = _.keys(additional);
                }

                _.each(pricesCode, function (priceCode) {  
                    if(additional[priceCode]) {
                     optionName = additional[priceCode]['name'] || {};
                     optionTitle = additional[priceCode]['title'] || {};
                     optionName = optionName + '';
                     optionTitle = optionTitle + '';                    
                    } else {
                     optionName = '';
                     optionTitle = '';
                    }
                    base_price = $('#base_price').val();                                        
                    priceValue = additional[priceCode] || {};                    
                    priceValue.amount = +priceValue.amount || 0;
                    
                    priceValue.adjustments = priceValue.adjustments || {};
                    additionalPrice[priceCode] = additionalPrice[priceCode] || {
                            'amount': 0,
                            'adjustments': {}
                        }; 

                    var optionsSelector = $('.size option:selected').first();
                    var option_id = optionsSelector.attr('value');                    
                    var optionSize = optionsSelector.text(); 
                  
                    sqrFt = 0;
                    if(optionSize == 'Custom Size') {
                      $('.custom-option-limit').css("display","block");                        
                       $('.size_field').css("display","block");  
                         if(width!=0 && height!=0){
                           var sqrFt = (width * height) / 144; 
                           //console.log(sqrFt);                          
                          sqrFt = Math.ceil(sqrFt);
                          //console.log(sqrFt);
                        }
                    } else {
                      $('.custom-option-limit').css("display","none");  
                        $('.custom-size-option').val('');
                        $('.size_field').css("display","none");                                
                        var numberPattern = /\d+/g;
                        var sizeArr = optionSize.match( numberPattern );
                        if(priceValue.amount != 0 && optionTitle=='Size')
                          var sqrFt=1;
                          else
                        var sqrFt = (sizeArr[0] * sizeArr[1]) / 144;
                      sqrFt = Math.ceil(sqrFt);
                    }
                     if(priceValue.amount == 0 && optionName != 'None' && optionName != '') { 

                          if(optionTitle == 'Production Time'){    
                            
                            if(productionCost !='+ 0'){  
                            total_discount +=productionCost; 
                            }

                          }
                           else if(optionTitle == 'Quantity'){                            
                              if(qtyDiscount != '+ 0'){
                                total_discount+=qtyDiscount; 
                              }
                            
                              //var optionAmt = 0;
                          }else if(optionTitle == 'Size' && base_price !=0 || optionTitle=='Strip Size') {                                
                                 var optionAmt = sqrFt * base_price; 
                                additionalPrice[priceCode].amount =  0 + (additionalPrice[priceCode].amount || 0) + optionAmt;
                            }
                    } else { 

                        if(optionTitle == 'Grommets' || optionTitle=='Size' || optionTitle=='Strip Size')                  
                          var optionAmt = priceValue.amount;  
                        else{
                          var optionAmt = priceValue.amount * sqrFt;  
                        }  
                                          
                        additionalPrice[priceCode].amount =  0 + (additionalPrice[priceCode].amount || 0) + optionAmt;
                        
                      }
                    _.each(priceValue.adjustments, function (adValue, adCode) {
                        additionalPrice[priceCode].adjustments[adCode] = 0 +
                            (additionalPrice[priceCode].adjustments[adCode] || 0) + adValue;
                    });
                });
            });
            var final_value;
            if (_.isEmpty(additionalPrice)) {
                this.cache.displayPrices = utils.deepClone(this.options.prices);
            } else {
                _.each(additionalPrice, function (option, priceCode) {
                    origin = this.options.prices[priceCode] || {};
                    finalPrice = prices[priceCode] || {};
                    option.amount = option.amount || 0;
                    origin.amount = origin.amount || 0;
                    origin.adjustments = origin.adjustments || {};
                    finalPrice.adjustments = finalPrice.adjustments || {};
                    finalPrice.amount = 0  + option.amount;
                    _.each(option.adjustments, function (pa, paCode) {
                        finalPrice.adjustments[paCode] = 0 + (origin.adjustments[paCode] || 0) + pa;
                    });  
                    final_value = finalPrice.amount;

                }, this);
                  if(final_value){
                   if(total_discount!=''){
                    var discount = eval(total_discount);
                    var  amount = (final_value*discount)/100;
                    final_value =final_value+amount;
                     finalPrice.amount = final_value;                      
                    
                   }

                show_final_price = final_value;
                    show_final_price = final_value*final_qty;
                      if(show_final_price<=25){                  
                        show_final_price = 25;              
                        $('#payment-error').show();
                      }else{
                        $('#payment-error').hide();
                      } 

                show_final_price = show_final_price.toFixed(2);
                $('.show_price').html(show_final_price);  
              }
            }
            
            this.element.trigger('reloadPrice');
        },

        /*eslint-disable no-extra-parens*/
        /**
         * Render price unit block.
         */
        reloadPrice: function reDrawPrices() {
            var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                priceTemplate = mageTemplate(this.options.priceTemplate);

            _.each(this.cache.displayPrices, function (price, priceCode) {
                price.final = _.reduce(price.adjustments, function (memo, amount) {
                    return memo + amount;
                }, price.amount);
                total_price = price.final*final_qty;
                price.formatted = utils.formatPrice(price.final, priceFormat);

                $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({
                    data: price
                }));
            }, this);
        },

        /*eslint-enable no-extra-parens*/
        /**
         * Overwrites initial (default) prices object.
         * @param {Object} prices
         */
        setDefault: function setDefaultPrices(prices) {
            this.cache.displayPrices = utils.deepClone(prices);
            this.options.prices = utils.deepClone(prices);
        },

        /**
         * Custom behavior on getting options:
         * now widget able to deep merge of accepted configuration.
         * @param  {Object} options
         * @return {mage.priceBox}
         */
        _setOptions: function setOptions(options) {
            $.extend(true, this.options, options);

            if ('disabled' in options) {
                this._setOption('disabled', options.disabled);
            }

            return this;
        },

        /**
         * setDefaultsFromDataSet
         */
        _setDefaultsFromDataSet: function _setDefaultsFromDataSet() {
            var box = this.element,
                priceHolders = $('[data-price-type]', box),
                prices = this.options.prices;
            this.options.productId = box.data('productId');
            if (_.isEmpty(prices)) {
                priceHolders.each(function (index, element) {
                    var type = $(element).data('priceType'),
                        amount = parseFloat($(element).data('priceAmount'));

                    if (type && !_.isNaN(amount)) {
                        prices[type] = {
                            amount: amount
                        };
                    }
                });
            }
        },

        /**
         * setDefaultsFromPriceConfig
         */
        _setDefaultsFromPriceConfig: function _setDefaultsFromPriceConfig() {
            var config = this.options.priceConfig;

            if (config && config.prices) {
                this.options.prices = config.prices;
            }
        } 
    });
$('.price').text(show_final_price);
      product_id = $('#product_id').val();      
      attribute_set = $('#product-attribute-set').val();
    //$(".custom-option-material").children('option:eq(0)').show();    
    $('.custom-option-material').on('change', function() {  
        var material = $(".custom-option-material option:selected").text();
        var product_name = $('.base').text();

        if(material=='Mesh Vinyl Banner' || material=='3M Controtac'){           
            deleteProductionDay('Same Day');
            deleteProductionDay('Next Business Day');            
            $('.production-time').trigger('change');

        } else if(material == '3.5 Mil Gloss Vinyl' || material == 'Satin/Matte Vinyl' || material == '13oz White Scrim Banner'){
           addProductionDay('Same day','before',0);            
           addProductionDay('Next Business Day','after',0);    
           $('.production-time option').each(function(i, obj) {  
              if($(this).text() == 'Next Business Day'){               
                $(this).attr('selected','selected');
              }
            });  
           $('.production-time').trigger('change');
        }
      });
    $('.production-time').on('change', function() {        
        var productionTime = $('.production-time option:selected');
        var baseUrl = $('.base_url').text();
        var optionName = productionTime.text();
        var product_name = $('.base').text();
        var request = $.ajax({
          async: false,
          url: baseUrl+"productiontime/",
          type: "POST",
          data: {title : optionName,product_id:product_id},
          dataType: "json",
          showLoader: true
        });
        request.done(function(data) {
          // return value assign to global variables 
          var remove = productionDayData(product_name);          
          var material = $(".custom-option-material option:selected").text();
          productionCost = data['slot'];
          showSameDay = data['show_same_day'];  
          if(showSameDay == 'yes'){ 
            if(material=='Mesh Vinyl Banner' || material=='3M Controtac'){
              deleteProductionDay(remove);           
            } 
            else{
                var title = $(".production-time").children('option:eq(0)').text();     
                addProductionDay(title,'before',0); 
            }
          }else{

           deleteProductionDay(remove);           
          }  
          $('.size').trigger('change');                  
        });

        request.fail(function(jqXHR, textStatus) {
          alert( "Request failed: " + textStatus );
          //return productionCost;
        });
        return productionCost;
    });

    var width_max=0;
    var height_max=0;
    var width_min=1;
    var height_min=1;
    var selected_width=1; 
    var selected_height=1; 
    var product_name = $('.base').text();
    if(product_name=='Poster Printing'){
          width_min=12;
          width_max=59;
          height_min=18;
          height_max=120;   
          selected_width=24;
          selected_height = 36;                 
       }else if(product_name == 'conVerd Board Posters'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;   
       }else if(product_name == 'Corrugated Plastic Boards'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=22;
          selected_height = 28;  
       }else if(product_name == 'Fabric Banners'){
         width_min=24;
          width_max=240;
          height_min=24;
          height_max=68;   
          selected_width=24;
          selected_height = 60;  
       }else if(product_name == 'Sintra PVC Boards'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }else if(product_name == 'Vinyl Banners'){
          width_min=24;
          width_max=240;
          height_min=24;
          height_max=59;   
          selected_width=24;
          selected_height = 60; 
       }else if(product_name == 'Mesh Banners'){
          width_min=24;
          width_max=240;
          height_min=24;
          height_max=120;   
          selected_width=24;
          selected_height = 60; 
       }else if(product_name == 'Wallpaper'){
          width_min=24;
          width_max=48;
          height_min=24;
          height_max=120;  
          selected_width=24;
          selected_height = 96;  
       }else if(product_name == 'Falconboard Posters'){
          width_min=12;
          width_max=59;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36; 
       }else if(product_name == 'Canvas Prints'){
          width_min=12;
          width_max=54;
          height_min=12;
          height_max=72;  
          selected_width=16;
          selected_height = 20;   
       }else if(product_name == 'Foam Core Posters'){
          width_min=12;
          width_max=59;
          height_min=18;
          height_max=120;  
          selected_width=24;
          selected_height = 36;   
       }else if(product_name == 'Adhesive Vinyl'){
          width_min=12;
          width_max=53;
          height_min=18;
          height_max=120;   
          selected_width=20;
          selected_height = 30;   
       }else if(product_name == 'Gator Board Posters'){
         width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }
       var widthSelect='width';
       var heightSelect='height';
          
        $('.size').on('change', function() { 
          var size = $('.size option:selected').text();  
          if(size == 'Custom Size' && !$('.custom-size-option').val()){            
              buildSelect(width_min,width_max,widthSelect,selected_width,product_name);
              buildSelect(height_min,height_max,heightSelect,selected_height,product_name);  
              width = $('#width option:selected').text();    
              height = $('#height option:selected').text(); 
          }
        });
        function buildSelect(min_value,max_value,select,selected,product_name) {
          $('#'+select).html('');          
          if(product_name == 'Canvas Prints'){
            for(var i=min_value;i<=max_value;i=i+2){
              if(selected==i)
              $('<option >').val(i).attr('selected','selected').text(i).appendTo('#'+select);  
              else
                $('<option >').val(i).text(i).appendTo('#'+select);  
            }
          }else if(product_name == 'Wallpaper'){
            for(var i=min_value;i<=max_value;i=i+12){
              if(i == 84 || i== 108)
                continue;
              if(selected==i)
              $('<option >').val(i).attr('selected','selected').text(i).appendTo('#'+select);  
              else
                $('<option >').val(i).text(i).appendTo('#'+select);  
            }
          }else{
            for(var i=min_value;i<=max_value;i=i+0.5){
              if(selected==i)
              $('<option >').val(i).attr('selected','selected').text(i).appendTo('#'+select);  
              else
                $('<option >').val(i).text(i).appendTo('#'+select);  
            }
          }
          
        }     
         $('#width').on('change',function(){          
          width  = $('#width option:selected').text();          
          height = $('#height option:selected').text();                  
          $('.custom-size-option').val(width+'"'+'x'+height+'"');
          $('.quantity').trigger('change');          
      });
        $('#height').on('change',function(){          
           width  = $('#width option:selected').text();          
           height = $('#height option:selected').text();                  
          $('.custom-size-option').val(width+'"'+'x'+height+'"');
          $('.quantity').trigger('change');                    
      });    
        if($('.custom-size-option').val()){
          var customSize = $('.custom-size-option').val().replace('",*','');
          if(customSize!=''){
            var data =customSize.split('x');
            var width  = parseFloat(data[0]);
            var height = parseFloat(data[1]);    
            buildSelect(width_max,widthSelect,width);
            buildSelect(height_max,heightSelect,height);      
            $('.quantity').trigger('change'); 
          } 
       }
    $('.quantity').on('change', function() {    
       var qtySelect = $('.quantity option:selected');
       var baseUrl = $('.base_url').text();
       var qtyPrice = '';
        var qty = qtySelect.text(); 
        
        if(attribute_set == 'canvas_print_set'){          
           if(qty<=5){            
           //$(".production-time").children('option:eq(0)').show();
            addProductionDay('Next Business Day','before',0);      
            $('.production-time').trigger('change');      
          }else{
            deleteProductionDay('Next Business Day');                       
          }
          if(qty >=15){
            addProductionDay('4-5 Business Days','after',1);
          }else{
            //$(".production-time").children('option:eq(3)').remove();
            deleteProductionDay('4-5 Business Days');           
          }          
          if(qty >=30){
            addProductionDay('6-7 Business Days','after',2);
            //$(".production-time").children('option:eq(4)').show();
          }else{            
            //$(".production-time").children('option:eq(4)').remove();
            deleteProductionDay('6-7 Business Days','after');           
          }
          if(qty >=40){
            addProductionDay('8-10 Business days','after',3);            
          }else{
            deleteProductionDay('8-10 Business days');           
            //$(".production-time").children('option:eq(5)').remove();
          }
        }else if(attribute_set == 'poster_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            //$(".production-time").children('option:eq(3)').remove();
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'adhesive_vinyl_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',0);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'vinyl_banner_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'fabric_banner_set'){
          if(qty <20){            
            $(".production-time").children('option:eq(1)').remove();
          }else{            
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'corrugated_plastic_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'sintra_pvc_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }
        else if(attribute_set == 'falcon_board_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }
        else if(attribute_set == 'converd_board_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }else if(attribute_set == 'gator_board_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }
        else if(attribute_set == 'foam_core_set'){
          if(qty >=20){
              addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }  
          
        $('#qty').val(qty);
        final_qty = qty;
         $('.price').html(show_final_price);
        var request = $.ajax({
          async: false,
          url: baseUrl+"productiontime/discount/discountqty/",
          type: "POST",    
          data: {qty : qty,product_id:product_id},    
          dataType: "html",
          showLoader: true
        });        
        request.done(function(data) {
          qtyDiscount = '+ 0';
          // return value assign to global variable 
            qtyDiscount = data;              
        });
        request.fail(function(jqXHR, textStatus) {
          alert( "Request failed: " + textStatus );
          //return productionCost;
        });
        return qtyDiscount;
    });

     function addProductionDay(production_value,operation,pos) {
       var obj = JSON.parse(window.production_time_array); 
       var production_id;               
       $.each(obj, function( index, value ){
          if(value == production_value){
            production_id = index;
          }
        });
       var isExists =0;
         $('.production-time option').each(function(i, obj) {  
          if($(this).val() == production_id){               
            isExists = 1;
          }
      });
       if(isExists==0){
         if(operation == 'after'){
          $(".production-time").children('option:eq('+pos+')').after($("<option price='0' not_selected='0' ></option>").val(production_id).text(production_value));     
         
        }
        else{
          $(".production-time").children('option:eq('+pos+')').before($("<option price='0' not_selected='0'></option>").val(production_id).text(production_value));     
        } 
      }
    }
    function deleteProductionDay(production_label){
      if(production_label!=null){
          $('.production-time option').each(function(i, obj) {  
            if($(this).text() == production_label){               
              $(this).remove();
            }
          });  
      }
    }
    function productionDayData(product_name){
      var return_value = null;
        var data = {
          'Poster Printing'           : 'Same day',
          'Foam Core Posters'         : 'Same day',
          'Gator Board Posters'       : 'Same day',          
          'Adhesive Vinyl'            : 'Same day',
          'Canvas Prints'             : 'Next Business Day',
          'conVerd Board Posters'     : 'Same day',
          'Corrugated Plastic Boards' : 'Same day',
          'Falconboard Posters'       : 'Same day',
          'PVC Board Posters'         : 'Same day',
          'Vinyl Banners'             : 'Same day',
          //'Mesh Banners'              : 'Same day',
          'Wallpaper'                 : 'Same day'
        };
        $.each(data, function(key, value) {          
            if(key == product_name){
              return_value = value;              
            }
        });
       return return_value; 
    }
     
    var edit = $('#edit_product').val(); 
    if(edit=='no'){
      $('.production-time').each(function(i, obj) {  
        var product_name = $('.base').text();
        if(product_name!='Canvas Prints'){
          $(obj).find('option:eq(1)').prop('selected', true);
        }else{
          $(".production-time").children('option:eq(2)').attr("selected", "selected");          
          $(".production-time").trigger('change');
       } 
      });
      var optionid = $('.optionId').attr('options_id');
      $('#options_'+optionid+'_2').prop('checked',true);
      if ($('#options_'+optionid+'_3').is(":checked")){
          $('.file-uploaded').hide();
          $('.delete-file').trigger('click');
          $('.cropit-image-input').val('');
          $('.upload-image').show();
          $('.not-image').hide();
      }
    
    }
    $('.radio-button').on("change", function() {
      var optionid = $('.optionId').attr('options_id');
      var checked;
      $('.delete-file').trigger('click');
      if ($('#options_'+optionid+'_3').is(":checked")){
        checked = false;
          $('#file-name').html('');
          $('.file-uploaded').hide();
          $('#image_upload_preview1').hide();
          $('.image-uploader').hide();
          $('#delete').hide();                 
          $('.file-upload-error').hide();
          $('.cropit-image-input').val('');
          $('.upload-image').show();
        $('.not-image').hide();
      } else{
          $('.file-uploaded').show();
          checked = true;
      }
    });
     $('.proofs').on('change', function() {    
        $('.proofs-info').hide();
        var proofSelect = $('.proofs option:selected');
        var proofSelected = proofSelect.text().toString();  
        if(proofSelected ==='PDF Proof' || proofSelected ==='PDF proof'){
          $('.proofs-info').show();
        }
    });

    $(document).ready(function(){
      setTimeout(function () {
        $('.production-time').trigger('change');
      }, 600);
    });

    return $.mage.priceBox;
});