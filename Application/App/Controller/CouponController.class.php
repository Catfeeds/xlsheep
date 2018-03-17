<?php
//卡劵
namespace App\Controller;

class CouponController extends BaseController
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
    }
    /**
     * @desc 获取access_token
     * @return String access_token
     */
    function getAccessToken(){
    	$AppId = 'wx42954c46519528d7';
    	$AppSecret = '195937452912ad5dc79d0fb4d8794a7c';
    	$getUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$AppId.'&secret='.$AppSecret;
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $getUrl);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURL_SSLVERSION_SSL, 2);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	$data = curl_exec($ch);
    	$response = json_decode($data);
    	return $response->access_token;
    }
    function getCode($access_token,$openid,$card_id){
    	$customMessageSendUrl = "https://api.weixin.qq.com/card/user/getcardlist?access_token=$access_token";
    	$postDataArr = array(
    			'openid'=>$openid,
    			'card_id'=>$card_id,
    	);
    	$postJosnData = json_encode($postDataArr);
    	$ch = curl_init($customMessageSendUrl);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postJosnData);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	$data = curl_exec($ch);
    	return $data;
    }
    function getCodedetails($access_token,$card_id){
    	$customMessageSendUrl = "https://api.weixin.qq.com/card/get?access_token=$access_token";
    	$postDataArr = array(
    			'card_id'=>$card_id,
    	);
    	$postJosnData = json_encode($postDataArr);
    	$ch = curl_init($customMessageSendUrl);
    	curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postJosnData);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	$data = curl_exec($ch);
    	return $data;
    }
    function object2array($object) {
    	$object =  json_decode( json_encode( $object),true);
    	return  $object;
    }
    function object2array_pre($array) {
    	if (is_object($array)) {
    		$arr = (array)($array);
    	} else {
    		$arr = &$array;
    	}
    	if (is_array($arr)) {
    		foreach($arr as $varName => $varValue){
    			$arr[$varName] = $this->object2array($varValue);
    		}
    	}
    	return $arr;
    }
    public function index()
    {   	
     	$vipid = $_SESSION['WAP']['vipid'];
     	$data = M('Vip')->where(array('id'=>$vipid))->find();
     	//用户授权获取access_token
     	
    	//$access_token = $this->getAccessToken();//获取access_token
    	$access_token = $opt['token'];
    	$openid = $data['openid'];
    	$card_id = 'pOg6DwhUhxYnanKjqIVYxUwXui8s';//卡劵ID
    	$code = $this->getCode($access_token,$openid,$card_id);
    	$object = json_decode($code);
    	$Cuser = $this->object2array($object);
    	$usercode = $Cuser['card_list'][0]['code'];//用户code
    	$usercardid = $Cuser['card_list'][0]['card_id'];//卡券ID
    	//var_dump($Cuser);exit();
    	//获取卡劵详情
    	$Cdetails = $this->getCodedetails($access_token,$card_id);
    	$array = json_decode($Cdetails,true);
    	$Codedetails = $this->object2array_pre($array);
    		
    	//var_dump($Codedetails['card']['general_coupon']);exit();//详情
    	//var_dump($Codedetails['card']['gift']);exit();//详情
    
    	$title = $Codedetails['card']['gift']['base_info']['title'];//卡劵名称
    	$logo_url = $Codedetails['card']['gift']['base_info']['logo_url'];//卡劵logo
    	$fixed_term = $Codedetails['card']['gift']['base_info']['date_info']['fixed_term'];//卡劵期限天数
    	$create_time = $Codedetails['card']['gift']['base_info']['create_time'];//卡劵创建时间
    	$update_time = $Codedetails['card']['gift']['base_info']['update_time'];//卡劵更新时间
    	$total_quantity = $Codedetails['card']['gift']['base_info']['sku']['total_quantity'];//卡券全部库存的数量
    	$quantity = $Codedetails['card']['gift']['base_info']['sku']['quantity'];//卡券现有库存的数量
    	$codestatus = $Codedetails['card']['gift']['base_info']['status'];//卡劵状态
    	//var_dump($codestatus);exit();
    	if($usercode){
    		$vipCoupon = M('Vip_coupon')->where(array('vipid'=>$vipid))->find();
    		$shoporder = M('Shop_order')->where(array('vipid'=>$vipid,'goodstype'=>coupon,'status'=>2))->find();
    		if($shoporder){
    			$this->error('您的新人礼券已使用或已过期！','/App/Shop/index');
    		}
    		if(empty($vipCoupon)){
    			$data_log['vipid'] = $vipid;
    			$data_log['code'] = $usercode;
    			$data_log['card_id'] = $usercardid;
    			$data_log['openid'] = $openid;
    			$data_log['unionid'] = $data['unionid'];
    			$data_log['type'] = 'coupon';
    			$data_log['title'] = $title;
    			$data_log['status'] = 1;
    			$data_log['ctime'] = time();
    			$data_log['fixed_term'] = $fixed_term;
    			$data_log['logo_url'] = $logo_url;
    			$data_log['create_time'] = $create_time;//创建时间
    			$data_log['update_time'] = $update_time;
    			$datatime = $create_time+30*24*3600;//卡劵结束时间
    			$data_log['e_time'] = $datatime;
    			$data_log['total_quantity'] = $total_quantity;
    			$data_log['quantity'] = $quantity;
    			$data_log['codestatus'] = $codestatus;
    			//var_dump($data_log);exit();
    			$result = M('Vip_coupon')->add($data_log);
    		}else{
    			if($vipCoupon['status']=='2' || $vipCoupon['create_time']>$vipCoupon['e_time']){//卡劵已使用或已过期
    				$this->error('您的新人礼券已使用或已过期！','/App/Shop/index');
    			}
    		}
    	}else {
    		$this->redirect('/App/Shop/index');
    	} 
    	$sid = $_GET['sid'] <> '' ? $_GET['sid'] : 0;//sid可以为0
    	$totalnum = 1;
    	$goodstype = "coupon";
    	$address = M('Vip_address')->where(array('vipid'=>$vipid))->find();
    	//查询赠品商品
    	$zporder = M('Shop_goods')->where(array('cid'=>'38','id'=>'38'))->find();
    	$apppic = $this->getPic($zporder['pic']);//图片
    	//存购物车
    	$userbask = M('Shop_basket')->where(array('vipid'=>$vipid))->find();
    	if(empty($userbask)){
	    	$bask['sid'] = $sid;
	    	$bask['vipid'] = $vipid;
	    	$bask['goodsid'] = $zporder[id];
	    	$bask['num'] = $totalnum; 
	    	$basket = M('Shop_basket')->add($bask);
    	}
    	//调取缓存购物车
    	$cache = M('Shop_basket')->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
     	//错误标记
    	$errflag = 0;
    	//等待删除ID
    	$todelids = '';
    	//totalprice
    	$totalprice = 0;
    	//totalnum
    	$totalnum = 0;
    	foreach ($cache as $k => $v) {
    		//sku模型
    		$goods = M('Shop_goods')->where('id=' . $v['goodsid'])->find();
    		$pic = $this->getPic($goods['pic']);
    		if ($v['sku']) {
    			//取商品数据
    			if ($goods['issku'] && $goods['status']) {
    				$map['sku'] = $v['sku'];
    				$sku = M('Shop_goods_sku')->where($map)->find();
    				if ($sku['status']) {
    					if ($sku['num']) {
    						//调整购买量
    						$cache[$k]['goodsid'] = $goods['id'];
    						$cache[$k]['skuid'] = $sku['id'];
    						$cache[$k]['name'] = $goods['name'];
    						$cache[$k]['skuattr'] = $sku['skuattr'];
    						$cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
    						$cache[$k]['price'] = $sku['price'];
    						$cache[$k]['total'] = $v['num'] * $sku['price'];
    						$cache[$k]['pic'] = $pic['imgurl'];
    						$totalnum = $totalnum + $cache[$k]['num'];
    						$totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
    					} else {
    						//无库存删除
    						$todelids = $todelids . $v['id'] . ',';
    						unset($cache[$k]);
    	
    					}
    				} else {
    					//下架删除
    					$todelids = $todelids . $v['id'] . ',';
    					unset($cache[$k]);
    				}
    			} else {
    				//下架删除
    				$todelids = $todelids . $v['id'] . ',';
    				unset($cache[$k]);
    			}
    	
    		} else {
    			if ($goods['status']) {
    				if ($goods['num']) {
    					//调整购买量
    					$cache[$k]['goodsid'] = $goods['id'];
    					$cache[$k]['skuid'] = 0;
    					$cache[$k]['name'] = $goods['name'];
    					$cache[$k]['skuattr'] = $sku['skuattr'];
    					$cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
    					$cache[$k]['price'] = $goods['price'];
    					$cache[$k]['total'] = $v['num'] * $goods['price'];
    					$cache[$k]['pic'] = $pic['imgurl'];
    					$totalnum = $totalnum + $cache[$k]['num'];
    					$totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
    				} else {
    					//无库存删除
    					$todelids = $todelids . $v['id'] . ',';
    					unset($cache[$k]);
    				}
    			} else {
    				//下架删除
    				$todelids = $todelids . $v['id'] . ',';
    				unset($cache[$k]);
    			}
    		}
    	}
    	if ($todelids) {
    		$rdel = M('Shop_basket')->delete($todelids);
    		if (!$rdel) {
    			$this->error('购物车获取失败，请重新尝试！');
    		}
    	}
    	sort($cache);
    	$allitems = serialize($cache);
    	$this->assign('allitems', $allitems);
    	$totalprice = 10;//邮费
    	//是否可以用余额支付
    	$useryue = $_SESSION['WAP']['vip']['money'];
    	$isyue = $useryue - $totalprice >= 0 ? 0 : 1;
    	$this->assign('zporder', $zporder);
    	$this->assign('apppic', $apppic);
    	$this->assign('isyue', $isyue);
    	$this->assign('sid',$sid);
    	$this->assign('goodstype',$goodstype);
    	$this->assign('totalnum',$totalnum);
    	$this->assign('totalprice',$totalprice);
    	$this->assign('address',$address);
    	$this->assign('data',$data);
    	$this->assign('vipCoupon',$vipCoupon);
    	$this->display(); 
    }
}