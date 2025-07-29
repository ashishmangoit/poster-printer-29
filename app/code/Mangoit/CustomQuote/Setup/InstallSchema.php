<?php

namespace Mangoit\CustomQuote\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0') < 0){

		$installer->run('create table custom_quote(quote_id int not null auto_increment, name varchar(100),email varchar(100),phone int,project_description varchar(500) ,pro_desc varchar(125) , size varchar(100), lamination varchar(100),turn_around_time varchar(100),quantity varchar(100),artwork varchar(100),instructions varchar(100),primary key(quote_id))');

		}

        $installer->endSetup();

    }
}