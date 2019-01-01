var AW_TEMPLATE = {
	'loadingBox':
		'<div id="aw-loading" class="collapse">'+
			'<div id="aw-loading-box"></div>'+
		'</div>',

	'loadingMiniBox':
		'<div id="aw-loading-mini-box"></div>',

	'userCard':
			'<div id="aw-card-tips" class="aw-card-tips aw-card-tips-user">'+
				'<div class="aw-mod">'+
					'<div class="mod-head">'+
						'<a href="{{url}}" class="img">'+
							'<img src="{{avatar_file}}" alt="" />'+
						'</a>'+
						'<p class="title clearfix">'+
							'<a href="{{url}}" class="name pull-left" data-id="{{uid}}">{{user_name}}</a>'+
							'<i class="{{verified_enterprise}} pull-left" title="{{verified_title}}"></i>'+
						'</p>'+
						'<p class="aw-user-center-follow-meta">'+
							'<span><i class="icon icon-prestige"></i> : <em class="aw-text-color-green">{{reputation}}</em></span>'+
							'<span><i class="icon icon-agree"></i> : <em class="aw-text-color-orange">{{agree_count}}</em></span>'+
						'</p>'+
					'</div>'+
					'<div class="mod-body">'+
						'<p>{{signature}}</p>'+
					'</div>'+
					'<div class="mod-footer clearfix">'+
						'<span>'+
							'<a class="text-color-999" onclick="AWS.dialog(\'inbox\', \'{{user_name}}\');"><i class="icon icon-inbox"></i> ' + _t('私信') + '</a>&nbsp;&nbsp;&nbsp;&nbsp;<a  class="text-color-999" onclick="AWS.User.ask_user({{uid}}, {{ask_name}});"><i class="icon icon-at"></i> ' + _t('问Ta') + '</a>'+
						'</span>'+
						'<a class="btn btn-normal btn-success follow {{focus}} pull-right" onclick="AWS.User.follow($(this), \'user\', {{uid}});"><span>{{focusTxt}}</span> <em>|</em> <b>{{fansCount}}</b></a>'+
					'</div>'+
				'</div>'+
			'</div>',

	'topicCard' :
			'<div id="aw-card-tips" class="aw-card-tips aw-card-tips-topic">'+
				'<div class="aw-mod">'+
					'<div class="mod-head">'+
						'<a href="{{url}}" class="img">'+
							'<img src="{{topic_pic}}" alt="" title=""/>'+
						'</a>'+
						'<p class="title">'+
							'<a href="{{url}}" class="name" data-id="{{topic_id}}">{{topic_title}}</a>'+
						'</p>'+
						'<p class="desc">'+
							'{{topic_description}}'+
						'</p>'+
					'</div>'+
					'<div class="mod-footer">'+
						'<span>'+ _t('讨论数') + ': {{discuss_count}}</span>'+
						'<a class="btn btn-normal btn-success follow {{focus}} pull-right" onclick="AWS.User.follow($(this), \'topic\', {{topic_id}});"><span>{{focusTxt}}</span> <em>|</em> <b>{{focus_count}}</b></a>'+
					'</div>'+
				'</div>'+
			'</div>',

	'alertBox' :
			'<div class="modal fade alert-box aw-tips-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<p>{{message}}</p>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

	'articleCommentBox' :
		'<div class="aw-article-replay-box clearfix">'+
			'<form action="'+ G_BASE_URL +'/publish/ajax/publish_article_comment/" onsubmit="return false;" method="post">'+
				'<div class="mod-body">'+
					'<input type="hidden" name="at_uid" value="{{at_uid}}">'+
					'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
					'<input type="hidden" name="article_id" value="{{article_id}}" />'+
					'<textarea placeholder="' + _t('写下你的评论...') + '" class="form-control" id="comment_editor" name="message" rows="2"></textarea>'+
				'</div>'+
				'<div class="mod-footer">'+
					'<label class="pull-right">'+
						'&nbsp;'+
						'<input type="checkbox" value="1" name="anonymous" />' +
						'&nbsp;'+
						_t('匿名') +
						'&nbsp;'+
						'<a href="javascript:;" onclick="AWS.ajax_post($(this).parents(\'form\'));" class="btn btn-normal btn-success btn-submit">' + _t('回复') + '</a>'+
					'</label>'+
					'<label class="pull-right">'+
						'<input class="pull-right form-control" type="text" name="later" placeholder="' + _t('分钟') + '" /> ' +
						'&nbsp;'+
						_t('延迟显示') +
						'&nbsp;'+
					'</label>'+
				'</div>'+
			'</form>'+
		'</div>',

	'favoriteBox' :
		'<div class="modal fade alert-box aw-tips-box">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<form id="favorite_form" action="' + G_BASE_URL + '/favorite/ajax/add_favorite/" method="post" onsubmit="return false;">'+
						'<input type="hidden" name="item_id" value="{{item_id}}" />'+
						'<input type="hidden" name="item_type" value="{{item_type}}" />'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<p>' + _t('已收藏') + '</p>'+
						'</div>'+
					'</form>'+
				'</div>'+
			'</div>'+
		'</div>',

	'questionRedirect' :
		'<div class="modal fade alert-box aw-question-redirect-box">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<div class="modal-header">'+
						'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
						'<h3 class="modal-title" id="myModalLabel">' + _t('问题重定向至') + '</h3>'+
					'</div>'+
					'<div class="modal-body">'+
						'<p>' + _t('将问题重定向至') + '</p>'+
						'<div class="aw-question-drodpwon">'+
							'<input id="question-input" class="form-control" type="text" data-id="{{data_id}}" placeholder="' + _t('搜索问题或问题 ID') + '" />'+
							'<div class="aw-dropdown"><p class="title">' + _t('没有找到相关结果') + '</p><ul class="aw-dropdown-list"></ul></div>'+
						'</div>'+
						'<p class="clearfix"><a href="javascript:;" class="btn btn-large btn-success pull-right" onclick="$(\'.alert-box\').modal(\'hide\');">' + _t('放弃操作') + '</a></p>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	'publishBox' :
			'<div class="modal fade alert-box aw-publish-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('发起问题') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<div class="alert alert-danger collapse error_message"><i class="icon icon-delete"></i> <em></em></div>'+
							'<form action="' + G_BASE_URL + '/publish/ajax/publish_question/" method="post" id="quick_publish" onsubmit="return false">'+
								'<input type="hidden" id="quick_publish_category_id" name="category_id" value="{{category_id}}" />'+
								'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
								'<input type="hidden" name="ask_user_id" value="{{ask_user_id}}" />'+
								'<div>'+
									'<textarea class="form-control" placeholder="' + _t('写下你的问题') + '..." rows="1" name="title" id="quick_publish_question_content" onkeydown="if (event.keyCode == 13) { return false; }"></textarea>'+
									'<div class="aw-publish-suggest-question collapse">'+
										'<p class="text-color-999">你的问题可能已经有答案</p>'+
										'<ul class="aw-dropdown-list">'+
										'</ul>'+
									'</div>'+
								'</div>'+
								'<textarea name="message" class="form-control" rows="4" placeholder="' + _t('问题背景、条件等详细信息') + '..."></textarea>'+
								'<div class="aw-publish-title">'+
									'<div class="dropdown" id="quick_publish_category_chooser">'+
										'<div class="dropdown-toggle" data-toggle="dropdown">'+
											'<span id="aw-topic-tags-select" class="aw-hide-txt">' + _t('选择分类') + '</span>'+
											'<a><i class="icon icon-down"></i></a>'+
										'</div>'+
									'</div>'+
								'</div>'+
								'<div class="aw-topic-bar" data-type="publish">'+
									'<div class="tag-bar clearfix">'+
										'<span class="aw-edit-topic"><i class="icon icon-edit"></i>' + _t('编辑话题') + '</span>'+
									'</div>'+
								'</div>'+
							'</form>'+
						'</div>'+
						'<div class="modal-footer">'+
							'<span class="pull-right">'+
								'<a data-dismiss="modal" aria-hidden="true" class="text-color-999">' + _t('取消') + '</a>'+
								'<button class="btn btn-large btn-success" onclick="AWS.ajax_post($(\'#quick_publish\'), AWS.ajax_processer, \'error_message\');">' + _t('发起') + '</button>'+
							'</span>'+
							'<a href="javascript:;" tabindex="-1" onclick="$(\'form#quick_publish\').attr(\'action\', \'' + G_BASE_URL + '/publish/\');$.each($(\'#quick_publish textarea\'), function (i, e){if ($(this).val() == $(this).attr(\'placeholder\')){$(this).val(\'\');}});document.getElementById(\'quick_publish\').submit();" class="pull-left">' + _t('高级模式') + '</a>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

	'inbox' :
			'<div class="modal fade alert-box aw-inbox">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('新私信') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<div class="alert alert-danger collapse error_message"> <i class="icon icon-delete"></i> <em></em></div>'+
							'<form action="' + G_BASE_URL + '/inbox/ajax/send/" method="post" id="quick_publish" onsubmit="return false">'+
								'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
								'<input id="invite-input" class="form-control" type="text" placeholder="' + _t('搜索用户') + '" name="recipient" value="{{recipient}}" />'+
								'<div class="aw-dropdown">'+
									'<p class="title">' + _t('没有找到相关结果') + '</p>'+
									'<ul class="aw-dropdown-list">'+
									'</ul>'+
								'</div>'+
								'<textarea class="form-control" name="message" rows="3" placeholder="' + _t('私信内容...') + '"></textarea>'+
							'</form>'+
						'</div>'+
						'<div class="modal-footer">'+
							'<a data-dismiss="modal" aria-hidden="true" class="text-color-999">' + _t('取消') + '</a>'+
							'<button class="btn btn-large btn-success" onclick="AWS.ajax_post($(\'#quick_publish\'), AWS.ajax_processer, \'error_message\');">' + _t('发送') + '</button>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

	'editTopicBox' :
		'<div class="aw-edit-topic-box form-inline">'+
			'<input type="text" class="form-control" id="aw_edit_topic_title" autocomplete="off"  placeholder="' + _t('创建或搜索添加新话题') + '...">'+
			'<a class="btn btn-normal btn-success add">' + _t('添加') + '</a>'+
			'<a class="btn btn-normal btn-gray close-edit">' + _t('取消') + '</a>'+
			'<div class="aw-dropdown">'+
				'<p class="title">' + _t('没有找到相关结果') + '</p>'+
				'<ul class="aw-dropdown-list">'+
				'</ul>'+
			'</div>'+
		'</div>',

	'ajaxData' :
		'<div class="modal fade alert-box aw-topic-edit-note-box aw-question-edit" aria-labelledby="myModalLabel" role="dialog">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<div class="modal-header">'+
						'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
						'<h3 class="modal-title" id="myModalLabel">{{title}}</h3>'+
					'</div>'+
					'<div class="modal-body">'+
						'{{data}}'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	'commentBox' :
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'<form action="{{comment_form_action}}" method="post" onsubmit="return false">'+
					'<div class="aw-comment-box-main">'+
						'<textarea class="aw-comment-txt form-control" rows="2" name="message" placeholder="' + _t('评论一下') + '..."></textarea>'+
						'<div class="aw-comment-box-btn">'+
							'<span class="pull-right">'+
								'<!--<label><input type="checkbox" name="anonymous" value="1"> ' + _t('匿名') + '</label>-->' +
								'<a href="javascript:;" class="btn btn-mini btn-success" onclick="AWS.User.save_comment($(this));">' + _t('评论') + '</a>'+
							'</span>'+
						'</div>'+
					'</div>'+
				'</form>'+
			'</div>',

	'commentBoxClose' :
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
			'</div>',

	'dropdownList' :
		'<div aria-labelledby="dropdownMenu" role="menu" class="aw-dropdown">'+
			'<ul class="aw-dropdown-list">'+
			'{{#items}}'+
				'<li><a data-value="{{id}}">{{title}}</a></li>'+
			'{{/items}}'+
			'</ul>'+
		'</div>',

	'searchDropdownListQuestions' :
		'<li class="{{active}} question clearfix"><i class="icon icon-bestbg pull-left"></i><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right text-color-999">{{discuss_count}} ' + _t('个回复') + '</span></li>',
	'searchDropdownListTopics' :
		'<li class="topic clearfix"><span class="topic-tag" data-id="{{topic_id}}"><a href="{{url}}" class="text">{{name}}</a></span> <span class="pull-right text-color-999">{{discuss_count}} ' + _t('个讨论') + '</span></li>',
	'searchDropdownListUsers' :
		'<li class="user clearfix"><a href="{{url}}"><img src="{{img}}" />{{name}}<span class="aw-hide-txt">{{intro}}</span></a></li>',
	'searchDropdownListArticles' :
		'<li class="question clearfix"><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right text-color-999">{{comments}} ' + _t('条评论') + '</span></li>',
	'inviteDropdownList' :
		'<li class="user"><a data-url="{{url}}" data-id="{{uid}}" data-actions="{{action}}" data-value="{{name}}"><img class="img" src="{{img}}" />{{name}}</a></li>',
	'editTopicDorpdownList' :
		'<li class="question"><a>{{name}}</a></li>',
	'questionRedirectList' :
		'<li class="question"><a class="aw-hide-txt" onclick="AWS.ajax_request({{url}})">{{name}}</a></li>',
	'questionDropdownList' :
		'<li class="question" data-id="{{id}}"><a class="aw-hide-txt" href="{{url}}">{{name}}</a></li>',

	'inviteUserList' :
		'<li>'+
			'<a class="pull-right btn btn-mini btn-default" onclick="disinvite_user($(this),{{uid}});$(this).parent().detach();">' + _t('取消邀请') + '</a>'+
			'<a class="aw-user-name" data-id="{{uid}}">'+
				'<img src="{{img}}" alt="" />'+
			'</a>'+
			'<span class="aw-text-color-666">{{name}}</span>'+
		'</li>',

	'alertImg' :
		'<div class="modal fade alert-box aw-tips-box aw-alert-img-box">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<div class="modal-header">'+
						'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
						'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
					'</div>'+
					'<div class="modal-body">'+
						'<p class="hide {{hide}}">{{message}}</p>'+
						'<img src="{{url}}" />'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	'confirmBox' :
		'<div class="modal fade alert-box aw-confirm-box">'+
			'<div class="modal-dialog">'+
				'<div class="modal-content">'+
					'<div class="modal-header">'+
						'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
						'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
					'</div>'+
					'<div class="modal-body">'+
						'{{message}}'+
					'</div>'+
					'<div class="modal-footer">'+
						'<a class="btn btn-gray" data-dismiss="modal" aria-hidden="true">取消</a>'+
						'<a class="btn btn-success yes">确定</a>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	'textBox' :
			'<div class="modal fade alert-box aw-share-box aw-share-box-message aw-text-box" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">{{title}}</h3>'+
						'</div>'+
							'<div class="modal-body">'+
								'<div class="alert alert-danger collapse error_message"><i class="icon icon-delete"></i> <em></em></div>'+
								'<textarea class="form-control" rows="5">{{message}}</textarea>'+
							'</div>'+
							'<div class="modal-footer">'+
								'<a data-dismiss="modal" aria-hidden="true" class="btn btn-large btn-gray">' + _t('取消') + '</a>'+
								'<a data-dismiss="modal" aria-hidden="true" class="btn btn-large btn-success yes">' + _t('确定') + '</a>'+
							'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

}
