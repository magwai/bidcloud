<?php

if (!class_exists('bidcloud')) include 'bidcloud.php';

session_start();

$error = '';

try {
	$bidcloud = new bidcloud(array(
		'token' => 'c16223f09febe13641bc16ec5ac8366c',
		'token_secret' => 'Keaiur5T'
	));

	if (!@$_SESSION['key']) {
		$res = $bidcloud->session_key(array(
			'session' => session_id(),
			'user' => 1001,
			'auction' => 123
		));
		$_SESSION['key'] = $res['key'];
	}
}
catch (Exception $e) {
	$error = var_export($e, 1);
}

$list = array(1, 2, 3);

?>
<html>
	<head>
		<meta charset=utf-8 />
		<style>
			.bc-lot{border:1px solid red;padding:10px 10px 0 10px;text-align:center;width:200px;float:left;margin:0 20px 20px 0;}
			.bc-timer{display:none;background:#ccc;margin-bottom:10px;line-height:20px;height:20px;}
			.bc-price{background:#ccc;margin-bottom:10px;line-height:20px;height:20px;}
			.bc-user{background:#ccc;margin-bottom:10px;line-height:20px;height:20px;}
			.bc-input{display:none;box-sizing:border-box;width:100%;padding:5px 10px;text-align:center;margin-bottom:10px;}
			.bc-button{display:none;box-sizing:border-box;padding:5px 10px;margin-bottom:10px;}
			.bc-info{display:none;margin-bottom:10px;}
			.bc-lot-na{background:#f1f1f1;}
			.bc-lot-initing{background:#ccc;}
			.bc-lot-na *,.bc-lot-initing *{visibility:hidden;}

			.bc-lot-active .bc-timer{display:block;}
			.bc-lot-active .bc-input{display:block;}
			.bc-lot-active .bc-button{display:block;}

			.bc-lot-finished .bc-info{display:block;}
		</style>
	</head>
	<body>
<?php

foreach ($list as $el) {

?>
		<div class="bc-lot bc-lot-na" data-bc-auction="123" data-bc-lot="<?php echo $el ?>">
			<div class="bc-timer"></div>
			<div class="bc-price"></div>
			<div class="bc-user"></div>
			<input type="text" value="" class="bc-input" />
			<input type="button" class="bc-button" value="Сделать ставку" />
			<div class="bc-info"></div>
		</div>
<?php

}

?>
		<div style="clear:both;"></div>
		<?php echo $error ?>
		<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
		<script type="text/javascript" src="https://cdn.socket.io/socket.io-1.1.0.js"></script>
		<script type="text/javascript" src="https://bidcloud.ru/js/jquery.countdown.js"></script>
		<script type="text/javascript" src="https://bidcloud.ru/js/bidcloud.js"></script>
		<script type="text/javascript" src="https://bidcloud.ru/js/lang/ru.js"></script>
		<script type="text/javascript">
			bc.init(<?php echo json_encode(array(
				'auction' => array(
					123 => array(
						'key' => @$_SESSION['key']
					)
				)
			)) ?>);
		</script>
	</body>
</html>