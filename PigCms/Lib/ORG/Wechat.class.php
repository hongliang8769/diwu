<?php
class Wechat {
	public $token;
	public $wxuser;
	public $pigsecret;
	private $data = array();
	public function __construct($token, $wxuser = '') {
		$this->token = $token;
		if (!$wxuser) {
			$wxuser = M('wxuser')->where(array('token' => $this->token))->find();
		}
		$this->wxuser = $wxuser;
		if (!$this->wxuser['pigsecret']) {
			$this->pigsecret = $this->token;
		}else {
			$this->pigsecret = $this->wxuser['pigsecret'];
		}
		if (! empty ( $_GET ['echostr'] ) && ! empty ( $_GET ["signature"] ) && ! empty ( $_GET ["nonce"] )) {
			$this->auth($this->pigsecret) || exit;
			echo($_GET['echostr']);
			exit;
		}else {
			$xml = file_get_contents("php://input");
			if ($this->wxuser['encode'] == 2) {
				$this->data = $this->decodeMsg($xml);
			}else {
				$xml = new SimpleXMLElement($xml);
				$xml || exit;
				foreach ($xml as $key => $value) {
					$this->data[$key] = strval($value);
				}
			}
		}
	}
	public function encodeMsg($sRespData) {
		$sReqTimeStamp = time();
		$sReqNonce = $_GET['nonce'];
		$encryptMsg = "";
		import("@.ORG.aes.WXBizMsgCrypt");
		$pc = new WXBizMsgCrypt($this->pigsecret, $this->wxuser['aeskey'], $this->wxuser['appid']);
		$sRespData = str_replace('<?xml version="1.0"?>', '', $sRespData);
		$errCode = $pc->encryptMsg($sRespData, $sReqTimeStamp, $sReqNonce, $encryptMsg);
		if ($errCode == 0) {
			return $encryptMsg;
		}else {
			return $errCode;
		}
	}
	public function decodeMsg($msg) {
		import("@.ORG.aes.WXBizMsgCrypt");
		$sReqMsgSig = $_GET['msg_signature'];
		$sReqTimeStamp = $_GET['timestamp'];
		$sReqNonce = $_GET['nonce'];
		$sReqData = $msg;
		$sMsg = "";
		$pc = new WXBizMsgCrypt($this->pigsecret, $this->wxuser['aeskey'], $this->wxuser['appid']);
		$errCode = $pc->decryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
		if ($errCode == 0) {
			$data = array();
			$xml = new SimpleXMLElement($sMsg);
			$xml || exit;
			foreach ($xml as $key => $value) {
				$data[$key] = strval($value);
			}
			return $data;
		}else {
			return $errCode;
		}
	}
	/**
	 * ��ȡ΢�����͵�����
	 * @return array ת��Ϊ����������
	 */	
	public function request() {
		return $this->data;
	}
	/**
	 * * ��Ӧ΢�ŷ��͵���Ϣ���Զ��ظ���
	 * @param  string $to      �����û���
	 * @param  string $from    �������û���
	 * @param  array  $content �ظ���Ϣ���ı���ϢΪstring����
	 * @param  string $type    ��Ϣ����
	 * @param  string $flag    �Ƿ��±�ս��ܵ�����Ϣ
	 * @return string          XML�ַ���
	 */
	public function response($content, $type = 'text', $flag = 0) {
		$this->data = array('ToUserName' => $this->data['FromUserName'], 'FromUserName' => $this->data['ToUserName'], 'CreateTime' => NOW_TIME, 'MsgType' => $type,);
		/* ����������� */
		$this->$type($content);
		/* ���״̬ */
		$this->data['FuncFlag'] = $flag;
		/* ת������ΪXML */
		$xml = new SimpleXMLElement('<xml></xml>');
		$this->data2xml($xml, $this->data);
		if (isset($_GET['encrypt_type']) && $_GET['encrypt_type'] == 'aes') {
			//$this->requestdataext($this->encodeMsg($xml->asXML()));
			exit($this->encodeMsg($xml->asXML()));
		}else {
			//$this->requestdataext($xml->asXML());
			exit($xml->asXML());
		}
	}
	/**
	 * �ظ��ı���Ϣ
	 * @param  string $content Ҫ�ظ�����Ϣ
	 */
	private function text($content) {
		$this->data['Content'] = $content;
	}
	/**
	 * �ظ�������Ϣ
	 * @param  string $content Ҫ�ظ�������
	 */
	private function music($music) {
		list($music['Title'], $music['Description'], $music['MusicUrl'], $music['HQMusicUrl']) = $music;
		$this->data['Music'] = $music;
	}
	/**
	 * �ظ�ͼ����Ϣ
	 * @param  string $news Ҫ�ظ���ͼ������
	 */
	private function news($news) {
		$articles = array();
		foreach ($news as $key => $value) {
			list($articles[$key]['Title'], $articles[$key]['Description'], $articles[$key]['PicUrl'], $articles[$key]['Url']) = $value;
			if ($key >= 9) {
				break;
                        }//���ֻ����10������
		}
		$this->data['ArticleCount'] = count($articles);
		$this->data['Articles'] = $articles;
	}
	private function transfer_customer_service($content) {
		$this->data['Content'] = '';
	}
	private function data2xml($xml, $data, $item = 'item') {
		foreach ($data as $key => $value) {
                         /* ָ��Ĭ�ϵ�����key */
			is_numeric($key) && $key = $item;
                        /* �����Ԫ�� */
			if (is_array($value) || is_object($value)) {
				$child = $xml->addChild($key);
				$this->data2xml($child, $value, $item);
			}else {
				if (is_numeric($value)) {
					$child = $xml->addChild($key, $value);
				}else {
					$child = $xml->addChild($key);
					$node = dom_import_simplexml($child);
					$node->appendChild($node->ownerDocument->createCDATASection($value));
				}
			}
		}
	}
	public function requestdataext($content){
		$data['year']=date('Y');
		$data['month']=date('m');
		$data['day']=date('d');
		$data['token']=$this->token;
		$mysql=M('Requestdata');
		$check=$mysql->field('id')->where($data)->find();
		if($check==false){
			$data['time']=time();
			$data['content']=$content;
			$mysql->add($data);
		}else{
			$mysql->where($data)->save(array('content'=>$content,'time'=>time()));
		}
	}
	private function auth($token) {
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if (trim($tmpStr) == trim($signature)) {
			return true;
		}else {
			return false;
		}
	}
}

?>