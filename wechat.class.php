<?php

// 引入配置文件
require './wechat.cfg.php';

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2017-12-26 11:53:10
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2017-12-28 16:59:19
 */
class Wechat
{
    // 构造方法
    // 实列化时被调用
    public function __construct()
    {
      // 获取配置参数
      $this->token = TOKEN;
      $this->appid = APPID;
      $this->appsecret = APPSECRET;
      $this->textTpl = "<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[%s]]></MsgType>
          <Content><![CDATA[%s]]></Content>
          <FuncFlag>0</FuncFlag>
          </xml>";
    }
    // 验证方法
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }
    // 消息管理
    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        // file_put_contents('str.xml', $postStr);
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            // 不同的消息类型，使用不同的处理方法
            switch ($postObj->MsgType) {
              case 'text':
                //接收文本消息处理方法
                $this->doText($postObj);
                break;
              case 'image':
                //接收图片消息处理方法
                $this->doImage($postObj);
                break;
              case 'voice':
                //接收语音消息处理方法
                $this->doVoice($postObj);
                break;
              case 'location':
                //接收地理位置消息处理方法
                $this->doLocation($postObj);
                break;
              case 'event':
                //接收事件消息处理方法
                $this->doEvent($postObj);
                break;
              default:
                # code...
                break;
            }
        }
    }
    // 校验签名
    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
    // 文本消息处理
    private function doText($postObj)
    {
      $keyword = trim($postObj->Content);
      if (!empty($keyword)) {
          // $contentStr = "Welcome to wechat world!";
          $contentStr = "Hello PHP world!";
          if($keyword == '你是谁'){
            $contentStr = "目前我也不知道，我是谁，我是谁的谁！";
          }
          // 接入自动回复机器人
          $url = "http://api.qingyunke.com/api.php?key=free&appid=0&msg=".$keyword;
          $content = file_get_contents($url);
          // json转对象
          $content = json_decode($content);
          // 调用对象属性，回复用户的内容
          $contentStr = str_replace("{br}", "\r", $content->content);
          // 拼接返回信息的xml文档
          $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), "text", $contentStr);
          // file_put_contents('return.xml',$resultStr);
          echo $resultStr;
      }
    }
    // 图片消息处理
    private function doImage($postObj)
    {
      // 返回用户发送的图片信息url连接
      $PicUrl = $postObj->PicUrl;
      $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), "text", $PicUrl);
      // file_put_contents('1.jpg',file_get_contents($PicUrl));
      echo $resultStr;
    }
    // 语音消息处理
    private function doVoice($postObj)
    {
      // 获取语音的Mediald
      $contentStr = $postObj->MediaId;
      $resultStr = sprintf($this->textTpl, $postObj->FromUserName, $postObj->ToUserName, time(), "text", $contentStr);
      file_put_contents('voiceid.txt',$contentStr);
      echo $resultStr;
    }
    // 地理位置消息处理
    private function doLocation($postObj)
    {
      // 接收用户经纬度信息并返回
      $location = '您所在的经度为:'.$postObj->Location_Y.',纬度为:'.$postObj->Location_X;
      $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),"text",$location);
      echo $resultStr;
    }
    // 封装发送请求方法
    // 支持http、https协议 支持get和post请求方式
    public function request($url,$https=true,$method='get',$data=null)
    {
      // 1.curl初始化
      $ch = curl_init($url);
      // 2.curl设置参数
      // 设置数据返回方式
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      // 支持https
      if($https === true)
      {
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      }
      // 支持post
      if($method === 'post')
      {
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      }
      // 3.发送请求
      $content = curl_exec($ch);
      // 4.关闭资源
      curl_close($ch);
      // 返回数据
      return $content;
    }
    // 获取access_token
    public function getAccessToken()
    {
      // 是否存在缓存
      $memcache = new Memcache();
      $memcache->connect('127.0.0.1',11211);
      $access_token = $memcache->get('access_token');
      // 没有缓存
      if($access_token === false){
        // 1.url
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
        // 2.请求方式
        // 3.发送请求
        $content = $this->request($url);
        // 4.处理返回值
        // json转对象
        $content = json_decode($content);
        $access_token = $content->access_token;
        // 缓存access_token
        $memcache->set('access_token',$access_token,0,7000);
      }
      return $access_token;
    }
    // 获取二维码ticket
    public function getTicket($scene_id,$tmp=true,$expire_seconds=604800)
    {
      // 1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$this->getAccessToken();
      // 2.请求方式
      // 判断临时还是永久
      if($tmp === true)
      {
        // 临时
        $data = '{"expire_seconds": '.$expire_seconds.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
      }else{
        // 永久
        $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
      }
      // 3.发送请求
      $content = $this->request($url,true,'post',$data);
      // 4.处理返回值
      $content = json_decode($content);
      $ticket = $content->ticket;
      return $ticket;
    }
    // 通过ticket获取二维码
    public function getQRCode($scene_id,$tmp=true,$expire_seconds=604800)
    {
      // 1.url
      $ticket = $this->getTicket($scene_id);
      $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
      // 2.请求方式
      // 3.发送请求
      $content = $this->request($url);
      // 4.处理返回值
      header('Content-Type:image/jpg');
      echo $content;
      file_put_contents('888.jpg',$content);
    }
    // 事件消息处理
    private function doEvent($postObj)
    {
      // 通过Event来判断是什么事件
      // 对应通过不同的方法实现处理
      switch ($postObj->Event) {
        case 'subscribe':
        // 关注事件和未关注扫描二维码事件
          $this->doSubscribe($postObj);
          break;
        case 'unsubscribe':
        // 取消关注事件
          $this->doUnsubscribe($postObj);
          break;
        case 'SCAN':
        // 已关注扫描二维码事件
          $this->doScan($postObj);
          break;
        default:
          # code...
          break;
      }
    }
    // 关注事件和未关注扫描二维码事件
    private function doSubscribe($postObj)
    {
      if($postObj->EventKey){
        // 未关注扫描带参数的二维码事件
        $contentStr = '欢迎关注我们!请常联系!,您参加的活动代码为'.$postObj->EventKey;
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
        echo $resultStr;
      }else{
        // 关注事件
        $contentStr = '欢迎关注我们!请常联系!';
        $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
        echo $resultStr;
      }
    }
    // 已关注扫描二维码事件
    private function doScan($postObj)
    {
      // 已关注扫描带参数的二维码事件
      $contentStr = '您已经是公众号会员了,您参加的活动是:'.$postObj->EventKey;
      $resultStr = sprintf($this->textTpl,$postObj->FromUserName,$postObj->ToUserName,time(),'text',$contentStr);
      echo $resultStr;
    }
    // 取消事件事件
    private function doUnsubscribe($postObj)
    {
      file_put_contents('./likai.txt',$postObj->FromUserName,FILE_APPEND);
      // 记录用户离开的唯一值(openID),时间
      // 删除用户相关的一些信息
    }
    // 获取用户openID列表
    public function getUserList()
    {
      // 1.URL
      $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$this->getAccessToken();
      // 2.请求方式
      // 3.发送请求
      $content = $this->request($url);
      // 4.处理返回值
      $content = json_decode($content);
      echo header('Content-Type:text/html;charset=utf-8');
      // 显示输出
      echo '用户总数为:'.$content->total.'<br />';
      echo '本次获取数为:'.$content->count.'<br />';
      // 遍历数组显示用户列表
      foreach ($content->data->openid as $key => $value) {
        echo '<a href = "http://localhost/wechat/do2.php?openid='.$value.'">'.($key+1).'<span style="color:#f44336">√</span>'.$value.'</a><br />';
      }
    }
    // 通过openID获取用户基本信息
    public function getUserInfo($openid)
    {
      // $openid = 'oGMVlw3nKFxY-XSfgTBN3dUjOKwk';
      // 1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->getAccessToken().'&openid='.$openid.'&lang=zh_CN';
      // 2.请求方式
      // 3.发送请求
      $content = $this->request($url);
      // 4.处理返回值
      $content = json_decode($content);
      switch ($content->sex) {
        case '1':
          $sex = '男';
          break;
        case '2':
          $sex = '女';
          break;
        default:
          $sex = '神秘';
          break;
      }
      echo '昵称:'.$content->nickname.'<br />';
      echo '性别:'.$sex.'<br />';
      echo '城市:'.$content->city.'<br />';
      echo '<img src="'.$content->headimgurl.'" style="width:100px;" /><br />';
      echo '关注时间:'.date('Y-m-d H:i:s',$content->subscribe_time);
    }
    // 上传素材
    public function uploadFile()
    {
      // 1.url
      $type = 'image';
      $url = 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->getAccessToken().'&type='.$type;
      // 2.请求方式
      // php5.6以上文件上传方法需要注意，查看以下链接
      // http://blog.csdn.net/zhouzme/article/details/51050980
      $data = array(
          'media' => '@D:\phpStudy\WWW\wechat\666.jpg'
      );
      // 3.发送请求
      $content = $this->request($url,true,'post',$data);
      // 4.处理返回值
      $media_id = json_decode($content)->media_id;
      echo $media_id;
    }
    // 获取下载素材
    public function download()
    {
      $media_id = 'F9nDoxI-0zuvlH4WOMGs4B697FJ0AHOmc_WzlBsON3jTWHaJTtzxiQawTT9ZGZrJ';
      $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$media_id;
      // echo $url;die();
      $content = $this->request($url);
      // echo $content;
      file_put_contents('new666.jpg',$content);
    }
    // 创建菜单
    public function createMenu()
    {
      // 1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->getAccessToken();
      // 2.请求方式
      $data = '{
              "button":[
               {
                    "type":"click",
                    "name":"最新资讯",
                    "key":"news"
                },
                {
                     "name":"点我",
                     "sub_button":[
                     {
                         "type":"view",
                         "name":"百度",
                         "url":"http://www.baidu.com/"
                      },
                      {
                         "type": "scancode_push",
                         "name": "扫码推事件",
                         "key": "rselfmenu_0_1",
                         "sub_button": [ ]
                      }
                    ]
                 }]
           }';
      // 3.发送请求
      $content = $this->request($url,true,'post',$data);
      // 4.处理返回
      $content = json_decode($content);
      if($content->errcode == 0){
        echo '创建菜单成功';
      }else{
        echo '创建失败';
        echo '错误代码'.$content->errcode;
      }
    }
    // 查询菜单
    public function showMenu()
    {
      // 1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$this->getAccessToken();
      // 2.请求方式
      // 3.发送请求
      $content = $this->request($url);
      // 4.处理返回值
      var_dump($content);
    }
    // 删除菜单
    public function delMenu()
    {
      // 1.url
      $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$this->getAccessToken();
      // 2.请求方式
      // 3.发送请求
      $content = $this->request($url);
      // 4.处理返回值
      $content = json_decode($content);
      if($content->errcode == 0){
        echo '删除菜单成功';
      }else{
        echo '删除失败';
        echo '错误代码'.$content->errcode;
      }
    }
}