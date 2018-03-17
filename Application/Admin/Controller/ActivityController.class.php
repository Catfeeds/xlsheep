<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组商城管理类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class ActivityController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
        //初始化两个配置
        self::$CMS['shopset'] = M('Shop_set')->find();
        self::$CMS['vipset'] = M('Vip_set')->find();
    }

    //CMS后台商城分组
    public function goods()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Activity/goods'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $map['type'] = 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商品设置
    public function goodsSet()
    {
        $id = I('id');
        $m = M('Shop_goods');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => '商品管理',
                'url' => U('Admin/Activity/goods'),
            ),
            '2' => array(
                'name' => '商品设置',
                'url' => $id ? U('Admin/Activity/goodsSet', array('id' => $id)) : U('Admin/Md/mdSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            // $data['adressid'] = substr($data['adressid'],0,strlen($data['adressid'])-1);
            $data['content'] = trimUE($data['content']);
            if ($id) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
            	$data['type'] = 1;
                $re = $m->add($data);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Activity_cate'), 0, $field);
        $this->assign('cate', $cate);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);    
        }
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }

    public function goodsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_goods');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->delete($id);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

    public function goodsStatus()
    {
        $m = M('Shop_goods');
        $now = I('status') ? 0 : 1;
        $map['id'] = I('id');
        if($now == 1){
            $handle = $m->where($map)->setField('ishandle', 1);
        }
        $re = $m->where($map)->setField('status', $now);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }

   
    //CMS后台商城分组
    public function group()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => '商城分组',
                'url' => U('Admin/Activity/group'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_group');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台SKU属性
    public function skuattr()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => 'SKU属性',
                'url' => U('Admin/Activity/skuattr'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_skuattr');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', 'SKU属性', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城订单
    public function order()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => '订单管理',
                'url' => U('Admin/Activity/order'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $groupid = I('groupid' ,0 ,'intval');
        if($groupid>0){
        	$map['groupid'] = $groupid;
        }
        $status = I('status');
        if ($status || $status == '0') {
            $map['status'] = $status;
            //交易满7天
            if ($status == 8) {
                $map['status'] = 3;
                $seven = time() - 604800;
                $map['ctime'] = array('elt', $seven);
            }
            // 当天所有订单，零点算起
            if ($status == 9) {
                unset($map['status']);
                $today = strtotime(date("Y-m-d"));
                $map['ctime'] = array('egt', $today);
                //echo $today;
            }
            if ($status == 10) {
                $map['status'] = 8;
            }
        }
        $this->assign('status', $status);
        //绑定搜索条件与分页
        $m = M('Shop_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //订单号邦定
            $map['oid'] = array('like', "%$name%");
            $map['vipmobile'] = array('like', "%$name%");
            $map['_logic'] = 'OR';
            $this->assign('name', $name);
        }
        $map['type'] = 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台Order详情
    public function orderDetail()
    {
        $id = I('id');
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Activity/index'),
            ),
            '1' => array(
                'name' => '商城订单',
                'url' => U('Admin/Activity/order'),
            ),
            '2' => array(
                'name' => '订单详情',
                'url' => $id ? U('Admin/Activity/orderDetail', array('id' => $id)) : U('Admin/Activity/orderDetail'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = $m->where('id=' . $id)->find();
        //坠入vip
        $vip = M('vip')->where('id=' . $cache['vipid'])->find();
        $this->assign('vip', $vip);
        $cache['items'] = unserialize($cache['items']);
        $log = $mlog->where('oid=' . $cache['id'])->select();
        $fxlog = M('Fx_syslog')->where('oid=' . $cache['id'])->select();
        //微信卡劵
        $wxcache = $m->where(array('id'=>$id,'goodstype'=>'coupon'))->find();
        $wxkjorder = M('Vip_coupon')->where(array('vipid'=>$wxcache['vipid']))->find();

        $this->assign('wxkjorder', $wxkjorder);
        $this->assign('log', $log);
        $this->assign('fxlog', $fxlog);
        $this->assign('cache', $cache);
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }


     public function orderExport()
     {

       
        $id = I('id');
        $map['type'] = 1;
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        }
        $status = I('status');
        if ($status || $status == '0') {
            $map['status'] = $status;
            //交易满7天
            if ($status == 8) {
                $map['status'] = 3;
                $seven = time() - 604800;
                $map['ctime'] = array('elt', $seven);
            }
            // 当天所有订单，零点算起
            if ($status == 9) {
                unset($map['status']);
                $today = strtotime(date("Y-m-d"));
                $map['ctime'] = array('egt', $today);
                //echo $today;
            }
            if ($status == 10) {
                $map['status'] = 8;
            }
        }

        $data = M('Shop_order')->where($map)->select();
        $title = array(
                '订单编号', 
                '订单总价', 
                '商品总数量', 
                '支付金额', 
                '支付方式', 
                '支付时间', 
                '会员ID', 
                '姓名', 
                '地址', 
                 '订单状态', 
                '下单时间', 
                '订单商品详情', 
                );

         foreach ($data as $k => $v) {
                $mydata[$k]['oid']=$data[$k]['oid'];
                $mydata[$k]['totalprice']=$data[$k]['totalprice'];
                $mydata[$k]['totalnum']=$data[$k]['totalnum'];
                $mydata[$k]['payprice']=$data[$k]['payprice'];
                
                if ($data[$k]['paytype'] == 'wxpay') {
                    $mydata[$k]['paytype']='微信支付';
                }
                if ($data[$k]['paytype'] == 'money') {
                    $mydata[$k]['paytype']='余额支付';
                }
                
                 $mydata[$k]['paytime'] = $data[$k]['paytime'] ? date('Y-m-d H:i:s', $data[$k]['paytime']) : '无';
                
                $mydata[$k]['vipid']=$data[$k]['vipid'];
                $mydata[$k]['vipname']=$data[$k]['vipname'];
                $mydata[$k]['vipaddress']=$data[$k]['vipaddress'];

                if ($data[$k]['status'] == 2) {
                     $mydata[$k]['status']='已确认';
                }

                if ($data[$k]['status'] == 3) {
                     $mydata[$k]['status']='已发货';
                }
                 $mydata[$k]['ctime'] = $data[$k]['ctime'] ? date('Y-m-d H:i:s', $data[$k]['ctime']) : '无';

            $tmpitems = unserialize($v['items']);
            $str = "";
            foreach ($tmpitems as $vv) {

                $tempattr=$vv['skuattr']?$vv['skuattr']:"无";
                $vt = '产品名称：' . $vv['name'] . '  属性：' . $tempattr. '  数量：' . $vv['num'] . '  单价：' . $vv['price'];
                $str = $str . $vt . ' / ';
            }
            $mydata[$k]['items'] = $str;
               
            }

           $this->exportexcel($mydata, $title, '订单数据' . date('Y-m-d H:i:s', time()));
          }

    //发货快递
    public function orderFhkd()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $express = D('Express');
        $express = $express->select();
        $this->assign("express", $express);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderFhkdSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['changetime'] = time();
        $re = M('Shop_order')->where('id=' . $data['id'])->save($data);
        if (FALSE !== $re) {
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单关闭
    public function orderClose()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderCloseSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $cache = $m->where('id=' . $data['id'])->find();
        switch ($cache['status']) {
            case '1':
                $data['status'] = 6;
                $data['closetime'] = time();
                $re = $m->where('id=' . $data['id'])->save($data);
                $data_msg['pids'] = $cache['vipid'];
                $data_msg['title'] = "关闭订单成功";
                $data_msg['content'] = '您的订单'.$cache['oid'].'关闭成功。感谢您的支持！';
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                if (FALSE !== $re) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);

                    $info['status'] = 1;
                    $info['msg'] = '关闭未支付订单成功！';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '关闭未支付订单失败！';
                }
                $this->ajaxReturn($info);
                break;
            case '2':
                //已支付订单跳转到这里处理
                $this->orderClosePay($cache, $data);
                break;
            case '8':
                $this->orderClosePay($cache, $data);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }

    }

    //已支付订单退款
    public function orderClosePay($cache, $data)
    {
        //关闭订单时不再处理库存
        $m = M('Shop_order');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        if (!$cache['ispay']) {
            $info['status'] = 0;
            $info['msg'] = '订单支付状态异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //抓取会员数据
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员数据获取异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //支付金额
        $payprice = $cache['payprice'];
        //全部退款至余额
        $data['status'] = 6;
        $data['closetime'] = time();
        $re = $m->where('id=' . $cache['id'])->save($data);
        if (FALSE !== $re) {
            $log['oid'] = $cache['id'];
            $log['msg'] = '订单关闭-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '关闭订单成功！';
            if ($cache['ispay']) {
                $mm = $vip['money'] + $payprice;
                $jifen = $m -> where('id=' . $cache['id']) -> getField('integral');
                $jiVip = $mvip->where('id=' . $cache['vipid'])->setInc('score', $jifen);
                $rvip = $mvip->where('id=' . $cache['vipid'])->setField('money', $mm);
                if ($rvip) {
                	//资金流水记录
                	$flow['vipid'] = $vip['id'];
                	$flow['openid'] = $vip['openid'];
                	$flow['nickname'] = $vip['nickname'];
                	$flow['mobile'] = $vip['mobile'];
                	$flow['money'] = $payprice;
                	$flow['paytype'] = '';
                	$flow['balance'] = $mm;
                	$flow['type'] = 9;
                	$flow['oid'] = $cache['oid'];
                	$flow['ctime'] = time();
                	$flow['remark'] = '订单退款，自动退款到用户余额';
                	$rflow = $mlog->add($flow);
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    //后端LOG
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额成功!';
                    $data_msg['pids'] = $cache['vipid'];
                    $data_msg['title'] = "关闭订单成功,已自动退款";
                    $data_msg['content'] = '您的订单'.$cache['oid'].'关闭成功。金额已自动退到您的账户余额。感谢您的支持！';
                    $data_msg['ctime'] = time();
                    $rmsg = M('Vip_message')->add($data_msg);
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-失败!请联系客服!';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额失败!请联系技术！';
                }
                $yuanyin = $m -> where('id=' . $cache['id']) -> getField('tuihuomsg');
                $data = array();
                $data['touser'] = $vip['openid'];
                $data['template_id'] = 'TjfH9p63y7iRgCn9Q73IvMBCS9QhWPFtrjbPiaK3NUg';
                $data['topcolor'] = "#00FF00";
                $data['data'] = array(
                    'first' => array('value' => '您好，您的退款申请已处理'),
                    'reason' => array('value' => $yuanyin),
                    'refund' => array('value' => $payprice),
                    'remark' => array('value' => '请注意查看金额是否有到账！')
                );
                $options['appid'] = self::$SYS['set']['wxappid'];
                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                $wx = new \Util\Wx\Wechat($options);
                $re = $wx->sendTemplateMessage($data);
            }

        } else {
            $info['status'] = 0;
            $info['msg'] = '关闭订单失败！请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    //订单发货
    public function orderDeliver()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
        }
        $re = M('Shop_order')->where('id=' . $id)->setField('status', 3);
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $dwechat = D('Wechat');
        if (FALSE !== $re) {
            $log['oid'] = $id;
            $log['msg'] = '订单已发货';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            //后端LOG
            $log['type'] = 3;
            $log['paytype'] = $cache['paytype'];
            $rslog = $mslog->add($log);

            // 插入订单发货模板消息=====================
            $order = M('Shop_order')->where('id=' . $id)->find();
            $vip = M('vip')->where(array('id' => $order['vipid']))->find();
            $templateidshort = 'OPENTM201541214';
            $templateid = $dwechat->getTemplateId($templateidshort);

            if ($templateid) { // 存在才可以发送模板消息
                $data = array();
                $data['touser'] = $vip['openid'];
                $data['template_id'] = $templateid;
                $data['topcolor'] = "#0000FF";
                $data['data'] = array(
                    'first' => array('value' => '您好，您的商品已发货'),
                    'keyword1' => array('value' => $order['oid']),
                    'keyword2' => array('value' => $order['fahuokd']),
                    'keyword3' => array('value' => $order['fahuokdnum']),
                    'keyword4' => array('value' => $order['payprice']),
                    'keyword5' => array('value' => $order['vipaddress']),
                    'remark' => array('value' => '商品已发出请注意查收')
                );
                $options['appid'] = self::$SYS['set']['wxappid'];
                $options['appsecret'] = self::$SYS['set']['wxappsecret'];

                $wx = new \Util\Wx\Wechat($options);
                $rere = $wx->sendTemplateMessage($data);

            }
            // 插入订单发货模板消息结束=================
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单批量发货
    public function orderDeliverAll()
    {
        $arr = array_filter(explode(',', $_GET['id'])); //必须使用get方法
        if (!$arr) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        // ==========================================================
        $dwechat = D('Wechat');
        $options['appid'] = self::$SYS['set']['wxappid'];
        $options['appsecret'] = self::$SYS['set']['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        // ==========================================================
        $err = TRUE;
        foreach ($arr as $k => $v) {
            $old = $m->where('id=' . $v)->find();
            if ($old['status'] == 2) {
                $re = $m->where('id=' . $old['id'])->setField('status', 3);
                if (FALSE !== $re) {
                    $log['oid'] = $old['id'];
                    $log['msg'] = '订单已发货';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 3;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    // 插入订单发货模板消息=====================
                    $vip = M('vip')->where(array('id' => $old['vipid']))->find();
                    $templateidshort = 'OPENTM201541214';
                    $templateid = $dwechat->getTemplateId($templateidshort);
                    if ($templateid) { // 存在才可以发送模板消息
                        $data = array();
                        $data['touser'] = $vip['openid'];
                        $data['template_id'] = $templateid;
                        $data['topcolor'] = "#0000FF";
                        $data['data'] = array(
                            'first' => array('value' => '您好，您的订单已发货'),
                            'keyword1' => array('value' => $old['oid']),
                            'keyword2' => array('value' => $old['fahuokd']),
                            'keyword3' => array('value' => $old['fahuokdnum']),
                            'remark' => array('value' => '')
                        );
                        $re = $wx->sendTemplateMessage($data);
                    }
                    // 插入订单发货模板消息结束=================
                } else {
                    $err = FALSE;
                }
            }
        }
        if ($err) {
            $info['status'] = 1;
            $info['msg'] = '批量发货成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '批量发货可能有部分失败，请刷新后重新尝试！';
        }

        $this->ajaxReturn($info);
    }

    //完成订单
    public function orderSuccess()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        //判断商城配置
        if (!self::$CMS['shopset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取商城配置信息！';
            $this->ajaxReturn($info);
        }
        //判断会员配置
        if (!self::$CMS['vipset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取会员配置信息！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $map['id'] = $id;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        if ($cache['status'] != 3) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = M('Vip')->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        $cache['etime'] = time(); //交易完成时间
        $cache['status'] = 5;
        $rod = $m->save($cache);
        if (FALSE !== $rod) {
            //修改会员账户金额、经验、积分、等级
            $data_vip['id'] = $cache['vipid'];
            $data_vip['score'] = array('exp', 'score+' . round($cache['payprice'] * self::$CMS['vipset']['cz_score'] / 100));
            if (self::$CMS['vipset']['cz_exp'] > 0) {
                $data_vip['exp'] = array('exp', 'exp+' . round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $level = $this->getLevel($vip['cur_exp'] + round($cache['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
                $data_vip['levelid'] = $level['levelid'];
                //会员合计支付
                $data_vip['total_buy'] = $data_vip['total_buy'] + $cache['payprice'];
            }
            $re = M('vip')->save($data_vip);
            if (FALSE === $re) {
                $info['status'] = 0;
                $info['msg'] = '更新订单关联会员信息失败！';
                $this->ajaxReturn($info);
            }
     
            $mlog = M('Shop_order_log');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货,交易完成。';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);

            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '交易完成-后台点击';
            $dlog['type'] = 5;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            //$this->success('交易已完成，感谢您的支持！');
            $info['status'] = 1;
            $info['msg'] = '后台确认收货操作完成！';
        } else {
            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            //$this->error('确认收货失败，请重新尝试！');
            $info['status'] = 0;
            $info['msg'] = '后台确认收货操作失败，请重新尝试！';
        }
        $this->ajaxReturn($info);
    }

    //订单退货
    public function orderTuihuo()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderTuihuoSave()
    {
        $data = I('post.');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $mvip = M('Vip');
        $cache = $m->where('id=' . $data['id'])->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取订单数据！';
            $this->ajaxReturn($info);
        }
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单数据！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        switch ($cache['status']) {
            case '4':
                $data['status'] = 7;
                $data['tuihuotime'] = time();
                if (!$data['tuihuoprice']) {
                    $info['status'] = 0;
                    $info['msg'] = '退货金额不能为空！';
                    $this->ajaxReturn($info);
                }
                $re = $m->where('id=' . $data['id'])->save($data);
                if (FALSE !== $re) {
                    $vip['money'] = $vip['money'] + $data['tuihuoprice'];
                    $rvip = $mvip->save($vip);
                    if ($rvip !== FALSE) {
                    	//资金流水记录
                    	$flow['vipid'] = $vip['id'];
                    	$flow['openid'] = $vip['openid'];
                    	$flow['nickname'] = $vip['nickname'];
                    	$flow['mobile'] = $vip['mobile'];
                    	$flow['money'] = $data['tuihuoprice'];
                    	$flow['paytype'] = '';
                    	$flow['balance'] = $vip['money'];
                    	$flow['type'] = 9;
                    	$flow['oid'] = $cache['oid'];
                    	$flow['ctime'] = time();
                    	$flow['remark'] = '商品退货，自动退款到用户余额';
                    	$rflow = $mlog->add($flow);
                    	
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-成功';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        $log['type'] = 6;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        //后端LOG
                        $info['status'] = 1;
                        $info['msg'] = '关闭订单成功！自动退款' . $data['tuihuoprice'] . '元至用户余额成功!';
                    } else {
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-失败!请联系客服!';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        //后端LOG
                        $log['type'] = -1;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        $info['status'] = 1;
                        $info['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额失败!请联系技术！';
                    }
                    $data = array();
                    $data['touser'] = $vip['openid'];
                    $data['template_id'] = 'TjfH9p63y7iRgCn9Q73IvMBCS9QhWPFtrjbPiaK3NUg';
                    $data['topcolor'] = "#00FF00";
                    $data['data'] = array(
                        'first' => array('value' => '您好，您的退货申请已处理'),
                        'reason' => array('value' => $cache['tuihuomsg']),
                        'refund' => array('value' => $cache['payprice']),
                        'remark' => array('value' => '请注意查看金额是否有到账！')
                    );
                    $options['appid'] = self::$SYS['set']['wxappid'];
                    $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                    $wx = new \Util\Wx\Wechat($options);
                    $re = $wx->sendTemplateMessage($data);

                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '订单退货失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '订单退货失败！';
                }
                $this->ajaxReturn($info);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }
        //$info['status']=0;
        //$info['msg']='通讯失败，请重新尝试!';
        //$this->ajaxReturn($info);

    }
    //CMS后台商城分类
    public function cate()
    {
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '拼团管理',
    					'url' => U('Admin/Activity/index'),
    			),
    			'1' => array(
    					'name' => '拼团分类',
    					'url' => U('Admin/Activity/cate'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//绑定搜索条件与分页
    	$m = M('Activity_cate');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$name = I('name') ? I('name') : '';
    	$map = array();
    	if ($name) {
    		$map['name'] = array('like', "%$name%");
    		$this->assign('name', $name);
    	}
    	//AppTree快速无限分类
    	$field = array("id", "pid", "lv", "name", "summary", "soncate", "sorts", "concat(path,'-',id) as bpath");
    	$cache = appTree($m, 0, $field, $map);
    	$this->assign('cache', $cache);
    	$this->display();
    }
    
    //CMS后台商城分类设置
    public function cateSet()
    {
    	$id = I('id');
    	$m = M('Activity_cate');
    	//设置面包导航，主加载器请配置
    	$bread = array(
    			'0' => array(
    					'name' => '拼团管理',
    					'url' => U('Admin/Activity/index'),
    			),
    			'1' => array(
    					'name' => '商城分类',
    					'url' => U('Admin/Activity/cate'),
    			),
    			'2' => array(
    					'name' => '分类设置',
    					'url' => $id ? U('Admin/Activity/cateSet', array('id' => $id)) : U('Admin/Shop/cateSet'),
    			),
    	);
    	$this->assign('breadhtml', $this->getBread($bread));
    	//处理POST提交
    	if (IS_POST) {
    		$data = I('post.');
    		if ($id) {
    			//保存时判断
    			if($data['pid'] == $id) {
    				$info['status'] = 0;
    				$info['msg'] = '不能添加自己为父类！';
    				$this->ajaxReturn($info);
    			}
    			$old = $m->where('id=' . $id)->limit(1)->find();
    			if ($old['pid'] != $data['pid']) {
    				$hasson = $m->where('pid=' . $id)->limit(1)->find();
    				if ($hasson) {
    					$info['status'] = 0;
    					$info['msg'] = '此分类有子分类，不可以移动！';
    					$this->ajaxReturn($info);
    				}
    			}
    			if ($data['pid']) {
    				//更新Path，强制处理
    				$path = setPath($m, $data['pid']);
    				$data['path'] = $path['path'];
    				$data['lv'] = $path['lv'];
    			} else {
    				$data['path'] = 0;
    				$data['lv'] = 1;
    			}
    			$re = $m->save($data);
    			if (FALSE !== $re) {
    				//更新新老父级，暂不做错误处理
    				if ($old['pid'] != $data['pid']) {
    					$re = setSoncate($m, $data['pid']);
    					$rold = setSoncate($m, $old['pid']);
    					$info['status'] = 1;
    					$info['msg'] = $old['pid'];
    					$this->ajaxReturn($info);
    				} else {
    					$re = setSoncate($m, $data['pid']);
    				}
    				$info['status'] = 1;
    				$info['msg'] = '设置成功！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '设置失败！';
    			}
    		} else {
    			if ($data['pid']) {
    				//更新父级，强制处理
    				$path = setPath($m, $data['pid']);
    				$data['path'] = $path['path'];
    				$data['lv'] = $path['lv'];
    			} else {
    				$data['path'] = 0;
    				$data['lv'] = 1;
    			}
    			$re = $m->add($data);
    			if ($re) {
    				//更新父级，暂不做错误处理
    				if ($data['pid']) {
    					$re = setSoncate($m, $data['pid']);
    				}
    				$info['status'] = 1;
    				$info['msg'] = '设置成功！';
    			} else {
    				$info['status'] = 0;
    				$info['msg'] = '设置失败！';
    			}
    		}
    		$this->ajaxReturn($info);
    	}
    	//处理编辑界面
    	if ($id) {
    		$cache = $m->where('id=' . $id)->find();
    		$this->assign('cache', $cache);
    	}
    	//AppTree快速无限分类
    	$field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
    	$map = array('lv' => 1);
    	$cate = appTree($m, 0, $field, $map);
    	$this->assign('cate', $cate);
    	$this->display();
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
    
    public function cateDel()
    {
    	$id = $_GET['id']; //必须使用get方法
    	$m = M('Activity_cate');
    	if (!$id) {
    		$info['status'] = 0;
    		$info['msg'] = 'ID不能为空!';
    		$this->ajaxReturn($info);
    	}
    	//删除时判断
    	$self = $m->where('id=' . $id)->limit(1)->find();
    	$re = $m->delete($id);
    	// 删除所有子类
    	$tempList = split(',', $self['soncate']);
    	foreach ($tempList as $k => $v) {
    		$res = $m->delete($v);
    	}
    	if ($re) {
    		//更新上级soncate
    		if ($self['pid']) {
    			$re = setSoncate($m, $self['pid']);
    		}
    		$info['status'] = 1;
    		$info['msg'] = '删除成功!';
    	} else {
    		$info['status'] = 0;
    		$info['msg'] = '删除失败!';
    		$this->ajaxReturn($info);
    	}
    	$this->ajaxReturn($info);
    }
    //成团列表
    public function groupList() {
    	//绑定搜索条件与分页
    	$m = M('Activity_group');
    	$p = $_GET['p'] ? $_GET['p'] : 1;
    	$status = I('status') ? I('status') : '';
    	if($status != '') {
    		$map['A.status'] = $status;
    		$this->assign('status', $status);
    	}
    	$goodsid = I('goodsid', 0, 'intval');
    	if($goodsid) {
    		$map['A.goods_id'] = array('eq', $goodsid);
    		$this->assign('goodsid', $goodsid);
    	}
    	$vipid = I('vipid', 0, 'intval');
    	if($vipid) {
    		$map['A.vipid'] = array('eq', $vipid);
    		$this->assign('vipid', $vipid);
    	}
    	if($name) {
    		$map['G.name'] = array('like', "%$name%");;
    		$this->assign('name', $name);
    	}
    	$map['G.type'] = 1;
    	$psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
    	$cache = $m->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'shop_goods` AS G ON G.id = A.goods_id')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where($map)
    	->field('A.*,V.nickname,V.headimgurl,G.name,G.price,G.unit,G.peoplenum')
    	->page($p, $psize)
    	->order('A.ctime desc')
    	->select();
    	$count = $m->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'shop_goods` AS G ON G.id = A.goods_id')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where($map)
    	->count();
    	$this->getPage($count, $psize, 'App-loader', '成团列表', 'App-search');
    	$this->assign('cache', $cache);
    	$this->display();
    }
}