<?php

require_once __DIR__ . '/Controller.php';


class Game2048 extends Controller {

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
    $this->loadModel('game2048');
    $this->session_model->resetClock($session_id);
    $this->open_id = $this->session_model->getOpenIdBySessionId($session_id);
    $this->uid = $this->user_model->getUidByOpenId($this->open_id);
    if ($this->uid == -1) {
      $this->errorResponse('notLogin', '用户未登录');
    }
  }

  public function switchOp() {
    switch ($_GET['op']) {
      case 'recordScore':
        $this->recordScore();
        break;

      case 'getBestScore':
        $this->getBestScore();
        break;

      case 'getRank':
        $this->getRank();
        break;

      default:
        $this->errorResponse('wrongOp', 'op参数无效：' . $_GET['op']);
    }
  }

  protected function recordScore() {
    if (isset($_GET['score']) && isset($_GET['max'])) {
      $score = intval($_GET['score']);
      $max = intval($_GET['max']);
      $this->game2048_model->recordScore($this->uid, $score, $max);
      $this->response(array(
        'ok' => true
      ));
    } else {
      $this->errorResponse('lackParams', '缺少score或max参数');
    }
  }

  protected function getBestScore() {
    $best_score = $this->game2048_model->getBestScore($this->uid);
    $this->response(array(
      'ok' => true,
      'bestScore' => $best_score
    ));
  }

  protected function getRank() {
    $rank = $this->game2048_model->getRank(100);
    $this->response(array(
      'ok' => true,
      'rank' => $rank
    ));
  }

}

$game2048 = new Game2048();
$game2048->switchOp();
