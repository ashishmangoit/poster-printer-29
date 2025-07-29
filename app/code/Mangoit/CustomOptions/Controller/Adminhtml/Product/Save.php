<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * Override product save controller for add custom options 
 */
namespace Mangoit\CustomOptions\Controller\Adminhtml\Product;
use Magento\Backend\App\Action;
use Magento\Catalog\Controller\Adminhtml\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class Save
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\Catalog\Controller\Adminhtml\Product
{
    /**
     * @var Initialization\Helper
     */
    protected $initializationHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Copier
     */
    protected $productCopier;

    /**
     * @var \Magento\Catalog\Model\Product\TypeTransitionManager
     */
    protected $productTypeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param Builder $productBuilder
     * @param Initialization\Helper $initializationHelper
     * @param \Magento\Catalog\Model\Product\Copier $productCopier
     * @param \Magento\Catalog\Model\Product\TypeTransitionManager $productTypeManager
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Product\Builder $productBuilder,
        \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper $initializationHelper,
        \Magento\Catalog\Model\Product\Copier $productCopier,
        \Magento\Catalog\Model\Product\TypeTransitionManager $productTypeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->initializationHelper = $initializationHelper;
        $this->productCopier = $productCopier;
        $this->productTypeManager = $productTypeManager;
        $this->productRepository = $productRepository;
        parent::__construct($context, $productBuilder);
    }
    /**
     * Save product action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {   
        $storeId = $this->getRequest()->getParam('store', 0);
        $store = $this->getStoreManager()->getStore($storeId);
        $this->getStoreManager()->setCurrentStore($store->getCode());
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $productAttributeSetId = $this->getRequest()->getParam('set');

        $productTypeId = $this->getRequest()->getParam('type');
        if ($data) {
            try {
                $product = $this->initializationHelper->initialize(
                    $this->productBuilder->build($this->getRequest())
                );
                $this->productTypeManager->processProduct($product);
        
                if (isset($data['product'][$product->getIdFieldName()])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Unable to save product'));
                }
        
                $originalSku = $product->getSku();
                
                $this->handleImageRemoveError($data, $product->getId());
                $this->getCategoryLinkManagement()->assignProductToCategories(
                    $product->getSku(),
                    $product->getCategoryIds(),
                    $product->save()
                );
                $productId = $product->getEntityId();
                //$attribute_id = 15;
                $this->htmlData($productId,$productAttributeSetId);
                $productTypeId = $product->getTypeId();        
                $this->copyToStores($data, $productId);
                
                $this->messageManager->addSuccess(__('You saved the product.'));
                $this->getDataPersistor()->clear('catalog_product');
                if ($product->getSku() != $originalSku) {
                    $this->messageManager->addNotice(
                        __(
                            'SKU for product %1 has been changed to %2.',
                            $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getName()),
                            $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($product->getSku())
                        )
                    );
                }
                $this->_eventManager->dispatch(
                    'controller_action_catalog_product_save_entity_after',
                    ['controller' => $this, 'product' => $product]
                );
        
                if ($redirectBack === 'duplicate') {
                    $newProduct = $this->productCopier->copy($product);
                    $this->messageManager->addSuccess(__('You duplicated the product.'));
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            } catch (\Exception $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->messageManager->addError($e->getMessage());
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            }
        } else {
            $resultRedirect->setPath('catalog/*/', ['store' => $storeId]);
            $this->messageManager->addError('No data to save');
            return $resultRedirect;
        }
        
        if ($redirectBack === 'new') {
            $resultRedirect->setPath(
                'catalog/*/new',
                ['set' => $productAttributeSetId, 'type' => $productTypeId]
            );
        } elseif ($redirectBack === 'duplicate' && isset($newProduct)) {
            $resultRedirect->setPath(
                'catalog/*/edit',
                ['id' => $newProduct->getEntityId(), 'back' => null, '_current' => true]
            );
        } elseif ($redirectBack) {
            $resultRedirect->setPath(
                'catalog/*/edit',
                ['id' => $productId, '_current' => true, 'set' => $productAttributeSetId]
            );
        } else {
            $resultRedirect->setPath('catalog/*/', ['store' => $storeId]);
        }
        return $resultRedirect;
    }

    /**
     * Notify customer when image was not deleted in specific case.
     * TODO: temporary workaround must be eliminated in MAGETWO-45306
     *
     * @param array $postData
     * @param int $productId
     * @return void
     */
    private function handleImageRemoveError($postData, $productId)
    {
        if (isset($postData['product']['media_gallery']['images'])) {
            $removedImagesAmount = 0;
            foreach ($postData['product']['media_gallery']['images'] as $image) {
                if (!empty($image['removed'])) {
                    $removedImagesAmount++;
                }
            }
            if ($removedImagesAmount) {
                $expectedImagesAmount = count($postData['product']['media_gallery']['images']) - $removedImagesAmount;
                $product = $this->productRepository->getById($productId);
                if ($expectedImagesAmount != count($product->getMediaGallery('images'))) {
                    $this->messageManager->addNotice(
                        __('The image cannot be removed as it has been assigned to the other image role')
                    );
                }
            }
        }
    }

    /**
     * Do copying data to stores
     *
     * @param array $data
     * @param int $productId
     * @return void
     */
    protected function copyToStores($data, $productId)
    {
        if (!empty($data['product']['copy_to_stores'])) {
            foreach ($data['product']['copy_to_stores'] as $websiteId => $group) {
                if (isset($data['product']['website_ids'][$websiteId])
                    && (bool)$data['product']['website_ids'][$websiteId]) {
                    foreach ($group as $store) {
                        $copyFrom = (isset($store['copy_from'])) ? $store['copy_from'] : 0;
                        $copyTo = (isset($store['copy_to'])) ? $store['copy_to'] : 0;
                        if ($copyTo) {
                            $this->_objectManager->create('Magento\Catalog\Model\Product')
                                ->setStoreId($copyFrom)
                                ->load($productId)
                                ->setStoreId($copyTo)
                                ->setCopyFromView(true)
                                ->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * @return \Magento\Catalog\Api\CategoryLinkManagementInterface
     */
    private function getCategoryLinkManagement()
    {
        if (null === $this->categoryLinkManagement) {
            $this->categoryLinkManagement = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Catalog\Api\CategoryLinkManagementInterface');
        }
        return $this->categoryLinkManagement;
    }

    /**
     * @return StoreManagerInterface
     * @deprecated
     */
    private function getStoreManager()
    {
        if (null === $this->storeManager) {
            $this->storeManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Store\Model\StoreManagerInterface');
        }
        return $this->storeManager;
    }

    /**
     * Retrieve data persistor
     *
     * @return DataPersistorInterface|mixed
     * @deprecated
     */
    protected function getDataPersistor()
    {
        if (null === $this->dataPersistor) {
            $this->dataPersistor = $this->_objectManager->get(DataPersistorInterface::class);
        }

        return $this->dataPersistor;
    }
   // Method create for add custom options to the product
    public function htmlData($productId,$attribute_id) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager        
        $product = $objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
        $attributeSet = $objectManager->create('Magento\Eav\Api\AttributeSetRepositoryInterface');
        $attributeSetRepository = $attributeSet->get($product->getAttributeSetId());
        $attribute_set_name = $attributeSetRepository->getAttributeSetName();
        $customOptions = $objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);//get options of product
        $optionarray = array();
        $count = 0;
       // echo $product->getName();die();
        foreach($customOptions as $option){
            $optionarray[$count]['title'] = $option->getTitle() ;
            $optionarray[$count]['type'] = $option->getType() ;
            $optionarray[$count]['values'] = $option->getValues() ;
            //$option->delete();
            $count++;
        } 
       // $product->setHasOptions(0)->save();  
        $optionSize = array(
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"22'' x 28''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"28'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
         $optionSizeGatorBoard = array(
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"22'' x 28''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"28'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
         $optionSizeDrawingBlueprints = array(
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"30'' x 42''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                            array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            )
                        );
        $optionSizeCorrugated = array(
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"22'' x 28''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"28'' x 44''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
         $optionSizeSintra = array(
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"22'' x 28''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"28'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeWallpaper=array(
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            )
                        );
        $optionSizeRet = array(
                            array(                    
                               "title"=>"33.5'' x 92''",
                               "price"=>240,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 92''",
                               "price"=>200,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 92''",
                               "price"=>280,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"48'' x 92''",
                               "price"=>320,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            )
                        );
        $optionSizeFabric = array(
                            
                            array(                    
                               "title"=>"24'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                            array(                    
                               "title"=>"24'' x 60''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"60'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"120'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"60'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"120'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeVinylBanner = array(
                           
                            array(                    
                               "title"=>"24'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                            array(                    
                               "title"=>"24'' x 60''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"60'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"120'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"60'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"120'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeAdhensive = array(
                            array(                    
                               "title"=>"20'' x 30''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"20'' x 20''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"30'' x 30''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"36'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"48'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeFlags = array(
                            array(                    
                               "title"=>"13ft Falcon Flag - 2 Sided",
                               "price"=>238,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"7ft Falcon Flag - 1 Sided",
                               "price"=>138,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"7ft Falcon Flag - 2 Sided",
                               "price"=>218,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"13ft Falcon Flag - 1 Sided",
                               "price"=>158,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );
        $optionSizeTableThrow = array(
                            array(                    
                               "title"=>"6ft Table - Drapes on 3 Sides",
                               "price"=>365,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"4ft Table - Drapes on 3 Sides",
                               "price"=>295,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"4ft Table - Drapes on 4 Sides",
                               "price"=>225,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"6ft Table - Drapes on 4 Sides",
                               "price"=>275,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"8ft Table - Drapes on 3 Sides",
                               "price"=>435,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"8ft Table - Drapes on 4 Sides",
                               "price"=>325,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            )
                        );

        $optionSizeMagnet = array(
                            array(                    
                               "title"=>"24'' x 18''",
                               "price"=>52,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>35,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 12''",
                               "price"=>40,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"42'' x 12''",
                               "price"=>70,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"72'' x 24''",
                               "price"=>190,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            )
                        );
        $optionSizeCanvas=array(
                            array(                    
                               "title"=>"16'' x 20''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"12'' x 12''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0,
                               "selected"=>"selected"
                            ),
                             array(                    
                               "title"=>"20'' x 20''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"24'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"40'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>7,
                               "is_delete"=>0
                            ),
                              array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeBacklit=array(
                            array(                    
                               "title"=>"12'' x 18''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"18'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"22'' x 28''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"28'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"30'' x 40''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionSizeFloorGraphics=array(
                            array(                    
                               "title"=>"20'' x 20''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"20'' x 30''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"24'' x 24''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"30'' x 30''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 36''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"36'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"48'' x 48''",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Custom Size",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>8,
                               "is_delete"=>0
                            )
                        );
        $optionMaterial = array(
                            array(                    
                               "title"=>"Semi Gloss Photopaper",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialFoamCore = array(
                            array(                    
                               "title"=>'3/16" White Foam Core',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialConverd = array(
                            array(                    
                               "title"=>'0.30" Polystyrene',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>'0.60" Polystyrene',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialWallpaper = array(
                            array(                    
                               "title"=>"3M Laminated Satin Vinyl",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"HP PVC Free Wallpaper",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optioNeedInstallationWallpaper = array(
                            array(                    
                               "title"=>"No",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Yes",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionWallSurfaceWallpaper = array(
                            array(                    
                               "title"=>"Smooth",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Textured",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialRect = array(
                            array(                    
                               "title"=>"Super Flat Matte Polypropylene",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
          $optionGroomets = array(
                            array(                    
                               "title"=>"No Grometts",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Placed on Each Corner",
                               "price"=>8,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),array(                    
                               "title"=>"Placed Every 2-3 Feet",
                               "price"=>16,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );

        $optionMaterialMagnet = array(
                            array(                    
                               "title"=>"Premium 30 Mil Magnet",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialFlags=array(
                            array(                    
                               "title"=>"Fabric",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialVinyl=array(
                            /*array(                    
                               "title"=>"13oz White Scrim Banner",
                               "price"=>5.50,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),*/
                            array(                    
                               "title"=>"Mesh Vinyl Banner",
                               "price"=>7.50,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialGatorBoard=array(
                            array(                    
                               "title"=>'3/16" Black Gator Board',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>'3/16" White Gator Board',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        ); 
         $optionMaterialCanvas=array(
                            array(                    
                               "title"=>"Premium Satin Canvas",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );

         $optionMaterialCorrugated=array(
                            array(                    
                               "title"=>"4mm White Corrugated Plastic",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialFabric=array(
                            array(                    
                               "title"=>"6oz Light Fabric",
                               "price"=>12.50,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialSintra = array(
                            array(                    
                               "title"=>"3mm White PVC",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
         $optionMaterialAdhensive=array(
                            array(                    
                               "title"=>"3.5 Mil Gloss Vinyl",
                               "price"=>7,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Satin/Matte Vinyl",
                               "price"=>7.50,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3M Controtac",
                               "price"=>12.50,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialDrawingBlueprints = array(
                            array(                    
                               "title"=>"20# White Paper",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialBacklitFilm = array(
                            array(                    
                               "title"=>"Backlit Film",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialAluminiumSigns = array(
                            array(                    
                               "title"=>"0.04'' Aluminum",
                               "price"=>15,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"0.08'' Aluminum",
                               "price"=>25,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialFloorGraphics = array(
                            array(                    
                               "title"=>"Adhesive Floor Vinyl",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialPerforatedWindowGraphics = array(
                            array(                    
                               "title"=>"50/50 Perforated Adhesive Vinyl",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"70/30 Perforated Adhesive Vinyl",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionPrintedSides = array(
                            array(                    
                               "title"=>"Front Side Only",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );

        $optionPrintedSidesAluminiumSigns = array(
                            array(                    
                               "title"=>"Single Sided",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Double Sided",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
                         
        $optionPrintedSewing = array(
                            array(                    
                               "title"=>"All 4 Sides",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"No Sewing",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
         $optionPrintedSewingTableThrow = array(
                            array(                    
                               "title"=>"Edges Sewn",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );

         $optionRoundedCornerAluminiumSigns = array(
                            array(                    
                               "title"=>"None",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"1/2'' Radius",
                               "price"=>2,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"1''Radius",
                               "price"=>2,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );
        $optionLaminate = array(
                            array(                    
                               "title"=>"None",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3.2 mil Gloss Laminate",
                               "price"=>2,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3.2 mil Satin Laminate",
                               "price"=>2.5,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );
        $optionLaminateRetracable = array(
                            array(                    
                               "title"=>"None",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Gloss Laminate",
                               "price"=>2,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"Satin/Matte Laminate",
                               "price"=>2.5,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            )
                        );
        $optionMounting = array(
                            array(                    
                               "title"=>"None",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3/16".'"'."White Foam Core",
                               "price"=>3.50,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3/16".'"'."Black Gator Board",
                               "price"=>7.50,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3/16".'"'."conVerd Board",
                               "price"=>5.50,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3/16".'"'."Falcon Board",
                               "price"=>5.50,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            )
                        );
        $optionMountingAdhensive = array(
                            array(                    
                               "title"=>"None",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"3/16".'"'."Corrugated Plastic",
                               "price"=>3.50,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),                           
                            array(                    
                               "title"=>"3mm White PVC Board",
                               "price"=>4.50,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            )
                        );
        $optionQty =array();
        for($i= 1; $i<=150; $i++){           
            $optionQty[] = array(                    
                               "title"=>$i,
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>$i,
                               "is_delete"=>0
                            );
                   
        }
        $optionQuantity = $optionQty;
        $optionQtymagnet = array();
        for($i= 1; $i<=99; $i++){           
            $optionQtym[] = array(                    
                               "title"=>$i,
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>$i,
                               "is_delete"=>0
                            );
                   
        }        
        $optionQtymagnet = $optionQtym;
        $optionQuantityCanvas = array();
         for($i= 1; $i<=50; $i++){           
            $optionQtycanvas[] = array(                    
                               "title"=>$i,
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>$i,
                               "is_delete"=>0
                            );
                   
        }      
        $optionQuantityCanvas = $optionQtycanvas;
        $optionQuantityDrawingBlueprints = array();
         for($i= 1; $i<=25; $i++){           
            $optionQtyDrawingBlueprints[] = array(                    
                               "title"=>$i,
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>$i,
                               "is_delete"=>0
                            );
                   
        }      
        $optionQuantityDrawingBlueprints = $optionQtyDrawingBlueprints;
         $productionTime =array(
                            array(                    
                               "title"=>"Same day",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"Next Business Day",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"2 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"3 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            )
                        );
         $productionTimem =array(                            
                             array(                    
                               "title"=>"3 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"4 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
          $productionTimeRect =array(                            
                                 array(                    
                                   "title"=>"Next Business Day",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>1,
                                   "is_delete"=>0
                                ),
                                 array(                    
                                   "title"=>"2 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>2,
                                   "is_delete"=>0
                                ),
                                 array(                    
                                   "title"=>"3 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>3,
                                   "is_delete"=>0
                                )
                            );
        $productionTimeFlags =  array(
                                 array(                    
                                   "title"=>"4 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>1,
                                   "is_delete"=>0
                                ),
                                 array(                    
                                   "title"=>"5-6 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>2,
                                   "is_delete"=>0
                                )
                            ); 
         $productionTimeTableThrow =  array(
                                 array(                    
                                   "title"=>"3 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>1,
                                   "is_delete"=>0
                                ),
                                 array(                    
                                   "title"=>"4-5 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>2,
                                   "is_delete"=>0
                                )
                            ); 
        $productionTimeFabric =  array(
                                 array(                    
                                   "title"=>"2 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>2,
                                   "is_delete"=>0
                                ),
                                 array(                    
                                   "title"=>"3 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>1,
                                   "is_delete"=>0
                                )
                            );
        $productionTimeWallpaper =  array(
                                 array(                    
                                   "title"=>"2-3 Business Days",
                                   "price"=>0,
                                    "price_type"=>"fixed",
                                    "sort_order"=>2,
                                   "is_delete"=>0
                                )
                            ); 
        $productionTimeCanvas = array(
                            array(                    
                               "title"=>"Next Business Day",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                            array(                    
                               "title"=>"2 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"3 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>3,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"4-5 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>4,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"6-7 Business Days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>5,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"8-10 Business days",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>6,
                               "is_delete"=>0
                            )
                        );  
       $proofsOptions = array(
                            array(                    
                               "title"=>" No Proof. Print as is.",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"PDF proof",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
       $artwork = array(
                            array(                    
                               "title"=>"File is ready to upload",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"Will send artwork after checkout",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialBase  = array(                            
                             array(                    
                               "title"=>"Spike Base",
                               "price"=>20,
                               "sort_order"=>1,
                                "price_type"=>"fixed",                                
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>" X-Base With Water Bag",
                               "price"=>60,
                                "price_type"=>"fixed",    
                                "sort_order"=>2,                            
                               "is_delete"=>0
                            )
                        );
        $optionMaterialBaseRetracable  = array(
                            array(                    
                               "title"=>"Silver Retractbale Base",
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionMaterialFalconBanner = array(
                            array(                    
                               "title"=>'3/16" White Falconboard',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            )
                        );
        $optionBinding = array(                            
                             array(                    
                               "title"=>"Bind/Staple Left Side",
                               "price"=>0,
                               "sort_order"=>1,
                                "price_type"=>"fixed",                                
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>" None",
                               "price"=>0,
                                "price_type"=>"fixed",    
                                "sort_order"=>2,                            
                               "is_delete"=>0
                            )
                        );  

        $optionStyle  = array(
                            array(                    
                               "title"=>"Gallery Wrapped",
                               "price"=>15,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"Museum Wraped",
                               "price"=>15,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>"Rolled - No Frame",
                               "price"=>12,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );  
        $optionFrame = array(
                            array(                    
                               "title"=>'3/4" Frame',
                               "price"=>5,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>'1 1/2" Frame',
                               "price"=>8,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );
        $optionColor = array(
                            array(                    
                               "title"=>'Black & White',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>1,
                               "is_delete"=>0
                            ),
                             array(                    
                               "title"=>'Color',
                               "price"=>0,
                                "price_type"=>"fixed",
                                "sort_order"=>2,
                               "is_delete"=>0
                            )
                        );                                      
            if($attribute_set_name =="poster_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSize                        
                    ),
                    array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterial
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                     array(
                        "sort_order"    => 5,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ), 
                     array(
                        "sort_order"    => 6,
                        "title"         => "Mounting Options",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMounting
                    ),
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ),
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        //"title"         => "UPLOAD YOUR FILE",
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else  if($attribute_set_name =="foam_core_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeGatorBoard                        
                    ),
                    array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFoamCore
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                     array(
                        "sort_order"    => 5,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ), 
                    
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ),
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }
            else if($attribute_set_name =="wallpaper_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Strip Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeWallpaper                        
                    ),
                    array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Wall Surface",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionWallSurfaceWallpaper
                    ),                   
                        array(
                        "sort_order"    => 4,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialWallpaper
                    ),
                    array(
                        "sort_order"    => 5,
                        "title"         => "Need Installation",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optioNeedInstallationWallpaper
                    ),
                    array(
                        "sort_order"    => 6,
                        "title"         => "Print Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeWallpaper
                    ),
                    array(
                            "sort_order"    => 7,
                            "title"         => "Preferred Installation Date",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="magnet_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeMagnet
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialMagnet
                    ),                 
                    
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQtymagnet
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimem
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="retracable_banner_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeRet
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialRect
                    ),                 
                     array(
                        "sort_order"    => 4,
                        "title"         => "Base",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialBaseRetracable
                    ),
                       array(
                        "sort_order"    => 5,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminateRetracable                
                    ),
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQtymagnet
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeRect
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="flags_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeFlags
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFlags
                    ),
                     array(
                        "sort_order"    => 4,
                        "title"         => "Base",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialBase
                    ),                 
                      
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQtymagnet
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeFlags
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="fabric_banner_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,                        
                        "values"        => $optionSizeFabric
                    ),
                     array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFabric
                    ),                 
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 5,
                        "title"         => "Sewing",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSewing
                    ),
                     array(
                        "sort_order"    => 6,
                        "title"         => "Grommets",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionGroomets
                    ),        
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeFabric
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ),  
                );
            }else if($attribute_set_name =="adhesive_vinyl_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeAdhensive
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialAdhensive
                    ),                 
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),
                     array(
                        "sort_order"    => 6,
                        "title"         => "Mounting Options",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMountingAdhensive
                    ),        
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="canvas_print_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeCanvas
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialCanvas
                    ),                 
                    array(
                        "sort_order"    => 3,
                        "title"         => "Style",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionStyle
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 5,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),
                     array(
                        "sort_order"    => 6,
                        "title"         => "Frame",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionFrame
                    ),        
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantityCanvas
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeCanvas
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }
            else if($attribute_set_name =="converd_board_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeSintra
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialConverd
                    ),                 
                    
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),
                        
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantityCanvas
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }
            else if($attribute_set_name =="corrugated_plastic_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeCorrugated
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialCorrugated
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),                         
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                  array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }            
             else if($attribute_set_name =="table_throw_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeTableThrow
                    ),                
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFlags
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),                 
                    array(
                        "sort_order"    => 4,
                        "title"         => "Sewing",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSewingTableThrow
                    ),                      
                   array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQtymagnet
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeTableThrow
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }
             else if($attribute_set_name =="sintra_pvc_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeSintra
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialSintra
                    ),                 
                      array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                       array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),
                   array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                  array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }
            else if($attribute_set_name =="vinyl_banner_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeVinylBanner                        
                    ),
                    array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialVinyl
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                     array(
                        "sort_order"    => 6,
                        "title"         => "Grommets",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionGroomets
                    ), 
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="falcon_board_set"){
                  $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeGatorBoard                        
                    ),
                    array(
                        "sort_order"    => 2,
                        "title"         => "Custom Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "field",
                        "is_require"    => 0                        
                    ),                   
                        array(
                        "sort_order"    => 3,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFalconBanner
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                     array(
                        "sort_order"    => 5,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),                    
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                   array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="gator_board_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeGatorBoard
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),                   
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialGatorBoard
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),
                    array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminate                
                    ),                         
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );
            }else if($attribute_set_name =="drawing_blueprints_set"){
                 $options = array(
                        array(
                        "sort_order"    => 2,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeDrawingBlueprints
                    ),
                        array(
                            "sort_order"    => 3,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                            "sort_order"    => 1,
                            "title"         => "Number Of Pages",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                        "sort_order"    => 4,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialDrawingBlueprints
                    ),
                    array(
                        "sort_order"    => 5,
                        "title"         => "Color",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionColor
                    ),                   
                    array(
                        "sort_order"    => 6,
                        "title"         => "Bindery",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionBinding
                    ),                          
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );  
            } else if($attribute_set_name =="backlit_film_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeBacklit
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialBacklitFilm
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),                   
                    array(
                        "sort_order"    => 4,
                        "title"         => "Laminate",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionLaminateRetracable                
                    ),                         
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );  
            }
            else if($attribute_set_name =="aluminium_signs_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeBacklit
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialAluminiumSigns
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSidesAluminiumSigns
                    ),                   
                    array(
                        "sort_order"    => 4,
                        "title"         => "Rounded Corner",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionRoundedCornerAluminiumSigns                
                    ),                         
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeFabric
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );  
            }
            else if($attribute_set_name =="floor_graphics_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeFloorGraphics
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialFloorGraphics
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),                                         
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTime
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );  
            }
            else if($attribute_set_name =="perforated_window_graphics_set"){
                 $options = array(
                        array(
                        "sort_order"    => 1,
                        "title"         => "Size",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionSizeFloorGraphics
                    ),
                        array(
                            "sort_order"    => 2,
                            "title"         => "Custom Size",
                            "price_type"    => "fixed",
                            "price"         => "",
                            "type"          => "field",
                            "is_require"    => 0                        
                        ),
                        array(
                        "sort_order"    => 2,
                        "title"         => "Material",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionMaterialPerforatedWindowGraphics
                    ),
                    array(
                        "sort_order"    => 3,
                        "title"         => "Printed Sides",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionPrintedSides
                    ),                                            
                    array(
                        "sort_order"    => 7,
                        "title"         => "Quantity",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $optionQuantity
                    ), 
                    array(
                        "sort_order"    => 8,
                        "title"         => "Production Time",
                        "price_type"    => "fixed",
                        "price"         => "",
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $productionTimeFabric
                    ), 
                    array(
                        "sort_order"    => 9,
                        "title"         => "Artwork",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "radio",
                        "is_require"    => 1,
                        "values"        => $artwork
                    ), 
                    array(
                        "sort_order"    => 10,
                        "title"         => "UPLOAD YOUR FILE",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "file",
                        "is_require"    => 0,
                        "file_extension" =>"pdf, jpg, jpeg, png, tiff, psd, eps, ai, doc., Ppt"
                    ), 
                     array(
                        "sort_order"    => 11,
                        "title"         => "Proofs",
                        "price_type"    => "fixed",
                        "price"         => 0,
                        "type"          => "drop_down",
                        "is_require"    => 1,
                        "values"        => $proofsOptions
                        
                    ), 
                );  
            }

            
       // echo '<pre>';print_r($options);die();
         //Compare to the old options to avoid repetition of options
        foreach ($options as $arrayOption) {
            $found = false;
            foreach ($optionarray as $key => $data) {                
                if ($data['title'] == $arrayOption['title'] && $data['title'] == $arrayOption['title']) {
                    $found = true;
                    break; 
                }
            }
            if ($found === false) {
                $product->setHasOptions(1);
                $product->getResource()->save($product);
                $option = $objectManager->create('\Magento\Catalog\Model\Product\Option')
                        ->setProductId($productId)
                        ->setStoreId($product->getStoreId())
                        ->addData($arrayOption);
                $option->save();
                $product->addOption($option);
            }          
            
        }
    } 
}