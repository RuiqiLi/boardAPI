<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/AES/wxBizDataCrypt.php';
require_once __DIR__ . '/AES/random.php';


class Login extends Controller {

  private $url;
  private $appid;
  private $secret;
  private $grant_type;

  function __construct() {
    parent::__construct();
    $config = parse_ini_file(__DIR__ . '/config/config.ini', true);
    $this->appid = $config['wechat']['appid'];
    $this->secret = $config['wechat']['secret'];
    $this->url = 'https://api.weixin.qq.com/sns/jscode2session';
    $this->grant_type = 'authorization_code';
  }

  public function checkSession() {
    $session_id = isset($_REQUEST['sessionid']) ? $_REQUEST['sessionid'] : "";
    if (!empty($session_id)) {
      $this->loadModel('session');
      $result = $this->session_model->checkSession($session_id);
      if ($result) {
        $open_id = $this->session_model->getOpenIdBySessionId($session_id);
        $this->loadModel('user');
        $is_admin = $this->user_model->checkAdminByOpenId($open_id);
        $real_name = $this->user_model->getRealNameByOpenId($open_id);
        $this->user_model->addLoginTimesByOpenId($open_id);
        $res = array(
          'errCode' => 0,
          'sessionid' => $session_id,
          'isAdmin' => $is_admin,
          'realName' => $real_name
        );
        echo json_encode($res);
        exit();
      }
    }
  }

  public function doLogin() {
    // 从客户端获取数据
    $code = $_REQUEST['code'];
    $iv = $_REQUEST['iv'];
    $encrypted_data = $_REQUEST['encryptedData'];
    // 从服务器获取数据
    $url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%",
      $this->url,
      $this->appid,
      $this->secret,
      $code,
      $this->grant_type
    );
    $user_data = json_decode(file_get_contents($url));
    $session_key = $user_data->session_key;
    $open_id = $user_data->openid;
    // 解密数据
    $data = "";
    $pc = new WXBizDataCrypt($this->appid, $session_key);
    $err_code = $pc->decryptData($encrypted_data, $iv, $data);
    if ($err_code == 0) {
      $this->recordUserInfo($data);
      $session_id = randomFromDev(128);
      $this->updateSession($session_id, $session_key, $open_id);
      $this->loadModel('user');
      $is_admin = $this->user_model->checkAdminByOpenId($open_id);
      $real_name = $this->user_model->getRealNameByOpenId($open_id);
      $this->user_model->addLoginTimesByOpenId($open_id);
      $res = array(
        'errCode' => 0,
        'sessionid' => $session_id,
        'isAdmin' => $is_admin,
        'realName' => $real_name
      );
      echo json_encode($res);
    } else {
      $res = array(
        'errCode' => $err_code
      );
      echo json_encode($res);
    }
  }

  private function recordUserInfo($data) {
    $data = json_decode($data);
    $open_id = $data->openId;
    $nick_name = $data->nickName;
    $gender = $data->gender;
    $city = $data->city;
    $province = $data->province;
    $country = $data->country;
    $avatar_url = $data->avatarUrl;
    $union_id = isset($data->unionId) ? $data->unionId : "";
    $this->loadModel('user');
    $this->user_model->insertOrUpdateUserInfo($open_id, $nick_name, $gender, $city, $province, $country, $avatar_url, $union_id);
  }

  private function updateSession($session_id, $session_key, $open_id) {
    $this->loadModel('session');
    $this->session_model->insertOrUpdateSession($session_id, $session_key, $open_id);
  }

}

$login = new Login();
$login->checkSession();
$login->doLogin();
