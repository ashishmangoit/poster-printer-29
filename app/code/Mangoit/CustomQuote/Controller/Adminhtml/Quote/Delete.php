<?php
namespace Mangoit\CustomQuote\Controller\Adminhtml\Quote;
 
use Magento\Backend\App\Action;
 
class Delete extends Action
{
    protected $_model;
 
    /**
     * @param Action\Context $context
     * @param \Maxime\Job\Model\Department $model
     */
    public function __construct(
        Action\Context $context,
        \Mangoit\CustomQuote\Model\Quote $model
    ) {
        parent::__construct($context);
        $this->_model = $model;
    }
 
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_model;
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('Quote deleted'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/index', ['id' => $id]);
            }
        }
        $this->messageManager->addError(__('Quote does not exist'));
        return $resultRedirect->setPath('*/*/');
    }
}