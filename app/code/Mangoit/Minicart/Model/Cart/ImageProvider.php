<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mangoit\Minicart\Model\Cart;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImageProvider extends \Magento\Checkout\Model\Cart\ImageProvider
{
    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var \Magento\Checkout\CustomerData\ItemPoolInterface
     */
    protected $itemPool;

    /**
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository
     * @param \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
    ) {
        $this->itemRepository = $itemRepository;
        $this->itemPool = $itemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($cartId)
    {
        $itemData = [];        
        /** @see code/Magento/Catalog/Helper/Product.php */
        $items = $this->itemRepository->getList($cartId);
        /** @var \Magento\Quote\Model\Quote\Item $cartItem */

        foreach ($items as $cartItem) {
            $allData = $this->itemPool->getItemData($cartItem);
           
            if(isset($allData['custom_file'])){
                $allData['product_image']['src'] = $allData['custom_file'];    
            }
            else{
                $allData['product_image']['src'] = $allData['product_image'];    
            }            
            
            $itemData[$cartItem->getItemId()] = $allData['product_image'];
        }
        
        return $itemData;
    }
}
