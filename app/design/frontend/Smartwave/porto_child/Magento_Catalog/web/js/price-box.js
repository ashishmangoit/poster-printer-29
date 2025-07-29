/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var numberOfPages,productionCost,qtyDiscount,total_discount='',showSameDay,show_business_day_3,show_business_day_2,customSize = 0, width = 0, height = 0,product_id=0,attribute_set,total_price=0,show_final_price=0,final_qty=1;
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
                    var product_id_cal = $('#product_id').val();
                  
                    sqrFt = 0;
                    if(optionSize == 'Custom Size') {
                      $('.custom-option-limit').css("display","block");                        
                       $('.size_field').css("display","block"); 

                        if(width!=0 && height!=0){
                            if(product_id_cal == 30){
                                var wallsurface = $('.custom-option-wallsurface option:selected').text();
                                var customMaterial = $('.custom-option-material option:selected').text();

                                var sqrFt = ((width * height) / 144);
                                sqrFt = Math.ceil(sqrFt);

                                if(wallsurface == 'Smooth' && customMaterial == '3M Laminated Satin Vinyl'){
                                    sqrFt = sqrFt * 8.50;
                                }else if(wallsurface == 'Smooth' && customMaterial == 'HP PVC Free Wallpaper'){
                                    sqrFt = sqrFt * 4.50;
                                }else if(wallsurface == 'Textured' && customMaterial == '3M Laminated Satin Vinyl'){
                                    sqrFt = sqrFt * 15.00;
                                }

                                $('.custom-size-option').val(width+'"'+'x'+height+'"');
                            }else{
                                var sqrFt = (width * height) / 144;
                            }
                        }else{
                            if(product_id_cal == 30){
                                var show_price = 0;
                                $('.show_price').html(show_price.toFixed(2));
                                var show_installation_price = 85;
                                $('.show_installation_price').html(show_installation_price.toFixed(2));
                                if($('.custom-option-need-installation option:selected').text() == 'Yes'){
                                    var show_total_price = parseFloat(show_price)+parseFloat(show_installation_price);
                                }else{
                                    var show_total_price = 0;
                                }
                                
                                $('.show_total_price').html(show_total_price.toFixed(2));
                            }
                        }                      
                        sqrFt = Math.ceil(sqrFt);
                    } else if(product_id_cal == 36){
                      $('.custom-option-limit').css("display","none");  
                        $('.custom-size-option').val('');
                        $('.size_field').css("display","none"); 
                        var color = $(".color option:selected").text();                              
                        var numberPattern = /\d+/g;
                        var sizeArr = optionSize.match( numberPattern );
                        /*if(priceValue.amount != 0 && optionTitle=='Size')
                        {
                          var sqrFt=1;
                        }
                        else{*/
                          if(!numberOfPages)
                          {
                            numberOfPages = 1;
                          }
                          if(color == 'Black & White')
                          {
                            var sqrFt = (sizeArr[0] == 18 && sizeArr[1] == 24) ? (numberOfPages * 1.5) : (sizeArr[0] == 24 && sizeArr[1] == 36) ? (numberOfPages * 3) : (sizeArr[0] == 30 && sizeArr[1] == 42) ? (numberOfPages * 4.5) : (sizeArr[0] == 36 && sizeArr[1] == 48) ? (numberOfPages * 6) : '';
                          }
                          else if(color == 'Color')
                          {
                            var sqrFt = (sizeArr[0] == 18 && sizeArr[1] == 24) ? (numberOfPages * 2.25) : (sizeArr[0] == 24 && sizeArr[1] == 36) ? (numberOfPages * 4.5) : (sizeArr[0] == 30 && sizeArr[1] == 42) ? (numberOfPages * 6.75) : (sizeArr[0] == 36 && sizeArr[1] == 48) ? (numberOfPages * 9) : '';
                          }
                        //}
                        //var sqrFt = (sizeArr[0] * sizeArr[1]) / 144;
                      sqrFt = Math.ceil(sqrFt);
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

                $('.show_price').html(show_final_price.toFixed(2));
                var installation_price = (Math.ceil((width*height)/144))*5+85;
                $('.show_installation_price').html(installation_price.toFixed(2));

                if($('.custom-option-need-installation option:selected').text() == 'Yes'){
                    var total_price = parseFloat(show_final_price)+parseFloat(installation_price);
                }else{
                    var total_price = show_final_price;
                }
                $('.show_total_price').html(total_price.toFixed(2));
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

$( ".number-of-pages" ).keyup(function() {
  $('.number-of-pages').trigger('change');
});
$('.number-of-pages').on('change', function() {
   numberOfPages = $(this).val();
});

$("#width-ft" ).keyup(function() {
  $('.size').trigger('change');
});
$("#width-in" ).keyup(function() {
  $('.size').trigger('change');
});
$("#height-ft" ).keyup(function() {
  $('.size').trigger('change');
});
$("#height-in" ).keyup(function() {
   $('.size').trigger('change');
});

$('.custom-option-need-installation').on('change', function() {
    if($('.custom-option-need-installation option:selected').text() == 'Yes'){
        $('.preferred-date').css("display","block");
        $('.preferred-date-text').css("display","block");
        $('.installation-price').css("display","block");
    }else{
        $('.preferred-date').css("display","none");
        $('.preferred-date-text').css("display","none");
        $('.installation-price').css("display","none");
    }   
});

product_id = $('#product_id').val();
if(product_id == 36 || product_id == 37 || product_id == 38)
{
  $('.size option').each(function(i, obj) {  
    if($(this).text() == "24'' x 36''"){               
      $(this).attr('selected','selected');
    }
  });
}
else if(product_id == 22 ){
  $('.size option').each(function(i, obj) {  
    if($(this).text() == "24'' x 36''"){               
      $(this).attr('selected','selected');
    }
  });
}
else if(product_id == 39 || product_id == 40){
  $('.size option').each(function(i, obj) {  
    if($(this).text() == "24'' x 24''"){               
      $(this).attr('selected','selected');
    }
  });
}
$('#height').on('change', function() {
    product_id = $('#product_id').val();
    if(product_id == 29){
      setTimeout(function() {
        $('.production-time').trigger('change'); }, 1000);
   }
});
$('.price').text(show_final_price);
product_id = $('#product_id').val();  

$('.custom-option-wallsurface').on('change', function() {
    var wallsurfaceoption = $('.custom-option-wallsurface option:selected').text();

    if(wallsurfaceoption == 'Textured'){
        deleteMaterial('HP PVC Free Wallpaper');
    }else{
        addMaterial('HP PVC Free Wallpaper','after',0);
    }
});

      attribute_set = $('#product-attribute-set').val();
    //$(".custom-option-material").children('option:eq(0)').show();    
    $('.custom-option-material').on('change', function() {  
        var material = $(".custom-option-material option:selected").text();
        var product_name = $('.base').text();

        if(material=='3M Controtac'){
            deleteProductionDay('Same Day');
            deleteProductionDay('Next Business Day');            
            $('.production-time').trigger('change');

        } else if(material=='Mesh Vinyl Banner' || material == '3.5 Mil Gloss Vinyl' || material == 'Satin/Matte Vinyl' || material == '13oz White Scrim Banner'){
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
        var product_id = $('#product_id').val();
        if(product_id == 29) {
          var customHeight = $('#height').val();
          if(customHeight>59) {
            addProductionDay('2 Business Days','after',1);
            addProductionDay('3 Business Days','after',0);
            deleteProductionDay('Same day');
            deleteProductionDay('Next Business Day');
          }
          else {
            addProductionDay('Same day','before',0);
            addProductionDay('Next Business Day','before',1);
          }
        }       
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
          
          show_business_day_3 = data['show_business_day_3'];
          show_business_day_2 = data['show_business_day_2'];
          if(product_id == 36 || product_id == 37 || product_id == 39 || product_id == 19 || product_id == 22 || product_id == 31 || product_id == 35){
            
            if(product_id == 36) {   
                if(show_business_day_3 == 'yes' && product_id == 36){
                    addProductionDay('3 Business Days','after',2);
                }else{
                    deleteProductionDay('3 Business Days');
                }
            }

            if(showSameDay == 'yes'){
                addProductionDay('Same day','before',0);
            }else{
                deleteProductionDay('Same day');
            }
          }
          if(product_id == 29)
          {
            var customHeight = $('#height').val();

            if(customHeight>59)
            {
              addProductionDay('2 Business Days','after',1);
              addProductionDay('3 Business Days','after',0);
              deleteProductionDay('Same day');
              deleteProductionDay('Next Business Day');
            }
            else
            {
              addProductionDay('Same day','before',0);
              addProductionDay('Next Business Day','before',1);
            }
          }
          /*if(product_id == 29){

            var customHeight = $('#height').val();

            if(customHeight > 59)
            {
              console.log("custom height");
              console.log(customHeight);
              if(show_business_day_3 == 'yes'){
                  addProductionDay('3 Business Days','after',2);
              }else{
                deleteProductionDay('3 Business Days');   
              }
              if(show_business_day_2 == 'yes'){
                  addProductionDay('2 Business Days','after',1);
              }else{
                deleteProductionDay('2 Business Days');   
              }
            }
          }*/
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
       }else if(product_name == 'Styrene Posters Printing'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;   
       }else if(product_name == 'Coroplast Corrugated Plastic Printing'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=22;
          selected_height = 28;  
       }else if(product_name == 'Fabric Banner Printing'){
         width_min=24;
          width_max=240;
          height_min=24;
          height_max=68;   
          selected_width=24;
          selected_height = 60;  
       }else if(product_name == 'Sintra PVC Board Printing'){
          width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }else if(product_name == 'Vinyl Banner Printing'){
          width_min=24;
          width_max=240;
          height_min=24;
          height_max=120;   
          selected_width=24;
          selected_height = 59; 
       }else if(product_name == 'Mesh Banner Printing'){
          width_min=24;
          width_max=240;
          height_min=24;
          height_max=120;   
          selected_width=24;
          selected_height = 60; 
       }else if(product_name == 'Wallpaper Printing'){
          width_min=24;
          width_max=48;
          height_min=24;
          height_max=120;  
          selected_width=24;
          selected_height = 96;  
       }else if(product_name == 'Falconboard Poster Printing'){
          width_min=12;
          width_max=59;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36; 
       }else if(product_name == 'Canvas Printing'){
          width_min=12;
          width_max=54;
          height_min=12;
          height_max=72;  
          selected_width=16;
          selected_height = 20;   
       }else if(product_name == 'Foam Core Poster Printing'){
          width_min=12;
          width_max=59;
          height_min=18;
          height_max=120;  
          selected_width=24;
          selected_height = 36;   
       }else if(product_name == 'Adhesive Vinyl Printing'){
          width_min=12;
          width_max=53;
          height_min=18;
          height_max=120;   
          selected_width=20;
          selected_height = 30;   
       }else if(product_name == 'Gator Board Poster Printing'){
         width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }
       else if(product_name == 'Backlit Film Printing'){
         width_min=24;
          width_max=48;
          height_min=24;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }
       else if(product_name == 'Aluminum Sign Printing'){
         width_min=12;
          width_max=48;
          height_min=18;
          height_max=96;   
          selected_width=24;
          selected_height = 36;  
       }
       else if(product_name == 'Floor Graphic Printing'){
         width_min=12;
          width_max=48;
          height_min=18;
          height_max=120;   
          selected_width=24;
          selected_height = 24;  
       }
       else if(product_name == 'Perforated Vinyl Printing'){
         width_min=24;
          width_max=48;
          height_min=24;
          height_max=120;   
          selected_width=24;
          selected_height = 24;  
       }
       var widthSelect='width';
       var heightSelect='height';
          
        $('.size').on('change', function() { 
          var size = $('.size option:selected').text();
          if(size == 'Custom Size'){
              if($('#product_id').val() == 30){
                var widthFt = parseFloat($('#width-ft').val());
                var widthIn = parseFloat($('#width-in').val());
                var heightFt = parseFloat($('#height-ft').val());
                var heightIn = parseFloat($('#height-in').val());

                width = (widthFt * 12) + widthIn;
                height = (heightFt * 12) + heightIn;

              }else if(!$('.custom-size-option').val()){
                  buildSelect(width_min,width_max,widthSelect,selected_width,product_name);
                  buildSelect(height_min,height_max,heightSelect,selected_height,product_name);  
                  width = $('#width option:selected').text();    
                  height = $('#height option:selected').text();
              }         
          }
        });
        function buildSelect(min_value,max_value,select,selected,product_name) {
          $('#'+select).html('');          
          if(product_name == 'Canvas Printing'){
            for(var i=min_value;i<=max_value;i=i+2){
              if(selected==i)
              $('<option >').val(i).attr('selected','selected').text(i).appendTo('#'+select);  
              else
                $('<option >').val(i).text(i).appendTo('#'+select);  
            }
          }else if(product_name == 'Wallpaper Printing'){
            for(var i=min_value;i<=max_value;i=i+12){
              if(i == 84 || i== 108)
                continue;
              if(selected==i)
              $('<option >').val(i).attr('selected','selected').text(i).appendTo('#'+select);  
              else
                $('<option >').val(i).text(i).appendTo('#'+select);  
            }
          }else{
            for(var i=min_value;i<=max_value;i=i+1){
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
        }/*else if(attribute_set == 'vinyl_banner_set'){
          if(qty >=20){
            addProductionDay('3 Business Days','after',1);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }*/else if(attribute_set == 'fabric_banner_set'){
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
        else if(attribute_set == 'backlit_film_set'){
          if(qty >=20){
              addProductionDay('3 Business Days','after',2);
          }else{
            deleteProductionDay('3 Business Days');   
          }
        }
        else if(attribute_set == 'floor_graphics_set'){
          if(qty >=20){
              addProductionDay('3 Business Days','after',2);
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
    function addMaterial(material_value,operation,pos) {
       var obj = JSON.parse(window.material_array); 
       var material_id;               
       $.each(obj, function( index, value ){
          if(value == material_value){
            material_id = index;
          }
        });
       var isExists =0;
         $('.custom-option-material option').each(function(i, obj) {  
          if($(this).val() == material_id){               
            isExists = 1;
          }
      });
       if(isExists==0){
         if(operation == 'after'){
          $(".custom-option-material").children('option:eq('+pos+')').after($("<option price='0' not_selected='0' ></option>").val(material_id).text(material_value));     
         
        }
        else{
          $(".custom-option-material").children('option:eq('+pos+')').before($("<option price='0' not_selected='0'></option>").val(material_id).text(material_value));     
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
    function deleteMaterial(material){
      if(material!=null){
          $('.custom-option-material option').each(function(i, obj) {  
            if($(this).text() == material){               
              $(this).remove();
            }
          });  
      }
    }
    function productionDayData(product_name){
      var return_value = null;
        var data = {
          'Poster Printing'           : 'Same day',
          'Foam Core Poster Printing'         : 'Same day',
          'Gator Board Poster Printing'       : 'Same day',          
          'Adhesive Vinyl Printing'            : 'Same day',
          'Canvas Printing'             : 'Next Business Day',
          'Converd Board Poster Printing'     : 'Same day',
          'Coroplast Corrugated Plastic  Printing' : 'Same day',
          'Falconboad Poster Printing'       : 'Same day',
          'Sintra PVC Board Printing'         : 'Same day',
          'Vinyl Banner Printing'             : 'Same day',
          //'Mesh Banners'              : 'Same day',
          'Wallpaper Printing'                 : 'Same day'
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
        if(product_name!='Canvas Printing'){
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