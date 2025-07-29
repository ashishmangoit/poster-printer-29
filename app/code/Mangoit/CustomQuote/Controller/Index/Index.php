<?php


namespace Mangoit\CustomQuote\Controller\Index;


class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     * 
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     * 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $title = '';
        if (isset($_REQUEST['title'])) {
            if (strpos($_REQUEST['title'], 'quote') !== false) {
                $title = 'Custom Quote';
            } else if (strpos($_REQUEST['title'], 'fileupload') !== false) {
                $title = 'File Upload';
            } else {
                $title = 'Custom Quote';
            }
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set($title);
        return $resultPage;
    }
}