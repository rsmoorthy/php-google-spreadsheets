<?php

require_once "GoogleSpreadsheets.php";

$gs = new GoogleSpreadsheets();
// $gs->login("email@gmail.com", "");
$gs->setAuth("auth key");
$gs->key = "spreadsheetkey";
$gs->ws = "worksheetkey";
// $gs->getSpreadsheet("Test Data", "Sheet1");
$gs->getCellFeed();
$gs->getColumnHeads();
$gs->getAllRows();
// print_r($gs->rc);
// print_r($gs->colheads);
// print_r($gs->allRows2);
// $gs->deleteRow(11);
// $gs->addRow(array("sync_id"=>"2342423423", "state"=>"Mizoram", "country"=>"India", "sync_ts"=>"233242342"));
// $gs->updateCell("2", "2", "TNTN");
// $gs->updateCell("3", "2", "KA");
// $gs->pushUpdates();

