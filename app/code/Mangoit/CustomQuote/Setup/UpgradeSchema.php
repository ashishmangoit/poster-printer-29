<?php
/**
 * Mangoit Collegewise Schema Setup.
 * @category    Mangoit
 * @package     Mangoit_Collegewise
 * @author      Mangoit Software Private Limited
 */
namespace Mangoit\CustomQuote\Setup;
 

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
 public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
 {
   $setup->startSetup();
  
   
      if(version_compare($context->getVersion(), '1.0.1','<=')) {
         $setup->getConnection()->addColumn(
                    $setup->getTable('custom_quote'),
                    'instructions',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'default' => 0,
                        'nullable' => true,
                        'comment' => 'custom quote'
                    ]
         );
      }
 }
}