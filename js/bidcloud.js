var bc = {
	config: {
		host: 'https://bidcloud.ru/socket'
	},
	callback: {},
	log: true,
	message: {
		1: 'Ваша ставка принята',
		2: 'Ваша ставка последняя. Вы не можете делать две ставки подряд',
		3: 'Ставка должна быть кратна {rate_multiple}',
		4: 'Ставка не может быть меньше {rate_min}',
		5: 'Ваша ставка превышает максимальный размер {rate_max}',
		6: 'Торги еще не начались',
		7: 'Торги завершены',
		50: 'Торги завершены'
	}
};

bc.init = function(d) {
	if (d) $.extend(bc, d);
	bc.socket_init();
	bc.auction_init();
};

bc.auction_init = function() {
	var lots = $('.bc-lot');
	if (lots.length) {
		lots.each(function() {
			var t = $(this);
			bc.lot_init(t);
		});
	}
	lots.on('click', '.bc-button', function() {
		var t = $(this);
		var o = t.parents('.bc-lot');
		var auction = o.data('bc-auction');
		var lot = o.data('bc-lot');
		var input = o.find('.bc-input');
		var rate = input.val();
		bc.rate(auction, lot, rate);
	});
};

bc.lot_init = function(o) {
	o.removeClass('bc-lot-na').addClass('bc-lot-initing');
	bc.lot_refresh(o);
};

bc.lot_refresh = function(o) {
	o.addClass('bc-lot-updating');
	var auction = o.data('bc-auction');
	var lot = o.data('bc-lot');
	bc.emit(auction, 'refresh', {
		lot: lot
	});
};
bc.lot_update = function(o, data) {
	o.removeClass('bc-lot-active bc-lot-finished');
	var price_o = o.find('.bc-price');
	var timer_o = o.find('.bc-timer');
	var user_o = o.find('.bc-user');
	var input_o = o.find('.bc-input');
	var info_o = o.find('.bc-info');

	var rate_next = data.price + data.rate_default;
	var end_time = new Date(data.end_time * 1000);

	price_o.html(data.price);

	input_o.val(rate_next);


	user_o.html(data.user);

	if (data.active) {
		o.addClass('bc-lot-active');
		if (end_time.getTime() >= (new Date).getTime()) timer_o.countdown(end_time).on('update.countdown', function(event) {
			bc.countdown_step(timer_o, event);
		}).on('finish.countdown', function(event) {
			bc.countdown_step(timer_o, event);
			bc.lot_refresh(o);
		});
		info_o.html('');
	}
	else {
		o.addClass('bc-lot-finished');
		if (timer_o.data('countdown-instance')) timer_o.countdown('stop');
		info_o.html(bc.parse_error({code: 50}));
	}

	o.removeClass('bc-lot-initing bc-lot-updating');
};

bc.countdown_step = function(o, event) {
	o.html(event.strftime('%d д. %H:%M:%S'));
};

bc.socket_init = function() {
	bc.socket = io(bc.config.host, {secure: true});
	bc.socket.on('api_error', function (data) {
		bc.api_error(data);
	});
	bc.socket.on('refresh', function (data) {
		var o = $('.bc-lot[data-bc-auction="' + data.auction + '"][data-bc-lot="' + data.id + '"]');
		if (o.length) bc.lot_update(o, data);
	});
	bc.socket.on('rate', function(data) {
		if (bc.log) console.log('callback rate', data);
		if (bc.callback.rate) bc.callback.rate(data);
	});
	bc.socket.on('user_error', function(data) {
		data.message = bc.parse_error(data);
		if (bc.log) console.log('callback user_error', data);
		if (bc.callback.user_error) bc.callback.user_error(data);
	});
	bc.socket.on('user_info', function(data) {
		data.message = bc.parse_error(data);
		if (bc.log) console.log('callback user_info', data);
		if (bc.callback.user_info) bc.callback.user_info(data);
	});
};

bc.rate = function(auction, lot, rate) {
	bc.emit(auction, 'rate', {
		lot: lot,
		rate: rate
	});
};

bc.api_error = function(data) {
	if (bc.log) console.log('callback api_error', data);
	if (bc.callback.api_error) bc.callback.api_error(data);
};

bc.emit = function(auction, method, data) {
	if (bc.auction[auction]) {
		bc.socket.emit('api', {
			auction: auction,
			key: bc.auction[auction].key,
			method: method,
			data: data
		});
	}
	else {
		bc.api_error({
			code: 100,
			message: 'Auction key was not sent in backend'
		});
	}
};

bc.parse_error = function(data) {
	var message = 'Unknown error';
	if (typeof bc.message[data.code] !== 'undefined') {
		message = bc.message[data.code];
		if (message.indexOf('{') !== -1) {
			for (var k in data) {
				message = message.replace('{' + k + '}', data[k]);
			}
		}
	}
	return message;
};







bc.callback.api_error = function(data) {
	$(document.body).append('<div>api error: ' + data.message + '</div>');
};

bc.callback.user_error = function(data) {
	$(document.body).append('<div>error: ' + data.message + '</div>');
};

bc.callback.user_info = function(data) {
	$(document.body).append('<div>info: ' + data.message + '</div>');
};