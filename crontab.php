<?php
crontab('定时分红',"http://sheep.ssslsdx.top/Home/index/fenhong");

function crontab($content = '未知操作', $url, $rootUrl = "/wwwroot/web/sheep/public_html/Data"){
	global $_GET;
	$str = "";
	$str .= "──────── " . date('Y-m-d H:i:s') . " ────────" . PHP_EOL;
	$str .= $content . PHP_EOL;
	$str .= curl($url) . PHP_EOL;
	$str .= "──────────── END ────────────" . PHP_EOL;
	$filename = date('Y-m-d') . '.log';
	$fp = fopen($rootUrl . '/crontab' . $filename, 'a+');
	fwrite($fp, $str);
	fclose($fp);
}

function curl($url, $timeout = 5){
	$ch = curl_init();

	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	$file_contents = curl_exec($ch);
	curl_close($ch);

	return $file_contents;
}
