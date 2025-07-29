<?php 

namespace Mangoit\MultiplyOptions\Helper;

class ServerTime extends \Magento\Framework\App\Helper\AbstractHelper
{	
 	protected $timezone;

 	public function __construct(\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone) {
    	$this->timezone = $timezone;
	}
 // server time slot display the server time 
 //mon_fri_01_01_pm_12_0_am in this formate
	public function serverTimeSlot() {
		$day = $this->dayslot();   
		return $day;
	}
 
	public function daySlot() {
	  $date = $this->timezone->date();
	  $dayNumber = $date->format('N');
	  //$dayNumber = date('N',$this->timeStamp);
	  $time = $this->timeSlot();

	  if($dayNumber <= 5) {
	   return 'mon_fri_'.$time;
	  } else {
	   return 'sat_mon_12_01_am_12_0_pm';
	  }	  
	}
    
	public function timeSlot() {		
		$date = $this->timezone->date();
	  	$timeData = $date->format('h:m A');
	  	$time = explode(' ',$timeData);
		$interval = $time[1];
		$hrsData = explode(':',$time[0]);
		$hr = $hrsData[0];
		$min = $hrsData[1];
		if($hr == 12) {
  			if($interval == 'AM' || $interval == 'PM') {
  			  $timeSlot =  '12_01_am_01_0_pm';  
	  		}
	  	} else if ($interval == 'AM') {
				 $timeSlot =  '12_01_am_01_0_pm';  
		} else if ($interval == 'PM') {
		  $timeSlot =  '01_01_pm_12_0_am';
		}
		return $timeSlot;    	
	}

 //compare database value with option production time
	public function compareToDatabase($time=null,$product_id = null) {
		$time = strtolower($time);
		$production_time = array();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
	    ->addFieldToFilter('entity_id', array('eq' => $product_id));        
	    $productCollection->load();	    
	    $data =  $productCollection->getData();	    
	    $attribute_set_id = $data[0]['attribute_set_id'];
	    $attributeCollection = $objectManager->create('Magento\Eav\Api\AttributeSetRepositoryInterface')->get($attribute_set_id);
	    $attributeName = $attributeCollection->getAttributeSetName();	    
	  	$productSlotCollection = $objectManager->get('Mangoit\MultiplyOptions\Model\ProductionTime')->getCollection();
	   foreach ($productSlotCollection->getData() as  $value) {
	           $production_time[] = $value;
	   }
	   $slot = $this->serverTimeSlot();	  
	   $match =0;
	   foreach($production_time as $v) {
	   		if(strtolower($v['attribute_name']) == $attributeName)   			
	   			$match = 1;

			if(strtolower($v['times']) == $time && isset($v[$slot]) && $match ==1) {		
	       		return $v[$slot] = $v[$slot] == '0' ?'+ 0' :$v[$slot] ;
	     	}
		     	
	    }
	}

	// public function sameDayShow($product_id) {
	// 	$timeStamp = $this->timezone->date();
	// 	$dayNumber = $timeStamp->format('N');
	// 	$eligible_to_show = 'yes';
	//   	$timeData = $timeStamp->format('h:i A'); 
	//   	$time = explode(' ',$timeData);
	//   	$interval = $time[1];
	// 	$hrsData = explode(':',$time[0]);
	// 	$hr = $hrsData[0];
	// 	$min = $hrsData[1];
	// 	if($product_id == 24 || $product_id == 26 || $product_id == 25 ||$product_id == 28){
	// 		$eligible_to_show = 'yes';	
	// 	}
	// 	else if($dayNumber <= 5 ){
	// 		if($interval == 'PM'){
	// 			if($product_id == 32){
	// 				$eligible_to_show = 'yes';	
	// 			}else if($product_id==23){
	// 				$eligible_to_show = 'no';	
	// 			}
	// 			else if($hr > 4 && $hr <= 11){
	// 				$eligible_to_show = 'no';		
	// 			}
	// 		}
	// 	}else{
	// 		$eligible_to_show = 'no';		
	// 	}
	// 	return $eligible_to_show;
	// }

	public function sameDayShow($product_id) {
		// Get current timestamp, day number, date, and time
		$timeStamp = $this->timezone->date();
		$dayNumber = $timeStamp->format('N'); // 1 (Monday) - 7 (Sunday)
	
		// Default to 'no'
		$eligible_to_show = 'no';
	
		// Check if it's Monday to Friday (dayNumber 1-5)
		if ($dayNumber >= 1 && $dayNumber <= 5) {
			$timeData = $timeStamp->format('H:i'); // Get time in 24-hour format 'HH:MM'
			$hour = (int)$timeStamp->format('H'); // Get current hour as an integer
			$minute = (int)$timeStamp->format('i'); // Get current minute as an integer
	
			// Check if time is between 12:01 AM (00:01) and 4:00 PM (16:00)
			if (($hour > 0 || ($hour == 0 && $minute >= 1)) && ($hour < 16)) {
				$eligible_to_show = 'yes';
			}
		}
	
		if (in_array($product_id, [24, 25, 26, 28])) {
			
		} else if ($product_id == 32 && $dayNumber <= 5) {

			// Product ID 32 follows the 12:01 AM to 4:00 PM, Monday-Friday condition
			$eligible_to_show = ($hour < 16) ? 'yes' : 'no';

		} else if ($product_id == 23 || $dayNumber <= 5 && $hour >= 16) {
			$eligible_to_show = 'no'; // Product ID 23 not eligible after 4 PM
		}
		
		return $eligible_to_show;
	}

	public function businessDay3Show($product_id) {
		$timeStamp = $this->timezone->date();
		$dayNumber = $timeStamp->format('N');
		$eligible_to_show = 'no';
	  	$timeData = $timeStamp->format('h:i A'); 
	  	$time = explode(' ',$timeData);
	  	$interval = $time[1];
		$hrsData = explode(':',$time[0]);
		$hr = $hrsData[0];
		$min = $hrsData[1];
		if($dayNumber > 5 ){
			if($product_id == 36){
				$eligible_to_show = 'yes';
			}
		}else if($product_id == 29 && $hr >= 5){
			$eligible_to_show = 'yes';		
		}
		else{
			$eligible_to_show = 'no';		
		}

		return $eligible_to_show;
	}

	public function businessDay2Show($product_id) {
		$timeStamp = $this->timezone->date();
		$dayNumber = $timeStamp->format('N');
		$eligible_to_show = 'no';
	  	$timeData = $timeStamp->format('h:i A'); 
	  	$time = explode(' ',$timeData);
	  	$interval = $time[1];
		$hrsData = explode(':',$time[0]);
		$hr = $hrsData[0];
		$min = $hrsData[1];
		if($product_id == 29 && $hr < 5)
		{
			$eligible_to_show = 'yes';
		}

		return $eligible_to_show;
	}

	public function getDeliveryDate($day_add)
	{
		$timeStamp = $this->timezone->date();
		$bussinessDays = [];	
		$day = strtolower($timeStamp->format('l'));
		$date = $timeStamp->format('M d, Y'); 
		date_default_timezone_set("America/Los_Angeles");
		if($day == "saturday")
	    {
	        $day_add = $day_add+2; 
	        $production_time = date('M d, Y', strtotime("+".$day_add ."days"));
	    } 
	    else if($day == "sunday")
	    {
	        $day_add = $day_add+1;
	        $production_time = date('M d, Y', strtotime("+".$day_add ."days"));

	    } else if($day != 'saturday' || $day != 'sunday'){
	    	for($i=$day_add; $i>=1 ; $i--){
			  $timestamp = strtotime("+".$i ."days");
			  $bussinessDays[] = date('l', $timestamp);
			}

			if(in_array('Saturday', $bussinessDays)){
				$day_add += 2;
			}

	    	$production_time = date('M d, Y', strtotime("+".$day_add ."days"));
	    	
	    }else{
	    	$production_time = date('M d, Y', strtotime("+".$day_add ."days"));
	    }
	    return $production_time;
  	}	
}	