var AW_TEMPLATE = {
	'loadingBox':
		'<div id="aw-loading" class="collapse">'+
			'<div id="aw-loading-box"></div>'+
		'</div>',

	'alertBox' : 
			'<div class="modal fade alert-box aw-tips-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<p>{{message}}</p>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

	'inbox' :
			'<div class="modal fade alert-box aw-inbox">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('新私信') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<div class="alert alert-danger collapse error_message"> <i class="icon icon-delete"></i> <em></em></div>'+
							'<form action="' + G_BASE_URL + '/inbox/ajax/send/" method="post" id="quick_publish" onsubmit="return false">'+
								'<input type="hidden" name="post_hash" value="' + G_POST_HASH + '" />'+
								'<input id="invite-input" class="form-control" type="text" placeholder="' + _t('搜索用户') + '" name="recipient" value="{{recipient}}" />'+
								'<div class="aw-dropdown">'+
									'<i class="aw-icon i-dropdown-triangle"></i>'+
									'<p class="title">' + _t('没有找到相关结果') + '</p>'+
									'<ul class="aw-dropdown-list">'+
									'</ul>'+
								'</div>'+
								'<textarea class="form-control" name="message" rows="3" placeholder="' + _t('私信内容...') + '"></textarea>'+
							'</form>'+
						'</div>'+
						'<div class="modal-footer">'+
							'<a data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
							'<button class="btn btn-large btn-success" onclick="AWS.ajax_post($(\'#quick_publish\'), AWS.ajax_processer, \'error_message\');">' + _t('发送') + '</button>'+
						'</div>'+
					'</div>'+
				'</div>'+
			'</div>',
	
	'editTopicBox' : 
		'<div class="aw-edit-topic-box form-inline">'+
			'<input type="text" class="form-control" id="aw_edit_topic_title" autocomplete="off"  placeholder="' + _t('创建或搜索添加新话题') + '...">'+
			'<a class="btn btn-large btn-success submit-edit">' + _t('添加') + '</a>'+
			'<a class="btn btn-large btn-default close-edit">' + _t('取消') + '</a>'+
			'<div class="aw-dropdown">'+
				'<i class="aw-icon i-dropdown-triangle active"></i>'+
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
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
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
								'<a href="javascript:;" class="btn btn-mini btn-success" onclick="AWS.User.save_comment($(this));">' + _t('评论') + '</a>'+
								'<a href="javascript:;" class="btn btn-mini btn-default close-comment-box">' + _t('取消') + '</a>'+
							'</span>'+
						'</div>'+
					'</div>'+
				'</form>'+
				'<i class="i-dropdown-triangle"></i>'+
			'</div>',
			
	'commentBoxClose' : 
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'<i class="i-dropdown-triangle"></i>'+
			'</div>',

	'dropdownList' : 
		'<div aria-labelledby="dropdownMenu" role="menu" class="aw-dropdown">'+
			'<i class="i-dropdown-triangle"></i>'+
			'<ul class="aw-dropdown-list">'+
			'{{#items}}'+
				'<li><a data-value="{{id}}">{{title}}</a></li>'+
			'{{/items}}'+
			'</ul>'+
		'</div>',

	'searchDropdownListQuestions' : 
		'<li class="{{active}} question clearfix"><i class="icon icon-bestbg pull-left"></i><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right text-color-999">{{discuss_count}} ' + _t('个回复') + '</span></li>',
	'searchDropdownListTopics' : 
		'<li class="topic clearfix"><a href="{{url}}" class="aw-topic-name" data-id="{{topic_id}}"><span>{{name}}</span></a> <span class="pull-right text-color-999">{{discuss_count}} ' + _t('个讨论') + '</span></li>',
	'searchDropdownListUsers' : 
		'<li class="user clearfix"><a href="{{url}}"><img src="{{img}}" />{{name}}<span class="aw-hide-txt">{{intro}}</span></a></li>',
	'searchDropdownListArticles' : 
		'<li class="question clearfix"><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right text-color-999">{{comments}} ' + _t('条评论') + '</span></li>',
	'inviteDropdownList' :
		'<li class="user"><a data-url="{{url}}" data-id="{{uid}}" data-actions="{{action}}" data-value="{{name}}"><img class="img" src="{{img}}" />{{name}}</a></li>',
	'editTopicDorpdownList' : 
		'<li class="question"><a>{{name}}</a></li>',

	'questionDropdownList' : 
		'<li class="question" data-id="{{id}}"><a class="aw-hide-txt" target="_blank" _href="{{url}}">{{name}}</a></li>',

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
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
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
						'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
						'<h3 class="modal-title" id="myModalLabel">' + _t('提示信息') + '</h3>'+
					'</div>'+
					'<div class="modal-body">'+
						'{{message}}'+
					'</div>'+
					'<div class="modal-footer">'+
						'<a class="btn btn-default" data-dismiss="modal" aria-hidden="true">取消</a>'+
						'<a class="btn btn-success yes">确定</a>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	// 后台分类移动设置
	'adminCategoryMove' :
		'<div class="modal fade alert-box aw-category-move-box">'+
			'<div class="modal-dialog">'+
				'<form method="post" id="settings_form" action="' + G_BASE_URL + '/admin/ajax/move_category_contents/">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
							'<h3 class="modal-title" id="myModalLabel">{{name}}</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<div class="alert alert-danger collapse error_message"></div>'+
							'<div class="row">'+
								'<div class="col-md-6 collapse">'+
									'<select class="from-category form-control" name="from_id">'+
										'{{#items}}'+
											'<option value="{{id}}">{{title}}</option>'+
										'{{/items}}'+
									'</select>'+
								'</div>'+
								'<div class="col-md-12">'+
									'<select name="target_id" class="form-control">'+
										'{{#items}}'+
											'<option value="{{id}}">{{title}}</option>'+
										'{{/items}}'+
									'</select>'+
								'</div>'+
							'</div>'+
						'</div>'+
						'<div class="modal-footer">'+
							'<a class="btn btn-default" aria-hidden="true" data-dismiss="modal">' + _t('取消') + '</a>'+
							'<a class="btn btn-success yes" onclick="AWS.ajax_post($(\'{{from_id}}\'), AWS.ajax_processer, \'error_message\')">' + _t('确定') + '</a>'+
						'</div>'+
					'</div>'+
				'</form>'+
			'</div>'+
		'</div>',

	// 后台微信群发消息弹窗
	'adminWechatSendMsg' :
		'<div class="modal fade alert-box aw-wechat-send-message">'+
			'<div class="modal-dialog">'+
				'<form method="post" id="settings_form" action="' + G_BASE_URL + '/admin/ajax/move_category_contents/">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>'+
							'<h3 class="modal-title" id="myModalLabel">' + _t('搜索消息内容') + '</h3>'+
						'</div>'+
						'<div class="modal-body">'+
							'<div class="aw-dropdown-box">'+
								'<div class="alert alert-danger collapse error_message"></div>'+
								'<input type="text" class="form-control search-input" />'+
								'<div class="aw-dropdown"><p class="title">' + _t('没有找到相关结果') + '</p><ul class="aw-dropdown-list"><li><a>123</a></li></ul></div>'+
							'</div>'+
						'</div>'+
						'<div class="modal-footer">'+
							'<a class="btn btn-default" aria-hidden="true" data-dismiss="modal">' + _t('取消') + '</a>'+
						'</div>'+
					'</div>'+
				'</form>'+
			'</div>'+
		'</div>'
}