$(function(){
	//加的效果
	$(".add").click(function(){
		var n=$(this).prev().val();
		var num=parseInt(n)+1;
		if(num==0){ return;}
		$(this).prev().val(num);
	});
	//减的效果
	$(".jian").click(function(){
		var n=$(this).next().val();
		var num=parseInt(n)-1;
		if(num==0){ return}
		$(this).next().val(num);
	});
	$('.cqsel .sitem').click(function() {
		$(this).addClass('on').siblings().removeClass('on');
	});
	$('.d-switch').click(function() {
		$(this).toggleClass('active');
	});
})