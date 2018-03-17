var area_id;
function load_area(areaid, id) {
	area_id = id; area_areaid[id] = areaid;
	$.ajax({
        type: 'post',
        url: "/Admin/Ajax/getArea",
        data: {area_id:area_id,areaid:areaid},
        global: false,
        dataType: "json",
        //beforeSend: $.App.loading(),
        success: function (info) {
            //$.App.loading();
            if (info.status) {
            	$('#areaid_'+area_id).val(area_areaid[area_id]);
            	if(info.msg) {
            		$('#load_area_'+area_id).html(info.msg);
            	}
            } else {
                $.App.alert('danger', info.msg);
            }
        }, //成功时执行Response函数
        error: function (info) {
            alert('操作失败，请重试或检查网络连接！')
        }//失败时调用函数
	});
}