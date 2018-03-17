<?php
// 本类由系统自动生成，仅供测试用途
namespace Admin\Controller;

class UploadController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    public function indeximg()
    {
        //查找带回字段
        $fbid = I('fbid');
        $isall = I('isall');
        $this->assign('fbid', $fbid);
        $this->assign('isall', $isall);
        $page = '1,8';
        $m = M('Upload_img');
        $cache = $m->page($page)->order('id desc')->select();
        $this->assign('cache', $cache);
        $this->ajaxReturn($this->fetch());
    }

    public function doupimg()
    {

        $config = array(
            'mimes' => array(), //允许上传的文件MiMe类型
            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
            'exts' => array('jpg', 'gif', 'png', 'jpeg'), //允许上传的文件后缀
            'autoSub' => true, //自动子目录保存文件
            'subName' => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => './Upload/', //保存根路径
            'savePath' => 'img/', //保存路径
            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt' => '', //文件保存后缀，空则使用原后缀
            'replace' => false, //存在同名是否覆盖
            'hash' => true, //是否生成hash编码
            'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
            'driver' => '', // 文件上传驱动
            'driverConfig' => array(), // 上传驱动配置
        );
        //var_dump($_FILES);
        $up = new \Util\Upload($config);
        if ($list = $up->upload($_FILES)) {
            //dump($list);
            $pic = M('Upload_img');
            $count = 0;
            $arr = array();
            foreach ($list as $k => $v) {
                //$arr['uid']=$uid;
                $arr['name'] = $list[$k]['name'];
                $arr['ext'] = $list[$k]['ext'];
                $arr['type'] = 'img';
                $arr['savename'] = $list[$k]['savename'];
                $arr['savepath'] = $list[$k]['savepath'];
                $re = $pic->add($arr);
                if ($re) {
                    $count += 1;
                }
            }

            if ($count) {
                $backstr = "'" . $count . "张图片上传成功！'" . ',' . "true";
                echo "<script>parent.doupimgcallback(" . $backstr . ")</script>";
            } else {
                echo "<script>parent.doupimgcallback('图片保存时失败！',false)</script>";
            };

        } else {
            echo "<script>parent.doupimgcallback('" . $up->getError() . "',false)</script>";
        };

    }

    public function delimgs()
    {
        if (IS_AJAX) {
            $m = M('Upload_img');
            $info = $m->find(I('ids'));
            $list = $m->delete(I('ids'));
            if(!$list | !$info){
                $data['status'] = 0;
                $data['msg'] = '删除失败，请重试或联系管理员！';
            }else{
                $path = './Upload/'.$info['savepath'].$info['savename'];
                if(unlink($path)){
                    $data['status'] = 1;
                    $data['msg'] = '成功删除' . $list . '张图片！';
                }else{
                    $data['status'] = 0;
                    $data['msg'] = '删除失败，请重试或联系管理员！';
                }
            }
            $this->ajaxReturn($data, 'JSON');
        } else {
            $this->error('微专家提醒您：禁止外部访问！');
        }
    }


    public function getmoreimg()
    {
        $page = I('p') . ',8';
        $m = M('Upload_img');
        $cache = $m->page($page)->order('id desc')->select();
        if ($cache) {
            $this->assign('cache', $cache);
            $this->ajaxReturn($this->fetch());//封装模板fetch并返回
        } else {
            $this->ajaxReturn("");
        }

    }
    public function newupload() 
    {
    	$config = array(
    			'mimes' => array(), //允许上传的文件MiMe类型
    			'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
    			'exts' => array('pdf', 'doc', 'docx'), //允许上传的文件后缀
    			'autoSub' => true, //自动子目录保存文件
    			'subName' => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
    			'rootPath' => './Upload/', //保存根路径
    			'savePath' => 'insurance/', //保存路径
    			'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
    			'saveExt' => '', //文件保存后缀，空则使用原后缀
    			'replace' => false, //存在同名是否覆盖
    			'hash' => true, //是否生成hash编码
    			'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
    			'driver' => '', // 文件上传驱动
    			'driverConfig' => array(), // 上传驱动配置
    	);
    	if(count($_FILES)==0) {
    		$data['error'] = '没有要上传的文件';
    		$this->ajaxReturn($data);
    	}
    	if(count($_FILES)>1) {
    		$data['error'] = '没有要上传的文件';
    		$this->ajaxReturn($data);
    	}
    	$up = new \Util\Upload($config);
    	if ($list = $up->upload($_FILES)) {
    		foreach ($list as $k => $v) {
	    		$data['error'] = '';
	    		$data['filepath'] = trim($config['rootPath'].$v['savepath'].$v['savename'],'.');
    		}
    	} else {
    		$data['error'] = $up->getError();
    	};
    	$this->ajaxReturn($data);
    }
}