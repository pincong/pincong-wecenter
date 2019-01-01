function ImageUploader(options) {
	this.url = options.url;
	this.field_name = options.field_name;
	this.upload_button = options.upload_button;
	this.image_container = options.image_container;
	this.loading_status = options.loading_status

	this.createIframe();
	this.createForm();
}


ImageUploader.prototype.createIframe = function() {
	var _this = this;

	this.iframe_name = 'imageuploader-iframe-' + Date.now();

	var iframe = $('<iframe>', {
		name:  this.iframe_name,
		width: 0,
		height: 0,
		frameborder: 0,
		scrolling: 'no',
		css: {'display': 'none'}
	});

	iframe.on('load', function() {
		_this.handleResponse(this);
	});

	$('body').append(iframe);
}


ImageUploader.prototype.createForm = function() {
	var _this = this;

	var form = $('<form>', {
		action: this.url,
		method: 'post',
		target: this.iframe_name,
		enctype: 'multipart/form-data'
	});

	var input = $('<input>', {
		type: 'file',
		name: this.field_name,
		css: {'display': 'none'}
	});

	input.on('change', function() {
		_this.handleSelection(this);
	});

	this.upload_button.on('click', function() {
		input.click();
	});

	form.append(input);

	$('body').append(form);

	this.form = form;
}


ImageUploader.prototype.handleResponse = function(element) {
	if (this.loading_status) this.loading_status.hide();

	var response = $(element).contents().text();
	if (!response) return;
	try {
		response = JSON.parse(response);
	} catch(e) {
		return;
	}

	if (response.err) {
		AWS.alert(response.err);
		return;
	}

	if (response.rsm && response.rsm.thumb) {
		if (this.image_container) {
			if (this.image_container.attr('src')) {
				this.image_container.attr('src', response.rsm.thumb);
			} else {
				this.image_container.css({'background' : 'url(' + response.rsm.thumb + ')'});
			}
		}
	}
}

ImageUploader.prototype.handleSelection = function(element) {
	if (!element.files || !element.files.length) return;

	if (this.loading_status) this.loading_status.show();

	this.form.submit();
}
