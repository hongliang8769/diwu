<?php
class ShakeModel extends Model{

	protected $_validate =array(
		array('acttitle','require','标题不能为空',1),
		array('picurl','require','图片不能为空',1),
		array('info','require','活动介绍不能为空',1),
		
	);
	
	protected $_auto = array (
		
		array('token','gettoken',self::MODEL_INSERT,'callback'),
		array('createtime','time',self::MODEL_INSERT,'function'),
		
	);
	
	
	function gettoken(){
		return session('token');
	}
	
	
}