<?php
namespace Mangoit\Minicart\Plugin\Checkout;

class Address
{
    /*public function afterGetFirstname(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {
        if (empty(trim($result))) {
            return "N/A";
        }

        return $result;
    }
*/
    public function afterGetLastname(
        \Magento\Quote\Model\Quote\Address $subject,
        $result
    ) {
        if (empty(trim($result))) {
            return ".";
        }
        return $result;
    }
}