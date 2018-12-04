<?php

date_default_timezone_set('PRC');

class Controller {

  protected $session_model;
  protected $user_model;
  protected $avalon_model;
  protected $game2048_model;
  protected $message_model;
  // for lock
  private $avalon_lock;
  const AVALON = 'avalon_lock';
  // end

  function __construct() {
    // TODO check GET & POST & REQUEST
  }

  protected function loadModel($model_name) {
    switch ($model_name) {
      case 'session':
        if (!$this->session_model) {
          require_once __DIR__ . '/model/SessionModel.php';
          $this->session_model = new SessionModel();
        }
        break;

      case 'user':
        if (!$this->user_model) {
          require_once __DIR__ . '/model/UserModel.php';
          $this->user_model = new UserModel();
        }
        break;

      case 'avalon':
        if (!$this->avalon_model) {
          require_once __DIR__ . '/model/AvalonModel.php';
          $this->avalon_model = new AvalonModel();
        }
        break;

      case 'game2048':
        if (!$this->game2048_model) {
          require_once __DIR__ . '/model/Game2048Model.php';
          $this->game2048_model = new Game2048Model();
        }
        break;

      case 'message':
        if (!$this->message_model) {
          require_once __DIR__ . '/model/MessageModel.php';
          $this->message_model = new MessageModel();
        }
        break;

      default:
        break;
    }
  }

  protected function errorResponse($error_type, $error_message) {
    $this->response(array(
      'ok' => false,
      'errType' => $error_type,
      'errMsg' => $error_message
    ));
  }

  protected function response($res, $exit = true) {
    echo json_encode($res);
    if ($exit) {
      exit();
    }
  }

  protected function lock($lock) {
    $this->$lock = fopen("./locks/$lock", 'r');
    flock($this->$lock, LOCK_EX);  // 排他锁
  }

  protected function unlock($lock) {
    flock($this->$lock, LOCK_UN);
    fclose($this->$lock);
  }

}
