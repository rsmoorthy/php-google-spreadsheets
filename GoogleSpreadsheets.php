<?php

/**
 * Copyright 2014 Moorthy RS
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// use SimpleXMLElement;
require_once "curl.php";

class GoogleSpreadsheets
{
	private $auth = "";
	public $key = "";
	public $ws = "";
	public $spreadsheets = array();
	public $worksheets = array();
	public $updates = array();
	public $rowId = array();
	/*
	 * Constructor
	 */
	public function __construct() 
	{

	}

	public function setAuth($auth)
	{
		$this->auth = $auth;

	}

	public function login($emailid, $password)
	{
		$data = array("Email"=>$emailid, "Passwd"=>$password, "accountType"=>"GOOGLE", 
						"source"=>"curl", "service"=>"wise");
		$curl = new cURL();
		$ret = $curl->post2("https://www.google.com/accounts/ClientLogin", $data);
		if(preg_match("/Auth=(\S+)/", $ret, $m))
			$this->auth = $m[1];
		print_r($this->auth);
	}

	public function listSpreadsheets()
	{
		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$ret = $curl->get("https://spreadsheets.google.com/feeds/spreadsheets/private/full");
		try {
			$obj = new SimpleXMLElement($ret);
		} catch (Exception $e) {
			print("bad xml"); // TODO
			return null;
		}
		$obj->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');

		$this->spreadsheets = array();
		foreach($obj->xpath("//x:entry") as $key => $node) {
			$node->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
			$url = (string)$node->xpath(".//x:id")[0];
			if(preg_match("/full\/(.*)$/", $url, $m))
				$key = $m[1];
			$title = (string)$node->xpath(".//x:title")[0];
			$this->spreadsheets[$title] = $key;
		}
	}

	public function listWorksheets()
	{
		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$ret = $curl->get("https://spreadsheets.google.com/feeds/worksheets/{$this->key}/private/full");
		try {
			$obj = new SimpleXMLElement($ret);
		} catch (Exception $e) {
			print("bad xml"); // TODO
			return null;
		}
		$obj->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');

		$this->spreadsheets = array();
		foreach($obj->xpath("//x:entry") as $key => $node) {
			$node->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
			$url = (string)$node->xpath(".//x:id")[0];
			if(preg_match("/full\/(.*)$/", $url, $m))
				$ws = $m[1];
			$title = (string)$node->xpath(".//x:title")[0];
			$this->worksheets[$title] = $ws;
		}

	}

	public function getSpreadsheet($spreadsheet, $worksheet)
	{
		$this->listSpreadsheets();
		if(array_key_exists($spreadsheet, $this->spreadsheets)) {
			$this->key = $this->spreadsheets[$spreadsheet];
			$this->listWorksheets();
			if(array_key_exists($worksheet, $this->worksheets))
				$this->ws = $this->worksheets[$worksheet];
		}
	}

	public function getCellFeed()
	{
		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$ret = $curl->get("https://spreadsheets.google.com/feeds/cells/{$this->key}/{$this->ws}/private/basic");
		try {
			$obj = new SimpleXMLElement($ret);
		} catch (Exception $e) {
			print("bad xml"); // TODO
			return null;
		}
		$obj->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');

		$this->data = array();
		$this->rc = array();
		foreach($obj->xpath("//x:entry") as $key => $node) {
			$node->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
			$url = (string)$node->xpath(".//x:id")[0];
			if(preg_match("/basic\/(.*)$/", $url, $m))
				$cell = $m[1];
			$content = (string)$node->xpath(".//x:content")[0];
			$this->data[$cell] = $content;
			list($row, $col) = $this->RCtoArray($cell);
			if(!array_key_exists($col, $this->rc))
				$this->rc[$col] = array();
			$this->rc[$col][$row] = $content;
		}
	}

	// Creates $this->colheads["1"] having column head
	public function getColumnHeads()
	{
		$this->colheads = array();
		for($col=1; $col <= count($this->rc); $col++) {
			$this->colheads[$col] = $this->rc[$col]["1"];
		}
	}

	// Returns the column number for a given column name
	// reverse of getColumnHeads
	public function getCol($columnName)
	{
		$headscol = array_flip($this->colheads);
		return $headscol[$header];
	}

	private function RCtoArray($RC)
	{
		if(preg_match("/R(\d+)C(\d+)/", $RC, $m))
			return array($m[1], $m[2]);
		return array(null, null);
	}

	public function getAllRows()
	{
		$this->allRows = array();
		$this->allRows2 = array();

		for($row=2; $row <= count($this->rc[1]); $row++) {
			$arow = array();
			for($col=1; $col <= count($this->rc); $col++) {
				$arow[$this->colheads[$col]] = $this->rc[$col][$row];
			}
			$arow["_row"] = $row;
			$this->allRows[] = $arow;
			$this->allRows2[$row] = $arow;
		}
	}

	public function addRow($values)
	{
		$xml = '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gsx="http://schemas.google.com/spreadsheets/2006/extended">';
		foreach($values as $k=>$v) {
			$k = str_replace("_", "", strtolower($k));
			$xml .= "<gsx:$k>$v</gsx:$k>";
		}
		$xml .= '</entry>';

		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$curl->set_header("Content-Type: application/atom+xml");
		$ret = $curl->post("https://spreadsheets.google.com/feeds/list/{$this->key}/{$this->ws}/private/full", $xml);
		print_r($ret);

	}

	public function deleteRow($row)
	{
		$this->getRowIds();

		$rowId = $this->rowId[$row];
		if(!$rowId)
			print "Unable to get rowId for $row\n";

		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$curl->set_header("If-Match: *");
		$ret = $curl->delete("https://spreadsheets.google.com/feeds/list/{$this->key}/{$this->ws}/private/full/$rowId", $xml);
		print_r($ret);
	}

	public function getRowIds()
	{
		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$ret = $curl->get("https://spreadsheets.google.com/feeds/list/{$this->key}/{$this->ws}/private/basic");
		try {
			$obj = new SimpleXMLElement($ret);
		} catch (Exception $e) {
			print("bad xml"); // TODO
			return null;
		}
		$obj->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');

		$this->rowId = array();
		$r = 2;
		foreach($obj->xpath("//x:entry") as $key => $node) {
			$node->registerXPathNamespace('x', 'http://www.w3.org/2005/Atom');
			$version = "";
			$url = (string)$node->xpath(".//x:id")[0];
			if(preg_match("/basic\/(.*)$/", $url, $m))
				$this->rowId[$r] = $m[1];
			$r++;
		}
	}

	public function updateCell($row, $col, $value)
	{
		$this->updates[] = array("row"=>$row, "col"=>$col, "value"=>$value);
	}

	public function pushUpdates()
	{
		$header = <<<EOD
		<feed xmlns="http://www.w3.org/2005/Atom"
      		  xmlns:batch="http://schemas.google.com/gdata/batch"
      		  xmlns:gs="http://schemas.google.com/spreadsheets/2006">
  			<id>https://spreadsheets.google.com/feeds/cells/{$this->key}/{$this->ws}/private/full</id>
EOD;
		$entries = "";
		$batch = "A1";
		foreach($this->updates as $update) {
			$row = $update["row"];
			$col = $update["col"];
			$value = $update["value"];
			$entries .= <<<EOD
	  			<entry>
	    			<batch:id>$batch</batch:id>
	    			<batch:operation type="update"/>
	    			<id>https://spreadsheets.google.com/feeds/cells/{$this->key}/{$this->ws}/private/full/R{$row}C{$col}</id>
	    			<link rel="edit" type="application/atom+xml"
	      						href="https://spreadsheets.google.com/feeds/cells/{$this->key}/{$this->ws}/private/full/R{$row}C{$col}"/>
	    			<gs:cell row="{$row}" col="{$col}" inputValue="{$value}"/>
	  			</entry>
EOD;
			$batch++;
		}

		$xml = $header . $entries . "</feed>";

		$curl = new cURL();
		$curl->set_header("Authorization: GoogleLogin auth=" . $this->auth);
		$curl->set_header("If-Match: *");
		$ret = $curl->post("https://spreadsheets.google.com/feeds/cells/{$this->key}/{$this->ws}/private/full/batch", $xml);

		if(preg_match_all("/batch:status code=.(\S+). reason=.(\S+)./", $ret, $m)) {
			foreach($m[1] as $code) {
				if($code != "200") {
					print "ERROR: Code: " . json_encode($m[1]) . " Reasons: " . json_encode($m[2]) . "\n";
					return false;
				}
			}
		}
		else {
			print "ERROR: $ret\n";
			return false;
		}

		return true;
	}
}

