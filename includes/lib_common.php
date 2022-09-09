<?php
function httpGet($url, $data){
    $u = $url.'?'.http_build_query($data);
    $ch = curl_init();
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $u);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    return $output;
}

function httpPost($url, $data){
    debug($url.'?'.http_build_query($data));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
function posturl($url, $apiParam,$method='POST') {
  	$bodyStr = $apiParam == NULL ? "" : json_encode($apiParam);

//echo $url;
  	$options = array(
    	'http' => array(
        'header'  => "Content-type: application/json; charset=utf-8",
        'method'  => $method,
        'content' => $bodyStr
			)
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if($result === FALSE){
			//throw new Exception("请求gateway异常");
			return json_decode("{ok:false,msg:\"请求gateway异常\"}",true);
		}

		return json_decode($result,true);
  }
function GUID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function getToken()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}
function get_all_header()
  {
    // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
    $ignore = array('host','accept','content-length','content-type');
 
    $headers = array();
    //这里大家有兴趣的话，可以打印一下。会出来很多的header头信息。咱们想要的部分，都是‘http_'开头的。所以下面会进行过滤输出。
/*    var_dump($_SERVER);
    exit;*/
 
    foreach($_SERVER as $key=>$value){
      if(substr($key, 0, 5)==='HTTP_'){
      //这里取到的都是'http_'开头的数据。
      //前去开头的前5位
        $key = substr($key, 5);
        //把$key中的'_'下划线都替换为空字符串
        $key = str_replace('_', ' ', $key);
        //再把$key中的空字符串替换成‘-’
        $key = str_replace(' ', '-', $key);
        //把$key中的所有字符转换为小写
        $key = strtolower($key);
 
    //这里主要是过滤上面写的$ignore数组中的数据
        if(!in_array($key, $ignore)){
          $headers[$key] = $value;
        }
      }
    }
//输出获取到的header
    return $headers;
 
  }
  
function getRandomNumber($len) 
{ 
  $chars_array = array( 
    "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
  ); 
  $charsLen = count($chars_array) - 1; 
  
  $outputstr = ""; 
  for ($i=0; $i<$len; $i++) 
  { 
    $outputstr .= $chars_array[mt_rand(0, $charsLen)]; 
  } 
  return $outputstr; 
} 

function debug($data, $file=null, $level='debug'){
	
    if(empty($file)){
        $path = ROOT_PATH.'logs/'.date('Y-m-d').'.log';
    }else{
        $path = ROOT_PATH.'logs/'.$file;
    }
    if(is_object($data) || is_array($data)){
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
    }
    $time = date('Y-m-d H:i:s');
	//echo $path;
	//echo $time.' '.$level.' '.$data.PHP_EOL;
    file_put_contents($path,$time.' '.$level.' '.$data.PHP_EOL, FILE_APPEND);
}

function merge_spaces ( $string )
{
    return preg_replace ( "/\s(?=\s)/","\\1", $string );
}

/**  
  * 获取某年第几周的开始日期和结束日期  
   * @param int $year  
   * @param int $week 第几周;  
   */   
function weekday($year,$week=1){   
      $year_start = mktime(0,0,0,1,1,$year);   
      $year_end = mktime(0,0,0,12,31,$year);   

      // 判断第一天是否为第一周的开始   
      if (intval(date('W',$year_start))===1){   
          $start = $year_start;//把第一天做为第一周的开始   
      }else{   
          $week++;   
          $start = strtotime('+1 monday',$year_start);//把第一个周一作为开始   
      }   

      // 第几周的开始时间   
      if ($week===1){   
          $weekday['start'] = $start;   
      }else{   
          $weekday['start'] = strtotime('+'.($week-0).' monday',$start);   
      }   

      // 第几周的结束时间   
      $weekday['end'] = strtotime('+1 sunday',$weekday['start']);   
      if (date('Y',$weekday['end'])!=$year){   
          $weekday['end'] = $year_end;   
      }   
      return $weekday;   
  }  

  function diffBetweenTwoDays ($day1, $day2)
{
  $second1 = strtotime($day1);
  $second2 = strtotime($day2);
    
  if ($second1 < $second2) {
    $tmp = $second2;
    $second2 = $second1;
    $second1 = $tmp;
  }
  return ($second1 - $second2) / 86400;
}


/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function addslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 * @author   Xuan Yan
 *
 * @return   mix                  对象或者数组
 */
function addslashes_deep_obj($obj)
{
    if (is_object($obj) == true)
    {
        foreach ($obj AS $key => $val)
        {
            $obj->$key = addslashes_deep($val);
        }
    }
    else
    {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

//======================
//长期 的转为3000年1月1日
//======================
function getRealTime($exptime)
{
	if($exptime=='长期'){
		return '3000-01-01';
	}else{
		return $exptime;
	}
}

function utf8_strlen($string = null) {
// 将字符串分解为单元
preg_match_all("/./us", $string, $match);
// 返回单元个数
return count($match[0]);
}

?>