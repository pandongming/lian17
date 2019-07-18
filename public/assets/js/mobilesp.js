function page() {
	//通过navigator判断是否是移动设备
	if((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
		//在移动端
		(function(doc, win) {
			//  html
			var docEl = doc.documentElement,
				resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
				recalc = function() {
					var clientWidth = docEl.clientWidth;
					if(!clientWidth) return;
					clientWidth = (clientWidth > 768) ? 768 : clientWidth;
					docEl.style.fontSize = 23 * (clientWidth / 375) + 'px'; //这个10可以根据自己使用的数据来调整
				};
			if(!doc.addEventListener) return;
			win.addEventListener(resizeEvt, recalc, false);
			recalc();
		})(document, window);
		//移动端  文字适配
	} else { //如果是pc端我们可以像微信公众号那样，设置最大宽度为740px
		//  	window.location.href="prompt.html"
		document.documentElement.style.maxWidth = 640 + 'px';
		document.documentElement.style.margin = "0 auto"
		//PC端
	}
}
page();