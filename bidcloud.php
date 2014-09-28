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
		40 => "No result available. Returned:\n\n%s",
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
			if ($post) {
				foreach ($post as $k => $v) {
					if (is_array($v)) $post[$k] = json_encode($v);
				}
			}
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

	private function _check_options($options) {
		if (!is_array($options)) $this->_error(31);
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

	public function token($param = array()) {
		$ret = $this->_request('token', $param);
		if ($ret) {
			if (isset($ret['token']) && !$this->_token) $this->set_token($ret['token']);
			if (isset($ret['token_secret']) && !$this->_token_secret) $this->set_token_secret($ret['token_secret']);
		}
		return $ret;
	}

	public function auction_add($param = array()) {
		$ret = $this->_request('auction/add', $param);
		return $ret;
	}

	public function auction_set($param = array()) {
		$ret = $this->_request('auction/set', $param);
		return $ret;
	}

	public function auction_remove($param = array()) {
		$ret = $this->_request('auction/remove', $param);
		return $ret;
	}

	public function auction_fetch_default($param = array()) {
		$ret = $this->_request('auction/fetch/default', $param);
		return $ret;
	}

	public function goods_add($param = array()) {
		$ret = $this->_request('goods/add', $param);
		return $ret;
	}

	public function goods_set($param = array()) {
		$ret = $this->_request('goods/set', $param);
		return $ret;
	}

	public function goods_remove($param = array()) {
		$ret = $this->_request('goods/remove', $param);
		return $ret;
	}

	public function goods_fetch_default($param = array()) {
		$ret = $this->_request('goods/fetch/default', $param);
		return $ret;
	}

	public function user_add($param = array()) {
		$ret = $this->_request('user/add', $param);
		return $ret;
	}

	public function user_set($param = array()) {
		$ret = $this->_request('user/set', $param);
		return $ret;
	}

	public function user_remove($param = array()) {
		$ret = $this->_request('user/remove', $param);
		return $ret;
	}

	public function session_key($param = array()) {
		$ret = $this->_request('session/key', $param);
		return $ret;
	}

	public function client() {
		$data = isset($_POST) && is_array($_POST) ? $_POST : array();
		$token = isset($data['token']) ? $data['token'] : '';
		$token_secret = isset($data['token_secret']) ? $data['token_secret'] : '';
		if ($token === $this->_token && $token_secret === $this->_token_secret) {
			$endpoint = isset($data['endpoint']) ? $data['endpoint'] : '';
			unset($data['endpoint']);
			if ($endpoint && method_exists($this, '_client_'.$endpoint)) {
				$res = $this->{'_client_'.$endpoint}($data);
			}
			else {
				$res = array(
					'code' => 90,
					'message' => 'Wrong endpoint'
				);
			}
		}
		else {
			$res = array(
				'code' => 91,
				'message' => 'Wrong auth'
			);
		}
		ob_clean();
		header('Content-Type: application/json');
		echo json_encode($res);
		exit();
	}

	private function _client_ping() {
		return array(
			'pong' => 1
		);
	}

	private function _client_lot_add($data) {
		return array(
			'ok' => $this->callback_lot_add($data)
		);
	}

	public function callback_lot_add($data) {
		return true;
	}

	public function callback_lot_remove($data) {
		return true;
	}
}