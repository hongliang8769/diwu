<?php
class RepastAction extends WapAction{
    public $token;
    public $wecha_id = '';
    public $session_dish_info;
    public $session_dish_user;
    public $_cid = 0;
    private $_sms_auth_code = '';//下订单的短信验证
    public function _initialize(){ 
        parent::_initialize();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!strpos($agent, "MicroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
        }
        $this->token = isset($_REQUEST['token']) ? $_REQUEST['token'] : session('token');
        $this->assign('token', $this->token);
        $this->wecha_id = isset($_REQUEST['wecha_id']) ? $_REQUEST['wecha_id'] : '';
        if (!$this -> wecha_id){
            $this -> wecha_id = '';
        }
        $this->assign('wecha_id', $this->wecha_id);
        //$this->_cid = $_SESSION["session_company_{$this->token}"];
		$this->_cid = 3;
        $this->assign('cid', $this->_cid);
        $this->session_dish_info = "session_dish_{$this->_cid}_info_{$this->token}";
        $this->session_dish_user = "session_dish_{$this->_cid}_user_{$this->token}";
        $menu                    = $this->getDishMenu();
        $count                   = count($menu);
        $this->assign('totalDishCount', $count);
    }
    /**
    * 餐厅分布
    */
    public function index(){       
        $data = M('dish_company');
        $list = $data->select();  
		$id_arr = array();
        foreach ($list as $row) {  
            $id_arr[] = $row['cid'];
        }   
        
        $company = M('Company')->where("`token`='{$this->token}' AND ((`isbranch`=1 AND `display`=1) OR `isbranch`=0)")->select();

        $company_new = array();
        foreach ($company as $row) {
                if (in_array($row['id'], $id_arr)) {
                        $company_new[] = $row;
                }
        }		
				$company = $company_new;
        
        if (count($company) == 1) {
            $this -> redirect(U('Repast/select', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $company[0]['id'])));
        }
        $this->assign('company', $company);
        $this->assign('metaTitle', '餐厅分布');
        $this->display();
    }
    /**
    *就餐形式选择页 
    */
    public function select() {
            unset($_SESSION[$this -> session_dish_user]);
            unset($_SESSION['david_add_dishset']);
            unset($_SESSION['david_add_mymenuset']);
            
            unset($_SESSION['david_add_takeaway']);
            unset($_SESSION['david_add_token']);
            unset($_SESSION['david_add_wecha_id']);            
            
        $istakeaway = 0;
        $cid        = isset($_GET['cid']) ? intval($_GET['cid']) : 0;

        if ($company = M('Company') -> where(array('token' => $this -> token, 'id' => $cid)) -> find()){
            $_SESSION["session_company_{$this->token}"] = $cid;
        } else {
            $this -> redirect(U('Repast/index', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id)));
        }
        if ($dishCompany = M('Dish_company') -> where(array('cid' => $cid)) -> find()){
            $istakeaway = $dishCompany['istakeaway'];
        }
        $this->assign('istakeaway', $istakeaway);
        $this->assign('metaTitle', '店面选择');
        $this->display();
    }
    /**
    * 餐厅介绍
    */
    public function virtual(){
        $cid     = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        $company = M('Company') -> where(array('token' => $this -> token, 'id' => $cid)) -> find();
        $this->assign('company', $company);
        $this->assign('metaTitle', '店面介绍');
        $this->display();
    }
    /**
    * 选取餐桌与填写个人信息
    */
    public function selectTable(){//to Repast/saveUser           dish-> 'token' => $this -> token, 'id' => $this -> _cid)   
       // echo $_SESSION[$this -> session_dish_user];
        $takeaway                           = isset($_GET['takeaway']) ? intval($_GET['takeaway']) : 0;
        if (isset($_SESSION['david_add_dishset']) || $_SESSION[$this -> session_dish_user] == 's:8:"wait_msg";') {
            $_SESSION['david_add_mymenuset'] = '1';
            $_GET['takeaway'] = $_SESSION['david_add_takeaway'];
            $this -> token = $_SESSION['david_add_token'];
            $this -> wecha_id = $_SESSION['david_add_wecha_id'];
        } else {//先选择菜单再写电话等信息
			// unserialize($_SESSION[$this -> session_dish_user]);exit;
            $_SESSION[$this -> session_dish_user] = 's:8:"wait_msg";';
            $_SESSION['david_add_takeaway'] = $takeaway;
            $_SESSION['david_add_token'] = $this -> token;      
            $_SESSION['david_add_wecha_id'] = $this -> wecha_id;                 
            $this -> redirect(U('Repast/dish', array('token' => $this -> token, 
                                                      'wecha_id' => $this -> wecha_id,
                                                     'id' => $this -> _cid)));            
        }
        $thisUser = M('Userinfo') -> where(array('token' => $this -> token, 'wecha_id' => $this -> wecha_id)) -> find();
        $this -> assign('thisUser', $thisUser);
        $takeaway = isset($_GET['takeaway']) ? intval($_GET['takeaway']) : 0; //2-现场点餐 0-在线预订
        //$_SESSION[$this -> session_dish_user] = null;
       // unset($_SESSION[$this -> session_dish_user]);
        $time       = time();
        $orderTable = M('Dish_table') -> where(array('reservetime' => array('elt', $time + 2 * 3600), 'reservetime' => array('egt', $time - 2 * 3600), 'cid' => $this -> _cid, 'isuse' => 0)) -> select();
        $tids       = array();
        foreach ($orderTable as $row) {
            $tids[] = $row['tableid'];
        }
        if ($tids) {
            $table = M('Dining_table') -> where(array('id' => array('not in', $tids), 'cid' => $this -> _cid)) -> select();
        }else{
            $table = M('Dining_table') -> where(array('cid' => $this -> _cid)) -> select();
        }
        $dates   = array();
        $dates[] = array('k' => date("Y-m-d"), 'v' => date("m月d日"));
        for ($i = 1; $i <= 90; $i++) {
            $dates[] = array('k' => date("Y-m-d", strtotime("+{$i} days")), 'v' => date("m月d日", strtotime("+{$i} days")));
        }
        $hours = array();
        for ($i = 0; $i < 24; $i++) {
            $hours[] = array('k' => $i, 'v' => $i . "时");
        }
        $seconds = array();
        for ($i = 0; $i < 60; $i++) {
            $seconds[] = array('k' => $i, 'v' => $i . "分");
        }
        
        $dishCompany = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();
        $this -> assign('phone_authorize', $dishCompany['phone_authorize']);
        
        $this->assign('dates', $dates);
        $this->assign('seconds', $seconds);
        $this->assign('hours', $hours);
        $this->assign('takeaway', $takeaway);
        $this->assign('tables', $table);
        $this->assign('metaTitle', '填写个人信息');
        $this->assign('time', date("Y-m-d H:i:s"));
        $this->display();
    }
    /**
    * ajax请求获取餐桌信息
    */
    public function getTable(){
        $date       = isset($_POST['redate']) ? htmlspecialchars($_POST['redate']) : '';
        $hour       = isset($_POST['rehour']) ? htmlspecialchars($_POST['rehour']) : '';
        $second     = isset($_POST['resecond']) ? htmlspecialchars($_POST['resecond']) : '';
        $time       = strtotime($date . ' ' . $hour . ':' . $second . ':00');
        $orderTable = M('Dish_table') -> where(array('reservetime' => array('elt', $time + 2 * 3600), 'reservetime' => array('egt', $time - 2 * 3600), 'cid' => $this -> _cid, 'isuse' => 0)) -> select();
        $tids       = array();
        foreach ($orderTable as $row) {
            $tids[] = $row['tableid'];
        }
        if ($tids) {
            $table = M('Dining_table') -> where(array('id' => array('not in', $tids), 'cid' => $this -> _cid)) -> select();
        }else{
            $table = M('Dining_table') -> where(array('cid' => $this -> _cid)) -> select();
        }
        exit(json_encode($table));
    }
    /**
     * 取短信验证码在下订单时
     */
    public function get_sms_auth_code() {
        if ($_POST['tel']) {
            $this->_sms_auth_code = rand(100000, 999999);
            $res = Sms :: sendSms($this -> token . "_" . $this -> _cid, "您的预约短信验证码是". $this->_sms_auth_code ."请妥善保管", $_POST['tel']);            
            exit(json_encode(array('success' => 1, 'msg' => $this->_sms_auth_code)));
        } else {
            exit(json_encode(array('success' => 0, 'msg' => '电话号码不能为空')));
        }              
    }    
    public function saveUser(){//保存订单  //2-现场点餐 0-在线预订 1-外卖（店铺上设置）
        /*if ($_POST['phone_authorize'] == 1 && $_POST['tel_auth_code'] != $_POST['tel_auth_code_ajax']) {
            exit(json_encode(array('success' => 0, 'msg' => '您的手机短信验证码错误，不能订餐!')));            
        }*/        
		$thisUser = M('Userinfo') -> where(array('token' => $this -> token, 'wecha_id' => $this -> wecha_id)) -> find();
        $takeaway = isset($_POST['takeaway']) ? intval($_POST['takeaway']) : 0;
        $tel      = $table = $address = $des = $name = '';
        $sex      = $nums = 1;
        $price    = 0;
        if ($takeaway == 1) {
            $dishCompany = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();
            if (isset($dishCompany['istakeaway']) && $dishCompany['istakeaway']) $price = $dishCompany['price'];
        }
        if ($takeaway != 2) {
           /* $tel = isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : '';
            if (empty($tel)) {
                exit(json_encode(array('success' => 0, 'msg' => '电话号码不能为空')));
            }
            $name = isset($_POST['guest_name']) ? $_POST['guest_name'] : '';
            if (empty($name)) {
                exit(json_encode(array('success' => 0, 'msg' => '姓名不能为空')));
            }*/
			$dishid        = isset($_POST['dishid']) ? intval($_POST['dishid']) : 0;
			$timezone     = isset($_POST['timezone']) ? htmlspecialchars($_POST['timezone']) : '11:30';
			$yprice        = isset($_POST['yprice']) ? floatval ($_POST['yprice']) : 0;
            $address     = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '';
            $sex         = isset($_POST['sex']) ? intval($_POST['sex']) : 0;
            $date        = isset($_POST['redate']) ? htmlspecialchars($_POST['redate']) : '';
            $hour        = isset($_POST['rehour']) ? htmlspecialchars($_POST['rehour']) : '';
            $second      = isset($_POST['resecond']) ? htmlspecialchars($_POST['resecond']) : '';
            //$reservetime = strtotime($date . ' ' . $hour . ':' . $second . ':00');
			$nums = isset($_POST['nums']) ? intval($_POST['nums']) : 1;
			$tday1 = isset($_POST['tday1']) ? intval($_POST['tday1']) : 1;
			if ($nums >3) {
                exit(json_encode(array('success' => 0, 'msg' => '预约数量不能大于3！')));
            }
			$reservetime = strtotime($date . ' ' . $hour  . ':00'); 
			$dishtb=M('Dish_time') -> where(array('dishid' => $dishid,'timezone'=>$timezone)) -> find();
			$er=0;
			$leftnum=0;
			if($dishtb){
				if(date('Ymd', $reservetime) == date('Ymd')){
					if($tday1 == 1){
						if($dishtb['ordernum1']+$nums>$dishtb['num1']){
								$er=1;$leftnum=$dishtb['num1']-$dishtb['ordernum1'];
						}
					}else{
						if($dishtb['ordernum2']+$nums>$dishtb['num2']){
								$er=1;$leftnum=$dishtb['num2']-$dishtb['ordernum2'];
						}
					}
				}else{
					if($tday1 == 1){
						if($dishtb['ordernum2']+$nums>$dishtb['num2']){
								$er=1;$leftnum=$dishtb['num2']-$dishtb['ordernum2'];
						}
					}else{
						if($dishtb['ordernum1']+$nums>$dishtb['num1']){
								$er=1;$leftnum=$dishtb['num1']-$dishtb['ordernum1'];
						}
					}
				}
				if($er==1){exit(json_encode(array('success' => 0, 'msg' => '抱歉，已经超过最大数量，你选择的时段剩余可约数量为：'.$leftnum)));}
			}else{
				 exit(json_encode(array('success' => 0, 'msg' => '没有您预定的项目！')));
			}
			$ordertime=$reservetime+7200;
			if($hour=='18:30') $ordertime=$reservetime+5400;
            if ($ordertime < time()) {
                exit(json_encode(array('success' => 0, 'msg' => '预约时间不可以小于当前时间')));
            }
            
        } else {
            $reservetime = time() + 600;
        }
        $table                              = isset($_POST['table']) ? intval($_POST['table']) : 0;
        $des                                = isset($_POST['remark']) ? htmlspecialchars($_POST['remark']) : '';
        $data = array('tableid' => $table, 'tel' =>$thisUser['tel'], 'takeaway' => $takeaway, 'address' => $thisUser['address'], 'name' => $thisUser['truename'], 'sex' => $thisUser['sex'], 'reservetime' => $reservetime, 'price' => $yprice, 'nums' => $nums, 'des' => $des);
        $_SESSION[$this->session_dish_user] = serialize($data);//var_dump($_SESSION[$this->session_dish_user]);exit();
		$_SESSION['david_add_mymenuset'] = '1';
        exit(json_encode(array('success' => 1, 'msg' => 'ok')));
        //repast_selecttable 成功后- window.location = "{pigcms::U('Repast/dish', array('token'=>$token, 'wecha_id' => $wecha_id, 'cid' => $cid))}";
    }
	 public function saveUseradmin(){//保存订单  //2-现场点餐 0-在线预订 1-外卖（店铺上设置）
        /*if ($_POST['phone_authorize'] == 1 && $_POST['tel_auth_code'] != $_POST['tel_auth_code_ajax']) {
            exit(json_encode(array('success' => 0, 'msg' => '您的手机短信验证码错误，不能订餐!')));            
        }*/    
		    $user_wecha_id = $this->_get('user_wecha_id');
		$thisUser = M('Userinfo') -> where(array('token' => $this -> token, 'wecha_id' => $user_wecha_id)) -> find();
        $takeaway = isset($_POST['takeaway']) ? intval($_POST['takeaway']) : 0;
        $tel      = $table = $address = $des = $name = '';
        $sex      = $nums = 1;
        $price    = 0;
        if ($takeaway == 1) {
            $dishCompany = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();
            if (isset($dishCompany['istakeaway']) && $dishCompany['istakeaway']) $price = $dishCompany['price'];
        }
        if ($takeaway != 2) {
           /* $tel = isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : '';
            if (empty($tel)) {
                exit(json_encode(array('success' => 0, 'msg' => '电话号码不能为空')));
            }
            $name = isset($_POST['guest_name']) ? $_POST['guest_name'] : '';
            if (empty($name)) {
                exit(json_encode(array('success' => 0, 'msg' => '姓名不能为空')));
            }*/
			$dishid        = isset($_POST['dishid']) ? intval($_POST['dishid']) : 0;
			$timezone     = isset($_POST['timezone']) ? htmlspecialchars($_POST['timezone']) : '11:30';
			$yprice        = isset($_POST['yprice']) ? floatval ($_POST['yprice']) : 0;
            $address     = isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '';
            $sex         = isset($_POST['sex']) ? intval($_POST['sex']) : 0;
            $date        = isset($_POST['redate']) ? htmlspecialchars($_POST['redate']) : '';
            $hour        = isset($_POST['rehour']) ? htmlspecialchars($_POST['rehour']) : '';
            $second      = isset($_POST['resecond']) ? htmlspecialchars($_POST['resecond']) : '';
            //$reservetime = strtotime($date . ' ' . $hour . ':' . $second . ':00');
			$nums = isset($_POST['nums']) ? intval($_POST['nums']) : 1;
			$tday1 = isset($_POST['tday1']) ? intval($_POST['tday1']) : 1;
			if ($nums >3) {
                exit(json_encode(array('success' => 0, 'msg' => '预约数量不能大于3！')));
            }
			$reservetime = strtotime($date . ' ' . $hour  . ':00'); 
			$dishtb=M('Dish_time') -> where(array('dishid' => $dishid,'timezone'=>$timezone)) -> find();
			$er=0;
			$leftnum=0;
			if($dishtb){
				if(date('Ymd', $reservetime) == date('Ymd')){
					if($tday1 == 1){
						if($dishtb['ordernum1']+$nums>$dishtb['num1']){
								$er=1;$leftnum=$dishtb['num1']-$dishtb['ordernum1'];
						}
					}else{
						if($dishtb['ordernum2']+$nums>$dishtb['num2']){
								$er=1;$leftnum=$dishtb['num2']-$dishtb['ordernum2'];
						}
					}
				}else{
					if($tday1 == 1){
						if($dishtb['ordernum2']+$nums>$dishtb['num2']){
								$er=1;$leftnum=$dishtb['num2']-$dishtb['ordernum2'];
						}
					}else{
						if($dishtb['ordernum1']+$nums>$dishtb['num1']){
								$er=1;$leftnum=$dishtb['num1']-$dishtb['ordernum1'];
						}
					}
				}
				if($er==1){exit(json_encode(array('success' => 0, 'msg' => '抱歉，已经超过最大数量，你选择的时段剩余可约数量为：'.$leftnum)));}
			}else{
				 exit(json_encode(array('success' => 0, 'msg' => '没有您预定的项目！')));
			}
			$ordertime=$reservetime+7200;
			if($hour=='18:30') $ordertime=$reservetime+5400;
            if ($ordertime < time()) {
                exit(json_encode(array('success' => 0, 'msg' => '预约时间不可以小于当前时间')));
            }
            
        } else {
            $reservetime = time() + 600;
        }
        $table                              = isset($_POST['table']) ? intval($_POST['table']) : 0;
        $des                                = isset($_POST['remark']) ? htmlspecialchars($_POST['remark']) : '';
        $data = array('tableid' => $table, 'tel' =>$thisUser['tel'], 'takeaway' => $takeaway, 'address' => $thisUser['address'], 'name' => $thisUser['truename'], 'sex' => $thisUser['sex'], 'reservetime' => $reservetime, 'price' => $yprice, 'nums' => $nums, 'des' => $des);
        $_SESSION[$this->session_dish_user] = serialize($data);//var_dump($_SESSION[$this->session_dish_user]);exit();
		$_SESSION['david_add_mymenuset'] = '1';
        exit(json_encode(array('success' => 1, 'msg' => 'ok')));
        //repast_selecttable 成功后- window.location = "{pigcms::U('Repast/dish', array('token'=>$token, 'wecha_id' => $wecha_id, 'cid' => $cid))}";
    }
    /**
    * 点餐页
    */
    public function dish(){
		$_SESSION[$this->session_dish_info] = $_SESSION[$this->session_dish_user] = '';
        unset($_SESSION[$this -> session_dish_user], $_SESSION[$this -> session_dish_info]);
		$data = M('dish_company');
        $list = $data->select();  
		$id_arr = array();
        foreach ($list as $row) {  
            $id_arr[] = $row['cid'];
        }   
        
        $company = M('Company')->where("`token`='{$this->token}' AND ((`isbranch`=1 AND `display`=1) OR `isbranch`=0)")->select();

        $company_new = array();
        foreach ($company as $row) {
                if (in_array($row['id'], $id_arr)) {
                        $company_new[] = $row;
                }
        }		
		$company = $company_new;
        
		$_SESSION["session_company_{$this->token}"] = $company[0]['id'];
		
        //$company = M('Company') -> where(array('token' => $this -> token, 'id' => $this -> _cid)) -> find();
        /*$userInfo = unserialize($_SESSION[$this->session_dish_user]);
        if (empty($userInfo)) {
            $this -> redirect(U('Repast/select', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }
        if (isset($_SESSION['david_add_mymenuset'])) {            
            $this -> redirect(U('Repast/mymenu', array('token' => $this -> token, 
                'wecha_id' => $this->wecha_id,
                                                        'id' => $this -> _cid)));             
        }*/
		$start = date("n月j日",time());
		$last = date("n月j日",strtotime("+1 day"));
		$startall = date("Y-m-d",time());
		$lastall = date("Y-m-d",strtotime("+1 day"));
		$year = date("Y",time());
		$month = date("m",time())-1;
		$day = date("d",time());
		$this->assign('year', $year);
		$this->assign('month', $month);
		$this->assign('day', $day);
		$this->assign('start', $start);
		$this->assign('last', $last);
		$this->assign('startall', $startall);
		$this->assign('lastall', $lastall);
        $this->assign('metaTitle', $company[0]['name']);
        $this->display();
    }
	 public function dishadmin(){
		 $info['cardid'] = $this->_get('cardid','intval');
		$uid = $this->_get('uid','intval');
		$info['token'] = $this->_get('token');
		$info['user_wecha_id'] = $this->_get('user_wecha_id');
		
		$_SESSION[$this->session_dish_info] = $_SESSION[$this->session_dish_user] = '';
        unset($_SESSION[$this -> session_dish_user], $_SESSION[$this -> session_dish_info]);
		$data = M('dish_company');
        $list = $data->select();  
		$id_arr = array();
        foreach ($list as $row) {  
            $id_arr[] = $row['cid'];
        }   
        
        $company = M('Company')->where("`token`='{$this->token}' AND ((`isbranch`=1 AND `display`=1) OR `isbranch`=0)")->select();

        $company_new = array();
        foreach ($company as $row) {
                if (in_array($row['id'], $id_arr)) {
                        $company_new[] = $row;
                }
        }		
		$company = $company_new;
        
		$_SESSION["session_company_{$this->token}"] = $company[0]['id'];
		
        //$company = M('Company') -> where(array('token' => $this -> token, 'id' => $this -> _cid)) -> find();
        /*$userInfo = unserialize($_SESSION[$this->session_dish_user]);
        if (empty($userInfo)) {
            $this -> redirect(U('Repast/select', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }
        if (isset($_SESSION['david_add_mymenuset'])) {            
            $this -> redirect(U('Repast/mymenu', array('token' => $this -> token, 
                'wecha_id' => $this->wecha_id,
                                                        'id' => $this -> _cid)));             
        }*/
		$start = date("n月j日",time());
		$last = date("n月j日",strtotime("+1 day"));
		$startall = date("Y-m-d",time());
		$lastall = date("Y-m-d",strtotime("+1 day"));
		$year = date("Y",time());
		$month = date("m",time())-1;
		$day = date("d",time());
		$this->assign('year', $year);
		$this->assign('month', $month);
		$this->assign('day', $day);
		$this->assign('start', $start);
		$this->assign('last', $last);
		$this->assign('startall', $startall);
		$this->assign('lastall', $lastall);
        $this->assign('metaTitle', $company[0]['name']);
		
		
		$this->assign('info',$info);
		$this->assign('user_wecha_id',$info['user_wecha_id']);
		$this->assign('uid',$uid);

        $this->display();
    }
    /**
    * 菜单列表
    */
    public function GetDishList(){
        $company = M('Company') -> where(array('token' => $this -> token, 'id' => $this -> _cid)) -> find();
        $dish_sort = M('Dish_sort') -> where(array('cid' => $this -> _cid))->order('sort asc') -> select();
        $dish = M('Dish') -> where(array('cid' => $this -> _cid,'isopen'=>1))->order('sort asc') -> select();
        $dish_like = M('Dish_like') -> where(array('cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id)) -> select();
        $like      = array();
        foreach ($dish_like as $dl) {
            $like[$dl['did']] = 1;
        }
		$xcprice=0;
		/*$card = M('Member_card_create')->field('cardid')->where(array('token' => $this->token , 'wecha_id' => $this -> wecha_id))->find();
		
		if($card){
			$thisCard=M('Member_card_set')->where(array('token'=>$this->token,'id'=>$card['cardid']))->find();
			if($thisCard&&$thisCard['xcmoney']>0)
			{
				$xcprice=$thisCard['xcmoney'];
			}
		}*/
        $mymenu = $this->getDishMenu();
        $list   = array();
        foreach ($dish as $d) {
            $t                   = array();
            $t['id']             = $d['id'];
            $t['aid']            = $d['cid'];
            $t['name']           = $d['name'];
            //$t['price']          = $xcprice>0&&$d['id']==1?$xcprice:$d['price'];
			$t['price']          = $d['price'];
            $t['discount_name']  = '';
            $t['discount_price'] = '';
            $t['class_id']       = $d['sid'];
            $t['pic']            = $d['image'];
            $t['note']           = $d['des'];
            $t['unit']           = $d['unit'];
            $t['tag_name']       = $d['ishot'] ? '推荐' : '';
            $t['html_name']      = '';
            $t['check']          = isset($like[$d['id']]) ? $like[$d['id']] : 0;
            $t['select']         = isset($mymenu[$d['id']]) ? 1 : 0;
			
			$dish_time = M('Dish_time') -> where(array('dishid' => $d['id'])) -> select();
		/*	if($xcprice>0&&$d['id']==1){
				foreach ($dish_time as $dt) {
					$dt['price1'] =$xcprice;
					$dt['price2'] =$xcprice;
					$t['dish_time'][] = $dt;
				}
			}else{
				$t['dish_time'] = $dish_time;
			}*/
			$t['dish_time'] = $dish_time;
			if(!$d['zonetime']){
				M('Dish')->where(array('id' => $d['id'], 'cid' => $this->_cid))->save(array('zonetime' => time(), 'zonetype' => 1));
			}else{
				if(date('Ymd', $d['zonetime']) != date('Ymd')) 
				{
					D('Dish_time')->where(array('dishid' => $d['id']))->save(array('ordernum'.$d['zonetype'] => 0));//设置老的预定总数为0
					M('Dish')->where(array('id' => $d['id'], 'cid' => $this->_cid))->save(array('zonetime' => time(), 'zonetype' =>$d['zonetype']==1?2:1));
				}
			}
			$findData = M('Dish')->where(array('id' => $d['id'], 'cid' => $this->_cid))->find();
			$t['t1']      = intval($findData['zonetype']);
			$t['t2']      = $findData['zonetype']==1?2:1;
			
            $list[$d['sid']][]   = $t;
        }
        $result = array();
        foreach ($dish_sort as $sort) {
            $r           = array();
            $r['id']     = $sort['id'];
            $r['aid']    = $sort['cid'];
            $r['name']   = $sort['name'];
            $r['dishes'] = isset($list[$sort['id']]) ? $list[$sort['id']] : '';
            $result[]    = $r;
        }
        exit(json_encode($result));
    }
    /**
    * 对某个菜进行喜欢标记操作
    */
    public function dolike(){
        if (empty($this->wecha_id)) {
            exit(json_encode(array('status' => 0)));
        }
        $id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $check = isset($_POST['check']) ? intval($_POST['check']) : 0;
        if ($id) {
            $dishLike = D('Dish_like');
            $data = array('did' => $id, 'cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id);
            if ($check) {
                $dishLike->add($data);
            } else {
                $dishLike->where($data)->delete();
                exit(json_encode(array('status' => 1)));
            }
        }
        exit(json_encode(array('status' => 0)));
    }
	/**
	 * 喜欢餐店中的某些菜的列表
	 */
    public function like(){
        if ($this->wecha_id) {
            $mymenu    = $this->getDishMenu();
            $dish_like = M('Dish_like') -> where(array('cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id)) -> select();
            $dids      = array();
            foreach ($dish_like as $like) {
                $dids[] = $like['did'];
            }
            $dish = array();
            if ($dids) {
                $list = M('Dish') -> where(array('id' => array('in', $dids), 'cid' => $this -> _cid)) -> select();
                foreach ($list as $row) {
                    $row['select'] = isset($mymenu[$row['id']]) ? 1 : 0;
                    $dish[]        = $row;
                }
            }
        } else {
            $dish = array();
        }
        $this->assign('dishlist', $dish);
        $this->assign('metaTitle', '我喜欢的菜');
        $this->display();
    }
    /**
    * 点餐操作
    */
    public function editOrder(){
        $id  = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $num = isset($_POST['num']) ? intval($_POST['num']) : 0;
		$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
		$tday1 = isset($_POST['tday1']) ? intval($_POST['tday1']) : 1;
		$timezone = isset($_POST['timezone']) ? htmlspecialchars($_POST['timezone']) : '11:30';
        $des = isset($_POST['des']) ? htmlspecialchars($_POST['des']) : '';
        if ($id) {
           if ($num > 0) {
                $oldMenu[$id]['des'] = $des;
                $oldMenu[$id]['num'] = $num;
				$oldMenu[$id]['price'] = $price;
				$oldMenu[$id]['timezone'] = $timezone;
				$oldMenu[$id]['tday1'] = $tday1;
            }else{
				$oldMenu=array();
			}
			unset($_SESSION[$this->session_dish_info]);
            $_SESSION[$this->session_dish_info] = serialize($oldMenu);
			
        }
    }
	 /**
     * 点餐信息
     */
    public function getDishMenu(){
        if (!isset($_SESSION[$this->session_dish_info]) || !strlen($_SESSION[$this->session_dish_info])) {
            $dish = array();
        } else {
            $dish = unserialize($_SESSION[$this->session_dish_info]);
        }
        return $dish;
    }
    /**
    * 我的菜单（我的购物车）
    */
    public function mymenu(){
        /*if (unserialize($_SESSION[$this -> session_dish_user]) == 'wait_msg') {//var_dump($_SESSION[$this -> session_dish_user]);exit();
            $_SESSION['david_add_dishset'] = 1;
            $this -> redirect(U('Repast/selectTable', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id,
                                                            'id' => $this -> _cid)));               
        }*/
        if (isset($_SESSION['david_add_mymenuset'])) {
            unset($_SESSION['david_add_dishset']);
        } else {
            //$_SESSION['david_add_dishset'] = 1;
            $this -> redirect(U('Repast/dish', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id,
                                                            'id' => $this -> _cid)));             
        }
        $menu = $this->getDishMenu();
        if (empty($menu)) {
            $this->error('没有预约，请先勾选项目吧!');
        }      
        $userInfo = unserialize($_SESSION[$this->session_dish_user]);
       
        if (empty($userInfo)) {
            $this -> error('请先注册会员，先填写信息，再提交订单！', U('Userinfo/index', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }
        
        $data     = array();
		$taocan=array('taocanpay'=>0,'recordid'=>0);
        $totalNum = $totalPrice = $distotalPrice=0;
		$cardname='普通';
		$timezone='';
		$ispaynow=0;
        if ($menu) {
            $dids     = array_keys($menu);
			$dishList = M('Dish') -> where(array('cid' => $this -> _cid,'ispaynow' => 1, 'id' => array('in', $dids))) -> find();
			if($dishList) $ispaynow=1;
            $dishList = M('Dish') -> where(array('cid' => $this -> _cid, 'id' => array('in', $dids))) -> select();
            foreach ($dishList as $dish) {
                if (isset($menu[$dish['id']])) {
                    $totalNum += $menu[$dish['id']]['num'];
                    $totalPrice += $menu[$dish['id']]['num'] * $menu[$dish['id']]['price'];
					
					$card = M('Member_card_create')->field('cardid')->where(array('token' => $this->token , 'wecha_id' => $this -> wecha_id))->find();
					$thisCard=M('Member_card_set')->where(array('token'=>$this->token,'id'=>$card['cardid']))->find();
					$lowprice=$dish['lprice'];
					$cardname=$thisCard['cardname'];
					$cardid=$thisCard['id'];
					$xcprice=0;
					if($thisCard&&$thisCard['xcmoney']>0&&$dish['id']==1)
					{
						$xcprice=$thisCard['xcmoney'];
						if($xcprice<$lowprice)
						{
							$distotalPrice+=$menu[$dish['id']]['num'] *$lowprice;
						}else if($xcprice>$menu[$dish['id']]['price']){
							$distotalPrice+=$menu[$dish['id']]['num'] * $menu[$dish['id']]['price'];
						}else{
							$distotalPrice+=$menu[$dish['id']]['num'] * $xcprice;
						}
					}else{
						if($menu[$dish['id']]['price']*$thisCard['discount']<$lowprice)
						{
							$distotalPrice+=$menu[$dish['id']]['num'] *$lowprice;
						}else{
							$distotalPrice+=$menu[$dish['id']]['num'] * $menu[$dish['id']]['price']*$thisCard['discount'];
						}
					}
					$timezone = $menu[$dish['id']]['timezone'];
                    $r          = array();
                    $r['id']    = $dish['id'];
                    $r['name']  = $dish['name'];
                    $r['price'] = $menu[$dish['id']]['price'];
                    $r['unit'] = $dish['unit'];
                    $r['nums']  = $menu[$dish['id']]['num'];
                    $r['des']   = $menu[$dish['id']]['des'];
                    $data[]     = $r;
                }
            }
			$cwhere = array('token'=>$this->token,'wecha_id'=>$this->wecha_id);
			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->select();
			foreach ($dish_set_recorddb as $row3) {
				$taocaninfo = unserialize($row3['info']);
				foreach ($taocaninfo as $row4) {
					if($row4['dishid']==$data[0]['id']&&$row4['num']>=$data[0]['nums']){
						$taocan['taocanpay'] = 1;
						$taocan['recordid'] = $row3['id'];
					}
				}
			}
        }
        $tableName = '';
        /*if ($userInfo['tableid']) {
            $diningTable = M('Dining_table') -> where(array('cid' => $this -> _cid, 'id' => $userInfo['tableid'])) -> find();
            $tableName   = isset($diningTable['name']) && isset($diningTable['isbox']) ? ($diningTable['isbox'] ? $diningTable['name'] . '(包厢' . $diningTable['num'] . '座)' : $diningTable['name'] . '(大厅' . $diningTable['num'] . '座)') : '';
        }*/
        $_SESSION['mymenu_order_tablename'] = $tableName;
        $company = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();
        $alipayConfig = M('Alipay_config')->where(array('token' => $this->token))->find();
		$this->assign('company', $company);
		$this->assign('alipayConfig', $alipayConfig);
        $this->assign('tableName', $tableName);
		$this->assign('timezone', $timezone);
        $this->assign('userInfo', $userInfo);
        $this->assign('totalNum', $totalNum);
		$this->assign('cardname', $cardname);
		$this->assign('cardid', $cardid);
        $this->assign('totalPrice', $totalPrice);
		$this->assign('distotalPrice', $distotalPrice);
        $this->assign('my_dish', $data);
		$this->assign('ispaynow', $ispaynow);
        $this->assign('metaTitle', '我的订单');
		$this->assign('taocan', $taocan);
	//是否要支付
        unset($_SESSION['david_add_dishset']);
        unset($_SESSION['david_add_mymenuset']);
        
        $this->display();
    }
	 public function mymenuadmin(){
        /*if (unserialize($_SESSION[$this -> session_dish_user]) == 'wait_msg') {//var_dump($_SESSION[$this -> session_dish_user]);exit();
            $_SESSION['david_add_dishset'] = 1;
            $this -> redirect(U('Repast/selectTable', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id,
                                                            'id' => $this -> _cid)));               
        }*/
		$user_wecha_id = $this->_get('user_wecha_id');
        if (isset($_SESSION['david_add_mymenuset'])) {
            unset($_SESSION['david_add_dishset']);
        } else {
            //$_SESSION['david_add_dishset'] = 1;
            $this -> redirect(U('Repast/dishadmin', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id,'user_wecha_id' => $user_wecha_id,
                                                            'id' => $this -> _cid)));             
        }
        $menu = $this->getDishMenu();
        if (empty($menu)) {
            $this->error('没有预约，请先勾选项目吧!');
        }      
        $userInfo = unserialize($_SESSION[$this->session_dish_user]);
       
        if (empty($userInfo)) {
            $this -> error('请先注册会员，先填写信息，再提交订单！', U('Userinfo/index', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }
      
        $data     = array();
		$taocan=array('taocanpay'=>0,'recordid'=>0);
        $totalNum = $totalPrice = $distotalPrice=0;
		$cardname='普通';
		$timezone='';
		$ispaynow=0;
        if ($menu) {
            $dids     = array_keys($menu);
			$dishList = M('Dish') -> where(array('cid' => $this -> _cid,'ispaynow' => 1, 'id' => array('in', $dids))) -> find();
			if($dishList) $ispaynow=1;
            $dishList = M('Dish') -> where(array('cid' => $this -> _cid, 'id' => array('in', $dids))) -> select();
            foreach ($dishList as $dish) {
                if (isset($menu[$dish['id']])) {
                    $totalNum += $menu[$dish['id']]['num'];
                    $totalPrice += $menu[$dish['id']]['num'] * $menu[$dish['id']]['price'];
					
					$card = M('Member_card_create')->field('cardid')->where(array('token' => $this->token , 'wecha_id' => $user_wecha_id))->find();
					$thisCard=M('Member_card_set')->where(array('token'=>$this->token,'id'=>$card['cardid']))->find();
					$lowprice=$dish['lprice'];
					$cardname=$thisCard['cardname'];
					$cardid=$thisCard['id'];
					$xcprice=0;
					if($thisCard&&$thisCard['xcmoney']>0&&$dish['id']==1)
					{
						$xcprice=$thisCard['xcmoney'];
						if($xcprice<$lowprice)
						{
							$distotalPrice+=$menu[$dish['id']]['num'] *$lowprice;
						}else if($xcprice>$menu[$dish['id']]['price']){
							$distotalPrice+=$menu[$dish['id']]['num'] * $menu[$dish['id']]['price'];
						}else{
							$distotalPrice+=$menu[$dish['id']]['num'] * $xcprice;
						}
					}else{
						if($menu[$dish['id']]['price']*$thisCard['discount']<$lowprice)
						{
							$distotalPrice+=$menu[$dish['id']]['num'] *$lowprice;
						}else{
							$distotalPrice+=$menu[$dish['id']]['num'] * $menu[$dish['id']]['price']*$thisCard['discount'];
						}
					}
					$timezone = $menu[$dish['id']]['timezone'];
                    $r          = array();
                    $r['id']    = $dish['id'];
                    $r['name']  = $dish['name'];
                    $r['price'] = $menu[$dish['id']]['price'];
                    $r['unit'] = $dish['unit'];
                    $r['nums']  = $menu[$dish['id']]['num'];
                    $r['des']   = $menu[$dish['id']]['des'];
                    $data[]     = $r;
                }
            }
			$cwhere = array('token'=>$this->token,'wecha_id'=>$user_wecha_id);
			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->select();
			foreach ($dish_set_recorddb as $row3) {
				$taocaninfo = unserialize($row3['info']);
				foreach ($taocaninfo as $row4) {
					if($row4['dishid']==$data[0]['id']&&$row4['num']>=$data[0]['nums']){
						$taocan['taocanpay'] = 1;
						$taocan['recordid'] = $row3['id'];
					}
				}
			}
        }

        $tableName = '';
        /*if ($userInfo['tableid']) {
            $diningTable = M('Dining_table') -> where(array('cid' => $this -> _cid, 'id' => $userInfo['tableid'])) -> find();
            $tableName   = isset($diningTable['name']) && isset($diningTable['isbox']) ? ($diningTable['isbox'] ? $diningTable['name'] . '(包厢' . $diningTable['num'] . '座)' : $diningTable['name'] . '(大厅' . $diningTable['num'] . '座)') : '';
        }*/
        $_SESSION['mymenu_order_tablename'] = $tableName;
        $company = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();
        $alipayConfig = M('Alipay_config')->where(array('token' => $this->token))->find();
		$this->assign('company', $company);
		$this->assign('alipayConfig', $alipayConfig);
        $this->assign('tableName', $tableName);
		$this->assign('timezone', $timezone);
        $this->assign('userInfo', $userInfo);
        $this->assign('totalNum', $totalNum);
		$this->assign('cardname', $cardname);
		$this->assign('cardid', $cardid);
        $this->assign('totalPrice', $totalPrice);
		$this->assign('distotalPrice', $distotalPrice);
        $this->assign('my_dish', $data);
		$this->assign('ispaynow', $ispaynow);
        $this->assign('metaTitle', '客户的订单');
		$this->assign('taocan', $taocan);
		$this->assign('user_wecha_id', $user_wecha_id);
	//是否要支付
        unset($_SESSION['david_add_dishset']);
        unset($_SESSION['david_add_mymenuset']);
        
        $this->display();
    }
    public function getInfo(){ //exit(json_encode(array('success' => 1, 'msg' => 'ok')));
        if (empty($this->wecha_id)) {
            exit(json_encode(array('success' => 0, 'msg' => '无法获取您的微信身份，请关注“公众号”，然后回复“预约”来使用此功能')));
    }
        exit(json_encode(array('success' => 1, 'msg' => 'ok')));
    }
    /**
    * 保存我的订单
    */
    public function saveMyOrder(){
        if (empty($this->wecha_id)) {
            unset($_SESSION[$this->session_dish_info]);
            $this->error('您的微信账号为空，不能订餐!');
            exit(json_encode(array('success' => 0, 'msg' => '您的微信账号为空，不能订餐!')));
        }
        $dishs = $this->getDishMenu();
        if (empty($dishs)) {
            $this->error('没有预约，请去预约吧!');
        }
        $userInfo = unserialize($_SESSION[$this -> session_dish_user]);//已有好多信息数组
        if (empty($userInfo)) {
            $this -> error('您的个人信息有误，请重新下单!', U('Repast/dish', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }
        $userInfo['cid']      = $this->_cid;
        $userInfo['wecha_id'] = $this->wecha_id;
        $userInfo['token']    = $this->token;
		$recordid = isset($_POST['recordid']) ? intval($_POST['recordid']) : 0;
		$paymode = isset($_POST['paymode']) ? intval($_POST['paymode']) : 0;
        $total                = $price = 0;
        $dids                 = array_keys($dishs);
        $dishList = M('Dish') -> where(array('cid' => $this ->_cid, 'id' => array('in', $dids))) -> select();
        $temp                 = array();
		$dishtimeid            = 0;
		$dishtimeorder         = '';
		$distotalPrice=0;
        foreach ($dishList as $r) {
            if (isset($dishs[$r['id']])) {
                $total += $dishs[$r['id']]['num'];
                $price += $dishs[$r['id']]['num'] * $dishs[$r['id']]['price'];
				
				$card = M('Member_card_create')->field('cardid')->where(array('token' => $this->token , 'wecha_id' => $this -> wecha_id))->find();
				$thisCard=M('Member_card_set')->where(array('token'=>$this->token,'id'=>$card['cardid']))->find();
				$lowprice=$r['lprice'];
				
				$xcprice=0;
				if($thisCard&&$thisCard['xcmoney']>0&&$r['id']==1)
				{
					$xcprice=$thisCard['xcmoney'];
					if($xcprice<$lowprice)
					{
						$distotalPrice+=$dishs[$r['id']]['num'] *$lowprice;
					}else if($xcprice>$dishs[$r['id']]['price']){
						$distotalPrice+=$dishs[$r['id']]['num'] * $dishs[$r['id']]['price'];
					}else{
						$distotalPrice+=$dishs[$r['id']]['num'] * $xcprice;
					}
				}else{
					if($dishs[$r['id']]['price']*$thisCard['discount']<$lowprice)
					{
						$distotalPrice+=$dishs[$r['id']]['num'] *$lowprice;
					}else{
						$distotalPrice+=$dishs[$r['id']]['num'] * $dishs[$r['id']]['price']*$thisCard['discount'];
					}
				}

				if(date('Ymd', $userInfo['reservetime']) == date('Ymd'))
				{
					if($dishs[$r['id']]['tday1']==1){
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum1', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum1';
					}else{
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum2', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum2';
					}
				}else{
					if($dishs[$r['id']]['tday1']==1){
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum2', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum2';
					}else{
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum1', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum1';
					}
				}
				$temp[$r['id']] = array('dishid'=>$r['id'],'dishtimeid'=>$dishtimeid,'dishtimeorder'=>$dishtimeorder,'timezone'=>$dishs[$r['id']]['timezone'],'price' => $distotalPrice,'oldprice' => $dishs[$r['id']]['price'], 'num' => $dishs[$r['id']]['num'], 'name' => $r['name'], 'des' => $dishs[$r['id']]['des']);
            }
        }
        $takeAwayPrice = 0;
        if (isset($userInfo['price']) && $userInfo['price']) {
            //$price += $userInfo['price'];
            $takeAwayPrice = $userInfo['price'];
        }
        $userInfo['total']   = $total;
        $userInfo['oldprice']   = $price;
		$userInfo['price']   = $distotalPrice;
        $userInfo['info'] = serialize(array('takeAwayPrice' => $takeAwayPrice, 'list' => $temp));
        $userInfo['time']    = time();
        $userInfo['orderid'] = substr($this->wecha_id, -1, 4) . date("YmdHis");
        $doid = D('Dish_order') -> add($userInfo);//send_email
        $dis_order_id = $doid;
        if ($doid) {
            if ($userInfo['takeaway'] != 2) {
                if ($userInfo['takeaway'] == 1) {
                    Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$userInfo['name']}刚刚下了一个预约单，订单号：{$userInfo['orderid']}，请您注意查看并处理");
                } else {
                    Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$userInfo['name']}刚刚下了一个预约单，订单号：{$userInfo['orderid']}，请您注意查看并处理");
                }
            }
        /**			
        * 保存个人信息
        */		
            if ($userInfo['tableid']) {
                $table_order = array('cid' => $this -> _cid, 
                    'tableid' => $userInfo['tableid'],
                    'orderid' => $doid,
                    'wecha_id' => $this->wecha_id,
                    'reservetime' => $userInfo['reservetime'],
                    'creattime' => time());
                $doid        = D('Dish_table')->add($table_order);
            }
            $_SESSION[$this->session_dish_info] = $_SESSION[$this->session_dish_user] = '';
            unset($_SESSION[$this -> session_dish_user], $_SESSION[$this -> session_dish_info]);
            $alipayConfig = M('Alipay_config') -> where(array('token' => $this -> token)) -> find();
            $dishCompany = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();

            
            $dish_info = unserialize($userInfo['info']);
            $cai_arr_mail = array();
            $cai_arr = array();
            //print_r($dish_info['list']);exit;
            $all_money = 0;
            foreach ($dish_info['list'] as $cai) {
                $c_name  = $cai['name'] . str_repeat(' ', (10-strlen($cai['name'])/3)*2);
                $c_price = str_pad($cai['price'], 5, " ", STR_PAD_RIGHT);
                $cai_arr[] = $c_name . $c_price . $cai['num'];
                
                $c_name  = $cai['name'] . str_repeat(' ', (10-strlen($cai['name'])/3)*3);
                $cai_arr_mail[] = $c_name . $c_price . $cai['num'];
                
                $all_money += $cai['price'] * $cai['num'];
            }
            $email_tpl=
"订单编号：$userInfo[orderid]
联 系 人：$userInfo[name]
电    话：$userInfo[tel]
条目        单价（元）   数量
----------------------------\n" .join(chr(10).chr(13), $cai_arr_mail). "

备注：$userInfo[des]
餐台：" .$_SESSION['mymenu_order_tablename']. "
----------------------------
订餐人数：$userInfo[nums]
总　　价：$all_money
送餐时间：" . date('Y-m-d H:i:s', $userInfo['reservetime']) . "
下单时间：".  date('Y-m-d H:i:s', $userInfo['time']);             
            //发邮件动作
            if ($dishCompany['email_status'] == 1 && $dishCompany['email']) {                                  
                    $to_email       = $dishCompany['email'];
                    $emailuser      = $info['emailuser'];
                    $emailpassword  = $info['emailpassword'];
                    $subject        = "您有新的订单，单号：".$userInfo['orderid']."，预定人：".$userInfo['name'];
                    $body           = $email_tpl;
                    //$this->send_email($subject,$body,$emailuser,$emailpassword,$to_email);
                    
                    $smtpserver = C('email_server'); 
                    $port = C('email_port');
                    $smtpuser = C('email_user');
                    $smtppwd = C('email_pwd');
                    $mailtype = "TXT";
                    $sender = C('email_user');
                    $smtp = new Smtp($smtpserver,$port,true,$smtpuser,$smtppwd,$sender); 
                $to = $to_email;//$list['email']; 
                $subject = $subject;//C('pwd_email_title');
                    //$body = iconv('UTF-8','gb2312',$fetchcontent);inv
                    $send=$smtp->sendmail($to,$sender,$subject,$body,$mailtype);     
                    D('Dish_order')->save(array('send_email' => 1, 'id'=>$dis_order_id));//是否发过邮件
                    
            }

//短信
	    if ($dishCompany['phone_status'] == 1 && $userInfo['takeaway'] != 2){
                if ($userInfo['takeaway'] == 1){
                    Sms :: sendSms($this -> token . "_" . $this -> _cid, "顾客{$userInfo['name']}刚刚叫了一份外卖，订单号：{$userInfo['orderid']}，请您注意查看并处理【云信使】");
                }else{
                    Sms :: sendSms($this -> token . "_" . $this -> _cid, "顾客{$userInfo['name']}刚刚预约了一次用餐，订单号：{$userInfo['orderid']}，请您注意查看并处理【云信使】");
                }
            }
            //打印 
            //商户代码：0466550ef46d11e391ea00163e02163b
            //API：c4e011af
            //设备编码：4600108698566106
            if ($dishCompany['print_status'] == 1 && $dishCompany['memberCode'] && $dishCompany['feiyin_key'] && $dishCompany['deviceNo']) {
                $company_row = M('Company') -> where(array('id' => $userInfo['cid'])) -> find();
                //$this->printTxt($email_tpl, $dishCompany);
               //echo $company_row[name];
						$str="
				     $company_row[name] 
					
							条目         单价（元） 数量
							----------------------------\n".join(chr(10).chr(8), $cai_arr)."
							
							备注：$userInfo[des]
							餐台：" .$_SESSION['mymenu_order_tablename']. "
							----------------------------
							合计：{$all_money}元 
							
							联 系 人：$userInfo[name]
							订餐人数：$userInfo[nums]
							送货地址：$userInfo[address]
							联系电话：$userInfo[tel]
							送餐时间：" . date('Y-m-d H:i:s', $userInfo['reservetime']) . "
							订购时间：".date("Y-m-d H:i:s");
                
                $print_total = $dishCompany['print_total'];
                for ($i=1; $i<=$print_total; $i++) {    
                    $msgInfo=array(
                            'memberCode'=>$dishCompany['memberCode'],
                            'msgDetail'=>str_replace('[num]', $i, $str),
                            'deviceNo'=>$dishCompany['deviceNo'],
                            'msgNo'=>time()+1,
                            'reqTime' => number_format(1000*time(), 0, '', '')
                    );
                    $content = $msgInfo['memberCode'].$msgInfo['msgDetail'].$msgInfo['deviceNo'].$msgInfo['msgNo'].$msgInfo['reqTime'].$dishCompany['feiyin_key'];
                    $msgInfo['securityCode'] = md5($content);
                    $msgInfo['mode']=2;                   
                          
                    /*$client = new HttpClient('my.feyin.net');
                    if($client->post('/api/sendMsg',$msgInfo)){
                            $printstate=$client->getContent();
                    }  sleep(3); */                 
                }

		if($printstate==0){
                        //echo '打印成功';
			//$this->success('打印成功', U('Printer/index',array('token'=>$this->token)));
		}else{
                    //echo '打印失败';
                    //$this->error('打印失败，错误代码：'.$printstate);
		}                
            }
            
            
		if ($_POST['paymode'] == 1 && $alipayConfig['open'] && $dishCompany['payonline']){
			$this -> success('正在提交中...', U('Alipay/pay', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $userInfo['orderid'], 'single_orderid' => $userInfo['orderid'], 'price' => $price)));
		}elseif ($_POST['paymode'] == 4 && $this -> fans['balance'] && $dishCompany['payonline']){
			$this -> success('正在提交中...', U('CardPay/pay', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $userInfo['orderid'], 'single_orderid' => $userInfo['orderid'], 'price' => $price)));
		}elseif ($_POST['paymode'] == 10){
			$this -> success('正在提交中...', U('Repast/taocanpay', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'].'(套餐支付)', 'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'], 'dishid' => $dishList[0]['id'], 'num' => $total, 'recordid' => $recordid)));
		}elseif ($_POST['paymode'] == 11){
			$this -> success('正在提交中...', U('Repast/weixinpay', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'], 'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'])));
		}elseif ($_POST['paymode'] == 12){
			$this -> success('正在提交中...', U('CardPay/pay', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'],  'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'])));
		}else{
			$this -> success('预定成功,进入您的订单页', U('Repast/myOrder', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid, 'success' => 1)));
		}
        }else{
            $this -> error('订单出错，请重新下单');
            exit(json_encode(array('success' => 0, 'msg' => '订单出错，请重新下单')));
        }
    }
public function saveMyOrderadmin(){
	$user_wecha_id = $this->_get('user_wecha_id');
        if (empty($user_wecha_id)) {
            unset($_SESSION[$this->session_dish_info]);
            $this->error('客户的微信账号为空，不能预约!');
            exit(json_encode(array('success' => 0, 'msg' => '客户的微信账号为空，不能预约!')));
        }
		
        $dishs = $this->getDishMenu();
        if (empty($dishs)) {
            $this->error('没有预约，请去预约吧!');
        }
        $userInfo = unserialize($_SESSION[$this -> session_dish_user]);//已有好多信息数组
        if (empty($userInfo)) {
            $this -> error('您的个人信息有误，请重新下单!', U('Repast/dish', array('token' => $this -> token, 'wecha_id' => $user_wecha_id, 'cid' => $this -> _cid)));
        }
        $userInfo['cid']      = $this->_cid;
        $userInfo['wecha_id'] = $user_wecha_id;
        $userInfo['token']    = $this->token;
		$recordid = isset($_POST['recordid']) ? intval($_POST['recordid']) : 0;
		$paymode = isset($_POST['paymode']) ? intval($_POST['paymode']) : 0;
        $total                = $price = 0;
        $dids                 = array_keys($dishs);
        $dishList = M('Dish') -> where(array('cid' => $this ->_cid, 'id' => array('in', $dids))) -> select();
        $temp                 = array();
		$dishtimeid            = 0;
		$dishtimeorder         = '';
		$distotalPrice=0;
        foreach ($dishList as $r) {
            if (isset($dishs[$r['id']])) {
                $total += $dishs[$r['id']]['num'];
                $price += $dishs[$r['id']]['num'] * $dishs[$r['id']]['price'];
				
				$card = M('Member_card_create')->field('cardid')->where(array('token' => $this->token , 'wecha_id' => $user_wecha_id))->find();
				$thisCard=M('Member_card_set')->where(array('token'=>$this->token,'id'=>$card['cardid']))->find();
				$lowprice=$r['lprice'];
				
				$xcprice=0;
				if($thisCard&&$thisCard['xcmoney']>0&&$r['id']==1)
				{
					$xcprice=$thisCard['xcmoney'];
					if($xcprice<$lowprice)
					{
						$distotalPrice+=$dishs[$r['id']]['num'] *$lowprice;
					}else if($xcprice>$dishs[$r['id']]['price']){
						$distotalPrice+=$dishs[$r['id']]['num'] * $dishs[$r['id']]['price'];
					}else{
						$distotalPrice+=$dishs[$r['id']]['num'] * $xcprice;
					}
				}else{
					if($dishs[$r['id']]['price']*$thisCard['discount']<$lowprice)
					{
						$distotalPrice+=$dishs[$r['id']]['num'] *$lowprice;
					}else{
						$distotalPrice+=$dishs[$r['id']]['num'] * $dishs[$r['id']]['price']*$thisCard['discount'];
					}
				}

				if(date('Ymd', $userInfo['reservetime']) == date('Ymd'))
				{
					if($dishs[$r['id']]['tday1']==1){
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum1', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum1';
					}else{
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum2', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum2';
					}
				}else{
					if($dishs[$r['id']]['tday1']==1){
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum2', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum2';
					}else{
						M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id'])) -> setInc('ordernum1', 1);
						$Dish_timetb=M('Dish_time') -> where(array('timezone' => $dishs[$r['id']]['timezone'], 'dishid' => $r['id']))->find();
						$dishtimeid=$Dish_timetb['id'];
						$dishtimeorder='ordernum1';
					}
				}
				$temp[$r['id']] = array('dishid'=>$r['id'],'dishtimeid'=>$dishtimeid,'dishtimeorder'=>$dishtimeorder,'timezone'=>$dishs[$r['id']]['timezone'],'price' => $distotalPrice,'oldprice' => $dishs[$r['id']]['price'], 'num' => $dishs[$r['id']]['num'], 'name' => $r['name'], 'des' => $dishs[$r['id']]['des']);
            }
        }
        $takeAwayPrice = 0;
        if (isset($userInfo['price']) && $userInfo['price']) {
            //$price += $userInfo['price'];
            $takeAwayPrice = $userInfo['price'];
        }
        $userInfo['total']   = $total;
        $userInfo['oldprice']   = $price;
		$userInfo['price']   = $distotalPrice;
        $userInfo['info'] = serialize(array('takeAwayPrice' => $takeAwayPrice, 'list' => $temp));
        $userInfo['time']    = time();
        $userInfo['orderid'] = substr($user_wecha_id, -1, 4) . date("YmdHis");
        $doid = D('Dish_order') -> add($userInfo);//send_email
        $dis_order_id = $doid;
        if ($doid) {
            if ($userInfo['takeaway'] != 2) {
                if ($userInfo['takeaway'] == 1) {
                    Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$userInfo['name']}刚刚下了一个预约单，订单号：{$userInfo['orderid']}，请您注意查看并处理");
                } else {
                    Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$userInfo['name']}刚刚下了一个预约单，订单号：{$userInfo['orderid']}，请您注意查看并处理");
                }
            }
        /**			
        * 保存个人信息
        */		
            if ($userInfo['tableid']) {
                $table_order = array('cid' => $this -> _cid, 
                    'tableid' => $userInfo['tableid'],
                    'orderid' => $doid,
                    'wecha_id' => $user_wecha_id,
                    'reservetime' => $userInfo['reservetime'],
                    'creattime' => time());
                $doid        = D('Dish_table')->add($table_order);
            }
            $_SESSION[$this->session_dish_info] = $_SESSION[$this->session_dish_user] = '';
            unset($_SESSION[$this -> session_dish_user], $_SESSION[$this -> session_dish_info]);
            $alipayConfig = M('Alipay_config') -> where(array('token' => $this -> token)) -> find();
            $dishCompany = M('Dish_company') -> where(array('cid' => $this -> _cid)) -> find();

            
            $dish_info = unserialize($userInfo['info']);
            $cai_arr_mail = array();
            $cai_arr = array();
            //print_r($dish_info['list']);exit;
            $all_money = 0;
            foreach ($dish_info['list'] as $cai) {
                $c_name  = $cai['name'] . str_repeat(' ', (10-strlen($cai['name'])/3)*2);
                $c_price = str_pad($cai['price'], 5, " ", STR_PAD_RIGHT);
                $cai_arr[] = $c_name . $c_price . $cai['num'];
                
                $c_name  = $cai['name'] . str_repeat(' ', (10-strlen($cai['name'])/3)*3);
                $cai_arr_mail[] = $c_name . $c_price . $cai['num'];
                
                $all_money += $cai['price'] * $cai['num'];
            }
            $email_tpl=
"订单编号：$userInfo[orderid]
联 系 人：$userInfo[name]
电    话：$userInfo[tel]
条目        单价（元）   数量
----------------------------\n" .join(chr(10).chr(13), $cai_arr_mail). "

备注：$userInfo[des]
餐台：" .$_SESSION['mymenu_order_tablename']. "
----------------------------
订餐人数：$userInfo[nums]
总　　价：$all_money
送餐时间：" . date('Y-m-d H:i:s', $userInfo['reservetime']) . "
下单时间：".  date('Y-m-d H:i:s', $userInfo['time']);             
            //发邮件动作
            if ($dishCompany['email_status'] == 1 && $dishCompany['email']) {                                  
                    $to_email       = $dishCompany['email'];
                    $emailuser      = $info['emailuser'];
                    $emailpassword  = $info['emailpassword'];
                    $subject        = "您有新的订单，单号：".$userInfo['orderid']."，预定人：".$userInfo['name'];
                    $body           = $email_tpl;
                    //$this->send_email($subject,$body,$emailuser,$emailpassword,$to_email);
                    
                    $smtpserver = C('email_server'); 
                    $port = C('email_port');
                    $smtpuser = C('email_user');
                    $smtppwd = C('email_pwd');
                    $mailtype = "TXT";
                    $sender = C('email_user');
                    $smtp = new Smtp($smtpserver,$port,true,$smtpuser,$smtppwd,$sender); 
                $to = $to_email;//$list['email']; 
                $subject = $subject;//C('pwd_email_title');
                    //$body = iconv('UTF-8','gb2312',$fetchcontent);inv
                    $send=$smtp->sendmail($to,$sender,$subject,$body,$mailtype);     
                    D('Dish_order')->save(array('send_email' => 1, 'id'=>$dis_order_id));//是否发过邮件
                    
            }

//短信
	    if ($dishCompany['phone_status'] == 1 && $userInfo['takeaway'] != 2){
                if ($userInfo['takeaway'] == 1){
                    Sms :: sendSms($this -> token . "_" . $this -> _cid, "顾客{$userInfo['name']}刚刚叫了一份外卖，订单号：{$userInfo['orderid']}，请您注意查看并处理【云信使】");
                }else{
                    Sms :: sendSms($this -> token . "_" . $this -> _cid, "顾客{$userInfo['name']}刚刚预约了一次用餐，订单号：{$userInfo['orderid']}，请您注意查看并处理【云信使】");
                }
            }
            //打印 
            //商户代码：0466550ef46d11e391ea00163e02163b
            //API：c4e011af
            //设备编码：4600108698566106
            if ($dishCompany['print_status'] == 1 && $dishCompany['memberCode'] && $dishCompany['feiyin_key'] && $dishCompany['deviceNo']) {
                $company_row = M('Company') -> where(array('id' => $userInfo['cid'])) -> find();
                //$this->printTxt($email_tpl, $dishCompany);
               //echo $company_row[name];
						$str="
				     $company_row[name] 
					
							条目         单价（元） 数量
							----------------------------\n".join(chr(10).chr(8), $cai_arr)."
							
							备注：$userInfo[des]
							餐台：" .$_SESSION['mymenu_order_tablename']. "
							----------------------------
							合计：{$all_money}元 
							
							联 系 人：$userInfo[name]
							订餐人数：$userInfo[nums]
							送货地址：$userInfo[address]
							联系电话：$userInfo[tel]
							送餐时间：" . date('Y-m-d H:i:s', $userInfo['reservetime']) . "
							订购时间：".date("Y-m-d H:i:s");
                
                $print_total = $dishCompany['print_total'];
                for ($i=1; $i<=$print_total; $i++) {    
                    $msgInfo=array(
                            'memberCode'=>$dishCompany['memberCode'],
                            'msgDetail'=>str_replace('[num]', $i, $str),
                            'deviceNo'=>$dishCompany['deviceNo'],
                            'msgNo'=>time()+1,
                            'reqTime' => number_format(1000*time(), 0, '', '')
                    );
                    $content = $msgInfo['memberCode'].$msgInfo['msgDetail'].$msgInfo['deviceNo'].$msgInfo['msgNo'].$msgInfo['reqTime'].$dishCompany['feiyin_key'];
                    $msgInfo['securityCode'] = md5($content);
                    $msgInfo['mode']=2;                   
                          
                    /*$client = new HttpClient('my.feyin.net');
                    if($client->post('/api/sendMsg',$msgInfo)){
                            $printstate=$client->getContent();
                    }  sleep(3); */                 
                }

		if($printstate==0){
                        //echo '打印成功';
			//$this->success('打印成功', U('Printer/index',array('token'=>$this->token)));
		}else{
                    //echo '打印失败';
                    //$this->error('打印失败，错误代码：'.$printstate);
		}                
            }
            
            
		if ($_POST['paymode'] == 1 && $alipayConfig['open'] && $dishCompany['payonline']){
			$this -> success('正在提交中...', U('Alipay/pay', array('token' => $this -> token,'user_wecha_id'=> $user_wecha_id, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $userInfo['orderid'], 'single_orderid' => $userInfo['orderid'], 'price' => $price)));
		}elseif ($_POST['paymode'] == 4 && $this -> fans['balance'] && $dishCompany['payonline']){
			$this -> success('正在提交中...', U('CardPay/pay', array('token' => $this -> token,'user_wecha_id'=> $user_wecha_id, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $userInfo['orderid'], 'single_orderid' => $userInfo['orderid'], 'price' => $price)));
		}elseif ($_POST['paymode'] == 10){
			$this -> success('正在提交中...', U('Repast/taocanpayadmin', array('token' => $this -> token, 'user_wecha_id'=> $user_wecha_id,'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'].'(套餐支付)', 'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'], 'dishid' => $dishList[0]['id'], 'num' => $total, 'recordid' => $recordid)));
		}elseif ($_POST['paymode'] == 11){
			$this -> success('正在提交中...', U('Repast/weixinpay', array('token' => $this -> token,'user_wecha_id'=> $user_wecha_id, 'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'], 'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'])));
		}elseif ($_POST['paymode'] == 12){
			$this -> success('正在提交中...', U('CardPay/pay', array('token' => $this -> token, 'user_wecha_id'=> $user_wecha_id,'wecha_id' => $this -> wecha_id, 'success' => 1, 'from' => 'Repast', 'orderName' => $dishList[0]['name'],  'single_orderid' => $userInfo['orderid'], 'price' => $userInfo['price'])));
		}else{
			$this -> success('预定成功,进入您的订单页', U('Repast/myOrderadmin', array('token' => $this -> token, 'user_wecha_id'=> $user_wecha_id,'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid, 'success' => 1)));
		}
        }else{
            $this -> error('订单出错，请重新下单');
            exit(json_encode(array('success' => 0, 'msg' => '订单出错，请重新下单')));
        }
    }
	//测试打印 $dishCompany['memberCode'] && $dishCompany['feiyin_key'] && $dishCompany['deviceNo']
	public function printTxt($email_tpl, $dishCompany){
               
		$str="
     微信平台订餐打印
	
条目      单价（元）   数量
----------------------------
番茄炒粉     10.0       1
客家咸香鸡   20.0       1

备注：$userInfo[des]
----------------------------
合计：{$all_money}元 

送货地址：$userInfo[address]
联系电话：$userInfo[tel]
订购时间：".date("Y-m-d H:i:s");
		$msgInfo=array(
			'memberCode'=>$dishCompany['memberCode'],
			'msgDetail'=>$email_tpl,//$str,
			'deviceNo'=>$dishCompany['deviceNo'],
			'msgNo'=>time()+1,
			'reqTime' => number_format(1000*time(), 0, '', '')
		);
		$content = $msgInfo['memberCode'].$msgInfo['msgDetail'].$msgInfo['deviceNo'].$msgInfo['msgNo'].$msgInfo['reqTime'].$dishCompany['feiyin_key'];
		$msgInfo['securityCode'] = md5($content);
		$msgInfo['mode']=2;
		$client = new HttpClient('my.feyin.net');
		if($client->post('/api/sendMsg',$msgInfo)){
			$printstate=$client->getContent();
		}
		if($printstate==0){
                        //echo '打印成功';
			//$this->success('打印成功', U('Printer/index',array('token'=>$this->token)));
		}else{
                    //echo '打印失败';
                    //$this->error('打印失败，错误代码：'.$printstate);
		}
	}
    
//打印方法 $dishCompany['memberCode'] && $dishCompany['feiyin_key'] && $dishCompany['deviceNo']
	public function printTxt_a($email_tpl, $dishCompany){
            $email_tpl = str_replace(chr(13).chr(10), "\r\n", $email_tpl);
			$str=$email_tpl;
			$str .= "\r\n打印时间：".date('Y-m-d H:i:s')."\r\n--------------------------------\r\n";		
			$str="<1B40><1D2111><1B6101>订餐内容<0D0A><1B6100><1D2100><0D0A>".$str;  //初始化打印机加粗居中						
			//$str=iconv('utf-8','gbk',$str);
			//设置打印服务器开始
			$server="http://218.97.194.59:8088/Router/Rest/";  //打印API接口地址
			$appkey= $dishCompany['memberCode'];  //商户编码
                        $appsecret = $dishCompany['feiyin_key'];  // 商户密钥
                        $type = "addPrintContext"  ;//   打印类型
			$printerid = $dishCompany['deviceNo'];  //打印机编号
                        $isrun = "1";   //1为直接打印，非1等待打印
			$printcontext = $str ;    //打印内容
			$printcount= 1;//$printermodel['PrinterCount'];
                        $contentencode=urlencode("$printcontext");
			//$contentencode=$printcontext;
            $url = "$server/?appkey=$appkey&appsecret=$appsecret&type=$type&printerid=$printerid&$isrun=$isrun&printcount=$printcount&printcontext=$contentencode";
                        $content = file_get_contents($url);
			//print_r ('反馈结果'.$content);   //服务器返回结果，成功则返回此订单打印序列号，用于判断修改打印状态及处理状态。
         
			//设置打印服务器结束
			//设置为打印过了
			//$this->product_cart_model->where(array('id'=>$thisOrder['id']))->save(array('printed'=>1,'handled'=>1,'pcid'=>$content));
			//echo "CMD=01	FLAG=0	MESSAGE=成功	DATETIME=".date('YmdHis',$now)."	ORDERCOUNT=".$count."	ORDERID=".$thisOrder['id']."	PRINT=".$str;

    }    
     //发邮件函数
    public function send_email($Subject,$body,$emailuser,$emailpassword,$to_email){
            $where['username']=$this->_post('username');
            $where['email']=$this->_post('email');
            $db=D('Users');
            $list=$db->where($where)->find();
            if($list==false) $this->error('邮箱和帐号不正确',U('Index/regpwd'));

            $smtpserver = C('email_server'); 
            $port = C('email_port');
            $smtpuser = C('email_user');
            $smtppwd = C('email_pwd');
            $mailtype = "TXT";
            $sender = C('email_user');
            $smtp = new Smtp($smtpserver,$port,true,$smtpuser,$smtppwd,$sender); 
            $to = $list['email']; 
            $subject = C('pwd_email_title');
            $code = C('site_url').U('Index/resetpwd',array('uid'=>$list['id'],'code'=>md5($list['id'].$list['password'].$list['email']),'resettime'=>time()));
            $fetchcontent = C('pwd_email_content');
            $fetchcontent = str_replace('{username}',$where['username'],$fetchcontent);
            $fetchcontent = str_replace('{time}',date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']),$fetchcontent);
            $fetchcontent = str_replace('{code}',$code,$fetchcontent);
            $body=$fetchcontent;
            //$body = iconv('UTF-8','gb2312',$fetchcontent);inv
            $send=$smtp->sendmail($to,$sender,$subject,$body,$mailtype);
    }    
    public function clearMyMenu(){
        $_SESSION[$this->session_dish_info] = null;
        unset($_SESSION[$this->session_dish_info]);
    }
	public function myreportadmin(){
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$token=$this->token;
		if($this->isadmin['level']>0){
        	$where = array('cid' => $this -> _cid);
		}else{
			$where = array('cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id);
		}

		$where['paid']  = 1;
		//$where['reservetime']= array('gt','UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL -1 MONTH))');
		$start=strtotime($this->_post('statdate'));
		$last=strtotime($this->_post('enddate'));
		$yue=$this->_post('yue');
		$day=$this->_post('day');
		if($_POST['enddate'] < $_POST['statdate']){$this->error('结束时间不能小于开始时间');exit();}
		$month=0;	
		if(IS_POST){	
			if($yue>0){
				$month=$yue;
				$nowY = date('Y');
				if($day>0){
					$start = strtotime($nowY."-".$yue."-".$day);
					$last = strtotime(date('Y-m-d 23:59:59',$start));
				}else{
					$start = strtotime($nowY."-".$yue."-01");
					$last = strtotime(date('Y-m-d 23:59:59',$start)." +1 month -1 day");
				}
				if($yue==13){
					$date = date("Y-m-d");
					$start  = date('Y-m-01 00:00:00', strtotime($date));  //本月第一天
					$last = date('Y-m-d 23:59:59', strtotime("$start +1 month -1 day")); //本月最后一天
					$start=strtotime($start);
					$last=strtotime($last);
				}
			 }
			
		}else{
			$t = time();
			$start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));  //当天开始时间
			$last = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t)); //当天结束时间
		}
		$dish_order = M('Dish_order')->where("token = '$token' AND paid=1  AND paytime < $last AND paytime > $start")->order('id DESC')->select();
		$pcrecord = M('Member_card_pay_record')->where("token = '$token' AND paid=1 and paytype ='recharge' AND createtime < $last AND createtime > $start")->sum('price');
		$wxrecord = M('Member_card_pay_record')->where("token = '$token' AND paid=1 and paytype ='weixin' AND createtime < $last AND createtime > $start")->sum('price');
		$pcrecordcount = M('Member_card_pay_record')->where("token = '$token' AND paid=1 and paytype ='recharge' AND createtime < $last AND createtime > $start")->count();
		$wxrecordcount = M('Member_card_pay_record')->where("token = '$token' AND paid=1 and paytype ='weixin' AND createtime < $last AND createtime > $start")->count();
		$pcrecord=$pcrecord?$pcrecord:0;
		$wxrecord=$wxrecord?$wxrecord:0;
		$pcrecordcount=$pcrecordcount?$pcrecordcount:0;
		$wxrecordcount=$wxrecordcount?$wxrecordcount:0;

		$listorder       = array();
        foreach ($dish_order as $row) {
			 $row3=array();
             $row['info'] = unserialize($row['info']);
			 foreach ($row['info']['list'] as $row2) {
				$row3['itemname'] = $row2['name'];
				$row3['dishid'] = $row2['dishid'];
				$row3['price1'] = $row2['price'];
				$row3['num1'] = $row2['num'];
			 }
             $listorder[$row3['dishid']][]  = $row3;
        }
		
		$listordernew       = array();
		foreach ($listorder as $key=>$list) {
			$moneyi=0;$numi=0;$itemname='';
			foreach ($list as $l) {
				$moneyi+=$l['price1'];
				$numi+=$l['num1'];
				$itemname=$l['itemname'];
			}
			$listordernew[$key]['summoney']   = $moneyi;
			$listordernew[$key]['numi']   = $numi;
			$listordernew[$key]['itemname']   = $itemname;
		}
	  
		$dish = M('Dish') -> where(array('cid' => $this -> _cid,'isopen'=>1))->order('sort asc') -> select();
		$listdish   = array();
        foreach ($dish as $d) {
		  $d['summoney'] =0;
		  $d['numi'] =0;
		  $d['itemname']='';
		  if(isset($listordernew[$d['id']])){
			    $d['summoney'] =$listordernew[$d['id']]['summoney'];
		 		$d['numi'] =$listordernew[$d['id']]['numi'];
				$d['itemname'] =$listordernew[$d['id']]['itemname'];
		  }
		  $d['itemname']=empty($d['itemname'])?$d['name']:$d['itemname'];
		  $listdish[$d['sid']][]   = $d;
        }
	
		$dish_sort = M('Dish_sort') -> where(array('cid' => $this -> _cid))->order('sort asc') -> select();
        $result = array();
		foreach ($dish_sort as $sort) {
            $r           = array();
            $r['id']     = $sort['id'];
            $r['aid']    = $sort['cid'];
            $r['name']   = $sort['name'];
            $r['dishes'] = isset($listdish[$sort['id']]) ? $listdish[$sort['id']] : '';
			
			$moneyi=0;$numi=0;
			foreach ($r['dishes'] as $l) {
				$moneyi+=$l['summoney'];
				$numi+=$l['numi'];
			}
			$r['totalmoney']   = $moneyi;
			$r['totalnum']   = $numi;
            $result[]    = $r;
        }
		
		$this->assign('month', $month);
		$this->assign('day', $day);
		$this->assign('pcrecord', $pcrecord);
		$this->assign('wxrecord', $wxrecord);
		$this->assign('pcrecordcount', $pcrecordcount);
		$this->assign('wxrecordcount', $wxrecordcount);
		$this->assign('statdate', $start);
		$this->assign('enddate', $last);
        $this->assign('orderList', $result);
        $this->assign('status', $status);
		if($this->isadmin['level']>0){
        	$this->assign('metaTitle', '报表管理');
		}else{
			$this->assign('metaTitle', '统计报表');
		}
        $this->display();
    }
	/**
    * 订单记录管理
    */
	public function myOrderadmin(){
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$token=$this->token;
		if($this->isadmin['level']>0){
        	$where = array('cid' => $this -> _cid);
		}else{
			$where = array('cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id);
		}
        if ($status == 4){
            $where['isuse'] = 1;
            $where['paid']  = 1;
        } elseif ($status == 3) {
            $where['isuse'] = 0;
            $where['paid']  = 1;
        } elseif ($status == 2) {
            $where['isuse'] = 1;
            $where['paid']  = 0;
        } elseif ($status == 1) {
            $where['isuse'] = 0;
            $where['paid']  = 0;
        }
		$where['paid']  = 1;
		//$where['reservetime']= array('gt','UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL -1 MONTH))');
		
		$count      = M('Dish_order')->where($where)->count();
		$Page       = new Page($count,15);
		$show       = $Page->show();
		$this->assign('page', $show);
		
        $dish_order = M('Dish_order')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $list       = array();
        foreach ($dish_order as $row) {
            $row['info'] = unserialize($row['info']);
			 foreach ($row['info']['list'] as $row2) {
				$row['dishtimeid'] = $row2['dishtimeid'];
				$row['dishtimeorder'] = $row2['dishtimeorder'];
				$row['itemname'] = $row2['name'];
				$row['timezone'] = $row2['timezone'];
			 }
			 $carinfolist = M('usercarinfo')->where("token = '$token' AND wecha_id = '".$row['wecha_id']."'")->find();
			 $row['carnumber'] =$carinfolist['carnumber'];
            $list[]  = $row;
        }
        $this->assign('orderList', $list);
        $this->assign('status', $status);
		if($this->isadmin['level']>0){
        	$this->assign('metaTitle', '预约管理');
		}else{
			$this->assign('metaTitle', '我的订单');
		}
        $this->display();
    }
    /**
    * 我的订单记录
    */
    public function myOrder(){
        $status = isset($_GET['status']) ? intval($_GET['status']) : 0;
		$where = array('cid' => $this -> _cid, 'wecha_id' => $this -> wecha_id);
        if ($status == 4){
            $where['isuse'] = 1;
            $where['paid']  = 1;
        } elseif ($status == 3) {
            $where['isuse'] = 0;
            $where['paid']  = 1;
        } elseif ($status == 2) {
            $where['isuse'] = 1;
            $where['paid']  = 0;
        } elseif ($status == 1) {
            $where['isuse'] = 0;
            $where['paid']  = 0;
        }
		$where['paid']  = 1;
		//$where['reservetime']= array('gt','UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL -1 MONTH))');
		
		$count      = M('Dish_order')->where($where)->count();
		$Page       = new Page($count,15);
		$show       = $Page->show();
		$this->assign('page', $show);
		
        $dish_order = M('Dish_order')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id DESC')->select();
        $list       = array();
        foreach ($dish_order as $row) {
            $row['info'] = unserialize($row['info']);
			 foreach ($row['info']['list'] as $row2) {
				$row['dishtimeid'] = $row2['dishtimeid'];
				$row['dishid'] = $row2['dishid'];
				$row['dishtimeorder'] = $row2['dishtimeorder'];
				$row['itemname'] = $row2['name'];
				$row['num'] = $row2['num'];
				$row['timezone'] = $row2['timezone'];
				$row['taocanpay'] = 0;
				
				$cwhere = array('token'=>$this->token,'wecha_id'=>$this->wecha_id);
    			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->select();
				foreach ($dish_set_recorddb as $row3) {
					$taocaninfo = unserialize($row3['info']);
					foreach ($taocaninfo as $row4) {
						if($row4['dishid']==$row['dishid']&&$row4['num']>=$row['num']){
							$row['taocanpay'] = 1;
							$row['recordid'] = $row3['id'];
						}
					}
				}
			 }
            $list[]  = $row;
        }
		
        $this->assign('orderList', $list);
        $this->assign('status', $status);
		$this->assign('metaTitle', '我的订单');
        $this->display();
    }
/**
	 * 删除订单
	 */
	public function deleteOrder() {
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		$dishtimeid = isset($_REQUEST['dishtimeid']) ? intval($_REQUEST['dishtimeid']) : 0;
		$dishtimeorder = isset($_REQUEST['dishtimeorder']) ? htmlspecialchars($_REQUEST['dishtimeorder']) : 'ordernum1';
		$dishOrder = M('Dish_order');
		$reservetime='';
		if ($thisOrder = $dishOrder->where(array('id' => $id, 'token' => $this->token))->find()) {
			$reservetime=$thisOrder['reservetime'];
			$dishOrder->where(array('id' => $id))->delete();
			if ($thisOrder['tableid']) {
				D('Dish_table')->where(array('orderid' => $thisOrder['id']))->delete();
			}
			if(date('Ymd', $reservetime) == date('Ymd')||date('Ymd', $reservetime) == date('Ymd',strtotime("+1 day"))){
				
				$Dish_timetb=M('Dish_time') -> where(array('id' => $dishtimeid))->find();
				if($Dish_timetb[$dishtimeorder]>0) {M('Dish_time') -> where(array('id' => $dishtimeid)) -> setDec($dishtimeorder, 1);}
			}
			$this->success('操作成功', U('Repast/myOrder', array('token' => $this->token, 'wecha_id' => $this -> wecha_id ,'cid' => $this->_cid)));
		}
	}
	//套餐支付
	public function taocanpay(){
		$user_wecha_id = $this->_get('user_wecha_id');
		$from = isset($_REQUEST['from']) ? htmlspecialchars($_REQUEST['from']) : 'Repast';
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
		$orderid = isset($_REQUEST['single_orderid']) ? htmlspecialchars($_REQUEST['single_orderid']) : '';
		$ordername = isset($_REQUEST['orderName']) ? htmlspecialchars($_REQUEST['orderName']) : '';
		$redirect = $this->_request('redirect');
		$price = $_REQUEST['price'];
		$num = $_REQUEST['num'];
		$recordid = $_REQUEST['recordid'];	
		$dishid = $_REQUEST['dishid'];
		$token = $this->token;
		$wecha_id = $this->wecha_id;
		
		if($price <= 0) $this->error('套餐支付失败，请输入有效金额!');
	//	if($orderid == '')$this->error('订单号不正确！');
		$record 	= M('Member_card_pay_record');
		if($orderid != ''){
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 0")->find();
			if($res){
				$payHandel=new payHandle($this->token,$from,'taocanpay');
				$payHandel->afterPay($orderid);
				$list       = array();
				$cwhere = array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'id'=>$recordid);
    			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->find();
				$taocaninfo = unserialize($dish_set_recorddb['info']);
				foreach ($taocaninfo as $row) {
					if($row['dishid']==$dishid&&$row['num']>=$num){
						$row['num']=$row['num']-$num;
					}
					$list[]  = $row;
				}
				$newdata['info']=serialize($list);
				$result=M('dish_set_record')->where($cwhere)->save($newdata);
				if($result){
					$returnurl=U("$from/payReturn",array('from'=>$from,'orderName'=>$res['ordername'],'orderid'=>$res['orderid'],'cardid'=>$cardid,'token'=>$res['token'], 'user_wecha_id'=> $user_wecha_id,'wecha_id'=>$res['wecha_id'],'price'=>$res['price'],'paytype'=>'taocanpay'));
					$this->success('支付成功，正在跳转..',$returnurl);
				}else{
					$this->error('套餐支付失败！');
				}
			}
			//var_dump(M('Member_card_pay_record')->getlastsql());exit();	
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 1")->find();
			if($res){
				$this->error('此订单号已经支付过了，请不要重复支付！');
			}
		}	
		$arr['orderid'] = $orderid;
		$arr['ordername'] = $ordername;
		$arr['paytype'] = 'weixin';
		$arr['createtime'] = time();
		$arr['paid'] = 0;
		$arr['price'] = $price;
		$arr['token'] = $this->token;
		$arr['wecha_id'] = $this->wecha_id;
		$arr['type'] = (int)$type;
		if(M('Member_card_pay_record')->add($arr)){
				$payHandel=new payHandle($this->token,$from,'taocanpay');
				$payHandel->afterPay($orderid);
				$list       = array();
				$cwhere = array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'id'=>$recordid);
    			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->find();
				$taocaninfo = unserialize($dish_set_recorddb['info']);
				foreach ($taocaninfo as $row) {
					if($row['dishid']==$dishid&&$row['num']>=$num){
						$row['num']=$row['num']-$num;
					}
					$list[]  = $row;
				}
				$newdata['info']=serialize($list);
				$result=M('dish_set_record')->where($cwhere)->save($newdata);
				if($result){
					$returnurl=U("$from/payReturn",array('from'=>$from,'orderName'=>$arr['ordername'],'orderid'=>$arr['orderid'],'cardid'=>$cardid,'token'=>$arr['token'],'wecha_id'=>$arr['wecha_id'],'price'=>$arr['price'],'paytype'=>'taocanpay'));
					$this->success('支付成功，正在跳转..',$returnurl);
				}else{
					$this->error('套餐支付失败！');
				}
		}else{
			$this->error('系统错误');
		}
		
	}
		//套餐支付
	public function taocanpayadmin(){
		$user_wecha_id = $this->_get('user_wecha_id');
		$from = isset($_REQUEST['from']) ? htmlspecialchars($_REQUEST['from']) : 'Repast';
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
		$orderid = isset($_REQUEST['single_orderid']) ? htmlspecialchars($_REQUEST['single_orderid']) : '';
		$ordername = isset($_REQUEST['orderName']) ? htmlspecialchars($_REQUEST['orderName']) : '';
		$redirect = $this->_request('redirect');
		$price = $_REQUEST['price'];
		$num = $_REQUEST['num'];
		$recordid = $_REQUEST['recordid'];	
		$dishid = $_REQUEST['dishid'];
		$token = $this->token;
		$wecha_id = $user_wecha_id;
		
		if($price <= 0) $this->error('套餐支付失败，请输入有效金额!');
	//	if($orderid == '')$this->error('订单号不正确！');
		$record 	= M('Member_card_pay_record');
		if($orderid != ''){
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 0")->find();
			if($res){
				$payHandel=new payHandle($this->token,$from,'taocanpay');
				$payHandel->afterPay($orderid);
				$list       = array();
				$cwhere = array('token'=>$this->token,'wecha_id'=>$user_wecha_id,'id'=>$recordid);
    			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->find();
				$taocaninfo = unserialize($dish_set_recorddb['info']);
				foreach ($taocaninfo as $row) {
					if($row['dishid']==$dishid&&$row['num']>=$num){
						$row['num']=$row['num']-$num;
					}
					$list[]  = $row;
				}
				$newdata['info']=serialize($list);
				$result=M('dish_set_record')->where($cwhere)->save($newdata);
				if($result){
					$returnurl=U("$from/payReturnadmin",array('from'=>$from,'orderName'=>$res['ordername'],'orderid'=>$res['orderid'],'cardid'=>$cardid,'token'=>$res['token'], 'user_wecha_id'=> $user_wecha_id,'wecha_id'=>$res['wecha_id'],'price'=>$res['price'],'paytype'=>'taocanpayadmin'));
					$this->success('套餐支付成功，正在跳转..',$returnurl);
				}else{
					$this->error('套餐支付失败！');
				}
			}
			//var_dump(M('Member_card_pay_record')->getlastsql());exit();	
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 1")->find();
			if($res){
				$this->error('此订单号已经支付过了，请不要重复支付！');
			}
		}	
		$myuinfo = M('Userinfo')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->field('wechaname,wecha_id,truename,id')->find();
		$arr['orderid'] = $orderid;
		$arr['ordername'] = $ordername;
		$arr['paytype'] = 'taocanpayadmin';
		$arr['createtime'] = time();
		$arr['paid'] = 0;
		$arr['adminname'] = $myuinfo['truename'];
		$arr['admin_wecha_id'] = $this->wecha_id;
		$arr['price'] = $price;
		$arr['token'] = $this->token;
		$arr['wecha_id'] = $user_wecha_id;
		$arr['type'] = (int)$type;
		if(M('Member_card_pay_record')->add($arr)){
				$payHandel=new payHandle($this->token,$from,'taocanpay');
				$payHandel->afterPay($orderid);
				$list       = array();
				$cwhere = array('token'=>$this->token,'wecha_id'=>$user_wecha_id,'id'=>$recordid);
    			$dish_set_recorddb 	= M('dish_set_record')->where($cwhere)->find();
				$taocaninfo = unserialize($dish_set_recorddb['info']);
				foreach ($taocaninfo as $row) {
					if($row['dishid']==$dishid&&$row['num']>=$num){
						$row['num']=$row['num']-$num;
					}
					$list[]  = $row;
				}
				$newdata['info']=serialize($list);
				$result=M('dish_set_record')->where($cwhere)->save($newdata);
				if($result){
					$returnurl=U("$from/payReturnadmin",array('from'=>$from,'orderName'=>$arr['ordername'],'orderid'=>$arr['orderid'],'cardid'=>$cardid,'token'=>$arr['token'],'user_wecha_id'=> $user_wecha_id,'wecha_id'=>$arr['wecha_id'],'price'=>$arr['price'],'paytype'=>'taocanpayadmin'));
					$this->success('支付成功，正在跳转..',$returnurl);
				}else{
					$this->error('套餐支付失败！');
				}
		}else{
			$this->error('系统错误');
		}
		
	}
	//充值处理
	public function weixinpay(){
		$from = isset($_REQUEST['from']) ? htmlspecialchars($_REQUEST['from']) : 'Repast';
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 0;
		$orderid = isset($_REQUEST['single_orderid']) ? htmlspecialchars($_REQUEST['single_orderid']) : '';
		$ordername = isset($_REQUEST['orderName']) ? htmlspecialchars($_REQUEST['orderName']) : '';
		$redirect = $this->_request('redirect');
		$price = $_REQUEST['price'];	
		$token = $this->token;
		$wecha_id = $this->wecha_id;
		
		if($price <= 0) $this->error('微信支付失败，请输入有效金额!!');
	//	if($orderid == '')$this->error('订单号不正确！');
		$record 	= M('Member_card_pay_record');
		if($orderid != ''){
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 0")->find();
			if($res){
				$this->success('提交成功，正在跳转支付页面..',U('Alipay/pay',array('from'=>$from,'orderName'=>$res['ordername'],'single_orderid'=>$res['orderid'],'cardid'=>$cardid,'token'=>$res['token'],'wecha_id'=>$res['wecha_id'],'price'=>$res['price'],'redirect'=>$redirect)));
			}
			$res = $record->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' AND paid = 1")->find();
			if($res){
				$this->error('此订单号已经支付过了，请不要重复支付！');
			}
		}	
		$dishOrder = M('Dish_order')->where("token = '$token' AND wecha_id = '$wecha_id' AND orderid = '$orderid' ")->find();
		if ($dishOrder) {
			$arr['orderid'] = $dishOrder['orderid'];
		}else{
			$arr['orderid'] = date('YmdHis',time()).mt_rand(1000,9999);
		}
		$arr['ordername'] = $ordername;
		$arr['paytype'] = 'weixin';
		$arr['createtime'] = time();
		$arr['paid'] = 0;
		$arr['price'] = $price;
		$arr['token'] = $this->token;
		$arr['wecha_id'] = $this->wecha_id;
		$arr['type'] = (int)$type;
		if(M('Member_card_pay_record')->add($arr)){
				$this->success('提交成功，正在跳转到支付页面..',U('Alipay/pay',array('from'=>$from,'orderName'=>$arr['ordername'],'single_orderid'=>$arr['orderid'],'token'=>$arr['token'],'wecha_id'=>$arr['wecha_id'],'price'=>$price,'redirect'=>$redirect)));
		}else{
			$this->error('系统错误');
		}
		
	}
    /**
     * 支付成功后的回调函数
     */
    public function payReturn(){
         //TODO 发货的短信提醒
		$act = $_GET['act'];
		$cardid = $_GET['cardid'];
		$orderid = $_GET['orderid'];
		$token = $_GET['token'];
		$wecha_id = $_GET['wecha_id'];
		$record = M('member_card_pay_record');
        if ($order = M('dish_order') -> where(array('orderid' => $orderid, 'token' => $this -> token)) -> find()){
            if ($order) {
				$payrecord = $record->where("orderid = '$orderid' AND token = '$token' AND wecha_id = '$wecha_id'")->find();
				
				if($payrecord){
					if($order['paid'] == 1){
						$record->where(array('orderid'=>$orderid,'token'=>$this->token))->save(array('paid' => 1,'paytime'=>time()));
						//M('Userinfo')->where("wecha_id = '$wecha_id' AND token = '$token'")->setDec('balance',$payrecord['price']);
					}else{
						exit('支付失败');
					}
				
				}else{
					exit('订单不存在');
				}
                Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$order['name']}刚刚对订单号：{$orderid}的订单进行了支付，请您注意查看并处理");
            }
            $this -> redirect(U('Repast/myOrder', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'cid' => $this -> _cid)));
        }else{
            exit('订单不存在');
        }
    }
	public function payReturnadmin(){
         //TODO 发货的短信提醒
		$act = $_GET['act'];
		$user_wecha_id = $this->_get('user_wecha_id');
		$cardid = $_GET['cardid'];
		$orderid = $_GET['orderid'];
		$token = $_GET['token'];
		$wecha_id = $_GET['wecha_id'];
		$record = M('member_card_pay_record');
        if ($order = M('dish_order') -> where(array('orderid' => $orderid, 'token' => $this -> token)) -> find()){
            if ($order) {
				$payrecord = $record->where("orderid = '$orderid' AND token = '$token' AND wecha_id = '$user_wecha_id'")->find();
				
				if($payrecord){
					if($order['paid'] == 1){
						$record->where(array('orderid'=>$orderid,'token'=>$this->token))->save(array('paid' => 1,'paytime'=>time()));
						//M('Userinfo')->where("wecha_id = '$wecha_id' AND token = '$token'")->setDec('balance',$payrecord['price']);
					}else{
						exit('支付失败');
					}
				
				}else{
					exit('订单不存在！');
				}
                Sms::sendSms($this->token . "_" . $this->_cid, "顾客{$order['name']}刚刚对订单号：{$orderid}的订单进行了支付，请您注意查看并处理");
            }
            $this -> redirect(U('Repast/myOrderadmin', array('token' => $this -> token, 'user_wecha_id' => $user_wecha_id, 'wecha_id' => $this -> wecha_id,'cid' => $this -> _cid)));
        }else{
            exit('订单不存在。');
        }
    }
}
?>