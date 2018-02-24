<?php

class CardPayAction extends BaseAction{

	public $token;
	public $wecha_id;

	public function __construct(){
		$this->token = $this->_request('token');
		$this->wecha_id = $this->_request('wecha_id');
		
	}
//调用地址 Wap/CardPay/pay ;参数：from price single_orderid orderName token wecha_id redirect（Moudle/Action|param1:value1,param2:value2）

	public function pay(){

			  if(IS_POST&&$_POST['pass']=='ok'){
				  $user_wecha_id = $this->_get('user_wecha_id');
				  $from = isset($_POST['from']) ? $_POST['from'] : 'Repast';
				  $cardid = isset($_POST['cardid']) ? intval($_POST['cardid']) : 0;
				  $type = isset($_POST['type']) ? $_POST['type'] : '';
				  $single_orderid = isset($_POST['single_orderid']) ? $_POST['single_orderid'] : '';
				  $orderName = isset($_REQUEST['orderName']) ? htmlspecialchars($_REQUEST['orderName']) : '';
				  $redirect =isset($_REQUEST['redirect']) ? htmlspecialchars($_REQUEST['redirect']) : '';
				  
				  $payHandel=new payHandle($this->token,$from,'CardPay');
				  $orderInfo=$payHandel->beforePay($single_orderid);
				  $price=$orderInfo['price'];
				
				  if(isset($user_wecha_id)&&!empty($user_wecha_id))
				  {
					   $this->gotopay2($single_orderid,$price,$from,$orderName,$cardid,$redirect,$user_wecha_id,$type);
				  }else{
				 	 $this->gotopay($single_orderid,$price,$from,$orderName,$cardid,$redirect,$user_wecha_id,$type);
				  }
				
			  }else{
				  $user_wecha_id = $this->_get('user_wecha_id');
				  $from = $this->_request('from');
				  $cardid = $this->_request('cardid');
				  $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
				  $single_orderid = $this->_request('single_orderid');
				  $orderName = $this->_request('orderName');
				  $redirect = $this->_request('redirect');
				  
				  $payHandel=new payHandle($this->token,$from,'CardPay');
				  $orderInfo=$payHandel->beforePay($single_orderid);
				  $price=$orderInfo['price'];
				 
				  if($price <= 0) {$this->error('余额支付失败，请输入有效金额。');}
				  
				  $this->assign('from', $from);
				  $this->assign('cardid', $cardid);
				  $this->assign('type', $type);
				  $this->assign('single_orderid', $single_orderid);
				  $this->assign('orderName', $orderName);
				  $this->assign('redirect', $redirect);
				  $this->assign('price', $price);
				  $this->assign('user_wecha_id', $user_wecha_id);
			
				  $this->display();
			  }
		}
		
		
		private function gotopay($orderid,$price,$from,$orderName,$cardid,$redirect='',$user_wecha_id='',$type=0){
			
				$userinfo = M('Userinfo');
				$payrecord = M('Member_card_pay_record');
				$create = M('Member_card_create');
				$exchange = M('Member_card_exchange');

				$cardid = $create->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->getField('cardid');
				$cardid = (int)$cardid;
				$reward = $exchange->where(array('token'=>$this->token,'cardid'=>$cardid))->getField('reward');
				$reward = (int)$reward;
				$uinfo = $userinfo->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->field('id,balance,expensetotal,total_score')->find();

				if(!$orderid){
					$this->error('请传入订单号');
				}
				if($uinfo['balance'] < $price){
					$this->error('余额不足');
				}
				
				if($payrecord->where("orderid = '$orderid'")->getField('id')){
					$flag1 = true;
				}else{
					$record['orderid'] = $orderid;
					$record['ordername'] = $orderName;
					$record['paytype'] = 'CardPay';
					$record['createtime'] = time();
					$record['paid'] = 0;
					$record['price'] = $price;
					$record['token'] = $this->token;
					$record['wecha_id'] = $this->wecha_id;
					$record['type'] = (int)$type;
					$flag1 = $payrecord->add($record);
					$flag1 = $flag1>0?true:false;
				}
				$udata['balance'] = $uinfo['balance'] - $price;
				$flag2 = $userinfo->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->save($udata);
				$flag2 = $flag2>0?true:false;
				$payHandel=new payHandle($this->token,$from,'CardPay');
				$payHandel->afterPay($orderid);

				if($flag1 && $flag2){
					  $payrecord->where(array('orderid'=>$orderid,'token'=>$this->token))->setField('paid',1);
					  
					  if (isset($redirect)&&!empty($redirect)){
							$urlinfo=explode('|',$_GET['redirect']);
							$parmArr=explode(',',$urlinfo[1]);
							$parms=array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'paytype'=>'CardPay','orderid'=>$orderid);
							if ($parmArr){
								foreach ($parmArr as $pa){
									$pas=explode(':',$pa);
									$parms[$pas[0]]=$pas[1];
								}
							 }
							 $this->redirect(U($urlinfo[0],$parms));		
					   }else{
						  $this->redirect(U("$from/payReturn",array('orderid'=>$orderid,'cardid'=>$cardid,'token'=>$this->token,'wecha_id'=>$this->wecha_id,'paytype'=>'CardPay')));
					   }		
				}else{
					$this->error('支付失败');
				}
		
		}
		private function gotopay2($orderid,$price,$from,$orderName,$cardid,$redirect='',$user_wecha_id='',$type=0){
			
				$userinfo = M('Userinfo');
				$payrecord = M('Member_card_pay_record');
				$create = M('Member_card_create');
				$exchange = M('Member_card_exchange');

				$cardid = $create->where(array('token'=>$this->token,'wecha_id'=>$user_wecha_id))->getField('cardid');
				$cardid = (int)$cardid;
				$reward = $exchange->where(array('token'=>$this->token,'cardid'=>$cardid))->getField('reward');
				$reward = (int)$reward;
				$uinfo = $userinfo->where(array('token'=>$this->token,'wecha_id'=>$user_wecha_id))->field('id,balance,expensetotal,total_score')->find();

				if(!$orderid){
					$this->error('请传入订单号');
				}
				if($uinfo['balance'] < $price){
					$this->error('余额不足');
				}
				
				if($payrecord->where("orderid = '$orderid'")->getField('id')){
					$flag1 = true;
				}else{
					$myuinfo = M('Userinfo')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->field('wechaname,wecha_id,truename,id')->find();
					$record['orderid'] = $orderid;
					$record['ordername'] =  $orderName;
					$record['paytype'] = 'CardPay';
					$record['createtime'] = time();
					$record['paid'] = 0;
					$record['price'] = $price;
					$record['token'] = $this->token;
					$record['wecha_id'] = $user_wecha_id;
					$record['adminname'] = $myuinfo['truename'];
					$record['admin_wecha_id'] = $this->wecha_id;
					$record['type'] = (int)$type;
					$flag1 = $payrecord->add($record);
					$flag1 = $flag1>0?true:false;
				}
				$udata['balance'] = $uinfo['balance'] - $price;
				$flag2 = $userinfo->where(array('token'=>$this->token,'wecha_id'=>$user_wecha_id))->save($udata);
				$flag2 = $flag2>0?true:false;
				$payHandel=new payHandle($this->token,$from,'CardPay');
				$payHandel->afterPay($orderid);

				if($flag1 && $flag2){
					  $payrecord->where(array('orderid'=>$orderid,'token'=>$this->token))->setField('paid',1);
					  
					  if (isset($redirect)&&!empty($redirect)){
							$urlinfo=explode('|',$_GET['redirect']);
							$parmArr=explode(',',$urlinfo[1]);
							$parms=array('token'=>$this->token,'user_wecha_id'=> $user_wecha_id,'wecha_id'=>$this->wecha_id,'paytype'=>'CardPay','orderid'=>$orderid);
							if ($parmArr){
								foreach ($parmArr as $pa){
									$pas=explode(':',$pa);
									$parms[$pas[0]]=$pas[1];
								}
							 }
							 $this->redirect(U($urlinfo[0],$parms));		
					   }else{
						  $this->redirect(U("$from/payReturnadmin",array('orderid'=>$orderid,'cardid'=>$cardid,'token'=>$this->token,'user_wecha_id'=> $user_wecha_id,'wecha_id'=>$this->wecha_id,'paytype'=>'CardPay')));
					   }		
				}else{
					$this->error('支付失败');
				}
		
		}

}
?>