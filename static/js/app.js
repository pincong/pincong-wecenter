AW_TEMPLATE['editTopicBox'] =
	'<div class="aw-edit-topic-box form-inline">' +
	'<input type="text" class="form-control" id="aw_edit_topic_title" autocomplete="off"  placeholder="' + _t('创建或搜索添加新话题') + '...">' +
	'<a class="btn btn-normal btn-success add">' + _t('添加') + '</a>' +
	'<a class="btn btn-normal btn-gray close-edit">' + _t('取消') + '</a>' +
	'<div class="aw-dropdown">' +
	'<p class="title">' + _t('没有找到相关结果') + '</p>' +
	'<ul class="aw-dropdown-list">' +
	'</ul>' +
	'</div>' +
	'</div>';

AW_TEMPLATE['dropdownList'] =
	'<div aria-labelledby="dropdownMenu" role="menu" class="aw-dropdown">' +
	'<ul class="aw-dropdown-list">' +
	'{{#items}}' +
	'<li><a data-value="{{id}}">{{title}}</a></li>' +
	'{{/items}}' +
	'</ul>' +
	'</div>';

AW_TEMPLATE['inviteDropdownList'] =
	'<li class="user"><a data-url="{{url}}" data-id="{{uid}}" data-actions="{{action}}" data-value="{{name}}"><img class="img" src="{{img}}" />{{name}}</a></li>';

AW_TEMPLATE['editTopicDorpdownList'] =
	'<li class="question"><a>{{name}}</a></li>';


var document_title = document.title;

// 全局变量
AWS.G = {
	dropdown_list_xhr: '',
	notification_timer: ''
}

AWS.User = {
	// 关注
	follow: function(selector, type, data_id) {
		if (selector.html()) {
			if (selector.hasClass('active')) {
				selector.find('span').html(_t('关注'));

				selector.find('b').html(parseInt(selector.find('b').html()) - 1);
			} else {
				selector.find('span').html(_t('取消关注'));

				selector.find('b').html(parseInt(selector.find('b').html()) + 1);
			}
		} else {
			if (selector.hasClass('active')) {
				selector.attr('data-original-title', _t('关注'));
			} else {
				selector.attr('data-original-title', _t('取消关注'));
			}
		}

		selector.addClass('disabled');

		var url = '/topic/ajax/focus_topic/';
		var data = {
			'topic_id': data_id
		};

		$.post(G_BASE_URL + url, data, function(result) {
			if (result.rsm) {
				if (result.rsm.type == 'add') {
					selector.addClass('active');
				} else {
					selector.removeClass('active');
				}
			} else if (result.err) {
				AWS.alert(result.err);
			}

			selector.removeClass('disabled');

		}, 'json');
	},

	share_out: function(options) {
		var title = $('title').text();
		var url = window.location.href;
		AWS.textBox(_t('分享'), title + '\r\n' + url);
	},

	// 删除别人邀请我回复的问题
	question_invite_delete: function(selector, question_invite_id) {
		$.post(G_BASE_URL + '/question/ajax/question_invite_delete/', 'question_invite_id=' + question_invite_id, function(result) {
			if (result.err) {
				AWS.alert(result.err);
			} else {
				selector.fadeOut();
			}
		}, 'json');
	},

	// 邀请用户回答问题
	invite_user: function(selector, img) {
		$.post(G_BASE_URL + '/question/ajax/save_invite/', {
			'question_id': QUESTION_ID,
			'uid': selector.attr('data-id')
		}, function(result) {
			if (!result.err) {
				if (selector.parents('.aw-invite-box').find('.invite-list a').length == 0) {
					selector.parents('.aw-invite-box').find('.invite-list').show();
				}
				selector.parents('.aw-invite-box').find('.invite-list').append(' <a class="aw-small-text invite-list-user" data-toggle="tooltip" data-placement="bottom" data-original-title="' + selector.attr('data-value') + '"><img src=' + img + ' /></a>');
				selector.addClass('active').attr('onclick', 'AWS.User.disinvite_user($(this))').text('取消邀请');
				selector.parents('.aw-question-detail').find('.aw-invite-reply .badge').text(parseInt(selector.parents('.aw-question-detail').find('.aw-invite-reply .badge').text()) + 1);
			} else if (result.err) {
				AWS.alert(result.err);
			}
		}, 'json');
	},

	// 取消邀请用户回答问题
	disinvite_user: function(selector) {
		$.get(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + QUESTION_ID + "__recipients_uid-" + selector.attr('data-id'), function(result) {
			if (!result.err) {
				$.each($('.aw-question-detail .invite-list a'), function(i, e) {
					if ($(this).attr('data-original-title') == selector.parents('.main').find('.aw-user-name').text()) {
						$(this).detach();
					}
				});
				selector.removeClass('active').attr('onclick', 'AWS.User.invite_user($(this),$(this).parents(\'li\').find(\'img\').attr(\'src\'))').text('邀请');
				selector.parents('.aw-question-detail').find('.aw-invite-reply .badge').text(parseInt(selector.parents('.aw-question-detail').find('.aw-invite-reply .badge').text()) - 1);
				if (selector.parents('.aw-invite-box').find('.invite-list').children().length == 0) {
					selector.parents('.aw-invite-box').find('.invite-list').hide();
				}
			}
		});
	},

	edit_verified_title: function(uid, text) {
		AWS.textBox(_t('头衔'), text, function(text) {
			text = encodeURIComponent(text.trim());
			AWS.ajax_request(G_BASE_URL + '/user/ajax/edit_verified_title/', 'uid=' + uid + '&text=' + text);
		});
	},

	edit_signature: function(uid, text) {
		AWS.textBox(_t('签名'), text, function(text) {
			text = encodeURIComponent(text.trim());
			AWS.ajax_request(G_BASE_URL + '/user/ajax/edit_signature/', 'uid=' + uid + '&text=' + text);
		});
	},

	toggle_vote: function(selector, type, operation, item_id) {
		var $ui = $(selector).parents('.aw-vote-ui');
		var $agree_btn = $ui.find('.agree');
		var $disagree_btn = $ui.find('.disagree');
		var $count = $ui.find('.count');
		// 初始状态
		var initial_count = parseInt($count.html());
		var initial_status = 0;
		if ($agree_btn.hasClass('active')) initial_status = 1;
		if ($disagree_btn.hasClass('active')) initial_status = -1;
		// 当前状态
		var status = initial_status;

		var set_btns = function(status) {
			if (status == 1) {
				$agree_btn.addClass('active');
				$disagree_btn.removeClass('active');
				return;
			}
			if (status == -1) {
				$agree_btn.removeClass('active');
				$disagree_btn.addClass('active');
				return;
			}
			$agree_btn.removeClass('active');
			$disagree_btn.removeClass('active');
		};

		var toggle_ui = function(callback) {
			// 还原
			if (status != initial_status) {
				set_btns(initial_status);
				$count.html(initial_count);
				status = initial_status;
				callback && callback();
				return;
			}

			// 取消赞同
			if (initial_status == 1) {
				set_btns(0);
				$count.html(initial_count - 1);
				status = 0;
				callback && callback();
				return;
			}

			// 取消反对
			if (initial_status == -1) {
				set_btns(0);
				$count.html(initial_count + 1);
				status = 0;
				callback && callback();
				return;
			}

			// 赞同/反对
			if (initial_status == 0) {
				if (operation == 'agree') {
					set_btns(1);
					$count.html(initial_count + 1);
					status = 1;
					callback && callback();
					return;
				}
				if (operation == 'disagree') {
					AWS.confirm(_t('确认反对?'), function() {
						set_btns(-1);
						$count.html(initial_count - 1);
						status = -1;
						callback && callback();
						return;
					});
				}
			}
		};

		toggle_ui(function() {
			$.post(G_BASE_URL + '/vote/ajax/' + operation + '/', 'type=' + type + '&item_id=' + item_id, function(result) {
				if (result.err) {
					AWS.alert(result.err);
					toggle_ui();
				}
			}, 'json');
		});
	},

	ask_user: function(ask_user_id, ask_user_name) {
		AWS.submit_redirect(G_BASE_URL + '/publish/', {
			ask_user_id: ask_user_id,
			ask_user_name: ask_user_name
		});
	},

	add_favorite: function(item_type, item_id) {
		AWS.confirm(_t('确认收藏?'), function() {
			AWS.ajax_request(G_BASE_URL + '/favorite/ajax/add_favorite/', {
				'item_id': item_id,
				'item_type': item_type
			}, function() {
				AWS.alert(_t('已收藏的内容可以在「动态 - 我的收藏」里找到'));
			});
		});
	},
}

AWS.Dropdown = {
	// 下拉菜单功能绑定
	bind_dropdown_list: function(selector, type) {
		$(selector).keyup(function(e) {
			if ($(selector).val().length >= 1) {
				if (e.which != 38 && e.which != 40 && e.which != 188 && e.which != 13) {
					AWS.Dropdown.get_dropdown_list($(this), type, $(selector).val());
				}
			} else {
				$(selector).parent().find('.aw-dropdown').hide();
			}

			if (type == 'topic') {
				// 逗号或回车提交
				if (e.which == 188) {
					if ($('.aw-edit-topic-box #aw_edit_topic_title').val() != ',') {
						$('.aw-edit-topic-box #aw_edit_topic_title').val($('.aw-edit-topic-box #aw_edit_topic_title').val().substring(0, $('.aw-edit-topic-box #aw_edit_topic_title').val().length - 1));
						$('.aw-edit-topic-box .aw-dropdown').hide();
						$('.aw-edit-topic-box .add').click();
					}
					return false;
				}

				// 回车提交
				if (e.which == 13) {
					$('.aw-edit-topic-box .aw-dropdown').hide();
					$('.aw-edit-topic-box .add').click();
					return false;
				}

				var lis = $(selector).parent().find('.aw-dropdown-list li');

				//键盘往下
				if (e.which == 40 && lis.is(':visible')) {
					var _index;
					if (!lis.hasClass('active')) {
						lis.eq(0).addClass('active');
					} else {
						$.each(lis, function(i, e) {
							if ($(this).hasClass('active')) {
								$(this).removeClass('active');
								if ($(this).index() == lis.length - 1) {
									_index = 0;
								} else {
									_index = $(this).index() + 1;
								}
							}
						});
						lis.eq(_index).addClass('active');
						$(selector).val(lis.eq(_index).text());
					}
				}

				//键盘往上
				if (e.which == 38 && lis.is(':visible')) {
					var _index;
					if (!lis.hasClass('active')) {
						lis.eq(lis.length - 1).addClass('active');
					} else {
						$.each(lis, function(i, e) {
							if ($(this).hasClass('active')) {
								$(this).removeClass('active');
								if ($(this).index() == 0) {
									_index = lis.length - 1;
								} else {
									_index = $(this).index() - 1;
								}
							}
						});
						lis.eq(_index).addClass('active');
						$(selector).val(lis.eq(_index).text());
					}

				}
			}
		});

		$(selector).blur(function() {
			$(selector).parent().find('.aw-dropdown').delay(500).fadeOut(300);
		});
	},

	// 插入下拉菜单
	set_dropdown_list: function(selector, data, selected) {
		$(selector).append(Hogan.compile(AW_TEMPLATE.dropdownList).render({
			'items': data
		}));

		$(selector + ' .aw-dropdown-list li a').click(function() {
			$('#aw-topic-tags-select').html($(this).text());
		});

		if (selected) {
			$(selector + " .dropdown-menu li a[data-value='" + selected + "']").click();
		}
	},

	/* 下拉菜单数据获取 */
	/*
	 *    type : invite, inbox, topic
	 */
	get_dropdown_list: function(selector, type, data) {
		if (AWS.G.dropdown_list_xhr != '') {
			AWS.G.dropdown_list_xhr.abort(); // 中止上一次ajax请求
		}
		var url;
		switch (type) {
			case 'invite':
			case 'inbox':
				url = G_BASE_URL + '/search/ajax/search/?type=users&q=' + encodeURIComponent(data) + '&limit=10';
				break;

			case 'topic':
				url = G_BASE_URL + '/search/ajax/search/?type=topics&q=' + encodeURIComponent(data) + '&limit=10';
				break;

		}

		AWS.G.dropdown_list_xhr = $.get(url, function(result) {
			if (result.length != 0 && AWS.G.dropdown_list_xhr != undefined) {
				$(selector).parent().find('.aw-dropdown-list').html(''); // 清空内容
				switch (type) {
					case 'topic':
						$.each(result, function(i, a) {
							$(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.editTopicDorpdownList).render({
								'name': a['name']
							}));
						});
						break;

					case 'inbox':
					case 'invite':
						$.each(result, function(i, a) {
							$(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.inviteDropdownList).render({
								'uid': a.uid,
								'name': a.name,
								'img': a.detail.avatar_file
							}));
						});
						break;

				}

				$(selector).parent().find('.aw-dropdown, .aw-dropdown-list').show().children().show();
				$(selector).parent().find('.title').hide();
			} else {
				$(selector).parent().find('.aw-dropdown').show().end().find('.title').html(_t('没有找到相关结果')).show();
				$(selector).parent().find('.aw-dropdown-list').hide();
			}
		}, 'json');

	}
}

AWS.Message = {
	// 检测通知
	check_notifications: function() {
		// 检测登录状态
		if (G_USER_ID == 0) {
			clearInterval(AWS.G.notification_timer);
			return false;
		}

		$.get(G_BASE_URL + '/home/ajax/notifications/', function(result) {
			$('#inbox_unread').html(Number(result.rsm.inbox_num));

			var last_unread_notification = G_UNREAD_NOTIFICATION;

			G_UNREAD_NOTIFICATION = Number(result.rsm.notifications_num);

			if (G_UNREAD_NOTIFICATION > 0) {
				if (G_UNREAD_NOTIFICATION != last_unread_notification) {
					// 加载消息列表
					AWS.Message.load_notification_list();

					// 给导航label添加未读消息数量
					$('#notifications_unread').html(G_UNREAD_NOTIFICATION);
				}

				document.title = '(' + (Number(result.rsm.notifications_num) + Number(result.rsm.inbox_num)) + ') ' + document_title;

				$('#notifications_unread').show();
			} else {
				if ($('#header_notification_list').length) {
					$("#header_notification_list").html('<p class="aw-padding10" align="center">' + _t('没有未读通知') + '</p>');
				}

				if ($("#index_notification").length) {
					$("#index_notification").fadeOut();
				}

				document.title = document_title;

				$('#notifications_unread').hide();
			}

			// 私信
			if (Number(result.rsm.inbox_num) > 0) {
				$('#inbox_unread').show();
			} else {
				$('#inbox_unread').hide();
			}

		}, 'json');
	},

	// 阅读通知
	read_notification: function(selector, notification_id, reload) {
		if (notification_id) {
			selector.remove();

			var url = G_BASE_URL + '/notification/ajax/mark_as_read/notification_id-' + notification_id;
		} else {
			if ($("#index_notification").length) {
				$("#index_notification").fadeOut();
			}

			var url = G_BASE_URL + '/notification/ajax/mark_all_as_read/';
		}

		$.get(url, function(result) {
			AWS.Message.check_notifications();

			if (reload) {
				window.location.reload();
			}
		});
	},

	// 重新加载通知列表
	load_notification_list: function() {
		if ($("#index_notification").length) {
			// 给首页通知box内label添加未读消息数量
			$("#index_notification").fadeIn().find('[name=notification_unread_num]').html(G_UNREAD_NOTIFICATION);

			$('#index_notification ul#notification_list').html('<p align="center" style="padding: 15px 0"><img src="' + G_STATIC_URL + '/common/loading_b.gif"/></p>');

			$.get(G_BASE_URL + '/notification/notify/template-list', function(result) {
				$('#index_notification ul#notification_list').html(result);

				AWS.Message.notification_show(5);
			});
		}

		if ($("#header_notification_list").length) {
			$.get(G_BASE_URL + '/notification/notify/', function(result) {
				if (result.length) {
					$("#header_notification_list").html(result);
				} else {
					$("#header_notification_list").html('<p class="aw-padding10" align="center">' + _t('没有未读通知') + '</p>');
				}
			});
		}
	},

	// 控制通知数量
	notification_show: function(length) {
		if ($('#index_notification').length > 0) {
			if ($('#index_notification ul#notification_list li').length == 0) {
				$('#index_notification').fadeOut();
			} else {
				$('#index_notification ul#notification_list li').each(function(i, e) {
					if (i < length) {
						$(e).show();
					} else {
						$(e).hide();
					}
				});
			}
		}
	}
}

AWS.Init = {
	// 初始化话题编辑box
	init_topic_edit_box: function(selector) //selector -> .aw-edit-topic
		{
			$(selector).click(function() {
				var _topic_editor = $(this).parents('.aw-topic-bar'),
					data_id = _topic_editor.attr('data-id'),
					data_type = _topic_editor.attr('data-type');

				if (!_topic_editor.hasClass('active')) {
					_topic_editor.addClass('active');

					if (!_topic_editor.find('.topic-tag .close').length) {
						_topic_editor.find('.topic-tag').append('<a class="close"><i class="icon icon-delete"></i></a>');
					}
				} else {
					_topic_editor.addClass('active');
				}

				// 判断插入编辑box
				if (_topic_editor.find('.aw-edit-topic-box').length == 0) {
					_topic_editor.append(AW_TEMPLATE.editTopicBox);

					// 给编辑box添加按钮添加事件
					_topic_editor.find('.add').click(function() {
						if (_topic_editor.find('#aw_edit_topic_title').val() != '') {
							switch (data_type) {
								case 'publish':
									_topic_editor.find('.tag-bar').prepend('<span class="topic-tag"><a class="text">' + _topic_editor.find('#aw_edit_topic_title').val() + '</a><a class="close" onclick="$(this).parents(\'.topic-tag\').remove();"><i class="icon icon-delete"></i></a><input type="hidden" value="' + _topic_editor.find('#aw_edit_topic_title').val() + '" name="topics[]" /></span>').hide().fadeIn();

									_topic_editor.find('#aw_edit_topic_title').val('');
									break;

								case 'question':
									$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=question&item_id=' + data_id + '&topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function(result) {
										if (result.err) {
											AWS.alert(result.err);

											return false;
										}

										_topic_editor.find('.tag-bar').prepend('<span class="topic-tag" data-id="' + result.rsm.topic_id + '"><a href="' + G_BASE_URL + '/topic/topic_id-' + result.rsm.topic_id + '" class="text">' + _topic_editor.find('#aw_edit_topic_title').val() + '</a><a class="close"><i class="icon icon-delete"></i></a></span>').hide().fadeIn();

										_topic_editor.find('#aw_edit_topic_title').val('');
									}, 'json');
									break;

								case 'article':
									$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=article&item_id=' + data_id + '&topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function(result) {
										if (result.err) {
											AWS.alert(result.err);

											return false;
										}

										_topic_editor.find('.tag-bar').prepend('<span class="topic-tag" data-id="' + result.rsm.topic_id + '"><a href="' + G_BASE_URL + '/topic/topic_id-' + result.rsm.topic_id + '" class="text">' + _topic_editor.find('#aw_edit_topic_title').val() + '</a><a class="close"><i class="icon icon-delete"></i></a></span>').hide().fadeIn();

										_topic_editor.find('#aw_edit_topic_title').val('');
									}, 'json');
									break;

								case 'video':
									$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=video&item_id=' + data_id + '&topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function(result) {
										if (result.err) {
											AWS.alert(result.err);

											return false;
										}

										_topic_editor.find('.tag-bar').prepend('<span class="topic-tag" data-id="' + result.rsm.topic_id + '"><a href="' + G_BASE_URL + '/topic/topic_id-' + result.rsm.topic_id + '" class="text">' + _topic_editor.find('#aw_edit_topic_title').val() + '</a><a class="close"><i class="icon icon-delete"></i></a></span>').hide().fadeIn();

										_topic_editor.find('#aw_edit_topic_title').val('');
									}, 'json');
									break;


								case 'topic':
									$.post(G_BASE_URL + '/topic/ajax/save_related_topic/topic_id-' + data_id, 'topic_title=' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()), function(result) {
										if (result.err) {
											AWS.alert(result.err);

											return false;
										}

										_topic_editor.find('.tag-bar').prepend('<span class="topic-tag"><a href="' + G_BASE_URL + '/favorite/tag-' + encodeURIComponent(_topic_editor.find('#aw_edit_topic_title').val()) + '" class="text">' + _topic_editor.find('#aw_edit_topic_title').val() + '</a><a class="close"><i class="icon icon-delete"></i></a></span>').hide().fadeIn();

										_topic_editor.find('#aw_edit_topic_title').val('');
									}, 'json');
									break;

							}
						}
					});

					// 给编辑box取消按钮添加事件
					_topic_editor.find('.close-edit').click(function() {
						_topic_editor.removeClass('active');
						_topic_editor.find('.aw-edit-topic-box').hide();
						_topic_editor.find('.aw-edit-topic').show();
					});

					AWS.Dropdown.bind_dropdown_list($(this).parents('.aw-topic-bar').find('#aw_edit_topic_title'), 'topic');
				}

				$(this).parents('.aw-topic-bar').find('.aw-edit-topic-box').fadeIn();

				$(this).hide();
			});
		}
}

AWS.init_answer_editor = function() {
	AWS.create_editor(document.getElementById('editor_reply'));
}

// 创建编辑器
AWS.create_editor = function(el, max_btn) {
	if (!el || !window.sceditor) return;

	return sceditor.create(el, {
		width: '100%',
		resizeEnabled: false,
		emoticonsEnabled: false,
		format: 'bbcode',
		icons: 'material',
		style: G_STATIC_URL + '/editor/sceditor/themes/content/default.css',
		toolbar: 'bold,italic,underline,strike|' +
			'left,center|' +
			'bulletlist,orderedlist|' +
			'horizontalrule|' +
			'code,quote|' +
			'image,link,unlink|' +
			'source' + (max_btn ? '|maximize' : '')
	});
};

// 计算延迟显示
AWS.init_later_time_helper = function($input, $label) {
	if (!$input.length || !$label.length) {
		return;
	}
	$input.on('input change', function(e) {
		var time = '';
		var minutes = parseInt($input.val());
		if (minutes && minutes > 0) {
			time = Date.now() + minutes * 60 * 1000;
			time = AWS.format_date(time);
		}
		$label.text(time);
	});
}


$(document).ready(function() {
	// fix form bug...
	$("form[action='']").attr('action', window.location.href);

	// 输入框自动增高
	autosize($('textarea.autosize'));

	//响应式导航条效果
	$('.aw-top-nav .navbar-toggle').click(function() {
		if ($(this).parents('.aw-top-nav').find('.navbar-collapse').hasClass('active')) {
			$(this).parents('.aw-top-nav').find('.navbar-collapse').removeClass('active');
		} else {
			$(this).parents('.aw-top-nav').find('.navbar-collapse').addClass('active');
		}
	});

	//检测通知
	if (typeof(G_NOTIFICATION_INTERVAL) != 'undefined') {
		AWS.Message.check_notifications();
		AWS.G.notification_timer = setInterval('AWS.Message.check_notifications()', G_NOTIFICATION_INTERVAL);
	}

	//话题添加 绑定事件
	AWS.Init.init_topic_edit_box('.aw-edit-topic');

	//话题编辑下拉菜单click事件
	$(document).on('click', '.aw-edit-topic-box .aw-dropdown-list li', function() {
		$(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
		$(this).parents('.aw-edit-topic-box').find('.add').click();
		$(this).parents('.aw-edit-topic-box').find('.aw-dropdown').hide();
	});

	//话题删除按钮
	$(document).on('click', '.topic-tag .close', function() {
		var data_type = $(this).parents('.aw-topic-bar').attr('data-type'),
			data_id = $(this).parents('.aw-topic-bar').attr('data-id'),
			data_url = '',
			topic_id = $(this).parents('.topic-tag').attr('data-id');

		switch (data_type) {
			case 'topic':
				data_url = G_BASE_URL + '/topic/ajax/remove_related_topic/related_id-' + $(this).parents('.topic-tag').attr('data-id') + '__topic_id-' + data_id;
				break;

			default:
				data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
				break;
		}

		if ($(this).parents('.aw-topic-bar').attr('data-url')) {
			data_url = $(this).parents('.aw-topic-bar').attr('data-url');
		}

		if (data_type == 'topic') {
			$.get(data_url);
		} else if (data_url) {
			$.post(data_url, {
				'type': data_type,
				'topic_id': topic_id,
				'item_id': data_id
			}, function(result) {
				$('#aw-ajax-box').empty();
			}, 'json');
		}

		$(this).parents('.topic-tag').remove();

		return false;
	});

	/*if ($('.aw-back-top').length)
	{
	    $(window).scroll(function ()
	    {
	        if ($(window).scrollTop() > ($(window).height() / 2))
	        {
	            $('.aw-back-top').fadeIn();
	        }
	        else
	        {
	            $('.aw-back-top').fadeOut();
	        }
	    });
	}*/

	/* RFC-091 展開閱讀全文 此功能由 onemorecat 提供 */
	$('.mod-body.aw-feed-list .aw-item .markitup-box').each(function() {
		var $contentDiv = $(this);
		if ($contentDiv.text().length > 1000) {
			$contentDiv.addClass('aw-briefly');
			var $button = $('<div class="aw-stretch-content-button-container"><button class="aw-stretch-content-button" type="button"></button></div>');
			$button.click(function() {
				$contentDiv.removeClass('aw-briefly');
				$button.hide();
			});
			$contentDiv.append($button);
		}
	});

});
