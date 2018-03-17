// 修改指定表的指定字段值
function changeTableVal(table,id_name,id_value,field,obj)
{
		var src = "";
		 if($(obj).attr('src').indexOf("cancel.png") > 0 )
		 {          
				src = '/Public/App/images/yes.png';
				var value = 1;
				
		 }else{                    
				src = '/Public/App/images/cancel.png';
				var value = 0;
		 }                                                 
		$.ajax({
				url:"/index.php?m=Admin&c=Index&a=changeTableVal&table="+table+"&id_name="+id_name+"&id_value="+id_value+"&field="+field+'&value='+value,			
				success: function(data){									
					$(obj).attr('src',src);           
				}
		});		
}

// 修改指定表的排序字段
function updateSort(table,id_name,id_value,field,obj)
{		       
 		var value = $(obj).val();
		$.ajax({
				url:"/index.php?m=Admin&c=Index&a=changeTableVal&table="+table+"&id_name="+id_name+"&id_value="+id_value+"&field="+field+'&value='+value,			
				success: function(data){	
					$.App.alert('success','更新成功');
				}
		});		
}