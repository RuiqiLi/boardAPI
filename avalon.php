<?php

require_once __DIR__ . '/Controller.php';


class Avalon extends Controller {

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
    $this->loadModel('avalon');
    $this->session_model->resetClock($session_id);
    $this->open_id = $this->session_model->getOpenIdBySessionId($session_id);
    $this->uid = $this->user_model->getUidByOpenId($this->open_id);
    if ($this->uid == -1) {
      $this->errorResponse('notLogin', '用户未登录');
    }
  }

  public function switchOp() {
    switch ($_GET['op']) {
      case 'status':
        $this->status();
        break;

      case 'createRoom':
        $this->createRoom();
        break;

      case 'dismissRoom':
        $this->dismissRoom();
        break;

      case 'resetRoom':
        $this->resetRoom();
        break;

      // case 'changeDelay':
      //   $this->changeDelay();
      //   break;

      case 'changeBadKnowOthers':
        $this->changeBadKnowOthers();
        break;

      case 'changeCaptainCanVote':
        $this->changeCaptainCanVote();
        break;

      case 'enterRoom':
        $this->enterRoom();
        break;

      case 'gameStart':
        $this->gameStart();
        break;

      case 'doMission':
        $this->doMission();
        break;

      case 'killPlayer':
        $this->killPlayer();
        break;

      case 'setRealName':
        $this->setRealName();
        break;

      case 'vote':
        $this->vote();
        break;

      case 'voteStatus':
        $this->voteStatus();
        break;

      // case 'exitVote':
      //   $this->exitVote();
      //   break;

      case 'heartBeat':
        $this->heartBeat();
        break;

      default:
        $this->errorResponse('wrongOp', 'op参数无效：' . $_GET['op']);
    }
  }

  protected function status() {
    $this->lock();
    $status_info = $this->avalon_model->getStatus($this->uid);
    $this->unlock();
    $this->response(array(
      'ok' => true,
      'statusInfo' => $status_info
    ));
  }

  protected function createRoom() {
    if (isset($_GET['playerNum'])) {
      $player_num = intval($_GET['playerNum']);
      $this->lock();
      $room_no = $this->avalon_model->createRoom($player_num, $this->uid);
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'roomNo' => $room_no
      ));
    } else {
      $this->errorResponse('lackParams', '缺少playerNum参数');
    }
  }

  protected function dismissRoom() {
    if (isset($_GET['roomNo'])) {
      $room_no = intval($_GET['roomNo']);
      $this->lock();
      $this->avalon_model->dismissRoom($room_no);
      $this->unlock();
      $this->response(array('ok' => true));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo参数');
    }
  }

  protected function resetRoom() {
    if (isset($_GET['roomNo']) && isset($_GET['playerNum'])) {
      $room_no = intval($_GET['roomNo']);
      $player_num = intval($_GET['playerNum']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $this->avalon_model->resetRoom($room_no, $player_num);
      $status_info = $this->avalon_model->getStatus($this->uid);
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或playerNum参数');
    }
  }

  protected function changeDelay() {
    if (isset($_GET['roomNo']) && isset($_GET['delayMax'])) {
      $room_no = intval($_GET['roomNo']);
      $delay_max = intval($_GET['delayMax']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $this->avalon_model->changeDelay($room_no, $delay_max);
      $this->unlock();
      $this->response(array('ok' => true));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或delayMax参数');
    }
  }

  protected function changeBadKnowOthers() {
    if (isset($_GET['roomNo']) && isset($_GET['badKnowOthers'])) {
      $room_no = intval($_GET['roomNo']);
      $bad_know_others = intval($_GET['badKnowOthers']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $this->avalon_model->changeBadKnowOthers($room_no, $bad_know_others);
      $this->unlock();
      $this->response(array('ok' => true));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或badKnowOthers参数');
    }
  }
  
  protected function changeCaptainCanVote() {
    if (isset($_GET['roomNo']) && isset($_GET['captainCanVote'])) {
      $room_no = intval($_GET['roomNo']);
      $captain_can_vote = intval($_GET['captainCanVote']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $this->avalon_model->changeCaptainCanVote($room_no, $captain_can_vote);
      $this->unlock();
      $this->response(array('ok' => true));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或captainCanVote参数');
    }
  }

  protected function enterRoom() {
    if (isset($_GET['roomNo'])) {
      $room_no = intval($_GET['roomNo']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $check = $this->avalon_model->enterRoom($room_no, $this->uid);
      if (!$check) {
        $this->unlock();
        $this->errorResponse('enterRoomFailed', '进入房间失败');
      }
      $status_info = $this->avalon_model->getStatus($this->uid);
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo参数');
    }
  }

  protected function gameStart() {
    if (isset($_GET['roomNo'])) {
      $room_no = intval($_GET['roomNo']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $status_info = $this->avalon_model->getStatus($this->uid);
      if ($status_info['turn'] == 0) {
        $this->avalon_model->gameStart($room_no, $status_info['playerNum']);
        $status_info = $this->avalon_model->getStatus($this->uid);
      }
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo参数');
    }
  }

  protected function doMission() {
    if (isset($_GET['roomNo']) && isset($_GET['turn']) && isset($_GET['voteType'])) {
      $room_no = intval($_GET['roomNo']);
      $turn = intval($_GET['turn']);
      $vote_type = intval($_GET['voteType']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $status_info = $this->avalon_model->getStatus($this->uid);
      if ($status_info['turn'] != $turn) {
        $this->unlock();
        $this->response(array(
          'ok' => false,
          'why' => 'wrongTurn',
          'statusInfo' => $status_info
        ));
      }
      // 好人不能投坏票
      if ($vote_type == 0 && ($status_info['userRole'] == 'ml' || $status_info['userRole'] == 'pxwe' || $status_info['userRole'] == 'zc')) {
        $this->unlock();
        $this->response(array(
          'ok' => false,
          'why' => 'goodGuy',
          'statusInfo' => $status_info
        ));
      }
      $name = $this->getNameFromStatusInfo($status_info);
      $change = $this->avalon_model->doMission($room_no, $status_info['playerNum'], $turn, $name, $status_info['playerNo'], $vote_type, $status_info['missionStatus']);
      if ($change) {
        $status_info = $this->avalon_model->getStatus($this->uid);
        $this->unlock();
        $this->response(array(
          'ok' => true,
          'statusInfo' => $status_info
        ));
      }
      $this->unlock();
      $this->response(array(
        'ok' => false,
        'why' => 'repeat',
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或turn或voteType参数');
    }
  }

  protected function killPlayer() {
    if (isset($_GET['roomNo']) && isset($_GET['killPlayer'])) {
      $room_no = intval($_GET['roomNo']);
      $kill_player = intval($_GET['killPlayer']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $status_info = $this->avalon_model->getStatus($this->uid);
      if ($status_info['turn'] == 6 && $status_info['killPlayer'] == 0 && $status_info['userRole'] == 'ck') {
        $this->avalon_model->killPlayer($room_no, $kill_player);
        $status_info = $this->avalon_model->getStatus($this->uid);
      }
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或killPlayer参数');
    }
  }

  protected function setRealName() {
    if (isset($_GET['realName'])) {
      $real_name = trim($_GET['realName']);
      $this->user_model->setRealName($this->uid, $real_name);
      $this->response(array(
        'ok' => true,
        'realName' => $real_name
      ));
    } else {
      $this->errorResponse('lackParams', '缺少realName参数');
    }
  }

  protected function vote() {
    if (isset($_GET['roomNo']) && isset($_GET['type'])) {
      $room_no = intval($_GET['roomNo']);
      $type = intval($_GET['type']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $status_info = $this->avalon_model->getStatus($this->uid);
      $name = $this->getNameFromStatusInfo($status_info);
      $vote_status = $this->avalon_model->vote($room_no, $status_info['playerNo'], $name, $type, $status_info['playerNum'], $status_info['voteStatus']);
      $status_info['voteStatus'] = $vote_status;
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或type参数');
    }
  }

  protected function voteStatus() {
    if (isset($_GET['roomNo'])) {
      $room_no = intval($_GET['roomNo']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $vote_status = $this->avalon_model->getVoteStatus($room_no);
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'voteStatus' => $vote_status['voteStatus'],
        'captainCanVote' => $vote_status['captainCanVote']
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo参数');
    }
  }

  protected function exitVote() {
    if (isset($_GET['roomNo'])) {
      $room_no = intval($_GET['roomNo']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $status_info = $this->avalon_model->getStatus($this->uid);
      $vote_status = $this->avalon_model->exitVote($room_no, $status_info['playerNo'], $status_info['voteStatus']);
      $status_info['voteStatus'] = $vote_status;
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo参数');
    }
  }

  protected function heartBeat() {
    if (isset($_GET['roomNo']) && isset($_GET['time'])) {
      $room_no = intval($_GET['roomNo']);
      $time = intval($_GET['time']);
      $this->lock();
      $this->checkRoomAutoUnlockResponse($room_no);
      $eq = $this->avalon_model->compareTime($room_no, $time);
      if ($eq) {
        $this->unlock();
        $this->response(array(
          'ok' => true
        ));
      }
      $status_info = $this->avalon_model->getStatus($this->uid);
      $this->unlock();
      $this->response(array(
        'ok' => true,
        'statusInfo' => $status_info
      ));
    } else {
      $this->errorResponse('lackParams', '缺少roomNo或time参数');
    }
  }

  protected function lock() {
    parent::lock(parent::AVALON);
  }

  protected function unlock() {
    parent::unlock(parent::AVALON);
  }

  private function checkRoomAutoUnlockResponse($room_no) {
    $check = $this->avalon_model->checkRoom($room_no);
    if (!$check) {
      $this->unlock();
      $this->errorResponse('checkRoomFailed', '房间不存在');
    }
  }

  private function getNameFromStatusInfo($status_info) {
    $name = "";
    $players = $status_info['players'];
    foreach ($players as $player) {
      if ($player['playerNo'] == $status_info['playerNo']) {
        $name = $player['realName'] ? $player['realName'] : $player['nickName'];
        break;
      }
    }
    return $name;
  }

}

$avalon = new Avalon();
$avalon->switchOp();
