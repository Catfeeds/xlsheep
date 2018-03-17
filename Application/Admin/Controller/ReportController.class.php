<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组日志管理类
// +----------------------------------------------------------------------
// | AppCMS V1.0 Beta
// +----------------------------------------------------------------------
// | Copyright (c) 2014 http://www.AppCMS.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: App <2094157689@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

class ReportController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    //CMS后台财务报表引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '财务报表',
                'url' => U('Admin/Report/index')
            ),
    		'1' => array(
    			'name' => '用户资金流水',
    			'url' => U('Admin/Report/index')
    		)
        );
        //绑定搜索条件与分页
        $m = M('Vip_log_money');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
        	/*
        	$type = I('type') ? I('type',0,'intval') : '';
        	if($type == 1) {
        		$map['money'] = array('lt', 0);
        	} elseif($type == 2) {
        		$map['money'] = array('gt', 0);
        	}
        	*/
        	$vipid = I('vipid') ? I('vipid',0,'intval') : '';
        	if($vipid) {
        		$map['vipid'] = $vipid;
        	}
        	$keyword = I('keyword') ? I('keyword') : '';
        	if($keyword) {
        		$map['nickname|mobile'] = array('like', "%$keyword%");
        	}
        	$date =  I('date') ? I('date') : '';
        	if ($date != '') {
        		$timeArr = explode(" - ", $date);
        		$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
        	}

        	$this->assign('vipid', $vipid);
        	$this->assign('keyword', $keyword);
        	$this->assign('date', $date);
        }
        $map['type'] = array('in', '1,3,4,8');
        //用户总余额
        $total_money = M('Vip')->sum('money');
        $total_money = sprintf("%.2f", $total_money);
        $total_money = $total_money ? $total_money : 0;
        //总财务收入
        $cz_money = M('Vip_log_money')->where(array('type'=>1))->sum('binary(money)');//用户充值
        $shop_buy_money = M('Shop_order')->where("ispay=1 and paytype<>'money'")->sum('binary(payprice)');//商城支付总额(余额支付除外)      
        $finance_buy_money = M('Finance_order')->where("ispay=1 and paytype<>'money'")->sum('binary(payprice)');//金融支付总额(余额支付除外)
        $income = $cz_money + $shop_buy_money + $finance_buy_money;
        //总财务支出
        $cardtx = M('Vip_tx')->where('status=2')->sum('txprice');//银行卡提现完成金额总和
        $expenditure =  round($cardtx, 2);
        $this->assign('income', $income);
        $this->assign('expenditure', $expenditure);
        $this->assign('total_money', $total_money);
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        unset($map['type']);
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员资金流水日志', 'App-search');
        $this->assign('cache', $cache);
        $this->assign('hover', 'index');
        $this->display();
    }
    /**
     * 区域管理员报表
     * author: feng
     * create: 2017/9/25 11:28
     */
    public function areaIndex()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '财务报表',
                'url' => U('Admin/Report/index')
            ),
            '1' => array(
                'name' => '用户资金流水',
                'url' => U('Admin/Report/index')
            )
        );
        //绑定搜索条件与分页
        $m = M('Vip_log_money');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            /*
            $type = I('type') ? I('type',0,'intval') : '';
            if($type == 1) {
                $map['money'] = array('lt', 0);
            } elseif($type == 2) {
                $map['money'] = array('gt', 0);
            }
            */
            $vipid = I('vipid') ? I('vipid',0,'intval') : '';
            if($vipid) {
                $map['v.vipid'] = $vipid;
            }
            $keyword = I('keyword') ? I('keyword') : '';
            if($keyword) {
                $map['v.nickname|v.mobile'] = array('like', "%$keyword%");
            }
            $date =  I('date') ? I('date') : '';
            if ($date != '') {
                $timeArr = explode(" - ", $date);
                $map['v.ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
            }

            $this->assign('vipid', $vipid);
            $this->assign('keyword', $keyword);
            $this->assign('date', $date);
        }
        $user=self::$CMS['user'];
        if($user["user_type"]>0){
            $map["O.adminid"]=$user["id"];
        }else{
            $map["O.adminid"]=array("gt",0);
        }
        $map['v.type'] = array('in', '17');
        //区域已返现
        $total_money = $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->field('v.*,O.adminid,O.adminfee,O.delivery_fee')
            ->where($map)->sum('binary(v.money)');

        $total_money = $total_money ? $total_money : 0;
        $map['v.type'] = array('in', '3');
        //区域购买商品收入
        $income = $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->field('v.*,O.adminid,O.adminfee,O.delivery_fee')
            ->where($map)->sum('binary(v.money)');
        //区域购买支付手续费用
        $map['v.type'] = array('in', '17');
        $cardtx = $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->field('v.*,O.adminid,O.adminfee,O.delivery_fee')
            ->where($map)->sum('binary(O.adminfee)');;
        $expenditure =  round($cardtx, 2);

        $this->assign('income', abs($income));
        $this->assign('expenditure', $expenditure);
        $this->assign('total_money', round($total_money,2));
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        unset($map['type']);
        $map["v.type"]=array("in","3,17");

        $cache = $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->field('v.*,O.adminid,O.adminfee,O.delivery_fee')
            ->where($map)
            ->page($p, $psize)
            ->order('v.ctime desc')
            ->select();
        $count =  $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员资金流水日志', 'App-search');
        $this->assign('cache', $cache);
        $this->assign('hover', 'index');
        $this->display();
    }
    /**
     * 配送员报表
     * author: feng
     * create: 2017/9/25 11:27
     */
    public function delivery()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '财务报表',
                'url' => U('Admin/Report/index')
            ),
            '1' => array(
                'name' => '用户资金流水',
                'url' => U('Admin/Report/index')
            )
        );
        //绑定搜索条件与分页
        $m = M('Vip_log_money');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            /*
            $type = I('type') ? I('type',0,'intval') : '';
            if($type == 1) {
                $map['money'] = array('lt', 0);
            } elseif($type == 2) {
                $map['money'] = array('gt', 0);
            }
            */
            $vipid = I('vipid') ? I('vipid',0,'intval') : '';
            if($vipid) {
                $map['vipid'] = $vipid;
            }
            $keyword = I('keyword') ? I('keyword') : '';
            if($keyword) {
                $map['nickname|mobile'] = array('like', "%$keyword%");
            }
            $date =  I('date') ? I('date') : '';
            if ($date != '') {
                $timeArr = explode(" - ", $date);
                $map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
            }

            $this->assign('vipid', $vipid);
            $this->assign('keyword', $keyword);
            $this->assign('date', $date);
        }
        $map['type'] = 18;
        //用户配送总收入

        $cz_money = M('Vip_log_money')->where(array('type'=>18))->sum('binary(money)');//用户充值

        $this->assign('income', $cz_money);

        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->assign("curIncome",$m->where($map)->sum('binary(money)'));
        $this->getPage($count, $psize, 'App-loader', '配送费用流水日志', 'App-search');
        $this->assign('cache', $cache);
        $this->assign('hover', 'index');
        $this->display();
    }

    //用户投资沉淀资金
    public function investment()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '财务报表',
    					'url' => U('Admin/Report/index')
    			),
    			'1' => array(
    					'name' => '用户投资沉淀资金',
    					'url' => U('Admin/Report/investment')
    			)
    	);
    	$m = M('Finance_order');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$search = I('search') ? I('search') : '';
    	if ($search) {
    		$vipid = I('vipid') ? I('vipid',0,'intval') : '';
    		if($vipid) {
    			$map['vipid'] = $vipid;
    		}
    		$keyword = I('keyword') ? I('keyword') : '';
    		if($keyword) {
    			$map['vipname|vipmobile'] = array('like', "%$keyword%");
    		}
    		$bonusway =  I('bonusway') ? I('bonusway') : '';
    		if($bonusway) {
    			$map['bonusway'] = $bonusway;
    		}
    		$date =  I('date') ? I('date') : '';
    		if ($date != '') {
    			$timeArr = explode(" - ", $date);
    			$map['F.ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
    		}
    		$this->assign('vipid', $vipid);
    		$this->assign('keyword', $keyword);
    		$this->assign('bonusway', $bonusway);
    		$this->assign('date', $date);
    	}
    	//用户投资沉淀资金总额
    	$total_money = $m->where(array('status'=>2))->sum('totalprice');
    	$total_money = $total_money>0 ? number_format($total_money,2):0;
    	$this->assign('total_money', $total_money);
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$map['status'] = 2;
    	$cache = $m->where($map)->page($p, $psize)
    	->order('ctime desc')->select();
    	$i = ($p-1)*$psize+1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = $i;
    		$i++;
    	}
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '用户投资沉淀资金', 'App-search');
    	$this->assign('cache', $cache);
    	$this->assign('hover', 'investment');
    	$this->display();
    }
    //用户分红资金
    public function fh()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '财务报表',
    					'url' => U('Admin/Report/index')
    			),
    			'1' => array(
    					'name' => '用户分红资金',
    					'url' => U('Admin/Report/fh')
    			)
    	);
    	$m = M('Finance_fhlog');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$search = I('search') ? I('search') : '';
    	if ($search) {
    		$vipid = I('vipid') ? I('vipid',0,'intval') : '';
    		if($vipid) {
    			$map['vipid'] = $vipid;
    		}
    		$keyword = I('keyword') ? I('keyword') : '';
    		if($keyword) {
    			$map['vipname|vipmobile'] = array('like', "%$keyword%");
    		}
    		$bonusway =  I('bonusway') ? I('bonusway') : '';
    		if($bonusway) {
    			$fhmap['bonusway'] = $bonusway;
    		}
    		$date =  I('date') ? I('date') : '';
    		if ($date != '') {
    			$timeArr = explode(" - ", $date);
    			$fhmap['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
    		}
    		$this->assign('vipid', $vipid);
    		$this->assign('keyword', $keyword);
    		$this->assign('bonusway', $bonusway);
    		$this->assign('date', $date);
    	}
    	//用户投资未分红总额
    	$nofh_total_money = $m->where(array('status'=>0, 'type'=>1))->sum('money');
    	$nofh_total_money = $nofh_total_money>0 ? number_format($nofh_total_money,2) : 0;
    	//用户投资已分红总额
    	$fh_total_money = $m->where(array('status'=>1, 'type'=>1))->sum('money');
    	$fh_total_money = $fh_total_money ? number_format($fh_total_money,2) : 0;
    	//用户投资分红总额
    	$total_money = $m->where(array('status'=>array('neq',2), 'type'=>1))->sum('money');
    	$total_money = $total_money>0 ? number_format($total_money, 2):0;
    	$this->assign('nofh_total_money', $nofh_total_money);
    	$this->assign('fh_total_money', $fh_total_money);
    	$this->assign('total_money', $total_money);
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	//$map['F.status'] = 0;
    	$map['type'] = 1;
    	$cache = $m->alias('as F')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_order` AS O ON O.id = F.oid')
    	->field('F.*,O.vipid,O.vipname,O.vipmobile,O.oid')
    	->where($map)
    	->page($p, $psize)
    	->order('F.ctime desc')
    	->group('`to`')
    	->select();
    	$i = ($p-1)*$psize+1;
    	foreach($cache as $k => $v) {
    		$fhmap['to'] = $v['to'];
    		$fhmap['status'] = 1;
    		$fhmap['type'] = 1;
    		$cache[$k]['index'] = $i;
    		$cache[$k]['fhsum'] = $m->where($fhmap)->sum('money');
    		$cache[$k]['fhsum'] = $cache[$k]['fhsum'] ? $cache[$k]['fhsum'] : 0;
    		$fhmap['status'] = 0;
    		$cache[$k]['nofhsum'] = $m->where($fhmap)->sum('money');
    		$cache[$k]['nofhsum'] = $cache[$k]['nofhsum'] ? $cache[$k]['nofhsum'] : 0;
    		$i++;
    	}
    	$groupSql = $m->alias('as F')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_order` AS o ON o.id = F.oid')
    	->where($map)
    	->group('`to`')
    	->field("count(*)")
    	->buildSql();
    	$count = D()->table("{$groupSql} as t")->count();
    	$this->getPage($count, $psize, 'App-loader', '用户分红资金', 'App-search');   	
    	$this->assign('cache', $cache);
    	$this->assign('hover', 'fh');
    	$this->display();
    }
    //获取用户未分红金额
    private function getVipNofh($vipid) 
    {
    	$m = M('Finance_fhlog');
    	$morder = M('Finance_order');
    	$money = 0;
    	//未开始分红订单预计分红
    	$fenhong1 = $morder->alias('as O')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'finance_goods` AS G ON O.goodsid = G.id')
    	->where('vipid='.$vipid.' AND O.status=2 AND G.status=1 AND G.ismb=0')
    	->sum('fenhong');
    	//一开始分红订单未分红总额
    	$fenhong2 = $m->where(array('to'=>$vipid,'status'=>0, 'type'=>1))->sum('money');
    	$money = $fenhong1 + $fenhong2;
    	return $money>0?$money:0;
    }
    //商城财务
    public function shop()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '财务报表',
    					'url' => U('Admin/Report/index')
    			),
    			'1' => array(
    					'name' => '商城财务',
    					'url' => U('Admin/Report/shop')
    			)
    	);
    	$m = M('Shop_order');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	//用户购买总金额
    	$total_money = $m->where(array('status'=>array('in', '2,3,4,5')))->sum('payprice');
    	$total_money = $total_money ? round($total_money,2) : 0;
    	$this->assign('total_money', $total_money);
    	$search = I('search') ? I('search') : '';
    	if ($search) {
    		$vipid = I('vipid') ? I('vipid',0,'intval') : '';
    		if($vipid) {
    			$map['vipid'] = $vipid;
    		}
    		$keyword = I('keyword') ? I('keyword') : '';
    		if($keyword) {
    			$map['vipname|vipmobile'] = array('like', "%$keyword%");
    		}
    		$date =  I('date') ? I('date') : '';
    		if ($date != '') {
    			$timeArr = explode(" - ", $date);
    			$map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
    		}
    	}
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$map['status'] = array('in', '2,3,4,5');
    	$cache = $m->where($map)->page($p, $psize)
    	->order('ctime desc')
    	->page($p, $psize)
    	->select();
    	$i = ($p-1)*$psize+1;
    	foreach($cache as $k => $v) {
    		$cache[$k]['index'] = $i;
    		$cache[$k]['total_payprice'] = round($cache[$k]['total_payprice'],2);
    		$i++;
    	}
    	$count = $m->where($map)->count();
    	$this->getPage($count, $psize, 'App-loader', '商城财务', 'App-search');
    	$this->assign('cache', $cache);
    	$this->assign('vipid', $vipid);
    	$this->assign('keyword', $keyword);
    	$this->assign('date', $date);
    	$this->assign('hover', 'shop');
    	$this->display();
    }
    /**
     * 导出报表
     * author: feng
     * create: 2017/10/5 17:43
     */
    public function indexExport()
    {

        $m = M('Vip_log_money');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            /*
            $type = I('type') ? I('type',0,'intval') : '';
            if($type == 1) {
                $map['money'] = array('lt', 0);
            } elseif($type == 2) {
                $map['money'] = array('gt', 0);
            }
            */
            $vipid = I('vipid') ? I('vipid',0,'intval') : '';
            if($vipid) {
                $map['vipid'] = $vipid;
            }
            $keyword = I('keyword') ? I('keyword') : '';
            if($keyword) {
                $map['nickname|mobile'] = array('like', "%$keyword%");
            }
            $date =  I('date') ? I('date') : '';
            if ($date != '') {
                $timeArr = explode(" - ", $date);
                $map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
            }
        }


        $data=$m->where($map)->order('ctime desc')->select();

        //dump($data);
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            unset($data[$k]['id']);
            unset($data[$k]['openid']);

            unset($data[$k]['appopenid']);
            switch ($v["paytype"]){
                case "money":
                    $data[$k]['paytype'] = "余额支付";
                    break;
                case "wxpay":
                    $data[$k]['paytype'] = "微信支付";
                    break;
                case "alipay":
                    $data[$k]['paytype'] = "支付宝";
                    break;
            }
            if($v["money"]<0){
                $data[$k]["money"]=-$v["money"];
            }
            switch ($v['type']) {

                case 1:
                    $data[$k]['type'] = "充值";
                    break;
                case 2:
                    $data[$k]['type'] = "提现";
                    break;
                case 3:
                    $data[$k]['type'] = "购买商品";
                    break;
                case 4:
                    $data[$k]['type'] = "众筹创业项目";
                    break;
                case 5:
                    $data[$k]['type'] = "项目收益";
                    break;
                case 6:
                    $data[$k]['type'] = "返还本金";
                    break;
                case 7:
                    $data[$k]['type'] = "取消提现";
                    break;
                case 8:
                    $data[$k]['type'] = "手续费";
                    break;
                case 9:
                    $data[$k]['type'] = "退款";
                    break;
                case 10:
                    $data[$k]['type'] = "赎回";
                    break;
                case 11:
                    $data[$k]['type'] = "带来注册奖励";
                    break;
                case 12:
                    $data[$k]['type'] = "带来消费奖励";
                    break;
                case 13:
                    $data[$k]['type'] = "购买VIP会员";
                    break;
                case 17:
                    $data[$k]['type'] = "区域产品被购买返回费用";
                    break;
                case 18:
                    $data[$k]['type'] = "配送费用";
                    break;

            }
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);


        }
        //dump($data);
        //die();
        $title = array('会员ID', '会员昵称', '手机号码', '金额', '支付类型', '余额', '明细类型', '关联订单号', '记录时间', '备注');
        $this->exportexcel($data, $title, "财务报表" . date('Y-m-d H:i:s', time()));
    }


    /**
     * 区域管理导出
     * author: feng
     * create: 2017/10/5 17:34
     */
    public function areaExport()
    {

        $status = I('status');
        $m = M('Vip_log_money');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            /*
            $type = I('type') ? I('type',0,'intval') : '';
            if($type == 1) {
                $map['money'] = array('lt', 0);
            } elseif($type == 2) {
                $map['money'] = array('gt', 0);
            }
            */
            $vipid = I('vipid') ? I('vipid',0,'intval') : '';
            if($vipid) {
                $map['v.vipid'] = $vipid;
            }
            $keyword = I('keyword') ? I('keyword') : '';
            if($keyword) {
                $map['v.nickname|v.mobile'] = array('like', "%$keyword%");
            }
            $date =  I('date') ? I('date') : '';
            if ($date != '') {
                $timeArr = explode(" - ", $date);
                $map['v.ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
            }

        }
        $user=self::$CMS['user'];
        if($user["user_type"]>0){
            $map["O.adminid"]=$user["id"];
        }else{
            $map["O.adminid"]=array("gt",0);
        }

        $map["v.type"]=array("in","3,17");

        $data = $m->alias('as v')
            ->join('LEFT JOIN `'.C('DB_PREFIX').'shop_order` AS O ON O.oid = v.oid')
            ->field('v.*')
            ->where($map)
            ->order('v.ctime desc')
            ->select();

        //dump($data);
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            unset($data[$k]['id']);
            unset($data[$k]['openid']);

            unset($data[$k]['appopenid']);
            if($v["money"]<0){
                $data[$k]["money"]=-$v["money"];
            }
            switch ($v["paytype"]){
                case "money":
                    $data[$k]['paytype'] = "余额支付";
                    break;
                case "wxpay":
                    $data[$k]['paytype'] = "微信支付";
                    break;
                case "alipay":
                    $data[$k]['paytype'] = "支付宝";
                    break;
            }
            switch ($v['type']) {

                case 1:
                    $data[$k]['type'] = "充值";
                    break;
                case 2:
                    $data[$k]['type'] = "提现";
                    break;
                case 3:
                    $data[$k]['type'] = "购买商品";
                    break;
                case 4:
                    $data[$k]['type'] = "众筹创业项目";
                    break;
                case 5:
                    $data[$k]['type'] = "项目收益";
                    break;
                case 6:
                    $data[$k]['type'] = "返还本金";
                    break;
                case 7:
                    $data[$k]['type'] = "取消提现";
                    break;
                case 8:
                    $data[$k]['type'] = "手续费";
                    break;
                case 9:
                    $data[$k]['type'] = "退款";
                    break;
                case 10:
                    $data[$k]['type'] = "赎回";
                    break;
                case 11:
                    $data[$k]['type'] = "带来注册奖励";
                    break;
                case 12:
                    $data[$k]['type'] = "带来消费奖励";
                    break;
                case 13:
                    $data[$k]['type'] = "购买VIP会员";
                    break;
                case 17:
                    $data[$k]['type'] = "区域产品被购买返回费用";
                    break;
                case 18:
                    $data[$k]['type'] = "配送费用";
                    break;

            }
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);


        }
        //dump($data);
        //die();
        $title = array('会员ID', '会员昵称', '手机号码', '金额', '支付类型', '余额', '明细类型', '关联订单号', '记录时间', '备注');
        $this->exportexcel($data, $title, "区域管理报表" . date('Y-m-d H:i:s', time()));
    }
    /**
     * 配送费用导出
     * author: feng
     * create: 2017/10/5 17:34
     */
    public function deliveryExport()
    {

        $m = M('Vip_log_money');
        $search = I('search') ? I('search') : '';
        if ($search) {

            $vipid = I('vipid') ? I('vipid',0,'intval') : '';
            if($vipid) {
                $map['vipid'] = $vipid;
            }
            $keyword = I('keyword') ? I('keyword') : '';
            if($keyword) {
                $map['nickname|mobile'] = array('like', "%$keyword%");
            }
            $date =  I('date') ? I('date') : '';
            if ($date != '') {
                $timeArr = explode(" - ", $date);
                $map['ctime'] = array('between',array(strtotime($timeArr[0]),strtotime($timeArr[1])+60*60*24));
            }
        }
        $map['type'] = 18;

        $data = $m->where($map)->order('ctime desc')->select();

        //dump($data);
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            unset($data[$k]['id']);
            unset($data[$k]['openid']);

            unset($data[$k]['appopenid']);
            if($v["money"]<0){
                $data[$k]["money"]=-$v["money"];
            }
            switch ($v["paytype"]){
                case "money":
                    $data[$k]['paytype'] = "余额支付";
                    break;
                case "wxpay":
                    $data[$k]['paytype'] = "微信支付";
                    break;
                case "alipay":
                    $data[$k]['paytype'] = "支付宝";
                    break;
            }
            switch ($v['type']) {

                case 1:
                    $data[$k]['type'] = "充值";
                    break;
                case 2:
                    $data[$k]['type'] = "提现";
                    break;
                case 3:
                    $data[$k]['type'] = "购买商品";
                    break;
                case 4:
                    $data[$k]['type'] = "众筹创业项目";
                    break;
                case 5:
                    $data[$k]['type'] = "项目收益";
                    break;
                case 6:
                    $data[$k]['type'] = "返还本金";
                    break;
                case 7:
                    $data[$k]['type'] = "取消提现";
                    break;
                case 8:
                    $data[$k]['type'] = "手续费";
                    break;
                case 9:
                    $data[$k]['type'] = "退款";
                    break;
                case 10:
                    $data[$k]['type'] = "赎回";
                    break;
                case 11:
                    $data[$k]['type'] = "带来注册奖励";
                    break;
                case 12:
                    $data[$k]['type'] = "带来消费奖励";
                    break;
                case 13:
                    $data[$k]['type'] = "购买VIP会员";
                    break;
                case 17:
                    $data[$k]['type'] = "区域产品被购买返回费用";
                    break;
                case 18:
                    $data[$k]['type'] = "配送费用";
                    break;

            }
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);


        }
        //dump($data);
        //die();
        $title = array('会员ID', '会员昵称', '手机号码', '金额', '支付类型', '余额', '明细类型', '关联订单号', '记录时间', '备注');
        $this->exportexcel($data, $title, "配送费用报表" . date('Y-m-d H:i:s', time()));
    }
    /**
     * 导出数据为excel表格
     * @param $data    一个二维数组,结构如同从数据库查出来的数组
     * @param $title   excel的第一行标题,一个数组,如果为空则没有标题
     * @param $filename 下载的文件名
     * @examlpe
    $stu = M ('User');
     * $arr = $stu -> select();
     * exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
     */
    private function exportexcel($data = array(), $title = array(), $filename = 'report')
    {
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $title[$k] = iconv("UTF-8", "GB2312", $v);
            }
            $title = implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck] = iconv("UTF-8", "GB2312", $cv);
                }
                $data[$key] = implode("\t", $data[$key]);

            }
            echo implode("\n", $data);
        }

    }
}