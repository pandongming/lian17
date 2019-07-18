$(function(){
    //------------------------下拉刷新-------------------------------
    //定义的全局变量
    var disY, startY, endY;
    //触摸事件开始时触发
    $('#Wrapper').on('touchstart', function (e) {
        startY = e.changedTouches[0].pageY;
    });
    //触摸事件移动中时触发
    $('#Wrapper').on('touchmove', function (e) {
        endY = e.changedTouches[0].pageY;
        disY = endY - startY;
        if (disY > 30) {
            $('.status').css({
                display: 'block',
                height: disY + 'px',
            });
        }
    });
    //触摸事件结束时触发
    $('#Wrapper').on('touchend', function (e) {
        endY = e.changedTouches[0].pageY;
        disY = endY - startY;
        if (disY > 62) {
            //定义一个定时器，返回下拉到一定的高度
            var timer = setInterval(function () {
                disY -= 13;
                if (disY <= 60) {
                    $('.status').css({
                        height: 52 + 'px',
                    });
                    clearInterval(timer);
                    refresh();
                }
                $('.status').css({
                    height: disY + 'px',
                });
            }, 65);
        }
    });
    //请求刷新数据

    function refresh() {
        $('.status').css({
            display: 'none',
            height:0
        });
        var pageIndex =1;
        $.ajax({
            url: "{:url('WxcuserScoreLog/getScore')}",
            type: 'GET',
            dataType: 'json',
        })
        .done(function(res) {
            console.log(res);
        })
        .fail(function() {
            console.log("error");
        })
        .always(function() {
            console.log("complete");
        });


        var t = setTimeout(function () {
            for (var i = 0; i < 13; i++) {
                $('#Wrapper ul').append('<li>' + '添加的数据:' + parseInt(i + 1) + '</li>');
            }
            $('.status').css({
                display: 'none',
                height:0
            });
            clearTimeout(t)
        }, 1000);
    }

    //--------------上拉加载更多---------------
    //获取滚动条当前的位置
    function getScrollTop() {
        var scrollTop = 0;
        if (document.documentElement && document.documentElement.scrollTop) {
            scrollTop = document.documentElement.scrollTop;
        } else if (document.body) {
            scrollTop = document.body.scrollTop;
        }
        return scrollTop;
    }

    //获取当前可视范围的高度
    function getClientHeight() {
        var clientHeight = 0;
        if (document.body.clientHeight && document.documentElement.clientHeight) {
            clientHeight = Math.min(document.body.clientHeight, document.documentElement.clientHeight);
        } else {
            clientHeight = Math.max(document.body.clientHeight, document.documentElement.clientHeight);
        }
        return clientHeight;
    }

    //获取文档完整的高度
    function getScrollHeight() {
        return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    }

    //滚动事件触发
    window.onscroll = function () {
        if (getScrollTop() + getClientHeight() + 60 >= getScrollHeight()) {
            $("#pullUp").show();
            refresh();
            //  $("#pullUp").show(1000,refresh)
         //    alert("正在加载中...")
        }
    };

})