<?php

namespace Magesales\QBMSPayment\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class InitializeCommand implements CommandInterface
{
    public function execute(array $command)
    {
        $state = SubjectReader::readStateObject($command);
        $paymentDO = SubjectReader::readPayment($command);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setAmountAuthorized($payment->getOrder()->getTotalDue());
        $payment->setBaseAmountAuthorized($payment->getOrder()->getBaseTotalDue());
        $payment->getOrder()->setCanSendNewEmailFlag(false);

        $state->setData(OrderInterface::STATE, Order::STATE_PENDING_PAYMENT);
        $state->setData(OrderInterface::STATUS, Order::STATE_PENDING_PAYMENT);
        $state->setData('is_notified', false);
    }
}
