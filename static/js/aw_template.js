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
							'<a href="{{url}}" class="name" data-id="{{uid}}">{{user_name}}</a>'+
							'&nbsp;'+
							'<em class="{{verified_style}}">{{verified_title}}</em>'+
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
							'<a class="aw-small-text" onclick="AWS.User.compose_message(\'{{user_name}}\');"><i class="icon icon-inbox"></i> ' + _t('私信') + '</a>&nbsp;&nbsp;&nbsp;&nbsp;<a  class="aw-small-text" onclick="AWS.User.ask_user({{uid}}, {{ask_name}});"><i class="icon icon-at"></i> ' + _t('问Ta') + '</a>'+
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

	'commentBox' :
			'<div class="aw-comment-box" id="{{comment_form_id}}">'+
				'<div class="aw-comment-list"><p align="center" class="aw-padding10"><i class="aw-loading"></i></p></div>'+
				'<form action="{{comment_form_action}}" method="post" onsubmit="return false">'+
					'<div class="aw-comment-box-main">'+
						'<textarea class="aw-comment-txt form-control" rows="2" name="message" placeholder="' + _t('讨论一下') + '..."></textarea>'+
						'<div class="aw-comment-box-btn">'+
							'<span class="pull-right">'+
								'<!--<label><input type="checkbox" name="anonymous" value="1"> ' + _t('匿名') + '</label>-->' +
								'<a href="javascript:;" class="btn btn-mini btn-success" onclick="AWS.User.save_comment($(this));">' + _t('讨论') + '</a>'+
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
		'<li class="{{active}} question clearfix"><i class="icon icon-bestbg pull-left"></i><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right aw-small-text">{{discuss_count}} ' + _t('个回复') + '</span></li>',
	'searchDropdownListTopics' :
		'<li class="topic clearfix"><span class="topic-tag" data-id="{{topic_id}}"><a href="{{url}}" class="text">{{name}}</a></span> <span class="pull-right aw-small-text">{{discuss_count}} ' + _t('个讨论') + '</span></li>',
	'searchDropdownListUsers' :
		'<li class="user clearfix"><a href="{{url}}"><img src="{{img}}" />{{name}}<span class="aw-hide-txt">{{intro}}</span></a></li>',
	'searchDropdownListArticles' :
		'<li class="question clearfix"><a class="aw-hide-txt pull-left" href="{{url}}">{{content}} </a><span class="pull-right aw-small-text">{{comments}} ' + _t('条讨论') + '</span></li>',
	'inviteDropdownList' :
		'<li class="user"><a data-url="{{url}}" data-id="{{uid}}" data-actions="{{action}}" data-value="{{name}}"><img class="img" src="{{img}}" />{{name}}</a></li>',
	'editTopicDorpdownList' :
		'<li class="question"><a>{{name}}</a></li>',

	'questionDropdownList' :
		'<li class="question" data-id="{{id}}"><a class="aw-hide-txt" href="{{url}}">{{name}}</a></li>',


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

	'confirmBox' :
		'<div class="modal fade alert-box aw-tips-box aw-confirm-box">'+
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
						'<a class="btn btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
						'<a class="btn btn-success yes">' + _t('确定') + '</a>'+
					'</div>'+
				'</div>'+
			'</div>'+
		'</div>',

	'promptBox' :
			'<div class="modal fade alert-box aw-share-box aw-prompt-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">{{title}}</h3>'+
						'</div>'+
							'<div class="modal-body">'+
								'<input class="form-control" type="text" value="{{message}}" />'+
							'</div>'+
							'<div class="modal-footer">'+
								'<a class="btn btn-large btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
								'<a class="btn btn-large btn-success yes">' + _t('确定') + '</a>'+
							'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

	'textBox' :
			'<div class="modal fade alert-box aw-share-box aw-text-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title" id="myModalLabel">{{title}}</h3>'+
						'</div>'+
							'<div class="modal-body">'+
								'<textarea class="form-control" rows="5">{{message}}</textarea>'+
							'</div>'+
							'<div class="modal-footer">'+
								'<a class="btn btn-large btn-gray" data-dismiss="modal" aria-hidden="true">' + _t('取消') + '</a>'+
								'<a class="btn btn-large btn-success yes">' + _t('确定') + '</a>'+
							'</div>'+
					'</div>'+
				'</div>'+
			'</div>',

}
