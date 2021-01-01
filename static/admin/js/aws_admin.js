var AWS =
{
	//全局loading
	loading: function (type)
	{
		if (!$('#aw-loading').length)
		{
			$('#aw-ajax-box').append(AW_TEMPLATE.loadingBox);
		}

		if (type == 'show')
		{
			if ($('#aw-loading').css('display') == 'block')
			{
				return false;
			}

			$('#aw-loading').fadeIn();

			AWS.G.loading_timer = setInterval(function ()
			{
				AWS.G.loading_bg_count -= 1;

				$('#aw-loading-box').css('background-position', '0px ' + AWS.G.loading_bg_count * 40 + 'px');

				if (AWS.G.loading_bg_count == 1)
				{
					AWS.G.loading_bg_count = 12;
				}
			}, 100);
		}
		else
		{
			$('#aw-loading').fadeOut();

			clearInterval(AWS.G.loading_timer);
		}
	},

	loading_mini: function (selector, type)
	{
		if (!selector.find('#aw-loading-mini-box').length)
		{
			selector.append(AW_TEMPLATE.loadingMiniBox);
		}

		if (type == 'show')
		{
			selector.find('#aw-loading-mini-box').fadeIn();

			AWS.G.loading_timer = setInterval(function ()
			{
				AWS.G.loading_mini_bg_count -= 1;

				$('#aw-loading-mini-box').css('background-position', '0px ' + AWS.G.loading_mini_bg_count * 16 + 'px');

				if (AWS.G.loading_mini_bg_count == 1)
				{
					AWS.G.loading_mini_bg_count = 9;
				}
			}, 100);
		}
		else
		{
			selector.find('#aw-loading-mini-box').fadeOut();

			clearInterval(AWS.G.loading_timer);
		}
	},

	ajax_request: function(url, params)
	{
		AWS.loading('show');

		if (params)
		{
			$.post(url, params + '&_post_type=ajax', function (result)
			{
				_callback(result);
			}, 'json').error(function (error)
			{
				_error(error);
			});
		}
		else
		{
			$.get(url, function (result)
			{
				_callback(result);
			}, 'json').error(function (error)
			{
				_error(error);
			});
		}

		function _callback (result)
		{
			AWS.loading('hide');

			if (!result)
			{
				return false;
			}

			if (result.err)
			{
				AWS.alert(result.err);
				return;
			}

			if (result.url)
			{
				window.location = result.url;
				return;
			}

			if (result.rsm && result.rsm.url)
			{
				window.location = result.rsm.url;
				return;
			}

			window.location.reload();
		}

		function _error (error)
		{
			AWS.loading('hide');

			if ($.trim(error.responseText) != '')
			{
				alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
			}
		}

		return false;
	},

	ajax_post: function(formEl, processer, type) // 表单对象，用 jQuery 获取，回调函数名
	{
		if (typeof (processer) != 'function')
		{
			var processer = AWS.ajax_processer;

			AWS.loading('show');
		}

		if (!type)
		{
			var type = 'default';
		}

		var custom_data = {
			_post_type: 'ajax'
		};

		formEl.ajaxSubmit(
		{
			dataType: 'json',
			data: custom_data,
			success: function (result)
			{
				processer(type, result);
			},
			error: function (error)
			{
				if ($.trim(error.responseText) != '')
				{
					AWS.loading('hide');

					alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
				}
			}
		});
	},

	// ajax提交callback
	ajax_processer: function (type, result)
	{
		if (type == 'default')
		{
			AWS.loading('hide');
		}
		if (result.err)
		{
			switch (type)
			{
				case 'default':
					AWS.alert(result.err);
				break;

				case 'ajax_post_alert':
				case 'ajax_post_modal':
				case 'error_message':
					if (!$('.error_message').length)
					{
						alert(result.err);
					}
					else if ($('.error_message em').length)
					{
						$('.error_message em').html(result.err);
					}
					else
					{
						 $('.error_message').html(result.err);
					}

					if ($('.error_message').css('display') != 'none')
					{
						AWS.shake($('.error_message'));
					}
					else
					{
						$('.error_message').fadeIn();
					}

				break;
			}
		}
		else
		{
			if (result.url)
			{
				window.location = result.url;
			}
			else if (result.rsm && result.rsm.url)
			{
				window.location = result.rsm.url;
			}
			else
			{
				switch (type)
				{
					case 'default':
					case 'ajax_post_alert':
					case 'error_message':
						window.location.reload();
					break;

					case 'ajax_post_modal':
						$('#aw-ajax-box div.modal').modal('hide');
					break;
				}
			}
		}
	},

	// 警告弹窗
	alert: function (text)
	{
		if ($('.alert-box').length)
		{
			$('.alert-box').remove();
		}

		$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.alertBox).render(
		{
			message: text
		}));

		$(".alert-box").modal('show');
	},

	// 确认弹窗
	confirm: function (text, callback)
	{
		$('.alert-box').remove();
		$('.modal-backdrop').remove();

		$('#aw-ajax-box').append(Hogan.compile(AW_TEMPLATE.confirmBox).render(
		{
			message: text
		}));

		$('.aw-confirm-box .yes').click(function()
		{
			$(".alert-box").modal('hide');
			if (callback)
			{
				callback();
			}
			return false;
		});

		$(".alert-box").modal('show');
	},

	/**
	 * 公共弹窗
	 */
	dialog: function (type, data, callback)
	{
		switch (type)
		{
			// 后台分类移动设置
			case 'adminCategoryMove':
				var template = Hogan.compile(AW_TEMPLATE.adminCategoryMove).render(
				{
					'items' : data.option,
					'name':data.name,
					'from_id':data.from_id
				});
			break;
		}

		if (template)
		{
			if ($('.alert-box').length)
			{
				$('.alert-box').remove();
			}

			$('#aw-ajax-box').html(template).show();

			switch (type)
			{
				//后台分类移动设置
				case 'adminCategoryMove':
					$('.aw-confirm-box .yes, .aw-category-move-box .yes').click(function()
					{
						if (callback)
						{
							callback();
						}

						$(".alert-box").modal('hide');

						return false;
					});
				break;
			}

			$(".alert-box").modal('show');
		}
	},

	// 错误提示效果
	shake: function(selector)
	{
		var length = 6;
		selector.css('position', 'relative');
		for (var i = 1; i <= length; i++)
		{
			if (i % 2 == 0)
			{
				if (i == length)
				{
					selector.animate({ 'left': 0 }, 50);
				}
				else
				{
					selector.animate({ 'left': 10 }, 50);
				}
			}
			else
			{
				selector.animate({ 'left': -10 }, 50);
			}
		}
	}
}

// 全局变量
AWS.G =
{
	loading_timer: '',
	loading_bg_count: 12,
	loading_mini_bg_count: 9,
}


function _t(string, replace)
{
	if (window.aws_lang && window.aws_lang[string])
	{
		string = aws_lang[string];
	}

	if (replace)
	{
		string = string.replace('%s', replace);
	}

	return string;
};
