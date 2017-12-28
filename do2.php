<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2017-12-28 15:30:08
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2017-12-28 15:33:09
 */
require './wechat.class.php';
header('Content-Type:text/html;charset=utf-8');
$wechat = new Wechat();
$wechat->getUserInfo($_GET['openid']);