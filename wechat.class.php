<?php

/**
 * @Author: jsy135135
 * @email:732677288@qq.com
 * @Date:   2017-12-26 11:53:10
 * @Last Modified by:   jsy135135
 * @Last Modified time: 2017-12-29 10:46:06
 */

// 引入配置文件
require './wechat.cfg.php';

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
      $this->newsTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>%s</Articles>
            </xml>";
      $this->itemTpl = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
      $this->imageTpl = "<xml>
            <ToUserName>
                <![CDATA[%s]]>
            </ToUserName>
            <FromUserName>
                <![CDATA[%s]]>
            </FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType>
                <![CDATA[image]]>
            </MsgType>
            <Image>
                <MediaId>
                    <![CDATA[%s]]>
                </MediaId>
            </Image>
            </xml>";
      $this->musicTpl = "<xml>
            <ToUserName>
                <![CDATA[%s]]>
            </ToUserName>
            <FromUserName>
                <![CDATA[%s]]>
            </FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType>
                <![CDATA[music]]>
            </MsgType>
            <Music>
                <Title>
                    <![CDATA[%s]]>
                </Title>
                <Description>
                    <![CDATA[%s]]>
                </Description>
                <MusicUrl>
                    <![CDATA[%s]]>
                </MusicUrl>
                <HQMusicUrl>
                    <![CDATA[%s]]>
                </HQMusicUrl>
                <ThumbMediaId>
                    <![CDATA[%s]]>
                </ThumbMediaId>
            </Music>
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
          if($keyword === '参加活动')
          {
            $this->sendPic($postObj);exit();
          }
          if($keyword === '音乐')
          {
            $this->sendMusic($postObj);exit();
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
        case 'CLICK':
        // 自定义菜单点击事件
          $this->doCLICK($postObj);
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
        $this->sendNews($postObj);die();
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
                         "sub_button": []
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
    // 自定义菜单点击事件处理
    public function doCLICK($postObj)
    {
      // 通过判断不同key值
      switch ($postObj->EventKey) {
        // 处理最新资讯的key news
        case 'news':
          $this->sendNews($postObj);
          break;

        default:
          # code...
          break;
      }
    }
    // 发送图文
    public function sendNews($postObj)
    {
      // 获取一些数据,可以从之前已经存储的数据库找
      $data = array(
        array(
          'Title' => '这条“路”，习近平关心了40多年',
          'Description' => '这是追梦之路，连接着脱贫致富的深切渴望。',
          'PicUrl' => 'http://dingyue.nosdn.127.net/8OrPmFDx5v1b=Oa3Gg7e8JRnA88YJ0KzgY3KloUEPjn7G1514451190566.jpg',
          'Url' => 'http://news.163.com/17/1228/16/D6OPQNV6000189FH.html'
        ),
        array(
          'Title' => '武警部队为什么要归中央军委统一领导？',
          'Description' => '昨天，武警部队调整建制归属的新闻刷爆了朋友圈',
          'PicUrl' => 'http://dingyue.nosdn.127.net/cfZUAjVlpBnyaWRzOTlffNJfB1C24HNPN=ZIWnTeE3UGm1514474020493transferflag.png',
          'Url' => 'http://news.163.com/17/1228/23/D6PFFTR60001875N.html'
        ),
        array(
          'Title' => '《全民道士》开机 "星女郎"张美娥将演重要角色',
          'Description' => '大连晚报8月27日报道 由潮旭银河文化传媒（大连）出品， 大连胜观文化传媒拍摄的网络大电影《全民道士》，昨天上午在大连滨海路开机。',
          'PicUrl' => 'http://img5.cache.netease.com/ent/2016/8/27/20160827091403f5057_550.jpg',
          'Url' => 'http://ent.163.com/16/0827/09/BVFDC4J3000300B1.html'
        ),
      );
      // 拼接单个新闻
      $items = '';
      foreach ($data as $key => $value) {
        $items .= sprintf($this->itemTpl,$value['Title'],$value['Description'],$value['PicUrl'],$value['Url']);
      }
      // 拼接图文消息xml
      $resultStr = sprintf($this->newsTpl,$postObj->FromUserName,$postObj->ToUserName,time(),count($data),$items);
      // 输出xml
      echo $resultStr;
    }
    // 发送图片
    public function sendPic($postObj)
    {
      $media_id = 'F9nDoxI-0zuvlH4WOMGs4B697FJ0AHOmc_WzlBsON3jTWHaJTtzxiQawTT9ZGZrJ';
      // 拼接图片模板
      $resultStr = sprintf($this->imageTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$media_id);
      echo $resultStr;
    }
    // 发送音乐
    public function sendMusic($postObj)
    {
      $Title = '窜天猴';
      $Description = '胡超';
      $MusicUrl = 'http://47.88.217.149/wechat/1.mp3';
      $HQMusicUrl = $MusicUrl;
      $ThumbMediaId = 'F9nDoxI-0zuvlH4WOMGs4B697FJ0AHOmc_WzlBsON3jTWHaJTtzxiQawTT9ZGZrJ';
      $resultStr = sprintf($this->musicTpl,$postObj->FromUserName,$postObj->ToUserName,time(),$Title,$Description,$MusicUrl,$HQMusicUrl,$ThumbMediaId);
      echo $resultStr;
      file_put_contents('musicTpl.xml',$resultStr);
    }
}