<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mangoit\MultiplyOptions\Model\Product\Type;

/**
 * Product type price model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price extends \Magento\Catalog\Model\Product\Type\Price
{

     /**
     * Apply options price
     *
     * @param Product $product
     * @param int $qty
     * @param float $finalPrice
     * @return float
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _applyOptionsPrice($product, $qty, $finalPrice)
    {   $id = $product->getId();
        $chkFinalPrice=1;
          $optionIds = $product->getCustomOption('option_ids');
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
          $collection = $objectManager->create('\Mangoit\MultiplyOptions\Helper\ServerTime');

           //print_r($qtyDiscount); die;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/posterprinterPricelogs.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('--Initial finalPrice: -- '.$finalPrice);             
          //echo $finalPrice;
          //die("price 1224");
          
        //$collection->compareToDatabase(strtolower('Same Day'));

        if ($optionIds) {
            $basePrice = $finalPrice;
            $sqrFt = 0;
            $totalPercentage = '';
            $sizeColor1 = 0;
            $sizeColor2 = 0;
            $numberOfPages = 0;
            $wallSurface = '';
            $material = '';
            $needInstallation = '';

            foreach (explode(',', $optionIds->getValue()) as $optionId) {

                if ($option = $product->getOptionById($optionId)) {
                   $optionType = $option->getType();

                    if($optionType != 'file') {
                     $optionName = $option->getTitle();
                    }
                    
                    $confItemOption = $product->getCustomOption('option_' . $option->getId());
                    
                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setConfigurationItemOption($confItemOption);
                    
                    /**
                    * Custom code for calculate options value
                    * according to selected size
                    */
                    $_result = $option->getValueById($confItemOption->getValue());

                    if($optionType != 'file' && $optionType != 'field') { 
                     $optionLabel = $_result->getTitle();
                    }
                    
                    $optionPrice = 0;
                    //$product_name = 'Poster Printing';
                    if(strtolower($optionName) == 'size' || strtolower($optionName) == 'strip size') {
                      $optionPrice = $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                      
                      if($optionLabel != "Custom Size") {
                        if((int)$optionPrice) {
                          
                          if($id == 26 || $id == 24 || $id == 25 || $id == 28){
                            $finalPrice = $optionPrice;
                            $logger->info('-- Conditional optionPrice: -- '.$optionPrice);
                          }

                        }
                        else {
                         preg_match_all('!\d+!', $optionLabel, $matches);
                         if(sizeof($matches)) {
                            
                                  $sizeArr = $matches[0];
                                  if($id == 36)
                                    {
                                        $sizeColor1 = $sizeArr[0];
                                        $sizeColor2 = $sizeArr[1];
                                    }
                                    else{
                                        $sqrFt = ($sizeArr[0] * $sizeArr[1]) / 144;
                                        $sqrFt = ceil($sqrFt);
                                        $finalPrice = $sqrFt * $basePrice;

                                        //$logger->info('--After sqrFt: -- '.$sqrFt);
                                        //$logger->info('--After basePrice: -- '.$basePrice);
                                        //$logger->info('--After finalPrice: -- '.$finalPrice);
                                    }        
                             
                          
                          //$finalPrice = $optionPrice;
                         }
                        }
                        
                      }
                    }
                    else if(strtolower($optionName) ==  'custom size') {
                     $customValue = $confItemOption->getValue();
                     if(!empty($customValue)) {                      
                        if(preg_match_all('/[0-9.]+/', $customValue, $matches)) {
                            $sizeArr = $matches[0];
                            $sqrFt = ($sizeArr[0] * $sizeArr[1]) / 144;
                            $sqrFt = ceil($sqrFt);
                            $finalPrice = $sqrFt * $basePrice;
                         //$finalPrice = $optionPrice;
                        }
                     }
                    }else if(strtolower($optionName) ==  'wall surface'){
                        $customValue = $confItemOption->getValue();
                        $wallSurfaceOption = $option->getValueById($customValue);
                        $wallSurface = $wallSurfaceOption->getTitle();

                    }else if($id == 30 && strtolower($optionName) ==  'material'){
                        $customValue = $confItemOption->getValue();
                        $materialOption = $option->getValueById($customValue);
                        $material = $materialOption->getTitle();

                        if($wallSurface == 'Smooth' && $material == '3M Laminated Satin Vinyl'){
                            $finalPrice = $finalPrice * 8.50;
                        }else if($wallSurface == 'Smooth' && $material == 'HP PVC Free Wallpaper'){
                            $finalPrice = $finalPrice * 4.50;
                        }else if($wallSurface == 'Textured' && $material == '3M Laminated Satin Vinyl'){
                            $finalPrice = $finalPrice * 15.00;
                        }
                    }else if($id == 30 && strtolower($optionName) ==  'need installation'){
                        $customValue = $confItemOption->getValue();
                        $needInstallationOption = $option->getValueById($customValue);
                        $needInstallation = $needInstallationOption->getTitle();

                        if($needInstallation == 'Yes'){
                            $finalPrice = $finalPrice + ($sqrFt*5+85);                           
                        }

                        $chkFinalPrice = ceil($finalPrice);
                    }
                    else if($id == 36 && strtolower($optionName) == 'number of pages') {
                        $customValue = $confItemOption->getValue();
                        $numberOfPages = $customValue;
                        //$finalPrice = $optionPrice;
                    }
                    else if($id == 36 && strtolower($optionName) == 'color') {
                        $customValue = $confItemOption->getValue();
                        $colorTitle = $option->getValueById($customValue);
                        $colorName = $colorTitle->getTitle();

                        if($colorName == 'Black & White')
                        {
                            $sqrFt = ($sizeColor1 == 18 && $sizeColor2 == 24)
                                  ? $numberOfPages*1.5
                                  : (
                                       ($sizeColor1 == 24 && $sizeColor2 == 36)
                                       ? $numberOfPages*3
                                       : (
                                           ($sizeColor1 == 30 && $sizeColor2 == 42)
                                           ? $numberOfPages*4.5
                                           : (
                                               ($sizeColor1 == 36 && $sizeColor2 == 48)
                                               ? $numberOfPages*6
                                               : 1
                                            )
                                        )
                                    );

                        }
                        else if($colorName == 'Color')
                        {
                            $sqrFt = ($sizeColor1 == 18 && $sizeColor2 == 24)
                                  ? $numberOfPages*2.25
                                  : (
                                       ($sizeColor1 == 24 && $sizeColor2 == 36)
                                       ? $numberOfPages*4.5
                                       : (
                                           ($sizeColor1 == 30 && $sizeColor2 == 42)
                                           ? $numberOfPages*6.75
                                           : (
                                               ($sizeColor1 == 36 && $sizeColor2 == 48)
                                               ? $numberOfPages*9
                                               : 1
                                            )
                                        )
                                    );
                        }
                        //$sqrFt = ($sizeArr[0] * $sizeArr[1]) / 144;
                        $sqrFt = ceil($sqrFt);
                        $finalPrice = $sqrFt * $basePrice; 

                        //$finalPrice = $optionPrice;
                    }
                    else if(strtolower($optionName) == 'production time') 
                    {
                     $proTimeDes = $collection->compareToDatabase(strtolower($optionLabel),$id);
                     $totalPercentage .= $proTimeDes; 
                    }
                    else if(strtolower($optionName) == 'quantity') {
                     $totalPercentage .= $this->getQtyDiscount($optionLabel,$id);
                     $chkFinalPrice = $finalPrice;
                     $chkFinalPrice = $chkFinalPrice * $optionLabel;
                     
                    }
                    else {
                      $optionPrice = $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                      $logger->info('-- **optionPrice: -- '.$optionPrice);             
                      $logger->info('-- optionName: -- '.$optionName);             
                      if ($optionName == 'Grommets') {
                          $finalPrice += $optionPrice;    
                      }
                      else {
                        if($sqrFt==0) {
                            $optionPrice = 1 * $optionPrice;
                            //$logger->info('--if sqrFt==0 optionPrice: -- '.$optionPrice);   
                        }else{

                            $optionPrice = $sqrFt * $optionPrice;
                        }
                            //$logger->info('--else optionPrice: -- '.$optionPrice);
                        $finalPrice += $optionPrice;
                      }

                      //$logger->info('---- Before finalPrice: ---- '.$finalPrice);
                      // $logger->info('---- optionPrice: ---- '.$optionPrice);
                      $logger->info('---- After finalPrice: ---- '.$finalPrice);

                    }

                }
            }

           if(!empty($totalPercentage)) {
            $logger->info('------before discount finalPrice: ------ '.$finalPrice);
            $finalPrice = $this->calculatePriceDiscount($finalPrice , $totalPercentage);
            $logger->info('------after discount finalPrice: ------ '.$finalPrice);
            $logger->info('------totalPercentage: ------ '.$totalPercentage);
           }
        }

        if($chkFinalPrice < 25) {
                        $finalPrice = 25;
                     }
        /*$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/posterprinterlogs.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);*/
        $logger->info('-- chkFinalPrice: -- '.$chkFinalPrice);             
        $logger->info('--finalPrice: -- '.ceil($finalPrice));

        return $finalPrice;
    }

    /**
     * Custom function for calculate production
     * time and quantity discount percentage on
     * final price
     */
    public function calculatePriceDiscount($finalPrice, $totalPercentage){
      if(!empty($totalPercentage)) {
       $result = eval('return ' . $totalPercentage . ';');
       if($result != 0) {
        $discount = ($finalPrice * $result) / 100 ;
        $finalPrice = $finalPrice + ($discount);
       }
      }
      return $finalPrice;
    }

    /**
     * Custom function for get discount percentage 
     * of selected quantity
     */

    public function getQtyDiscount($qty = 24 , $id = '') {
     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
     $quantityDiscount = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
     $qtyDiscount = $quantityDiscount->getDiscount($qty,$id);
     return $qtyDiscount;
    }
    
}
