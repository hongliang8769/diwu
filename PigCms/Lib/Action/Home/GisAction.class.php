<?php

class GisAction extends Action
{ 
	public function index()
	{
		$this->redirect('index');
	}
	public function ad()
	{
/*		$raw_post_data = file_get_contents('php://input', 'r');
		echo "-------\$specGPS------------------<br/>";
		echo var_dump(self::getJson('specGPS')) . "<br/>";
		echo "-------\$specOBD------------------<br/>";
		echo var_dump(self::getJson('specOBD')) . "<br/>";
		echo "-------\$specCODE------------------<br/>";
		echo var_dump(self::getJson('specCODE')) . "<br/>";
		echo "-------php://specURL-------------<br/>";
		echo var_dump(self::getJson('specURL')) . "<br/>";
		echo "-------php://specJQM-------------<br/>";
		echo var_dump(self::getJson('specJQM')) . "<br/>";
		echo "-------php://input-------------<br/>";
		echo $raw_post_data. "<br/>";
		exit();*/
		
		$result1=self::get_code();
		if(!empty($result1))
		{
			echo self::encode($result1);
		}
		$result2=self::get_bangURL();
		if(!empty($result2))
		{
			echo self::encode($result2);
		}
		$result3=self::get_bangJQM();
		if(!empty($result3))
		{
			echo self::encode($result3);
		}
		$result4=self::insertGps();
		if(!empty($result4))
		{
			echo self::encode($result4);
		}
		$result5=self::insertObd();
		if(!empty($result5))
		{
			echo self::encode($result5);
		}
	}
	public function getSpecJSON($specJSON){
		$rawData =self::getJson($specJSON); 
		if(!$rawData) return ;
		$jsonData=self::decode(htmlspecialchars_decode($rawData));
		if(!$jsonData)  return array('flag' => 'fail','message' => '规格值不符合标准');
		return $jsonData;
	}

	public function insertGps(){
		$jsonData=self::getSpecJSON('specGPS');
		if(!$jsonData) return ;
		$carid=self::getgiscarid($jsonData['mcode']);
		if(!empty($jsonData['gps'])){
			foreach($jsonData['gps'] as $k => $val){
				$jData[$k]['lng']=$val['lng'];
				$jData[$k]['lat']=$val['lat'];
				$jData[$k]['carinfoid']=$carid;
				$jData[$k]['ctime']=date("Y-m-d H:i",$val['ctime']);
			}
			$Gis=D('gisgps');
			if($Gis->addAll($jData))
			{
				return  array('flag' => 'OK','message' => 'GPS成功上传！');
			}else{
				return array('flag' => 'fail','message' => 'GPS上传失败！');
			}
		}
		//return  array('flag' => 'fail','message' => '无GPS信息！');
	}
	public function insertObd(){
		$jsonData=self::getSpecJSON('specOBD');
		if(!$jsonData) return ;
		$carid=self::getgiscarid($jsonData['mcode']);
		if(!empty($jsonData['obd'])){
			foreach($jsonData['obd'] as $k => $val){
				$jData[$k]['carinfoid']=$carid;
				$jData[$k]['obd']=self::encode($val['obd']);
				$jData[$k]['ctime']=date("Y-m-d H:i",$val['ctime']);
			}
			$Gis=D('gisobd');
			if($Gis->addAll($jData))
			{
				return  array('flag' => 'OK','message' => 'OBD成功上传！');
			}else{
				return array('flag' => 'fail','message' => 'OBD上传失败！');
			}
		}
		//return  array('flag' => 'fail','message' => '无OBD信息！');
	}
	public function get_code(){
		//$token='cqddtu1426832772';
		$jsonData=self::getSpecJSON('specCODE');
		if(!$jsonData) return ;
		if(empty($jsonData['mlist'])){return  array('flag' => 'fail','message' => '无二维码信息！');}
		$mlist=$jsonData['mlist'];
		$token=$jsonData['token'];
		
		foreach($mlist as $k => $data){
			$mcode=$data['mcode'];
			$mysql=M('giscarinfo');
			$check=$mysql->field('id')->where($data)->find();
			$post=self::getcoderulStr($mcode,$token);
			$rejson[$k]=$post;
			$post['token']=$token;
			$post['mcode']=$mcode;
			if($check==false){
				$result=$mysql->add($post);			
			}else{
				$update=$mysql->where(array('id'=>$check['id']))->save($post);
			}
			//var_dump($mysql->getLastSql()); exit();
		}
		return $rejson;
	}
	public function get_bangURL($spec='specURL',$type='url'){
		$jsonData=self::getSpecJSON($spec);
		if(!$jsonData) return ;
		if(empty($jsonData[$type])){return  array('flag' => 'fail','message' => '无效数据！');}
		$tel=$jsonData['tel'];
		$token=$jsonData['token'];
		$infoWhere=array($type=>$jsonData[$type]);
		$gisCar=new GisCar($token,'',$tel);
		$mcode=$gisCar->getPhoneBang($infoWhere);
		if($mcode) {return  array('flag' => 'OK','mcode' => $mcode);}
		return  array('flag' => 'fail','message' => '绑定失败！');
	}
	public function get_bangJQM(){
		return self::get_bangURL('specJQM','mcode');
	}
	public  function getgiscarid($mcode)
	{
		$data=array('mcode'=>$mcode);
		$mysql=M('giscarinfo');
		$check=$mysql->field('id')->where($data)->find();
		if($check==false){
		   return $mysql->add($data);
		}else{
			return $check['id'];
		}
		//echo self::encode(array('flag' => 'fail','message' => '写入或读取carinfo失败！'));
	}
	public  function getJson($key, $type=false)
	{
		//默认方式
		if($type==false)
		{
			if(isset($_GET[$key])) return $_GET[$key];
			else if(isset($_POST[$key])) return $_POST[$key];
			else return null;
		}

		//get方式
		else if($type=='get' && isset($_GET[$key]))
			return $_GET[$key];

		//post方式
		else if($type=='post' && isset($_POST[$key]))
			return $_POST[$key];

		//无匹配
		else
			return null;

	}
	public  function encode($param)
	{
		if(version_compare(phpversion(),'5.4.0') >= 0)
		{
			return json_encode($param,JSON_UNESCAPED_UNICODE);
		}

		$result = '';
		if(function_exists('json_encode'))
		{
			$result = json_encode($param);
		}
		//对于中文的转换
		return preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $result);
	}

	public  function decode($string)
	{
		if(strpos($string,"\t") !== false)
		{
			$string = str_replace("\t",'',$string);
		}

		if(function_exists('json_decode'))
		{
			return json_decode($string,true);
		}

		return $string;
	}

	public function getcoderul($carinfoid,$strtoken){
		$actoken=new Token($strtoken);
		$access_token=$actoken->getAccessToken();//var_dump($access_token);exit(33);
		$qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
		//{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
		$data['action_name']='QR_SCENE';
		$data['expire_seconds']=604800;
		$data['action_info']['scene']['scene_id']=$carinfoid;
		$post=$this->api_notice_increment($qrcode_url,json_encode($data));

		return $post;
	}
	public function getcoderulStr($mcode,$strtoken){
		$actoken=new Token($strtoken);
		$access_token=$actoken->getAccessToken();
		$qrcode_url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
		//{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
		$data['action_name']='QR_LIMIT_STR_SCENE';
		$data['action_info']['scene']['scene_str']=$mcode.',';
		$post=$this->api_notice_increment($qrcode_url,json_encode($data));

		return $post;
	}
	function api_notice_increment($url, $data){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		$errorno=curl_errno($ch);
		if ($errorno) {
			 echo self::encode(array('flag' => 'fail','message' => '发生错误：curl error'.$errorno));exit();
			 exit();
			
		}else{

			$js=json_decode($tmpInfo,1);
			
			if (!$js['errcode']){
				return $js;
			}else {
				 echo self::encode(array('flag' => 'fail','message' => '发生错误：错误代码'.$js['errcode'].',微信返回错误信息：'.$js['errmsg']));exit();
				 exit();
			}
		}
	}
	function curlGet($url){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
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

}
