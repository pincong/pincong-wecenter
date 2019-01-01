window.onload = function()
{
	if (/MSIE 6/.test(navigator.userAgent) || /MSIE 7/.test(navigator.userAgent))
	{
		var newNode = document.createElement("div");
			newNode.setAttribute('id', 'browser-not-support');
			newNode.innerHTML = '您的浏览器不受支持, 建议更新或者使用其他浏览器来访问';
		document.getElementsByTagName('body')[0].appendChild(newNode);
	}
}