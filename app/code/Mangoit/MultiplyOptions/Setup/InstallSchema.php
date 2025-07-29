<?php 
namespace Mangoit\MultiplyOptions\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
 
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
 
        $table = $setup->getConnection()->newTable(
            $setup->getTable('production_time')
        )
        ->addColumn(
           'id',
           \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
           null,
           ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
           'id'
       )
        ->addColumn(
            'times',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'production times'
        )
         ->addColumn(
            'attribute_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Attribute Name'
        )->addColumn(
            'mon_fri_12_01_am_01_0_pm',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'mon fri 12 am to 1pm'
        )->addColumn(
            'mon_fri_01_01_pm_12_0_am',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'mon fri 12 am to 1pm'
        )->addColumn(
            'sat_mon_12_01_am_12_0_pm',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'sat mon 12 am to 12pm'
        )

        ->setComment(
            'Custom Table'
        );        
        $setup->getConnection()->createTable($table);     
         $table1 = $setup->getConnection()->newTable(
            $setup->getTable('quantity_discount')
        )
        ->addColumn(
           'id',
           \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
           null,
           ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
           'id'
       )
         ->addColumn(
            'attribute_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'Attribute Name'
        )
        ->addColumn(
            'limit',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'quantity limit'
        )
        ->addColumn(
            'discount',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            [],
            'quantity discount'
        )
        ->setComment(
            'quantity discount'
        );        
        $setup->getConnection()->createTable($table1);        
        $setup->endSetup();
    }    
}