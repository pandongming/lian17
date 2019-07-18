$(function() {
				//点击生成显示二维码
				// $(".erw_box").hide();
				// $(".shengc").click(function(){
				// 	$(".erw_box").show();
				// })
				//查看全部js
				// $('.lookall').click(function() {
				// 	if ($(this).siblings('ul.pinfo').css('overflow') == 'hidden') {
				// 		$(this).siblings('ul.pinfo').css({
				// 			'overflow': 'visible',
				// 			'overflow-y': 'scroll',
				// 			'height': '9rem'
				// 		});
				// 		$(this).html('收起全部 <span><img src="https://wx.lian17.com/assets/img/jiantou2.png" class="img-responsive"/> </span> ');
				// 	} else {
				// 		$(this).siblings('ul.pinfo').css({
				// 			'overflow': 'hidden',
				// 			'height': '6rem'
				// 		});
				// 		$(this).html('查看全部 <span><img src="https://wx.lian17.com/assets/img/jiantou.png" class="img-responsive"/> </span> ');
				// 	}
				// });
				$('.lookall2').click(function() {
					if ($(this).siblings('ul.pinfo2').css('overflow') == 'hidden') {
						$(this).siblings('ul.pinfo2').css({
							'overflow': 'visible',
							'overflow-y': 'scroll',
							'height': '9rem'
						});
						$(this).html('收起全部 <span><img src="https://wx.lian17.com/assets/img/jiantou2.png" class="img-responsive"/> </span> ');
					} else {
						$(this).siblings('ul.pinfo2').css({
							'overflow': 'hidden',
							'height': '5rem'
						});
						$(this).html('查看全部 <span><img src="https://wx.lian17.com/assets/img/jiantou.png" class="img-responsive"/> </span> ');
					}
				});
			})