!function(){function i(i,a){clearTimeout(i.tid),i.tid=setTimeout(function(){i.call(a)},300)}for(var a=[{name:"客服-丽丽",qq:"2850792362"},{name:"客服-冰冰",qq:"2850792360"},{name:"客服-小米",qq:"2850792361"},{name:"客服-清清",qq:"2850792363"},{name:"客服-冬冬",qq:"2850792367"}],o="",n=0;n<a.length;n++)o+='<dd><a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin='+a[n].qq+'&site=qq&menu=yes"><em class="kefu0'+(n+1)+'"></em>'+a[n].name+"</a></dd>";var s='<ul class="m-bar">    <li>        <div class="click-box" id="J_BarKf">            <em class="iconfont bar-ico">&#xe60b;</em>            <div class="fly-box" id="J_Kefu" style="display: none;">                <p class="kefu-box"><i class="iconfont">&#xe60e;</i>客服热线：</p>                <p class="tel">400-833-1311</p>                <p class="kefu-box"><i class="iconfont tengxun">&#xe60d;</i>在线客服：</p>                <dl>'+o+'                </dl>            </div>            <span class="bar-arrow"></span>        </div>    </li>    <li>        <div class="click-box" id="J_BarQr">            <em class="iconfont bar-ico">&#xe603;</em>            <div class="fly-box" style="text-align: center;display: none;" id="J_QRL">                <img class="down-qr" src="/assets/web/img/ewmsm.jpg" alt=""/>                <p style="color:#333;margin-bottom: 5px;">养羊啦微信号</p>            </div>            <span class="bar-arrow"></span>        </div>    </li>    <li><div class="click-box" id="J_BackTop" style="display: none;"><em class="iconfont bar-ico">&#xe60c;</em></div></li></ul>';$("body").append(s),$("#J_BarKf").hover(function(){$(this).find(".bar-arrow").show(),$("#J_Kefu").show()},function(){$(this).find(".bar-arrow").hide(),$("#J_Kefu").hide()}),$("#J_BarQr").hover(function(){$(this).find(".bar-arrow").show(),$("#J_QRL").show()},function(){$(this).find(".bar-arrow").hide(),$("#J_QRL").hide()});var e=$("#J_BackTop");$(window).on("scroll",function(){i(function(){$(window).scrollTop()>500?e.show():e.hide()},window)}),e.on("click",function(){return $("body,html").animate({scrollTop:0},500),!1})}();