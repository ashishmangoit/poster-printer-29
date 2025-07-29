<?php 
namespace Mangoit\MultiplyOptions\Plugin;

class CustomOptionsUiPlugin
{
    public function afterModifyMeta(\Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions $subject,$meta) {

            $result['custom_options']['children']['options']['children']['record']['children']["container_option"]['children']['values']['children']['record']['children'] = [ 
                'is_default' => [
                    'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => __('Default'),
                                    'componentType' => 'field',
                                    'formElement' => 'checkbox',
                                    'dataScope' => 'is_default',
                                    'dataType' => 'number',
                                    'additionalClasses' => 'admin__field-small',
                                    'sortOrder' => 55,
                                    'value' => '0',
                                    'valueMap' => [
                                        'true' => '1',
                                        'false' => '0'
                                    ]
                                ]  
                            ]
                    ]
                ]
            ];

    return $result; 

    }

}