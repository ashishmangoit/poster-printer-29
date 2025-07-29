<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category  BSS
 * @package   Bss_PreSelectShippingPayment
 * @author    Extension Team
 * @copyright Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license   http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\PreSelectShippingPayment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Bss\PreSelectShippingPayment\Helper\Data;

/**
 * Class PaymentMethods
 *
 * @package Bss\PreSelectShippingPayment\Model\Config\Source
 */
class PaymentMethods implements ArrayInterface
{
    private $helper;

    /**
     * @param Data $paymentHelper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $paymentMethods = $this->helper->getPaymentMethodList();
        $results = [['value' => '', 'label' => __('-- Please select --')]];
        foreach ($paymentMethods as $methodCode => $methodTitle) {
            $results[] = [
                'value' => $methodCode,
                'label' => $methodTitle
            ];
        }
        return $results;
    }
}
