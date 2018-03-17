<?php
namespace Admin\Controller;

class AjaxController extends BaseController{
	
	protected $pagesize = 10;
	
	public function _initialize()
	{
		//你可以在此覆盖父类方法
		parent::_initialize();
	}
	
	public function getArea() {
		$area_title= I('area_title', '请选择');
		$area_extend= I('area_extend', '');
		$areaid = I('areaid', 0 ,'intval');
		$area_deep = I('area_deep', 0 , 'intval');
		$area_id = I('area_id', 0, 'intval');
		$area_deep = I('area_deep', 0, 'intval');
		$select = get_area_select($area_title, $areaid, $area_extend, $area_deep, $area_id);
		
		$info['status'] = 1;
		$info['msg'] = $select;
		$this->ajaxReturn($info);
	}
}