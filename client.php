<?php

if (!class_exists('bidcloud')) include 'bidcloud.php';

try {
	$bidcloud = new bidcloud(array(
		'token' => 'c16223f09febe13641bc16ec5ac8366c',
		'token_secret' => 'Keaiur5T'
	));
	$bidcloud->client();
}
catch (Exception $e) {
	var_export($e);
}