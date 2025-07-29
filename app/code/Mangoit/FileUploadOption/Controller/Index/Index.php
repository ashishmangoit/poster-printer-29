<?php
namespace Mangoit\FileUploadOption\Controller\Index; 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

  class Index extends Action
  {
      /**
       * @var \Tutorial\SimpleNews\Model\NewsFactory
       */
       protected $_resultPageFactory;
       
      // protected $_helperData;

     /**
      * @param Context     $context
      * @param PageFactory $resultPageFactory
      */
     public function __construct(
         Context $context,
         PageFactory $resultPageFactory
     ) {
         $this->_resultPageFactory = $resultPageFactory;
         parent::__construct($context);
     }
   
      public function execute()
      { 
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        if(isset($_REQUEST['product'])){
          $product_id = $_REQUEST['product'];
          $catalogSession = $objectManager->create('\Magento\Catalog\Model\Session');
          $file_key = array_keys($_FILES);       
          $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
          $mediaPath  =   $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath(); 
          $media  =  $mediaPath.'files/';        
          $title = explode('.',$_FILES[$file_key[0]]['name']);
          $extension = strtolower(array_pop($title));    
          $file_name = $_FILES[$file_key[0]]['name'];        
          $file_size =$_FILES[$file_key[0]]['size'];
          $file_tmp =$_FILES[$file_key[0]]['tmp_name'];
          $file_type=$_FILES[$file_key[0]]['type'];
          if($extension =='pdf' || $extension == 'png' || $extension == 'jpeg' || $extension == 'jpg' ||$extension== 'tiff' || $extension == 'ai' || $extension == 'psd' || $extension == 'ppt' ||$extension == 'pptx'|| $extension == 'eps' || $extension == 'doc' || $extension == 'docx'){
            if (move_uploaded_file($file_tmp,$media.$file_name))
            {   
              if($extension == 'ppt' ||$extension == 'pptx' || $extension == 'doc' || $extension == 'docx'){
                echo json_encode(array('type' =>$extension));die();
              }else{
                try { 
                  $file = 'thumbnails/'.time().'.png';
                  $filepath = 'files/'.$file; 
                  $mediathumbnails  = $media.$file;
                  $catalogSession->setCustomThumbImg($filepath);
                  $mediathumbnails = preg_replace('/\s+/', '', $mediathumbnails);
                  $im = new \Imagick($media.$file_name."[0]");
                  $im->setimageformat('png');
                  $im->setbackgroundcolor('rgb(64, 64, 64)');
                  $im->thumbnailImage(460, 460, true, true);
                 // $im->thumbnailimage(150, 300); // width and height
                  $im->writeimage($mediathumbnails);
                  $im->clear();
                  $im->destroy();
                  $path = $mediathumbnails;
                  $type = pathinfo($path, PATHINFO_EXTENSION);
                  $data = file_get_contents($path);
                  echo json_encode(array('path' =>$filepath,'type'=>'png'));
                  die();
                   
                }
                catch(Exception $e) {
                  echo json_encode(array('error'=>'true','message'=>$e->getMessage()));
                  die;
                }
              }
            }
          }else{
            echo json_encode(array('path' =>'Error'));die();
          }
        }else{
            echo json_encode(array('path' =>'Error'));die();
          }        
      }
    }