var oA=document.getElementsByTagName("*");
for (var i=0;i<oA.length;i++) {
	oA[i].addEventListener("touchstart",function(){this.className="hover";},false);
	oA[i].addEventListener("touchend",function(){this.className="";},false);
}
