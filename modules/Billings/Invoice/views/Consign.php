<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */



class Invoice_Consign_View extends Inventory_Detail_View {

    public function process(Vtiger_Request $request) {

        $recordId = $request->get('record');
        $moduleName = $request->getModule();

        if (!$this->record) {
            $this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
        }

        $recordModel = $this->record->getRecord();
        $moduleModel = $recordModel->getModule();
        
        if (!$recordModel->getData()["prefix"] && !$recordModel->getData()["prefix"]) {

            $viewer = $this->getViewer($request);
            $viewer->assign('RECORD', $recordModel);
            //$viewer->assign('JSON', json_encode($recordModel->getJson()));
            //$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
            //$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
            //$viewer->assign('USER_MODEL', Invoice_Record_Model);
            //$viewer->assign('MODULE_NAME', $moduleName);
//$viewer->assign('RELATED_PRODUCTS', $recordModel->getProductsJson($request));

            return $viewer->view('ConsignView.tpl', $moduleName, false);
        } else
            return parent::process($request);
    }

    function preProcess(Vtiger_Request $request) {
        $viewer = $this->getViewer($request);
        $viewer->assign('NO_SUMMARY', true);
        parent::preProcess($request);
    }

    function getHeaderScripts(Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);

        $moduleName = $request->getModule();

//Added to remove the module specific js, as they depend on inventory files
        $modulePopUpFile = 'modules.' . $moduleName . '.resources.Popup';
        $moduleEditFile = 'modules.' . $moduleName . '.resources.Edit';
        $moduleDetailFile = 'modules.' . $moduleName . '.resources.Detail';
        unset($headerScriptInstances[$modulePopUpFile]);
        unset($headerScriptInstances[$moduleEditFile]);
        unset($headerScriptInstances[$moduleDetailFile]);

        $jsFileNames = array(
            'modules.Inventory.resources.Popup',
            'modules.Inventory.resources.Detail',
            'modules.Inventory.resources.Edit',
            "modules.$moduleName.resources.Consign",
        );
        $jsFileNames[] = $moduleEditFile;
        $jsFileNames[] = $modulePopUpFile;
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }

}
