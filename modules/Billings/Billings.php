<?php

/* * *********************************************************************************************
 * * The contents of this file are subject to the Vtiger Module-Builder License Version 1.3
 * ( "License" ); You may not use this file except in compliance with the License
 * The Original Code is:  Technokrafts Labs Pvt Ltd
 * The Initial Developer of the Original Code is Technokrafts Labs Pvt Ltd.
 * Portions created by Technokrafts Labs Pvt Ltd are Copyright ( C ) Technokrafts Labs Pvt Ltd.
 * All Rights Reserved.
 * *
 * *********************************************************************************************** */

include_once 'vtlib/Vtiger/Module.php';
include_once 'vtlib/Vtiger/Package.php';
include_once 'includes/main/WebUI.php';
include_once 'include/Webservices/Utils.php';

include_once 'modules/Vtiger/CRMEntity.php';

class Billings extends Vtiger_CRMEntity {

    var $table_name = 'vtiger_billings';
    var $table_index = 'billingsid';

    /**
     * Mandatory table for supporting custom fields.
     */
    var $customFieldTable = Array('vtiger_billingscf', 'billingsid');

    /**
     * Mandatory for Saving, Include tables related to this module.
     */
    var $tab_name = Array('vtiger_crmentity', 'vtiger_billings', 'vtiger_billingscf');

    /**
     * Other Related Tables
     */
    var $related_tables = Array(
        'vtiger_billingscf' => Array('billingsid')
    );

    /**
     * Mandatory for Saving, Include tablename and tablekey columnname here.
     */
    var $tab_name_index = Array(
        'vtiger_crmentity' => 'crmid',
        'vtiger_billings' => 'billingsid',
        'vtiger_billingscf' => 'billingsid');

    /**
     * Mandatory for Listing (Related listview)
     */
    var $list_fields = Array(
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Billings No' => Array('billings', 'billingsno'),
        /* 'Billings No'=> Array('billings', 'billingsno'), */
        'Assigned To' => Array('crmentity', 'smownerid')
    );
    var $list_fields_name = Array(
        /* Format: Field Label => fieldname */
        'Billings No' => 'billingsno',
        /* 'Billings No'=> 'billingsno', */
        'Assigned To' => 'assigned_user_id'
    );
    // Make the field link to detail view
    var $list_link_field = 'billingsno';
    // For Popup listview and UI type support
    var $search_fields = Array(
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Billings No' => Array('billings', 'billingsno'),
        /* 'Billings No'=> Array('billings', 'billingsno'), */
        'Assigned To' => Array('vtiger_crmentity', 'assigned_user_id'),
    );
    var $search_fields_name = Array(
        /* Format: Field Label => fieldname */
        'Billings No' => 'billingsno',
        /* 'Billings No'=> 'billingsno', */
        'Assigned To' => 'assigned_user_id',
    );
    // For Popup window record selection
    var $popup_fields = Array('billingsno');
    // For Alphabetical search
    var $def_basicsearch_col = 'billingsno';
    // Column value to use on detail view record text display
    var $def_detailview_recname = 'billingsno';
    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    var $mandatory_fields = Array('billingsno', 'assigned_user_id');
    var $default_order_by = 'billingsno';
    var $default_sort_order = 'ASC';

    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type
     */
    function vtlib_handler($moduleName, $eventType) {
        global $adb;
        if ($eventType == 'module.postinstall') {
            // TODO Handle actions after this module is installed.
            Billings::checkWebServiceEntry();
            Billings::moveInvoicePatch($moduleName);
            Billings::appendNewFields($moduleName);
            Billings::createViews($moduleName);
            Billings::createUserFieldTable($moduleName);
            Billings::addInTabMenu($moduleName);
        } else if ($eventType == 'module.disabled') {
            Billings::appendNewFields($moduleName);
            // TODO Handle actions before this module is being uninstalled.
        } else if ($eventType == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } else if ($eventType == 'module.preupdate') {
            //Billings::moveInvoicePatch($moduleName);
            //Billings::appendNewFields($moduleName);
            // TODO Handle actions before this module is updated.
        } else if ($eventType == 'module.postupdate') {
            // TODO Handle actions after this module is updated.
            Billings::checkWebServiceEntry();
        }
    }

    /*
     * Function to handle module specific operations when saving a entity
     */

    function createViews($module) {
        global $adb;
            $q ="CREATE VIEW `vtiger_view_invoice` AS
    SELECT 
        `vtiger_invoice`.`invoiceid` AS `id`,
        NULL AS `number`,
        NULL AS `prefix`,
        NULL AS `resolution_number`,
        '01' AS `document_type_code`,
        '10' AS `operation_type_code`,
        CAST(CURRENT_TIMESTAMP() AS DATE) AS `date`,
        CAST(CURRENT_TIMESTAMP() AS TIME) AS `time`,
        'COP' AS `currency_type_code`,
        `vtiger_account`.`siccode` AS `identification_number`,
        `vtiger_accountscf`.`dv` AS `dv`,
        `vtiger_account`.`accountname` AS `name`,
        `vtiger_account`.`phone` AS `phone`,
        CONCAT(`vtiger_invoicebillads`.`bill_street`,
                ',',
                `vtiger_invoicebillads`.`bill_city`,
                ' - ',
                `vtiger_invoicebillads`.`bill_state`,
                ', ',
                `vtiger_invoicebillads`.`bill_code`,
                ', ',
                `vtiger_invoicebillads`.`bill_country`,
                ' ',
                `vtiger_invoicebillads`.`bill_pobox`) AS `address`,
        `vtiger_account`.`email1` AS `email`,
        `vtiger_accountscf`.`merchant_registration` AS `merchant_registration`,
        `vtiger_accountscf`.`identification_type_code` AS `identification_type_code`,
        'es' AS `language_code`,
        '1' AS `organization_type_code`,
        'CO' AS `country_code`,
        `vtiger_municipality`.`number` AS `municipality_code`,
        `vtiger_accountscf`.`regime_type_code` AS `regime_type_code`,
        `vtiger_accountscf`.`tax_code` AS `tax_code`,
        `vtiger_accountscf`.`liability_type_code` AS `liability_type_code`,
        `vtiger_invoice`.`subtotal` AS `line_extension_amount`,
        NULL AS `tax_exclusive_amount`,
        NULL AS `tax_inclusive_amount`,
        `vtiger_invoice`.`adjustment` AS `allowance_total_amount`,
        `vtiger_invoice`.`s_h_amount` AS `charge_total_amount`,
        `vtiger_invoice`.`subtotal` AS `payable_amount`
    FROM
        ((((((`vtiger_invoice`
        LEFT JOIN `vtiger_account` ON (`vtiger_account`.`accountid` = `vtiger_invoice`.`accountid`))
        LEFT JOIN `vtiger_invoicecf` ON (`vtiger_invoicecf`.`invoiceid` = `vtiger_invoice`.`invoiceid`))
        LEFT JOIN `vtiger_accountscf` ON (`vtiger_accountscf`.`accountid` = `vtiger_invoice`.`accountid`))
        LEFT JOIN `vtiger_municipality` ON (`vtiger_municipality`.`municipalityid` = `vtiger_accountscf`.`municipality`))
        LEFT JOIN `vtiger_department` ON (`vtiger_department`.`departmentid` = `vtiger_accountscf`.`department`))
        LEFT JOIN `vtiger_invoicebillads` ON (`vtiger_invoicebillads`.`invoicebilladdressid` = `vtiger_invoice`.`invoiceid`))";
            $adb->pquery($q1, array());
            
            $q ="CREATE VIEW `vtiger_view_products` AS
    SELECT 
        `vtiger_inventoryproductrel`.`id` AS `id`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_productcf`.`unit_measure_code`
            ELSE `vtiger_servicecf`.`unit_measure_code`
        END AS `unit_measure_code`,
        0 AS `free_of_charge`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_products`.`productname`
            ELSE `vtiger_service`.`servicename`
        END AS `description`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_products`.`product_no`
            ELSE `vtiger_service`.`service_no`
        END AS `code`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_productcf`.`item_identification_type_code`
            ELSE `vtiger_servicecf`.`item_identification_type_code`
        END AS `item_identification_type_code`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_products`.`unit_price`
            ELSE `vtiger_service`.`unit_price`
        END AS `price_amount`,
        `vtiger_inventoryproductrel`.`margin` AS `line_extension_amount`,
        `vtiger_inventoryproductrel`.`quantity` AS `invoiced_quantity`,
        CASE
            WHEN `vtiger_products`.`productid` <> '' THEN `vtiger_products`.`qty_per_unit`
            ELSE `vtiger_service`.`qty_per_unit`
        END AS `base_quantity`
    FROM
        (((((`vtiger_inventoryproductrel`
        LEFT JOIN `vtiger_crmentity` ON (`vtiger_crmentity`.`crmid` = `vtiger_inventoryproductrel`.`productid`))
        LEFT JOIN `vtiger_products` ON (`vtiger_products`.`productid` = `vtiger_inventoryproductrel`.`productid`))
        LEFT JOIN `vtiger_productcf` ON (`vtiger_productcf`.`productid` = `vtiger_inventoryproductrel`.`productid`))
        LEFT JOIN `vtiger_service` ON (`vtiger_service`.`serviceid` = `vtiger_inventoryproductrel`.`productid`))
        LEFT JOIN `vtiger_servicecf` ON (`vtiger_servicecf`.`serviceid` = `vtiger_inventoryproductrel`.`productid`))
    ORDER BY `vtiger_inventoryproductrel`.`sequence_no`";
            $adb->pquery($q1, array());
    }
    function save_module($module) {
        global $adb;
        $q = 'SELECT ' . $this->def_detailview_recname . ' FROM ' . $this->table_name . ' WHERE ' . $this->table_index . ' = ' . $this->id;

        $result = $adb->pquery($q, array());
        $cnt = $adb->num_rows($result);
        if ($cnt > 0) {
            $label = $adb->query_result($result, 0, $this->def_detailview_recname);
            $q1 = 'UPDATE vtiger_crmentity SET label = \'' . $label . '\' WHERE crmid = ' . $this->id;
            $adb->pquery($q1, array());
        }
    }

    /**
     * Function to check if entry exsist in webservices if not then enter the entry
     */
    static function checkWebServiceEntry() {
        global $log;
        $log->debug("Entering checkWebServiceEntry() method....");
        global $adb;

        $sql = "SELECT count(id) AS cnt FROM vtiger_ws_entity WHERE name = 'Billings'";
        $result = $adb->query($sql);
        if ($adb->num_rows($result) > 0) {
            $no = $adb->query_result($result, 0, 'cnt');
            if ($no == 0) {
                $tabid = $adb->getUniqueID("vtiger_ws_entity");
                $ws_entitySql = "INSERT INTO vtiger_ws_entity ( id, name, handler_path, handler_class, ismodule ) VALUES" .
                        " (?, 'Billings','include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation' , 1)";
                $res = $adb->pquery($ws_entitySql, array($tabid));
                $log->debug("Entered Record in vtiger WS entity ");
            }
        }
        $log->debug("Exiting checkWebServiceEntry() method....");
    }

    static function createUserFieldTable($module) {
        global $log;
        $log->debug("Entering createUserFieldTable() method....");
        global $adb;

        $sql = "CREATE TABLE IF NOT EXISTS `vtiger_" . $module . "_user_field` (
  						`recordid` int(19) NOT NULL,
					  	`userid` int(19) NOT NULL,
  						`starred` varchar(100) DEFAULT NULL,
  						 KEY `record_user_idx` (`recordid`,`userid`)
						) 			
						ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $result = $adb->pquery($sql, array());
    }

    static function addInTabMenu($module) {
        global $log;
        $log->debug("Entering addInTabMenu() method....");
        global $adb;
        $gettabid = $adb->pquery("SELECT tabid,parent FROM vtiger_tab WHERE name = ?", array($module));
        $tabid = $adb->query_result($gettabid, 0, 'tabid');
        $parent = $adb->query_result($gettabid, 0, 'parent');
        $parent = strtoupper($parent);

        $getmaxseq = $adb->pquery("SELECT max(sequence)+ 1 as maxseq FROM vtiger_app2tab WHERE appname = ?", array($parent));
        $sequence = $adb->query_result($getmaxseq, 0, 'maxseq');

        $sql = "INSERT INTO `vtiger_app2tab` (`tabid` ,`appname` ,`sequence` ,`visible`)VALUES (?, ?, ?, ?)";
        $result = $adb->pquery($sql, array($tabid, $parent, $sequence, 1));
    }

    static function appendNewFields($mod) {
        global $log;
        $log->debug("Entering addInTabMenu() method....");
        global $adb;


        Billings::crearModulo('Country');

        $module = Vtiger_Module::getInstance('Country');
        $module->save();
        $module->initWebservice();
        $module->initTables();
        $module->setDefaultSharing(Public_ReadOnly);

        $block1 = new Vtiger_Block();
        $block1->label = 'LBL_TAXONOMY_INFORMATION';
        $module->addBlock($block1);
        $block2 = new Vtiger_Block();
        $block2->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($block2);


        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'alfa2';
        $fieldInstance->label = 'Alfa 2';
        $fieldInstance->column = 'alfa2';
        $fieldInstance->table = 'vtiger_country';
        $fieldInstance->columntype = 'VARCHAR(2)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block1->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'alfa3';
        $fieldInstance->label = 'Alfa 3';
        $fieldInstance->column = 'alfa3';
        $fieldInstance->table = 'vtiger_country';
        $fieldInstance->columntype = 'VARCHAR(3)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block1->addField($fieldInstance);


        /*****************************************
         *  Departamento
         ****************************************/
        Billings::crearModulo('Department');
        $module = Vtiger_Module::getInstance('Department');
        $module->save();
        $module->initWebservice();
        $module->initTables();
        $module->setDefaultSharing(Public_ReadOnly);

        $block1 = new Vtiger_Block();
        $block1->label = 'LBL_TAXONOMY_INFORMATION';
        $module->addBlock($block1);
        $block2 = new Vtiger_Block();
        $block2->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($block2);

        $field1 = new Vtiger_Field();
        $field1->label = 'Number';
        $field1->name = 'number';
        $field1->column = 'number';
        $field1->table = 'vtiger_department';
        $field1->columntype = 'VARCHAR(50)';
        $field1->uitype = 2;
        $field1->typeofdata = 'V~M';
        $block1->addField($field1);


        $field4 = new Vtiger_Field();
        $field4->name = 'countryid';
        $field4->label = 'Country';
        $field4->table = 'vtiger_department';
        $field4->column = 'countryid';
        $field4->columntype = 'VARCHAR(100)';
        $field4->uitype = 10;
        $field4->typeofdata = 'V~M';
        $field4->helpinfo = 'Relate to an existing Country';
        $block1->addField($field4);
        $field4->setRelatedModules(Array('Country'));


        /*****************************************
         *  Municipality
         ****************************************/
        Billings::crearModulo('Municipality');
        $module = Vtiger_Module::getInstance('Municipality');
        $module->save();
        $module->initWebservice();
        $module->initTables();
        $module->setDefaultSharing(Public_ReadOnly);

        $block1 = new Vtiger_Block();
        $block1->label = 'LBL_TAXONOMY_INFORMATION';
        $module->addBlock($block1);
        $block2 = new Vtiger_Block();
        $block2->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($block2);

        $field1 = new Vtiger_Field();
        $field1->label = 'Number';
        $field1->name = 'number';
        $field1->column = 'number';
        $field1->table = 'vtiger_municipality';
        $field1->columntype = 'VARCHAR(50)';
        $field1->uitype = 2;
        $field1->typeofdata = 'V~M';
        $block1->addField($field1);


        $field4 = new Vtiger_Field();
        $field4->name = 'departmentid';
        $field4->label = 'Department';
        $field4->table = 'vtiger_municipality';
        $field4->column = 'departmentid';
        $field4->columntype = 'VARCHAR(100)';
        $field4->uitype = 10;
        $field4->typeofdata = 'V~M';
        $field4->helpinfo = 'Relate to an existing Department';
        $block1->addField($field4);
        $field4->setRelatedModules(Array('Department'));



        // invoice params
        $module = Vtiger_Module::getInstance('Invoice');

        $module->initTables();

        $block = new Vtiger_Block();
        $block->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($block);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'prefix';
        $fieldInstance->table = 'vtiger_invoicecf';
        $fieldInstance->column = 'prefix';
        $fieldInstance->label = 'Prefix';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        
        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'resolution';
        $fieldInstance->table = 'vtiger_invoicecf';
        $fieldInstance->column = 'resolution';
        $fieldInstance->label = 'Resolution';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        // organization account
        $module = Vtiger_Module::getInstance('Accounts');
        $module->initTables();
        $block = new Vtiger_Block();
        $block->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($block);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'merchant_registration';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'merchant_registration';
        $fieldInstance->label = 'Merchant Registration';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'identification_type_code';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'identification_type_code';
        $fieldInstance->label = 'identification_type_code';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        
        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'dv';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'dv';
        $fieldInstance->label = 'dv';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        
        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'regime_type_code';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'regime_type_code';
        $fieldInstance->label = 'regime_type_code';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'tax_code';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'tax_code';
        $fieldInstance->label = 'tax_code';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'liability_type_code';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = 'liability_type_code';
        $fieldInstance->label = 'liability_type_code';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->label = 'Department';
        $fieldInstance->name = 'department';
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->column = $fieldInstance->name;
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 10;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        $fieldInstance->setRelatedModules(Array('Department'));

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->label = 'Municipality';
        $fieldInstance->name = 'municipality';
        $fieldInstance->column = $fieldInstance->name;
        $fieldInstance->table = 'vtiger_accountscf';
        $fieldInstance->columntype = 'VARCHAR(100)';
        $fieldInstance->uitype = 10;
        $fieldInstance->typeofdata = 'V~O';
        $block->addField($fieldInstance);
        $fieldInstance->setRelatedModules(Array('Municipality'));

        
        $module = Vtiger_Module::getInstance('Products');

        $module->initTables();

        $blockInstance = new Vtiger_Block();
        $blockInstance->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($blockInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'free_of_charge';
        $fieldInstance->table = 'vtiger_productcf';
        $fieldInstance->column = 'free_of_charge';
        $fieldInstance->label = 'Free of charge';
        $fieldInstance->columntype = 'VARCHAR(3)';
        $fieldInstance->uitype = 56;
        $fieldInstance->typeofdata = 'C~O';
        $blockInstance->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'unit_measure_code';
        $fieldInstance->table = 'vtiger_productcf';
        $fieldInstance->column = 'unit_measure_code';
        $fieldInstance->label = 'Unit measure';
        $fieldInstance->columntype = 'Int (11)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'I~O~LE~11';
        $blockInstance->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'item_identification_type_code';
        $fieldInstance->table = 'vtiger_productcf';
        $fieldInstance->column = 'item_identification_type_code';
        $fieldInstance->label = 'Item identification type';
        $fieldInstance->columntype = 'Int(11)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'I~O~LE~11';
        $blockInstance->addField($fieldInstance);

        // Fields on Services
        $module = Vtiger_Module::getInstance('Services');
        $module->initTables();

        $blockInstance = new Vtiger_Block();
        $blockInstance->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($blockInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'free_of_charge';
        $fieldInstance->table = 'vtiger_servicecf';
        $fieldInstance->column = 'free_of_charge';
        $fieldInstance->label = 'Free of charge';
        $fieldInstance->columntype = 'VARCHAR(3)';
        $fieldInstance->uitype = 56;
        $fieldInstance->typeofdata = 'C~O';
        $blockInstance->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'unit_measure_code';
        $fieldInstance->table = 'vtiger_servicecf';
        $fieldInstance->column = 'unit_measure_code';
        $fieldInstance->label = 'Unit measure';
        $fieldInstance->columntype = 'Int (11)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'I~O~LE~11';
        $blockInstance->addField($fieldInstance);

        $fieldInstance = new Vtiger_Field();
        $fieldInstance->name = 'item_identification_type_code';
        $fieldInstance->table = 'vtiger_servicecf';
        $fieldInstance->column = 'item_identification_type_code';
        $fieldInstance->label = 'Item identification type';
        $fieldInstance->columntype = 'Int(11)';
        $fieldInstance->uitype = 2;
        $fieldInstance->typeofdata = 'I~O~LE~11';
        $blockInstance->addField($fieldInstance);
        error_log("OK");
    }

    static function moveInvoicePatch($module) {
        global $log;
        $log->debug("Entering addInTabMenu() method....");
        global $adb;
        if (file_exists('modules/Billings/Invoice/views/Consign.php')) {
            rename("modules/Billings/Invoice/views/Consign.php", "modules/Invoice/views/Consign.php");
        }
        if (file_exists('modules/Billings/Invoice/actions/Consign.php')) {
            rename("modules/Billings/Invoice/actions/Consign.php", "modules/Invoice/actions/Consign.php");
        }
        if (file_exists('modules/Billings/Invoice/models/Record.php')) {
            rename("modules/Billings/Invoice/models/Record.php", "modules/Invoice/models/Record.php");
        }
        if (is_dir('modules/Billings/Invoice')) {
            rmdir("modules/Billings/Invoice");
        }
    }
    static function crearModulo($modulo, $parent='Tools', $name='name', $label='Name'){
    $moduleInformation['name'] = $modulo;
    $moduleInformation['entityfieldlabel'] = $label;
    $moduleInformation['entityfieldname']  = $name;
    $moduleInformation['parent'] = $parent;


    $module = new Vtiger_Module();
    $module->name = $moduleInformation['name'];
    $module->parent=$moduleInformation['parent'];
    $module->save();

    $module->initTables();

    $block = new Vtiger_Block();
    $block->label = 'LBL_'. strtoupper($module->name) . '_INFORMATION';
    $module->addBlock($block);

    $blockcf = new Vtiger_Block();
    $blockcf->label = 'LBL_CUSTOM_INFORMATION';
    $module->addBlock($blockcf);

    $field1  = new Vtiger_Field();
    $field1->name = $moduleInformation['entityfieldname'];
    $field1->label= $moduleInformation['entityfieldlabel'];
    $field1->uitype= 2;
    $field1->column = $field1->name;
    $field1->columntype = 'VARCHAR(255)';
    $field1->typeofdata = 'V~M';
    $block->addField($field1);

    $module->setEntityIdentifier($field1);

    /** Common fields that should be in every module, linked to vtiger CRM core table */
    $field2 = new Vtiger_Field();
    $field2->name = 'assigned_user_id';
    $field2->label = 'Assigned To';
    $field2->table = 'vtiger_crmentity';
    $field2->column = 'smownerid';
    $field2->uitype = 53;
    $field2->typeofdata = 'V~M';
    $block->addField($field2);

    $field3 = new Vtiger_Field();
    $field3->name = 'createdtime';
    $field3->label= 'Created Time';
    $field3->table = 'vtiger_crmentity';
    $field3->column = 'createdtime';
    $field3->uitype = 70;
    $field3->typeofdata = 'T~O';
    $field3->displaytype= 2;
    $block->addField($field3);

    $field4 = new Vtiger_Field();
    $field4->name = 'modifiedtime';
    $field4->label= 'Modified Time';
    $field4->table = 'vtiger_crmentity';
    $field4->column = 'modifiedtime';
    $field4->uitype = 70;
    $field4->typeofdata = 'T~O';
    $field4->displaytype= 2;
    $block->addField($field4);

// Create default custom filter (mandatory)
    $filter1 = new Vtiger_Filter();
    $filter1->name = 'All';
    $filter1->isdefault = true;
    $module->addFilter($filter1);
// Add fields to the filter created
    $filter1->addField($field1)->addField($field2, 1)->addField($field3, 2);

// Set sharing access of this module
    $module->setDefaultSharing();

// Enable and Disable available tools
    $module->enableTools(Array('Import', 'Export', 'Merge'));

// Initialize Webservice support
    $module->initWebservice();
    $entityField=$field1;

    $targetpath = 'modules/' . $module->name;

    if (!is_file($targetpath)) {
        mkdir($targetpath);
        mkdir($targetpath . '/language');

        $templatepath = 'vtlib/ModuleDir/6.0.0';

        $moduleFileContents = file_get_contents($templatepath . '/ModuleName.php');
        $replacevars = array(
            'ModuleName'   => $module->name,
            '<modulename>' => strtolower($module->name),
            '<entityfieldlabel>' => $entityField->label,
            '<entitycolumn>' => $entityField->column,
            '<entityfieldname>' => $entityField->name,
        );

        foreach ($replacevars as $key => $value) {
            $moduleFileContents = str_replace($key, $value, $moduleFileContents);
        }
file_put_contents($targetpath.'/'.$module->name.'.php', $moduleFileContents);
}
// Link to menu
Settings_MenuEditor_Module_Model::addModuleToApp($module->name, $module->parent);
}
}
