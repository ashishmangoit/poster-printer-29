<?php
namespace Mangoit\Banner\Controller\Adminhtml\Index;
use Magento\Backend\App\Action\Context;

class Delete extends \Magento\Backend\App\Action
{
    public $model;

    public function __construct(
        Context $context,
        \Mangoit\Banner\Model\Banner $model
    ) {
        $this->model=$model;
        parent::__construct($context);        
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mangoit_Banner::banner');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $this->model->load($id);
                $this->model->delete();
                $this->messageManager->addSuccess(__('The banner has been deleted.'));
                $this->_eventManager->dispatch(
                    'adminhtml_banners_on_delete',
                    ['status' => 'success']
                );
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->_eventManager->dispatch(
                    'adminhtml_banners_on_delete',
                    ['status' => 'fail']
                );
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        $this->messageManager->addError(__('We can\'t find a banner to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}