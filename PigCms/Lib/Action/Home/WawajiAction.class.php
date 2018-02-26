<?php

class WawajiAction extends Action
{ 
	//密钥
	private $_secrect_key;
	private  $app_id;
	private  $iv;
	private $token;
	public function __construct(){
		$this->_secrect_key ='1a80755fd96649ffa3f1bf6ff457a774';
		$this->app_id='4217531404';
		$this->token='ulqmjx1516605489';
	}	
	public function getToken()
	{
		$app_id='4217531404';
		$idname=$this->_get('id_name','trim');
		$expired_add=7200;
		$app_key ='0xcd,0xe6,0x76,0x82,0x06,0x27,0x3b,0x7b,0x3f,0x0f,0xe3,0xa5,0x6d,0xa2,0xce,0x6f,0x99,0xa8,0xec,0x00,0x14,0x0c,0x79,0x1f,0x9b,0x3a,0xe1,0x6a,0x36,0x30,0x2f,0xb1';
		$nonce = uniqid();
		$expired = time() + $expired_add; //单位:秒
		
		$app_key = str_replace("0x", "", $app_key);
		$app_key = str_replace(",", "", $app_key);
		if(strlen($app_key) < 32) {
			return false;
		}
		$app_key_32 = substr($app_key, 0, 32);
		
		$source = $app_id.$app_key_32.$idname.$nonce.$expired;
		$sum = md5($source);

		$tokenInfo = [
				'ver' => 1,
				'hash'  => $sum,
				'nonce' => $nonce,
				'expired' => $expired,
		];
		$token = base64_encode(json_encode($tokenInfo));
		echo $token;
	}
	public function pay()
	{
		//http://my.newsmy-car.com/index.php?g=Home&m=Wawaji&a=pay&app_id=4217531404&id_name=oExb0s6SyE8CuGjqRPCbpBexDfeA&session_id=4740a34ad86c4df78023b4bd36f16371&confirm=1&time_stamp=1517306817388&item_type=item_type1&item_price=6
		$session_id = $this->_get('session_id','trim');
		$time_stamp = $this->_get('time_stamp','trim');
		$confirm    = $this->_get('confirm','trim');
		$game_config=array(		
				'game_time'       => 30,  // 游戏总时长
				'claw_power_grab' => 67,  // 表示抓起爪力(1—100)，指下爪时，抓住娃娃的爪力，建议这个值设置大一点
				'claw_power_up'   => 33,  // 表示到顶爪力(1—100)，指天车提起娃娃到 up_height 指定的高度后将使用该爪力值直至天车到达顶部
				'claw_power_move' => 21,  // 表示移动爪力(1—100)，指天车到达顶部后，移动过程中的爪力
				'up_height'       => 7,   // 抓起高度（0–10）底部到顶部分成10份，爪子到达该值指定的高度时就会将爪力减小至到顶爪力
				);
		$authority_info=array(
				'session_id'   => $session_id,          // 同信令中 session_id 值
				'confirm'      => intval($confirm),     // 同信令中 confirm 值
				'time_stamp'   => (float)$time_stamp,  // 同信令中 time_stamp 值
				'custom_token' => $this->app_id,        // 业务侧自定义鉴权信息，会在游戏结果加密串中带回，用于实现自定义校验, 如支付信息等，该字段长度不要超过 300 个字符
		);
		$config=array('game_config'=>$game_config,'authority_info'=>$authority_info);
		$configjson=json_encode($config);
		$outstr=$this->EncryptConfig($configjson);
		//echo $this->iv;
		//echo '<br>';
		//echo $configjson;
		//echo '<br>';
		
		echo $outstr;
	}
	private function str_rand($length = 32, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
		if(!is_int($length) || $length < 0) {
			return false;
		}
		
		$string = '';
		for($i = $length; $i > 0; $i--) {
			$string .= $char[mt_rand(0, strlen($char) - 1)];
		}
		
		return $string;
	}
	public function roomlist(){
		$appid =$this->_get('appid','trim');
		$where = array('token' => $this->token);
		$outstr='';
		$roomlist=array();
		if ($this->app_id!=$appid ) {
			$outstr=json_encode(array());
		}
		$list = M('Wawaji')->where($where)->order('id desc')->select();
		foreach($list as $key => $value) {
			$roomlist[]=array(
				'room_id'=>$value['roomid'],
				'room_name'=>$value['title'],
				'anchor_id_name'=>'n'.$value['roomid'],
				'anchor_nick_name'=>'u'.$value['roomid'],
				'stream_info'=>array(
						array('stream_id'=>'WWJ_ZEGO_STREAM_32a0f718e123_2'),
						array('stream_id'=>'WWJ_ZEGO_STREAM_32a0f718e123')
				)
			);
		}
		$data=array(
			'code'=>0,
			'data'=>array('room_list'=>$roomlist),
			"message"=>"success"
		);
		$outstr=json_encode($data);
		echo $outstr;
		
	}
	public function index(){
		$plaintext = "This string was AES-256 / CBC / ZeroBytePadding encrypted.";
		$ciphertext = $this->EncryptConfig($plaintext);
		
		echo $ciphertext;
		echo '<br>';
		$encrypt_str =$this->DecryptConfig($ciphertext);
		echo $encrypt_str;
		
	}
	/**
	 * 加密方法
	 * @param string $str
	 * @return string
	 */
	public function EncryptConfig($str){
		//AES, 128 ECB模式加密数据
		$screct_key = $this->_secrect_key;
		$str = trim($str);
		$str = $this->addPKCS7Padding($str);
		//$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),MCRYPT_RAND);
		$iv=$this->str_rand(16);
		$this->iv=$iv;
		$encrypt_str =  mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC, $iv);
		
		return base64_encode($iv.$encrypt_str);
	}
	
	/**
	 * 解密方法
	 * @param string $str
	 * @return string
	 */
	public function DecryptConfig($str){
		//AES, 128 ECB模式加密数据
		$screct_key = $this->_secrect_key;
		$str = base64_decode($str);
		$iv=substr($str,0,16);
		$str=substr($str,16);
		
		//$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),MCRYPT_RAND);
		$encrypt_str =  mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $screct_key, $str, MCRYPT_MODE_CBC, $iv);
		$encrypt_str = trim($encrypt_str);
		$encrypt_str = $this->stripPKSC7Padding($encrypt_str);
		return $encrypt_str;
	}
	
	/**
	 * 填充算法
	 * @param string $source
	 * @return string
	 */
	private function addPKCS7Padding($source){
		$source = trim($source);
		$block = mcrypt_get_block_size('rijndael-128', 'cbc');
		$pad = $block - (strlen($source) % $block);
		if ($pad <= $block) {
			$char = chr($pad);
			$source .= str_repeat($char, $pad);
		}
		return $source;
	}
	/**
	 * 移去填充算法
	 * @param string $source
	 * @return string
	 */
	private function stripPKSC7Padding($source){
		$source = trim($source);
		$char = substr($source, -1);
		$num = ord($char);
		if($num==62)return $source;
		$source = substr($source,0,-$num);
		return $source;
	}
	
}
