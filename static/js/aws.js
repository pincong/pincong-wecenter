// jQuery扩展
(function($) {
	$.fn.extend({
		insertAtCaret: function(textFeildValue) {
			var textObj = $(this).get(0);
			if (document.all && textObj.createTextRange && textObj.caretPos) {
				var caretPos = textObj.caretPos;
				caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == '' ?
					textFeildValue + '' : textFeildValue;
			} else if (textObj.setSelectionRange) {
				var rangeStart = textObj.selectionStart,
					rangeEnd = textObj.selectionEnd,
					tempStr1 = textObj.value.substring(0, rangeStart),
					tempStr2 = textObj.value.substring(rangeEnd);
				textObj.value = tempStr1 + textFeildValue + tempStr2;
				textObj.focus();
				var len = textFeildValue.length;
				textObj.setSelectionRange(rangeStart + len, rangeStart + len);
				textObj.blur();
			} else {
				textObj.value += textFeildValue;
			}
		}
	});

	$.extend({
		// 滚动到指定位置
		scrollTo: function(type, duration, options) {
			if (typeof type == 'object') {
				type = $(type).offset().top;
			}

			$('html, body').animate({
				scrollTop: type
			}, {
				duration: duration,
				queue: options.queue
			});
		}
	});
})(jQuery);

function _t(string, replace) {
	if (window.aws_lang && window.aws_lang[string]) {
		string = aws_lang[string];
	}
	if (replace) {
		string = string.replace('%s', replace);
	}
	return string;
}

var AW_TEMPLATE = {};

AW_TEMPLATE['loadingBox'] =
	'<div id="aw-loading" class="collapse">' +
	'<div id="aw-loading-box"></div>' +
	'</div>';

AW_TEMPLATE['alertBox'] =
	'<div class="modal fade alert-box aw-tips-box">' +
	'<div class="modal-dialog">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>' +
	'<h3 class="modal-title">' + _t('提示信息') + '</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'<p>{{message}}</p>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'</div>';

AW_TEMPLATE['confirmBox'] =
	'<div class="modal fade alert-box aw-tips-box aw-confirm-box">' +
	'<div class="modal-dialog">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>' +
	'<h3 class="modal-title">' + _t('提示信息') + '</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'{{message}}' +
	'</div>' +
	'<div class="modal-footer">' +
	'<a class="btn btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>' +
	'<a class="btn btn-success yes">' + _t('确定') + '</a>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'</div>';

AW_TEMPLATE['promptBox'] =
	'<div class="modal fade alert-box aw-share-box aw-prompt-box">' +
	'<div class="modal-dialog">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>' +
	'<h3 class="modal-title">{{title}}</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'<input class="form-control" type="text" value="{{message}}" />' +
	'</div>' +
	'<div class="modal-footer">' +
	'<a class="btn btn-large btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>' +
	'<a class="btn btn-large btn-success yes">' + _t('确定') + '</a>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'</div>';

AW_TEMPLATE['passwordPromptBox'] =
	'<div class="modal fade alert-box aw-share-box aw-prompt-box">' +
	'<div class="modal-dialog">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>' +
	'<h3 class="modal-title">{{title}}</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'<input class="form-control" type="password" value="{{message}}" />' +
	'</div>' +
	'<div class="modal-footer">' +
	'<a class="btn btn-large btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>' +
	'<a class="btn btn-large btn-success yes">' + _t('确定') + '</a>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'</div>';

AW_TEMPLATE['textBox'] =
	'<div class="modal fade alert-box aw-share-box aw-text-box">' +
	'<div class="modal-dialog">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>' +
	'<h3 class="modal-title">{{title}}</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'<textarea class="form-control" rows="5">{{message}}</textarea>' +
	'</div>' +
	'<div class="modal-footer">' +
	'<a class="btn btn-large btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>' +
	'<a class="btn btn-large btn-success yes">' + _t('确定') + '</a>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'</div>';

var AWS = {};

//全局loading
AWS.loading_timer = null;
AWS.loading = function(type) {
	if (!$('#aw-loading').length) {
		$('#aw-ajax-box').append(AW_TEMPLATE.loadingBox);
	}

	if (type == 'show') {
		if ($('#aw-loading').css('display') == 'block') {
			return;
		}

		$('#aw-loading').fadeIn();

		if (!AWS.loading_timer) {
			var loading_bg_count = 12;
			AWS.loading_timer = setInterval(function() {
				loading_bg_count -= 1;
				if (loading_bg_count == 1) loading_bg_count = 12;
				$('#aw-loading-box').css('background-position', '0px ' + loading_bg_count * 40 + 'px');
			}, 100);
		}
	} else {
		$('#aw-loading').fadeOut();

		if (AWS.loading_timer) {
			clearInterval(AWS.loading_timer);
			AWS.loading_timer = null;
		}
	}
}

// 警告弹窗
AWS.alert = function(text) {
	$('.alert-box').remove();
	$('.modal-backdrop').remove();

	$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.alertBox).render({
		message: text
	}));

	$(".alert-box").modal('show');
}

// 确认弹窗
AWS.confirm = function(text, callback) {
	$('.alert-box').remove();
	$('.modal-backdrop').remove();

	$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.confirmBox).render({
		message: text
	}));

	$('.aw-confirm-box .yes').click(function() {
		$(".alert-box").modal('hide');
		if (callback) {
			callback();
		}
		return false;
	});

	$(".alert-box").modal('show');
}

// 单行输入框弹窗
AWS.prompt = function(title, message, callback) {
	$('.alert-box').remove();
	$('.modal-backdrop').remove();

	if (!callback && typeof message === 'function') {
		callback = message;
		message = '';
	}

	$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.promptBox).render({
		title: title,
		message: message
	}));

	$('.aw-prompt-box .yes').click(function() {
		$(".alert-box").modal('hide');
		if (callback) {
			callback($('.aw-prompt-box input').val());
		}
		return false;
	});

	$(".alert-box").modal('show');
}

// 单行输入框弹窗
AWS.passwordPrompt = function(title, message, callback) {
	$('.alert-box').remove();
	$('.modal-backdrop').remove();

	if (!callback && typeof message === 'function') {
		callback = message;
		message = '';
	}

	$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.passwordPromptBox).render({
		title: title,
		message: message
	}));

	$('.aw-prompt-box .yes').click(function() {
		$(".alert-box").modal('hide');
		if (callback) {
			callback($('.aw-prompt-box input').val());
		}
		return false;
	});

	$(".alert-box").modal('show');
}

// 多行输入框弹窗
AWS.textBox = function(title, message, callback) {
	$('.alert-box').remove();
	$('.modal-backdrop').remove();

	if (!callback && typeof message === 'function') {
		callback = message;
		message = '';
	}

	$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.textBox).render({
		title: title,
		message: message
	}));

	$('.aw-text-box .yes').click(function() {
		$(".alert-box").modal('hide');
		if (callback) {
			callback($('.aw-text-box textarea').val());
		}
		return false;
	});

	$(".alert-box").modal('show');
}

AWS.popup = function(url, callback) {
	$.get(url, function(template) {
		$('.alert-box').remove();
		$('.modal-backdrop').remove();

		$('#aw-ajax-box').html(template).show();

		if (callback) {
			callback();
		}

		$(".alert-box").modal('show');
	});
}

// 错误提示效果
AWS.shake = function(selector) {
	var length = 6;
	selector.css('position', 'relative');
	for (var i = 1; i <= length; i++) {
		if (i % 2 == 0) {
			if (i == length) {
				selector.animate({
					'left': 0
				}, 50);
			} else {
				selector.animate({
					'left': 10
				}, 50);
			}
		} else {
			selector.animate({
				'left': -10
			}, 50);
		}
	}
}

AWS.format_date = function(timestamp) {
	var d = new Date(timestamp);
	return d.getFullYear() + "-" + ("0" + (d.getMonth() + 1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2) + " " + ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
}

// 提交表单并跳转
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

// 加载更多
AWS.load_list_view = function(url, selector, container, start_page, total_pages, callback) {
	if (typeof start_page == 'function') {
		callback = start_page;
		start_page = null;
		total_pages = null;
	} else if (typeof total_pages == 'function') {
		callback = total_pages;
		total_pages = null;
	}

	if (!start_page) {
		start_page = 0
	}

	var page = start_page;

	selector.bind('click', function() {
		var _this = this;

		$(this).addClass('loading');

		$.get(url + '__page-' + page, function(result) {
			$(_this).removeClass('loading');

			result = $.trim(result);

			if (!result) {
				//没有内容
				if (page == start_page && $(_this).attr('auto-load') != 'false') {
					container.html('<p style="padding: 15px 0" align="center">' + _t('没有内容') + '</p>');
				}
			} else {
				container.append(result);
			}

			// 页数增加1
			page++;

			if (!result || (total_pages && page > total_pages)) {
				$(_this).addClass('disabled').unbind('click').bind('click', function() {
					return false;
				});

				$(_this).find('span').html(_t('没有更多了'));
			}

			if (callback) {
				callback();
			}
		});

		return false;
	});

	// 自动加载
	if (selector.attr('auto-load') != 'false') {
		selector.click();
	}
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
		_show_error(_t('网络连接异常'));
	}
}

AWS.submit_form = function(form_el, btn_el, err_el, cb, error_cb) {
	// 若有编辑器的话就从编辑器更新内容再提交
	form_el.find('textarea').each(function() {
		if (this._sceditor) {
			this._sceditor.updateOriginal();
		}
	});

	if (btn_el) {
		btn_el.addClass('disabled');
	}

	AWS.ajax_request(form_el.attr('action'), form_el.serialize(), function(rsm) {
		if (btn_el) {
			btn_el.removeClass('disabled');
		}
		if (cb) {
			form_el.find('textarea').each(function() {
				$(this).val('');
				// 若有编辑器的话
				if (this._sceditor) {
					this._sceditor.val('');
				}
			});
			(typeof cb === 'function') && cb(rsm);
		} else {
			window.location.reload();
		}
	}, function(text) {
		if (btn_el) {
			btn_el.removeClass('disabled');
		}
		if (err_el && err_el.length) {
			if (err_el.find('em').length) {
				err_el.find('em').html(text);
			} else {
				err_el.html(text);
			}
			if (err_el.css('display') != 'none') {
				AWS.shake(err_el);
			} else {
				err_el.fadeIn();
			}
		}
		if (error_cb) {
			(typeof error_cb === 'function') && error_cb(text);
		} else {
			if (!err_el || !err_el.length) {
				AWS.alert(text);
			}
		}
	});
}

AWS.submit_append = function(form_el, btn_el, container_el, cb, error_cb) {
	AWS.submit_form(form_el, btn_el, null, function(rsm) {
		if (container_el && container_el.length) {
			if (rsm && rsm.ajax_html) {
				try {
					$.scrollTo($(rsm.ajax_html).appendTo(container_el), 600, {
						queue: true
					});
				} catch (e) {}
			}
		}
		if (cb) {
			(typeof cb === 'function') && cb(rsm);
		}
	}, error_cb);
}
