<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Template\TagLib;
use Think\Template\TagLib;

/**
 * 自定义标签
 */

class Wemall extends TagLib {
	protected $tags = array(
		'adv' => array('attr'=>'limit,order,where,item','close'=>1),
	);
	
	
	/**
	 * 广告标签
	 * @access public
	 * @param array $tag 标签属性
	 * @param string $content  标签内容
	 * @return string
	 */
	public function _adv($tag,$content){
     	$order = $tag['order']; //排序
        $limit = !empty($tag['limit']) ? $tag['limit'] : '1'; 
        $where = $tag['where']; //查询条件
        $item  = !empty($tag['item']) ? $tag['item'] : 'item';// 返回的变量item	
        $key  =  !empty($tag['key']) ? $tag['key'] : 'key';// 返回的变量key
        $pid  =  !empty($tag['pid']) ? $tag['pid'] : '0';// 返回的变量key
           
        $str = '<?php ';
        $str .= '$pid ='.$pid.';';
        $str .= '$ad_position = M("ad_position")->getField("position_id,position_name,ad_width,ad_height");';
        $str .= '$result = M("ad")->where("pid=$pid  and enabled = 1 and start_time < '.strtotime(date('Y-m-d H:00:00')).' and end_time > '.strtotime(date('Y-m-d H:00:00')).' ")->order("orderby desc")->limit("'.$limit.'")->select();';
        $str .= '

if(!in_array($pid,array_keys($ad_position)) && $pid)
{
  M("ad_position")->add(array(
         "position_id"=>$pid,
         "position_name"=>CONTROLLER_NAME."页面自动增加广告位 $pid ",
         "is_open"=>1,
         "position_desc"=>CONTROLLER_NAME."页面",
  ));
}


$c = '.$limit.'- count($result); //  如果要求数量 和实际数量不一样 并且编辑模式
if($c > 0 && $_GET[edit_ad])
{
    for($i = 0; $i < $c; $i++) // 还没有添加广告的时候
    {
      $result[] = array(
          "ad_code" => "/Public/App/images/not_adv.jpg",
          "ad_link" => "/index.php?m=Admin&c=Ad&a=ad&pid=$pid",
          "title"   =>"暂无广告图片",
          "not_adv" => 1,
          "target" => 0,
      );  
    }
}
foreach($result as $'.$key.'=>$'.$item.'):       
    
    $'.$item.'[position] = $ad_position[$'.$item.'[pid]]; 
	if($'.$item.'[not_adv] == 0) {
		$pic = M("Upload_img")->where("id=$'.$item.'[ad_pic]")->find();
        $'.$item.'[imgurl] = $pic ? (__ROOT__ "/Upload/" . $pic[savepath] . $pic[savename]) : "";
	}
    if($_GET[edit_ad] && $'.$item.'[not_adv] == 0 )
    {
        $'.$item.'[style] = "filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity: 0.5; opacity: 0.5"; // 广告半透明的样式
        $'.$item.'[ad_link] = "/index.php?m=Admin&c=Ad&a=ad&act=edit&ad_id=$'.$item.'[ad_id]";        
        $'.$item.'[title] = $ad_position[$'.$item.'[pid]][position_name]."===".$'.$item.'[ad_name];
        $'.$item.'[target] = 0;
    }
    ?>';
        $str .=  $this->tpl->parse($content);
        $str .= '<?php endforeach; ?>';
        return $str;
	}
}
 