<?php
namespace Mangoit\CustomQuote\Controller\Adminhtml\Quote;
 
use Magento\Backend\App\Action;
 
class View extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
 
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var \Maxime\Job\Model\Department
     */
    protected $_model;
 
    /**
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Zalw\MagEcartApp\Model\Notification $model
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Mangoit\CustomQuote\Model\Quote $model
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_model = $model;
        parent::__construct($context);
    }
  
    /**
     * Edit Department
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {

    	/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        // $resultPage->setActiveMenu('Mangoit_CustomQuote::custom_quote');
        // $resultPage->addBreadcrumb(__('Quote'), __('Quote'));
        // $resultPage->addBreadcrumb(__('Manage Quote'), __('Manage Quote'));
         $resultPage->getConfig()->getTitle()->prepend(__('Custom Quote'));
 
        return $resultPage;
        // $id = $this->getRequest()->getParam('id');
        // $model = $this->_model;
 
        // // If you have got an id, it's edition
        // if ($id) {
        //     $model->load($id);
        //     //print_r($model->getData());die;
            
        // }
    }
}