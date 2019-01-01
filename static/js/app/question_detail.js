$(function()
{
	//邀请回答按钮操作
	$('.aw-question-detail .aw-invite-replay').click(function()
	{
		$('.aw-question-detail .aw-comment-box, .aw-question-detail .aw-question-related-box').hide();
		if ($('.aw-question-detail .aw-invite-box').is(':visible'))
		{
			$('.aw-question-detail .aw-invite-box').fadeOut();
		}
		else
		{
			$('.aw-question-detail .aw-invite-box').fadeIn();
		}
	});

	//邀请初始化
	for (var i = 0; i < 4; i++)
	{
		$('.aw-question-detail .aw-invite-box ul li').eq(i).show();
	}

	// 邀请翻页
	if ($('.aw-question-detail .aw-invite-box .mod-body ul li').length <=4 )
	{
		//长度小于4翻页隐藏
		$('.aw-question-detail .aw-invite-box .mod-footer').hide();
	}
	else
	{
		//邀请上一页
		$('.aw-question-detail .aw-invite-box .prev').click(function()
		{
			if (!$(this).hasClass('active'))
			{
				var flag = 0, list = $('.aw-question-detail .aw-invite-box ul li');

				$.each(list, function (i, e)
				{
					if ($(this).is(':visible') == true)
					{
						flag = $(this).index();

						return false;
					}
				});

				list.hide();

				for (var i = 0; i < 4; i++)
				{
					flag--;

					if (flag >= 0)
					{
						list.eq(flag).show();
					}
				}
				if (flag <= 0)
				{
					$('.aw-question-detail .aw-invite-box .prev').addClass('active');
				}

				$('.aw-question-detail .aw-invite-box .next').removeClass('active');
			}
		});

		//邀请下一页
		$('.aw-question-detail .aw-invite-box .next').click(function()
		{
			if (!$(this).hasClass('active'))
			{
				var flag = 0, list = $('.aw-question-detail .aw-invite-box ul li');

				$.each(list, function (i, e)
				{
					if ($(this).is(':visible') == true)
					{
						flag = $(this).index();
					}
				});

				list.hide();

				for (var i = 0; i < 4; i++)
				{
					if (flag + 1 <= list.length)
					{
						flag++;

						list.eq(flag).show();

						if (flag + 1 == list.length)
						{
							$('.aw-question-detail .aw-invite-box .next').addClass('active');
						}
					}
				}

		 		$('.aw-question-detail .aw-invite-box .prev').removeClass('active');

			}
		});
	}

	//邀请用户下拉绑定
	AWS.Dropdown.bind_dropdown_list($('.aw-invite-box #invite-input'), 'invite');

	//邀请用户回答点击事件
	$(document).on('click', '.aw-invite-box .aw-dropdown-list a', function () {
		AWS.User.invite_user($(this),$(this).find('img').attr('src'));
	});

});

function one_click_add_topic(selector, topic_title, question_id)
{
	$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=question&item_id=' + question_id + '&topic_title=' + topic_title, function (result) {
		if (result.err)
		{
			AWS.alert(result.err);
		}
		else
		{
			$('.aw-topic-bar .tag-bar').prepend('<span class="topic-tag" data-id="' + result.rsm.topic_id + '"><a class="text" href="topic/' + result.rsm.topic_id + '">' + topic_title + '</a></a></span>').hide().fadeIn();

			selector.hide();
		}
	}, 'json');
}
