<?php

class cURL {
	var $headers;
	var $user_agent;
	var $compression;
	var $cookie_file;
	var $proxy = null;
	var $imgbuf = null;
	var $countcaptchas = 0;

	var $captcha = null;
	var $firsturl = '';
	var $imageurl = '';
	var $shcarturl = '';
	var $keywordsurl = '';
	var $sorturl = '';
	var $errstr = null;
	var $ipaddr = null;

	function cURL($cookies=TRUE,$cookie='',$ipaddr=null, $compression='gzip',$proxy='') {
		$this->headers[] = 'Accept:	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		$this->headers[] = 'Accept-Language: en-us,en;q=0.5';
		$this->headers[] = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		$this->user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.46 Safari/535.11';
		$this->compression=$compression;
		$this->cookies=$cookies;
		$this->kwcnt = 0;
		if (!empty($this->cookies)) $this->cookie($cookie);
	}
	function cookie($cookie_file) {
		if (file_exists($cookie_file)) {
			$this->cookie_file=$cookie_file;
		} else {
			$this->cookie_file = "/dev/null";
			return;
			$fh = fopen($cookie_file,'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
			$this->cookie_file=$cookie_file;
			fclose($fh);
		}
	}
	function set_header($header) {
		$this->headers[] = $header;
	}
	function get($url) {
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		// curl_setopt($process,CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 120);
		if ($this->proxy) curl_setopt($cUrl, CURLOPT_PROXY, $this->proxy);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		if($this->ipaddr) curl_setopt($process, CURLOPT_INTERFACE, $this->ipaddr);
		$return = curl_exec($process);
		if(!$return)
			$this->errstr = curl_error($process);
		curl_close($process);
		return $return;
	}
	function post($url,$data) {
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		// curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 120);
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		if($this->ipaddr) curl_setopt($process, CURLOPT_INTERFACE, $this->ipaddr);
		curl_setopt($process, CURLOPT_POST, 1);
		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}
	function post2($url, $data) {
		$d = array();
		foreach($data as $k=>$v)
			$d[] = "$k=" . urlencode($v);
		$sdata = implode("&", $d);
		return $this->post($url, $sdata);
	}
	function error($error) {
		die("ERROR: $error\n");
	}

	function put($url,$data) {
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 1);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		// curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 120);
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		curl_setopt($process, CURLOPT_PUT, 1);
		curl_setopt($process, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_HEADER, false);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		if($this->ipaddr) curl_setopt($process, CURLOPT_INTERFACE, $this->ipaddr);

		$fp = fopen('php://temp/maxmemory:256000', 'w');
		if (!$fp)
			return false;
		fwrite($fp, $data);
		fseek($fp, 0); 
		curl_setopt($process, CURLOPT_INFILE, $fp); // file pointer
		curl_setopt($process, CURLOPT_INFILESIZE, strlen($data)); 

		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}

	function delete($url) {
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
		if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
		// curl_setopt($process, CURLOPT_ENCODING , $this->compression);
		curl_setopt($process, CURLOPT_TIMEOUT, 120);
		if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($process, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_HEADER, false);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
		if($this->ipaddr) curl_setopt($process, CURLOPT_INTERFACE, $this->ipaddr);

		$return = curl_exec($process);
		curl_close($process);
		return $return;
	}
}

