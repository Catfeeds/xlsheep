<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class ShopController extends BaseController
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

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    function getJson($url){
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	$output = curl_exec($ch);
    	curl_close($ch);
    	return json_decode($output, true);
    }
    public function index()
    {
        $m = M('Shop_goods');
        //最新产品
        $map['issj'] = 1;
        $map['status'] = 1;
        $new =  M('Finance_goods')->where($map)->order('id desc')->find();
        if(!empty($new)) {
	        $ca = +$new['num']+$new['sells'];
            $percent = round($new['sells']/$ca*100,1);
	        if($percent == 100){
	        	$new['percent'] = '100';
	        } elseif ($percent == 0){
	        	$new['percent'] = '0';
	        } else {
	        	$new['percent'] = intval($percent);
	        }
	        $new['ro'] = intval($new['percent']/10)*10;
        }else{
            $map['status'] = 2;
            $new =  M('Finance_goods')->where($map)->order('id desc')->find();
            $ca = +$new['num']+$new['sells'];
            if($new['status']>1) {
            	$percent = 100;
            } else {
            	$percent = round($new['sells']/$ca*100,1);           	
            	if($percent == 100){
            		$new['percent'] = '100';
            	} elseif ($percent == 0){
            		$new['percent'] = '0';
            	} else {
            		$new['percent'] = intval($percent);
            	}
            }

            $new['ro'] = intval($new['percent']/10)*10;
        }
        if($new['isobject']&&$new['ismoney']){
            $way = 3;
        }else{
            if($new['isobject']&&!$new['ismoney']){
                $way = 2;
            }else{
                $way = 1;
            }
        }
        $this->assign('way',$way);
        $this->assign('new', $new);
        //获取公告
        $announce = M('Artical')->limit(1) -> where('cid = 3') ->order('id desc')->select();
        $this->assign('announce', $announce);      
        $this->assign('actname', 'fthome');
		$this->display();
    }
    //生活商城
    public function home()
    {    	
    	$m = M('Shop_goods');
    	$map['status'] = 1;
    	$map['is_recommend'] = 1;
        //$map['cid'] = array('not in','13,16,17');
    	$cache = $m->where($map)->order('sorts DESC,id DESC')->select();
    	foreach ($cache as $k => $v) {
    		$listpic = $this->getPic($v['listpic']);
    		$cache[$k]['imgurl'] = $listpic['imgurl'];
    	}
        // $category = M('shop_cate') -> where('pid = 0 and id <> 13') -> field('id,name,icon') -> limit(4) -> order('id DESC') -> select();
        // foreach ($category as $key => $value) {
        //     $result = M('upload_img') ->field('savename,savepath') -> where('id = '.$value['icon']) -> find();
        //     $category[$key]['img'] = $result['savepath'].$result['savename'];
        // }
        // $this->assign('category', $category);
    	$this->assign('cache', $cache);
    	$this->assign('actname', 'ftcategory');
    	$this->display();
    }

    public function bianmin()
    {       
        $m = M('Shop_goods');
        $map['status'] = 1;
        $map['is_recommend'] = 1;
        //$map['cid'] = array('not in','13,16,17');
        $cache = $m->where($map)->order('sorts DESC,id DESC')->select();
        foreach ($cache as $k => $v) {
            $listpic = $this->getPic($v['listpic']);
            $cache[$k]['imgurl'] = $listpic['imgurl'];
        }
        // $category = M('shop_cate') -> where('pid = 0 and id <> 13') -> field('id,name,icon') -> limit(4) -> order('id DESC') -> select();
        // foreach ($category as $key => $value) {
        //     $result = M('upload_img') ->field('savename,savepath') -> where('id = '.$value['icon']) -> find();
        //     $category[$key]['img'] = $result['savepath'].$result['savename'];
        // }
        // $this->assign('category', $category);
        $this->assign('cache', $cache);
        $this->assign('actname', 'ftbase');
        $this->display();
    }
    public function category()
    {
    	//获取顶级分类
     //    $whe['status'] = 1;
     //    $whe['pid'] = 0;
     //    $whe['id'] = array('notin','13,16,17');
    	// $cates = M('Shop_cate')->where($whe)->select();
    	// $cid = I('type');
    	// if(!$cid && $cates) $cid = $cates[0]['id'];
     //    $this->assign('cid', $cid);
     //    $twocate = M('Shop_cate') -> where('pid = '.$cid) -> select();
     //    $ccid = I('ccid');
     //    if(!$ccid && $twocate){
     //        $cid = $twocate[0]['id'];
     //    }elseif($ccid){
     //        $cid = $ccid;
     //    }
    	// $map['cid'] = $cid;
    	// $map['status'] = 1;
    	// $list = M('Shop_goods')->where($map)->order('sorts DESC,id DESC')->select();
    	// foreach ($list as $k => $v) {
    	// 	$listpic = $this->getPic($v['listpic']);
    	// 	$list[$k]['imgurl'] = $listpic['imgurl'];
    	// }
     //    $this->assign('twocate',$twocate);
     //    $this->assign('ccid',$cid); 
    	// $this->assign('list', $list);
    	
        $whe['status'] = 1;
        $whe['pid'] = 0;
        $whe['id'] = array('notin','13,16,17');
        $cates = M('Shop_cate')->where($whe)->order('sorts DESC')->select();
        foreach ($cates as $key => $value) {

          
            $listpic = $this->getPic($value['icon']);
            $cates[$key]['imgurl'] = $listpic['imgurl'];
            
           

            $cates[$key]['two'] = M('Shop_cate') -> where('pid = '.$value['id'])->order('sorts DESC') -> select();
            // var_dump($cates[$key]);
            foreach ($cates[$key]['two'] as $k => $v) {
                $cates[$key]['two'][$k]['tree'] = M('Shop_cate') -> where('pid = '.$v['id']) ->order('sorts DESC')-> select();
            }
        }
        // var_dump($cates);exit;
    	$this->assign('cates', $cates);
    	//高亮底导航
    	$this->assign('actname', 'ftcategory');
    	
    	$this->display();  
    }

    public function goodslist(){
        $cid = I('cid');
        if(!$cid){
            $this->diemsg(0, '缺少ID参数!');
        }
        $this->assign('cid', $cid);
        $name = I('name') ? I('name') : '';
        $where = ' status=1';
        if ($name) {
        	$where .= " AND name LIKE '%$name%'";
            $this->assign('name', $name);
        }

        $cid2=I('cid2');
        if(!$cid2){
            $cid2=0;
        }
        if ($cid2) {
            $where .= ' AND cid2='.$cid2;   
         }

        $where .= ' AND (cid='.$cid.' OR FIND_IN_SET('.$cid.',extend_cid))';
        $list = M('Shop_goods')->where($where)->order('sorts DESC,id DESC')->select();
        foreach ($list as $k => $v) {
            $listpic = $this->getPic($v['listpic']);
            $list[$k]['imgurl'] = $listpic['imgurl'];
        }

      
        $this->assign('cid2',$cid2);

        $cate2=M('Shop_cate2')->select();
        $this->assign("cate2",$cate2);

        $this->assign('ccid',$cid); 
        $this->assign('actname', 'ftcategory');
        $this->assign('list', $list);
        $this->display();
    }

    //山羊圈
    public function goat()
    {
    	//高亮底导航
    	$this->assign('actname', 'ftgoat');
    	 
    	$this->display();
    }
    public function goods()
    {
    	$id = I('id') ? I('id') : $this->diemsg(0, '缺少ID参数!');
        $vip = M('vip')->field("score,groupid,vip_expiration_time") -> where('id = '.$_SESSION['WAP']['vipid']) ->find();

        $this->assign('score',$vip['score']);
    	$m = M('Shop_goods');
    	$cache = $m->where('id=' . $id)->find();
    	if (!$cache) {
    		$this->error('此商品已下架！', U('App/Shop/index'));
    	}
    	if (!$cache['status']) {
    		$this->error('此商品已下架！', U('App/Shop/index'));
    	}
        //if($vip["groupid"]==2&&$vip["vip_expiration_time"]>time()){
        if($vip["groupid"]==2){
            $cache["isvip"]=1;
        }else{
            $cache["isvip"]=0;
        }

    	//自动计数
    	$rclick = $m->where('id=' . $id)->setInc('clicks', 1);
    	//读取标签
    	foreach (explode(',', $cache['lid']) as $k => $v) {
    		$label[$k] = M('ShopLabel')->where(array('id' => $v))->getField('name');
    	}
    	$cache['label'] = $label;
    	$this->assign('cache', $cache);
    	if ($cache['issku']) {
    		if ($cache['skuinfo']) {
    			$skuinfo = unserialize($cache['skuinfo']);
    			$skm = M('Shop_skuattr_item');
    			foreach ($skuinfo as $k => $v) {
    				$checked = explode(',', $v['checked']);
    				$attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select();
    				foreach ($attr as $kk => $vv) {
    					$attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
    				}
    				$skuinfo[$k]['allitems'] = $attr;
    			}
    			$this->assign('skuinfo', $skuinfo);
    		} else {
    			$this->diemsg(0, '此商品还没有设置SKU属性！');
    		}
    		$skuitems = M('Shop_goods_sku')->field('sku,skuattr,price,vprice,num,hdprice,hdnum')->where(array('goodsid' => $id, 'status' => 1))->select();
    		if (!$skuitems) {
    			$this->diemsg(0, '此商品还未生成SKU!');
    		}
    		$skujson = array();
    		foreach ($skuitems as $k => $v) {
    			$skujson[$v['sku']]['sku'] = $v['sku'];
    			$skujson[$v['sku']]['skuattr'] = $v['skuattr'];
    			$skujson[$v['sku']]['price'] = $v['price'];
    			$skujson[$v['sku']]['vprice'] = $v['vprice'];
    			$skujson[$v['sku']]['num'] = $v['num'];
    			$skujson[$v['sku']]['hdprice'] = $v['hdprice'];
    			$skujson[$v['sku']]['hdnum'] = $v['hdnum'];
    		}
    		$this->assign('skujson', json_encode($skujson));
    	}
    
    	//绑定图集
    	if ($cache['album']) {
    		$appalbum = $this->getAlbum($cache['album']);
    		if ($appalbum) {
    			$this->assign('appalbum', $appalbum);
    		}
    	}
    	//绑定图片
    	if ($cache['pic']) {
    		$apppic = $this->getPic($cache['pic']);
    		if ($apppic) {
    			$this->assign('apppic', $apppic);
    		}
    	}
        //绑定购物车数量
        $basket = M('Shop_basket')->field("id,goodsid")->where(array('sid' => 0, 'vipid' => self::$WAP['vipid']))->select();
        $this->assign('basketnum', $basket?count($basket):0);
        $carExtendCid=array();
        $isame=0;
        //检查是否是区域产品
        $isadmin=0;
        if($basket){
           $cartlist=$m->where(array("id"=>$basket[0]["goodsid"]))->field("extend_cid,adminid")->find();
           if($cartlist&&$cartlist["extend_cid"]){
               $carExtendCid=explode(',', $cartlist["extend_cid"]);
               $isame=2;
           }else{
               $isame=1;
           }
           if($cartlist&&$cartlist["adminid"])
               $isadmin=$cartlist["adminid"];
        }



        //配送地址
    	$cates = getCate(M('Shop_cate'));
    	$cache['area'] = '';
    	if($cache['extend_cid']) {
    		$cache['extend_cid'] = explode(',', $cache['extend_cid']);
            $hasarea=0;
    		foreach($cache['extend_cid'] as $k => $v) {
    			$cache['area'] .= $cates[$v].'，';
                if($carExtendCid&&in_array($v,$carExtendCid)){
                    $hasarea=2;
                }
    		}
    		if($hasarea!=$isame)
    		    $isame=1;
    		$cache['area'] = trim($cache['area'], '，');
    	}else{
    	    $isame=0;
        }
        if($basket&&$isadmin!=$cache["adminid"]){
    	    $isame=1;
    	   $isadmin=1;

        }else{
            $isadmin=0;
        }
        $this->assign("isadmin",$isadmin);
        $this->assign("isame",$isame);//为1时，则不一样
        $shopset = M('shop_set') -> find();
        $this->assign('shopset',$shopset);
    	$cache['content'] = filter_the_content(htmlspecialchars_decode($cache['content']));
    	$this->assign('cache', $cache);
        $orderG['goodsid'] = $id;
        $orderG['vipid'] = $_SESSION['WAP']['vipid'];

        
        $this->assign('vipLv', session('WAP.vip.groupid') );
        $ordergoods = M('shop_order_goods') -> where($orderG) -> getField('num');
        $this->assign('ordernum',$ordergoods);

    	//绑定登陆跳转地址
    	$backurl = base64_encode(U('App/Shop/goods', array('id' => $id)));
    	$loginback = U('App/Vip/login', array('backurl' => $backurl));
    	$this->assign('loginback', $loginback);
    	$this->assign('lasturl', $backurl);
    	$this->display();
    }
    
    public function basket()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$lasturl = I('lasturl') ? I('lasturl') : $this->diemsg(0, '缺少LastURL参数');
    	$basketlasturl = base64_decode($lasturl);
    	$basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
    	$backurl = base64_encode($basketurl);
    	$basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//保存当前购物车地址
    	$this->assign('basketurl', $basketurl);
    	//保存登陆购物车地址
    	$this->assign('basketloginurl', $basketloginurl);
    	//保存购物车前地址
    	$this->assign('basketlasturl', $basketlasturl);
    	//保存购物车加密地址，用于OrderMaker正常返回
    	$this->assign('lasturlencode', $lasturl);
    	//已登陆
    	$m = M('Shop_basket');
    	$mgoods = M('Shop_goods');
    	$msku = M('Shop_goods_sku');
    	$returnurl = base64_decode($lasturl);
    	$this->assign('returnurl', $returnurl);
    	$cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
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
    		$goods = $mgoods->where('id=' . $v['goodsid'])->find();
    		$pic = $this->getPic($goods['pic']);
    		if ($v['sku']) {
    			//取商品数据
    			if ($goods['issku'] && $goods['status']) {
    				$map['sku'] = $v['sku'];
    				$sku = $msku->where($map)->find();
    				if ($sku['status']) {
    					if ($sku['num']) {
    						//调整购买量
    						$cache[$k]['name'] = $goods['name'];
    						$cache[$k]['skuattr'] = $sku['skuattr'];
    						$cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
    						$cache[$k]['price'] = $sku['price'];
    						$cache[$k]['total'] = $sku['num'];
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
    					$cache[$k]['name'] = $goods['name'];
    					$cache[$k]['skuattr'] = $sku['skuattr'];
    					$cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
    					$cache[$k]['price'] = $goods['price'];
    					$cache[$k]['total'] = $goods['num'];
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
    		$rdel = $m->delete($todelids);
    		if (!$rdel) {
    			$this->error('购物车获取失败，请重新尝试！');
    		}
    	}
        
    	if(empty($cache)) {
    		$this->display('Shop_basket_empty');
    	} else {
    	
    		$this->assign('cache', $cache);
    		$this->assign('totalprice', $totalprice);
    		$this->assign('totalnum', $totalnum);
    		$this->display();
    	}
    }
    
    //添加购物车
    public function addtobasket()
    {
    	if (IS_AJAX) {
    		$m = M('Shop_basket');
    		$data = I('post.');
    		if (!$data) {
    			$info['status'] = 0;
    			$info['msg'] = '未获取数据，请重新尝试';
    			$this->ajaxReturn($info);
    		}
    		//删除
    		if($data["isame"]=="1"){
                $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->delete();
            }
    		//区分SKU模式
    		if ($data['sku']) {
    			$old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'sku' => $data['sku']))->find();
    			if ($old) {
    				$old['num'] = $old['num'] + $data['num'];
    				$rold = $m->save($old);
    				if ($rold === FALSE) {
    					$info['status'] = 0;
    					$info['msg'] = '添加购物车失败，请重新尝试！';
    				} else {
    					$total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
    					$info['total'] = $total;
    					$info['status'] = 1;
    					$info['msg'] = '添加购物车成功！';
    				}
    			} else {
    				$rold = $m->add($data);
    				if ($rold) {
    					$info['status'] = 1;
    					$info['msg'] = '添加购物车成功！';
    				} else {
    					$info['status'] = 0;
    					$info['msg'] = '添加购物车失败，请重新尝试！';
    				}
    			}
    		} else {
    			$old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'goodsid' => $data['goodsid']))->find();
    			if ($old) {
    				$old['num'] = $old['num'] + $data['num'];
    				$rold = $m->save($old);
    				if ($rold === FALSE) {
    					$info['status'] = 0;
    					$info['msg'] = '添加购物车失败，请重新尝试！';
    				} else {
    					$total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
    					$info['total'] = $total;
    					$info['status'] = 1;
    					$info['msg'] = '添加购物车成功！';
    				}
    			} else {
    				$rold = $m->add($data);
    				if ($rold) {
    					$total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
    					$info['total'] = $total;
    					$info['status'] = 1;
    					$info['msg'] = '添加购物车成功！';
    				} else {
    					$info['status'] = 0;
    					$info['msg'] = '添加购物车失败，请重新尝试！';
    				}
    			}
    		}
    		$this->ajaxReturn($info);
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    
    //删除购物车
    public function delbasket()
    {
    	if (IS_AJAX) {
    		$id = I('id');
    		if (!$id) {
    			$info['status'] = 0;
    			$info['msg'] = '未获取ID参数,请重新尝试！';
    			$this->ajaxReturn($info);
    		}
    		$m = M('Shop_basket');
    		$re = $m->where('id=' . $id)->delete();
    		if ($re) {
    			$info['status'] = 1;
    			$info['msg'] = '删除成功，更新购物车状态...';
    
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '删除失败，自动重新加载购物车...';
    		}
    		$this->ajaxReturn($info);
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    
    //清空购物车
    public function clearbasket()
    {
    	if (IS_AJAX) {
    		$sid = $_GET['sid'];
    		//前端必须保证登陆状态
    		$vipid = $_SESSION['WAP']['vipid'];
    		if (!$vipid) {
    			$info['status'] = 3;
    			$info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
    			$this->ajaxReturn($info);
    		}
    		if ($sid == '') {
    			$info['status'] = 0;
    			$info['msg'] = '未获取SID参数,请重新尝试！';
    			$this->ajaxReturn($info);
    		}
    		$m = M('Shop_basket');
    		$re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
    		if ($re) {
    			$info['status'] = 2;
    			$info['msg'] = '购物车已清空';
    			$this->ajaxReturn($info);
    		} else {
    			$info['status'] = 0;
    			$info['msg'] = '购物车清空失败，请重新尝试！';
    			$this->ajaxReturn($info);
    		}
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    
    //购物车库存检测
    public function checkbasket()
    {
    	if (IS_AJAX) {
    		unset($_SESSION['isact']);
    		unset($_SESSION['groupid']);
    		$sid = $_GET['sid'];
    		//前端必须保证登陆状态
    		$vipid = $_SESSION['WAP']['vipid'];
    		if (!$vipid) {
    			$info['status'] = 3;
    			$info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
    			$this->ajaxReturn($info);
    		}
    		$arr = $_POST;
    		if ($sid == '') {
    			$info['status'] = 0;
    			$info['msg'] = '未获取SID参数';
    			$this->ajaxReturn($info);
    		}
    		if (!$arr) {
    			$info['status'] = 0;
    			$info['msg'] = '未获取数据，请重新尝试';
    			$this->ajaxReturn($info);
    		}
    		$vip = self::$WAP['vip'];
    		if($vip['is_auth'] != 2) {
    			$info['status'] = 0;
    			$info['url'] = U('App/Vip/auth');
    			$info['msg'] = '您未进行实名认证，马上去验证？';
    			$this->ajaxReturn($info);
    		}
    		$m = M('Shop_basket');
    		$mgoods = M('Shop_goods');
    		$msku = M('Shop_goods_sku');
    		$data = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
    		foreach ($data as $k => $v) {
    			$goods = $mgoods->where('id=' . $v['goodsid'])->find();
    			if ($v['sku']) {
    				$sku = $msku->where(array('sku' => $v['sku']))->find();
    				if ($sku && $sku['status'] && $goods && $goods['issku'] && $goods['status']) {
    					$nownum = $arr[$v['id']];
    					if ($sku['num'] - $nownum >= 0) {
    						//保存购物车新库存
    						if ($nownum <> $v['num']) {
    							$v['num'] = $nownum;
    							$rda = $m->save($v);
    						}
    					} else {
    						$info['status'] = 2;
    						$info['msg'] = '存在已下架或库存不足商品！';
    						$this->ajaxReturn($info);
    					}
    
    				} else {
    					$info['status'] = 2;
    					$info['msg'] = '存在已下架或库存不足商品！';
    					$this->ajaxReturn($info);
    				}
    			} else {
    				if ($goods && $goods['status']) {
    					$nownum = $arr[$v['id']];
    					if ($goods['num'] - $nownum >= 0) {
    						//保存购物车新库存
    						if ($nownum <> $v['num']) {
    							$v['num'] = $nownum;
    							$rda = $m->save($v);
    						}
    					} else {
    						$info['status'] = 2;
    						$info['msg'] = '存在已下架或库存不足商品！';
    						$this->ajaxReturn($info);
    					}
    
    				} else {
    					$info['status'] = 2;
    					$info['msg'] = '存在已下架或库存不足商品！';
    					$this->ajaxReturn($info);
    				}
    
    			}
    		}
    		$info['status'] = 1;
    		$info['msg'] = '商品库存检测通过，进入结算页面！';
    		$this->ajaxReturn($info);
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    
    //立刻购买逻辑
    public function fastbuy()
    {
    	if (IS_AJAX) {
    		$m = M('Shop_basket');
    		$mgroup = M('Activity_group');
    		$mact = M('Activity');
    		$data = I('post.');
    		if (!$data) {
    			$info['status'] = 0;
    			$info['msg'] = '未获取数据，请重新尝试';
    			$this->ajaxReturn($info);
    		}
    		$vip = self::$WAP['vip'];
    		//前端必须保证登陆状态
    		$vipid = $_SESSION['WAP']['vipid'];
    		if($vip['is_auth'] != 2) {
    			$info['status'] = 0;
    			$info['url'] = U('App/Vip/auth');
    			$info['msg'] = '您未进行实名认证，马上去验证？';
    			$this->ajaxReturn($info);
    		}
            unset($_SESSION['isact']);
            unset($_SESSION['groupid']);
            if($data['isact']){
                $_SESSION['isact'] = 1;
                $_SESSION['groupid'] = $data['groupid'];
                if($data['groupid'] == 0) {
                	$condition['goods_id'] = $data['goodsid'];
                	$condition['vipid'] = $vip['id'];
                	$condition['status'] = 0;
                	$group = $mgroup->where($condition)->count();
                	if($group>0) {
                		$info['status'] = 0;
                		$info['msg'] = '您已开团';
                		$this->ajaxReturn($info);
                	}
                } else {
                	$group = $mgroup->where(array('id'=>$data['groupid']))->find();
                	if(empty($group)) {
                		$info['status'] = 0;
                		$info['msg'] = '拼团不存在';
                		$this->ajaxReturn($info);
                	}
                	if($group['rtime'] <= time()) {
                		$info['status'] = 0;
                		$info['msg'] = '拼团已过期';
                		$this->ajaxReturn($info);
                	}
                	$condition['groupid'] = $data['groupid'];
                	$condition['vipid'] = $vip['id'];
                	$act = $mact->where($condition)->count();
                	if($act>0) {
                		$info['status'] = 0;
                		$info['msg'] = '您已参团';
                		$this->ajaxReturn($info);
                	}
                }
            } elseif($data['bid']) {
            	$bargain = M('bargain') -> where(array('id'=>$data['bid'],'vipid'=>$vipid,'helpvipid'=>$vipid))->find();
            	if(empty($bargain)) {
            		$info['status'] = 0;
            		$info['msg'] = '砍价不存在';
            		$this->ajaxReturn($info);
            	}
            	//是否已下单
            	$isbuyed = M('shop_order')->where(array('bargain_id'=>$data['bid'],'ispay'=>1))->count();
            	if($isbuyed>0) {
            		$info['status'] = 0;
            		$info['msg'] = '砍价已完成';
            		$this->ajaxReturn($info);
            	}
            }
    		//清除购物车
    		$sid = 0;
    		$re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
    		//区分SKU模式
    		if ($data['sku']) {
    			$rold = $m->add($data);
    			if ($rold) {
    				$info['status'] = 1;
    				$info['msg'] = '库存检测通过！2秒后自动生成订单！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '通讯失败，请重新尝试！';
    			}
    		} else {
    			$rold = $m->add($data);
    			if ($rold) {
    				$info['status'] = 1;
    				$info['msg'] = '库存检测通过！2秒后自动生成订单！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '通讯失败，请重新尝试！';
    			}
    		}
    		$this->ajaxReturn($info);
    	} else {
    		$this->diemsg(0, '禁止外部访问！');
    	}
    }
    
    public function orderMake()
    {
    	if (IS_POST) {
    		$vip = self::$WAP['vip'];
    		$vipid = $_SESSION['WAP']['vipid'];
    		if($vip['is_auth'] != 2) {
    			$this->error('您未进行实名认证');
    		}
    		$result = calculate_price($vipid);
    		if($result['status']) {
    			$totalprice = $result['result']['totalprice'];
    			$totalnum = $result['result']['totalnum'];
                $cache = $result['result']['goodslist'];
    		} else {
    			$this->error($result['msg']);
    		}
            
    		$morder = M('Shop_order');
    		$data = I('post.');
    		//是否能快递
    		$cankd = 1;
    		$adminid=0;
    		foreach ($cache as $key => $value) {
    			$postage = M('Shop_goods') -> where('id = '.$value['goodsid']) ->field('postage,adminid')->find();
    			//判断是否能快递
    			if($postage&&$postage['postage'] == ''&&$cankd){
    				$cankd = 0;
    			}
                //判断是否同一个区域管理员
                if($adminid&&$adminid!=$postage["adminid"]){
    			    $this->error("你的订单内包含不同区域产品");
                }
                $adminid=$postage["adminid"];
    		}
            $data["adminid"]=$adminid;

    		if($cankd == 0 && $data['delivery'] != 'since') {
    			$this->error('该订单不能选择快递运输！');
    		}
    		$this->assign('cankd', $cankd);
            //邮费逻辑
            $yf=0;
            if($data['delivery'] != 'since') {
            	foreach ($cache as $key => $value) {
            		$postage = M('Shop_goods') -> where('id = '.$value['goodsid']) ->getField('postage');
            		if($value['num']>1){
            			$postage += $postage*($value['num']-1)*0.5;
            		}
            		$yf += $postage;
            	}
            }
            $data['yf'] = $yf;
            $this->assign('yf', $yf);
    		if($totalnum != $data['totalnum']) {
    			$this->error('数据异常，请重新下单', U('App/Shop/index'));
    		}
    		if($totalprice != $data['totalprice']) {
    			$this->error('数据异常，请重新下单', U('App/Shop/index'));
    		}
    		if($data['delivery'] == 'since') {
    			if(!$data['sinceid']) {
    				$this->error('缺少自提点ID！');
    			}
    			$since = M('since')->where('id='.$data['sinceid'])->find();
    			if(empty($since)) {
    				$this->error('自提点不存在！');
    			}
    			$data['vipname'] = $vip['name'];
    			$data['vipmobile'] = $vip['mobile'];
    			$data['vipaddress'] = $since['address'];
    		}
    		if(!$data['vipaddress']) {
    			$this->error('未选择配送方式！');
    		}
    		if($data['bid'] >0 ) {
    			$bargain = M('bargain') -> where(array('id'=>$data['bid'],'vipid'=>$vipid,'helpvipid'=>$vipid))->find();
    			if(empty($bargain)) {
    				$this->error('砍价不存在');
    			}
    			//是否已下单
    			$isbuyed = M('shop_order')->where(array('bargain_id'=>$data['bid'],'ispay'=>1))->count();
    			if($isbuyed>0) {
    				$this->error('您已下单');
    			}
    			$data['bargain_id'] = $data['bid'];
    		}
    		$data['items'] = stripslashes(htmlspecialchars_decode($data['items']));            
            $temporary = unserialize($data['items']);
            $jifen = 0;
            $mgoods = M('Shop_goods');
            foreach ($temporary as $key => $value) {
            	$type = $mgoods -> where('id = '.$value['goodsid']) -> getField('type');
            	if($type==2){
            		$data['type'] = 2;
            	}elseif($type==1){
            		if($_SESSION['isact']){
            			$data['type'] = 1;
            		}
            	}
                $jif = $mgoods -> where('id = '.$value['goodsid']) -> getField('integral');
                $jifen += $jif;
            }
            $data['vipid'] = $vip['id'];
            $data['integral'] = $jifen;
    		$data['ispay'] = 0;
    		$data['status'] = 1;//订单成功，未付款
    		$data['ctime'] = time();
    		$data['payprice'] = $totalprice+$yf;
            if($data["adminid"]){
                $admin=M("user")->where(array('id'=>$data["adminid"]))->find();

                if($admin){
                    if($vip["groupid"]<2&&$admin["order_percent"])
                    {
                        //管理费用
                        $data["adminfee"]=  $totalprice*$admin["order_percent"]/100;
                    }else if($vip["groupid"]==2&&$admin["vip_fee"]>0){
                        //合伙人价格
                        $data["adminfee"]=  $totalprice*$admin["vip_fee"]/100;
                    }

                }
            }
    		//代金卷流程
    		if ($data['djqid']) {
    			$mcard = M('Vip_card');
    			$djq = $mcard->where('id=' . $data['djqid'])->find();
    			if (!$djq) {
    				$this->error('通讯失败！请重新尝试支付！');
    			}
    			if ($djq['usetime']) {
    				$this->error('此代金卷已使用！');
    			}
    			$djq['status'] = 2;
    			$djq['usetime'] = time();
    			$rdjq = $mcard->save($djq);
    			if (FALSE === $rdjq) {
    				$this->error('通讯失败！请重新尝试支付！');
    			}
    			//修改支付价格
    			$data['payprice'] = $data['totalprice'] - $djq['money'];
    		}
            if($data["delivery"]&&$data["sinceid"]){
                $dmap["vipid"]=array("GT",0);
                $dmap["status"]=0;
                $dmap['_string']='FIND_IN_SET("'.$data["sinceid"].'", adlist)';
                $deliveryman =M("deliveryman")->field("vipid")->where($dmap)->find();
                if($deliveryman&&$deliveryman["status"]==0){
                    $data["deliveryid"]=$deliveryman["vipid"];
                    if($deliveryman["fee"]>0&&$vip["groupid"]<2){
                        $data["delivery_fee"]=round( $totalprice*$deliveryman["fee"]/100,2);
                    }
                }
            }
    		$re = $morder->add($data);
    		if ($re) {
    			$old = $morder->where('id=' . $re)->setField('oid', 'G'.date('YmdHis') . '-' . $re);
    			if (FALSE !== $old) {
    			    //

    				//后端日志
    				$mlog = M('Shop_order_syslog');
    				$dlog['oid'] = $re;
    				$dlog['msg'] = '订单创建成功';
    				$dlog['type'] = 1;
    				$dlog['ctime'] = time();
    				$rlog = $mlog->add($dlog);
    				//清空购物车
    				$rbask = M('Shop_basket')->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->delete();
    				//					$this->success('订单创建成功，转向支付界面!',U('App/Shop/pay/',array('sid'=>$data['sid'],'orderid'=>$re)));
    				$this->redirect('App/Shop/pay/', array('sid' => $data['sid'], 'orderid' => $re));
    			} else {
    				$old = $morder->delete($re);
    				$this->error('订单生成失败！请重新尝试！');
    			}
    		} else {
    			//可能存在代金卷问题
    			$this->error('订单生成失败！请重新尝试！');
    		}
    
    	} else {
    		//非提交状态
    		$sid = $_GET['sid'] <> '' ? $_GET['sid'] : $this->diemsg(0, '缺少SID参数');//sid可以为0
    		$lasturl = $_GET['lasturl'] ? $_GET['lasturl'] : $this->diemsg(0, '缺少LastURL参数');
    		$basketlasturl = base64_decode($lasturl);
    		$basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
    		$backurl = base64_encode($basketurl);
    		$basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
    		$re = $this->checkLogin($backurl);
    		//保存当前购物车地址
    		$this->assign('basketurl', $basketurl);
    		//保存登陆购物车地址
    		$this->assign('basketloginurl', $basketloginurl);
    		//保存购物车前地址
    		$this->assign('basketlasturl', $basketlasturl);
    		//保存lasturlencode
    		//保存购物车加密地址，用于OrderMaker正常返回
    		$this->assign('lasturlencode', $lasturl);
    		$this->assign('sid', $sid);
    		//清空临时地址
    		unset($_SESSION['WAP']['orderURL']);
    		$result = calculate_price($_SESSION['WAP']['vipid']);
    		if($result['status']) {
    			$totalprice = $result['result']['totalprice'];
    			$totalnum = $result['result']['totalnum'];
    			$allitems = $result['result']['allitems'];
    			$cache = $result['result']['goodslist'];
    			$this->assign('cache', $cache);
    			$this->assign('totalprice', $totalprice);
    			$this->assign('totalnum', $totalnum);
    			$this->assign('allitems', $allitems);
    		} else {
    			$this->error($result['msg'], $basketurl);
    		}
    		//VIP信息
    		$vipadd = I('vipadd');
    		if ($vipadd) {
    			$vip = M('Vip_address')->where('id=' . $vipadd)->find();
    		} else {
    			$vip = M('Vip_address')->where('vipid=' . $_SESSION['WAP']['vipid'])->find();
    		}
    		$this->assign('vip', $vip);
    		//可用代金卷
    		$mdjq = M('Vip_card');
    		$mapdjq['type'] = 2;
    		$mapdjq['vipid'] = $_SESSION['WAP']['vipid'];
    		$mapdjq['status'] = 1;//1为可以使用
    		$mapdjq['usetime'] = 0;
    		$mapdjq['etime'] = array('gt', time());
    		$mapdjq['usemoney'] = array('lt', $totalprice);
    		$djq = $mdjq->field('id,money,usemoney')->where($mapdjq)->select();
    		$djq_count = count($djq);
    		//邮费逻辑
    		// if (self::$WAP['shopset']['isyf']) {
    		// 	$this->assign('isyf', 1);
    		// 	$yf = $totalprice >= self::$WAP['shopset']['yftop'] ? 0 : self::$WAP['shopset']['yf'];
    		// 	$this->assign('yf', $yf);
    		// 	$this->assign('yftop', self::$WAP['shopset']['yftop']);
    		// } else {
    		// 	$this->assign('isyf', 0);
    		// 	$this->assign('yf', 0);
    		// }
            $yf=0;
            if(I('delivery') != 'since') {
	            foreach ($cache as $key => $value) {
	                $postage = M('Shop_goods') -> where('id = '.$value['goodsid']) ->getField('postage');
	                if($value['num']>1){
	                    $postage += $postage*($value['num']-1)*0.5;
	                }
	                $yf += $postage;
	            }
            }
            $this->assign('yf', $yf);
     		$payprice = $totalprice + $yf;
    		//是否可以用余额支付
    		$useryue = $_SESSION['WAP']['vip']['money'];
    		$isyue = $_SESSION['WAP']['vip']['money'] - $payprice >= 0 ? 0 : 1;
    		$sort_left = $sort_money = $sort_usemoney = array();
    		foreach($djq as $k => $v) {
    			$djq[$k]['left'] = $v['usemoney'] - $payprice;
    			$sort_money[$k] = $v['money'];
    			$sort_left[$k] = $djq[$k]['left'] > 0 ? 0 : 1;
    			$sort_usemoney[$k] = $v['usermoney'];
    		};
    		$pickup = checkPickup($allitems);
    		array_multisort($sort_left, SORT_DESC, $sort_money, SORT_DESC, $sort_usemoney, SORT_DESC, $djq);
    		$this->assign('djq', $djq);
    		$this->assign('djq_count', $djq_count>0 ? $djq_count : 0);
    		$this->assign('isyue', $isyue);
    		$this->assign('payprice', $payprice);
    		$this->assign('pickup', $pickup);
    		$this->assign('pickups', $pickup ? serialize($pickup) : '');
    		//是否能快递
    		$cankd = 1;
    		foreach ($cache as $key => $value) {
    			$postage = M('Shop_goods') -> where('id = '.$value['goodsid']) ->getField('postage');

    			if($postage == ''){
    				$cankd = 0;
    				break;
    			}
    		}
    		//获取省份
    		$p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
    		$this->assign('province', $p);
    		$this->assign('cankd', $cankd);
    		$this->assign('bid', I('bid'));
    		$this->display();
    	}
    
    }
    
    //订单地址跳转
    public function orderAddress()
    {
    	$sid = I('sid');
    	$lasturlencode = I('lasturl');
    	$backurl = U('App/Shop/orderMake', array('sid' => $sid, 'lasturl' => $lasturlencode));
    	$_SESSION['WAP']['orderURL'] = $backurl;
    	$this->redirect('App/Vip/address');
    }
    
    //订单列表
    public function orderList()
    {
    	$type = I('type') ? I('type') : 4;
    	$this->assign('type', $type);
    	$bkurl = U('App/Shop/orderList', array('type' => $type));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$map['vipid'] = $vipid;
        $map['type'] = 0;
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
            case '6':
                //团购
                $map['type'] = 1;
                break;
            case '7':
                //砍价
                $map['type'] = 2;
                break;
    		default:
    			$map['status'] = 1;
    			break;
    	}
        if($map['type']==0){
            $this->assign('title','订单列表');
        }elseif($map['type']==1){
            $this->assign('title','团购列表');
        }else{
            $this->assign('title','砍价列表');
        }
        $this->assign('act',$map['type']);
    	$cache = $m->where($map)->order('ctime desc')->select();
    	if ($cache) {
    		foreach ($cache as $k => $v) {
    			if ($v['items']) {
    				$cache[$k]['items'] = unserialize($v['items']);
    			}
    		}
    	}
    	//待付款订单数
    	$count = $m->where('status=1 and vipid='.$vipid)->count();
    	$this->assign('count', $count);
    	$this->assign('cache', $cache);
    
    	//高亮底导航
    	$this->assign('actname', 'ftvip');
    	$this->display();
    }
    /**
     * 配送订单列表
     * author: feng
     * create: 2017/9/26 20:55
     */
    public function deliveryOrderList()
    {
        $type = I('type') ? I('type') : 0;
        $this->assign('type', $type);
        $bkurl = U('App/Shop/deliveryOrderList', array('type' => $type));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $dmap['vipid'] = $vipid;

        $deliveryman =M("deliveryman")->where($dmap)->find();
        if(!$deliveryman){
            $this->error('你没有权限查看该列表!');
        }
        if(!$deliveryman["adlist"]){
            $this->error('你还没有设置自提点，请联系管理员!');
        }
        $vip=M("vip")->where(array("id"=>$vipid))->find();
        $map["delivery"]="since";
        $map["deliveryid"]=$vipid;
        $map['type'] = 0;
        $map["status"]=array("GT",1);

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

        $cache = $m->where($map)->order('ctime desc')->select();
        if ($cache) {
            foreach ($cache as $k => $v) {
                if ($v['items']) {
                    $cache[$k]['items'] = unserialize($v['items']);
                }
                if(!($v["delivery_fee"]>0)){
                    if(M("vip")->where(array("id"=>$v["vipid"]))->getField("groupid")<2)
                        $cache[$k]['delivery_fee']=round($v["totalprice"]*$deliveryman['fee']/100,2);

                }

            }
        }

        $this->assign('cache', $cache);

        //高亮底导航
        $this->assign('actname', 'ftvip');
        $this->display();
    }
    /**
     * 配送订单详情
     * author: feng
     * create: 2017/9/27 9:45
     */
    public function deliveryOrderDetail()
    {

        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $dmap['vipid'] = $vipid;
        $deliveryman =M("deliveryman")->where($dmap)->find();
        if(!$deliveryman){
            $this->error('你没有权限查看该列表!');
        }


        $map["delivery"]="since";

        $map['type'] = 0;
        $map["status"]=array("GT",1);

        //$map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();

        if (!$cache) {
            $this->error('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);
        //order日志
        $mlog = M('Shop_order_log');
        $log = $mlog->where('oid=' . $cache['id'])->select();
        $this->assign('log', $log);
        if (!$cache['status'] == 1) {
            //是否可以用余额支付
            $useryue = $_SESSION['WAP']['vip']['money'];
            $isyue = $_SESSION['WAP']['vip']['money'] - $cache['payprice'] >= 0 ? 0 : 1;
            $this->assign('isyue', $isyue);
        }
        if(!($cache["delivery_fee"]>0)){
            if(M("vip")->where(array("id"=>$cache["vipid"]))->getField("groupid")<2){
                $cache['delivery_fee']=round($cache["totalprice"]*$deliveryman['fee']/100,2);
            }
        }
        $this->assign('cache', $cache);

        /*
         //代金卷调用
         if ($cache['djqid']) {
         $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
         $this->assign('djq', $djq);
         }
         */
        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单详情
    //订单列表
    public function orderDetail()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$map['sid'] = $sid;
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg('此订单不存在!');
    	}
    	$cache['items'] = unserialize($cache['items']);
    	//order日志
    	$mlog = M('Shop_order_log');
    	$log = $mlog->where('oid=' . $cache['id'])->select();
    	$this->assign('log', $log);
    	if (!$cache['status'] == 1) {
    		//是否可以用余额支付
    		$useryue = $_SESSION['WAP']['vip']['money'];
    		$isyue = $_SESSION['WAP']['vip']['money'] - $cache['payprice'] >= 0 ? 0 : 1;
    		$this->assign('isyue', $isyue);
    	}
    	$this->assign('cache', $cache);
    	/*
    	 //代金卷调用
    	 if ($cache['djqid']) {
    	 $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
    	 $this->assign('djq', $djq);
    	 }
    	 */
    	//高亮底导航
    	$this->assign('actname', 'ftorder');
    	$this->display();
    }
    
    //订单取消
    public function orderCancel()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$map['sid'] = $sid;
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg(0, '此订单不存在!');
    	}
    	if ($cache['status'] <> 1) {
    		$this->error('只有未付款订单可以取消！');
    	}
    	$re = $m->where($map)->setField('status', 0);
    	if ($re) {
    		//订单取消只有后端日志
    		$mslog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '订单取消';
    		$dlog['type'] = 0;
    		$dlog['ctime'] = time();
    		$rlog = $mslog->add($dlog);
    		$this->success('订单取消成功！');
    	} else {
    		$this->error('订单取消失败,请重新尝试！');
    	}
    }
    
    //确认收货
    public function orderOK()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$map['sid'] = $sid;
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg(0, '此订单不存在!');
    	}
    	if ($cache['status'] <> 3) {
    		$this->error('只有待收货订单可以确认收货！');
    	}
    	$cache['etime'] = time();//交易完成时间
    	$cache['status'] = 5;
    	$rod = $m->save($cache);
    	if (FALSE !== $rod) {
    		//修改会员账户金额、经验、积分、等级   		 
    		if (self::$WAP['vipset']['xf_exp'] > 0) {
    			$data_vip['exp'] = array('exp', 'exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$data_vip['levelid'] = $level['levelid'];
    			if (self::$SHOP['set']['isfx']) {
    				//会员分销统计字段
    				//会员购买一次变成分销商
    				$data_vip['isfx'] = 1;
    			}
    			//会员合计支付
    			$data_vip['total_buy'] = $data_vip['total_buy'] + $cache['payprice'];
    		}
    		//获取赠送积分数
    		$score = get_order_credit($cache);
    		if($score > 0){
    			$data_vip['score'] = array('exp', 'score+' .$score );
    		}
    		if(!empty($data_vip)) {
	    		$re = M('Vip')->where('id='.$cache['vipid'])->save($data_vip);
	    		if (FALSE === $re) {
	    			$this->error('更新会员信息失败！');
	    		} else {
	    			if($score > 0){
	    				log_credit($cache['id'], $score, 3, $cache['oid']);
	    			}
	    		}
    		}
            //如果是区域管理员，需要扣取手续费，并将费用转到区域管理员的帐号
            handleAdminFee($cache);
            //如果是合伙人，不用参与佣金发放  1 普通会员   2 vip会员
            $vip = M('Vip')->where('id='.$cache['vipid']);
            //判断购物是否是合伙人，不是合伙人则统计配送费用
            handleDeliveryFee($cache,$vip);
            if($vip["groupid"]==1){
            handleCommission($cache, $orderid);//发放分销佣金
            }
    
    		$mlog = M('Shop_order_log');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '确认收货,交易完成。';
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    
    		//后端日志
    		$mlog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '交易完成-会员点击';
    		$dlog['type'] = 5;
    		$dlog['paytype'] = $cache['paytype'];
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		$this->success('交易已完成，感谢您的支持！');
    	} else {
    		//后端日志
    		$mlog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '确认收货失败';
    		$dlog['type'] = -1;
    		$dlog['paytype'] = $cache['paytype'];
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		$this->error('确认收货失败，请重新尝试！');
    	}
    }
    
    //确认提货
    public function orderPickupSuccess()
    {
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderDetail', array('sid' =>I('sid'), 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();

    	if (!$cache) {
    		$this->diemsg(0, '此订单不存在!');
    	}
    	if ($cache['delivery'] <> 'since') {
    		$this->error('只有配送方式是自提的订单才可以确认自提！');
    	}
    	if ($cache['status'] <> 3) {
    		$this->error('只有到达自提点的订单才可以确认自提！');
    	}
    	$cache['pickuptime'] = time();//自提时间
    	$cache['etime'] = time();//交易完成时间
    	$cache['status'] = 5;
    	$rod = $m->save($cache);
    	if (FALSE !== $rod) {
    		//修改会员账户金额、经验、积分、等级
    		if (self::$WAP['vipset']['xf_exp'] > 0) {
    			$data_vip['exp'] = array('exp', 'exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($cache['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
    			$data_vip['levelid'] = $level['levelid'];
    			/*
    			if (self::$SHOP['set']['isfx']) {
    				//会员分销统计字段
    				//会员购买一次变成分销商
    				$data_vip['isfx'] = 1;
    			}
    			*/
    			//会员合计支付
    			$data_vip['total_buy'] = $data_vip['total_buy'] + $cache['payprice'];
    		}
    		//获取赠送积分数
    		$score = get_order_credit($cache);
    		if($score > 0){
    			$data_vip['score'] = array('exp', 'score+' .$score );
    		}
    		if(!empty($data_vip)) {
    			$re = M('Vip')->where('id='.$cache['vipid'])->save($data_vip);
    			if (FALSE === $re) {
    				$this->error('更新会员信息失败！');
    			} else {
    				if($score > 0){
    					log_credit($cache['id'], $score, 3, $cache['oid']);
    				}
    			}
    		}
    		//如果是区域管理员，需要扣取手续费，并将费用转到区域管理员的帐号
            handleAdminFee($cache);
    		// 插入订单发货模板消息=====================
    		$vip = M('vip')->where(array('id' => $cache['vipid']))->find();
            //判断购物是否是合伙人，不是合伙人则统计配送费用
            handleDeliveryFee($cache,$vip);
    		$tmpitems = unserialize($cache['items']);
    		$goodsname = isset($tmpitems[0]) ? ($cache['totalnum']>1 ? $tmpitems[0]['name'].'等'.$cache['totalnum'].'件' : $tmpitems[0]['name']) : '';
    		
    		$data = array();
    		$data['touser'] = $vip['openid'];
    		$data['template_id'] = 'aSf0Ck3XcS2cNsDRU28CGJ7CJTWjk-cdHoEHakF9Iok';
    		$data['topcolor'] = "#0000FF";
    		$data['data'] = array(
    				'first' => array('value' => '亲爱的用户您的商品已自提成功'),
    				'keyword1' => array('value' => $cache['oid']),
    				'keyword2' => array('value' => $goodsname),
    				'keyword3' => array('value' => $cache['vipname']),
    				'keyword4' => array('value' => date('Y年m月d日 H:i')),
    				'remark' => array('value' => '感谢您对农牧源的支持！')
    		);
    		$options['appid'] = self::$_wxappid;
    		$options['appsecret'] = self::$_wxappsecret;
    		
    		$wx = new \Util\Wx\Wechat($options);
    		$rere = $wx->sendTemplateMessage($data);
    		
    		// 插入订单发货模板消息结束=================
            if($vip['groupid']==1)
            {
                handleCommission($cache, $orderid);//发放分销佣金
            }

    		$mlog = M('Shop_order_log');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '订单自提成功';
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		
    		//后端日志
    		$mlog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '交易完成-会员点击提货完成';
    		$dlog['type'] = 5;
    		$dlog['paytype'] = $cache['paytype'];
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		$this->success('交易已完成，感谢您的支持！');
    	} else {
    		//后端日志
    		$mlog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '确认提货失败';
    		$dlog['type'] = -1;
    		$dlog['paytype'] = $cache['paytype'];
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		$this->error('确认提货失败，请重新尝试！');
    	}
    }
    //订单退货
    public function orderTuihuo()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$vipid = $_SESSION['WAP']['vipid'];
    	$map['sid'] = $sid;
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg('此订单不存在!');
    	}
    	$cache['items'] = unserialize($cache['items']);
    
    	$this->assign('cache', $cache);
    	/*
    	 //代金卷调用
    	 if ($cache['djqid']) {
    	 $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
    	 $this->assign('djq', $djq);
    	 }
    	 */
    	//高亮底导航
    	$this->assign('actname', 'ftorder');
    	$this->display();
    }
    
    //订单取消
    public function orderTuihuoSave()
    {
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$map['sid'] = $sid;
    	$map['id'] = $orderid;
    	$cache = $m->where($map)->find();
    	if (!$cache) {
    		$this->diemsg(0, '此订单不存在!');
    	}
    	if ($cache['status'] <> 3) {
    		$this->error('只有待收货订单可以办理退货！');
    	}
    	$data = I('post.');
    	$cache['status'] = 4;
    	$cache['tuihuoprice'] = $data['tuihuoprice'];
    	$cache['tuihuokd'] = $data['tuihuokd'];
    	$cache['tuihuokdnum'] = $data['tuihuokdnum'];
    	$cache['tuihuomsg'] = $data['tuihuomsg'];
    	//退货申请时间
    	$cache['tuihuosqtime'] = time();
    	$re = $m->where($map)->save($cache);
    	if ($re) {
    		//后端日志
    		$mlog = M('Shop_order_log');
    		$mslog = M('Shop_order_syslog');
    		$dlog['oid'] = $cache['id'];
    		$dlog['msg'] = '申请退货';
    		$dlog['ctime'] = time();
    		$rlog = $mlog->add($dlog);
    		$dlog['type'] = 4;
    		$rslog = $mslog->add($dlog);
    		$this->success('申请退货成功！请等待工作人员审核！');
    	} else {
    		$this->error('申请退货失败,请重新尝试！');
    	}
    }
    
    //订单支付
    public function pay()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	$paylist = I('paylist');
    	$paydetail = I('paydetail');
    	$sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
    	$orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
    	$type = I('type');
    	$bkurl = U('App/Shop/pay', array('sid' => $sid, 'orderid' => $orderid, 'type' => $type));
    	//		$backurl=base64_encode($orderdetail);
    	$backurl = base64_encode($bkurl);
    	$loginurl = U('App/Vip/login', array('backurl' => $backurl));
    	$re = $this->checkLogin($backurl);
    	//已登陆
    	$m = M('Shop_order');
    	$order = $m->where('id=' . $orderid)->find();
    	if (!$order) {
    		$this->error('此订单不存在！');
    	}
    	if ($order['status'] <> 1) {
    		$this->error('此订单不可以支付！');
    	}
    	$paytype = I('type') ? I('type') : $order['paytype'];
    	switch ($paytype) {
    		case 'money':
    			$mvip = M('Vip');
    			$vip = $mvip->where('id=' . $vipid)->find();
    			$pp = $vip['money'] - $order['payprice'];
    			if ($pp >= 0) {
    				$re = $mvip->where('id=' . $vipid)->setField('money', $pp);
    				if ($re) {
    					$order['paytype'] = 'money';
    					$order['ispay'] = 1;
    					$order['paytime'] = time();
    					$order['status'] = 2;
    					$rod = $m->save($order);
    					if (FALSE !== $rod) {
    						//销量计算-只减不增
    						$rsell = $this->doSells($order);
                            if($order['type']==1){
                            	$ree = $m->where('id=' . $orderid)->setField('status',9);
                            	$actgood = unserialize($order['items']);
                            	$actdata['goodsid'] = $actgood[0]['goodsid'];
                            	$actdata['vipid'] = $vipid;
                            	if($order['groupid']) {
                            		$group = M('activity_group')->where('id='.$order['groupid'])->find();
                            		M('activity_group')->where('id='.$order['groupid'])->setInc('num',1);
                            		$actdata['activityid'] = $group['vipid'];
                            	} else {//开团
                            		$groupData['goods_id'] = $actgood[0]['goodsid'];
                            		$groupData['vipid'] = $vipid;
                            		$groupData['num'] = 1;
                            		$groupData['status'] = 0;
                            		$groupData['ctime'] = $groupData['etime'] = time();
                            		$groupData['rtime'] = getGroupEtime();
                            		$res = M('activity_group')->add($groupData);
                            		if(FALSE !== $res) {
                            			$m->where('id=' . $orderid)->setField('groupid', $res);
                            			$order['groupid'] = $res;
                            		}
                            		$actdata['activityid'] = $vipid;
                            	}
                            	$actdata['time'] = time();
                            	$actdata['orderid'] = $order['id'];
                            	$actdata['groupid'] = $order['groupid'];
                            	$actdata['status'] = 0;
                            	$act = M('activity') -> add($actdata);
                            	groupSuccess($order);
                            } elseif($order['type']==2) {//砍价
                            	if($order['bargain_id'] >0) {
                            		$bargain = M('bargain')->where('id='.$order['bargain_id'])->find();
                            		if(!empty($bargain)) {
                            			$where['helpvipid'] = $bargain['helpvipid'];
                            			$where['goodsid'] = $bargain['goodsid'];
                            			$re = M('bargain') -> where($where) -> setField('status',1);
                            		}
                            	}
                            }
    						//前端日志
    						$mlog = M('Shop_order_log');
    						$dlog['oid'] = $order['id'];
    						$dlog['msg'] = '余额-付款成功';
    						$dlog['ctime'] = time();
    						$rlog = $mlog->add($dlog);
    						//后端日志
    						$mlog = M('Shop_order_syslog');
    						$dlog['type'] = 2;
    						$rlog = $mlog->add($dlog);
    						//资金流水记录
    						$mlog = M('Vip_log_money');
    						$flow['vipid'] = $vip['id'];
    						$flow['openid'] = $vip['openid'];
    						$flow['nickname'] = $vip['nickname'];
    						$flow['mobile'] = $vip['mobile'];
    						$flow['money'] = -$order['payprice'];
    						$flow['paytype'] = 'money';
    						$flow['balance'] = $pp;
    						$flow['type'] = 3;
    						$flow['oid'] = $order['oid'];
    						$flow['ctime'] = time();
    						$tmpitems = unserialize($order['items']);
                            $ordeg = M('shop_order_goods');
                            foreach ($tmpitems as $ke => $val) {
                                $integ['id'] = $val['goodsid'];
                                unset($val['id']);
                                $orderge['goodsid'] = $val['goodsid'];
                                $orderge['vipid'] = $val['vipid'];
                                $orderg = $ordeg -> where($orderge) ->find();
                                if($orderg){
                                    $res = $ordeg -> where($orderge) -> setInc('num',$val['num']);
                                }else{
                                    $res = $ordeg -> add($val);
                                }
                                $goods = M('Shop_goods')-> where($integ) -> getField('integral');
                                $goods = $goods*$order['totalnum'];
                                if($goods){
                                    $tegal['id'] = $_SESSION['WAP']['vipid'];
                                    $tegal['score'] = array('egt', $goods);
                                    $integral = $mvip -> where($tegal)->setDec('score', $goods);
                                }
                            }
    						$flow['remark'] = isset($tmpitems[0]) ? ($order['totalnum']>1 ? '购买商品：'.$tmpitems[0]['name'].'等'.$order['totalnum'].'件' : '购买商品：'.$tmpitems[0]['name']) : '';
    						$rflow = $mlog->add($flow);
    						/*
    						if(self::$WAP['vipset']['vip_buy_amount']>0 && self::$WAP['vipset']['vip_buy_period']>0){
    							//设置VIP会员
    							$half_year_ago = strtotime('-6months',time());//半年内的消费有效
    							if($vip['stime'] && ($vip['stime'] > $half_year_ago)) {
    								$vtime = $vip['stime'];
    							} else {
    								$vtime = $half_year_ago;
    							}
    							//半年来商城消费总额
    							$totalpay = get_user_totalpay($vip['id'], 1, $vtime);
    							if($vip['groupid']==1){
                                    if($totalpay >= self::$WAP['vipset']['vip_buy_amount']){
                                    $expiration_time = strtotime("+".intval(self::$WAP['vipset']['vip_buy_period'])." months", time()); //有效期
                                    if($expiration_time > $vip['vip_expiration_time']) {
                                        $data_vip['groupid'] = 2;
                                        $data_vip['stime'] = time();
                                        $data_vip['vip_expiration_time'] = $expiration_time;
                                        $re = $mvip->where('id='.$vip['id'])->save($data_vip);
                                        if(FALSE !== $re) {
                                            $data_msg['pids'] = $vip['id'];
                                            $data_msg['title'] = "恭喜您，成功升级为VIP会员！";
                                            $data_msg['content'] = '您半年内在'.self::$SHOP['set']['name'].'消费达到'.self::$WAP['vipset']['vip_buy_amount'].'元。为此系统自动将您升级为VIP会员，购买商品将享受会员专享价，有效期至'.date('Y-m-d',$expiration_time).'，感谢您的支持！';
                                            $data_msg['ctime'] = time();
                                            $rmsg = M('Vip_message')->add($data_msg);
                                        }
                                    }
                                }
                                }
    						}
    						//检查是否当前用户首单
    						$map['vipid'] = $vip['id'];
    						$map['ispay'] = 1;
    						$smap['vipid'] = $vip['id'];
    						$smap['ispay'] = 1;
    						$smap['id'] = array('neq', $order['id']);
    						$shop_order = M('Shop_order')->where($smap)->count();
    						$finance_order = M('Finance_order')->where($map)->count();
    						$firstorder = false;
    						if(empty($shop_order) && empty($finance_order)) {
    							$firstorder = true;
    						}
    						$old = $mvip->where('id='.$vip['pid'])->find();
    						if(!empty($old)&&$firstorder==true) {
    							$tj_xf_money = self::$WAP['vipset']['tj_xf_money'];
    							$tj_xf_score = self::$WAP['vipset']['tj_xf_score'];
    							if ($tj_xf_money || $tj_xf_score) {
    								$msg = "带来消费奖励：<br>消费用户：" . $vip['nickname'] . "<br>奖励内容：<br>";
    								$mglog = "获得带来消费奖励:";
    								if ($tj_xf_score>0) {
    									$msg = $msg . $tj_xf_score . "个积分<br>";
    									$mglog = $mglog . $tj_xf_score . "个积分；";
    								}
    								//检查是否超过佣金封顶
    								$logmap['vipid'] = $vip['id'];
    								$logmap['status'] = array('neq',2);
    								//带来消费佣金
    								$tj_money = M('Vip_log_xftj')->where($logmap)->sum('money');
    								//带来注册佣金
    								$tj_reg_money = M('Vip_log_tj')->where('vipid='.$vip['id'])->sum('money');
    								$tj_money = $tj_money + $tj_reg_money;
    								$money =  $order['payprice']/1000*$tj_xf_money;
    								if($tj_money > self::$WAP['vipset']['max_tg_commission']) {
    									$money = 0;
    								} elseif($tj_money + $money > self::$WAP['vipset']['max_tg_commission']){
    									$money = self::$WAP['vipset']['max_tg_commission'] - $tj_money;
    								} 
    								if ($firstorder && $money>0 && $tj_xf_money) {
    									$msg = $msg . $money . "余额<br>";
    									$mglog = $mglog . $money . "余额；";   								
										$msg = $msg . "奖励的余额将由下级用户确认收货后自动打入您的帐户！感谢您的支持！";
										$data_mglog['vipid'] = $old['id'];
										$data_mglog['origin'] = 1;
										$data_mglog['oid'] = $order['id'];
										$data_mglog['money'] = $money;
										$data_mglog['score'] = $tj_xf_score;
										$data_mglog['msg'] = $mglog;
										$data_mglog['status'] = 0;
										$data_mglog['ctime'] = time();
										$rmglog = M('Vip_log_xftj')->add($data_mglog);
									}
									if($firstorder && $msg != '') {
										$data_msg['pids'] = $old['id'];
										$data_msg['title'] = "你获得一份带来消费奖励！";
										$data_msg['content'] = $msg;
										$data_msg['ctime'] = time();
										$rmsg = M('Vip_message')->add($data_msg);
									}
    							}
    						}    		
    						*/
                            if($order["delivery"]=="since"&&$order["deliveryid"]>0){
                                $delivery=M("vip")->where(array("id"=>$order["deliveryid"]))->find();
                                if($delivery){
                                    $item=unserialize($order["items"]);
                                    $goods="";
                                    foreach ($item as $k=> $v){
                                        $goods.=$v["name"]."x".$v["num"]." ";
                                    }
                                    $data = array();
                                    $data['touser'] = $delivery['openid'];
                                    $data['template_id'] = 'zlA97-t_2BKC-S_DB6UXaprTucy9ttmibgnD78vAJiI';
                                    $data['topcolor'] = "#0000FF";
                                    $since=M("since")->where(array("id"=>$order["sinceid"]))->getField("address");
                                    $data['data'] = array(
                                        'first' => array('value' => '你管理的自提点'.$since?("(".$since.")"):"".'有新订单'),
                                        'keyword1' => array('value' => $order["oid"]),
                                        'keyword2' => array('value' => $goods),
                                        'keyword3' => array('value' => $order["totalprice"]),
                                        'keyword4' => array('value' => date('Y年m月d日 H:i')),
                                        'remark' => array('value' => '如有问题请联系客服！')
                                    );
                                    $options['appid'] = self::$_wxappid;
                                    $options['appsecret'] = self::$_wxappsecret;

                                    $wx = new \Util\Wx\Wechat($options);
                                    $rere = $wx->sendTemplateMessage($data);
                                }

                            }

                            if($order['type']==0){
                                $this->success('余额付款成功！', U('App/Shop/orderList', array('type' => '5')));
                            }elseif($order['type']==1){
                            	$this->success('余额付款成功！', U('App/Activity/groupShare', array('id' => $act)));
                            }else{
                                $this->success('余额付款成功！', U('App/Shop/orderList', array('type' => '7')));
                            }			
                            if($order['type'] == 0) {
                                // 插入订单支付成功模板消息=====================
                                $data = array();
                                if(count($tmpitems)>=2){
                                    $orderProductName = $tmpitems[0]['name'].'等';
                                }else{
                                    $orderProductName = $tmpitems[0]['name'];
                                }
                                $data['touser'] = $vip['openid'];
                                $data['template_id'] = 'O24fS1RC6sPVwIbOcr2UYG3xQA63ri842--TrZQDo7U';
                                $data['topcolor'] = "#00FF00";
                                $data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Shop/orderDetail", array('sid'=>'0',"orderid" => $order["id"]));
                                $data['data'] = array(
                                    'first' => array('value' => '您好，您的订单已付款成功'),
                                    'orderProductPrice' => array('value' => $order['payprice'].'元'),
                                    'orderProductName' => array('value' => $orderProductName),
                                    'orderAddress' => array('value' => $order['vipname'].'，'.$order['vipmobile'].'，'.$order['vipaddress']),
                                    'orderName' => array('value' => $order['oid']),
                                    'remark' => array('value' => '感谢您对农牧源的支持！')
                                );
                                $options['appid'] = self::$_wxappid;
                                $options['appsecret'] = self::$_wxappsecret;
                                $wx = new \Util\Wx\Wechat($options);
                                $re = $wx->sendTemplateMessage($data);
                            }


    						// 插入订单支付成功模板消息结束=================
    						// if (self::$SHOP['set']['isfx']) {
    						// 	//首次支付成功自动变为花蜜
    						// 	if ($vip && !$vip['isfx']) {
    						// 		$rvip = $mvip->where('id=' . $_SESSION['WAP']['vipid'])->setField('isfx', 1);
    						// 		$data_msg['pids'] = $_SESSION['WAP']['vipid'];
    
    						// 		$shopset = self::$WAP['shopset'] = $_SESSION['WAP']['shopset'];
    						// 		$data_msg['title'] = "您成功升级为" . $shopset['name'] . "的" . $shopset['fxname'] . "！";
    						// 		$data_msg['content'] = "欢迎成为" . $shopset['name'] . "的" . $shopset['fxname'] . "，开启一个新的旅程！";
    						// 		$data_msg['ctime'] = time();
    						// 		$rmsg = M('vip_message')->add($data_msg);
    						// 	}
    
    						// 	//代收花生米计算-只减不增
    						// 	$rds = $this->doDs($order);
    						// }
    
    					} else {
    						//后端日志
    						$mlog = M('Shop_order_syslog');
    						$dlog['oid'] = $order['id'];
    						$dlog['msg'] = '余额付款失败';
    						$dlog['type'] = -1;
    						$dlog['ctime'] = time();
    						$rlog = $mlog->add($dlog);
    						$this->error('余额付款失败！请联系客服！');
    					}
    
    				} else {
    					//后端日志
    					$mlog = M('Shop_order_syslog');
    					$dlog['oid'] = $order['id'];
    					$dlog['msg'] = '余额付款失败';
    					$dlog['type'] = -1;
    					$dlog['ctime'] = time();
    					$this->error('余额支付失败，请重新尝试！');
    				}
    			} else {
    				$this->error('余额不足，请使用其它方式付款！');
    			}
    			break;
    		case 'alipayApp':
    			$this->redirect("App/Alipay/alipay", array('sid' => $sid, 'price' => $order['payprice'], 'oid' => $order['oid']));
    			break;
    		case 'wxpay':
    			$_SESSION['wxpaysid'] = 0;
    			$_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
    			$this->redirect('Home/Wxpay/pay', array('oid' => $order['oid'],'goodstype'=>$order['goodstype'],'paylist'=>$paylist,'paydetail'=>$paydetail));
    			break;
    		default:
    			$this->error('支付方式未知！');
    			break;
    	}
    
    }
    //首单上级用户奖励
    public function dogiftxf($order) {
    	if(empty($order)) {
    		return false;
    	}
    	$mvip = M('Vip');
    	$vipid = $order['vipid'];
    	$vip = $mvip->where('id='.$vipid)->find();
    	if(!$vip['pid']) {
    		return false;
    	}
    	//检查是否当前用户首单
    	$map['vipid'] = $vipid;
    	$map['ispay'] = 1;
    	$shop_order = M('Shop_order')->where($map)->count();
    	$finance_order = M('Finance_order')->where($map)->count();
    	$firstorder = false;
    	if(empty($shop_order) && empty($finance_order)) {
    		$firstorder = true;
    	}
    	$old = $mvip->where('id='.$vip['pid'])->find();
    	if(!empty($old)) {   	
	    	$tj_xf_money = self::$WAP['vipset']['tj_xf_money'];
	    	$tj_xf_score = self::$WAP['vipset']['tj_xf_score'];
	    	if ($tj_xf_money || $tj_xf_score) {
	    		$msg = "带来消费奖励：<br>消费用户：" . $vip['nickname'] . "<br>奖励内容：<br>";
	    		$mglog = "获得带来消费奖励:";
	    		if ($tj_xf_score) {
	    			$old['score'] = $old['score'] + $tj_xf_score;
	    			$msg = $msg . $tj_xf_score . "个积分<br>";
	    			$mglog = $mglog . $tj_xf_score . "个积分；";
	    		}
	    		//检查是否超过佣金封顶
	    		$mlog = M('Vip_log_xftj');
	    		$logmap['vipid'] = $vipid;
	    		$logmap['status'] = array('neq',2);
	    		$tj_money = $mglog->where($logmap)->sum('money');
	    		$money =  $order['payprice']/1000*$tj_xf_money;
	    		if($tj_money > self::$WAP['vipset']['max_tg_commission']) {
	    			$money = 0;
	    		} elseif($tj_money+$money > self::$WAP['vipset']['max_tg_commission']){
	    			$money = self::$WAP['vipset']['max_tg_commission'] - $tj_money;
	    		}
	    		if ($money>0 && $tj_xf_money) {
	    			$old['money'] = $old['money'] + $money;
	    			$msg = $msg . $money . "元余额<br>";
	    			$mglog = $mglog . $money . "元余额；";
	    		}
	    		$msg = $msg . "此奖励一个月后将自动打入您的帐户！感谢您的支持！";    		
	    		$data_msg['pids'] = $old['id'];
	    		$data_msg['title'] = "你获得一份带来消费奖励！";
	    		$data_msg['content'] = $msg;
	    		$data_msg['ctime'] = time();
	    		$rmsg = M('Vip_message')->add($data_msg);
	    		$data_mglog['vipid'] = $old['id'];
	    		$data_mglog['origin'] = 1;
	    		$data_mglog['oid'] = $order['id'];
	    		$data_mglog['money'] = $money;
	    		$data_mglog['score'] = $tj_xf_score;
	    		$data_mglog['msg'] = $mglog;
	    		$data_mglog['status'] = 0;
	    		$data_mglog['ctime'] = time();
	    		$rmglog = M('Vip_log_xftj')->add($data_mglog);
	    	}
    	}
    }
    //销量计算
    private function doSells($order)
    {
    	$mgoods = M('Shop_goods');
    	$msku = M('Shop_goods_sku');
    	$mlogsell = M('Shop_syslog_sells');
    	//封装dlog
    	$dlog['oid'] = $order['id'];
    	$dlog['vipid'] = $order['vipid'];
    	$dlog['vipopenid'] = $order['vipopenid'];
    	$dlog['vipname'] = $order['vipname'];
    	$dlog['ctime'] = time();
    	$items = unserialize($order['items']);
    	$tmplog = array();
    	foreach ($items as $k => $v) {
    		//销售总量
    		$dnum = $dlog['num'] = $v['num'];
    		if ($v['skuid']) {
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
    			$rs = $msku->where('id=' . $v['skuid'])->setDec('num', $dnum);
    			$rs = $msku->where('id=' . $v['skuid'])->setInc('sells', $dnum);
    			//sku模式
    			$dlog['goodsid'] = $v['goodsid'];
    			$dlog['goodsname'] = $v['name'];
    			$dlog['skuid'] = $v['skuid'];
    			$dlog['skuattr'] = $v['skuattr'];
    			$dlog['price'] = $v['price'];
    			$dlog['num'] = $v['num'];
    			$dlog['total'] = $v['total'];
    		} else {
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
    			$rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
    			//纯goods模式
    			$dlog['goodsid'] = $v['goodsid'];
    			$dlog['goodsname'] = $v['name'];
    			$dlog['skuid'] = 0;
    			$dlog['skuattr'] = 0;
    			$dlog['price'] = $v['price'];
    			$dlog['num'] = $v['num'];
    			$dlog['total'] = $v['total'];
    		}
    		array_push($tmplog, $dlog);
    	}
    	if (count($tmplog)) {
    		$rlog = $mlogsell->addAll($tmplog);
    	}
    	return true;
    }
    
    //代收花生米计算
    public function doDs($order)
    {
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
    	//分销佣金计算
    	$commission = D('Commission');
    	$orderids = array();
    	$orderids[] = $order['id'];
    
    	$vipid = $order['vipid'];
    	$mvip = M('vip');
    	$vip = $mvip->where('id=' . $vipid)->find();
    	if (!$vip && !$vip['pid']) {
    		return FALSE;
    	}
    	//初始化
    	$pid = $vip['pid'];
    	$mfxlog = M('fx_dslog');
    	$shopset = M('Shop_set')->find();//追入商城设置
    	$fxlog['oid'] = $order['id'];
    	$fxlog['fxprice'] = $fxprice = $order['payprice'] - $order['yf'];
    	$fxlog['ctime'] = time();
    	// $fx1rate=$shopset['fx1rate']/100;
    	// $fx2rate=$shopset['fx2rate']/100;
    	// $fx3rate=$shopset['fx3rate']/100;
    	$fxtmp = array();//缓存3级数组
    	if ($pid) {
    		//第一层分销
    		$fx1 = $mvip->where('id=' . $pid)->find();
    		if ($fx1['isfx']) {
    			$fxlog['fxyj'] = $commission->ordersCommission('fx1rate', $orderids);
    			$fxlog['from'] = $vip['id'];
    			$fxlog['fromname'] = $vip['nickname'];
    			$fxlog['to'] = $fx1['id'];
    			$fxlog['toname'] = $fx1['nickname'];
    			$fxlog['status'] = 1;
    			//单层逻辑
    			//$rfxlog=$mfxlog->add($fxlog);
    			//file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
    			array_push($fxtmp, $fxlog);
    		}
    		/*
    		//第二层分销
    		if ($fx1['pid']) {
    			$fx2 = $mvip->where('id=' . $fx1['pid'])->find();
    			if ($fx2['isfx']) {
    				$fxlog['fxyj'] = $commission->ordersCommission('fx2rate', $orderids);
    				$fxlog['from'] = $vip['id'];
    				$fxlog['fromname'] = $vip['nickname'];
    				$fxlog['to'] = $fx2['id'];
    				$fxlog['toname'] = $fx2['nickname'];
    				$fxlog['status'] = 1;
    				//单层逻辑
    				//$rfxlog=$mfxlog->add($fxlog);
    				//file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
    				array_push($fxtmp, $fxlog);
    			}
    		}
    		//第三层分销
    		if ($fx2['pid']) {
    			$fx3 = $mvip->where('id=' . $fx2['pid'])->find();
    			if ($fx3['isfx']) {
    				$fxlog['fxyj'] = $commission->ordersCommission('fx3rate', $orderids);
    				$fxlog['from'] = $vip['id'];
    				$fxlog['fromname'] = $vip['nickname'];
    				$fxlog['to'] = $fx3['id'];
    				$fxlog['toname'] = $fx3['nickname'];
    				$fxlog['status'] = 1;
    				//单层逻辑
    				//$rfxlog=$mfxlog->add($fxlog);
    				//file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
    				array_push($fxtmp, $fxlog);
    			}
    		}
    		*/
    		//多层分销
    		if (count($fxtmp) >= 1) {
    			$refxlog = $mfxlog->addAll($fxtmp);
    			if (!$refxlog) {
    				//file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
    			}
    		}
    
    	}
    	return true;
    	//逻辑完成
    }
    //检查支付密码
    public function checkPayPassword()
    {
    	$vipid = $_SESSION['WAP']['vipid'];
    	if(!$vipid) {
    		$this->error('您未登陆！');
    	}
    	$password = I('password');
    	$mvip = M('Vip');
    	$mlog = M('Vip_paypassword_error_log');
    	$start_time = strtotime(date('Y-m-d'));
    	$end_time = $start_time + 60*60*24 -1;
    	//删除今天零时以前的记录
    	$mlog->where('ctime<'.$start_time)->delete();
    	$map['vipid'] = $vipid;
    	$map['ctime'] = array ('BETWEEN',array ($start_time,$end_time));
    	$count = $mlog->where($map)->count();
    	if($count>=3) {
    		$this->error('输入错误密码超过3次!');
    	}
    	$pay_password = $mvip->where('id='.$vipid)->getField('pay_password');
    	if($pay_password == ''){
    		$re = $mvip->where('id='.$vipid)->setField('pay_password',md5($password));
            $mlog->where('vipid='.$vipid)->delete();
            $this->success('支付密码正确！');
    	}else{
            if(md5($password) != $pay_password) {
                //写入日志
                $log['vipid'] = $vipid;
                $log['ctime'] = time();
                $mlog->add($log);
                $chance = 3-$count-1;
                $this->error('支付密码错误，您今天还有'.$chance.'次机会');
            } else {
                $mlog->where('vipid='.$vipid)->delete();
                $this->success('支付密码正确！');
            }
        }
    }

    public function orderTuikuan()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuikuan', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);
    
        $this->assign('cache', $cache);
        /*
         //代金卷调用
         if ($cache['djqid']) {
         $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
         $this->assign('djq', $djq);
         }
         */
        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单取消
    public function orderTuikuanSave()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuikuan', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 2) {
            $this->error('只有已付款并且未发货的订单可以办理退款！');
        }
        $data = I('post.');
        $cache['status'] = 8;
        $cache['tuihuomsg'] = $data['tuikuanmsg'];
        //退款申请时间
        $cache['tuihuosqtime'] = time();
        $re = $m->where($map)->save($cache);
        if ($re) {
            //后端日志
            $mlog = M('Shop_order_log');
            $mslog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '申请退款';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $dlog['type'] = 4;
            $rslog = $mslog->add($dlog);
            $this->success('申请退款成功！请等待工作人员审核！');
        } else {
            $this->error('申请退款失败,请重新尝试！');
        }
    }

    //自提商品到达自提点
    public function pickupDeliver()
    {
        $id = I('get.id');
        if (!$id) {

            $this->error("未正常获取ID数据");
        }
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];

        $order = $m->where(array("id"=>$id,"deliveryid"=>$vipid))->find();
        if(!$order){
            $this->error("订单不存在或你没有权限操作!");
        }
        if($order["status"]!=2){
            $this->error("订单状态只有待到达配送点才能执行此操作!");
        }
        $map['id'] = $id;
        $map['delivery'] = 'since';
        $re = M('Shop_order')->where($map)->setField('status', 3);
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $dwechat = D('Wechat');
        if (FALSE !== $re) {
            $log['oid'] = $id;
            $log['msg'] = '订单已到达自提点';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);


            // 插入订单发货模板消息=====================
            $vip = M('vip')->where(array('id' => $order['vipid']))->find();

            $data = array();
            $data['touser'] = $vip['openid'];
            $data['template_id'] = '1oDtgGzNreAsnFGWahrpW4cwchNxxDkRWWnu19x7WY0';
            $data['topcolor'] = "#0000FF";
            $data['data'] = array(
                'first' => array('value' => '亲爱的用户您的订单商品已到达自提点。'),
                'keyword1' => array('value' => $order['oid']),
                'keyword2' => array('value' => $order['vipaddress']),
                'keyword3' => array('value' => '自提'),
                'keyword4' => array('value' => $order['payprice'].'元'),
                'keyword5' => array('value' => date('m-d H:i', $order['ctime'])),
                'remark' => array('value' => '请尽快把他们领回家哦！感谢您对农牧源的支持！')
            );
            $options['appid'] = self::$_wxappid;
            $options['appsecret'] = self::$_wxappsecret;

            $wx = new \Util\Wx\Wechat($options);
            $rere = $wx->sendTemplateMessage($data);
            $this->success("操作成功!");

        } else {
            $this->error("操作失败!");
        }
    }

    /**
     * 完成自提
     * author: feng
     * create: 2017/10/3 15:57
     */
    public function pickupSuccess()
    {
        $id = I('get.id');
        if (!$id) {
            $this->error("未正常获取ID数据!");
        }



        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];

        $order = $m->where(array("id"=>$id,"deliveryid"=>$vipid))->find();
        if(!$order){
            $this->error("订单不存在或你没有权限操作!");
        }
        if($order["status"]!=3){
            $this->error("订单状态只有送达配送点才能执行此操作!");
        }
        $map['id'] = $id;
        $map['delivery'] = 'since';
        $re = M('Shop_order')->where($map)->save(array('status'=>5,'pickuptime'=>time()));
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $dwechat = D('Wechat');
        if (FALSE !== $re) {
            //修改会员账户金额、经验、积分、等级
            if (self::$WAP['vipset']['xf_exp'] > 0) {
                $data_vip['exp'] = array('exp', 'exp+' . round($order['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($order['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $level = $this->getLevel(self::$WAP['vipset']['cur_exp'] + round($order['payprice'] * self::$WAP['vipset']['cz_exp'] / 100));
                $data_vip['levelid'] = $level['levelid'];
                //会员合计支付
                $data_vip['total_buy'] = $data_vip['total_buy'] + $order['payprice'];
            }
            //获取赠送积分数
            $score = get_order_credit($order);
            if($score > 0){
                $data_vip['score'] = array('exp', 'score+' .$score );
            }
            if(!empty($data_vip)) {
                $re = M('Vip')->where('id='.$order['vipid'])->save($data_vip);
                if (FALSE === $re) {
                    $this->error('更新会员信息失败！');
                } else {
                    if($score > 0){
                        log_credit($order['id'], $score, 3, $order['oid']);
                    }
                }
            }
            //如果是区域管理员，需要扣取手续费，并将费用转到区域管理员的帐号
            handleAdminFee($order);
            $log['oid'] = $id;
            $log['msg'] = '订单自提成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);


            // 插入订单发货模板消息=====================
            $vip = M('vip')->where(array('id' => $order['vipid']))->find();
            //判断购物是否是合伙人，不是合伙人则统计配送费用
            handleDeliveryFee($order,$vip);
            if ($vip['groupid']==1) {
                handleCommission($order, $id);//发放分销佣金
            }
            $tmpitems = unserialize($order['items']);
            $goodsname = isset($tmpitems[0]) ? ($order['totalnum']>1 ? $tmpitems[0]['name'].'等'.$order['totalnum'].'件' : $tmpitems[0]['name']) : '';

            $data = array();
            $data['touser'] = $vip['openid'];
            $data['template_id'] = 'aSf0Ck3XcS2cNsDRU28CGJ7CJTWjk-cdHoEHakF9Iok';
            $data['topcolor'] = "#0000FF";
            $data['data'] = array(
                'first' => array('value' => '亲爱的用户您的商品已自提成功'),
                'keyword1' => array('value' => $order['oid']),
                'keyword2' => array('value' => $goodsname),
                'keyword3' => array('value' => $order['vipname']),
                'keyword4' => array('value' => date('Y年m月d日 H:i')),
                'remark' => array('value' => '感谢您对农牧源的支持！')
            );
            $options['appid'] = self::$_wxappid;
            $options['appsecret'] = self::$_wxappsecret;


            $wx = new \Util\Wx\Wechat($options);
            $rere = $wx->sendTemplateMessage($data);

            // 插入订单发货模板消息结束=================
            $this->success("操作成功!");
        } else {
            $this->error("操作失败!");
        }
    }

    public function test(){
		echo date('Y-m-d H:i:s');
	}
}
