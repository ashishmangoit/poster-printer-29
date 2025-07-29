<?php
namespace Mangoit\Banner\Controller\Adminhtml\Index;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Mangoit\Banner\Model\Banner;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Inspection\Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\RequestInterface;

class Save extends \Magento\Backend\App\Action
{
    protected $dataPersistor;
    protected $scopeConfig;   
    protected $_escaper;
    protected $inlineTranslation;
    protected $_dateFactory;
    public $model;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
        \Mangoit\Banner\Model\Banner $model
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->scopeConfig = $scopeConfig;
        $this->_escaper = $escaper;
        $this->_dateFactory = $dateFactory;
        $this->inlineTranslation = $inlineTranslation;
        $this->model = $model;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
       
        if ($data) {

            $id = $this->getRequest()->getParam('id');

            $data['stores'] = implode(',',$data['stores']);

            if (isset($data['status']) && $data['status'] === 'true') {
                $data['status'] = Block::STATUS_ENABLED;
            }
            if (empty($data['id'])) {
                $data['id'] = null;
            }
           
            $this->model = $this->model->load($id);
            if (!$this->model->getId() && $id) {
                $this->messageManager->addError(__('This Banner no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            if (isset($data['image'][0]['name']) && isset($data['image'][0]['tmp_name'])) {
                $data['image'] ='/mangoit/homepage-banner/'.$data['image'][0]['name'];
                 
            } elseif (isset($data['image'][0]['name']) && !isset($data['image'][0]['tmp_name'])) {
                $data['image'] =$data['image'][0]['name'];
            } else {
                $data['image'] = null;
            }

            if (isset($data['mobile_image'][0]['name']) && isset($data['mobile_image'][0]['tmp_name'])) {
                $data['mobile_image'] ='/mangoit/homepage-banner/'.$data['mobile_image'][0]['name'];
                 
            } elseif (isset($data['mobile_image'][0]['name']) && !isset($data['mobile_image'][0]['tmp_name'])) {
                $data['mobile_image'] =$data['mobile_image'][0]['name'];
            } else {
                $data['mobile_image'] = null;
            }

            $this->model->setData($data);

            $this->inlineTranslation->suspend();
            try {
                $this->model->save();
                $this->messageManager->addSuccess(__('Banner Saved successfully'));
                $this->dataPersistor->clear('mangoit_banner');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $this->model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the banner.'));
            }

            $this->dataPersistor->set('mangoit_banner', $data);
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}