<?php
namespace Mangoit\Banner\Controller\Adminhtml\Index;
use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{
    protected $_coreRegistry = null;
    protected $resultPageFactory;
    public $model;
    public $_modelSession;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Mangoit\Banner\Model\Banner $model,
        \Magento\Backend\Model\Session $modelSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_modelSession=$modelSession;
        $this->model=$model; 
        parent::__construct($context);   
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mangoit_Banner::banner');
    }

    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Mangoit_Banner::banner')
            ->addBreadcrumb(__('Banner'), __('Banner'))
            ->addBreadcrumb(__('Manage Banner'), __('Manage Banner'));
        return $resultPage;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $this->model->load($id);
            if (!$this->model->getId()) {
                $this->messageManager->addError(__('This record no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $data = $this->_modelSession->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        
        $this->_coreRegistry->register('banner', $this->model);
        
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Banner') : __('New Banner'),
            $id ? __('Edit Banner') : __('New Banner')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Banner'));
        $resultPage->getConfig()->getTitle()
            ->prepend($this->model->getId() ? __('Edit Banner') : __('New Banner'));

        return $resultPage;
    }
}