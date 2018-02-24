<?php
class GisModel extends Model{
	protected $_validate = array(
		array('lng','require','lng不能为空',1),
	);
    protected $_auto = array ( 
        array('ctime','time',1,'function'), // 对create_time字段在更新的时候写入当前时间戳
    );
}
?>