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

UTL.change_theme = function(name) {
	!name && (name = 'default');
	var url = '';
	if (name != 'default') {
		url = '<?php echo G_STATIC_URL; ?>/themes/' + name + '.css';
	}
	$('#id_stylesheet_theme').attr('href', url);

	$.cookie('<?php echo G_COOKIE_PREFIX; ?>_theme', name, {
		path: '/'
	});
}

window.UTL = UTL;
})(this);
