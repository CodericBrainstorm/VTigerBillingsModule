<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Inventory Record Model Class
 */
class Invoice_Record_Model extends Inventory_Record_Model {

    public function getCreatePurchaseOrderUrl() {
        $purchaseOrderModuleModel = Vtiger_Module_Model::getInstance('PurchaseOrder');
        return "index.php?module=" . $purchaseOrderModuleModel->getName() . "&view=" . $purchaseOrderModuleModel->getEditViewName() . "&invoice_id=" . $this->getId();
    }

    function getResolutions() {
        global $log;
        global $adb;
        global $theme, $current_user;
        $query = "SELECT 
        `resolutionid`,
         `resolutionno`,
         `resolution_tks_resolution`,
         `resolution_tks_prefix`,
         `resolution_tks_serial`,
         `resolution_tks_from`,
         `resolution_tks_to`,
         `resolution_tks_expedition`,
         `resolution_tks_validity`,
         `resolution_tks_type`,
         `resolution_tks_next`,
         `tags`
          FROM `vtiger_view_resolution`;";
        $result = $adb->pquery($query);
        $num_rows = $adb->num_rows($result);

        $res = array();

        for ($i = 1; $i <= $num_rows; $i++) {

            $res[] = array(
                "resolutionid" => $adb->query_result($result, $i - 1, 'resolutionid'),
                "resolutionno" => $adb->query_result($result, $i - 1, 'resolutionno'),
                "resolution_tks_resolution" => $adb->query_result($result, $i - 1, 'resolution_tks_resolution'),
                "resolution_tks_prefix" => $adb->query_result($result, $i - 1, 'resolution_tks_prefix'),
                "resolution_tks_serial" => $adb->query_result($result, $i - 1, 'resolution_tks_serial'),
                "resolution_tks_from" => $adb->query_result($result, $i - 1, 'resolution_tks_from'),
                "resolution_tks_to" => $adb->query_result($result, $i - 1, 'resolution_tks_to'),
                "resolution_tks_expedition" => $adb->query_result($result, $i - 1, 'resolution_tks_expedition'),
                "resolution_tks_validity" => $adb->query_result($result, $i - 1, 'resolution_tks_validity'),
                "resolution_tks_type" => $adb->query_result($result, $i - 1, 'resolution_tks_type'),
                "resolution_tks_next" => $adb->query_result($result, $i - 1, 'resolution_tks_next')
            );
        }
        return $res;
    }

    function getFactura() {
        $factura_data = $this->getData();
        $id = $this->getId();
        $factura = getJsonInvoice($id, $this->getProducts());
        //$factura["base"]=$factura_data;
        //$factura["base"]=$factura_data["account_id"];
        //$numOfCurrencyDecimalPlaces = getCurrencyDecimalPlaces();
        //$factura["customer"] = getJsonCustomer($id, $factura_data["account_id"]);
        //$factura["invoice_lines"] = getJsonProducts($id, $this->getProducts());
        return $factura;
    }

    public function getDianValue($case, $index) {
        $json = $this->getDianList($case);
        $items = json_decode($json, true);
        return $items[$index];
    }

    static public function getDV($nit) {
        $dv = new DV($nit);
        return $dv->getDV();
    }

    public function getDianList($case) {
        $cache_file = "test/" . $case . ".json";
        $url = "https://endpoint.emision.co/dian-tables/" . $case;

        if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 2592000))) {
            // Cache file is less than five minutes old. 
            // Don't bother refreshing, just use the file as-is.
            $file = file_get_contents($cache_file);
        } else {
            // Our cache is out-of-date, so load the data from our remote server,
            // and also save it over our cache for next time.
            $file = file_get_contents($url);
            file_put_contents($cache_file, $file, LOCK_EX);
        }
        return $file;
    }

    public function getSerial($res, $prefix) {
        global $log;
        global $adb;
        global $theme, $current_user;
        $query = "SELECT get_serial_resolution(?, ?, 'Invoices') as serial";
        $result = $adb->pquery($query, array($res, $prefix));
        return $adb->query_result($result, 0, 'serial');
    }

    public function consignInvoice($id, $res, $prefix) {
        global $log;
        global $adb;
        global $theme, $current_user;

        $serial = $this->affectSerial($res, $prefix);

        $query = "UPDATE `vtiger_invoice`
LEFT JOIN `vtiger_invoicecf` ON `vtiger_invoice`.`invoiceid`=`vtiger_invoicecf`.`invoiceid`
                    SET
                    `prefix` = ?,
                        `resolution` = ?,
                        `invoice_no` = ?
                        WHERE  `vtiger_invoice`.`invoiceid` = ?;";
        $result = $adb->pquery($query, array($prefix, $res, $serial, $id));
        
    }

    public function affectSerial($res, $prefix) {
        global $log;
        global $adb;
        global $theme, $current_user;
        $query = "SELECT serial_resolution(?, ?, 'Invoices') as serial";
        $result = $adb->pquery($query, array($res, $prefix));
        return $adb->query_result($result, 0, 'serial');
    }

    public function affectDian($json) {
        global $log;
        global $adb;
        global $theme, $current_user;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://test.endpoint.emision.co/api/v1/service/invoice",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($json),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer 6w0rprnf0ZUPRGVEnzXx7P1yssVi5Zl0GNAXF9Yfc2ZoQjZJeHJnQNTboqCjCQXtUgW7bCAmUXBNPC5m"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        if ($result->status == "success") {
            //file_put_contents("test/facturas/" . $result->document->number . ".pdf", base64_decode($result->document->pdfBase64Bytes));
        } elseif ($result->status == "error") {
            //print_r($result);
        }
        return $result;
    }
    
}

function getJsonInvoice($id = '', $related) {
    global $log;
    global $adb;
    $output = '';
    global $theme, $current_user;
    $query = "SELECT * FROM vtiger_view_invoice WHERE id=?";
    $params = array($id);
    //echo $query;

    $result = $adb->pquery($query, $params);
    // Invoice
    $invoice = array(
        "number" => $adb->query_result($result, 0, 'number'),
        "prefix" => $adb->query_result($result, 0, 'prefix'),
        "document_type_code" => $adb->query_result($result, 0, 'document_type_code'),
        "operation_type_code" => $adb->query_result($result, 0, 'operation_type_code'),
        "resolution_number" => $adb->query_result($result, 0, 'resolution_number'),
        "send" => true,
        "date" => $adb->query_result($result, 0, 'date'),
        "time" => $adb->query_result($result, 0, 'time'),
        "currency_type_code" => $adb->query_result($result, 0, 'currency_type_code')
    );
    // Customer
    $invoice["customer"] = array(
        "identification_number" => $adb->query_result($result, 0, 'identification_number'),
        "dv" => $adb->query_result($result, 0, 'dv'),
        "name" => $adb->query_result($result, 0, 'name'),
        "phone" => $adb->query_result($result, 0, 'phone'),
        "address" => $adb->query_result($result, 0, 'address'),
        "email" => $adb->query_result($result, 0, 'email'),
        "merchant_registration" => $adb->query_result($result, 0, 'merchant_registration'),
        "identification_type_code" => $adb->query_result($result, 0, 'identification_type_code'),
        "language_code" => $adb->query_result($result, 0, 'language_code'),
        "organization_type_code" => $adb->query_result($result, 0, 'organization_type_code'),
        "country_code" => $adb->query_result($result, 0, 'country_code'),
        "municipality_code" => $adb->query_result($result, 0, 'municipality_code'),
        "regime_type_code" => $adb->query_result($result, 0, 'regime_type_code'),
        "tax_code" => $adb->query_result($result, 0, 'tax_code'),
        "liability_type_code" => $adb->query_result($result, 0, 'liability_type_code')
    );
    $invoice["invoice_lines"] = getJsonProducts($id, $related);
    $taxs = array();
    $line_extension_amount = 0;
    $tax_exclusive_amount = 0;
    $tax_inclusive_amount = 0;
    foreach ($invoice["invoice_lines"] AS $prod) {
        $line_extension_amount += $prod["line_extension_amount"];
        $tax_exclusive_amount += $prod["tax_totals"][0]["taxable_amount"];
        $tax_inclusive_amount += $prod["tax_totals"][0]["tax_amount"];
        $taxs[] = $prod["tax_totals"][0];
    }
    $invoice["tax_totals"] = $taxs;

    //legal_monetary_totals
    $invoice["payment_form"] = array(
        "payment_form_code" => "1",
        "payment_method_code" => "10"
    );
    $invoice["legal_monetary_totals"] = array(
        "line_extension_amount" => $line_extension_amount,
        "tax_exclusive_amount" => $tax_exclusive_amount,
        "tax_inclusive_amount" => $tax_inclusive_amount + $tax_exclusive_amount,
        "allowance_total_amount" => 0,
        "charge_total_amount" => 0,
        "payable_amount" => $tax_inclusive_amount + $tax_exclusive_amount
    );
    return $invoice;
}

function getJsonProducts($id = '', $related) {
    global $log;
    global $adb;
    $output = '';
    global $theme, $current_user;

    $query = "SELECT
                `invoiced_quantity`,
                `unit_measure_code`,
                `free_of_charge`,
                `description`,
                `code`,
                `item_identification_type_code`,
                `price_amount`,
                `line_extension_amount`,
                `base_quantity`
              FROM `vtiger_view_products`
              WHERE
                id = ?;";
    $params = array($id);

    $result = $adb->pquery($query, $params);
    $num_rows = $adb->num_rows($result);

    $product = array();

    for ($i = 1; $i <= $num_rows; $i++) {

        $unit_measure_code = $adb->query_result($result, $i - 1, 'unit_measure_code');
        $invoiced_quantity = $adb->query_result($result, $i - 1, 'invoiced_quantity');
        $line_extension_amount = $adb->query_result($result, $i - 1, 'line_extension_amount');
        $free_of_charge_indicator = $adb->query_result($result, $i - 1, 'free_of_charge');
        $description = $adb->query_result($result, $i - 1, 'description');
        $code = $adb->query_result($result, $i - 1, 'code');
        $item_identification_type_code = $adb->query_result($result, $i - 1, 'item_identification_type_code');
        $price_amount = $adb->query_result($result, $i - 1, 'price_amount');
        $base_quantity = $adb->query_result($result, $i - 1, 'base_quantity');
        $tax = 19;
        settype($free_of_charge_indicator, "bool");
        $product[] = array(
            'unit_measure_code' => $unit_measure_code,
            'invoiced_quantity' => $invoiced_quantity,
            'line_extension_amount' => $line_extension_amount,
            'free_of_charge_indicator' => $free_of_charge_indicator,
            'description' => $description,
            'code' => $code,
            'item_identification_type_code' => $item_identification_type_code,
            'price_amount' => $price_amount,
            'base_quantity' => $base_quantity,
            'tax_totals' => array(new ArrayObject(array(
                    "tax_code" => "01",
                    "tax_amount" => ($line_extension_amount / 100) * $tax,
                    "taxable_amount" => $line_extension_amount,
                    "percent" => 19
                        ))),
        );
    }
    return $product;
}
