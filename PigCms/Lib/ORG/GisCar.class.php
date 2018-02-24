<?php
class GisCar{
  private $wecha_id;
  private $token;
  private $siteUrl;
  private $giscarinfo;
  private $tel;

  public function __construct($token,$wecha_id,$tel='') {
	  $this->token=$token;
	  $this->wecha_id=$wecha_id;
	  $this->tel=$tel;
	  $this->siteUrl=C('site_url');
  }
	//获取用户信息
	//返回值:userinfoid
	private function getUserinfo(){
		if(empty($this->tel))
		{
			$data['wecha_id']=$this->wecha_id;
		}else{
			$data['tel']=$this->tel;
		}
		$data['token']=$this->token;
		$userInfoExist=M('Userinfo')->where($data)->find();
		if ($userInfoExist){
			return $userInfoExist['id'];
		}else {
			return M('Userinfo')->add($data);
		}
	}
	private function getbangcar(){
		$userinfoid=$this->getUserinfo();
		$data=array('userinfoid'=>$userinfoid);
		$mysql=M('gisgl');
		$check=$mysql->where($data)->find();
		if($check){
			$this->giscarinfo=M('giscarinfo')->find($check['carinfoid']);
			return  true;
		}
			return false;
	}
	private function delbangcar(){
		$userinfoid=$this->getUserinfo();
		$data=array('userinfoid'=>$userinfoid);
		$mysql=M('gisgl');
		$check=$mysql->where($data)->delete();
		if($check){
			return true;
		}
			return false;
	}
	private function getGisCarinfo($infoWhere){
		$giscarmode=M('giscarinfo');
		$infoWhere['token']=$this->token;
		$carInfoExist=$giscarmode->where($infoWhere)->find();
		if (!$carInfoExist){
			$giscarmode->add($infoWhere);
			return $giscarmode->where($infoWhere)->find();
		}
		return $carInfoExist;
	}
	private function getgisglowner($carinfoid){
		$infoWhere=array('owner'=>1,'carinfoid'=>$carinfoid);
		$mysql=M('gisgl');
		$check=$mysql->where($infoWhere)->find();
		if($check==false){
			return false;
		}
		return true;
	}
	private function addgisgl($infoWhere,$type=0){
		$userinfoid=$this->getUserinfo();
		$GisCarinfo=$this->getGisCarinfo($infoWhere);
		$this->giscarinfo=$GisCarinfo;
		$data=array('userinfoid'=>$userinfoid,'carinfoid'=>$GisCarinfo['id'],'type'=>$type);
		$mysql=M('gisgl');
		$check=$mysql->where($data)->find();
		if($check==false){
			$data['owner']=$this->getgisglowner($GisCarinfo['id'])?0:1;
			$data['ctime']=date('Y-m-d H:i:s');
		    return $mysql->add($data);
			//var_dump($mysql->getLastSql());exit();  
		}else{
			return 0;
		}
	}
	//菜单中的解除绑定功能。
	public function removeReply(){
		if(!$this->getbangcar()){
			  return  $this->noUseText();
		}else{
			if($this->delbangcar()){
			return array("解除绑定成功！\n设备号：".$this->giscarinfo['mcode'],'text');
			}
			return array('解除绑定失败！','text');
		}
	}
	public function getPhoneBang($infoWhere){
		//$infoWhere=array('url'=>$this->url);
		$this->addgisgl($infoWhere,1);
		return $this->giscarinfo['mcode'];
	}
	//扫码绑定
	public function getScanReply($mcode){
		$infoWhere=array('mcode'=>$mcode);
		if($this->addgisgl($infoWhere)>0){
			  return $this->isUsedText('绑定设备成功！');
		}else{
			 return  $this->isUsedText();
		}
	}
	//菜单中的我的设备回复
	public function getReply(){
		if(!$this->getbangcar()){
			 return  $this->noUseText();
		}else{
			 return $this->isUsedText();
		}
	}
	//菜单中的我的设备回复
	public function getCenterReply(){
		if(!$this->getbangcar()){
			 return  $this->noUseText();
		}else{
			 return  array(array(
			 array('欢迎使用智能行车管家系统！','设备号：'.$this->giscarinfo['mcode'],rtrim($this->siteUrl,'/').'/tpl/static/images/homelogo.png',rtrim($this->siteUrl,'/').U('Wap/Userinfo/addcode',array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'title'=>'个人中心','cardid'=>$this->giscarinfo['id']))),array('个人中心','设备号：'.$this->giscarinfo['mcode'],rtrim($this->siteUrl,'/').'/tpl/static/images/yj.jpg',rtrim($this->siteUrl,'/').U('Wap/Userinfo/addcode',array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'title'=>'个人中心','cardid'=>$this->giscarinfo['id'])))
			 ),'news');
		}
	}
	private function noUseText($txt='您还未绑定任何设备！'){
		return array(array(
			  array($txt,'设备号',rtrim($this->siteUrl,'/').'/tpl/static/images/home.jpg',rtrim($this->siteUrl,'/').U('Wap/Carb/bang',array('token'=>$this->token,'wecha_id'=>$this->wecha_id))),array('扫码绑定','设备号',rtrim($this->siteUrl,'/').'/tpl/static/images/yj.jpg',rtrim($this->siteUrl,'/').U('Wap/Carb/bang',array('token'=>$this->token,'wecha_id'=> $this->wecha_id)))
			 ),'news');
	}
	private function isUsedText($txt='欢迎使用智能行车管家系统！'){
		return  array(array(
			 array($txt,'设备号',rtrim($this->siteUrl,'/').'/tpl/static/images/home.jpg',rtrim($this->siteUrl,'/').U('Wap/Carb/index',array('token'=>$this->token,'wecha_id'=> $this->wecha_id))),array('查看设备','设备号',rtrim($this->siteUrl,'/').'/tpl/static/images/yj.jpg',rtrim($this->siteUrl,'/').U('Wap/Carb/index',array('token'=>$this->token,'wecha_id'=>$this->wecha_id))),array('完善信息','设备号',rtrim($this->siteUrl,'/').'/tpl/static/images/yj.jpg',rtrim($this->siteUrl,'/').U('Wap/Userinfo/addcode',array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'title'=>'完善信息','cardid'=>$this->giscarinfo['id'])))
			 ),'news');
	}
 /* 	
 private  function addgiscar($mcode)
	{
		$data=array('mcode'=>$mcode);
		$mysql=M('giscarinfo');
		$check=$mysql->field('id')->where($data)->find();
		if($check==false){
			$data['token']=$this->token;
		   return $mysql->add($data);
		}else{
			return $check['id'];
		}
	}
 private function getodbinfo(){
		$userinfoid=$this->getUserinfo();
		$data=array('userinfoid'=>$userinfoid);
		$mysql=M('gisgl');
		$check=$mysql->where($data)->find();
		if($check==false){
		   return "抱歉，您还没绑定任何设备，请先扫描绑定设备！";
		}
	    $carinfoid=$check['carinfoid'];
		$GisCarinfo=M('giscarinfo')->where(array('id'=>$carinfoid))->find();
		$gisobj=M('gisgps')->where(array('carinfoid',$carinfoid))->order('ctime desc')->limit(1)->select();
		$gis=$gisobj[0];
		$strGis="【车位置信息】\n";
		$strGis.="车号：".$GisCarinfo['mcode']."\n";
		$strGis.="纬度：".$gis[lat]."\n";
		$strGis.="经度：".$gis[lng]."\n";
		$strGis.="时间：".$gis[ctime]."\n";
		$strGis.="【车OBD信息】\n";
		$gisobj=M('gisobd')->where(array('carinfoid',$carinfoid))->order('ctime desc')->limit(1)->select();
		$gis=$gisobj[0];
		$odbData=json_decode($gis[obd],true);
		foreach($odbData as $obd){
			$strGis.=$obd[desc].'：'.$obd[value].' '.$obd[unit]."\n";
		}
		return $strGis;
	}*/
}

