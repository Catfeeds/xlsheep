<?php
function get_area_select($title = '', $areaid = 0, $extend = '', $deep = 0, $id = 1) {
	$m = M('region');
	$parents = array();
	if($areaid) {
		$r = $m->where('id='.$areaid)->field('child,arrparentid')->find();
		$parents = explode(',', $r['arrparentid']);
		if($r['child']) $parents[] = $areaid;
	} else {
		$parents[] = 0;
	}
	$select = '';
	foreach($parents as $k => $v) {
		if($deep && $deep <= $k) break;
		$v = intval($v);
		$select .= '<select onchange="load_area(this.value, '.$id.');" '.$extend.'>';
		if($title) $select .= '<option value="0">'.$title.'</option>';
		$result = $m->where('parent_id='.$v)->field('id,name')->order('id ASC')->select();
		foreach($result as $kk => $vv) {
			$selectid = isset($parents[$k+1]) ? $parents[$k+1] : $areaid;
			$selected = $vv['id'] == $selectid ? ' selected' : '';
			$select .= '<option value="'.$vv['id'].'"'.$selected.'>'.$vv['name'].'</option>';
		}
		$select .= '</select> ';
	}
	return $select;
}

function ajax_area_select($name = 'areaid', $title = '', $areaid = 0, $extend = '', $deep = 0) {
	global $area_id;
	if($area_id) {
		$area_id++;
	} else {
		$area_id = 1;
	}
	$areaid = intval($areaid);
	$deep = intval($deep);
	$select = '';
	$select .= '<input name="'.$name.'" id="areaid_'.$area_id.'" type="hidden" value="'.$areaid.'"/>';
	$select .= '<span id="load_area_'.$area_id.'">'.get_area_select($title, $areaid, $extend, $deep, $area_id).'</span>';
	$select .= '<script type="text/javascript">';
	if($area_id == 1) $select .= 'var area_title = new Array;';
	$select .= 'area_title['.$area_id.']=\''.$title.'\';';
	if($area_id == 1) $select .= 'var area_areaid = new Array;';
	$select .= 'area_areaid['.$area_id.']=\''.$areaid.'\';';
	if($area_id == 1) $select .= 'var area_deep = new Array;';
	$select .= 'area_deep['.$area_id.']=\''.$deep.'\';';
	$select .= '</script>';
	if($area_id == 1) $select .= '<script type="text/javascript" src="/Public/Admin/js/area.js"></script>';
	return $select;
}
