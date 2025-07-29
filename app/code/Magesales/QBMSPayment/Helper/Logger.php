<?php

namespace Magesales\QBMSPayment\Helper;

use Psr\Log\LoggerInterface;

class Logger
{
    protected $logger;
    protected $helper;

    public function __construct(LoggerInterface $logger, Data $helper)
    {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function debug($message, array $context = [])
    {
        if ($this->helper->isLoggerEnabled()) {
            $message = "QBMS Direct : " . $message;
            $this->logger->debug($message, $context);
        }
    }
}
