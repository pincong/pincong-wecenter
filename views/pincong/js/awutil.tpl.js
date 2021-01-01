(function(window) {
'use strict';

var AWUtil = {};

AWUtil.change_theme = function(name) {
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

window.AWUtil = AWUtil;
})(this);
