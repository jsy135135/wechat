<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2017-12-28 10:39:11
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2017-12-28 16:59:54
 */
// 测试调用方法
require './wechat.class.php';
header('Content-Type:text/html;charset=utf-8');
$wechat = new Wechat();
// 获取access_token
// $wechat->getAccessToken();
// 获取二维码ticket
// $wechat->getTicket(666);
// 通过ticket获取二维码
// $wechat->getQRCode(888);
// 获取用户openID列表
// $wechat->getUserList();
// 通过openID获取用户基本信息
// $wechat->getUserInfo();
// 上传素材
// $wechat->uploadFile();
// 下载素材
// $wechat->download();
// 创建菜单
// $wechat->createMenu();
// 查看菜单
$wechat->showMenu();
// 删除菜单
// $wechat->delMenu();