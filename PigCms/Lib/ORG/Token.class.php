<?php
class Token {
  private $appId;
  private $appSecret;
  private $wxuser;

  public function __construct($token) {
	  if(!empty($token)){
		  $WxUser=M('Wxuser')->where(array('token'=>$token))->find();
		  if($WxUser){
			  $this->wxuser=$WxUser;
			  $this->appId = $WxUser['appid'];
			  $this->appSecret = $WxUser['appsecret'];
		  }
	  }
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }
public function getCardSignPackage($signdata = array(),$nonce=0) {
    $cardapiTicket = $this->getCardApiTicket();
    $timestamp = time();
    $nonce_str = $this->createNonceStr();

	array_push($signdata, (string)$cardapiTicket);
	array_push($signdata, (string)$timestamp);
	if($nonce)array_push($signdata, (string)$nonce_str);
	
	sort( $signdata, SORT_STRING );
	$signature= sha1( implode( $signdata ) );
		
    $signPackage = array(
      "appId"     => $this->appId,
      "nonce_str"  => $nonce_str,
      "timestamp" => $timestamp,
      "signature" => $signature
    );

    return $signPackage; 
  }
  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    if ($this->wxuser['jsapi_expires_in'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        	$data['jsapi_expires_in'] = (time() + 7000);
			$data['jsapi_ticket'] = $ticket;
			M('Wxuser')->where(array('appid'=>$this->appId))->save($data);
      }
    } else {
      $ticket = $this->wxuser['jsapi_ticket'];
    }

    return $ticket;
  }
  private function getCardApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    if ($this->wxuser['cardapi_expires_in'] < time()) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      //$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
	  $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$accessToken&type=wx_card";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        	$data['cardapi_expires_in'] = (time() + 7000);
			$data['cardapi_ticket'] = $ticket;
			M('Wxuser')->where(array('appid'=>$this->appId))->save($data);
      }
    } else {
      $ticket = $this->wxuser['cardapi_ticket'];
    }

    return $ticket;
  }
  public	function clearAccessToken(){
	  $data=array();
	  $data['token_expires_in'] = (time() - 7000);
	  M('Wxuser')->where(array('appid'=>$this->appId))->save($data);
	  return true;
  }
   public	function clearAccessToken2($appId){
	   $this->appId =$appId;
	  $this->clearAccessToken();
	  return true;
  }
  public	function getAccessToken(){
	  if ($this->wxuser['token_expires_in'] < time()) {
		  $url_get= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
		  $json=json_decode($this->httpGet($url_get));
		  $access_token = $json->access_token;
		  
		  if ($access_token) {
			$data['token_expires_in'] = (time() + 7000);
			$data['access_token'] = $access_token;
			M('Wxuser')->where(array('appid'=>$this->appId))->save($data);
		  }
	  }else {
		  $access_token = $this->wxuser['access_token'];
	  }
	  return $access_token;
  }
 public function isadmin($wecha_id){
		$access_token=$this->getAccessToken();
		$url2='https://api.weixin.qq.com/cgi-bin/groups/getid?access_token='.$access_token;
		$classData=json_decode($this->curlGet($url2,'post','{
										  "openid":"'.$wecha_id.'"
										  }'
								));
		if($classData->groupid<1){
			return array('level'=>-1,'msg'=>'无访问权限');
		}
		if($classData->groupid==106){//"超级管理员"
			return array('level'=>1,'msg'=>'超级管理员');
		}if($classData->groupid==107){//"店面管理员"
			return array('level'=>2,'msg'=>'店面管理员');
		}else{
			return array('level'=>-1,'msg'=>'无访问权限');
		}
		
		
 } 
  private function curlGet($url,$method='get',$data=''){
	  $ch = curl_init();
	  $header = "Accept-Charset: utf-8";
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	  curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	  curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  $temp = curl_exec($ch);
	  return $temp;
  }	
  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

