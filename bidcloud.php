<?php
/**
 * messify
 *
 * Copyright (c) 2014 Magwai Ltd. <info@magwai.ru>, http://magwai.ru
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php

USAGE:

// Include class and create instance
include 'bidcloud.php';
$bidcloud = new bidcloud();
try {
}
catch (Exception $e) {
	var_dump($e);
}

*/

class bidcloud {
	private $_service_host = 'bidcloud.ru';
	private $_token = null;
	private $_token_secret = null;
	private $_error_messages = array(
		1 => ''
	);

	public function __construct($options = array()) {
		$this->options($options);
	}

	private function _error($code, $message = '') {
		if (isset($this->_error_messages[$code])) {
			$args = func_get_args();
			$args[0] = $this->_error_messages[$code];
			$message = call_user_func_array('sprintf', $args);
		}
		if (!$message) $message = 'Unknown error';
		throw new Exception($message, $code);
	}

	private function _request($endpoint, $post = array()) {
		try {
			$post = array_merge(array(
				'token' => $this->_token,
				'token_secret' => $this->_token_secret,
				'host' => @$_SERVER['HTTP_HOST']
			), $post);
			$result = file_get_contents('https://'.$this->_service_host.'/api/'.$endpoint, false, stream_context_create(array(
				'http' => array(
					'method' =>	'POST',
					'header' => 'Content-Type: application/x-www-form-urlencoded',
					'user_agent' => 'bidcloud-1.0',
					'content' => http_build_query($post),
				)
			)));
		}
		catch (Exception $e) {
			$this->_error($e->getCode(), $e->getMessage());
		}
		$code = 8;
		$message = 'HTTP error';
		$result_encoded = $this->_result_encode($result);
		if (!$result_encoded) {
			$this->_error(40, $result);
		}
		if (isset($result_encoded['error'])) {
			$code = $result_encoded['error']['code'];
			$message = $result_encoded['error']['message'];
		}
		else {
			return $result_encoded;
		}
		$this->_error($code, $message);
	}

	private function _result_encode($json) {
		try {
			$result = json_decode($json, true);
		}
		catch (Exception $e) {
			$this->_error($e->getCode(), $e->getMessage());
		}
		return $result;
	}

	public function options($options) {
		if (!$options) {
			return;
		}
		$this->_check_options($options);
		foreach ($options as $key => $value) {
			$method = 'set_'.$key;
			if (method_exists($this, $method)) {
				$this->$method($value);
				$this->set_dirty(true);
			}
			else {
				$this->_error(30, $key);
			}
		}
		return $this;
	}

	public function set_token($token) {
		if ($token) {
			$this->_token = $token;
		}
		else {
			$this->_error(34);
		}
		return $this;
	}

	public function set_token_secret($token_secret) {
		if ($token_secret) {
			$this->_token_secret = $token_secret;
		}
		else {
			$this->_error(35);
		}
		return $this;
	}
}