<?php
/**
 * Created by Eclipse.
 * User: mapeijian
 * Date: 16/7/12
 * Time: 18:31
 */

namespace Admin\Controller;
use Org\Util\Geohash;

class SinceController extends BaseController
{

    //CMS后台商城管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Finance/index'),
            ),
        );
        $this->display();
    }

    //CMS后台门店设置
    public function set()
    {
        $id = I('id');
        $m = M('Shop_set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城管理',
                'url' => U('Admin/Finance/index'),
            ),
            '1' => array(
                'name' => '商城设置',
                'url' => U('Admin/Finance/set'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $old = $m->where('id=' . $id)->find();
            if ($old) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
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
        $cache = $m->where('id=1')->find();
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台金融商品分类
    public function cate()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '自提点列表',
                'url' => U('Admin/Since/cate'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Since');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $province = I('province', 0, 'intval');
        $city = I('city', 0, 'intval');
        $district = I('district', 0, 'intval');
        $name = I('name') ? I('name') : '';
        $map = array();
        if($province) {
        	$map['province'] = array('eq', $province);
        	$this->assign('province', $province);
        	$c = M('region')->where(array('parent_id' => $province, 'level' => 2))->select();
        	$this->assign('citylist', $c);
        }
        if($city) {
        	$map['city'] = array('eq', $city);
        	$this->assign('city', $city);
        	$d = M('region')->where(array('parent_id' => $city, 'level' => 3))->select();
        	$this->assign('districtlist', $d);
        }
        if($district) {
        	$map['district'] = array('eq', $district);
        	$this->assign('district', $district);
        }
        if ($name) {
            $map['address'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        //AppTree快速无限分类
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m->where($map)->page($p, $psize)->order('id desc')->select();
        $this->getPage($count, $psize, 'App-loader', '自提点列表', 'App-search');
        $this->assign('cache', $cache);
        $region_list = get_region_list();
        $this->assign('region_list', $region_list);
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('provincelist', $p);
        
        $this->display();
    }

    //CMS后台商城分类设置
    public function cateSet()
    {
        $id = I('id');
        $m = M('Since');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '首页',
                'url' => U('Admin/Index/index'),
            ),
            '1' => array(
                'name' => '自提点列表',
                'url' => U('Admin/Since/cate'),
            ),
            '2' => array(
                'name' => '自提点设置',
                'url' => $id ? U('Admin/Since/cateSet', array('id' => $id)) : U('Admin/Shop/cateSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            $geohash=new Geohash;
            $computed_hash=$geohash->encode($data['latitude'], $data['longitude']);
            $data['geohash'] = $computed_hash;
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
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
            $c = M('region')->where(array('parent_id' => $cache['province'], 'level' => 2))->select();
            $d = M('region')->where(array('parent_id' => $cache['city'], 'level' => 3))->select();
            if ($data['twon']) {
            	$e = M('region')->where(array('parent_id' => $data['district'], 'level' => 4))->select();
            	$this->assign('twon', $e);
            }
            $this->assign('city', $c);
            $this->assign('district', $d);
        }
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();      
        $this->assign('province', $p);
        $this->display();
    }

    public function cateDel()
    {
        $user=self::$CMS['user'];
        if($user["user_type"]==1){
            $info['status'] = 0;
            $info['msg'] = '你没有权限删除!';
            $this->ajaxReturn($info);
        }
        $id = $_GET['id']; //必须使用get方法
        $m = M('Since');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        //删除时判断
        $re = $m->delete($id);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
            $this->ajaxReturn($info);
        }
        $this->ajaxReturn($info);
    }

}