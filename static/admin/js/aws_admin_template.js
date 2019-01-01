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

}