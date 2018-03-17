$.extend($.fn,{
    fnTimeCountDown:function(d){
        this.each(function(){
            var $this = $(this);
            var o = {
                hm: $this.find(".hm"),
                sec: $this.find(".sec"),
                mini: $this.find(".mini"),
                hour: $this.find(".hour"),
            };
            var f = {
                haomiao: function(n){
                    if(n < 10)return "" + n.toString();
                    if(n < 100)return "0" + n.toString();
                    return n.toString();
                },
                zero: function(n){
                    var _n = parseInt(n, 10);//解析字符串,返回整数
                    if(_n > 0){
                        if(_n <= 9){
                            _n = "0" + _n
                        }
                        return String(_n);
                    }else{
                        return "00";
                    }
                },
                dv: function(){
                    var _d = $this.data("end") || d;
                    var now = new Date(),
                        endDate = new Date(_d);
                    //现在将来秒差值
                    //alert(future.getTimezoneOffset());
                    var dur = (endDate - now.getTime()) / 1000 , mss = endDate - now.getTime() ,pms = {
                        hm:"0",
                        sec: "00",
                        mini: "00",
                        hour: "00",
                    };
                    if(mss > 0){
                        pms.hm = f.haomiao(parseInt((mss % 1000)/100));
                        pms.sec = f.zero(dur % 60);
                        pms.mini = Math.floor((dur / 60)) > 0? f.zero(Math.floor((dur / 60)) % 60) : "00";
                        pms.hour = Math.floor((dur / 3600)) > 0? f.zero(Math.floor((dur / 3600))) : "00";
                    }else{
                        pms.hour=pms.mini=pms.sec="00";
                        pms.hm = "0";
                        //alert('结束了');
                        $.ajax({
                      	 　  type: "GET",
                      	　    url: "/Home/Index/actg",
                      	   dataType:'json',
                      	　      success: function(data) {
                      	　	 window.location.reload();
                      	　 }
                      });
                        return;
                    }
                    return pms;
                },
                ui: function(){
                    if(o.hm){
                        o.hm.html(f.dv().hm);
                    }
                    if(o.sec){
                        o.sec.html(f.dv().sec);
                    }
                    if(o.mini){
                        o.mini.html(f.dv().mini);
                    }
                    if(o.hour){
                        o.hour.html(f.dv().hour);
                    }
                    setTimeout(f.ui, 1);
                }
            };
            f.ui();
        });
    }
});