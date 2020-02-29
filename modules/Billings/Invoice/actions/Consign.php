<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Invoice_Consign_Action extends Inventory_Save_Action {

    public function saveRecord($request) {
        $recordModel = $this->getRecordModelFromRequest($request);
        $recordId = $request->get('record');
        $moduleName = $request->getModule();

        if (!$recordModel->getFactura()["prefix"] && !$recordModel->getFactura()["resolution"]) {
            $json = getJsonInvoice($recordId, $recordModel->getProducts());
            $resolution = $request->get('resolution');
            $prefix = $request->get('prefix');

            $serial = $recordModel->getSerial($resolution, $prefix);
            $json["number"] = $serial;
            $json["prefix"] = $prefix;
            $json["resolution_number"] = $resolution;
            $json["customer"]["dv"] = $this->getDV($json["customer"]["identification_number"]);

            $result = $recordModel->affectDian($json);
            
            if ($result->status == "success") {
                $recordModel->consignInvoice($recordModel->getId(), $resolution, $prefix);
                $this->savedRecordId = $recordModel->getId();

                $_REQUEST['action'] = "edit";
            } else {
                
                $_REQUEST['action'] =$request->get('action');
                
                //print_r($result);
                echo '<html><head><meta http-equiv="refresh" content="5;URL=./index.php?module=Invoice&relatedModule=Invoice&view=Consign&record='.$recordModel->getId().'&tab_label=Assets&app=MARKETING" /></head><body>';
                echo "<h2>Error ". $result->document->statusCode.": ". $result->message."</h2><ul>";
                echo "<h2>". $result->document->statusDescription."</h2><ul>";
                foreach($result->document->errors as $errores){
                    echo "<li>". $errores."</li>";
                }
                echo "</ul>";
                echo "</body></html>";
                exit;
            }
        }

        return $recordModel;
    }
function getDV($nit) {
    $multiplier = [
        1 => 3,
        2 => 7,
        3 => 13,
        4 => 17,
        5 => 19,
        6 => 23,
        7 => 29,
        8 => 37,
        9 => 41,
        10 => 43,
        11 => 47,
        12 => 53,
        13 => 59,
        14 => 67,
        15 => 71,
    ];
        $sum = 0;
        $nitarray=str_split($nit);
        foreach($nitarray as $key => $value) {
            $sum += ($value * $multiplier[(count($nitarray) - $key)]);
        }

        if (($mod = ($sum % 11)) > 1) {
            return (11 - $mod);
        }

        return $mod;
    }
    private function getSerial($id) {

        global $log;
        global $adb;
        global $theme, $current_user;
    }

}
