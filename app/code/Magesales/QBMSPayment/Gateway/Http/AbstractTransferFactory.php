<?php

namespace Magesales\QBMSPayment\Gateway\Http;

use Magento\Framework\Xml\Generator;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magesales\QBMSPayment\Helper\Data as QBMSPaymentHelper;

/**
 * Class AbstractTransferFactory
 */
abstract class AbstractTransferFactory implements TransferFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Generator
     */
    protected $generator;
    /**
     * @var QBMSPaymentHelper
     */
    protected $qbmspaymentHelper;
    /**
     * Transaction Type
     *
     * @var string
     */
    private $action;

    /**
     * AbstractTransferFactory constructor.
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     * @param Generator $generator
     * @param QBMSPaymentHelper $qbmspaymentHelper
     * @param null $action
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder,
        Generator $generator,
        QBMSPaymentHelper $qbmspaymentHelper,
        $action = null
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
        $this->generator = $generator;
        $this->action = $action;
        $this->qbmspaymentHelper = $qbmspaymentHelper;
    }

    protected function getUrl()
    {
        return $this->qbmspaymentHelper->getGatewayUrl();
    }
}
