<?php
function random_str($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ ){
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}
function request_by_fsockopen($url, $post_data = array())
{
    $url_array = parse_url($url);
    $hostname = $url_array['host'];
    $port = isset($url_array['port']) ? $url_array['port'] : 80;
    $requestPath = $url_array['path'] . "?" . $url_array['query'];
    $fp = fsockopen($hostname, $port, $errno, $errstr, 10);
    if (!$fp) {
        echo "$errstr ($errno)";
        return false;
    }
    $method = "GET";
    if (!empty($post_data)) {
        $method = "POST";
    }
    $header = "$method $requestPath HTTP/1.1\r\n";
    $header .= "Host: $hostname\r\n";
    if (!empty($post_data)) {
        $_post = strval(NULL);
        foreach ($post_data as $k => $v) {
            $_post[] = $k . "=" . urlencode($v);//必须做url转码以防模拟post提交的数据中有&符而导致post参数键值对紊乱
        }
        $_post = implode('&', $_post);
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";//POST数据
        $header .= "Content-Length: " . strlen($_post) . "\r\n";//POST数据的长度
        $header .= "Connection: Close\r\n\r\n";//长连接关闭
        $header .= $_post; //传递POST数据
    } else {
        $header .= "Connection: Close\r\n\r\n";//长连接关闭
    }
    fwrite($fp, $header);
    //-----------------调试代码区间-----------------
    /*$html = '';
    while (!feof($fp)) {
        $html.=fgets($fp);
    }
    echo $html;*/
    //-----------------调试代码区间-----------------
    fclose($fp);
}

/**
 * 其它版本
 * 使用方法：
 * $post_string = "app=request&version=beta";
 * request_by_other('http://facebook.cn/restServer.php',$post_string);
 */
function request_by_other($remote_server, $post_string)
{
    $context = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded' .
                '\r\n' . 'User-Agent : Jimmy\'s POST Example beta' .
                '\r\n' . 'Content-length:' . strlen($post_string) + 8,
            'content' => 'mypost=' . $post_string)
    );
    $stream_context = stream_context_create($context);
    $data = file_get_contents($remote_server, false, $stream_context);
    return $data;
}
/**
 *
 +--------------------------------------------------------------------
 * Description 递归创建目录
 +--------------------------------------------------------------------
 * @param  string $dir 需要创新的目录
 +--------------------------------------------------------------------
 * @return 若目录存在,或创建成功则返回为TRUE
 +--------------------------------------------------------------------
 * @author gongwen
 +--------------------------------------------------------------------
 */
function mkdirs($dir, $mode = 0777){
	if (is_dir($dir) || mkdir($dir, $mode)) return TRUE;
	if (!mkdirs(dirname($dir), $mode)) return FALSE;
	return mkdir($dir, $mode);
}

/**
 *
 +--------------------------------------------------------------------
 * Description 递归删除目录
 +--------------------------------------------------------------------
 * @param  string $dir 需要删除的目录
 +--------------------------------------------------------------------
 * @return 若目录不存在或册除成功则返回为TRUE
 +--------------------------------------------------------------------
 * @author gongwen
 +--------------------------------------------------------------------
 */
function rmdirs($dir){
	if (!is_dir($dir) || rmdir($dir)) return TRUE;
	if($dir_handle=opendir($dir)){
		while($filename=readdir($dir_handle)){
			if($filename!='.' && $filename!='..'){
				$subFile=$dir.'/'.$filename;
			}
			is_dir($subFile)?rmdirs($subFile):unlink($subFile);
		}
		closedir($dir_handle);
		return rmdir($dir);
	}
}
/**
 * Goofy 2011-11-30
 * getDir()去文件夹列表，getFile()去对应文件夹下面的文件列表,二者的区别在于判断有没有“.”后缀的文件，其他都一样
 */

//获取文件目录列表,该方法返回数组
function getDir($dir)
{
    $dirArray[] = NULL;
    if (false != ($handle = opendir($dir))) {
        $i = 0;
        while (false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && !strpos($file, ".") && $file != '.DS_Store') {
                $dirArray[$i] = $file;
                $i++;
            }
        }
        //关闭句柄
        closedir($handle);
    }
    return $dirArray;
}

//获取文件列表
function getFile($dir)
{
    $fileArray[] = NULL;
    if (false != ($handle = opendir($dir))) {
        $i = 0;
        while (false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && strpos($file, ".")) {
                $fileArray[$i] = "./imageroot/current/" . $file;
                if ($i == 100) {
                    break;
                }
                $i++;
            }
        }
        //关闭句柄
        closedir($handle);
    }
    return $fileArray;
}

//调用方法getDir("./dir")……
function displayDir($str)
{
    if (!is_dir($str))
        die ('不是一个目录！');
    $files = array();
    if ($hd = opendir($str)) {
        while ($file = readdir($hd)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($str . '/' . $file)) {
                    $files [$file] = displayDir($str . '/' . $file);
                } else {
                    $files [] = $file;
                }
            }
        }
    }
    return $files;
}

/**
 * 执行SQL文件
 */
function execute_sql_file($sql_path)
{
    // 读取SQL文件
    $sql = wp_file_get_contents($sql_path);
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    // 替换表前缀
    $orginal = 'wp_';
    $prefix = C('DB_PREFIX');
    $sql = str_replace("{$orginal}", "{$prefix}", $sql);

    // 开始安装
    foreach ($sql as $value) {
        $value = trim($value);
        if (empty ($value))
            continue;

        $res = M()->execute($value);
        // dump($res);
        // dump(M()->getLastSql());
    }
}

// 防超时的file_get_contents改造函数
function wp_file_get_contents($url)
{
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 30
        )
    )); // 超时时间，单位为秒

    return file_get_contents($url, 0, $context);
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author better
 * @useage u_addons('apply://App/Index/addorder',array('id'=>'1'))
 */
function u_addons($url, $param = array())
{
    $url = explode('://', $url);
    $addon = $url[0];
    $url = $url[1];

    $url = U($url, $param, false);
    return $url . '/addon/' . $addon;
}

/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * UTF-8错误信息输出
 * @param  string $msg 错误名称
 * @return null
 * @author App <2094157689@qq.com>
 */
function utf8error($msg)
{
    header("Content-type: text/html; charset=utf-8");
    die($msg);
}

/**
 * UE编辑器处理
 * @param  string $data 编辑器内容
 * @return null
 * @author App <2094157689@qq.com>
 */
function trimUE($data)
{
    $data = stripslashes(htmlspecialchars_decode($data));
    $find = array("<p><br/></p>", "<p>		</p>", "<p>			</p>");
    $data = htmlspecialchars(str_replace($find, "", $data));
    return $data;
}

/**
 * 分类路径配置
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function setPath($m, $pid)
{
    $cate = $m;
    $map['id'] = $pid;
    $list = $cate->field('id,path')->where($map)->limit(1)->find();
    $path = $list['path'] . '-' . $list['id'];
    $lv = count(explode('-', $path));
    return array('path' => $path, 'lv' => $lv);
}

/**
 * SonCate配置
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function setSoncate($m, $pid)
{
    $cate = $m;
    $map['pid'] = $pid;
    $father = $cate->where('id=' . $pid)->limit(1)->find();
    $son = $cate->field('id')->where($map)->select();
    if ($son && $father) {
        //存在子栏目
        $arr = '';
        foreach ($son as $k => $v) {
            $arr = $arr . $v['id'] . ',';
        }
        $father['soncate'] = $arr;
        $rf = $m->save($father);
        if ($rf === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    } elseif (!$son && $father) {
        //子栏目为空
        $father['soncate'] = '';
        $rf = $m->save($father);
        if ($rf === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    } else {
        //未知错误
        return FALSE;
    }
}

/**
 * 获取分类
 * @param  string $m
 * @return array
 */
function getCate($m,$where='')
{
	$cates = array();
	$list = $m->where($where)->select();
	foreach($list as $k => $v) {
		$cates[$v['id']] = $v['name'];
	}
	return $cates;
}
/**
 * AppTree快速无限分类树
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function appTree($m, $pid, $field, $map, $order = 'sorts desc', $keyid = 'id', $keypid = 'pid', $keychild = '_child')
{
    $list = $m->where($map)->field($field)->order('sorts desc')->select();
    $list = list_to_tree($list, $keyid, $keypid, $keychild, $root = $pid);
    return $list;
}

/**
 * 所有数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function Descartes()
{
    $t = func_get_args();
    if (func_num_args() == 1) {
        return call_user_func_array(__FUNCTION__, $t[0]);
    }

    $a = array_shift($t);
    if (!is_array($a)) {
        $a = array($a);
    }

    $a = array_chunk($a, 1);
    do {
        $r = array();
        $b = array_shift($t);
        if (!is_array($b)) {
            $b = array($b);
        }

        foreach ($a as $p) {
            foreach (array_chunk($b, 1) as $q) {
                $r[] = array_merge($p, $q);
            }
        }

        $a = $r;
    } while ($t);
    return $r;
}

/**
 * 插件模板显示方法
 * @param  string $tpl 模板名称,可以为空值
 * @return null
 * @author App <2094157689@qq.com>
 */
function adisplay($tpl)
{
    if ($tpl) {
        C('VIEW_PATH', MODULE_PATH . '/Addon/');
    } else {

    }
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str 要分割的字符串
 * @param  string $glue 分割符
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array $arr 要连接的数组
 * @param  string $glue 分割符
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data) {
            $refer[$i] = &$data[$field];
        }

        switch ($sortby) {
            case 'asc':    // 正向排序
                asort($refer);
                break;
            case 'desc':    // 逆向排序
                arsort($refer);
                break;
            case 'nat':    // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val) {
            $resultSet[] = &$list[$key];
        }

        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
    if (is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby = 'asc');
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function set_redirect_url($url)
{
    cookie('redirect_url', $url);
}

/**
 * 获取跳转页面URL
 * @return string 跳转页URL
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_redirect_url()
{
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = array())
{
    \Think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name)
{
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function addons_url($url, $param = array())
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );
    $params = array_merge($params, $param); //添加额外参数

    return U('Addons/execute', $params);
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null)
{

    //参数检查
    if (empty($action) || empty($model) || empty($record_id)) {
        return '参数不能为空';
    }
    if (empty($user_id)) {
        $user_id = is_login();
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return '该行为被禁用或删除';
    }

    //插入行为日志
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;

    //解析日志规则,生成日志备注
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = array('user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME);
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //未定义日志规则，记录操作url
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self)
{
    if (empty($action)) {
        return false;
    }

    //参数支持id或者name
    if (is_numeric($action)) {
        $map = array('id' => $action);
    } else {
        $map = array('name' => $action);
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();
    if (!$info || $info['status'] != 1) {
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key => &$rule) {
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields) {
            $field = empty($fields) ? array() : explode(':', $fields);
            if (!empty($field)) {
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
            unset($return[$key]['cycle'], $return[$key]['max']);
        }
    }

    return $return;
}

/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null)
{
    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }

    $return = true;
    foreach ($rules as $rule) {

        //检查执行周期
        $map = array('action_id' => $action_id, 'user_id' => $user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }

        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

        if (!$res) {
            $return = false;
        }
    }
    return $return;
}

//基于数组创建目录和文件
function create_dir_or_files($files)
{
    foreach ($files as $key => $value) {
        if (substr($value, -1) == '/') {
            mkdir($value);
        } else {
            @file_put_contents($value, '');
        }
    }
}

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 * @author huajie <banhuajie@163.com>
 */
function get_table_name($model_id = null)
{
    if (empty($model_id)) {
        return false;
    }
    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if ($info['extend'] != 0) {
        $name = $Model->getFieldById($info['extend'], 'name') . '_';
    }
    $name .= $info['name'];
    return $name;
}

/**
 * 获取属性信息并缓存
 * @param  integer $id 属性ID
 * @param  string $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true)
{
    static $list;

    /* 非法ID */
    if (empty($model_id) || !is_numeric($model_id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('attribute_list');
    }

    /* 获取属性 */
    if (!isset($list[$model_id])) {
        $map = array('model_id' => $model_id);
        $extend = M('Model')->getFieldById($model_id, 'extend');

        if ($extend) {
            $map = array('model_id' => array("in", array($model_id, $extend)));
        }
        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
        //S('attribute_list', $list); //更新缓存
    }

    $attr = array();
    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if ($group) {
        $sort = M('Model')->getFieldById($model_id, 'field_sort');

        if (empty($sort)) {
            //未排序
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($sort, true);

            $keys = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        $attr = $group;
    }
    return $attr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'Common';
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * 根据条件字段获取指定表的数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @param string $table 需要查询的表
 * @author huajie <banhuajie@163.com>
 */
function get_table_field($value = null, $condition = 'id', $field = null, $table = null)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);
    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * where in 数组为空时返回不存在的字符例如(-10000000000)
 * @param $value
 * @return string
 */
function in_parse_str($value)
{
    if (!$value) {
        $value = '-10000000000';
    }
    return $value;
}

/**
 * @param $file
 * @param $word
 *
 * 日志功能
 */
function  log_result($file,$word)
{
    $fp = fopen($file,"a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 求两个日期之间相差的天数
 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
 * @param int $day1
 * @param int $day2
 * @return number
 */
function diffBetweenTwoDays($day1, $day2)
{
	if ($day1 < $day2) {
		$tmp = $day2;
		$day2 = $day1;
		$day1 = $tmp;
	}
	return intval(($day1 - $day2) / 86400, 2);
}
/**
 * 自动生成合同编号
 */
function build_contract_no(){
	return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}
/**
 * 验证码检查
 */
function check_verify($code, $id = ""){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}

function checkorderstatus($ordid){
	$Ord=M('Orderlist');
	$ordstatus=$Ord->where('ordid='.$ordid)->getField('ordstatus');
	if($ordstatus==1){
		return true;
	}else{
		return false;
	}
}

//处理订单函数
//更新订单状态，写入订单支付后返回的数据
function orderhandle($parameter){
	$ordid = $parameter['out_trade_no'];
	$data['payment_trade_no']      =$parameter['trade_no'];
	$data['payment_trade_status']  =$parameter['trade_status'];
	$data['payment_notify_id']     =$parameter['notify_id'];
	$data['payment_notify_time']   =$parameter['notify_time'];
	$data['payment_buyer_email']   =$parameter['buyer_email'];
	$data['ordstatus']             =1;
	$Ord=M('Orderlist');
	$Ord->where('ordid='.$ordid)->save($data);
}

//金融产品销量计算
function doBuySells($order)
{
	$mgoods = M('Finance_goods');
	$mlogsell = M('Finance_syslog_sells');
	//封装dlog
	$dlog['oid'] = $order['id'];
	$dlog['vipid'] = $order['vipid'];
	$dlog['vipopenid'] = $order['vipopenid'];
	$dlog['vipname'] = $order['vipname'];
	$dlog['ctime'] = time();
	$tmplog = array();
	$dnum = $order['totalnum'];
	 
	$goods = $mgoods->where('id=' . $order['goodsid'])->find();;
	if($goods['ismb']) {
		return false;
	}
	if(($goods['num']-$dnum)>=0) {
		$rg = $mgoods->where('id=' . $order['goodsid'])->setDec('num', $dnum);
		$rg = $mgoods->where('id=' . $order['goodsid'])->setInc('sells', $dnum);
	} else {
		return false;
	}
	$goods =  $mgoods->where('id=' . $order['goodsid'])->find();
	//修改产品状态
	if($goods['status'] == 1 && $goods['num'] <= 0) {
		$data['status'] = 2;
		$rod = $mgoods->where('id='.$order['goodsid'])->save($data);
	}
	$dlog['goodsid'] = $goods['id'];
	$dlog['goodsname'] = $goods['name'];
	$dlog['price'] = $goods['price'];
	$dlog['num'] = $goods['num'];
	$dlog['total'] = $goods['num'] * $goods['price'];
	$rlog = $mlogsell->addAll($dlog);
	return true;
}
//生成分红记录
function doAllFhLog($goodsid)
{
	if(!$goodsid) {
		return false;
	}
	$mgoods = M('Finance_goods');
	$morder = M('Finance_order');
	$m = M('Finance_fhlog');

	$goods =  $mgoods->where('id=' . $goodsid)->find();
	if(!$goods) {
		return false;
	}
	$now = time();
	$getdate = getFhDate($now, $goods['cycle']);
	$mgoods->where('id='.$goods['id'])->setField('rtime',$now);
	$morder->where('goodsid='.$goods['id'])->setField('rtime',$getdate);
	$orderlist = $morder->where(array('goodsid'=>$goodsid,'status'=>2))->select();

	$flog['qid'] = $goodsid;
	$flog['status'] = 0;
	$flog['issh'] = 0;
	$flog['bonusway'] = $goods['bonusway'];
	//到期分红
	if($goods['bonusway'] == 1) {
		foreach($orderlist as $k => $v) {
			$flog['oid'] = $v['id'];
			$flog['to'] = $v['vipid'];
			$flog['toname'] = $v['vipname'];
			$flog['msg'] = '到期分红';
			$flog['rate'] = $goods['rate']; //年回报率
			$flog['money'] = round($v['totalprice']*$goods['rate']/12/30*$goods['cycle'],2);
			$flog['type'] = 1;
			$flog['getdate'] = $getdate;
			$flog['ctime'] = $now;
			$m->add($flog);

			$flog['msg'] = '返还购买款项';
			$flog['rate'] = 0;
			$flog['money'] = $v['totalprice'];
			$flog['type'] = 2;
			$flog['getdate'] = $v['rtime'];
			$flog['ctime'] = $now;
			$m->add($flog);
		}
	} 
 //    elseif($goods['bonusway'] == 2) {
	// 	//按月分红
	// 	if($goods['cycle'] > 0) {
	// 		foreach($orderlist as $k => $v) {
	// 			$flog['type'] = 1;
	// 			$flog['oid'] = $v['id'];
	// 			$flog['to'] = $v['vipid'];
	// 			$flog['toname'] = $v['vipname'];
	// 			$flog['rate'] = $goods['rate']; //年回报率
	// 			for($i=1;$i<=$goods['cycle'];$i++) {
	// 				$flog['msg'] = '按月分红，['.$i.'/'.$goods['cycle'].'期]分红';
	// 				$flog['money'] = round($order['totalprice']*$goods['rate']/12,2);
	// 				$flog['getdate'] = getFhDate($now, $i);
	// 				$flog['ctime'] = $now;
	// 				$m->add($flog);
	// 			}
	// 			$flog['msg'] = '返还购买款项';
	// 			$flog['rate'] = 0;
	// 			$flog['money'] = $v['totalprice'];
	// 			$flog['status'] = 0;
	// 			$flog['type'] = 2;
	// 			$flog['getdate'] = $v['rtime'];
	// 			$flog['ctime'] = $now;
	// 			$m->add($flog);
	// 		}
	// 	}
	// } 
    elseif($goods['bonusway'] == 3) {
		if($goods['day'] > 0) {
			foreach($orderlist as $k => $v) {
				//按天分红
				$days = floor(diffBetweenTwoDays($v['rtime'], $now));
				//分红的次数
				$times = floor($days/$goods['day']);
				$flog['type'] = 1;
				$flog['oid'] = $v['id'];
				$flog['to'] = $v['vipid'];
				$flog['toname'] = $v['vipname'];
				$flog['rate'] = $goods['rate']; //年回报率
				for($i=1; $i <= $times; $i++) {
					$flog['msg'] = '按天分红，['.$i.'/'.$times.'期]分红';
					$flog['money'] = round($v['totalprice']*$goods['rate']/365*$goods['day'],2);
					$flog['getdate'] = $now + $i*$goods['day']*24*60*60;
					$flog['ctime'] = $now;
					$m->add($flog);
				}
				$flog['msg'] = '返还购买款项';
				$flog['rate'] = 0;
				$flog['money'] = $v['totalprice'];
				$flog['status'] = 0;
				$flog['type'] = 2;
				$flog['getdate'] = $v['rtime'];
				$flog['ctime'] = $now;
				$m->add($flog);
			}
		}
	}
}
//获取分红日期(返回订单日期$month个月后的日期时间戳)
function getFhDate($orderdate, $day)
{
	$str = "+".$day." day";
	$result_date = strtotime($str, $orderdate);
	$result_date = strtotime(date('Y-m-d',$result_date));//取零时时间戳
	return $result_date;
}
/*-----------------------------------
 2013.8.13更正
 下面这个函数，其实不需要，大家可以把他删掉，
 具体看我下面的修正补充部分的说明
 ------------------------------------*/

//获取一个随机且唯一的订单号；
function getordcode(){
	$Ord=M('Orderlist');
	$numbers = range (10,99);
	shuffle ($numbers);
	$code=array_slice($numbers,0,4);
	$ordcode=$code[0].$code[1].$code[2].$code[3];
	$oldcode=$Ord->where("ordcode='".$ordcode."'")->getField('ordcode');
	if($oldcode){
		getordcode();
	}else{
		return $ordcode;
	}
}
/*移动端判断*/
function isMobile()
{
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
	{
		return true;
	}
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset ($_SERVER['HTTP_VIA']))
	{
		// 找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT']))
	{
		$clientkeywords = array ('nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile'
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
		{
			return true;
		}
	}
	// 协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT']))
	{
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
		{
			return true;
		}
	}
	return false;
}
//获取文件类型后缀
function extend($file_name){
	$extend = pathinfo($file_name);
	$extend = strtolower($extend["extension"]);
	return $extend;
}
//商品销量计算
function doShopSells($order)
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
/**
 * 获取当前页面完整URL地址
 */
function get_url() {
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}
/**
 * 校验身份证号码
 */
function checkIdCard($idcard){

	// 只能是18位
	if(strlen($idcard)!=18){
		return false;
	}

	// 取出本体码
	$idcard_base = substr($idcard, 0, 17);

	// 取出校验码
	$verify_code = substr($idcard, 17, 1);

	// 加权因子
	$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

	// 校验码对应值
	$verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

	// 根据前17位计算校验码
	$total = 0;
	for($i=0; $i<17; $i++){
		$total += substr($idcard_base, $i, 1)*$factor[$i];
	}

	// 取模
	$mod = $total % 11;

	// 比较校验码
	if($verify_code == $verify_code_list[$mod]){
		return true;
	}else{
		return false;
	}
}
function lazyload($matches) {
	if (!preg_match('/class\s*=\s*"/i', $matches[0])) {
		$class_attr = 'class="" ';
	}
	$replacement = $matches[1] . $class_attr . 'src="/Public/App/images/loading.gif" data-original' . substr($matches[2], 3) . $matches[3];
	$replacement = preg_replace('/class\s*=\s*"/i', 'class="lazy ', $replacement);
	$replacement .= '<noscript>' . $matches[0] . '</noscript>';
	return $replacement;
}
function filter_the_content($content) {
	return preg_replace_callback('/(<\s*img[^>]+)(src\s*=\s*"[^"]+")([^>]+>)/i', "lazyload", $content);
}
function request_get($url)
{
	$oCurl = curl_init();
	if (stripos($url, "https://") !== FALSE) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
	}
	curl_setopt($oCurl, CURLOPT_URL, $url);
	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);
	if (intval($aStatus["http_code"]) == 200) {
		return $sContent;
	} else {
		return false;
	}
}
//积分记录
function log_credit($vipid, $score, $type=1, $oid, $xxvipid=0, $xxnickname='')
{
	$m = M('vip_log_credit');
	$data['ip'] = get_client_ip();
	$data['vipid'] = $vipid;
	$data['score'] = $score;
	$data['oid'] = $oid;
	$data['type'] = $type;
	$data['ctime'] = time();
	switch($type) {
		case 1:
			$data['msg'] = '注册赠送'.$score.'积分';
			break;
		case 2:
			$data['xxvipid'] = $xxvipid;
			$data['xxnickname'] = $xxnickname;
			$data['msg'] = '带来注册奖励,用户昵称：'.$xxnickname;
			break;
		case 3:
			$data['msg'] = '购买商品，订单号：'.$oid;
			break;
		case 4:
			$data['msg'] = '兑换商品，订单号：'.$oid;
			break;
		case 5:
			$data['msg'] = '带来消费奖励，订单号：'.$oid;
			break;
	}
	$re = $m->add($data);
	return $re;
}
//计算订单赠送的积分
function get_order_credit($order)
{
	$m = M('Shop_goods');
	$items = unserialize($order['items']);
	$totalscore = 0;
	foreach($items as $k => $v) {
		$score = $m->where(array('id'=>$v['goodsid']))->getField('score');
		if($score>0) {
			$totalscore += $score;
		}
	}
	return $totalscore;
}

/**
 * 计算用户消费总额
 * @param int $vipid 用户ID
 * @param int $time	统计时间，从这个时间开始计算
 * @param int $type 1:商城 2：金融
 */
function get_user_totalpay($vipid, $type, $time=0){
	$m = M('Vip_log_money');
	$map['vipid'] = $vipid;
	if($type == 1) {
		$map['type'] = array('in','3,9');
	} else {
		$map['type'] = 4;
	}
	if($time && $time < time()) {
		$map['ctime'] = array('gt',$time);
	}
	$totalpay = abs($m->where($map)->sum('money'));
	return $totalpay > 0 ? $totalpay : 0;
}
/**
 * @param $vipid 用户ID
 * 计算购物车价格
 */
function calculate_price($vipid) {
	if(!$vipid) {
		return array('status'=>0,'msg'=>"您未登陆");
	}
	
	$m = M('Shop_basket');
	$mgoods = M('Shop_goods');
	$msku = M('Shop_goods_sku');
	$mvip = M('Vip');
	$vip = $mvip->where('id='.$vipid)->find();
	if(empty($vip)) {
		return array('status'=>0,'msg'=>"用户不存在");
	}
	$cache = $m->where(array('sid' => 0, 'vipid' => $vipid))->select();
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
		$pic = getPic($goods['pic']);
		if ($v['sku']) {
			//取商品数据
			if ($goods['issku'] && $goods['status']) {
				$map['sku'] = $v['sku'];
				$sku = $msku->where($map)->find();
				if ($sku['status']) {
					if ($sku['num']) {
						//调整购买量
						$cache[$k]['goodsid'] = $goods['id'];
						$cache[$k]['skuid'] = $sku['id'];
						$cache[$k]['name'] = $goods['name'];
						$cache[$k]['skuattr'] = $sku['skuattr'];
						$cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
						if($vip['groupid'] == 2 && $sku['vprice'] > 0) {
							$cache[$k]['price'] = $sku['vprice'];//会员价
						} else {
							$cache[$k]['price'] = $sku['price'];
						}
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
					if($vip['groupid'] == 2 && $goods['vprice'] > 0) {
						$cache[$k]['price'] = $goods['vprice'];//会员价
					} else {
						$cache[$k]['price'] = $goods['price'];
					}
					$cache[$k]['total'] = $v['num'] * $goods['price'];
					$cache[$k]['pic'] = $pic['imgurl'];
					$totalnum = $totalnum + $cache[$k]['num'];
                    if($goods['type']==2){
                        // $where['vipid'] = 
                        $where['helpvipid'] = $vipid;
                        $where['goodsid'] = $goods['id'];
                        // $barnum = M('bargain') -> where($where) -> order('etime desc') -> count();
                        // if($barnum<3){
                            // unset($where['vipid']);
                        $where['status'] = 0;
                        $where['money'] = array('gt', 0);
                        $bargain = M('bargain') -> where($where) -> order('etime desc') -> getField('price');
                        if($bargain){
                            $cache[$k]['price'] = $bargain;
                        }else{
                            $cache[$k]['price'] = $goods['oprice'];
                        }
                        // }else{
                        //     $cache[$k]['price'] = $goods['oprice'];
                        // }
                    }elseif ($goods['type']==1) {
                        if($_SESSION['isact']){
                            $cache[$k]['price'] = $goods['price'];
                        }else{
                            $cache[$k]['price'] = $goods['oprice'];
                        }
                    }
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
		$m->delete($todelids);
	}
	if(empty($cache)) {
		return array('status'=>0,'msg'=>"购物没有商品");
	}
	//将商品列表
	sort($cache);
	$allitems = serialize($cache);
	//商品数量 订单总价   订单商品
	$result = array(
			'totalnum'          => $totalnum,
			'totalprice'        => $totalprice,
			'allitems'       	=> $allitems,
			'goodslist'			=> $cache,
	);
	return array('status'=>1,'msg'=>"计算价钱成功",'result'=>$result); // 返回结果状态
}
//获取单张图片
function getPic($id)
{
	$m = M('UploadImg');
	$map['id'] = $id;
	$list = $m->where($map)->find();
	if ($list) {
		$list['imgurl'] = __ROOT__ . "/Upload/" . $list['savepath'] . $list['savename'];
	}
	return $list ? $list : "";
}
//处理用户名（从第二位起用星号替换）
function handleNickname($username){
	$strlen = mb_strlen($username, 'utf-8');
	if($strlen<2){
		return '*';
	}else{
		$firstStr = mb_substr($username, 0, 1, 'utf-8');
		return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($username, 'utf-8')) : $firstStr . str_repeat("*", $strlen) ;
	}
}
//生成合同
function doContract($order)
{
	$m = M('Finance_contract');
	$mgoods = M('Finance_goods');
	$mvip = M('Vip');
	$vip = $mvip->where('id='.$order['vipid'])->find();
	$goods = $mgoods->where('id='.$order['goodsid'])->find();
	$profit = round($order['totalprice']*$goods['rate']/12/30*$goods['cycle'],2);
	$tomorrow = date("Y-m-d",strtotime("+1 day"));
    $endtime = strtotime("+".$goods['cycle']." day", strtotime($tomorrow));
	$etime = strtotime(date("Y-m-d", $endtime))+60*60*24-1;
	$contract_id = M('Finance_cate')->where('id = '.$goods['cid'])->getField('contract_id'); 
    $data['contract_id'] = $contract_id?$contract_id:1;
	$data['no'] = $order['contract'];
	$data['name'] = '第'.$order['goodsid'].'期：'.$goods['name'].'众筹创业项目合同';
	$data['idno'] = $vip['idno'];
	$data['vipid'] = $order['vipid'];
	$data['qid'] = $order['goodsid'];
	$data['oid'] = $order['oid'];
	$data['vipname'] = $order['vipname'];
	$data['vipmobile'] = $order['vipmobile'];
	$data['bonusway'] = $goods['bonusway'];
	$data['totalnum'] = $order['totalnum'];;
	$data['totalprice'] = $order['totalprice'];;
	$data['profit'] = $profit;
	$data['status'] = 0;
	$data['ctime'] = time();
	$data['etime'] = $etime;
	$r = $m->add($data);
	return $r;
}
//生成分红记录
function doFhLog($order)
{
	$mgoods = M('Finance_goods');
	$m = M('Finance_fhlog');
	//当前时间
	$now = time();
	$goods =  $mgoods->where('id=' . $order['goodsid'])->find();
	$flog['qid'] = $order['goodsid'];
	$flog['oid'] = $order['id'];
	$flog['to'] = $order['vipid'];
	$flog['toname'] = $order['vipname'];
	$flog['rate'] = $goods['rate']; //预期年回报率
	$flog['status'] = 0;
	$flog['type'] = 1;
	$flog['ctime'] = $now;
	//到期分红
	if($goods['bonusway'] == 1) {
		$flog['msg'] = '到期分红';
		//预期分红
		$flog['money'] = round($order['totalprice']*$goods['rate']/12/30*$goods['cycle'],2);
		$flog['getdate'] = $order['rtime'];
		$m->add($flog);
	}
 //     elseif($goods['bonusway'] == 2) {
	// 	//按月分红
	// 	if($goods['cycle'] > 0) {
	// 		for($i=1;$i<=$goods['cycle'];$i++) {
	// 			$flog['msg'] = '按月分红，['.$i.'/'.$goods['cycle'].'期]分红';
	// 			$flog['money'] = round($order['totalprice']*$goods['rate']/12,2);
	// 			$flog['getdate'] = getFhDate($order['ctime'], $i);
	// 			$m->add($flog);
	// 		}
	// 	}
	// } 
    elseif($goods['bonusway'] == 3) {
		//按天分红
		$days = floor(diffBetweenTwoDays($order['rtime'], time()));
		//分红的次数
		$times = floor($days/1);
		for($i=1; $i <= $times; $i++) {
			$flog['msg'] = '按天分红，['.$i.'/'.$times.'期]分红';
			$flog['money'] = round($order['totalprice']*$goods['rate']/365*1,2);
			$flog['getdate'] = $order['ctime'] + $i*24*60*60;
			$m->add($flog);
		}
	}
	$flog['msg'] = '返还本金';
	$flog['rate'] = 0;
	$flog['money'] = $order['totalprice'];
	$flog['status'] = 0;
	$flog['type'] = 2;
	$flog['ctime'] = time();
	$flog['getdate'] = $order['rtime'];
	$m->add($flog);
}
//销量计算
function doSells($order)
{
	$mgoods = M('Finance_goods');
	$mlogsell = M('Finance_syslog_sells');
	//封装dlog
	$dlog['oid'] = $order['id'];
	$dlog['vipid'] = $order['vipid'];
	$dlog['vipopenid'] = $order['vipopenid'];
	$dlog['vipname'] = $order['vipname'];
	$dlog['ctime'] = time();
	$tmplog = array();
	$dnum = $order['totalnum'];
	$goods =  $mgoods->where('id=' . $order['goodsid'])->find();
	//修改产品状态
	if($goods['status'] == 1 && $goods['num'] <= 0) {
		$data['status'] = 2;
		$rod = $mgoods->where('id='.$order['goodsid'].' and num<=0')->save($data);
	}
	$dlog['goodsid'] = $goods['id'];
	$dlog['goodsname'] = $goods['name'];
	$dlog['price'] = $goods['price'];
	$dlog['num'] = $goods['num'];
	$dlog['total'] = $goods['num'] * $goods['price'];
	$rlog = $mlogsell->addAll($dlog);
	return true;
}
//生成订单商品详情（用于后期编辑商品属性，如：体重，耳号等）
function doOrderGoods($order)
{
	$m = M('Finance_order_goods');
	$num = $order['totalnum'];
	// for($i=0;$i<$num;$i++) {
		$data['goodsid'] = $order['goodsid'];
		$data['oid'] = $order['id'];
		$data['ctime'] = $data['etime'] = time();
		$re = $m->add($data);
		if(FALSE !== $re) {
			$m->where('id='.$re)->setField('sn','S'.sprintf("%08d", $re));
		}
	// }
	return true;
}
/**
 * 处理配置费用
 * author: feng
 * create: 2017/9/23 17:47
 */
function handleDeliveryFee($order,$vip){
    //判断购物是否是合伙人，不是合伙人则统计配送费用
    if($order["delivery"]=="since"&&$order['sinceid']&&$vip["groupid"]==1){
        $condition['_string'] = 'FIND_IN_SET('.$order['sinceid'].',adlist)';
        $deliveryman= M('deliveryman')->where($condition)->find();
        $dvip=M("vip")->where(array("id"=>$deliveryman["vipid"]))->find();
        if($deliveryman&&$deliveryman["status"]==0&&$deliveryman["fee"]&&$dvip&&M("vip")->where(array("id"=>$deliveryman["vipid"]))->setInc("money",round( $order["totalprice"]*$deliveryman["fee"]/100,2))!==false){
            //资金流水记录
            $mlog = M('Vip_log_money');
            $flow['vipid'] =  $deliveryman["vipid"];
            $flow['openid'] = $dvip['openid'];
            $flow['nickname'] = $dvip['nickname'];
            $flow['mobile'] = $dvip['mobile'];
            $flow['money'] = round($order["totalprice"]*$deliveryman["fee"]/100,2);
            $flow['paytype'] = 'money';
            $flow['balance'] = $dvip['money'];
            $flow['type'] = 18;
            $flow['oid'] = $order["oid"];
            $flow['ctime'] = time();
            $flow['remark'] = '自提点配送订单'.$order["oid"]."获得配送费用".$flow['money'] ."元";
            $rflow = $mlog->add($flow);
            M('Shop_order')->where(array("id"=>$order['id']))->setField("delivery_fee",$flow["money"]);
            $data_msg["pids"]=$deliveryman["vipid"];
            $data_msg['title'] = "配送费用已到余额";
            $data_msg['content'] = '自提点配送订单'.$order["oid"]."获得配送费用".$flow['money'].'元。如有问题请联系客服！';
            $data_msg['ctime'] = time();
            $rmsg = M('vip_message')->add($data_msg);
        }

    }
}
/**
 * 处理区域管理员分佣金的问题
 * author: feng
 * create: 2017/9/23 17:54
 */
function handleAdminFee($order){
    //如果是区域管理员，需要扣取手续费，并将费用转到区域管理员的帐号
    if($order['adminid']&&$order['adminfee']){
        $admin=M("user")->where(array('id'=>$order['adminid']))->find();
        //将钱转到区域管理员余额
        if($admin["vipid"]){
            $adminvip=M("vip")->where(array("id"=>$admin["vipid"]))->find();
            if( $adminvip&&M("vip")->where(array("id"=>$admin["vipid"]))->setInc("money",$order["payprice"]-$order['adminfee'])!==false){
                //资金流水记录
                $mlog = M('Vip_log_money');
                $flow['vipid'] =  $admin["vipid"];
                $flow['openid'] = $adminvip['openid'];
                $flow['nickname'] = $adminvip['nickname'];
                $flow['mobile'] = $adminvip['mobile'];
                $flow['money'] = $order["payprice"]-$order['adminfee'];
                $flow['paytype'] = 'money';
                $flow['balance'] = $adminvip['money'];
                $flow['type'] = 17;
                $flow['oid'] = $order["oid"];
                $flow['ctime'] = time();
                $flow['remark'] = '区域产品被购买返回费用，扣取手续费'.$order['adminfee']."元";
                $rflow = $mlog->add($flow);
                $data_msg['title'] = "您区域产品被购买费用已到余额";
                $data_msg['content'] = '订单号：'.$order['oid'].'区域产品被购买返回费用已到余额'.($order["payprice"]-$order['adminfee']).'元，扣取手续费'.$order['adminfee']."元。如有问题请联系客服！";
                $data_msg['ctime'] = time();
                $data_msg["pids"]= $admin["vipid"];
                $rmsg = M('vip_message')->add($data_msg);
            }
        }
    }
}

function handleCommission($order, $id)
{
    //分销佣金计算(多分销)
    $commission = D('Commission');
    $orderids = array();
    $orderids[] = $id;


    $mvip = M('vip');
    $mfxlog = M('fx_syslog');
    $vip=$mvip->where(array("id"=>$order["vipid"]))->find();
    $pid = $vip['pid'];
    $fxlog['oid'] = $order['id'];
    $fxlog['fxprice'] = $fxprice = $order['payprice'] - $order['yf'];
    $fxlog['ctime'] = time();
    $fxtmp = array(); //缓存3级数组
    if ($pid) {
        //第一层分销
        $fx1 = $mvip->where('id=' . $pid)->find();
        $yj1 = $commission->ordersCommission('fx1rate', $orderids);
        if ($fx1['isfx'] && $yj1>0) {
            $fxlog['fxyj'] = $yj1;
            $fx1['money'] = $fx1['money'] + $fxlog['fxyj'];
            $fx1['total_xxbuy'] = $fx1['total_xxbuy'] + 1; //下线中购买产品总次数
            $fx1['total_xxyj'] = $fx1['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
            $rfx = $mvip->save($fx1);
            $fxlog['from'] = $vip['id'];
            $fxlog['type'] = 1;
            $fxlog['fromname'] = $vip['nickname'];
            $fxlog['to'] = $fx1['id'];
            $fxlog['toname'] = $fx1['nickname'];
            if (FALSE !== $rfx) {
                //佣金发放成功
                $fxlog['status'] = 1;
            } else {
                //佣金发放失败
                $fxlog['status'] = 0;
            }   
            array_push($fxtmp, $fxlog);
        }
        //第二层分销
        /*
        if ($fx1['pid']) {
            $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
            $yj2 = $commission->ordersCommission('fx2rate', $orderids);
            if ($fx2['isfx'] && $yj2>0) {
                $fxlog['fxyj'] = $yj2;
                $fx2['money'] = $fx2['money'] + $fxlog['fxyj'];
                $fx2['total_xxbuy'] = $fx2['total_xxbuy'] + 1; //下线中购买产品人数计数
                $fx2['total_xxyj'] = $fx2['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                $rfx = $mvip->save($fx2);
                $fxlog['from'] = $vip['id'];
                $fxlog['type'] = 1;
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx2['id'];
                $fxlog['toname'] = $fx2['nickname'];
                if (FALSE !== $rfx) {
                    //佣金发放成功
                    $fxlog['status'] = 1;
                } else {
                    //佣金发放失败
                    $fxlog['status'] = 0;
                }
                array_push($fxtmp, $fxlog);
            }
        }
        //第三层分销
        if ($fx2['pid']) {
            $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
            $yj3 = $commission->ordersCommission('fx3rate', $orderids);
            if ($fx3['isfx'] && $yj3>0) {
                $fxlog['fxyj'] = $yj3;
                $fx3['money'] = $fx3['money'] + $fxlog['fxyj'];
                $fx3['total_xxbuy'] = $fx3['total_xxbuy'] + 1; //下线中购买产品人数计数
                $fx3['total_xxyj'] = $fx3['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                $rfx = $mvip->save($fx3);
                $fxlog['from'] = $vip['id'];
                $fxlog['type'] = 1;
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx3['id'];
                $fxlog['toname'] = $fx3['nickname'];
                if (FALSE !== $rfx) {
                    //佣金发放成功
                    $fxlog['status'] = 1;
                } else {
                    //佣金发放失败
                    $fxlog['status'] = 0;
                }           
                array_push($fxtmp, $fxlog);
            }
        }*/
        //多层分销
        if (count($fxtmp) >= 1) {
            $refxlog = $mfxlog->addAll($fxtmp);
            if(FALSE !== $refxlog) {
                M('fx_dslog')->where('oid='.$order['id'])->setField('status', 0);
            } else {
                //file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
            }
        }
    }
}
    function handleCommissionBuy($order, $id){
        //分销佣金计算(多分销)
        $commission = D('Commission');
        $orderids = array();
        $orderids[] = $id;
        $mvip = M('vip');
        $vip = $mvip->where('id = '.$order['vipid'])->find();
        $pid = $vip['pid'];
        $mfxlog = M('fx_syslog');
        $fxlog['oid'] = $order['id'];
        $fxlog['fxprice'] = $fxprice = $order['payprice'] - $order['yf'];
        $fxlog['ctime'] = time();
        $fxtmp = array(); //缓存3级数组
        if ($pid) {
            //第一层分销
            $fx1 = $mvip->where('id=' . $pid)->find();
            $yj1 = $commission->ordersCommissionBuy('fx1rate', $orderids);
            if ($fx1['isfx'] && $yj1>0) {
                $fxlog['fxyj'] = $yj1;
                $fx1['money'] = $fx1['money'] + $fxlog['fxyj'];
                $fx1['total_xxbuy'] = $fx1['total_xxbuy'] + 1; //下线中购买产品总次数
                $fx1['total_xxyj'] = $fx1['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                $rfx = $mvip->save($fx1);
                $fxlog['from'] = $vip['id'];
                $fxlog['type'] = 2;
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx1['id'];
                $fxlog['toname'] = $fx1['nickname'];
                if (FALSE !== $rfx) {
                    //佣金发放成功
                    $fxlog['status'] = 1;
                } else {
                    //佣金发放失败
                    $fxlog['status'] = 0;
                }   
                array_push($fxtmp, $fxlog);
            }
            //第二层分销
            if ($fx1['pid']) {
                $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
                $yj2 = $commission->ordersCommissionBuy('fx2rate', $orderids);
                if ($fx2['isfx'] && $yj2>0) {
                    $fxlog['fxyj'] = $yj2;
                    $fx2['money'] = $fx2['money'] + $fxlog['fxyj'];
                    $fx2['total_xxbuy'] = $fx2['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $fx2['total_xxyj'] = $fx2['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                    $rfx = $mvip->save($fx2);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['type'] = 2;
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx2['id'];
                    $fxlog['toname'] = $fx2['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $fxlog['status'] = 1;
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    array_push($fxtmp, $fxlog);
                }
            }
            //第三层分销
            if ($fx2['pid']) {
                $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
                $yj3 = $commission->ordersCommissionBuy('fx3rate', $orderids);
                if ($fx3['isfx'] && $yj3>0) {
                    $fxlog['fxyj'] = $yj3;
                    $fx3['money'] = $fx3['money'] + $fxlog['fxyj'];
                    $fx3['total_xxbuy'] = $fx3['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $fx3['total_xxyj'] = $fx3['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                    $rfx = $mvip->save($fx3);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['type'] = 2;
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx3['id'];
                    $fxlog['toname'] = $fx3['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $fxlog['status'] = 1;
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }           
                    array_push($fxtmp, $fxlog);
                }
            }
            //多层分销
            if (count($fxtmp) >= 1) {
                $refxlog = $mfxlog->addAll($fxtmp);
                if(FALSE !== $refxlog) {
                    M('fx_dslog')->where('oid='.$order['id'])->setField('status', 0);
                } else {
                    //file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    function handleCommissionBuylog($order, $id){
        //分销佣金计算(多分销)
        $commission = D('Commission');
        $orderids = array();
        $orderids[] = $id;
        $mvip = M('vip');
        $vip = $mvip->where('id = '.$order['vipid'])->find();
        $pid = $vip['pid'];
        $mfxlog = M('fx_syslog');
        $fxlog['oid'] = $order['id'];
        $fxlog['fxprice'] = $fxprice = $order['payprice'] - $order['yf'];
        $fxlog['ctime'] = time();
        $fxtmp = array(); //缓存3级数组
        if ($pid) {
            //第一层分销
            $fx1 = $mvip->where('id=' . $pid)->find();
            $yj1 = $commission->ordersCommissionBuy('fx1rate', $orderids);
            if ($fx1['isfx'] && $yj1>0) {
                $fxlog['fxyj'] = $yj1;
                // $fx1['money'] = $fx1['money'] + $fxlog['fxyj'];
                // $fx1['total_xxbuy'] = $fx1['total_xxbuy'] + 1; //下线中购买产品总次数
                // $fx1['total_xxyj'] = $fx1['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
                // $rfx = $mvip->save($fx1);
                $fxlog['from'] = $vip['id'];
                $fxlog['status'] = 0;
                $fxlog['type'] = 2;
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx1['id'];
                $fxlog['toname'] = $fx1['nickname'];
                // if (FALSE !== $rfx) {
                //     //佣金发放成功
                //     $fxlog['status'] = 1;
                // } else {
                //     //佣金发放失败
                //     $fxlog['status'] = 0;
                // }   
                array_push($fxtmp, $fxlog);
            }
            // // 第二层分销
            // if ($fx1['pid']) {
            //     $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
            //     $yj2 = $commission->ordersCommissionBuy('fx2rate', $orderids);
            //     if ($fx2['isfx'] && $yj2>0) {
            //         $fxlog['fxyj'] = $yj2;
            //         $fx2['money'] = $fx2['money'] + $fxlog['fxyj'];
            //         $fx2['total_xxbuy'] = $fx2['total_xxbuy'] + 1; //下线中购买产品人数计数
            //         $fx2['total_xxyj'] = $fx2['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
            //         $rfx = $mvip->save($fx2);
            //         $fxlog['from'] = $vip['id'];
            //         $fxlog['type'] = 2;
            //         $fxlog['fromname'] = $vip['nickname'];
            //         $fxlog['to'] = $fx2['id'];
            //         $fxlog['toname'] = $fx2['nickname'];
            //         if (FALSE !== $rfx) {
            //             //佣金发放成功
            //             $fxlog['status'] = 1;
            //         } else {
            //             //佣金发放失败
            //             $fxlog['status'] = 0;
            //         }
            //         array_push($fxtmp, $fxlog);
            //     }
            // }
            // //第三层分销
            // if ($fx2['pid']) {
            //     $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
            //     $yj3 = $commission->ordersCommissionBuy('fx3rate', $orderids);
            //     if ($fx3['isfx'] && $yj3>0) {
            //         $fxlog['fxyj'] = $yj3;
            //         $fx3['money'] = $fx3['money'] + $fxlog['fxyj'];
            //         $fx3['total_xxbuy'] = $fx3['total_xxbuy'] + 1; //下线中购买产品人数计数
            //         $fx3['total_xxyj'] = $fx3['total_xxyj'] + $fxlog['fxyj']; //下线贡献佣金
            //         $rfx = $mvip->save($fx3);
            //         $fxlog['from'] = $vip['id'];
            //         $fxlog['type'] = 2;
            //         $fxlog['fromname'] = $vip['nickname'];
            //         $fxlog['to'] = $fx3['id'];
            //         $fxlog['toname'] = $fx3['nickname'];
            //         if (FALSE !== $rfx) {
            //             //佣金发放成功
            //             $fxlog['status'] = 1;
            //         } else {
            //             //佣金发放失败
            //             $fxlog['status'] = 0;
            //         }           
            //         array_push($fxtmp, $fxlog);
            //     }
            // }
            //多层分销
            if (count($fxtmp) >= 1) {
                $refxlog = $mfxlog->addAll($fxtmp);
                if(FALSE !== $refxlog) {
                    M('fx_dslog')->where('oid='.$order['id'])->setField('status', 0);
                } else {
                    //file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $rfxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }
    //获取最新参团人
    function getLastJoin($id) 
    {
    	$map['A.goodsid'] = $id;
    	$user = M('activity')->alias('as A')
    	->join('LEFT JOIN `'.C('DB_PREFIX').'vip` AS V ON V.id = A.vipid')
    	->where($map)
    	->field('V.headimgurl')
    	->order('A.id DESC')
    	->limit(3)
    	->select();
    	return $user;
    }
    //获取拼团过期时间
    function getGroupEtime(){
    	$shopset = M('shop_set') -> find();
    	$day = $shopset['ptday'];
    	$hour = $shopset['pthour'];
    	$minute = $shopset['ptminute'];
    	$second = $shopset['ftsecond'];
    	$time = time();
    	if($day>0) {
    		$time += $day*86400;
    	}
    	if($hour>0) {
    		$time += $hour*3600;
    	}
    	if($minute>0) {
    		$time += $minute*60;
    	}
    	if($second>0) {
    		$time += $second;
    	}
    	return $time;
    }
    //检查是否拼团已成功
    function groupSuccess($order){
    	$mgoods = M('Shop_goods');
    	$map['orderid'] = $order['id'];
    	$map['vipid'] = $order['vipid'];
    	$activity = M('activity')->where($map)->find();
    	if(empty($activity)) {
    		return false;
    	}
    	$goods = $mgoods->where('id='.$activity['goodsid'])->find();
    	if(empty($goods)) {
    		return false;
    	}
    	$group = M('activity_group')->where('id='.$activity['groupid'])->find();
    	if(empty($group)) {
    		return false;
    	}
    	if($goods['peoplenum']<= $group['num']) {
    		M()->startTrans();
    		$re1 = M('activity')->where(array('groupid'=>$group['id']))->setField('status', 1);
    		$re2 = M('activity_group')->where(array('id'=>$group['id']))->save(array('status'=>1, 'etime'=>time()));
    		$re3 = M('shop_order')->where(array('groupid'=>$group['id'],'type'=>1, 'ispay'=>1, 'status'=>9))->setField('status', 2);
    		$re4 = $mgoods->where('id='.$activity['goodsid'])->setInc('groupbuysells', $group['num']);
    		if($re1 && $re2 && $re3 && $re4) {
    			M()->commit();   
    			$info =  M('activity')->where(array('groupid'=>$group['id']))->select();
    			foreach ($info as $k => $v) {
    				$cache = M('Shop_order')->where('id = '.$v['orderid']) -> setField('status',2);
    				$oid = M('Shop_order')->where('id = '.$v['orderid'])->getField('oid');
    				$vip = M('Vip')->where('id='.$v['vipid'])->find();
    				//发送微信模板消息
    				$data['touser'] = $vip['openid'];
    				$data['template_id'] = 'aaTN_GjFkt-Sm4YmYVAfm_YKW_PJtGfQBvjZkGNyGYI';
    				$data['topcolor'] = "#00FF00";
    				$data['url'] = $_SERVER['HTTP_HOST'] . U("/App/Shop/orderDetail", array('sid'=>'0',"orderid" => $v['orderid']));
    				$data['data'] = array(
    						'first' => array('value' => '您参团的商品［'.$goods['name'].'］已组团成功，我们将尽快为您发货，请注意查收！'),
    						'keyword1' => array('value' => $order['payprice'].'元'),
    						'keyword2' => array('value' => $oid),
    						'remark' => array('value' => '点击查看订单详情')
    				);
    				$set = M('Set')->find();
    				$options['appid'] = $set['wxappid'];
    				$options['appsecret'] = $set['wxappsecret'];
    				$wx = new \Util\Wx\Wechat($options);
    				$re = $wx->sendTemplateMessage($data);
    				
    				$data_msg['pids'] = $v['vipid'];
    				$data_msg['title'] = "拼团成功，等待发货";
    				$data_msg['content'] = '您的订单'.$oid.'团购成功，请耐心等待发货，感谢您的支持！';
    				$data_msg['ctime'] = time();
    				$rmsg = M('Vip_message')->add($data_msg);
    			}
    			return true;
    		} else {
    			M()->rollback();//不成功，回滚
    			return false;
    		}
    	} else {
    		$vip = M('Vip')->where('id='.$order['vipid'])->find();
    		$tz = M('Vip')->where('id='.$group['vipid'])->find();//团长
    		//发送微信模板消息
    		$data['touser'] = $vip['openid'];
    		$data['template_id'] = '00IJJudfWi_3kQwUpbCsf_O69ISHEK_a6tDBe9zvk8M';
    		$data['topcolor'] = "#00FF00";
    		$data['url'] = $_SERVER['HTTP_HOST'] . U("App/Activity/groupShare", array("id" => $activity['id']));
    		$data['data'] = array(
    				'first' => array('value' => '你已成功参团【'.$goods['name'].'】'),
    				'leadername' => array('value' => $tz['nickname']),
    				'number' => array('value' => ($goods['peoplenum']- $group['num']).'人'),
    				'remark' => array('value' => '赶快邀请你的朋友们也来参加吧。')
    		);
    		$set = M('Set')->find();
    		$options['appid'] = $set['wxappid'];
    		$options['appsecret'] = $set['wxappsecret'];
    		$wx = new \Util\Wx\Wechat($options);
    		$re = $wx->sendTemplateMessage($data);
    	}
    }
    //检测是否可以自提
    function checkPickup($items){
    	$items = unserialize($items);
    	$pickup = array();
    	foreach($items as $k => $v) {
    	    $addressid = M('Shop_goods')->where('id='.$v['goodsid'])->getField('adressid');
    	    if($addressid) {
    	        $addressid = explode(',', $addressid);
    		} else {
    		    return array();
    		}
    		$pickup[$v['goodsid']] = $addressid;
    	}
    	if(count($items) == 1 && $addressid) {
    	    return $pickup;
    	}
    	if(!empty($pickup)) {
    		foreach($pickup as $k => $v) {
    			if(!isset($result)) {
    			    $result = $v;
    				continue;
    			} else {
    			    $result = array_intersect($result, $v);
    			}
    		}
    	}
    	if(empty($result)) {
    	    return array();
    	} else {
    		return $result;
    	}
    }
    /**
     * @param $arr
     * @param $key_name
     * @return array
     * 将数据库中查出的列表以指定的 id 作为数组的键名
     */
    function convert_arr_key($arr, $key_name)
    {
    	$arr2 = array();
    	foreach($arr as $key => $val){
    		$arr2[$val[$key_name]] = $val;
    	}
    	return $arr2;
    }
    /*
     * 获取地区列表
     */
    function get_region_list(){
    	//获取地址列表 缓存读取
    	if(!S('region_list')){
    		$region_list = M('region')->field('id,name,level,parent_id')->select();
    		$region_list = convert_arr_key($region_list,'id');
    		S('region_list',$region_list);
    	}
    	
    	return $region_list ? $region_list : S('region_list');
    }
    
    /**
     *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
     * @param type $goods_id  商品id
     * @param type $width     生成缩略图的宽度
     * @param type $height    生成缩略图的高度
     */
    function goods_thum_images($id,$width,$height){
    	if(empty($id))
    		return '';
    		//判断缩略图是否存在
    		$path = "Upload/goods/thumb/$id/";
    		$goods_thumb_name ="goods_thumb_{$id}_{$width}_{$height}";
    		
    		// 这个商品 已经生成过这个比例的图片就直接返回了
    		if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
    		if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
    		if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
    		if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
    		
    		$pic = M('Shop_goods')->where("id = $id")->getField('pic');
    		if($pic>0) {
    			$listpic = getPic($pic);
    			$original_img = $listpic['imgurl'];
    		} else {
    			$original_img = '';
    		}
    		if(empty($original_img)) return '';
    		
    		$original_img = '.'.$original_img; // 相对路径
    		if(!file_exists($original_img)) return '';
    		
    		$image = new \Think\Image();
    		$image->open($original_img);
    		
    		$goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    		//生成缩略图
    		if(!is_dir($path))
    			mkdir($path,0777,true);
    			
    			$image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    			
    			return '/'.$path.$goods_thumb_name;
    }
    /*
     * 获取子地区id
     */
    function get_region_child_ids($parent_id){
    	$ids = '';
    	$region_list = M('region')->where("parent_id=".$parent_id)->field('id')->select();
    	foreach($region_list as $k => $v) {
    		$ids .= $v['id'].',';
    	}
    	if($ids != '') {
    		$ids = trim($ids, ',');
    	}
    	return $ids;
    }
    
    function myTrim($str)
    {
    	$search = array(" ","　","\n","\r","\t");
    	$replace = array("","","","","");
    	return str_replace($search, $replace, $str);
    }