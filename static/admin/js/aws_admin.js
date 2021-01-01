// 后台分类移动设置
AW_TEMPLATE['adminCategoryMove'] =
	'<div class="modal fade alert-box aw-category-move-box">' +
	'<div class="modal-dialog">' +
	'<form method="post" id="settings_form" action="' + G_BASE_URL + '/admin/ajax/move_category_contents/">' +
	'<div class="modal-content">' +
	'<div class="modal-header">' +
	'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>' +
	'<h3 class="modal-title">{{name}}</h3>' +
	'</div>' +
	'<div class="modal-body">' +
	'<div class="alert alert-danger collapse error_message"></div>' +
	'<div class="row">' +
	'<div class="col-md-6 collapse">' +
	'<select class="from-category form-control" name="from_id">' +
	'{{#items}}' +
	'<option value="{{id}}">{{title}}</option>' +
	'{{/items}}' +
	'</select>' +
	'</div>' +
	'<div class="col-md-12">' +
	'<select name="target_id" class="form-control">' +
	'{{#items}}' +
	'<option value="{{id}}">{{title}}</option>' +
	'{{/items}}' +
	'</select>' +
	'</div>' +
	'</div>' +
	'</div>' +
	'<div class="modal-footer">' +
	'<a class="btn btn-default" aria-hidden="true" data-dismiss="modal">' + _t('取消') + '</a>' +
	'<a class="btn btn-success yes" onclick="AWS.ajax_post($(\'{{from_id}}\'), AWS.ajax_processer, \'error_message\')">' + _t('确定') + '</a>' +
	'</div>' +
	'</div>' +
	'</form>' +
	'</div>' +
	'</div>';

AWS.ajax_post = function(formEl, processer, type) {
	if (typeof(processer) != 'function') processer = AWS.ajax_processer;
	if (!type) type = 'default';

	AWS.loading('show');

	$.ajax({
		type: 'post',
		url: formEl.attr('action'),
		data: formEl.serialize() + '&_post_type=ajax',
		dataType: 'json',
		timeout: 60000,
		success: function(result) {
			AWS.loading('hide');
			processer(type, result);
		},
		error: function(error) {
			AWS.loading('hide');
			alert(_t('发生错误, 返回的信息:') + ' ' + error.responseText);
		},
	});
}

// ajax提交callback
AWS.ajax_processer = function(type, result) {
	if (result.err) {
		switch (type) {
			case 'default':
				AWS.alert(result.err);
				break;

			case 'ajax_post_alert':
			case 'ajax_post_modal':
			case 'error_message':
				if (!$('.error_message').length) {
					alert(result.err);
				} else if ($('.error_message em').length) {
					$('.error_message em').html(result.err);
				} else {
					$('.error_message').html(result.err);
				}

				if ($('.error_message').css('display') != 'none') {
					AWS.shake($('.error_message'));
				} else {
					$('.error_message').fadeIn();
				}

				break;
		}
	} else {
		if (result.url) {
			window.location = result.url;
		} else {
			switch (type) {
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
}

AWS.dialog = function(type, data, callback) {
	switch (type) {
		// 后台分类移动设置
		case 'adminCategoryMove':
			var template = Hogan.compile(AW_TEMPLATE.adminCategoryMove).render({
				'items': data.option,
				'name': data.name,
				'from_id': data.from_id
			});
			break;
	}

	if (template) {
		if ($('.alert-box').length) {
			$('.alert-box').remove();
		}

		$('#aw-ajax-box').html(template).show();

		switch (type) {
			//后台分类移动设置
			case 'adminCategoryMove':
				$('.aw-confirm-box .yes, .aw-category-move-box .yes').click(function() {
					if (callback) {
						callback();
					}

					$(".alert-box").modal('hide');

					return false;
				});
				break;
		}

		$(".alert-box").modal('show');
	}
}
