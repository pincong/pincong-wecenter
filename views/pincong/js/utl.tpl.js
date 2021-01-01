(function(window) {
'use strict';

var UTL = {};

UTL.create_editor = function(textarea) {
	if (!textarea.length || !window.sceditor) return;
	textarea = textarea[0];
	if (textarea._sceditor) return;

	return sceditor.create(textarea, {
		width: '100%',
		resizeEnabled: false,
		emoticonsEnabled: false,
		format: 'bbcode',
		icons: 'material',
		style: '<?php echo G_STATIC_URL; ?>/editor/sceditor/themes/content/default.css',
		toolbar: 'bold,italic,underline,strike|' +
			'left,center|' +
			'bulletlist,orderedlist|' +
			'horizontalrule|' +
			'code,quote|' +
			'image,link,unlink|' +
			'source|maximize'
	});
};

UTL.init_textarea = function(textarea, wysiwyg) {
	if (wysiwyg) return UTL.create_editor(textarea);
	if (!textarea.length || !window.autosize) return;
	autosize(textarea);

};

function change_navbar(color) {
	var el = $('header.navbar');
	if (!el.length) return;
	var primary_btn = el.find('.cls_primary_button');
	var secondary_btn = el.find('.cls_secondary_button');
	var logo_img = el.find('.navbar-brand img');
	el.removeClass('shadow shadow-sm navbar-light navbar-dark bg-light bg-dark bg-primary');
	primary_btn.removeClass('btn-primary btn-outline-primary btn-secondary btn-success');
	secondary_btn.removeClass('btn-primary btn-outline-primary btn-secondary btn-success');
	if (color == 'light') {
		el.addClass('shadow-sm navbar-light bg-light');
		primary_btn.addClass('btn-outline-primary');
		secondary_btn.addClass('btn-primary');
		logo_img.attr('src', "<?php echo S::get('logo_dark'); ?>");
	}
	else if (color == 'dark') {
		el.addClass('shadow navbar-dark bg-dark');
		primary_btn.addClass('btn-primary');
		secondary_btn.addClass('btn-success');
		logo_img.attr('src', "<?php echo S::get('logo_light'); ?>");
	}
	else {
		el.addClass('shadow navbar-dark bg-primary');
		primary_btn.addClass('btn-secondary');
		secondary_btn.addClass('btn-success');
		logo_img.attr('src', "<?php echo S::get('logo_light'); ?>");
	}
}

UTL.change_theme = function(name, navbar, css) {
	change_navbar(navbar);

	$('#id_stylesheet_theme').attr('href', css);

	$.cookie('<?php echo G_COOKIE_PREFIX; ?>_theme', name, {
		path: '/'
	});
}

window.UTL = UTL;
})(this);
