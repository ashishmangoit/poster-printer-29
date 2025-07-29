<?php

namespace Magesales\QBMSPayment\Controller\Adminhtml\Connection;

use Magesales\QBMSPayment\Controller\Adminhtml\AbstractConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\Exception;

class Start extends AbstractConnection
{
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $redirectPage = $this->resultFactory->create('redirect');
        try {
            $redirectUrl = $this->client->getAuthorizationURL();
            $this->_redirect($redirectUrl);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirectPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirectPage;
        }
    }
}
