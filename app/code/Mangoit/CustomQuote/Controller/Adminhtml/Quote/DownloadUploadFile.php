<?php
/**
 * Copyright © 2017 Mangoit. All rights reserved.
 */
namespace Mangoit\CustomQuote\Controller\Adminhtml\Quote;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
/**
 * Class Index
 * @package Magestore\OrderSuccess\Controller\Adminhtml\Pattern
 */
class DownloadUploadFile extends \Magento\Backend\App\Action
{
   
    
 /**
     * @var Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_downloader;
 
    /**
     * @var Magento\Framework\Filesystem\DirectoryList
     */
    protected $_directory;
 
    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory
    ) {
        $this->_downloader =  $fileFactory;
        $this->_directory = $directory;
        parent::__construct($context);
    }
 

     public function execute()
    {   
        $filename = $this->getRequest()->getParams('filepath');
        $filepath = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $filename['filepath']);

        $path = $this->_directory->getPath('media').'/quote/'.$filepath;

        return $this->_downloader->create(
                $filename['filepath'],
                @file_get_contents($path)
            );
        
    }

   
}