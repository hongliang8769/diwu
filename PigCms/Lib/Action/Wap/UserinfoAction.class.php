<?php
class UserinfoAction extends WapAction{
	public function _initialize() {
		parent::_initialize();
		session('wapupload',1);
		if (!$this->wecha_id){
			$this->error('您无权访问','');
		}
		$member_card_create_db=M('Member_card_create');
		$cardsCount=$member_card_create_db->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->count();
		$this->assign('cardsCount',$cardsCount);
	}
public function addcar(){	
		$where['token']=$this->_get('token');
		$carnumber= $this->_post('shortname').$this->_post('carnumber');
		if($this->_post('carnumber')){
			$where['carnumber']=$carnumber;
			$userCarExist=M('Usercarinfo')->where($where)->find();
			if($userCarExist){
				$data['wecha_id']=$this->_get('wecha_id');
				$data['cartype']=$this->_post('cartype');
				D('Usercarinfo')->where($where)->save($data);
				$result=array('car'=>$userCarExist,'flag'=>'no');
				echo json_encode($result);
				exit();
			}else{
				$where['wecha_id']=$this->_get('wecha_id');
				$where['cartype']=$this->_post('cartype');
				D('Usercarinfo')->add($where);
			}
			$userCarExist=M('Usercarinfo')->where($where)->find();
			$result=array('car'=>$userCarExist,'flag'=>'ok');
		}else{
			$result=array('car'=>$userCarExist,'flag'=>'error');
		}
		
		echo json_encode($result);
		
	}
	public function delcar(){	
		$carid=$this->_post('carid');
		if(M('Usercarinfo')->where(array('id'=>$carid))->delete()){
			$result=array('flag'=>'ok');
		}else{
			$result=array('flag'=>'error');
		}
		
		echo json_encode($result);
		
	}
	public function savecar(){	
		$where['token']=$this->_get('token');
		$carid=$this->_post('carid');
		$carnumber= $this->_post('shortname').$this->_post('carnumber');
		if($this->_post('carnumber')){
			$where['carnumber']=$carnumber;
			$userCarExist=M('Usercarinfo')->where($where)->find();
			if($userCarExist&&$userCarExist['id']!=$carid){
				$result=array('car'=>$userCarExist,'flag'=>'no');
				echo json_encode($result);
				exit();
			}else{
				$data['wecha_id']=$this->_get('wecha_id');
				$data['cartype']=$this->_post('cartype');
				$data['carnumber']=$carnumber;
				M('Usercarinfo')->where(array('id'=>$carid))->save($data);
			}
			$userCarExist=M('Usercarinfo')->where(array('id'=>$carid))->find();
			$result=array('car'=>$userCarExist,'flag'=>'ok');
		}else{
			$result=array('car'=>$userCarExist,'flag'=>'error');
		}
		
		echo json_encode($result);
		
	}
	public function getuserinfo(){	
		$data['token']=$this->_get('token');
		$tel = $this->_post('tel');
		$carnumber= $this->_post('shortname').$this->_post('carnumber');
		if($tel){
			$data['tel']=$tel;
			$userinfo=M('Userinfo')->where($data)->find();
			if(!$userinfo){
				$userinfo=M('Userinfo')->where(array('token'=>$this->_get('token'),'wecha_id'=>$this->_get('wecha_id')))->find();
			}
			if($userinfo){
				$userCarExist=M('Usercarinfo')->where("token='".$this->_get('token')."' and wecha_id='".$userinfo['wecha_id']."'")->find();
				if($userCarExist){
					$userCarExist['shortname']=self::msubstr($userCarExist['carnumber'],0,1);
					$userCarExist['carnumber']=self::msubstr($userCarExist['carnumber'],1);
				}
			}
			
		}
		if($this->_post('carnumber')){
			$data['carnumber']=$carnumber;
			$userCarExist=M('Usercarinfo')->where($data)->find();
			if(!$userCarExist){
				$userCarExist=M('Usercarinfo')->where("token='".$this->_get('token')."' and wecha_id='".$this->_get('wecha_id')."'")->find();
			}
			if($userCarExist){
				$userinfo=M('Userinfo')->where("token='".$this->_get('token')."' and  wecha_id='".$userCarExist['wecha_id']."'")->find();
			}
		}
		

		$result=array('user'=>$userinfo,'car'=>$userCarExist,'flag'=>'ok');
		echo json_encode($result);
		
	}
	public function saveotherdata($oldwecha_id,$newwecha_id){
		$token=$this->token;
		$data=array('wecha_id'=>$newwecha_id);
		
		if(M('dish_order')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->find()){
			$dishorder = M('dish_order')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->save($data);
		}
		if(M('usercarinfo')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->find()){
			$carinfolist = M('usercarinfo')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->save($data);
		}
		if(M('Member_card_pay_record')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->find()){
			M('Member_card_pay_record')->where("token = '$token' AND wecha_id = '".$oldwecha_id."'")->save($data);
		}
		 
		
	}
public function saveUser(){
	
		$thisCard['is_check']='1';
		$telcheck=intval($this->_get('telcheck'));
		$infoWhere['wecha_id']=$this->_get('wecha_id');
		$infoWhere['token']=$this->_get('token');
		
		$data['wecha_id']=$this->_get('wecha_id');
		$data['token']=$this->_get('token');
		$data['wechaname'] = $this->_post('wechaname');
		$data['tel']       = $this->_post('tel');
		if(M('Member_card_custom')->where(array('token'=>$this->token))->getField('tel')){
			if(empty($data['tel'])){
				$this->error("手机号必填。");exit;
			}
		}
		$userCarExist=M('Usercarinfo')->where($infoWhere)->find();
		if(!$userCarExist){
			echo 8;exit;
		}

		 $this->_post('truename')? $data['truename'] = $this->_post('truename') : $data['truename'] = '';	
		 $this->_post('sex')? $data['sex'] = $this->_post('sex') : $data['sex'] = '';	

		 $this->_post('qq')? $data['qq'] = $this->_post('qq') : $data['qq'] = '';
		 $this->_post('beizhu')? $data['beizhu'] = $this->_post('beizhu') : $data['beizhu'] = '';
		 $this->_post('bornyear')? $data['bornyear'] = $this->_post('bornyear') : $data['bornyear'] = '';
		 $this->_post('bornmonth')? $data['bornmonth'] = $this->_post('bornmonth') : $data['bornmonth'] = '';
		 $this->_post('bornday')? $data['bornday'] = $this->_post('bornday') : $data['bornday'] = '';
		 $this->_post('portrait')? $data['portrait'] = $this->_post('portrait') : $data['portrait'] = '';
		 $this->_post('address')? $data['address'] = $this->_post('address') : $data['address'] = '';

		if($this->_post('paypass') != ''){
			$data['paypass'] = md5($this->_post('paypass'));
		}
		$userinfo=M('Userinfo')->where($infoWhere)->find();
	   if ($userinfo){
			  if(!($userinfo['tel']==$data['tel'])){
				  if($thisCard['is_check'] == '1'){
					  $code 	= $this->_post('code','trim,strtolower');
					  if($telcheck<1){
						  echo 6;exit;
					  }
					  if($this->_check_code($code) == false){
						  echo 5;exit;
					  }
				  }				  
			  }
			  M('Userinfo')->where($infoWhere)->save($data);
			 $this->saveotherdata($userInfoExist['wecha_id'],$infoWhere['wecha_id']);
		}else {//不匹配姓名，因为姓名没人记住，而且容易重名。
			  $telWhere=array('tel'=>$data['tel'],'token'=>$data['token']);
			  $userInfoExist=M('Userinfo')->where($telWhere)->find();
			  if ($userInfoExist){
				  	M('Userinfo')->where($telWhere)->save($data);
					$this->saveotherdata($userInfoExist['wecha_id'],$infoWhere['wecha_id']);
			  }else{
				   if($thisCard['is_check'] == '1'){
					  $code 	= $this->_post('code','trim,strtolower');
					  if($this->_check_code($code) == false){
						 echo 5;exit;
					  }
					  $data['getcardtime']=time();
					  M('Userinfo')->add($data);
				  }
			  }
		 }
		$cardinfo=M('Member_card_create')->where($infoWhere)->find();
		if(!$cardinfo){
			$userinfo=M('Userinfo')->where($infoWhere)->find();
			Sms::sendSms($this->token,'有新的会员领了会员卡');
			
			$card=M('Member_card_create')->field('id,number')->where(array('token'=>$this->_get('token'),'userinfoid'=>$userinfo['id']))->find();
			if($card){
				M('Member_card_create')->where(array('id'=>$card['id']))->save(array('wecha_id'=>$this->_get('wecha_id')));
				S('fans_'.$this->token.'_'.$this->_get('wecha_id'),NULL);
				echo 2;exit;
			}else{
				$cardset=M('Member_card_set')->field('id')->where(array('token'=>$this->_get('token')))->order('minimomey asc')->find();			
				$card=M('Member_card_create')->field('id,number')->where("token='".$this->_get('token')."' and cardid=".$cardset['id']." and wecha_id = ''")->order('id ASC')->find();
				if(!$card){
					echo 3;exit;
				}else {
					$card_up=M('Member_card_create')->where(array('id'=>$card['id']))->save(array('wecha_id'=>$this->_get('wecha_id'),'userinfoid'=>$userinfo['id']));
					$data['getcardtime']=time();
					S('fans_'.$this->token.'_'.$this->_get('wecha_id'),NULL);
					echo 2;exit;
				}
		    }

	     } else{//换领会员卡
		 	$userinfo=M('Userinfo')->where($infoWhere)->find();
			Sms::sendSms($this->token,'有新的会员领了会员卡');
			
			$card=M('Member_card_create')->field('id,number')->where(array('token'=>$this->_get('token'),'userinfoid'=>$userinfo['id']))->find();
			if($card){
				M('Member_card_create')->where(array('id'=>$card['id']))->save(array('wecha_id'=>$this->_get('wecha_id')));
				S('fans_'.$this->token.'_'.$this->_get('wecha_id'),NULL);
				echo 2;exit;
			}
		}
		 S('fans_'.$this->token.'_'.$this->_get('wecha_id'),NULL);
		 echo 1;exit;
	}
	public function index(){
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"icroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
		}
		$cardid=intval($this->_get('cardid'));
		$telcheck=intval($this->_get('telcheck'));
		
		$conf = M('Member_card_custom')->where(array('token'=>$this->token))->find();
		if($conf == NULL){
			$conf = array(
				'wechaname' => 1,
				'tel' => 1,
				'truename' => 1,
				'qq' => 1,
				'paypass' => 1,
				'portrait' => 1,
				'sex' => 1
			);
		}
		$this->assign('conf',$conf);
		
		$infoWhere['wecha_id']=$this->_get('wecha_id');
		$infoWhere['token']=$this->_get('token');
 
		$cardinfo=M('Member_card_create')->where($infoWhere)->find();
		$this->assign('cardInfo',$cardinfo);
		
		$thisCard=M('Member_card_set')->where(array('token'=>$this->_get('token'),'id'=>intval($_GET['cardid'])))->find();
		if (!$thisCard&&intval($_GET['cardid'])){
			$this->error('会员卡已经售罄!');
		}
		if($thisCard['memberinfo']!=false){
			$img=$thisCard['memberinfo'];			
		}else{
			$img='tpl/Wap/default/common/images/userinfo/fans.jpg';
		}
		
		$userinfo=M('Userinfo')->where($infoWhere)->find();
		if($userinfo){
			$allcar=M('Usercarinfo')->where($infoWhere)->select();
			foreach($allcar as $key=>$value){
			  $allcar[$key]['shortname']=self::msubstr($value['carnumber'],0,1);
			  $allcar[$key]['carnumber']=self::msubstr($value['carnumber'],1);
			}
		}
		$thisCard['is_check']='1';
		$this->assign('cardnum',$cardinfo['number']);
		$this->assign('is_check',$thisCard['is_check']);//选择后，用户领取会员卡时则必须验证，注：使用此功能必须购买短信服务)
		$this->assign('homepic',$img);
		$this->assign('info',$userinfo);
		$this->assign('allcar',$allcar);
		$this->assign('cardid',$cardid);
		//redirect url
		if (isset($_GET['redirect'])){
			$urlinfo=explode('|',$_GET['redirect']);
			$parmArr=explode(',',$urlinfo[1]);
			$parms=array('token'=>$infoWhere['token']);
			if ($parmArr){
				foreach ($parmArr as $pa){
					$pas=explode(':',$pa);
					$parms[$pas[0]]=$pas[1];
				}
			}
			if(empty($parms['wecha_id']))$parms['wecha_id']=$infoWhere['wecha_id'];
			$redirectUrl=U($urlinfo[0],$parms);
			$this->assign('redirectUrl',$redirectUrl);
		}
		if(IS_POST){
			$this->saveUser();
		}else{
			$this->display();
		}
    } //end function index
	public function saveUseradmin(){
		if($this->isadmin['level']>0){}else{
			$this->error('权限错误，不合法的操作');
		}
		$user_wecha_id=$this->_get('user_wecha_id');
		$thisCard['is_check']='1';
		$telcheck=intval($this->_get('telcheck'));
		$infoWhere['wecha_id']=$this->_get('user_wecha_id');
		$infoWhere['token']=$this->_get('token');
		
		$data['wecha_id']=$this->_get('user_wecha_id');
		$data['token']=$this->_get('token');
		$data['wechaname'] = $this->_post('wechaname');
		$data['tel']       = $this->_post('tel');
		if(M('Member_card_custom')->where(array('token'=>$this->token))->getField('tel')){
			if(empty($data['tel'])){
				$this->error("手机号必填。");exit;
			}
		}
		$userCarExist=M('Usercarinfo')->where($infoWhere)->find();
		if(!$userCarExist){
			echo 8;exit;
		}

		 $this->_post('truename')? $data['truename'] = $this->_post('truename') : $data['truename'] = '';	
		 $this->_post('sex')? $data['sex'] = $this->_post('sex') : $data['sex'] = '';	

		 $this->_post('qq')? $data['qq'] = $this->_post('qq') : $data['qq'] = '';
		 $this->_post('beizhu')? $data['beizhu'] = $this->_post('beizhu') : $data['beizhu'] = '';
		 $this->_post('bornyear')? $data['bornyear'] = $this->_post('bornyear') : $data['bornyear'] = '';
		 $this->_post('bornmonth')? $data['bornmonth'] = $this->_post('bornmonth') : $data['bornmonth'] = '';
		 $this->_post('bornday')? $data['bornday'] = $this->_post('bornday') : $data['bornday'] = '';
		 $this->_post('portrait')? $data['portrait'] = $this->_post('portrait') : $data['portrait'] = '';
		 $this->_post('address')? $data['address'] = $this->_post('address') : $data['address'] = '';

		if($this->_post('paypass') != ''){
			$data['paypass'] = md5($this->_post('paypass'));
		}
		$userinfo=M('Userinfo')->where($infoWhere)->find();
	   if ($userinfo){
			  M('Userinfo')->where($infoWhere)->save($data);
		}
		
		 S('fans_'.$this->token.'_'.$this->_get('user_wecha_id'),NULL);
		 echo 1;exit;
	}
	public function indexadmin(){
		
		if($this->isadmin['level']>0){}else{
			$this->error('权限错误，不合法的操作');
		}
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"icroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
		}
		$cardid=intval($this->_get('cardid'));
		$telcheck=intval($this->_get('telcheck'));
		
		
		$conf = M('Member_card_custom')->where(array('token'=>$this->token))->find();
		if($conf == NULL){
			$conf = array(
				'wechaname' => 1,
				'tel' => 1,
				'truename' => 1,
				'qq' => 1,
				'paypass' => 1,
				'portrait' => 1,
				'sex' => 1
			);
		}
		$this->assign('conf',$conf);

		$cardInfoRow['wecha_id']=$this->_get('user_wecha_id');
		$cardInfoRow['token']=$this->_get('token');
		//$cardInfoRow['cardid']=$this->_get('cardid');
		$card = D('Member_card_create'); 
		$cardinfo=$card->where($cardInfoRow)->find();
		$this->assign('cardInfo',$cardinfo);
		
		$member_card_set_db=M('Member_card_set');
		$thisCard=$member_card_set_db->where(array('token'=>$this->_get('token'),'id'=>intval($_GET['cardid'])))->find();
		if (!$thisCard&&intval($_GET['cardid'])){
			exit();
		}
		if($thisCard['memberinfo']!=false){
			$img=$thisCard['memberinfo'];			
		}else{
			$img='tpl/Wap/default/common/images/userinfo/fans.jpg';
		}
		$infoWhere['wecha_id']=$this->_get('user_wecha_id');
		$infoWhere['token']=$this->_get('token');
		$userinfo=D('Userinfo')->where($infoWhere)->find();
		if($userinfo){
			//echo $sql->getLastSql();exit;
			/*$where['token'] = $data['token'];		
			$map['wecha_id'] = $this->_get('wecha_id');
			$map['_logic'] = 'or';
			$where['_complex'] = $map;*/
			$allcar=M('Usercarinfo')->where($infoWhere)->select();
			foreach($allcar as $key=>$value){
			  $allcar[$key]['shortname']=self::msubstr($value['carnumber'],0,1);
			  $allcar[$key]['carnumber']=self::msubstr($value['carnumber'],1);
			}
		}
		$thisCard['is_check']='1';
		$this->assign('cardnum',$cardinfo['number']);
		$this->assign('is_check',$thisCard['is_check']);
		$this->assign('homepic',$img);
		$this->assign('info',$userinfo);
		$this->assign('user_wecha_id',$this->_get('user_wecha_id'));
		$this->assign('allcar',$allcar);
		$this->assign('cardid',$cardid);
		//redirect url
		if (isset($_GET['redirect'])){
			$urlinfo=explode('|',$_GET['redirect']);
			$parmArr=explode(',',$urlinfo[1]);
			$parms=array('token'=>$cardInfoRow['token']);
			if ($parmArr){
				foreach ($parmArr as $pa){
					$pas=explode(':',$pa);
					$parms[$pas[0]]=$pas[1];
				}
			}
			if(empty($parms['wecha_id']))$parms['wecha_id']=$this->wecha_id;
			$redirectUrl=U($urlinfo[0],$parms);
			$this->assign('redirectUrl',$redirectUrl);
		}
		if(IS_POST){
			$this->saveUseradmin();
			
		}else{
			$this->display();
		}

		
    } //end function index
   	public function addcode(){
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"icroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
		}
		$carinfoid=intval($this->_get('cardid'));
		$conf = M('Member_card_custom')->where(array('token'=>$this->token))->find();
		if($conf == NULL){
			$conf = array(
				'wechaname' => 1,
				'tel' => 1,
				'truename' => 1,
				'qq' => 1,
				'paypass' => 1,
				'portrait' => 1,
				'sex' => 1,
				'bornyear' => 1,
				'bornmonth' => 1,
				'bornday' => 1
			);
		}
		$this->assign('conf',$conf);
		
		$data['wecha_id']=$this->_get('wecha_id');
		$data['token']=$this->_get('token');

		$userinfo=D('Userinfo')->where($data)->find();

		$car = D('gisgl'); 
		$carInfoRow['tp_gisgl.userinfoid']=intval($userinfo['id']);
		$carInfoRow['tp_gisgl.carinfoid']=$carinfoid;
		$carinfo=$car->join('tp_giscarinfo   ON tp_gisgl.carinfoid = tp_giscarinfo.id')->where($carInfoRow)->find(); 
		$this->assign('cardInfo',$carinfo);
		


		$img='tpl/Wap/default/common/images/userinfo/fans.jpg';
		$this->assign('is_check',0);
		$this->assign('homepic',$img);
		$this->assign('info',$userinfo);
		$this->assign('cardid',$carinfoid);
		//redirect url
		if (isset($_GET['redirect'])){
			$urlinfo=explode('|',$_GET['redirect']);
			$parmArr=explode(',',$urlinfo[1]);
			$parms=array('token'=>$data['token'],'wecha_id'=>$data['wecha_id']);
			if ($parmArr){
				foreach ($parmArr as $pa){
					$pas=explode(':',$pa);
					$parms[$pas[0]]=$pas[1];
				}
			}
			$redirectUrl=U($urlinfo[0],$parms);
			$this->assign('redirectUrl',$redirectUrl);
		}
		//
		if(IS_POST){
			//如果有post提交，说明是修改
			$data['wechaname'] = $this->_post('wechaname');
			$data['tel']       = $this->_post('tel');
			if(M('Member_card_custom')->where(array('token'=>$this->token))->getField('tel')){
				if(empty($data['tel'])){
					$this->error("手机号必填。");exit;
				}
			}

			
			 $this->_post('truename')? $data['truename'] = $this->_post('truename') : $data['truename'] = '';	
			 $this->_post('sex')? $data['sex'] = $this->_post('sex') : $data['sex'] = '';	
			 
			 $this->_post('qq')? $data['qq'] = $this->_post('qq') : $data['qq'] = '';
			 $this->_post('bornyear')? $data['bornyear'] = $this->_post('bornyear') : $data['bornyear'] = '';
			 $this->_post('bornmonth')? $data['bornmonth'] = $this->_post('bornmonth') : $data['bornmonth'] = '';
			 $this->_post('bornday')? $data['bornday'] = $this->_post('bornday') : $data['bornday'] = '';
			 $this->_post('portrait')? $data['portrait'] = $this->_post('portrait') : $data['portrait'] = '';
			 

			if($this->_post('paypass') != ''){
				$data['paypass'] = md5($this->_post('paypass'));
			}
			//如果会员卡不为空[更新]
 			//写入两个表 Userinfo Member_card_create 
 				
			$infoWhere=array();
			$infoWhere['wecha_id']=$data['wecha_id'];
			$infoWhere['token']=$data['token'];
			$userInfoExist=M('Userinfo')->where($infoWhere)->find();
			if ($userInfoExist){
				M('Userinfo')->where($infoWhere)->save($data);
			}else {
				M('Userinfo')->add($data);
			}
			S('fans_'.$this->token.'_'.$this->wecha_id,NULL);
			echo 1;exit;
		}
		$this->display();	
	}
	function _create_code(){
		return rand(100000,999999);
	}
    function get_code(){
    	$code_db 	= M('Sms_code');
		$action 	= GROUP_NAME.'-'.MODULE_NAME;
    	$code 		= $this->_create_code();
    	$phone 		= $this->_post('phone');
    	$data['code'] 			= $code;
    	$data['token'] 			= $this->token;
    	$data['wecha_id'] 		= $this->wecha_id;
    	$data['create_time'] 	= time();
		$data['is_use']         = '0'; 
    	$data['action'] 		= $action ;
    	
    	
    	$result 	= array();
    	$where 		= array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'action'=>$action);
    	$last_info 	= $code_db->where($where)->order('create_time desc')->find();
    	if(($last_info['create_time']+60) > time()){
    		$result['error']	= -1;
    		$result['info']		= '请不要频繁获取效验码';
    	}else{
			$where['is_use']='0'; 
    		$codedb=$code_db->where($where)->save(array('is_use'=>'1'));
    		if($code_db->add($data)){
    			$msg 	= '您的验证码为：'.$code.'，5分钟内有效，谢谢！【星意汽车】';
    			$result['error']	= 0;
    			$result['info']		= '';
    			
    			Sms::sendSms($this->token,$msg,$phone);
    		}
    		
    	}
    	
    	echo json_encode($result);
    }
    
    /* @param  intval length 效验码长度
     * @param  string type  效验码类型  number数字, string字母, mingle数字、字母混合
     * @return string
     */
	function randString($length=4,$type="number"){
		$array = array(
			'number' => '0123456789',
			'string' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			'mixed' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		);
		$string = $array[$type];
		$count = strlen($string)-1;
		$rand = '';
		for ($i = 0; $i < $length; $i++) {
			$rand .= $string[mt_rand(0, $count)];
		}
		return $rand;
	}
    /* @param  string code 效验码
     * @param  string time 过期时间
     * @return boolean
     */
    function _check_code($code,$time=300){
    	$code_db 	= M('Sms_code');
		$action 	= GROUP_NAME.'-'.MODULE_NAME;
    	$last_time 	= time()-$time;
    	$where 		= array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'action'=>$action,'is_use'=>'0','create_time'=>array('gt',$last_time));
    	$true_code 	= $code_db->where($where)->getField('code');
    	
    	if(!empty($true_code) && $true_code == $code){
    		return true;
    	}else{
    		return false;
    	}
    }
	/**
 * 截取中文字符串
 */
	function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false){
	 if(function_exists("mb_substr")){
	  if($suffix)
	   return mb_substr($str, $start, $length, $charset)."...";
	  else
	   return mb_substr($str, $start, $length, $charset);
	 }elseif(function_exists('iconv_substr')) {
	  if($suffix)
	   return iconv_substr($str,$start,$length,$charset)."...";
	  else
	   return iconv_substr($str,$start,$length,$charset);
	 }
	 $re['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
	 $re['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
	 $re['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
	 $re['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
	 preg_match_all($re[$charset], $str, $match);
	 $slice = join("",array_slice($match[0], $start, $length));
	 if($suffix) return $slice."…";
	 return $slice;
	}
} // end class UserinfoAction

?>