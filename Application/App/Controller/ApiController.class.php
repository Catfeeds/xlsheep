<?php
namespace App\Controller;
use Think\Controller;

class ApiController extends Controller {
    /*
     * 获取地区
     */
    public function getRegion(){
    	$parent_id = I('get.parent_id', 0, 'intval');
    	if($parent_id<=0) {
    		exit('0');
    	}
        $selected = I('get.selected',0);        
        $data = M('region')->where("parent_id=$parent_id")->select();
        $html = '';
        if($data){
            foreach($data as $h){
            	if($h['id'] == $selected){
            		$html .= "<option value='{$h['id']}' selected>{$h['name']}</option>";
            	}
                $html .= "<option value='{$h['id']}'>{$h['name']}</option>";
            }
        }
        echo $html;
    }
    

    public function getTwon(){
    	$parent_id = I('get.parent_id', 0, 'intval');
    	if($parent_id<=0) {
    		exit('0');
    	}
    	$data = M('region')->where("parent_id=$parent_id")->select();
    	$html = '';
    	if($data){
    		foreach($data as $h){
    			$html .= "<option value='{$h['id']}'>{$h['name']}</option>";
    		}
    	}
    	if(empty($html)){
    		echo '0';
    	}else{
    		echo $html;
    	}
    }
}