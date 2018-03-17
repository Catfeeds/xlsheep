<?php
namespace Admin\Controller;

class VipController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    public function set()
    {
        $m = M('vip_set');
        $data = $m->find();
        if (IS_POST) {
            $post = I('post.');
            if ($post['isgift'] == 1) {
                $post['gift_detail'] = $post['gift_type'] . "," . $post['gift_money'] . "," . $post['gift_days'] . "," . $post['gift_usemoney'];
            }
            unset($post['gift_type']);
            unset($post['gift_money']);
            unset($post['gift_days']);
            unset($post['gift_usemoney']);
            $r = $data ? $m->where('id=' . $data['id'])->save($post) : $m->add($post);
            if (FALSE !== $r) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info, "json");
        } else {
            //设置面包导航，主加载器请配置
            $bread = array(
                '0' => array(
                    'name' => '会员中心',
                    'url' => U('Admin/Vip/#'),
                ),
                '1' => array(
                    'name' => '会员设置',
                    'url' => U('Admin/Vip/set'),
                ),
            );
            $this->assign('breadhtml', $this->getBread($bread));
            $data = $m->find();
            if ($data['isgift'] == 1) {
                $gift = explode(",", $data['gift_detail']);
                $data['gift_type'] = $gift[0];
                $data['gift_money'] = $gift[1];
                $data['gift_days'] = $gift[2];
                $data['gift_usemoney'] = $gift[3];
            }
            $this->assign('data', $data);
            $shopset = self::$SHOP['set'];
            $this->assign('shopset', $shopset);
            $this->display();
        }
    }

    // 获取层级
    public function vipTree()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        $mvip = M('vip');
        $data = I('data');
        $id = I('id');
        $str = '<br>';
        $vipids = explode('-', $data);
        $vip = $mvip->where('id=' . $id)->find();
        if (count($vipids) <= 1) {
            $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $vip['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $vip['nickname'] . "(当前用户)" . "</div>";
        } else {
            foreach ($vipids as $k => $v) {
                # code...
                if ($k == 0) {
                } else {
                    $temp = $mvip->where('id=' . $v)->find();
                    $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $temp['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $temp['nickname'] . "<br>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp↑<br></div>";
                    $str .= "<br><br><br>";
                }
            }
            $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $vip['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $vip['nickname'] . "(当前用户)" . "</div>";
        }

        $this->ajaxReturn(array('msg' => $str), "json");
    }

    // 层级树
    public function vipTrack()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        // 获取模型
        $dvip = D('Vip');
        if (IS_POST) {
            $vipid = I('vipid');
            $cache = D('Vip')->getChildren($vipid);
            $str = '<ul>';
            // 组装返回数据
            if (count($cache) > 0) {
                foreach ($cache as $k => $vip) {
                    if ($vip['type'] == 1) {
                        $str .= '<li id="node' . $vip['id'] . '" data-id="' . $vip['id'] . '" class="parent">';
                        $str .= '<span onclick="javascript:pathopen(this);"><i class="glyphicon glyphicon-plus"></i> ' . $vip['nickname'] . '</span> <a href="javascript:;"></a><span class="numPer redCol">' . $vip['count1'] . '</span><span class="numPer rouCol">' . $vip['ocount'] . '单：共计' . $vip['osum'] . '</span><span class="numPer eyeCol" data-id="' . $vip['id'] . '" onclick="userInfo(this)"><i class="glyphicon glyphicon-eye-open"></i></span>';
                    } else {
                        $str .= '<li id="node' . $vip['id'] . '" data-id="' . $vip['id'] . '" class="leaf">';
                        $str .= '<span><i class="glyphicon glyphicon-leaf"></i> ' . $vip['nickname'] . '</span> <a href="javascript:;"></a><span class="numPer rouCol" style="color:black">' . $vip['ocount'] . '单：共计' . $vip['osum'] . '</span><span class="numPer eyeCol eyeColCol" data-id="' . $vip['id'] . '" onclick="userInfo(this)"><i class="glyphicon glyphicon-eye-open"></i></span>';
                    }
                    $str .= '</li>';
                }
            }
            $str .= '</ul>';
            $this->ajaxReturn(array('msg' => $str, 'id' => $vipid), "json");
            exit();
        }
        $top = $dvip->getChildren();
        $this->assign('cache', $top);
        $this->display();
    }

    // 获取个人信息
    public function vipInfo()
    {
        if (IS_AJAX) {
            $id = I('id');
            $mvip = D('Vip');
            $str = $mvip->getVipForMessage($id);
            if ($str) {
                $this->ajaxReturn(array('msg' => $str), "json");
            } else {
                $this->ajaxReturn(array('msg' => "通信失败"), "json");
            }
        }
    }

    // 设置
    public function vipReborn()
    {
        if (IS_AJAX) {
            $dvip = D('Vip');
            $id = I('id');
            $ppid = I('ppid');

            if ($ppid == $id) {
                $info['status'] = 0;
                $info['msg'] = "调配失败";
            }

            $re = $dvip->vipReborn($id, $ppid);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = "调配成功";
            } else {
                $info['status'] = 0;
                $info['msg'] = "调配失败";
            }
            $this->ajaxReturn($info);
        }
    }

    // Vip未分配会员列表
    public function vipRebornList()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '可调配会员',
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        // 员工介入
        $temp = M('employee')->select();
        $employee = array();
        foreach ($temp as $k => $v) {
            $employee[$v['id']] = $v;
        }

        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $map['plv'] = 1;
        $map['pid'] = 0;
        $map['isfx'] = 0;
        $map['total_xxlink'] = 0;
        //$map['employee']=0;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }

            // 写入员工数据
            if ($v['employee']) {
                $cache[$k]['employee'] = $employee[$v['employee']]['nickname'];
            } else {
                $cache[$k]['employee'] = '无';
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


    // 设置
    public function vipAlloc()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        if (IS_AJAX) {
            $dvip = D('Vip');
            $id = I('vipid');
            $eid = I('empid');
            $employee = M('employee')->where(array('id' => $eid))->find();
            $vip = M('vip')->where(array('id' => $id, 'plv' => 1))->find();

            if ($employee && $vip) {
                $re = $dvip->setEmployee($id, $eid);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = "员工账户绑定成功";
                } else {
                    $info['status'] = 0;
                    $info['msg'] = "员工账户绑定失败";
                }
                //$info['msg'] = json_encode($re);

            } else {
                $info['status'] = 0;
                $info['msg'] = "员工账户不存在";
            }
            $this->ajaxReturn($info);

        }
    }

    // Vip未分配会员列表
    public function vipAllocList()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员分配中心',
                'url' => U('Admin/Vip/#'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        // 员工介入
        $temp = M('employee')->select();
        $employee = array();
        foreach ($temp as $k => $v) {
            $employee[$v['id']] = $v;
        }
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            //$map['mobile'] = array('like', "%$search%");
            //$map['_logic'] = 'OR';
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $map['plv'] = 1;
        //$map['employee']=0;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }

            // 写入员工数据
            if ($v['employee']) {
                $cache[$k]['employee'] = $employee[$v['employee']]['nickname'];
            } else {
                $cache[$k]['employee'] = '无';
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    // VIP列表
    public function vipList()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/vipList'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        // 员工介入
        $temp = M('employee')->select();
        $employee = array();
        foreach ($temp as $k => $v) {
            $employee[$v['id']] = $v;
        }
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        $plv = I('plv') ? I('plv') : 0;
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        if ($plv) {
            $map['plv'] = $plv;
            $this->assign('plv', $plv);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }
            // 写入员工数据
            if ($v['employee']) {
                $cache[$k]['employee'] = $employee[$v['employee']]['nickname'];
            } else {
                $cache[$k]['employee'] = '无';
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $shopset = self::$SHOP['set'];
        $this->assign('shopset', $shopset);
        $this->display();
    }

    //CMS后台商品设置
    public function vipSet()
    {
        $id = I('id');
        $m = M('Vip');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/vipList'),
            ),
            '1' => array(
                'name' => '会员编辑',
                'url' => U('Admin/Vip/vipSet', array('id' => $id)),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                $vip=$m->where('id=' . $id)->find();
                if($data["groupid"]==2)
                {
                    $nextTime=strtotime( $data["vip_expiration_time"]);
                    if($nextTime<time()){
                        $info['status'] = 0;
                        $info['msg'] = '社员时间不正确！';
                        $this->ajaxReturn($info);
                    }
                    $upgradeData['vip_apply_status']=2;

                    $data["vip_expiration_time"]=strtotime( $data["vip_expiration_time"]);
                }else{

                    $data["vip_expiration_time"]="";
                    $data["vip_apply_type"]=0;
                    $data["vip_apply_status"]=0;
                    $data["vip_apply_money"]=0;

                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    if($vip["groupid"]==2&&$data["groupid"]==1){
                        $m->where('id=' . $id)->setInc("money",$vip["vip_apply_money"]);
                        $mlog = M('Vip_log_money');
                        $data_mlog =array(
                            'vipid' => $id,
                            'openid' => $vip['openid'],
                            'nickname' => $vip['nickname'],
                            'mobile' => $vip['mobile'],
                            'money' => $vip["vip_apply_money"],
                            'paytype' => 'money',
                            'balance' => $vip['money'] + $vip["vip_apply_money"],
                            'type' => 9,
                            'oid'=>'后台退保证金',
                            'ctime' => time(),
                            'remark' => '返回保证金',
                        );
                        $rflow = $mlog->add($data_mlog);
                    }


                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '未获取会员ID！';
            }
            $this->ajaxReturn($info);
        }

        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        } else {
            $info['status'] = 0;
            $info['msg'] = '未获取会员ID！';
            $this->ajaxReturn($info);
        }
        $this->assign('shopset', self::$SHOP['set']);
        $this->display();
    }

    //CMS后台商品设置
    public function vipFxtj()
    {
    	//是否开启分销
    	if (!self::$SHOP['set']['isfx']) {
    		exit;
    	}
        header("Content-type: text/html; charset=utf-8");
        $id = I('id');
        $mvip = M('Vip');
        //dump($m);
        //设置面包导航，主加载器请配置
        //		$bread=array(
        //			'0'=>array(
        //				'name'=>'会员中心',
        //				'url'=>U('Admin/Vip/#')
        //			),
        //			'1'=>array(
        //				'name'=>'会员列表',
        //				'url'=>U('Admin/Vip/vipList')
        //			),
        //			'1'=>array(
        //				'name'=>'会员编辑',
        //				'url'=>U('Admin/Vip/vipSet',array('id'=>$id))
        //			)
        //		);
        //		$this->assign('breadhtml',$this->getBread($bread));

        $vip = $mvip->where('id=' . $id)->find();
        if (!$vip) {
            $this->die('不存在此用户！');
        }
        echo '会员分销统计预估开始：<br><br>';
        echo '<br><br>*********************************************<br><br>';
        echo '会员名：' . $vip['nickname'] . '<br>';
        echo '会员层级：' . $vip['plv'] . '<br>';
        echo '会员路由：' . $vip['path'] . '<br>';
        echo '会员余额：' . $vip['money'] . '<br>';
        echo '<br><br>*********************************************<br><br>';
        echo '第一步：取出3层下线所有用户<br><br>';
        $maxlv = $vip['plv'] + 3;
        $likepath = $vip['path'] . '-' . $vip['id'];
        echo '层级条件：最大层级不超过' . $maxlv . '<br>';
        echo '路由条件：' . $likepath . '<br>';
        //两次模糊查询
        //1:取出第一层，2:取出其他层
        $firstlv = $vip['plv'] + 1;
        $firstpath = $likepath;
        $mapfirst['plv'] = $firstlv;
        $mapfirst['path'] = $firstpath;
        $firstsub = $mvip->field('id,plv,path,nickname')->where($mapfirst)->select();
        if ($firstsub) {
            //模糊查询第二层和第三层
            $maplike['plv'] = array('gt', $firstlv);
            $maplike['plv'] = array('elt', $maxlv);
            $maplike['path'] = array('like', $likepath . '-%');
            $sesendsub = $mvip->field('id,plv,path,nickname')->where($maplike)->select();
            //dump($firstsub);
            //dump($sesendsub);
            //合并两个数组
            if ($sesendsub) {
                $sub = array_merge($firstsub, $sesendsub);
            } else {
                $sub = $firstsub;
            }
            echo '3层下线总数：' . count($sub) . ' 人<br>';
            echo '列出所有下线会员：<br>';
            dump($sub);
            echo '将下线会员按照层级与会员ID重新整理：<br>';
            $subarr = array();
            foreach ($sub as $v) {
                //按层级分组
                $subarr[$v['plv']] = $subarr[$v['plv']] . $v['id'] . ',';
                //array_push($subarr[$v['plv']],$v['id']);
            }
            dump($subarr);
            echo '再次整理下线分层数组：<br>';
            $subarr = array_values($subarr);
            dump($subarr);
            echo '<br><br>*********************************************<br><br>';
            echo '第二步：取出系统佣金比例设置<br><br>';
            $shopset = M('Shop_set')->find();
            $morder = M('Shop_order');
            $fx1rate = $shopset['fx1rate'];
            $fx2rate = $shopset['fx2rate'];
            $fx3rate = $shopset['fx3rate'];
            echo '第一层分销比例：' . $fx1rate . '%<br>';
            echo '第二层分销比例：' . $fx2rate . '%<br>';
            echo '第三层分销比例：' . $fx3rate . '%<br>';
            echo '<br><br>*********************************************<br><br>';
            echo '第三步：逐级分析算出分销佣金<br><br>';
            if ($fx1rate && $subarr[0]) {
                $tmprate = $fx1rate;
                $tmplv = $data['plv'] + 1;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[0]));
                echo '第一层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[0] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第一层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第一层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx1total = $tmptotal * ($tmprate / 100);
                echo '第一层会员所有订单应贡献佣金[公式=支付总额*(第一层分销率/100)]：' . $fx1total . '元<br>';
                echo '第一层统计结束。<br><br>';
            } else {
                $fx1total = 0;
                echo '不存在第一层会员，该层分销佣金为0。<br><br>';
            }
            if ($fx2rate && $subarr[1]) {
                $tmprate = $fx2rate;
                $tmplv = $data['plv'] + 2;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[1]));
                echo '第二层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[1] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第二层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第二层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx2total = $tmptotal * ($tmprate / 100);
                echo '第二层会员所有订单应贡献佣金[公式=支付总额*(第二层分销率/100)]：' . $fx2total . '元<br>';
                echo '第二层统计结束。<br><br>';
            } else {
                $fx2total = 0;
                echo '不存在第二层会员，该层分销佣金为0。<br><br>';
            }
            if ($fx3rate && $subarr[2]) {
                $tmprate = $fx3rate;
                $tmplv = $data['plv'] + 3;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[2]));
                echo '第三层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[2] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第三层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第三层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx3total = $tmptotal * ($tmprate / 100);
                echo '第三层会员所有订单应贡献佣金[公式=支付总额*(第三层分销率/100)]：' . $fx3total . '元<br>';
                echo '第三层统计结束。<br><br>';
            } else {
                $fx3total = 0;
                echo '不存在第三层会员，该层分销佣金为0。<br><br>';
            }
            $totalfxmoney = number_format(($fx1total + $fx2total + $fx3total), 2);
            echo '当前会员的代收佣金预估值为[公式=第一层贡献佣金+第二层贡献佣金+第三层贡献佣金，保留2位小数格式化处理]：' . $totalfxmoney . '<br><br>';
            echo '**********************本次分析结束！*****************';

        } else {
            echo '此会员没有下线成员，代收佣金为0，直接结束统计分析！';
        }

    }

    public function vipExport()
    {
        $id = I('id');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        }

        $data = M('Vip')->where($map)->select();
        //是否开启分销
        if (self::$SHOP['set']['isfx']) {
        	  foreach ($data as $k => $v) {
	            // unset($data[$k]['id']);
	            // unset($data[$k]['mobile']);
	            // unset($data[$k]['idno']);
	            // unset($data[$k]['name']);
	            // unset($data[$k]['money']);
	            // unset($data[$k]['score']);
	            // unset($data[$k]['nickname']);
	            // unset($data[$k]['province']);
	            // unset($data[$k]['city']);
	            // unset($data[$k]['signtime']);

                $mydata[$k]['id']=$data[$k]['id'];
                $mydata[$k]['mobile']=$data[$k]['mobile'];
                $mydata[$k]['idno']=$data[$k]['idno'];
                $mydata[$k]['name']=$data[$k]['name'];
                $mydata[$k]['money']=$data[$k]['money'];
                $mydata[$k]['score']=$data[$k]['score'];
                $mydata[$k]['nickname']=$data[$k]['nickname'];
                $mydata[$k]['province']=$data[$k]['province'];
                $mydata[$k]['city']=$data[$k]['city'];
               $mydata[$k]['ctime'] = $data[$k]['ctime'] ? date('Y-m-d H:i:s', $data[$k]['ctime']) : '无';
            



	    
	            // $data[$k]['ctime'] = $v['ctime'] ? date('Y-m-d H:i:s', $v['ctime']) : '无';
	            // $data[$k]['cctime'] = $v['cctime'] ? date('Y-m-d H:i:s', $v['cctime']) : '无';
	        }
        	$title = array(
                '会员ID', 
                '手机号码', 
                '身份证号', 
                '真实姓名', 
                '账户余额', 
                '积分', 
                '昵称', 
                '省份', 
                '城市', 
                '关注时间',
            
                );


        } else {
        	foreach ($data as $k => $v) {
                $mydata[$k]['id']=$data[$k]['id'];
                $mydata[$k]['mobile']=$data[$k]['mobile'];
                $mydata[$k]['idno']=$data[$k]['idno'];
                $mydata[$k]['name']=$data[$k]['name'];
                $mydata[$k]['money']=$data[$k]['money'];
                $mydata[$k]['score']=$data[$k]['score'];
                $mydata[$k]['nickname']=$data[$k]['nickname'];
                $mydata[$k]['province']=$data[$k]['province'];
                $mydata[$k]['city']=$data[$k]['city'];
               $mydata[$k]['ctime'] = $data[$k]['ctime'] ? date('Y-m-d H:i:s', $data[$k]['ctime']) : '无';
        		// unset($data[$k]['pid']);
        		// unset($data[$k]['plv']);
        		// unset($data[$k]['path']);
        		// unset($data[$k]['password']);
        		// unset($data[$k]['cur_exp']);
        		// unset($data[$k]['levelid']);
        		// unset($data[$k]['language']);
        		// unset($data[$k]['headimgurl']);
        		// unset($data[$k]['status']);
        		// unset($data[$k]['sign']);
        		// unset($data[$k]['signtime']);
        		// unset($data[$k]['isfx']);
        		// unset($data[$k]['total_buy']);
        		// unset($data[$k]['total_yj']);
        		// unset($data[$k]['total_xxyj']);
        		// unset($data[$k]['total_xxlink']);
        		// unset($data[$k]['total_xxsub']);
        		// unset($data[$k]['total_xxunsub']);
        		// unset($data[$k]['total_xxbuy']);
        		// unset($data[$k]['txname']);
        		// unset($data[$k]['txmobile']);
        		// unset($data[$k]['txyh']);
        		// unset($data[$k]['txfh']);
        		// unset($data[$k]['txszd']);
        		// unset($data[$k]['txcard']);
        		// unset($data[$k]['employee']);
        		// unset($data[$k]['ticket']);
        		// unset($data[$k]['continue_sign']);
        		// $data[$k]['ctime'] = $v['ctime'] ? date('Y-m-d H:i:s', $v['ctime']) : '无';
        		// $data[$k]['cctime'] = $v['cctime'] ? date('Y-m-d H:i:s', $v['cctime']) : '无';
        	}
        	// $title = array('会员ID', '真实电话', '真实姓名', 'E-mail', '金钱', '积分', '经验', 'openid', '微信昵称', '性别', '城市', '省份', '国家', '关注情况', '关注时间', '备注','创建时间', '交互时间', '是否正常');
            $title = array(
                '会员ID', 
                '手机号码', 
                '身份证号', 
                '真实姓名', 
                '账户余额', 
                '积分', 
                '昵称', 
                '省份', 
                '城市', 
                '关注时间',
            
                ); 
        }
      // var_dump($data);exit;
        $this->exportexcel($mydata, $title, '会员数据' . date('Y-m-d H:i:s', time()));
    }

    public function message()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '消息管理',
                'url' => U('Admin/Vip/message'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('vip_message');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['title'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id desc')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '消息管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function messageSet()
    {
    		$id = I('id');
    		$m = M('vip_message');
    		//设置面包导航，主加载器请配置
    		$bread = array(
    				'0' => array(
    						'name' => '会员中心',
    						'url' => U('Admin/Vip/#'),
    				),
    				'1' => array(
    						'name' => '消息管理',
    						'url' => U('Admin/Vip/message'),
    				),
    				'2' => array(
    						'name' => '消息设置',
    						'url' => $id ? U('Admin/Vip/messageSet', array('id' => $id)) : U('Admin/Vip/messageSet'),
    				),
    		);
    		$this->assign('breadhtml', $this->getBread($bread));
    		//处理POST提交
    		if (IS_POST) {
    			$data = I('post.');
    			$data['ctime'] = time();
    			if ($id) {
    				unset($data['pids']);
    				$re = $m->save($data);
    				if (FALSE !== $re) {
    					$info['status'] = 1;
    					$info['msg'] = '设置成功！';
    				} else {
    					$info['status'] = 0;
    					$info['msg'] = '设置失败！';
    				}
    			} else {
    				if($data['pids']) {
    					$pids = explode(',', $data['pids']);
    					foreach($pids as $k=>$v) {
    						if($v) {
    							$data['pids'] = $v;
    							$re = $m->add($data);
    						}
    					}
    				}
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
    		//处理编辑界面
    		if ($id) {
    			$cache = $m->where('id=' . $id)->find();
    			$this->assign('cache', $cache);
    		}
    		if (I('pids')) {
    			$cache['pids'] = I('pids');
    			$this->assign('cache', $cache);
    		}
    		$this->display();
    }

    public function mailSet()
    {
        $pids = I('pids');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/viplist'),
            ),
            '2' => array(
                'name' => '发送邮件',
                'url' => U('Admin/Vip/messageSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $m = M('vip');
            $data = I('post.');
            $id_arr = explode(',', $data['pids']);
            foreach ($id_arr as $k => $v) {
                $mail_addr = $m->where('id=' . $v)->getField('email');
                if ($mail_addr != '') {
                    think_send_mail($mail_addr, '系统会员', $data['title'], $data['content']);
                }
            }

            $info['status'] = 1;
            $info['msg'] = ' 发送成功！';

            $this->ajaxReturn($info);
        }
        $this->assign('pids', $pids);
        $this->display();
    }

    public function messageDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip_message');
        if (!id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->delete($id);
        if ($re) {
            //删除消息浏览记录
            M('vip_log')->where('type=5 and opid in (' . $id . ')')->delete();
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

    public function card()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '卡券列表',
                'url' => U('Admin/Vip/card'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $this->assign('status', $status);
        $m = M('vip_card');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['cardno'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $type = I('type');
        if ($type) {
            $map['type'] = $type;
            $this->assign('type', $type);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id desc')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '卡券列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function cardSet()
    {
        $m = M('vip_card');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '卡券列表',
                'url' => U('Admin/Vip/card'),
            ),
            '2' => array(
                'name' => '新增卡券',
                'url' => U('Admin/Vip/cardSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $data['ctime'] = time();
            if ($data['usetime'] != '') {
                $timeArr = explode(" - ", $data['usetime']);
                $data['stime'] = strtotime($timeArr[0]);
                $data['etime'] = strtotime($timeArr[1]);
            }
            $num = $data['num'];
            unset($data['usetime']);
            unset($data['num']);
            for ($i = 0; $i < $num; $i++) {
                $cardnopwd = $this->getCardNoPwd();
                $data['cardno'] = $cardnopwd['no'];
                $data['cardpwd'] = $cardnopwd['pwd'];
                $r = $m->add($data);
            }
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        } else {
            $this->display();
        }

    }

    public function cardDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip_card');
        if (!id) {
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

    private function getCardNoPwd()
    {
        $dict_no = "0123456789";
        $length_no = 10;
        $dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length_pwd = 10;
        $card['no'] = "";
        $card['pwd'] = "";
        for ($i = 0; $i < $length_no; $i++) {
            $card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
        }
        for ($i = 0; $i < $length_pwd; $i++) {
            $card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
        }
        return $card;
    }

    public function sendCard()
    {
        $post = I('post.');
        $m = M('vip_card');
        if ($post['vipid'] == '') {
            $info['status'] = 0;
            $info['msg'] = '请输入发送会员ID！';
            $this->ajaxReturn($info);
        }
        if (!M('vip')->where('id=' . $post['vipid'])->find()) {
            $info['status'] = 0;
            $info['msg'] = '该会员不存在！';
            $this->ajaxReturn($info);
        }
        $data['vipid'] = $post['vipid'];
        $data['status'] = 1;
        $re = $m->where('id=' . $post['cardid'])->save($data);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '发送成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '发送失败!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台会员等级列表
    public function level()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '分组列表',
                'url' => U('Admin/Vip/level'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Vip_level');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('exp')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '分组列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台会员等级设置
    public function levelSet()
    {
        $id = I('id');
        $m = M('vip_level');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '分组列表',
                'url' => U('Admin/Vip/level'),
            ),
            '2' => array(
                'name' => '分组设置',
                'url' => $id ? U('Admin/Vip/levelSet', array('id' => $id)) : U('Admin/Vip/levelSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $re = $id ? $m->save($data) : $m->add($data);
            if (FALSE !== $re) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        } else {
            if ($id) {
                $cache = $m->where('id=' . $id)->find();
                $this->assign('cache', $cache);
            }
            $this->display();
        }
    }

    public function levelDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Vip_level');
        if (!id) {
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

    public function cardExport()
    {
        $id = I('id');
        $type = I('type');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        } else {
            $map['type'] = $type;
        }
        $data = M('vip_card')->where($map)->field('id,type,cardno,cardpwd,status')->select();
        foreach ($data as $k => $v) {
            switch ($v['type']) {
                case 1:
                    $data[$k]['type'] = "充值卡";
                    break;
                case 2:
                    $data[$k]['type'] = "代金券";
                    break;
            }
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "可制作";
                    break;
                case 1:
                    $data[$k]['status'] = "已发放";
                    break;
                case 2:
                    $data[$k]['status'] = "已使用";
                    break;
            }
        }
        $title = array('id', '类型', '卡号', '卡密', '状态');
        $this->exportexcel($data, $title, '卡券数据');
    }

    //CMS后台Vip提现订单
    public function txorder()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '提现订单',
                'url' => U('Admin/Vip/txorder'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $status = I('status');
       
        if ($status || $status == '0') {
            $map['status'] = $status;
            
        }
        $this->assign('status', $status);

        //绑定搜索条件与分页
        $m = M('Vip_tx');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //提现人姓名
            $map['txname'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('id DESC')->select();
        foreach ($cache as $k=>$v){
            $cache[$k]["vip"]=M("vip")->where(array("id"=>$v["vipid"]))->find();
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员提现订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function txorderOk()
    {

        $options['appid'] = self::$SYS['set']['wxappid'];
        $options['appsecret'] = self::$SYS['set']['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);

        $data = I('post.');
        if(!$data['id']) {
        	$this->error('参数错误！');
        }
        if(!$data['sno']) {
        	$this->error('请填写转账流水号！');
        }
        $m = M('Vip_tx');
        $old = $m->where(array('id'=>$data['id']))->find();
        if(!$old) {
        	$this->error('提现项目不存在！');
        }
        if($old['status'] == 0) {
        	$this->error('该项目已取消提现，不能提现！');
        }
        if($old['status'] == 2) {
        	$this->error('该项目已提现，不能提现！');
        }
        $mlog = M('Vip_message');
        $mvip = M('Vip');

        $err = TRUE;
        $old['sno'] = $data['sno'];
        $old['status'] = 2;
        $old['txtime'] = time();
        $rv = $m->save($old);
        if ($rv !== FALSE) {
              $data_msg['pids'] = $old['vipid'];
              
              if($old['txtype'] == 0)
                $data_msg['title'] = "亲爱的用户，提现已完成！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功发放到您的银行卡提现帐户里面了！";
              
              if($old['txtype'] == 1)
                $data_msg['title'] = "亲爱的用户，提现已完成！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功发放到您的微信零钱里面了！";

              $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现完成时间：" . date('Y-m-d H:i', $old['txtime']) . "<br><br>转账流水号：".$old['sno']." <br><br>您的提现申请已完成，如有异常请联系客服！";
              $data_msg['ctime'] = time();

              // 发送信息===============
              $customer = M('Wx_customer')->where(array('type' => 'tx2'))->find();
              $vip = $mvip->where(array('id' => $old['vipid']))->find();
              $msg = array();
              $msg['touser'] = $vip['openid'];
              $msg['msgtype'] = 'text';
              $str = $customer['value'];
              $msg['text'] = array('content' => $str);
              $ree = $wx->sendCustomMessage($msg);
              // 发送消息完成============

             $rmsg = $mlog->add($data_msg);
         } else {
              $err = FALSE;             
        }
        if ($err) {
            $info['status'] = 1;
            $info['msg'] = '提现完成成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '提现完成失败，请刷新后重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    public function txorderCancel()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Vip_tx');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $old = $m->where('id=' . $id)->find();
        if (!$old) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取提现订单数据！';
            $this->ajaxReturn($info);
        }
        if ($old['status'] != 1) {
            $info['status'] = 0;
            $info['msg'] = '只可以操作新申请订单！';
            $this->ajaxReturn($info);
        }
        $vip = $mvip->where('id=' . $old['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取相关会员信息！';
            $this->ajaxReturn($info);
        }
        $rold = $m->where('id=' . $id)->setField('status', 0);
        if ($rold !== FALSE) {
            $rvip = $mvip->where('id=' . $old['vipid'])->setInc('money', $old['txprice']);
            if ($rvip) {
            	//资金流水记录
            	$mlog = M('Vip_log_money');
            	$flow['vipid'] = $vip['id'];
            	$flow['openid'] = $vip['openid'];
            	$flow['nickname'] = $vip['nickname'];
            	$flow['mobile'] = $vip['mobile'];
            	$flow['money'] = $old['txprice'];
            	$flow['paytype'] = '';
            	$flow['balance'] = $vip['money'] + $old['txprice'];
            	$flow['type'] = 2;
            	$flow['oid'] = '提现编号：'.$old['id'];
            	$flow['ctime'] = time();
            	$flow['remark'] = '用户申请银行卡提现未通过审核，提现金额退回到用户账号余额';
            	$rflow = $mlog->add($flow);
            	
                $data_msg['pids'] = $vip['id'];
                $data_msg['title'] = "提现申请未通过审核！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功退回您的帐户余额！";
                $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现退回时间：" . date('Y-m-d H:i', time()) . "<br><br>您的提现申请未通过审核，如有疑问请联系客服！";
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                $info['status'] = 1;
                $info['msg'] = '取消提现申请成功！提现' . self::$SHOP['set']['yjname'] . '已自动退回用户帐户余额！';

                // 发送信息===============
                $customer = M('Wx_customer')->where(array('type' => 'tx3'))->find();
                $options['appid'] = self::$SYS['set']['wxappid'];
                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
                $wx = new \Util\Wx\Wechat($options);
                $msg = array();
                $msg['touser'] = $vip['openid'];
                $msg['msgtype'] = 'text';
                $str = $customer['value'];
                $msg['text'] = array('content' => $str);
                $ree = $wx->sendCustomMessage($msg);
                // 发送消息完成============

                $this->ajaxReturn($info);
            } else {
                $info['status'] = 0;
                $info['msg'] = '取消成功，但自动退还' . self::$SHOP['set']['yjname'] . '至用户余额失败，请联系此会员！';
                $this->ajaxReturn($info);
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败，请重新尝试！';
            $this->ajaxReturn($info);
        }
    }

    public function txorderExport()
    {
        $id = I('id');
        $status = I('status');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        } else {
            $map['status'] = $status;
        }
        switch ($status) {
            case 0:
                $tt = "提现失败";
                break;
            case 1:
                $tt = "新申请";
                break;
            case 2:
                $tt = "提现完成";
                break;
        }
        $data = M('Vip_tx')->where($map)->select();
        foreach ($data as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "提现失败";
                    break;
                case 1:
                    $data[$k]['status'] = "新申请";
                    break;
                case 2:
                    $data[$k]['status'] = "提现完成";
                    break;
            }
            $data[$k]['txsqtime'] = date('Y-m-d H:i:s', $v['txsqtime']);
            $data[$k]['txtime'] = $v['txtime'] ? date('Y-m-d H:i:s', $v['txtime']) : '未执行';
        }
        $title = array('ID', '会员ID', '提现金额', '提现姓名', '提现电话', '提现银行', '提现分行', '提现银行所在地', '提现银行卡卡号', '提现申请时间', '提现完成时间', '订单状态');
        $this->exportexcel($data, $title, $tt . '订单' . date('Y-m-d H:i:s', time()));
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
    public function getIndex()
    {
    	//查找带回字段
    	$fbid = I('fbid');
    	$isall = I('isall');
    	$this->assign('fbid', $fbid);
    	$this->assign('isall', $isall);
    	$page = '1,8';
    	$m = M('Vip');
    	$cache = $m->page($page)->order('id desc')->select();
    	$this->assign('cache', $cache);
    	$this->ajaxReturn($this->fetch());
    }
    
    public function getMore()
    {
    	$page = I('p') . ',8';
    	$m = M('Vip');
    	$search = I('search') ? I('search') : '';
    	$map = array();
    	if ($search) {
    		$map['nickname|mobile'] = array('like', "%$search%");
    	}
    	$cache = $m->where($map)->page($page)->order('id desc')->select();
    	if ($cache) {
    		$this->assign('cache', $cache);
    		$this->ajaxReturn($this->fetch());//封装模板fetch并返回
    	} else {
    		$this->ajaxReturn("");
    	}   
    }
    
    //上传提现凭证
    public function txVoucher()
    {
    	$map['id'] = I('id');
    	$cache = M('Vip_tx')->where($map)->find();
    	$this->assign('cache', $cache);
    	$mb = $this->fetch();
    	$this->ajaxReturn($mb);
    }

    //微信提现凭证
    public function txWeui(){

        $info = [
                    'code' => 0,
                    'msg' => '提现失败',
                ];
        if(IS_AJAX){
            $map['id'] = I('post.id');
            $map['txtype'] = 1;
            $m = M('Vip_tx');
            $cache = $m->where($map)->find();
            $money = $cache['txprice'] * 100;
            $mchPay = new \Util\Wx\WxMchPay($money,$cache['openid'],$cache['sno']);
            $response = $mchPay->payExec();
            $response = json_decode($response);

            if($response->return_code == 'SUCCESS' && $response->result_code == 'SUCCESS'){

                $data = [
                        'status' => 2,
                        'txtime' => strtotime($response->payment_time),
                    ];
                $bool = $m->where($map)->save($data);
                if($bool !== false){
                    $info = [
                            'code' => 1,
                            'msg' => '提现完成',
                        ];
                    
                }   
            }
        
        }
        $this->ajaxReturn($info);
    }

    // VIP实名验证审核列表
    public function vipAuth()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
                '0' => array(
                        'name' => '会员中心',
                        'url' => U('Admin/Vip/#'),
                ),
                '1' => array(
                        'name' => '实名审核',
                        'url' => U('Admin/Vip/vipAuth'),
                ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        $plv = I('plv') ? I('plv') : 0;
        if ($search) {
            $map['name|nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $map['is_auth'] = 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '实名审核列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //会员实名验证通过审核
    public function realNameAudit()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $vip = $m->where('id='.$id)->find();
        if(!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员不存在!';
            $this->ajaxReturn($info);
        }
        if($vip['is_auth'] == 0) {
            $info['status'] = 0;
            $info['msg'] = '该会员未提交实名认证!';
            $this->ajaxReturn($info);
        }
        if($vip['is_auth'] == 2) {
            $info['status'] = 0;
            $info['msg'] = '该会员已实名认证，无需再认证!';
            $this->ajaxReturn($info);
        }
        //0:未实名认证 1：待审核 2：已认证
        $data["is_auth"] = 2;
        $data["auth_time"] = time();
        $re = $m->where(array('id'=>$id))->save($data);
        if ($re) {
            //需要记录日志
            $info['status'] = 1;
            $info['msg'] = '认证成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '认证失败!';
        }
        $this->ajaxReturn($info);
    }
    //会员实名验证通过审核
    public function realNameAuditBatch()
    {
        $id = $_GET['id'];
        $m = M('vip');
        if ($id) {
            $map ['id'] = array (
                    'in',
                    in_parse_str ( $id )
            );
        } else {
            exit('参数错误！' );
        }
        $map['is_auth'] = 1;
        $viplist = $m->where($map)->select();
        $count = 0;
        foreach($viplist as $k => $v) {
            $data['is_auth'] = 2;
            $data['auth_time'] = time();
            $re = $m->where('id='.$v['id'])->save($data);
            if(FALSE !== $re) {
                $count++;
            }
        }
        if($count>0) {
            $info ['status'] = 1;
            $info ['msg'] = '成功认证'.$count.'位会员！';
        } else {
            $info ['status'] = 0;
            $info ['msg'] = '认证失败！';
        }
        $this->ajaxReturn($info);
    }
    public function vipCheck()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => 'VIP会员审核',
                'url' => U('Admin/Vip/vipCheck'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('vip_log');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        $plv = I('plv') ? I('plv') : 0;
        if ($search) {
            $map['vipid|nickname'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $map['type'] =13;
        $map["status"]=array("GT",1);
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();

        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', 'VIP会员审核列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }
    /**
     * 通过会员申请审核
     * author: feng
     * create: 2017/8/29 18:59
     */
    public function passCheck()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip_log');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $viplog = $m->where('id='.$id)->find();
        if(!$viplog) {
            $info['status'] = 0;
            $info['msg'] = '记录不存在!';
            $this->ajaxReturn($info);
        }
        if($viplog['status'] !=2) {
            $info['status'] = 0;
            $info['msg'] = '该记录已处理过!';
            $this->ajaxReturn($info);
        }
        $data["status"] = 3;
        $data["check_time"] = time();
        $data["fx_time"] = time();
        $re = $m->where(array('id'=>$id))->save($data);
        if ($re!==false) {
            $upgradeData['groupid'] = 2;
            $upgradeData['vtime'] = time();
            $upgradeData['vip_expiration_time'] = strtotime('+'.$viplog["code"].'months',time());
            $re = M("vip")->where('id='.$viplog["vipid"])->save($upgradeData);
            if($re!==false){
                $info["status"]=1;
                $info['msg'] = '操作成功!';
            }else{
                $info["status"]=0;
                $info['msg'] = '会员升级失败，请到会员列表中手动升级!';
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败!';
        }
        $this->ajaxReturn($info);
    }
    /**
     * 不通过审核，并将钱退到余额
     * author: feng
     * create: 2017/8/29 19:07
     */
    public function noPassCheck()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip_log');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $viplog = $m->where('id='.$id)->find();
        if(!$viplog) {
            $info['status'] = 0;
            $info['msg'] = '记录不存在!';
            $this->ajaxReturn($info);
        }
        if($viplog['status'] !=2) {
            $info['status'] = 0;
            $info['msg'] = '该记录已处理过!';
            $this->ajaxReturn($info);
        }
        $data["status"] = 4;//
        $data["check_time"] = time();
        $data["fx_time"] = time();
        $re = $m->where(array('id'=>$id))->save($data);
        if ($re!==false) {
            $vipMap['id'] = $viplog["vipid"];
            $re = M("vip")->where($vipMap)->setInc('money', $viplog["money"]);
            if (false !== $re) {
                $vip=M('vip')->where($vipMap)->find();
                //资金流水记录
                $mlog = M('Vip_log_money');
                $flow['vipid'] =  $viplog["vipid"];
                $flow['openid'] = $vip['openid'];
                $flow['nickname'] = $vip['nickname'];
                $flow['mobile'] = $vip['mobile'];
                $flow['money'] = $viplog["money"];
                $flow['paytype'] = 'money';
                $flow['balance'] = $vip['money'] + $viplog["money"];
                $flow['type'] = 14;
                $flow['ctime'] = time();
                $flow['remark'] = '申请会员VIP退款';
                $rflow = $mlog->add($flow);
                $info["status"]=1;
                $info['msg'] = '操作成功!';

            }else{
                $info["status"]=0;
                $info['msg'] = '已审核不通过，但退款失败，请手动添加退款到余额!';
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败!';
        }
        $this->ajaxReturn($info);
    }
    // 分销商申请审核列表
    public function fxAuth()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
                '0' => array(
                        'name' => '会员中心',
                        'url' => U('Admin/Vip/#'),
                ),
                '1' => array(
                        'name' => '分销商申请审核',
                        'url' => U('Admin/Vip/fxAuth'),
                ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        $plv = I('plv') ? I('plv') : 0;
        if ($search) {
            $map['name|nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $map['apply_fx_status'] = 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '分销商申请审核列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }
    //分销商申请通过审核
    public function fxAudit()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $vip = $m->where('id='.$id)->find();
        if(!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员不存在!';
            $this->ajaxReturn($info);
        }
        if($vip['is_auth'] == 0) {
            $info['status'] = 0;
            $info['msg'] = '该会员未提交分销商申请!';
            $this->ajaxReturn($info);
        }
        if($vip['isfx'] || $vip['apply_fx_status'] == 2) {
            $info['status'] = 0;
            $info['msg'] = '该会员已是分销商，无需再通过!';
            $this->ajaxReturn($info);
        }
        //0:未申请 1：待审核 2：已通过
        $data["isfx"] = 1;
        $data["apply_fx_status"] = 2;
        $data["fx_time"] = time();
        $re = $m->where(array('id'=>$id))->save($data);
        if ($re) {
            //消息通知
            $data_msg['pids'] = $vip['id'];
            $data_msg['title'] = "申请分销商审核状态更新";
            $data_msg['content'] = "恭喜您，您的分销商申请已经通过！感谢您的支持！";
            $data_msg['ctime'] = time();
            $rmsg = M('Vip_message')->add($data_msg);
            //需要记录日志
            $info['status'] = 1;
            $info['msg'] = '审核成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '审核失败!';
        }
        $this->ajaxReturn($info);
    }
    //分销商申请批量通过审核
    public function fxAuditBatch()
    {
        $id = $_GET['id'];
        $m = M('vip');
        if ($id) {
            $map ['id'] = array (
                    'in',
                    in_parse_str ( $id )
            );
        } else {
            exit('参数错误！' );
        }
        $map['apply_fx_status'] = 1;
        $viplist = $m->where($map)->select();
        $count = 0;
        foreach($viplist as $k => $v) {
            $data["isfx"] = 1;
            $data["apply_fx_status"] = 2;
            $data["fx_time"] = time();
            $re = $m->where('id='.$v['id'])->save($data);
            if(FALSE !== $re) {
                //消息通知
                $data_msg['pids'] = $v['id'];
                $data_msg['title'] = "申请分销商审核状态更新";
                $data_msg['content'] = "恭喜您，您的分销商申请已经通过！感谢您的支持！";
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                $count++;
            }
        }
        if($count>0) {
            $info ['status'] = 1;
            $info ['msg'] = '成功审核'.$count.'位会员！';
        } else {
            $info ['status'] = 0;
            $info ['msg'] = '审核失败！';
        }
        $this->ajaxReturn($info);
    }
    //拒绝分销商申请
    public function fxNoAudit()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $vip = $m->where('id='.$id)->find();
        if(!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员不存在!';
            $this->ajaxReturn($info);
        }
        if($vip['is_auth'] == 0) {
            $info['status'] = 0;
            $info['msg'] = '该会员未提交分销商申请!';
            $this->ajaxReturn($info);
        }
        if($vip['isfx'] || $vip['apply_fx_status'] == 2) {
            $info['status'] = 0;
            $info['msg'] = '该会员已是分销商，不能拒绝!';
            $this->ajaxReturn($info);
        }
        $data["apply_fx_status"] = 0;
        $re = $m->where(array('id'=>$id))->save($data);
        if ($re) {
            //消息通知
            $data_msg['pids'] = $vip['id'];
            $data_msg['title'] = "申请分销商审核状态更新";
            $data_msg['content'] = "我们很抱歉地告诉您，您的分销商申请未通过！如有疑问请咨询客服，感谢您的支持！";
            $data_msg['ctime'] = time();
            $rmsg = M('Vip_message')->add($data_msg);
            //需要记录日志
            $info['status'] = 1;
            $info['msg'] = '操作成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败!';
        }
        $this->ajaxReturn($info);
    }


    //查询会员升级列表
    public function upVipList(){

        //绑定搜索和分页
        $p = I('p',1);       
        
        if ($search = I('search','')) 
            $where['nickname|mobile'] = array('like', "%$search%");
        
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $vip_rule = M('VipSet')->where('id=1')->getField('vip_rule');

        $vip_rule = explode(',', $vip_rule);

        $vipRule = array();
        foreach($vip_rule as $v){
            $arr = explode(':', $v);
            $vipRule[$arr[0]] = $arr[1];
        }

        $m =  M('Vip');
        $where['vip_apply_status'] = 1;
        $vipItems = $m->where($where)->page($p, $psize)->order('vip_apply_time desc')->select();
        $count = $m->where($where)->count();
        
        $this->getPage($count, $psize, 'App-loader', '会员申请列表', 'App-search');
        $this->assign(array(
                    'vipRule' => $vipRule,
                    'vipItems' => $vipItems,
                    'search' => $search,
                ));
        $this->display();
    }


    //会员等级升级
    public function ajaxUpVipLv(){
        $info = array(
                'code' => 0,
                'msg' => '通讯失败，请重新发送！',
            );

        //判断是否ajax
        if(IS_AJAX){
          
            //判断用户ID存在
            $where['id'] = I('post.id','','intval');
            if( !$where['id'] ){
                $info['msg'] = '参数错误！';
                $this->ajaxReturn($info);    
            }

            //查询用户级别，申请状态，申请类型
            $m = M('Vip');
            $field = array( 'groupid', 'vip_apply_status' ,'vip_apply_type');
            $vipItem = $m->field($field)->where($where)->find();
            

            if(!$vipItem){
                $info['msg'] = '该会员不存在！';
                $this->ajaxReturn($info); 
            }

            if($vipItem['groupid'] == 2){
                $info['msg'] = '该会员已经是vip,无需升级';
                $this->ajaxReturn($info);
            }

            if($vipItem['vip_apply_status'] !=1 ){
                $info['msg'] = '该会员并没有申请会员';
                $this->ajaxReturn($info);
            }

            $type = $vipItem['vip_apply_type'];
            if( $type == 0){
                $info['msg'] = '该会员并没有选择升级时间，无法升级';
                $this->ajaxReturn($info);
            }

            $data = array(
                    'groupid' => 2,
                    'vip_apply_status' => 2,
                    'vtime' => time(),
                    'vip_expiration_time' => strtotime('+'.$type.'years ',time()),
                );
            $bool = $m->where($where)->save($data);
            if($bool !== false){
                $info = array(
                        'code' => 1,
                        'msg' => '会员升级成功！',
                    );
                $this->ajaxReturn($info);
            }

        }
        $this->ajaxReturn($info);
    }


    public function ajaxDelVipApply(){
        $info = array(
                'code' => 0,
                'msg' => '通讯失败，请重新发送！',
            );

        if(IS_AJAX){
            
            // I am not responsible of this code.
            // They made me write it, against my will.
            
            //判断用户ID存在
            $where['id'] = I('post.id','','intval');
            if( !$where['id'] ){
                $info['msg'] = '参数错误！';
                $this->ajaxReturn($info);    
            }
            $m = M('Vip');
            $vipItem = $m->where($where)->find();
            
            if(!$vipItem){
                $info['msg'] = '该会员不存在！';
                $this->ajaxReturn($info);
            }

            if($vipItem['groupid'] == 2){
                $info['msg'] = '该会员已经是vip,无法删除';
                $this->ajaxReturn($info);
            }

            $m->startTrans();

            $m_log = M('VipLog');
            /*$log_where = array('vipid'=> $vipItem['id'], 'event'=>'会员购买VIP', 'type'=>13);


            $money = $m_log->where($log_where)->order('id desc')->getField('money');*/

            $data_log = array(
                    'ip' => get_client_ip(),
                    'vipid' => $vipItem['id'],
                    'openid' => $vipItem['openid'],
                    'nickname' => $vipItem['nickname'],
                    'ctime' => time(),
                    'event' => "取消会员购买VIP",

                    'opid' => date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8),
                    'code' => $vipItem['type'],
                    'money' => $vipItem["vip_apply_money"],
                    'status' => 1,
                    'type' => 9,
                );

            $logid = M('vip_log')->add($data_log);

            if(!$logid){
                $m->rollback();
                $info['msg'] = '操作失败，请重新尝试！';
                $this->ajaxReturn($info);
            }


            $mlog = M('Vip_log_money');
            $data_mlog =array(
                'vipid' => $vipItem['id'],
                'openid' => $vipItem['openid'],
                'nickname' => $vipItem['nickname'],
                'mobile' => $vipItem['mobile'],
                'money' => $vipItem["vip_apply_money"],
                'paytype' => 'money',
                'balance' => $vipItem['money'] + $vipItem["vip_apply_money"],
                'type' => 9,
                'oid' => $data_log['opid'],
                'ctime' => time(),
                'remark' => '取消升级VIP会员',
            );


            $rflow = $mlog->add($data_mlog);
            if(!$rflow){
                $m->rollback();
                $info['msg'] = '操作失败，请重新尝试！';
                $this->ajaxReturn($info);
            }

           
            $data = array(
                'money' => $vipItem['money'] + $vipItem["vip_apply_money"],
                'vip_apply_status' => 0,
                'vtime' => 0,
                'vip_expiration_time' => 0,
                'vip_apply_type' => 0,
                'vip_apply_money'=>0
            );

            $bool = $m->where($where)->save($data);

            if($bool === false){
                $m->rollback();
                $info['msg'] = '操作失败，请重新尝试！';
            }else{
                $m->commit();
                $info = array(
                            'code' => 1,
                            'msg' => '操作成功!',
                        );
            }
            $this->ajaxReturn($info);
        }

        $this->ajaxReturn($info);
    }

    public function upVipSetList(){

        $where = array('id'=>1);
        $vip_rule = M('VipSet')->where($where)->getField('vip_rule');
        $vip_rule = explode(',', $vip_rule);
        $vipRuleItem = array();
        foreach($vip_rule as $k => $vo){
            $vipRuleItem[] = explode(':', $vo);
        }
        $this->display();
    }

}