<?php
namespace Mangoit\CustomQuote\Controller\Adminhtml\Quote;
 
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
 
class Index extends Action
{
    const ADMIN_RESOURCE = 'Mangoit_CustomQuote::quote';
 
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
 
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
 
    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Mangoit_CustomQuote::custom_quote');
        $resultPage->addBreadcrumb(__('Quote'), __('Quote'));
        $resultPage->addBreadcrumb(__('Manage Quote'), __('Manage Quote'));
        $resultPage->getConfig()->getTitle()->prepend(__('Quote'));
 
        return $resultPage;
    }
}