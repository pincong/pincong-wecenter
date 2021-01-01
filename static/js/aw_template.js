var AW_TEMPLATE = {
	'loadingBox':
		'<div id="aw-loading" class="collapse">'+
			'<div id="aw-loading-box"></div>'+
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

	'dropdownList' :
		'<div aria-labelledby="dropdownMenu" role="menu" class="aw-dropdown">'+
			'<ul class="aw-dropdown-list">'+
			'{{#items}}'+
				'<li><a data-value="{{id}}">{{title}}</a></li>'+
			'{{/items}}'+
			'</ul>'+
		'</div>',

	'inviteDropdownList' :
		'<li class="user"><a data-url="{{url}}" data-id="{{uid}}" data-actions="{{action}}" data-value="{{name}}"><img class="img" src="{{img}}" />{{name}}</a></li>',

	'editTopicDorpdownList' :
		'<li class="question"><a>{{name}}</a></li>',


	'alertBox' :
			'<div class="modal fade alert-box aw-tips-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title">' + _t('提示信息') + '</h3>'+
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
						'<h3 class="modal-title">' + _t('提示信息') + '</h3>'+
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
							'<h3 class="modal-title">{{title}}</h3>'+
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

	'passwordPromptBox' :
			'<div class="modal fade alert-box aw-share-box aw-prompt-box">'+
				'<div class="modal-dialog">'+
					'<div class="modal-content">'+
						'<div class="modal-header">'+
							'<a type="button" class="close icon icon-delete" data-dismiss="modal" aria-hidden="true"></a>'+
							'<h3 class="modal-title">{{title}}</h3>'+
						'</div>'+
							'<div class="modal-body">'+
								'<input class="form-control" type="password" value="{{message}}" />'+
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
							'<h3 class="modal-title">{{title}}</h3>'+
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
