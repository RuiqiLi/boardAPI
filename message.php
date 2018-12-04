<?php

require_once __DIR__ . '/Controller.php';


class Message extends Controller {

  private $open_id;
  private $uid;

  function __construct() {
    parent::__construct();
    $op = isset($_GET['op']) ? $_GET['op'] : "";
    if (empty($op)) {
      $this->errorResponse('noOp', '缺少op参数');
    }
    $session_id = isset($_SERVER['HTTP_SESSIONID']) ? $_SERVER['HTTP_SESSIONID'] : "";
    if (empty($session_id)) {
      $this->errorResponse('notLogin', '用户未登录');
    }
    $this->loadModel('session');
    $this->loadModel('user');
    $this->loadModel('message');
    $this->session_model->resetClock($session_id);
    $this->open_id = $this->session_model->getOpenIdBySessionId($session_id);
    $this->uid = $this->user_model->getUidByOpenId($this->open_id);
    if ($this->uid == -1) {
      $this->errorResponse('notLogin', '用户未登录');
    }
  }

  public function switchOp() {
    switch ($_GET['op']) {

      case 'adminAll':
        $this->adminAllMessages();
        break;

      case 'all':
        $this->allMessages();
        break;

      case 'new':
        $this->newMessage();
        break;

      case 'getLetterList':
        $this->getLetterList();
        break;

      case 'getLetterContent':
        $this->getLetterContent();
        break;

      default:
        $this->errorResponse('wrongOp', 'op参数无效：' . $_GET['op']);
    }
  }

  protected function adminAllMessages() {
    $all_messages = $this->message_model->getAdminMessages();
    $this->response(array(
      'ok' => true,
      'adminAllMessages' => $all_messages
    ));
  }

  protected function allMessages() {
    $all_messages = $this->message_model->getAllMessages($this->uid);
    $this->response(array(
      'ok' => true,
      'allMessages' => $all_messages
    ));
  }

  protected function newMessage() {
    if (isset($_GET['message'])) {
      $message = trim($_GET['message']);
      if (empty($message)) {
        $this->errorResponse('messageEmpty', '没有内容');
      }
      $this->message_model->addMessage($this->uid, $message);
      $all_messages = $this->message_model->getAllMessages($this->uid);
      $this->response(array(
        'ok' => true,
        'allMessages' => $all_messages
      ));
    } else {
      $this->errorResponse('lackParams', '缺少message参数');
    }
  }

  protected function getLetterList() {
    $letter_list = $this->message_model->getLetterList();
    $this->response(array(
      'ok' => true,
      'letterList' => $letter_list
    ));
  }

  protected function getLetterContent() {
    if (isset($_GET['letterID'])) {
      $letter_id = intval($_GET['letterID']);
      $content = $this->message_model->getLetterContentByID($letter_id);
      $this->response(array(
        'ok' => true,
        'content' => $content
      ));
    } else {
      $this->errorResponse('lackParams', '缺少letterID参数');
    }
  }

}

$message = new Message();
$message->switchOp();
