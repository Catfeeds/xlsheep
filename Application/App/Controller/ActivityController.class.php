<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class ActivityController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法	
        parent::_initialize();
        $shopset = M('Shop_set')->where('id=1')->find();
        if ($shopset['pic']) {
            $listpic = $this->getPic($shopset['pic']);
            $shopset['sharepic'] = $listpic['imgurl'];
        }
        if ($shopset) {
            self::$WAP['shopset'] = $_SESSION['WAP']['shopset'] = $shopset;
            $this->assign('shopset', $shopset);
        } else {
            $this->diemsg(0, '您还没有进行商城配置！');
        }     
        $this->assign('actname', 'ftcategory');
    }

    //砍价操作页面
    public function bargain(){
        $id = I('id');
        $vipid = I('vipid');
        if(!$id){
            $this->error('缺少关键参数');
        }
        $m = M('vip');
        $map['id'] = $id;
        $map['status'] = 1;
        $map['type'] = 2;
        $cache = M('Shop_goods') -> where($map) -> find();
        $info['goodsid'] = $id;
        $info['helpvipid'] = $vipid;
        $info['_string'] = 'helpvipid=vipid';
        $data = M('bargain')->where($info)->find();
        $time['Y'] = date('Y',$data['time']);
        $time['m'] = date('m',$data['time']);
        $time['d'] = date('d',$data['time']);
        $time['H'] = date('H',$data['time']);
        $time['i'] = date('i',$data['time']);
        $time['s'] = date('s',$data['time']);
        $time['status'] = 3;
        unset($info['_string']);
        // var_dump($time);exit;
        if(!$data){
            $info['vipid'] = $info['helpvipid'];
            $info['price'] = $cache['oprice'];
            $info['bprice'] = $cache['price'];
            $info['money'] = 0;
            $info['ctime'] = time();
            $info['vipid'] = $info['helpvipid'];
            $info['time'] = strtotime("+1 week");
            $info['vipid'] = $info['helpvipid'];
            $info['status'] = 0;
            $re = M('bargain') -> add($info);
            $data = M('bargain')->where($info)->order('ctime desc')->find();
        }else{
        	$info['money'] = array('gt', 0);
            $vips = M('bargain')->where($info)->order('ctime desc')->field('vipid,money,ctime,etime')->select();
            foreach ($vips as $key => $value) {
                $vips[$key]['nickname'] = $m -> where('id = '.$value['vipid']) -> getField('nickname');
                $vips[$key]['headimgurl'] = $m -> where('id = '.$value['vipid']) -> getField('headimgurl');
                if($vipid == $value['vipid']&&$value['money']==0){
                    unset($vips[$key]);
                }
            }
        }
		$this->assign('bid', $data['id']);
        if ($cache['pic']) {
            $apppic = $this->getPic($cache['pic']);
            if ($apppic) {
                $this->assign('apppic', $apppic);
            }
        }
        // var_dump($apppic);exit;

        $success = M('bargain')->where('status = 1 and goodsid = '.$id)->field('id,vipid,money,ctime,etime')->order('money DESC')->limit(20)->select();
        if($success){
            foreach ($success as $k => $v) {
                $success[$k]['nickname'] = $m -> where('id = '.$v['vipid']) -> getField('nickname');
                $success[$k]['headimgurl'] = $m -> where('id = '.$v['vipid']) -> getField('headimgurl');
            }
        }
        if($data['time']<time()){
            $this->assign('over',1);
        }
        if($cache['endtime']<time()){
            $time['status'] = 1;
        }
        $this->assign('success',$success);
        $data['price'] = M('bargain')->where($info)->order('ctime desc')->getField('price');
        $data['aboutnum'] = M('bargain')->where($info)->order('ctime desc')->count();
        $data['pmoney'] = round(M('bargain')->where($info)->order('ctime desc')->sum('money'),2);
        $info['vipid'] = $info['helpvipid'];
        $vipdata = M('bargain')->where($info)->order('ctime desc')->find();
        
        if($vipdata['money']==0){
            $data['aboutnum'] -=1;
        }
        if($vipid == $_SESSION['WAP']['vipid']){
            $this->assign('people',1);
        }
        if(!$vipdata){
            $vipdata["ctime"]=time();
        }
        $condition['goodsid'] = $id;
        $condition['helpvipid'] = $vipid;
        $condition['vipid'] = $_SESSION['WAP']['vipid'];
        $condition['money'] = array('gt', 0);
        $count = M('bargain')->where($condition)->count();
        if($count>0) {
        	$selfkan = 1;
        } else {
        	$selfkan = 0;
        }
        $count2 = M('bargain')->where(array('goodsid'=>$id, 'helpvipid'=>$vipid, 'money'=>array('gt', 0)))->count();
        //是否已下单
        $isbuyed = M('shop_order')->where(array('bargain_id'=>$data['id'],'ispay'=>1))->count();
        if($isbuyed>0 || ($count2 >= $cache['blimit'] && $cache['blimit']>0)) {
        	$cankan = 0;
        } else {
        	$cankan = 1;
        }
         if($_SESSION['WAP']['vipid'])
        {

            $vip = M('vip') -> where(array('id'=>$_SESSION['WAP']['vipid'])) -> find();
            if($vip)
            {
                $this->assign('subscribe',$vip['subscribe']);   //1为已关注
            }
            else{
                 $this->assign('subscribe',$vip['subscribe']);  //0为未关注
            }
        }

        
        $backurl = base64_encode(U('App/Activity/barlist'));
        $loginback = U('App/Vip/login', array('backurl' => $backurl));
        $this->assign('loginback', $loginback);
        $this->assign('lasturl', $backurl);
        $vipinfo = $m->where('id = '.$vipid)->find();
        $this->assign('helpvipid',$vipid);
        $this -> assign('vips',$vips);
        $this -> assign('time',$time);
        $this -> assign('data',$data);
        $this -> assign('cache',$cache);
        $this -> assign('vipdata',$vipdata);
        $this -> assign('vipinfo',$vipinfo);
        $this -> assign('selfkan',$selfkan);
        $this -> assign('cankan',$cankan);
        $this -> assign('isbuyed',$isbuyed);
        $this->display();
    }
    public function bargainDetail(){
    	$id = I('id');
    	if(!$id){
    		$this->error('缺少关键参数');
    	}
    	$map['id'] = $id;
    	$map['status'] = 1;
    	$map['cid'] = 17;
    	$cache = M('Shop_goods') -> where($map) -> find();
    	$backurl = base64_encode(U('App/Activity/barlist'));
    	$loginback = U('App/Vip/login', array('backurl' => $backurl));
    	$time['Y'] = date('Y',$cache['endtime']);
    	$time['m'] = date('m',$cache['endtime']);
    	$time['d'] = date('d',$cache['endtime']);
    	$time['H'] = date('H',$cache['endtime']);
    	$time['i'] = date('i',$cache['endtime']);
    	$time['s'] = date('s',$cache['endtime']);
    	$time['status'] = 3;
    	if($cache['endtime']<time()){
    		$this->assign('over',1);
    		$time['status'] = 1;
    	}
    	if ($cache['album']) {
    		$appalbum = $this->getAlbum($cache['album']);
    		if ($appalbum) {
    			$this->assign('appalbum', $appalbum);
    		}
    	}
    	if ($cache['pic']) {
    		$apppic = $this->getPic($cache['pic']);
    		if ($apppic) {
    			$this->assign('apppic', $apppic);
    		}
    	}
    	$where['goodsid'] = $id;
    	$where['helpvipid'] = $where['vipid'] = $_SESSION['WAP']['vipid'];
    	$where['status'] = 0;
    	$ishave = M('bargain') -> where($where) -> find();
    	$this->assign('time', $time);
    	$this -> assign('vipid',$_SESSION['WAP']['vipid']);
    	$this -> assign('cache',$cache);
    	$this->display();
    }

    public function bargoods(){
        $id = I('id');
        if(!$id){
            $this->error('缺少关键参数');
        }
        $map['id'] = $id;
        $map['status'] = 1;
        $map['type'] = 2;
        $cache = M('Shop_goods') -> where($map) -> find();
        $backurl = base64_encode(U('App/Activity/barlist'));
        $loginback = U('App/Vip/login', array('backurl' => $backurl));
        $time['Y'] = date('Y',$cache['endtime']);
        $time['m'] = date('m',$cache['endtime']);
        $time['d'] = date('d',$cache['endtime']);
        $time['H'] = date('H',$cache['endtime']);
        $time['i'] = date('i',$cache['endtime']);
        $time['s'] = date('s',$cache['endtime']);
        $time['status'] = 3;
        // var_dump(date('Y-m-d H:i:s',$cache['endtime']));
        // var_dump($time);exit;
        if($cache['endtime']<time()){
            $this->assign('over',1);
            $time['status'] = 1;
        }
        if ($cache['album']) {
        	$appalbum = $this->getAlbum($cache['album']);
        	if ($appalbum) {
        		$this->assign('appalbum', $appalbum);
        	}
        }
        if ($cache['pic']) {
            $apppic = $this->getPic($cache['pic']);
            if ($apppic) {
                $this->assign('apppic', $apppic);
            }
        }

        if($_SESSION['WAP']['vipid'])
        {

            $vip = M('vip') -> where(array('id'=>$_SESSION['WAP']['vipid'])) -> find();
            if($vip)
            {
                $this->assign('subscribe',$vip['subscribe']);   //1为已关注
            }
            else{
                 $this->assign('subscribe',$vip['subscribe']);  //0为未关注
            }
        }

        //砍价刀数
        $where['goodsid'] = $id;
        $where['helpvipid'] = $_SESSION['WAP']['vipid'];
        $kjcount = M('bargain') -> where($where) -> count();
        $this->assign('time', $time);
        $this->assign('kjcount', $kjcount);
        $this->assign('loginback', $loginback);
        $this->assign('lasturl', $backurl);
        $this->assign('vipid',$_SESSION['WAP']['vipid']);
        $this->assign('cache',$cache);
        $this->display();
    }

    public function barlist(){
        // $id = I('id');
        // if(!$id){
        //     $this->error('缺少关键参数');
        // }
        $type = I('type') ? I('type') : 1;
        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        switch ($type) {
            case '2':
                $goodsids='';
                $info['vipid'] = $info['helpvipid'] = $_SESSION['WAP']['vipid'];
                $info['status'] = 0;
                $goodsid = M('bargain') -> where($info) -> field('goodsid') -> select();
                foreach ($goodsid as $key => $value) {
                    $goodsids .= $value['goodsid'].',';
                }
                break;
            case '3':
                $goodsids='';
                $info['vipid'] = $info['helpvipid'] = $_SESSION['WAP']['vipid'];
                $info['status'] = 1;
                $goodsid = M('bargain') -> where($info) -> field('goodsid') -> select();
                foreach ($goodsid as $key => $value) {
                    $goodsids .= $value['goodsid'].',';
                }
                break;
            default:
                break;
        }

        if(isset($goodsids)){
            if($goodsids){
                $goodsids = substr($goodsids,0,strlen($goodsids)-1);
            }
            $map['id'] = array('in',$goodsids);
        }
        $map['type'] = 2;
        $map['status'] = 1;
        $psize = 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            if ($v['pic']) {
                $cache[$k]['apppic'] = $this->getPic($v['pic']);
            }
        }
        $count = $m->where($map)->count();
        $this->assign('type', $type);
        $this->assign('cache', $cache);
        $this->display();
    }

    public function help(){
        $data = I('post.');
        $vipid = $_SESSION['WAP']['vipid'];
        $vip = self::$WAP['vip'];        
        if(!$data['helpvipid'] || !$data['goodsid']) {
            $return['status'] = 0;
            $return['msg'] = '缺少参数';
            $this->ajaxReturn($return);
        }
        if(!$vip['subscribe']) {
        	$return['status'] = 0;
        	$return['sub'] = 1;
        	$return['msg'] = '您未关注';
        	$this->ajaxReturn($return);
        }
        $condition['helpvipid'] = $data['helpvipid'];
        $condition['vipid'] = $data['helpvipid'];
        $condition['goodsid'] = $data['goodsid'];
        $bargain = M('bargain')->where($condition)->find();
        if(empty($bargain)) {
            $return['status'] = 0;
            $return['msg'] = '数据异常';
            $this->ajaxReturn($return);
        }
        $goods = M('shop_goods')->where(array('id'=>$data['goodsid'],'type'=>2))->find();
        if(empty($goods)) {
            $return['status'] = 0;
            $return['msg'] = '砍价商品不存在';
            $this->ajaxReturn($return);
        }
        $map['helpvipid'] = $data['helpvipid'];
        $map['goodsid'] = $data['goodsid'];
        $info = M('bargain') -> where($map) -> order('id desc') -> find();
        if($info['status'] == 1) {
            $return['status'] = 0;
            $return['msg'] = '砍价发起者已下单，不能帮忙砍价啦！';
            $this->ajaxReturn($return);
        }
        if($info['time'] <= time()) {
            $return['status'] = 0;
            $return['msg'] = '砍价已过期，不能帮忙砍价啦！';
            $this->ajaxReturn($return);
        }
        if($bargain['helpvipid'] != $vipid) {
            $iskan = M('bargain') -> where(array('goodsid'=>$data['goodsid'],'helpvipid'=>$data['helpvipid'], 'vipid'=>$vipid)) -> count();
            if($iskan>0){
                $return['status'] = 0;
                $return['msg'] = '您已砍价！';
                $this->ajaxReturn($return);
            }
        } else {
            if($bargain['money'] != 0) {
                $return['status'] = 0;
                $return['msg'] = '您已砍价！';
                $this->ajaxReturn($return);
            }
        }
        //砍价次数
        $count = M('bargain') -> where(array('goodsid'=>$data['goodsid'],'helpvipid'=>$data['helpvipid'],'money'=>array('gt',0))) -> count();
        if($goods['blimit']>0 && $count >= $goods['blimit']) {
        	$return['status'] = 0;
        	$return['msg'] = '已经砍价'.$count.'次啦，不能再砍了！';
        	$this->ajaxReturn($return);
        }
        $minPrice = $goods['pricemin'];
        $maxPrice = $goods['pricetop'];
        if($minPrice<=0 || $maxPrice<=0) {
            $return['status'] = 0;
            $return['msg'] = '商品配置错误，请联系管理员';
            $this->ajaxReturn($return);
        }
        if($info['price'] <= $info['bprice']) {
            $return['status'] = 0;
            $return['msg'] = '已经砍到最低价啦！';
            $this->ajaxReturn($return);
        }
        $flag = false;//是否已砍到最低价
        if($vipid == $data['helpvipid']){
            if(!empty($info) && $info['money'] == 0){
                $money = rand($minPrice*100, $maxPrice*100)/100;
                if(($info['price'] - $money)<$info['bprice']) {
                	$money = round($info['price'] - $info['bprice'],2);
                    $flag = true;
                }
                $info['money'] = $money;
                $info['etime'] = time();
                $info['price'] = $info['price'] - abs($money) ;//有时候会$money会变负数，所以
                $data['vipid'] = $vipid;
                $re = M('bargain') -> where($data) -> save($info);
                $return['status'] = 1;
                $return['msg'] = '成功砍价'.abs($info['money']).'元';
            } else {
                $return['status'] = 2;
            }
        } else if(!empty($info)) {
            unset($info['id']);
            $money = rand($minPrice*100, $maxPrice*100)/100;
            if(($info['price'] - $money)<$info['bprice']) {
                $money = round($info['price'] - $info['bprice'],2);
                $flag = true;
            }
            $info['money'] = $money;
            $info['vipid'] = $vipid;
            $info['goodsid'] = $data['goodsid'];
            $info['helpvipid'] = $data['helpvipid'];
            if($info['time']) {
                $info['time'] = $info['time'];
            } else {
                $info['time'] = strtotime("+1 week",time());
            }
            $info['status'] = 0;
            $info['ctime'] = $info['etime'] = time();
            $info['price'] = $info['price'] - $money;
            $info['bprice'] = $info['bprice'];
            $data['vipid'] = $vipid;
            $re = M('bargain') -> where($data) -> add($info);
            $return['status'] = 1;
            $return['msg'] = '成功砍价'.$money.'元';
        } else {
            $return['status'] = 2;
        }
        if($flag === true) {
        	$user = M('Vip')->where('id='.$data['helpvipid'])->find();
        	$data['touser'] = $user['openid'];
        	$data['template_id'] = 'AT6OtnK1nqOz4eRFrSL4FczctKEp6hDOfdEGsBljGN4';
        	$data['topcolor'] = "#00FF00";
        	$data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Activity/bargain", array('id'=>$goods['id'],"vipid" => $data['helpvipid']));
        	$data['data'] = array(
        			'first' => array('value' => '好腻害！你的朋友们已经帮你砍到底价了！'),
        			'keyword1' => array('value' => $goods['name']),
        			'keyword2' => array('value' => $goods['price']),
        			'remark' => array('value' => '感谢您对农牧源的支持！')
        	);
        	$options['appid'] = self::$_wxappid;
        	$options['appsecret'] = self::$_wxappsecret;
        	$wx = new \Util\Wx\Wechat($options);
        	$re = $wx->sendTemplateMessage($data);
        } else {
        	if(($count+1)>=$goods['blimit'] && $goods['blimit']>0){
        		$user = M('Vip')->where('id='.$data['helpvipid'])->find();
        		$data['touser'] = $user['openid'];
        		$data['template_id'] = 'YHkvC0atoLpbWE8RJyafBNTDf4nub0RqwrsxqvOkBnw';
        		$data['topcolor'] = "#00FF00";
        		$data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Activity/bargain", array('id'=>$goods['id'],"vipid" => $data['helpvipid']));
        		$data['data'] = array(
        				'first' => array('value' => '好腻害！你有'.($count+1).'个朋友帮你砍价，砍价已完成！'),
        				'keyword1' => array('value' => $goods['name']),
        				'keyword2' => array('value' => $info['price']),
        				'remark' => array('value' => '感谢您对农牧源的支持！')
        		);
        		$options['appid'] = self::$_wxappid;
        		$options['appsecret'] = self::$_wxappsecret;
        		$wx = new \Util\Wx\Wechat($options);
        		$re = $wx->sendTemplateMessage($data);
        	}
        }
        $this->ajaxReturn($return);
    }


    //拼团
    public function actgoods(){

        unset($_SESSION['actid']);
        $id = I('id');
        $groupid = I('groupid');
		$this->assign('groupid', $groupid);
        $actmap['goodsid'] = $id;
        $actmap['vipid'] = $_SESSION['WAP']['vipid'];
        $actmap['status'] = 0;
        $actinfo = M('activity') -> where($actmap) -> find();
        if($actinfo){
            $this->assign('isact',1);
        }

        if(!$id){
            $this->error('缺少关键参数');
        }
        $map['id'] = $id;
        $map['status'] = 1;
        $map['type'] = 1;
        $cache = M('Shop_goods') -> where($map) -> find();
        if ($cache['album']) {
            $appalbum = $this->getAlbum($cache['album']);
            if ($appalbum) {
                $this->assign('appalbum', $appalbum);
            }
        }
        if ($cache['pic']) {
            $apppic = $this->getPic($cache['pic']);
            if ($apppic) {
                $this->assign('apppic', $apppic);
            }
        }

        if($_SESSION['WAP']['vipid'])
        {

            $vip = M('vip') -> where(array('id'=>$_SESSION['WAP']['vipid'])) -> find();
            if($vip)
            {
                $this->assign('subscribe',$vip['subscribe']);   //1为已关注
            }
            else{
                 $this->assign('subscribe',$vip['subscribe']);  //0为未关注
            }
        }
        $cache['content'] = filter_the_content(htmlspecialchars_decode($cache['content']));
        //团
        $group = M('activity_group')->where(array('status'=>0,'goods_id'=>$id))->find();
        $this->assign('group', $group);
        $groupCount = M('activity_group')->where(array('status'=>0,'goods_id'=>$id))->count();
        $this->assign('groupCount', $groupCount);
        if(!empty($group)) {
        	//开团列表
        	$groupMap['goods_id'] = $id;
        	$groupMap['A.status'] = 0;
        	$groupMap['rtime'] = array('egt', time());
        	$grouplistcount = M('activity_group')->alias('as A')
        	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
        	->where($groupMap)
        	->count();
        	$grouplist = M('activity_group')->alias('as A')
        	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
        	->where($groupMap)
        	->field('V.headimgurl,V.nickname,A.id,A.num,A.rtime')
        	->order('A.num DESC, A.id ASC')
        	->limit(3)
        	->select();
        	foreach($grouplist as $k => $v) {
        		$num = $cache['peoplenum'] - $v['num'];
        		$grouplist[$k]['left'] = $num >0 ? $num : 0;
        	}
        	$this->assign('grouplist', $grouplist);
        	$this->assign('grouplistcount', $grouplistcount);
        }
        $backurl = base64_encode(U('App/Activity/actlist'));
        $loginback = U('App/Vip/login', array('backurl' => $backurl));
        $this->assign('loginback', $loginback);
        $this->assign('lasturl', $backurl);
        $this -> assign('vipid',$_SESSION['WAP']['vipid']);
        $this -> assign('cache',$cache);
        $this->display();
    }

    
    public function actlist(){

        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $type = I('type', 0, 'intval');
        if ($type) {
            $map['gcid'] = array('eq', $type);
            $this->assign('type', $type);
        }
        $map['type'] = array('eq','1');
        $psize = 10;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            if ($v['pic']) {
                $cache[$k]['apppic'] = $this->getPic($v['pic']);
            }
            $cache[$k]['join'] = getLastJoin($v['id']);
        }
        $count = $m->where($map)->count();
        $this->assign('cache', $cache);
        if(IS_AJAX) {
        	$tpl = $this->fetch('./Tpl/App/Ajax/actlist.html');
        	$data = array(
        			'status' => 1,
        			'info' => $tpl,
        			'more' => count($cache) < $psize ? 0 : 1
        	);
        	$this->ajaxReturn($data);
        } else {
        	$cates = M('Activity_cate')->where('pid=0')->select();
        	$this->assign('cates', $cates);
        	$this->assign('type', $type);
        	$this->assign('datamore', $count > $psize? 1 : 0);
        	$this->display();
        }
    }
    
    //开团列表
    public function groupall(){
    	$m = M('activity_group');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$id = I('id', 0, 'intval');
    	if($id <= 0) {
    		$this->error('缺少参数');
    	}
    	$map['type'] = 1;
    	$map['id'] = $id;
    	$group = M('Shop_goods')->where($map)->find();
    	if(empty($group)) {
    		$this->error('拼团产品不存在');
    	}
    	if ($group['album']) {
    		$appalbum = $this->getAlbum($group['album']);
    		if ($appalbum) {
    			$this->assign('appalbum', $appalbum);
    		}
    	}
    	if ($group['pic']) {
    		$apppic = $this->getPic($group['pic']);
    		if ($apppic) {
    			$this->assign('apppic', $apppic);
    		}
    	}
    	$psize = 10;
    	$groupMap['A.id'] = $id;
    	$groupMap['A.status'] = 0;
    	$groupMap['rtime'] = array('egt', time());
    	$count = $m->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where($groupMap)
    	->count();
    	if($count>0) {
    		$grouplist = $m->alias('as A')
    		->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    		->where($groupMap)
    		->field('V.headimgurl,V.nickname,A.id,A.num,A.rtime')
    		->order('A.num DESC, A.id ASC')
    		->page($p, $psize)
    		->select();
    		foreach($grouplist as $k => $v) {
    			$num = $group['peoplenum'] - $v['num'];
    			$grouplist[$k]['left'] = $num >0 ? $num : 0;
    		}
    	}
    	$this->assign('cache', $grouplist);
    	if(IS_AJAX) {
    		$tpl = $tpl = $this->fetch('./Tpl/App/Ajax/groupall.html');
    		$data = array(
    				'status' => 1,
    				'info' => $tpl,
    				'more' => count($cache) < $psize ? 0 : 1
    		);
    		$this->ajaxReturn($data);
    	} else {
    		$this->assign('group', $group);
    		$this->assign('count', $count);
    		$this->assign('datamore', $count > $psize? 1 : 0);
    		$this->display();
    	}
    }
    
    public function groupShare(){
    	$id = I('id', 0, 'intval');
    	if(!$id) {
    		$this->error('缺少参数');
    	}
    	$vipid = $_SESSION['WAP']['vipid'];
    	$activity = M('activity')->where('id='.$id)->find();
    	if(empty($activity)) {
    		$this->error('数据错误',U('App/Activity/actlist'));
    	}
    	$group = M('activity_group')->where('id='.$activity['groupid'])->find();
    	$cache = M('Shop_goods')->where('id='.$group['goods_id'])->find();
    	if ($cache['pic']) {
    	    $apppic = $this->getPic($cache['pic']);
    	    if ($apppic) {
    	        $this->assign('apppic', $apppic);
    	    }
    	}
    	$group['left'] = $cache['peoplenum'] - $group['num'];
    	$group['left']= $group['left']>0 ? $group['left']: 0;
    	//团长
    	$vip = M('vip')->where('id='.$group['vipid'])->find();
    	$this->assign('vip', $vip);
    	//团员
    	$member = M('activity')->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where(array('A.groupid'=>$activity['groupid'],'A.vipid'=>array('neq', $vip['id'])))
    	->field('V.headimgurl,V.nickname')
    	->order('A.id ASC')
    	->select();
    	//当前用户是否已参团
    	$isjoin = M('activity')->where(array('groupid'=>$activity['groupid'], 'vipid'=>$vipid))->count();
    	$this->assign('isjoin', $isjoin);
    	$this->assign('activity', $activity);
    	$this->assign('group', $group);
    	$this->assign('member', $member);
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    public function joinGroup(){
    	$id = I('id', 0, 'intval');
    	if(!$id) {
    		$this->error('缺少参数');
    	}
    	$vipid = $_SESSION['WAP']['vipid'];
    	$group = M('activity_group')->where('id='.$id)->find();
    	if(empty($group)) {
    		$this->error('拼团不存在');
    	}
    	$cache = M('Shop_goods')->where('id='.$group['goods_id'])->find();
    	if ($cache['pic']) {
    		$apppic = $this->getPic($cache['pic']);
    		if ($apppic) {
    			$this->assign('apppic', $apppic);
    		}
    	}
    	$group['left'] = $cache['peoplenum'] - $group['num'];
    	$group['left']= $group['left']>0 ? $group['left']: 0;
    	//团长
    	$vip = M('vip')->where('id='.$group['vipid'])->find();
    	$this->assign('vip', $vip);
    	//团员
    	$member = M('activity')->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where(array('A.groupid'=>$group['id'],'A.vipid'=>array('neq', $vip['id'])))
    	->field('V.headimgurl,V.nickname')
    	->order('A.id ASC')
    	->select();
    	//当前用户是否已参团
    	$isjoin = M('activity')->where(array('groupid'=>$id, 'vipid'=>$vipid))->count();
    	$this->assign('isjoin', $isjoin);
    	$this->assign('member', $member);
    	$this->assign('group', $group);
    	$this->assign('cache', $cache);
    	$backurl = base64_encode(U('App/Activity/actlist'));
    	$loginback = U('App/Vip/login', array('backurl' => $backurl));
    	$this->assign('loginback', $loginback);
    	$this->assign('lasturl', $backurl);
    	$this->display();
    }
}
