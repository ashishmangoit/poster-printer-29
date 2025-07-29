<?php 
namespace Mangoit\MultiplyOptions\Helper;			
use Magento\Framework\Filesystem;			
use Magento\Framework\Url;			
use Magento\Framework\App\Helper\AbstractHelper;			
use Magento\Framework\App\Filesystem\DirectoryList;			
class Image extends \Magento\Framework\App\Helper\AbstractHelper {
    protected $scopeConfig;			
    protected $storeManager;			
    protected $messageManager;			
    protected $_response;			
    protected $_resourceConfig;			
    protected $_responseFactory;			
    protected $_url;			
    protected $_filesystem;			
    protected $_directory;			
    protected $_imageFactory;			
    public function __construct(			
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,			
        \Magento\Store\Model\StoreManagerInterface $storeManager,			
        \Magento\Framework\Message\ManagerInterface $messageManager,			
        \Magento\Framework\App\ResponseInterface $response,			
        \Magento\Framework\App\Config\Storage\WriterInterface $resourceConfig,			
        \Magento\Framework\App\ResponseFactory $responseFactory,			
        \Magento\Framework\UrlInterface $url,			
        \Magento\Framework\Filesystem $filesystem,			
        \Magento\Framework\Image\AdapterFactory $imageFactory			
    )	
    {
			
        $this->scopeConfig = $scopeConfig;			
        $this->_storeManager=$storeManager;			
        $this->messageManager=$messageManager;			
        $this->_response=$response;			
        $this->_resourceConfig=$resourceConfig;			
         $this->_responseFactory = $responseFactory;			
        $this->_url = $url;			
        $this->_filesystem = $filesystem;			
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);			
        $this->_imageFactory = $imageFactory;			
    }


			
    public function imageResize(			
    $src,			
    $width=165,			
    $height=165,		
    $dir='custom-resize/'			
    ){
    	
        $absPath = $this->_filesystem


			
        ->getDirectoryRead(DirectoryList::MEDIA)


			
        ->getAbsolutePath().$src;
        $imageResized = $this->_filesystem


			
        ->getDirectoryRead(DirectoryList::MEDIA)


			
        ->getAbsolutePath($dir).


			
        $this->getNewDirectoryImage($src);


			
        $imageResize = $this->_imageFactory->create(); 


			
        $imageResize->open($absPath);

			
        $imageResize->backgroundColor([255, 255, 255]);


			
        $imageResize->constrainOnly(TRUE);


			
        $imageResize->keepTransparency(TRUE);


			
        $imageResize->keepFrame(true);


			
        $imageResize->keepAspectRatio(true);


			
        $imageResize->resize($width,$height);



        $dest = $imageResized ;


			
        $imageResize->save($dest);


			
        $resizedURL= $this->_storeManager->getStore()


			
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).


			
        $dir.$this->getNewDirectoryImage($src);


			
        return $resizedURL;


			


			
    }


			
    public function getNewDirectoryImage($src){


			
        $segments = array_reverse(explode('/',$src));


			
        $first_dir = substr($segments[0],0,1);


			
        $second_dir = substr($segments[0],1,1);


			
        return 'cache/'.$first_dir.'/'.$second_dir.'/'.$segments[0];


			
    }


			
}

			