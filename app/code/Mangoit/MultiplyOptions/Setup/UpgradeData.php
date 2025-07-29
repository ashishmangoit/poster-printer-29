<?php
/**
 * Mangoit Collegewise Schema Setup.
 * @category    Mangoit
 * @package     Mangoit_Collegewise
 * @author      Mangoit Software Private Limited
 */
namespace Mangoit\MultiplyOptions\Setup;
 
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
  /**
     * @var SalesSetupFactory
     */
    protected $salesSetupFactory;

    /**
     * @param SalesSetupFactory $salesSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory) {
        $this->salesSetupFactory = $salesSetupFactory;
    }
   public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
     $setup->startSetup();
        /*if(version_compare($context->getVersion(), '1.0.1','<=')) {
           $setup->getConnection()->addColumn(
                      $setup->getTable('catalog_product_option_type_value'),
                      'is_default',
                      [
                          'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                          'default' => 0,
                          'nullable' => true,
                          'comment' => 'default sort order'
                      ]
           );
        } else*/
        if(version_compare($context->getVersion(), '1.0.1','<=')) {
           //Order table
            /** @var \Magento\Sales\Setup\SalesSetup $salesInstaller */
          $salesInstaller = $this->salesSetupFactory->create(['resourceName' => 'sales_setup', 'setup' => $setup]);
          $salesInstaller->addAttribute('order', 'delivery_date', ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length'=> 255, 'visible' => false,'nullable' => true,]);
          $setup->endSetup();
        }
   }
}