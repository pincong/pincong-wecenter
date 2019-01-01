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
            }
            else if (result.rsm && result.rsm.url)
            {
                window.location = decodeURIComponent(result.rsm.url);
            }
            else if (result.errno == 1)
            {
                window.location.reload();
            }
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
		if (typeof (result.errno) == 'undefined')
		{
			AWS.alert(result);
		}
		else if (result.errno != 1)
		{
			switch (type)
			{
				case 'default':
				case 'comments_form':
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

			    	if ($('#captcha').length)
			    	{
			    		$('#captcha').click();
			    	}
				break;
			}
		}
		else
		{
			if (type == 'comments_form')
			{
				AWS.reload_comments_list(result.rsm.item_id, result.rsm.item_id, result.rsm.type_name);
	        	$('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form input').val('');
	        	$('#aw-comment-box-' + result.rsm.type_name + '-' + result.rsm.item_id + ' form textarea').val('');
	        	$('.aw-comment-box-btn .btn-success').removeClass('disabled');
			}

			if (result.rsm && result.rsm.url)
	        {
	            window.location = decodeURIComponent(result.rsm.url);
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

	/**
	 *	公共弹窗
	 *	inbox       : 私信
	 */
	dialog: function (type, data, callback)
	{
	    switch (type)
	    {
		    case 'alertImg':
		    	var template = Hogan.compile(AW_TEMPLATE.alertImg).render(
		    	{
		    		'hide': data.hide,
		    		'url': data.url,
		    		'message': data.message
		    	});
		    break;


		    case 'inbox':
		        var template = Hogan.compile(AW_TEMPLATE.inbox).render(
		        {
		            'recipient': data
		        });
		    break;


			case 'ajaxData':
				var template = AW_TEMPLATE.ajaxData.replace('{{title}}', data.title).replace('{{data}}', '<div id="aw_dialog_ajax_data"></div>');
			break;

			case 'imagePreview':
				var template = AW_TEMPLATE.ajaxData.replace('{{title}}', data.title).replace('{{data}}', '<p align="center"><img src="' + data.image + '" alt="" style="max-width:520px" /></p>');
			break;

			case 'confirm':
				var template = Hogan.compile(AW_TEMPLATE.confirmBox).render(
				{
					'message': data.message
				});
			break;

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

		        case 'ajaxData':
			    	$.get(data.url, function (result) {
						$('#aw_dialog_ajax_data').html(result);
					});
		    	break;

		    	case 'confirm':
		    	//后台根话题
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

	// 兼容placeholder
	check_placeholder: function(selector)
	{
		$.each(selector, function()
		{
			if (typeof ($(this).attr("placeholder")) != "undefined")
            {
                $(this).attr('data-placeholder', 'true');

                if ($(this).val() == '')
                {
	                $(this).addClass('aw-placeholder').val($(this).attr("placeholder"));
                }

                $(this).focus(function () {
                    if ($(this).val() == $(this).attr('placeholder'))
                    {
                        $(this).removeClass('aw-placeholder').val('');
                    }
                });

                $(this).blur(function () {
                    if ($(this).val() == '')
                    {
                        $(this).addClass('aw-placeholder').val($(this).attr('placeholder'));
                    }
                });
            }
		});
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
	cashUserData: [],
	cashTopicData: [],
	card_box_hide_timer: '',
	card_box_show_timer: '',
	dropdown_list_xhr: '',
	loading_timer: '',
	loading_bg_count: 12,
	loading_mini_bg_count: 9,
	notification_timer: ''
}


AWS.Dropdown =
{
	// 下拉菜单功能绑定
	bind_dropdown_list: function(selector, type)
	{
	    if (type == 'search')
	    {
	        $(selector).focus(function()
	        {
	            $(selector).parent().find('.aw-dropdown').show();
	        });
	    }
	    $(selector).keyup(function(e)
	    {
	        if (type == 'search')
	        {
	            $(selector).parent().find('.search').show().children('a').text($(selector).val());
	        }
	        if ($(selector).val().length >= 1)
	        {
	        	if (e.which != 38 && e.which != 40 && e.which != 188 && e.which != 13)
	        	{
	            	AWS.Dropdown.get_dropdown_list($(this), type, $(selector).val());
	        	}
	        }
	        else
	        {
	           $(selector).parent().find('.aw-dropdown').hide();
	        }

	        if (type == 'topic')
	        {
	        	// 逗号或回车提交
	            if (e.which == 188)
	            {
	                if ($('.aw-edit-topic-box #aw_edit_topic_title').val() != ',')
	                {
	                    $('.aw-edit-topic-box #aw_edit_topic_title').val( $('.aw-edit-topic-box #aw_edit_topic_title').val().substring(0,$('.aw-edit-topic-box #aw_edit_topic_title').val().length-1));
	                    $('.aw-edit-topic-box .aw-dropdown').hide();
	                    $('.aw-edit-topic-box .submit-edit').click();
	                }
	                return false;
	            }

	            // 回车提交
	            if (e.which == 13)
	            {
	            	$('.aw-edit-topic-box .aw-dropdown').hide();
	                $('.aw-edit-topic-box .submit-edit').click();
	            	return false;
	            }

	            var lis = $(selector).parent().find('.aw-dropdown-list li');

	            //键盘往下
	            if (e.which == 40 && lis.is(':visible'))
	            {
	            	var _index;
	            	if (!lis.hasClass('active'))
	            	{
	            		lis.eq(0).addClass('active');
	            	}
	            	else
	            	{
	            		$.each(lis, function (i, e)
	            		{
	            			if ($(this).hasClass('active'))
							{
		            			$(this).removeClass('active');
		            			if ($(this).index() == lis.length - 1)
		            			{
		            				_index = 0;
		            			}
		            			else
		            			{
		            				_index = $(this).index() + 1;
		            			}
		            		}
	            		});
	            		lis.eq(_index).addClass('active');
	            		$(selector).val(lis.eq(_index).text());
	            	}
	            }

	            //键盘往上
	            if (e.which == 38 && lis.is(':visible'))
	            {
	            	var _index;
	            	if (!lis.hasClass('active'))
	            	{
	            		lis.eq(lis.length - 1).addClass('active');
	            	}
	            	else
	            	{
	            		$.each(lis, function (i, e)
	            		{
	            			if ($(this).hasClass('active'))
							{
								$(this).removeClass('active');
								if ($(this).index() == 0)
								{
									_index = lis.length - 1;
								}
								else
								{
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

	    $(selector).blur(function()
	    {
	        $(selector).parent().find('.aw-dropdown').delay(500).fadeOut(300);
	    });
	},

	// 插入下拉菜单
	set_dropdown_list: function(selector, data, selected)
	{
	    $(selector).append(Hogan.compile(AW_TEMPLATE.dropdownList).render(
	    {
	        'items': data
	    }));

	    $(selector + ' .aw-dropdown-list li a').click(function ()
	    {
	        $('#aw-topic-tags-select').html($(this).text());
	    });

	    if (selected)
	    {
	        $(selector + " .dropdown-menu li a[data-value='" + selected + "']").click();
	    }
	},

	/* 下拉菜单数据获取 */
	/*
	*    type : search, publish, invite, inbox, topic_question, topic
	*/
	get_dropdown_list: function(selector, type, data)
	{
	    if (AWS.G.dropdown_list_xhr != '')
	    {
	        AWS.G.dropdown_list_xhr.abort(); // 中止上一次ajax请求
	    }
	    var url;
	    switch (type)
	    {
	        case 'search' :
	            url = G_BASE_URL + '/search/ajax/search/?q=' + encodeURIComponent(data) + '&limit=5';
	        break;

	        case 'publish' :
	            url = G_BASE_URL + '/search/ajax/search/?type=questions&q=' + encodeURIComponent(data) + '&limit=5';
	        break;

	        case 'invite' :
	        case 'inbox' :
	            url = G_BASE_URL + '/search/ajax/search/?type=users&q=' + encodeURIComponent(data) + '&limit=10';
	        break;

	        case 'topic_question' :
	            url = G_BASE_URL + '/search/ajax/search/?type=questions,articles&q=' + encodeURIComponent(data) + '&topic_ids=' + CONTENTS_RELATED_TOPIC_IDS + '&limit=50';
	        break;

	        case 'topic' :
	            url = G_BASE_URL + '/search/ajax/search/?type=topics&q=' + encodeURIComponent(data) + '&limit=10';
	        break;

	        case 'questions' :
	        	url = G_BASE_URL + '/search/ajax/search/?type=questions&q=' + encodeURIComponent(data) + '&limit=10';
	        break;

	        case 'articles' :
	        	url = G_BASE_URL + '/search/ajax/search/?type=articles&q=' + encodeURIComponent(data) + '&limit=10';
	        break;

	    }

	    AWS.G.dropdown_list_xhr = $.get(url, function (result)
	    {
	        if (result.length != 0 && AWS.G.dropdown_list_xhr != undefined)
	        {
	            $(selector).parent().find('.aw-dropdown-list').html(''); // 清空内容
	            switch (type)
	            {
	                case 'search' :
	                    $.each(result, function (i, a)
	                    {
	                        switch (a.type)
	                        {
	                            case 'questions':
	                                if (a.detail.best_answer > 0)
	                                {
	                                    var active = 'active';
	                                }
	                                else
	                                {
	                                    var active = ''
	                                }

	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListQuestions).render(
	                                {
	                                    'url': a.url,
	                                    'active': active,
	                                    'content': a.name,
	                                    'discuss_count': a.detail.answer_count
	                                }));
	                                break;

								case 'articles':
	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListArticles).render(
	                                {
	                                    'url': a.url,
	                                    'content': a.name,
	                                    'comments': a.detail.comments
	                                }));
	                                break;

	                            case 'topics':
	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListTopics).render(
	                                {
	                                    'url': a.url,
	                                    'name': a.name,
	                                    'discuss_count': a.detail.discuss_count,
	                                    'topic_id': a.detail.topic_id
	                                }));
	                                break;

	                            case 'users':
	                                if (a.detail.signature == '')
	                                {
	                                    var signature = _t('暂无介绍');
	                                }
	                                else
	                                {
	                                    var signature = a.detail.signature;
	                                }

	                                $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.searchDropdownListUsers).render(
	                                {
	                                    'url': a.url,
	                                    'img': a.detail.avatar_file,
	                                    'name': a.name,
	                                    'intro': signature
	                                }));
	                                break;
	                        }
	                    });
	                break;

	                case 'publish' :
	                case 'topic_question' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.questionDropdownList).render(
	                        {
	                            'url': a.url,
	                            'name': a.name
	                        }));
	                    });
	                	break;

	                case 'topic' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.editTopicDorpdownList).render(
	                        {
	                            'name': a['name']
	                        }));
	                    });
	                	break;

	                case 'questions' :
	                case 'articles' :
	                	$.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.questionDropdownList).render(
	                        {
	                            'url': '#',
	                            'name': a['name']
	                        }));
	                    });
	                	break;

	                case 'inbox' :
	                case 'invite' :
	                    $.each(result, function (i, a)
	                    {
	                        $(selector).parent().find('.aw-dropdown-list').append(Hogan.compile(AW_TEMPLATE.inviteDropdownList).render(
	                        {
	                            'uid': a.uid,
	                            'name': a.name,
	                            'img': a.detail.avatar_file
	                        }));
	                    });
	                	break;


	            }
	            if (type == 'publish')
	            {
	                $(selector).parent().find('.aw-publish-suggest-question, .aw-publish-suggest-question .aw-dropdown-list').show();
	            }
	            else
	            {
	                $(selector).parent().find('.aw-dropdown, .aw-dropdown-list').show().children().show();
	                $(selector).parent().find('.title').hide();
	                // 关键词高亮
	                $(selector).parent().find('.aw-dropdown-list li.question a').highText(data, 'b', 'active');
	            }
	        }else
	        {
	            $(selector).parent().find('.aw-dropdown').show().end().find('.title').html(_t('没有找到相关结果')).show();
	            $(selector).parent().find('.aw-dropdown-list, .aw-publish-suggest-question').hide();
	        }
	    }, 'json');

	}
}


function _t(string, replace)
{
    if (typeof (aws_lang) != 'undefined')
    {
        if (typeof (aws_lang[string]) != 'undefined')
        {
            string = aws_lang[string];
        }
    }

    if (replace)
    {
        string = string.replace('%s', replace);
    }

    return string;
};

// jQuery扩展
(function ($)
{
	$.fn.extend(
    {
    	insertAtCaret: function (textFeildValue)
	    {
	        var textObj = $(this).get(0);
	        if (document.all && textObj.createTextRange && textObj.caretPos)
	        {
	            var caretPos = textObj.caretPos;
	            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == '' ?
	                textFeildValue + '' : textFeildValue;
	        }
	        else if (textObj.setSelectionRange)
	        {
	            var rangeStart = textObj.selectionStart,
	            	rangeEnd = textObj.selectionEnd,
	            	tempStr1 = textObj.value.substring(0, rangeStart),
	            	tempStr2 = textObj.value.substring(rangeEnd);
	            textObj.value = tempStr1 + textFeildValue + tempStr2;
	            textObj.focus();
	            var len = textFeildValue.length;
	            textObj.setSelectionRange(rangeStart + len, rangeStart + len);
	            textObj.blur();
	        }
	        else
	        {
	            textObj.value += textFeildValue;
	        }
	    },

	    highText: function (searchWords, htmlTag, tagClass)
	    {
	        return this.each(function ()
	        {
	            $(this).html(function high(replaced, search, htmlTag, tagClass)
	            {
	                var pattarn = search.replace(/\b(\w+)\b/g, "($1)").replace(/\s+/g, "|");

	                return replaced.replace(new RegExp(pattarn, "ig"), function (keyword)
	                {
	                    return $("<" + htmlTag + " class=" + tagClass + ">" + keyword + "</" + htmlTag + ">").outerHTML();
	                });
	            }($(this).text(), searchWords, htmlTag, tagClass));
	        });
	    },

	    outerHTML: function (s)
	    {
	        return (s) ? this.before(s).remove() : jQuery("<p>").append(this.eq(0).clone()).html();
	    }
    });

	$.extend(
	{
		// 滚动到指定位置
		scrollTo : function (type, duration, options)
		{
			if (typeof type == 'object')
			{
				var type = $(type).offset().top
			}

			$('html, body').animate({
				scrollTop: type
			}, {
				duration: duration,
				queue: options.queue
			});
		}
	})

})(jQuery);