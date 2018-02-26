<?php
class WawajiAction extends UserAction {
	public $_cid = 0;
	public $token;
	public function _initialize() {
		parent::_initialize();
		$this->canUseFunction('wawaji');
		$this->_cid = session('companyid');
		if (empty($this->token)) {
			$this->error('不合法的操作', U('Index/index'));
		}
		if (empty($this->_cid))  {
			$company = M('Company')->where(array('token' => $this->token, 'isbranch' => 0))->find();
			
			if ($company) {
				$this->_cid = $company['id'];
				session('companyid', $this->_cid);
				session('companyk', md5($this->_cid . session('uname')));
				D("Wawaji")->where(array('token' => $this->token, 'cid' => 0))->save(array('cid' => $this->_cid));
			} else {
				$this->error('您还没有添加您的商家信息',U('Company/index',array('token' => $this->token)));
			}
		} else {
			$k = session('companyk');
			$company = M('Company')->where(array('token' => $this->token, 'id' => $this->_cid))->find();
			if (empty($company)) {
				$this->error('非法操作', U('Wawaji/index',array('token' => $this->token)));
			} else {
				$username = $company['isbranch'] ? $company['username'] : session('uname');
				if (md5($this->_cid . $username) != $k) {
					$this->error('非法操作', U('Wawaji/index',array('token' => $this->token)));
				}
			}
		}
		$ischild = session('companyLogin');
		$this->assign('ischild', $ischild);
		$this->assign('cid', $this->_cid);
	}
	public function index() {
		$searchkey = $this->_post('searchkey', 'trim');
		$where = array('token' => $this->token);
		if (!empty($searchkey)) {
			$where['title|keyword'] = array('like', '%' . $searchkey . '%');
		}
		$count = M('Wawaji')->where($where)->count();
		$Page = new Page($count, 15);
		$list = M('Wawaji')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach($list as $key => $value) {
			$log = M('wawaji_log')->Distinct(true)->field('wecha_id')->where(array('token' => $this->token, 'packet_id' => $value['id']))->select();
			$list[$key]['pcount'] = count($log);
		}
		$this->assign('lists', $list);
		$this->assign('page', $Page->show());
		$this->display();
	}
	
	/**
	 * 设置
	 */
	public function setting()
	{
		$setting = M('Wawaji_setting');
		$obj = $setting->where(array('token' => session('token'), 'cid' => $this->_cid))->find();
		if (IS_POST) {
			if ($obj) {
				unset($_POST['id']);
				$t = $setting->where(array('token' => session('token'), 'cid' => $this->_cid, 'id' => $obj['id']))->save($_POST);
				if ($t) {
					$this->success('修改成功',U('Wawaji/setting',array('token' => session('token'))));
				} else {
					$this->error('操作失败');
				}
			} else {
				$pid = $setting->add($_POST);
				if ($pid) {
					$this->success('增加成功',U('Wawaji/setting',array('token' => session('token'))));
				} else {
					$this->error('操作失败');
				}
			}
		} else {
			$showGroup = C('zhongshuai') ? 1 : 0;
			
			include('./PigCms/Lib/ORG/index.Tpl.php');
			include('./PigCms/Lib/ORG/cont.Tpl.php');
			
			$this->assign('showgroup', $showGroup);
			$this->assign('tpl', $tpl);
			$this->assign('contTpl', $contTpl);
			$this->assign('setting', $obj);
			$this->display();
		}
	}
	public function flash()
	{
		$flash = M("Wawaji_flash")->where(array('token' => $this->token, 'cid' => $this->_cid))->select();
		$this->assign('flash', $flash);
		$this->display();
	}
	
	public function flashadd()
	{
		$type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
		$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
		
		if (IS_POST) {
			$data = D('Wawaji_flash');
			$id = intval($this->_post('id'));
			if ($id) {
				$where = array('id' => $id, 'token' => $this->token, 'cid' => $this->_cid);
				$check = $data->where($where)->find();
				if ($check == false) $this->error('非法操作');
			}
			if ($data->create()) {
				if ($id) {
					if ($data->save()) {
						$this->success('修改成功', U('Wawaji/flash',array('token' => session('token'))));
					} else {
						$this->error('操作失败!');
					}
				} else {
					if ($data->add()) {
						$this->success('添加成功', U('Wawaji/flash',array('token' => session('token'))));
					} else {
						$this->error('操作失败');
					}
				}
			} else {
				$this->error($data->getError());
			}
		} else {
			$flash = M("Wawaji_flash")->where(array('token' => $this->token, 'cid' => $this->_cid, 'id' => $id))->find();
			$type = isset($flash['type']) ? $flash['type'] : $type;
			$this->assign('flash', $flash);
			$this->assign('type', $type);
			$this->display();
		}
	}
	
	public function flashdel()
	{
		$where = array();
		$where['id']=$this->_get('id','intval');
		$where['token']=$this->token;
		$where['cid']=$this->_cid;
		if(D("Wawaji_flash")->where($where)->delete()){
			$this->success('操作成功',U('Wawaji/flash',array('token' => session('token'))));
		}else{
			$this->error('操作失败',U('Wawaji/flash',array('token' => session('token'))));
		}
	}
	public function set() {
		$prize_db = M('Wawaji');
		$where = array('token' => $this->token, 'id' => $this->_get('id', 'intval'));
		$wawaji_info = $prize_db->where($where)->find();
		if (IS_POST) {
			if ($prize_db->create()) {
				//$_POST['start_time'] = strtotime($_POST['start_time']);
				//$_POST['end_time'] = strtotime($_POST['end_time']);
				if (empty($wawaji_info)) {
					$_POST['token'] = $this->token;
					$id = $prize_db->add($_POST);
					$this->success('添加成功', U('Wawaji/index', array('token' => $this->token)));
				}else {
					$swhere = array('token' => $this->token, 'id' => $this->_post('id', 'intval'));
					$offset = $prize_db->where($swhere)->save($_POST);
					$this->success('修改成功', U('Wawaji/index', array('token' => $this->token)));
				}
			}else {
				$this->error($prize_db->getError());
			}
		}else {
			$this->assign('set', $wawaji_info);
			$this->display();
		}
	}
	public function del() {
		$id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'id' => $id);
		if (M('Wawaji')->where($where)->delete()) {
			M('Wawaji_prize')->where(array('token' => $this->token, 'packet_id' => $id))->delete();
			M('Wawaji_log')->where(array('token' => $this->token, 'packet_id' => $id))->delete();
			M('Wawaji_reward')->where(array('token' => $this->token, 'packet_id' => $id))->delete();
			M('Keyword')->where(array('token' => $this->token, 'pid' => $id, 'module' => 'Wawaji'))->delete();
			$this->success('删除成功', U('Wawaji/index', array('token' => $this->token)));
		}
	}
	public function prize_list() {
		$packet_id = $this->_get('id', 'intval');
		$type = $this->_post('type', 'trim');
		$searchkey = $this->_post('searchkey', 'trim');
		$where = array('token' => $this->token, 'packet_id' => $packet_id);
		if (!empty($searchkey)) {
			$where['name'] = array('like', '%' . $searchkey . '%');
		}
		if (!empty($type)) {
			$where['type'] = $type;
		}
		$count = M('Wawaji_prize')->where($where)->count();
		$Page = new Page($count, 15);
		$list = M('Wawaji_prize')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $Page->show());
		$this->assign('packet_id', $packet_id);
		$this->display();
	}
	public function add_prize() {
		$prize_db = D('Wawaji_prize');
		$packet_id = $this->_get('packet_id', 'intval');
		$id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'packet_id' => $packet_id, 'id' => $id);
		$prize_info = $prize_db->where($where)->find();
		if (IS_POST) {
			if ($prize_db->create()) {
				if (empty($prize_info)) {
					$_POST['token'] = $this->token;
					$_POST['packet_id'] = $packet_id;
					$id = $prize_db->add($_POST);
					$this->success('添加成功', U('Wawaji/prize_list', array('token' => $this->token, 'id' => $packet_id)));
				}else {
					$swhere = array('token' => $this->token, 'id' => $this->_post('id', 'intval'));
					$offset = $prize_db->where($swhere)->save($_POST);
					$this->success('修改成功', U('Wawaji/prize_list', array('token' => $this->token, 'id' => $packet_id)));
				}
			}else {
				$this->error($prize_db->getError());
			}
		}else {
			$this->assign('packet_id', $packet_id);
			$this->assign('info', $prize_info);
			$this->display();
		}
	}
	public function prize_del() {
		$packet_id = $this->_get('packet_id', 'intval');
		$id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'packet_id' => $packet_id, 'id' => $id);
		if (M('Wawaji_prize')->where($where)->delete()) {
			$this->success('删除成功', U('Wawaji/prize_list', array('token' => $this->token, 'id' => $packet_id)));
		}
	}
	public function prize_log() {
		$packet_id = $this->_get('id', 'intval');
		$prize_id = $this->_get('prize_id', 'intval');
		$log_id = $this->_get('log_id', 'trim');
		$where = array('token' => $this->token, 'packet_id' => $packet_id);
		$searchkey = $this->_post('searchkey', 'trim');
		$is_reward = $this->_post('is_reward', 'intval');
		if (!empty($searchkey)) {
			$searchkey = M('Userinfo')->where(array('truename|wechaname' => $searchkey))->getField('wecha_id');
			$where['wecha_id'] = $searchkey;
		}
		if (!empty($is_reward)) {
			$where['is_reward'] = $is_reward;
		}
		if (!empty($prize_id)) {
			$where['prize_id'] = $prize_id;
		}
		if (!empty($log_id)) {
			$where['id'] = array('in', $log_id);
		}
		$count = M('Wawaji_log')->where($where)->count();
		$Page = new Page($count, 20);
		$list = M('Wawaji_log')->where($where)->order('add_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach ($list as $key => $value) {
			$list[$key]['wxname'] = M('Userinfo')->where(array('wecha_id' => $value['wecha_id']))->getField('wechaname');
		}
		$this->assign('list', $list);
		$this->assign('packet_id', $packet_id);
		$this->assign('page', $Page->show());
		$this->display();
	}
	public function log_del() {
		$packet_id = $this->_get('packet_id', 'intval');
		$id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'packet_id' => $packet_id, 'id' => $id);
		if (M('Wawaji_log')->where($where)->delete()) {
			$this->success('删除成功', U('Wawaji/prize_log', array('token' => $this->token, 'id' => $packet_id)));
		}
	}
	public function show_forms() {
		$id = $this->_get('id', 'intval');
		$packet_id = $this->_get('packet_id', 'intval');
		$where = array('token' => $this->token, 'id' => $id, 'packet_id' => $packet_id);
		$info = M('Wawaji_exchange')->where($where)->find();
		$info['wxname'] = M('Userinfo')->where(array('wecha_id' => $info['wecha_id']))->getField('wechaname');
		$this->assign('info', $info);
		$this->display();
	}
	public function is_ok() {
		$id = $this->_get('id', 'intval');
		$packet_id = $this->_get('packet_id', 'intval');
		$where = array('token' => $this->token, 'id' => $id, 'packet_id' => $packet_id);
		$result = array();
		M('Wawaji_exchange')->where($where)->save(array('status' => '1'));
		$result['err'] = 0;
		$result['info'] = '操作成功！';
		echo json_encode($result);
	}
	public function exchange() {
		$packet_id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'packet_id' => $packet_id);
		$type = $this->_post('type', 'intval');
		$status = $this->_post('status');
		$searchkey = $this->_post('searchkey', 'trim');
		if (!empty($type)) {
			$where['type'] = $type;
		}
		if ($status != '') {
			$where['status'] = intval($status);
		}
		if (!empty($searchkey)) {
			$searchkey = M('Userinfo')->where(array('truename|wechaname' => $searchkey))->getField('wecha_id');
			$where['wecha_id'] = $searchkey;
		}
		$count = M('Wawaji_exchange')->where($where)->count();
		$Page = new Page($count, 20);
		$list = M('Wawaji_exchange')->where($where)->order('status asc,time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		foreach ($list as $key => $value) {
			$list[$key]['wxname'] = M('Userinfo')->where(array('wecha_id' => $value['wecha_id']))->getField('wechaname');
		}
		$this->assign('list', $list);
		$this->assign('packet_id', $packet_id);
		$this->assign('page', $Page->show());
		$this->display();
	}
	public function change_del() {
		$packet_id = $this->_get('packet_id', 'intval');
		$id = $this->_get('id', 'intval');
		$where = array('token' => $this->token, 'packet_id' => $packet_id, 'id' => $id);
		if (M('Wawaji_exchange')->where($where)->delete()) {
			$this->success('删除成功', U('Wawaji/exchange', array('token' => $this->token, 'id' => $packet_id)));
		}
	}
}

?>