(function(window) {
'use strict';

var AWS = {};

AWS.scrollTo = function(top, duration, queue) {
	if (typeof top == 'object') {
		top = $(top).offset().top;
	}
	$('html, body').animate({
		scrollTop: top
	}, {
		duration: duration || 600,
		queue: !!queue || true,
	});
}

var TPL = {};

TPL['loading'] =
	'<div id="id_global_loading" class="collapse" style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);">' +
		'<div class="spinner-border" role="status">' +
			'<span class="sr-only">Loading...</span>' +
		'</div>' +
	'</div>';

TPL['alert'] =
	'<div class="modal fade" tabindex="-1" aria-hidden="true">' +
		'<div class="modal-dialog">' +
			'<div class="modal-content p-sm-3">' +
				'<div class="modal-header border-0">' +
					'<div param="title"></div>' +
				'</div>' +
				'<div class="modal-footer border-0">' +
					'<button class="btn btn-primary" style="min-width:6rem;" type="button" data-dismiss="modal">OK</button>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>';

TPL['confirm'] =
	'<div class="modal fade" tabindex="-1" aria-hidden="true">' +
		'<div class="modal-dialog">' +
			'<div class="modal-content p-sm-3">' +
				'<div class="modal-header border-0">' +
					'<div param="title"></div>' +
				'</div>' +
				'<div class="modal-footer border-0">' +
					'<button class="btn btn-outline-secondary" style="min-width:6rem;" type="button" data-dismiss="modal">Cancel</button>' +
					'<button class="btn btn-primary" style="min-width:6rem;" type="button" data-dismiss="modal">OK</button>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>';

TPL['prompt'] =
	'<div class="modal fade" tabindex="-1" aria-hidden="true">' +
		'<div class="modal-dialog">' +
			'<div class="modal-content p-sm-3">' +
				'<div class="modal-header border-0">' +
					'<div param="title"></div>' +
				'</div>' +
				'<div class="modal-body">' +
					'<input class="form-control" type="text" value="" param="text">' +
				'</div>' +
				'<div class="modal-footer border-0">' +
					'<button class="btn btn-outline-secondary" style="min-width:6rem;" type="button" data-dismiss="modal">Cancel</button>' +
					'<button class="btn btn-primary" style="min-width:6rem;" type="button" data-dismiss="modal">OK</button>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>';

TPL['password_prompt'] =
	'<div class="modal fade" tabindex="-1" aria-hidden="true">' +
		'<div class="modal-dialog">' +
			'<div class="modal-content p-sm-3">' +
				'<div class="modal-header border-0">' +
					'<div param="title"></div>' +
				'</div>' +
				'<div class="modal-body">' +
					'<input class="form-control" type="password" value="" param="text">' +
				'</div>' +
				'<div class="modal-footer border-0">' +
					'<button class="btn btn-outline-secondary" style="min-width:6rem;" type="button" data-dismiss="modal">Cancel</button>' +
					'<button class="btn btn-primary" style="min-width:6rem;" type="button" data-dismiss="modal">OK</button>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>';

TPL['text_box'] =
	'<div class="modal fade" tabindex="-1" aria-hidden="true">' +
		'<div class="modal-dialog">' +
			'<div class="modal-content p-sm-3">' +
				'<div class="modal-header border-0">' +
					'<div param="title"></div>' +
				'</div>' +
				'<div class="modal-body">' +
					'<textarea class="form-control" rows="6" param="text"></textarea>'+
				'</div>' +
				'<div class="modal-footer border-0">' +
					'<button class="btn btn-outline-secondary" style="min-width:6rem;" type="button" data-dismiss="modal">Cancel</button>' +
					'<button class="btn btn-primary" style="min-width:6rem;" type="button" data-dismiss="modal">OK</button>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>';

function show_tpl(tpl_name) {
	var id = 'id_global_' + tpl_name;
	var el = $('#' + id);
	if (!el.length) {
		el = $(TPL[tpl_name]).attr('id', id).appendTo('body');
	}
	return el;
}

function show_text_box(tpl_name, title, text, callback) {
	var el = show_tpl(tpl_name);

	var title_el = el.find('*[param="title"]');
	!title && (title = '');
	title_el.text(title);

	var text_el = el.find('*[param="text"]');
	if (!callback && typeof text === 'function') {
		callback = text;
		text = null;
	}
	!text && (text = '');
	text_el.val(text);

	el.find('.modal-body textarea').css('height', 'auto');

	el.on('hide.bs.modal', function() {
		title_el.text('');
		text_el.val('');
	});

	el.find('.modal-footer .btn-primary').click(function() {
		callback && callback(text_el.val());
	});
	el.modal('show');
}

AWS.loading = function(type) {
	var el = show_tpl('loading');
	type != 'hide' && (type = 'show');
	el.collapse(type);
}

AWS.alert = function(title) {
	var el = show_tpl('alert');

	var title_el = el.find('*[param="title"]');
	!title && (title = '');
	title_el.text(title);

	el.on('hide.bs.modal', function() {
		title_el.text('');
	});

	el.modal('show');
}

AWS.confirm = function(title, callback) {
	var el = show_tpl('confirm');

	var title_el = el.find('*[param="title"]');
	!title && (title = '');
	title_el.text(title);

	el.on('hide.bs.modal', function() {
		title_el.text('');
	});

	el.find('.modal-footer .btn-primary').click(function() {
		callback && callback();
	});
	el.modal('show');
}

AWS.prompt = function(title, text, callback) {
	show_text_box('prompt', title, text, callback);
}

AWS.passwordPrompt = function(title, text, callback) {
	show_text_box('password_prompt', title, text, callback);
}

AWS.textBox = function(title, text, callback) {
	show_text_box('text_box', title, text, callback);
}

AWS.submit_redirect = function(url, data, method, target) {
	data = data || {};
	method = method || 'post';
	target = target || '_top';
	var form = $('<form>', {
		action: url,
		method: method,
		target: target
	});
	for (var key in data) {
		form.append($('<input>', {
			name: key,
			value: data[key],
			type: 'hidden'
		}));
	}
	form.appendTo('body').submit().remove();
}

AWS.ajax_request = function(url, params, cb, error_cb) {
	AWS.loading('show');

	params || (params = '');
	if (typeof params == 'object') params['_post_type'] = 'ajax';
	else params += '&_post_type=ajax';

	$.ajax({
		type: 'post',
		url: url,
		data: params,
		dataType: 'json',
		timeout: 60000,
		success: _success,
		error: _error,
	});

	function _show_error(text) {
		if (error_cb) {
			(typeof error_cb === 'function') && error_cb(text);
		} else {
			AWS.alert(text);
		}
	}

	function _success(data, textStatus, jqXHR) {
		AWS.loading('hide');

		if (typeof data !== 'object' || data === null) {
			data = {};
		}

		if (data.err) {
			_show_error(data.err);
			return;
		}

		if (data.url) {
			window.location = data.url;
			return;
		}

		if (cb) {
			(typeof cb === 'function') && cb(data.rsm);
		} else {
			window.location.reload();
		}
	}

	function _error(jqXHR, textStatus, errorThrown) {
		AWS.loading('hide');

		console.log(jqXHR.responseText);
		_show_error('Network error');
	}
}

window.AWS = AWS;
})(this);
