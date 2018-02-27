<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php echo ($f_siteTitle); ?></title>
        <meta name="Keywords" content="<?php echo ($f_metaKeyword); ?>" />
        <meta name="description" content="<?php echo ($f_metaDes); ?>">
        <link type="text/css" rel="stylesheet" href="<?php echo RES;?>/css/reset.css" />
        <link type="text/css" rel="stylesheet" href="<?php echo RES;?>/css/style.css" />
        <link type="text/css" rel="stylesheet" href="<?php echo RES;?>/css/kufu.css" />
        <script src="<?php echo RES;?>/js/jquery-1.8.3.min.js"></script>
        <script src="<?php echo RES;?>/js/common.js"></script>
        <script src="<?php echo RES;?>/js/jquery.slider.js"></script>
        <script src="<?php echo RES;?>/js/jquery-runbanner.js"></script>
        <script src="<?php echo RES;?>/js/turn4.1.min.js"></script>
</head>


<body>
<div class="bg_login_main">
	<div class="login_main">
      <div class="img"><img src="<?php echo RES;?>/images/login/img.png" width="485" height="485"/></div>
      <div class="img02"><img src="<?php echo RES;?>/images/login/img02.png" width="127" height="127"/></div>
      <form action="<?php echo U('Users/checklogin');?>" method="post">
      <input name="username" type="text" class="txt_name" placeholder="平台账号" />
      <input name="password" type="password" class="txt_pwd" placeholder="密码" />
      <input name="captcha" type="text" class="txt_code" placeholder="验证码" />
       <script>
        function refreshImg(){
        document.getElementById("txtCheckCode").src="/index.php?m=Index&a=verify&s="+Math.random();
        }
       </script>
        <div class="code">
          <img src="<?php echo U('Index/verify');?>" width="81" height="31" id="txtCheckCode" alt="验证码" data-url="<?php echo U('Index/verify');?>" />
          <a href="javascript:refreshImg();">换一张</a>
        </div>
      	<div class="op">
        	<!--<span><input name="" type="checkbox" value="" />下次自动登录</span>
            <a href="#">忘记密码？</a> -->
        </div>
      <input type="submit" class="btn_login" value="" />
      <a href="<?php echo U('Index/reg');?>" class="btn_reg">新用户注册</a>
      </form>
    </div>
</div>
<script type="text/javascript">try{Dd('webpage_6').className='left_menu_on';}catch(e){}</script>
<script type="text/javascript">$(function(){$(".img").addClass("active");$(".btn_reg").animate({right:"30px"},"slow");})</script>