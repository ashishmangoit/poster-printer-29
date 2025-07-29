<?php
namespace Mangoit\Banner\Block\Adminhtml\Banner;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;
    protected $blockRepository;

    public function __construct(
        Context $context
    ) {
        $this->context = $context;
    }

    public function getBlockId()
    {
        return null;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}