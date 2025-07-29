<?php
/**
 * Mangoit_CartProduct
 * Copyright (C) 2019 Mangoit_CartProduct
 * 
 * This file is part of Mangoit/CartProduct.
 * 
 * Mangoit/CartProduct is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Mangoit\CartProduct\Controller\Index;

use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductFactory;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    protected $formKey;

    protected $request;

    protected $productRepository;

    private $cart;
    private $productFactory;

    private $serializer;

    protected $_resultRedirectFactory;
    protected $_url;
    protected $_serverTime;


    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Request\Http $request,
        Cart $cart, 
        ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\UrlInterface $url,
        \Mangoit\MultiplyOptions\Helper\ServerTime $serverTime


    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->formKey = $formKey;
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->_resultRedirectFactory = $resultRedirectFactory;
        $this->_url = $url;
        $this->_serverTime = $serverTime;

        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */

    public function execute()
    {   

        $allowedProducts = array(16, 33, 19, 22, 29, 20);

        $productId = $this->request->getParam('id');

        if(!isset($productId) || !in_array($productId, $allowedProducts) ){

            return $this->resultRedirectFactory->create()
            ->setPath('/*');
        }
        $sameDayShow = $this->_serverTime->sameDayShow($productId);

        /*echo 'Product Id :'.$productId;*/


        $posterPrintingOptionsKeys = array(15108, 15110, 15111, 15112, 15113, 15114, 15115, 15116, 15118);
        $posterPrintingOptionsValues = array(163461, 163466, 163467, 163468, 163471, 163476, 163627, 163631, 163632);


        $foamCorePostersOptionsKeys = array(15460, 15462, 15463, 15464, 15465, 15466, 15467, 15469);

        $foamCorePostersOptionsValues = array(168295, 168300, 168301, 168302, 168305, 168456, 168460, 168461);

        $styrenePostersOptionsKeys = array(15333, 15335, 15336, 15337, 15338, 15339, 15340, 15342 );

        $styrenePostersOptionsValues = array(166648, 166653, 166655, 166656, 166659, 166710, 166714, 166715);

        $coroplastPostersOptionsKeys = array(15354, 15356, 15357, 15358, 15359, 15360, 15361, 15363);

        $coroplastPostersOptionsValues = array(166896, 166901, 166902, 166903, 166906, 167057, 167061, 167062);

        $vinylBannersOptionsKeys = array(15418, 15420, 15421, 15422, 15423, 15424, 15425, 15427);

        $vinylBannersOptionsValues = array(167861, 167867, 167869, 167870, 167873, 168024, 168028, 168029);

        $adhesiveVinylOptionsKeys = array(15343, 15345, 15346, 15347, 15348, 15349, 15350, 15351, 15353);

        $adhesiveVinylOptionsValues = array(166718, 166725, 166728, 166729, 166732, 166735, 166886, 166890, 166891);

        if($sameDayShow == 'no'){

            $posterPrintingOptionsValues = array(163461, 163466, 163467, 163468, 163471, 163476, 163628, 163631, 163632);

            $foamCorePostersOptionsValues = array(168295, 168300, 168301, 168302, 168305, 168457, 168460, 168461);

            $styrenePostersOptionsValues = array(166648, 166653, 166655, 166656, 166659, 166711, 166714, 166715);

            $coroplastPostersOptionsValues = array(166896, 166901, 166902, 166903, 166906, 167058, 167061, 167062);

            $vinylBannersOptionsValues = array(167861, 167867, 167869, 167870, 167873, 168025, 168028, 168029);

            $adhesiveVinylOptionsValues = array(166718, 166725, 166728, 166729, 166732, 166735, 166887, 166890, 166891);

        }

        $product = $this->productRepository->getById($productId);


        /*foreach ($product->getOptions() as $o) {

            if(is_array($o->getValues())) {

            foreach ($o->getValues() as $value) {

                $options[$value['option_id']] = $value['option_type_id'];
 
            }

            }
        }*/
        /*print_r($options);*/

        /*die('Core options');*/


        if($productId == 16){
            $options = array_combine($posterPrintingOptionsKeys, $posterPrintingOptionsValues);
        } else if($productId == 33){

            $options = array_combine($foamCorePostersOptionsKeys, $foamCorePostersOptionsValues);
        } else if($productId == 19){

            $options = array_combine($styrenePostersOptionsKeys, $styrenePostersOptionsValues);
        } else if($productId == 22){

            $options = array_combine($coroplastPostersOptionsKeys, $coroplastPostersOptionsValues);
        } else if($productId == 29){

            $options = array_combine($vinylBannersOptionsKeys, $vinylBannersOptionsValues);
        } else if($productId == 20){

            $options = array_combine($adhesiveVinylOptionsKeys, $adhesiveVinylOptionsValues);
        }

        /*print_r($options);*/

        $params = array();
        $params['qty'] = 1;
        $params['product'] = $productId;        
        $params['options'] = $options;

        $this->cart->addProduct($product, $params);
        $this->cart->save();


        $RedirectUrl = $this->_url->getUrl('checkout/cart');

        /*$result = $this->resultRedirectFactory->create()
            ->setPath('checkout/cart');*/

        return $this->resultRedirectFactory->create()
            ->setPath('checkout/cart');

        die('Product added :'. $productId);
        
        return $this->resultPageFactory->create();
    }
}
