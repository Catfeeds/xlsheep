<?php
namespace App\Controller;
use Think\Model;
use Org\Util\Geohash;

class AjaxController extends BaseController
{
	protected $pagesize = 10;
	
    public function record()
    {
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	if($page<1) $page = 1;
    	$type = intval(I('type')) ? intval(I('type')) : 0;
    	$m = M('Vip_log_money');
    	$map['vipid'] = $_SESSION['WAP']['vipid'];
    	switch ($type) {
    		case 1://全部流水
    			$cache = $m->where($map)->limit(($page-1)*$this->pagesize, $this->pagesize)->order('id DESC')->select();
    			break;
    		case 2://联养记录
    			$map['type'] = 4;
    			$cache = $m->where($map)->limit(($page-1)*$this->pagesize, $this->pagesize)->order('id DESC')->select();
    			break;
    		case 3://收益日志
    			$map['type'] = 5;
    			$cache = $m->where($map)->limit(($page-1)*$this->pagesize, $this->pagesize)->order('id DESC')->select();
    			break;
    		case 5://返还本金日志
    			$map['type'] = 6;
    			$cache = $m->where($map)->limit(($page-1)*$this->pagesize, $this->pagesize)->order('id DESC')->select();
    			break;
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/Record.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //获取文章
    public function articalList()
    {
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$cid = intval(I('cid'));
    	if($cid) {
    		$map['cid'] = $cid;
    	}
    	$m = M('Artical');
    	$data = $m->where($map)
    	->page($page, $this->pagesize)
    	->order('id DESC')
    	->select();
    	$html = '';
    	foreach($data as $k => $v) {
    		$html .= '<article class="ariticle-list">
            <a class="list-info" href="'.U('App/Artical/index',array('id'=>$vo['id'])).'">
                <div class="ariticle-con">
                    <div class="at-title">'.$vo['title'].'</div>
                    <div class="at-con">'.$vo['sub'] ? msubstr($vo['sub'], 0, 40):''.'
                    </div>
                    <div class="at-date">'.$vo['time'].'</div>
                </div>
            </a>
        	</article>';
    	}
    	$data = array(
    			'status' => 1,
    			'info' => $html,
    			'more' => count($data) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //获取公告
    public function announceList()
    {
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Announce');
    	$data = $m->where($map)
    	->page($page, $this->pagesize)
    	->order('id DESC')
    	->select();
    	$html = '';
    	foreach($data as $k => $v) {
    		$html .= '<article class="ariticle-list">
            <a class="list-info" href="'.U('App/Announce/index',array('id'=>$vo['id'])).'">
                <div class="ariticle-con">
                    <div class="at-title">'.$vo['title'].'</div>
                    <div class="at-date">'.$vo['time'].'</div>
                </div>
            </a>
        	</article>';
    	}
    	$data = array(
    			'status' => 1,
    			'info' => $html,
    			'more' => count($data) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //获取站内消息
    public function messageList()
    {
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$vipid = self::$WAP['vipid'];
    	$vip = self::$WAP['vip'];
    	$Model = new Model ();
    	$sql = 'SELECT *  FROM (SELECT * FROM `' . C ( 'DB_PREFIX' ) . 'vip_message`
        		WHERE `pids` = '.$vipid.'
        		UNION ALL
        		SELECT * FROM ' . C ( 'DB_PREFIX' ) . 'vip_message
        		WHERE `pids` = "" AND ctime>='.$vip['ctime'].') as a
        		ORDER BY a.id DESC LIMIT '.($page-1)*$this->pagesize.', '.$this->pagesize;
    	$cache = $Model->query ( $sql );
    	foreach ($cache as $k => $val) {
    		$read = M('vip_log')->where('vipid=' . $vipid . ' and opid=' . $val['id'] . ' and type=5')->find();
    		$cache[$k]['read'] = $read ? 1 : 0;
    	}
    	$this->assign('data',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/MessageLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //合同列表
    public function contractList() {
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Finance_contract');
    	$map['vipid'] = $vipid;
    	$count = $m->where($map)->count();
    	$cache = $m->where($map)->page($page, $this->pagesize)->select();
    	$i=1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = ($page-1)*$this->pagesize+$i;
    		$i++;
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/ContractLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //保单列表
    public function insuranceList() {
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Finance_insurance');
    	$map['vipid'] = $vipid;
    	$count = $m->where($map)->count();
    	$cache = $m->where($map)->page($page, $this->pagesize)->select();
    	$i=1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = ($page-1)*$this->pagesize+$i;
    		$i++;
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/InsuranceLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    public function buyAllList()
    {
    	$page = I('p', 1, 'intval');
    	$cid = I('cid', 0, 'intval');
    	$m = M('Finance_goods');
    	if ($cid) {
    		$where['cid'] = $cid;
    	}
    	$where['issj'] = 1;
    	$count = $m->where($where)->count();
    	$cache = $m->where($where)->page(1, $this->pagesize)->order('id DESC')->select();
    	foreach ($cache as $k => $v) {
    		$listpic = $this->getPic($v['pic']);
    		$cache[$k]['imgurl'] = $listpic['imgurl'];
    		$cache[$k]['percent'] = round($v['sells']/($v['num']+$v['sells'])*100,1);
    		$cache[$k]['ro'] = intval($v['sells']/($v['sells']+$v['num'])*360/10)*10;
    	}
    	$this->assign('cache', $cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/BuyAllLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($data) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //购买产品用户列表
    public function buyUserList()
    {
    	$page = I('p', 1, 'intval');
    	$gid = I('gid', 0, 'intval');
    	$cid = I('cid', 0 , 'intval');
    	if($gid) {
    		$goods = M('Finance_goods')->where('id='.$gid)->find();
    		if(!$goods) {
    			$data = array(
    					'status' => 0,
    					'info' => ''
    			);
    			$this->ajaxReturn($data);
    		}
    		$map['goodsid'] = $gid;
    	}
    	if($cid) {
    		$map['G.cid'] = $cid;
    		$groupby = 'O.vipid';
    	} else {
    		$groupby = 'G.cid';
    	}
    	$map['O.status'] = array('gt', 1);
    	 
    	$m = M('Finance_order');
    	$userlist = $m->alias('as O')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON O.vipid = V.id')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_goods` AS G ON O.goodsid = G.id')
    	->field('V.headimgurl,V.nickname,O.totalnum,G.cid,sum(O.totalnum) as num')
    	->where($map)
    	->page($page, $this->pagesize)
    	->group($groupby)
    	->order('num DESC,O.ctime asc')
    	->cache(true)
    	->select();
    	//序号
    	$index = 1;
    	foreach($userlist as $k => $v) {
    		$userlist[$k]['index'] = $index;
    		$userlist[$k]['catname'] = $category[$v['cid']];
    		$index++;
    	}
    	$this->assign('userlist',$userlist);
    	$tpl = $this->fetch('./Tpl/App/Ajax/BuyUserLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($data) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //我的订单
    public function orderList() {
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$type = I('type') ? I('type') : 4;
    	$m = M('Shop_order');
    	$map['vipid'] = $vipid;
    	switch ($type) {
    		case '1':
    			//待付款
    			$map['status'] = 1;
    			break;
    		case '2':
    			//待收货
    			$map['status'] = 3;
    			break;
    		case '3':
    			//已完成
    			$map['status'] = array('in', array('5', '6'));
    			break;
    		case '4':
    			//全部
    			$map['status'] = array('neq', '0');
    			break;
    		case '5':
    			//待发货
    			$map['status'] = 2;
    			break;
    		default:
    			$map['status'] = 1;
    			break;
    	}
    	$count = $m->where($map)->count();
    	$cache = $m->where($map)->order('ctime desc')->page($page, $this->pagesize)->select();
    	if ($cache) {
    		foreach ($cache as $k => $v) {
    			if ($v['items']) {
    				$cache[$k]['items'] = unserialize($v['items']);
    			}
    		}
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/OrderLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }

    /**
     * 风格
     * author: feng
     * create: 2017/9/26 21:34
     */
    public function deliveryOrderList() {
        $vipid = $_SESSION['WAP']['vipid'];
        if(!$vipid) {
            $this->error('您未登录！');
        }
        $page = intval(I('p')) ? intval(I('p')) : 1;
        $dmap['vipid'] = $vipid;
        $deliveryman =M("deliveryman")->where($dmap)->find();
        if(!$deliveryman){
            $this->error('你没有权限查看该列表!');
        }
        if(!$deliveryman["adlist"]){
            $this->error('你还没有设置自提点，请联系管理员!');
        }
        $map["delivery"]="since";
        $map["deliveryid"]=$vipid;
        $map['type'] = 0;
        $map["status"]=array("GT",1);
        $type = I('type') ? I('type') : 0;
        $m = M('Shop_order');

        switch ($type) {
            case '1':
                //待配送
                $map['status'] =array("in","2,3");
                break;
            case '2':
                //已完成
                $map['status'] =5;
                break;

        }
        $count = $m->where($map)->count();
        $cache = $m->where($map)->order('ctime desc')->page($page, $this->pagesize)->select();
        if ($cache) {
            foreach ($cache as $k => $v) {
                if ($v['items']) {
                    $cache[$k]['items'] = unserialize($v['items']);
                }
                if(!($v["delivery_fee"]>0)){
                    if(M("vip")->where(array("id"=>$v["vipid"]))->getField("groupid")<2&&$v["deliveryid"]>0) {
                        $dmap['vipid'] = $v["deliveryid"];
                        $deliveryman = M("deliveryman")->where($dmap)->find();
                        if($deliveryman&&$deliveryman["fee"]>0&&$deliveryman["status"]<1){
                            $cache[$k]['delivery_fee'] = round($v["totalprice"] * $deliveryman['fee'] / 100, 2);
                        }


                    }


                }
            }
        }
        $this->assign('cache',$cache);
        $tpl = $this->fetch('./Tpl/App/Ajax/dOrderLists.html');
        $data = array(
            'status' => 1,
            'info' => $tpl,
            'more' => count($cache) < $this->pagesize ? 0 : 1
        );
        $this->ajaxReturn($data);
    }

    //金融产品订单
    public function buyOrderList(){
    	$vipid = self::$WAP ['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$m = M('Finance_order');
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$map['vipid'] = $vipid;
    	$count = $m->where($map)->count();
    	$cache = $m->alias('as O')->join('LEFT JOIN `'.C('DB_PREFIX').'finance_goods` AS G ON O.goodsid = G.id')
    	->where($map)->field('O.*,G.bonusway,G.name,G.vname,G.rate,G.cycle,G.cid,G.price,G.unit,G.status as goodstatus')->order('ctime desc')->select();
    	if ($cache) {
    		foreach ($cache as $k => $v) {
    			if ($v['goodsid']) {
    				if($v['cid']) {
    					$category = M('Finance_cate')->where('id=' . $cache[$k]['cid'])->find();
    				}
    				$cache[$k]['cycle'] = $cache[$k]['cycle'].'个月';
    				$cache[$k]['rate'] = ($cache[$k]['rate']*100).'%';
    				$cache[$k]['catname'] = isset($category)&&$category['name'] ? $category['name'] : '';
    			}
    		}
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/BuyOrderLists.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //充值记录
    public function czRecord(){
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Vip_log');
    	$map['vipid'] = $vipid;
    	$map['type'] = 7;
    	$map['status'] = 2;
    	$cache = $m->where($map)->page($page,$this->pagesize)->order('id DESC')->select();
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/CzRecord.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //提现记录
    public function txRecord(){
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Vip_tx');
    	$map['vipid'] = $vipid;
    	$cache = $m->where($map)->page($page,$this->pagesize)->order('id DESC')->select();
    	foreach($cache as $k => $v) {
    		$cache[$k]['txcard'] = substr($v['txcard'], -4);
    	}
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/TxRecord.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
    //积分记录
    public function credit(){
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登录！');
    	}
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Vip_log_credit');
    	if($type) {
    		$map['type'] = $type;
    	}
    	$map['vipid'] = $vipid;
    	$cache = $m->where($map)->page($page,$this->pagesize)->order('id DESC')->select();
    	$this->assign('cache',$cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/Credit.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }

    public function since(){
        $geohash=new Geohash;
        //经纬度转换成Geohash
        //获取附近的信息
        $latitude = I('latitude');
        $longitude = I('longitude');
        $province = I('province');
        $city = I('city');
        $district = I('district');
        $goodsid = I('goodsid');
        $addressid = M('finance_goods') -> where('id = '.$goodsid) -> getField('adressid');
        $map['id'] = array('in',$addressid);
        if($province>0) {
        	$map['province'] = $province;
        }
        if($city>0) {
        	$map['city'] = $city;
        }
        if($district>0) {
        	$map['district'] = $district;
        }
        $cache = M('since')-> where($map) -> select();
        foreach($cache as $key=>$val)
        {
            $distance = $this->getDistance($latitude,$longitude,$val['latitude'],$val['longitude']);
            $cache[$key]['distance'] = number_format($distance/1000,2);
            //排序列
            $sortdistance[$key] = $distance;
        }
        //距离排序
        array_multisort($sortdistance,SORT_ASC,$cache);
        $this->assign('cache',$cache);
        $tpl = $this->fetch('./Tpl/App/Ajax/Since.html');
        $data = array(
                'status' => 1,
                'info' => $tpl
        );
        $this->ajaxReturn($data);
    }

    function getDistance($lat1,$lng1,$lat2,$lng2)
    {
    //地球半径
    $R = 6378137;
    //将角度转为狐度
    $radLat1 = deg2rad($lat1);
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    //结果
    $s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*$R;
    //精度
    $s = round($s* 10000)/10000;
    return round($s);
    }
    
    public function pickup(){
    	$geohash=new Geohash;
    	//经纬度转换成Geohash
    	//获取附近的信息
    	$latitude = I('latitude');
    	$longitude = I('longitude');
        $province = I('province',0 , 'intval');
        $city = I('city',0 , 'intval');
        $district = I('district',0 , 'intval');
        $pickups = I('pickups');
        /*
    	$latitude = 30.267447;
    	$longitude = 120.152792;
    	$province = 25579;
    	$pickups = 'a:24:{i:0;s:2:"19";i:1;s:2:"20";i:2;s:2:"21";i:3;s:2:"22";i:4;s:2:"25";i:5;s:2:"26";i:6;s:2:"27";i:7;s:2:"32";i:8;s:2:"34";i:9;s:2:"37";i:10;s:2:"42";i:11;s:2:"47";i:12;s:2:"48";i:13;s:2:"49";i:14;s:2:"50";i:15;s:2:"52";i:16;s:2:"53";i:17;s:2:"54";i:18;s:2:"55";i:19;s:2:"56";i:20;s:2:"57";i:21;s:2:"58";i:22;s:2:"59";i:23;s:2:"60";}';
        */
        if(!$province) {
        	$this->error('缺少参数');
        }
        if(!$pickups) {
            $data = array(
                'status' => 0,
                'info' => '没有符合条件的自提点'
            );
            $this->ajaxReturn($data);
        }
        $pickups = unserialize(stripslashes(htmlspecialchars_decode($pickups)));
        $pickups = array_values($pickups);
        $addressid = $pickups[0];
        if(count($pickups)>1) {
            $addressid = implode(',', $pickups);
        }
        if(!$addressid){
            $data = array(
                'status' => 0,
                'info' => '自提点不存在'
            );
            $this->ajaxReturn($data);
        }
        $map['id'] = array('in',$addressid);
        if($province>0) {
        	$map['province'] = $province;
        }
        if($city>0) {
        	$map['city'] = $city;
        }
        if($district>0) {
        	$map['district'] = $district;
        }
        $cache = M('since')-> where($map) -> select();
        foreach($cache as $key=>$val)
        {
            $distance = $this->getDistance($latitude,$longitude,$val['latitude'],$val['longitude']);
            $cache[$key]['distance'] = number_format($distance/1000,2);
            //排序列
            $sortdistance[$key] = $distance;
        }
        //距离排序
        array_multisort($sortdistance,SORT_ASC,$cache);
        $this->assign('cache',$cache);
        $tpl = $this->fetch('./Tpl/App/Ajax/Since.html');
        $data = array(
            'status' => 1,
            'info' => $tpl
        );
        $this->ajaxReturn($data);
    }
    //分销中心推广日志
    public function tjlog(){
    	$page = intval(I('p')) ? intval(I('p')) : 1;
    	$m = M('Vip_log_tj');
    	$map['vipid'] = $_SESSION['WAP']['vipid'];
    	$cache = $m->where($map)->page($page, $this->pagesize)->order('ctime desc')->select();
    	$this->assign('cache', $cache);
    	$tpl = $this->fetch('./Tpl/App/Ajax/FxTjlog.html');
    	$data = array(
    			'status' => 1,
    			'info' => $tpl,
    			'more' => count($cache) < $this->pagesize ? 0 : 1
    	);
    	$this->ajaxReturn($data);
    }
}