<?php

require_once __DIR__ . '/DB.php';


class AvalonModel {

  private $db;

  function __construct() {
    $this->db = DB::instance();
  }

  // Get all room data
  public function getStatus($uid) {
    $status_info = array('roomNo' => -1);
    $sql = "SELECT avalon_room_id, player_no, user_role from t_user_avalon where user_id = $uid";
    $row = $this->db->resultRow($sql);
    if (isset($row['avalon_room_id'])) {
      $room_no = $row['avalon_room_id'];
      $sql = "SELECT * from t_avalon_room where id = $room_no";
      $room_row = $this->db->resultRow($sql);
      if (isset($room_row['id'])) {
        $status_info['roomNo'] = $room_no;
        $status_info['playerNo'] = $row['player_no'];
        $status_info['userRole'] = $row['user_role'];
        $status_info['playerNum'] = $room_row['player_num'];
        $status_info['captainNo'] = $room_row['captain_no'];
        $status_info['delayMax'] = $room_row['delay_max'];
        $status_info['badKnowOthers'] = ($room_row['bad_know_others'] == 1 ? true : false);
        $status_info['captainCanVote'] = ($room_row['captain_can_vote'] == 1 ? true : false);
        $status_info['turn'] = $room_row['turn'];
        $status_info['missionStatus'] = $room_row['mission_status'];
        $status_info['voteStatus'] = $room_row['vote_status'];
        $status_info['killPlayer'] = $room_row['kill_player'];
        $status_info['time'] = strtotime($room_row['updated']);
        // get other player infos
        $sql = "SELECT user_id, player_no, user_role, nick_name, gender, avatar_url, real_name from t_user_avalon t1, t_user t2 where t1.avalon_room_id = $room_no and t2.id = t1.user_id order by player_no asc";
        $result = $this->db->resultArray($sql);
        // clear result
        $new_result = array();
        foreach ($result as $row) {
          $new_result[] = array(
            'userId' => $row['user_id'],
            'playerNo' => $row['player_no'],
            'userRole' => $row['user_role'],
            'nickName' => $row['nick_name'],
            'gender' => $row['gender'],
            'avatarUrl' => $row['avatar_url'],
            'realName' => $row['real_name']
          );
        }
        $status_info['players'] = $new_result;
        return $status_info;
      } else {
        $sql = "DELETE from t_user_avalon where user_id = $uid";
        $this->db->query($sql);
      }
    }
    return $status_info;
  }

  public function createRoom($player_num, $user_id) {
    $captain_no = rand(1, $player_num);
    $sql = "INSERT into t_avalon_room(player_num, captain_no) values($player_num, $captain_no)";
    $room_no = $this->db->queryReturnKey($sql);
    $sql = "INSERT into t_user_avalon(avalon_room_id, user_id, player_no) values($room_no, $user_id, 1)";
    $this->db->query($sql);
    return $room_no;
  }

  public function checkRoom($room_no) {
    $sql = "SELECT id from t_avalon_room where id = $room_no";
    $row = $this->db->resultRow($sql);
    return isset($row['id']);
  }

  public function dismissRoom($room_no) {
    $sql = "DELETE from t_avalon_room where id = $room_no";
    $this->db->query($sql);
    $sql = "DELETE from t_user_avalon where avalon_room_id = $room_no";
    $this->db->query($sql);
  }

  public function resetRoom($room_no, $player_num) {
    // 重置房间信息
    $captain_no = rand(1, $player_num);
    $sql = "UPDATE t_avalon_room set
      captain_no = $captain_no,
      turn = 0,
      mission_status = '[]',
      vote_status = '[]',
      kill_player = 0
    where id = $room_no";
    $this->db->query($sql);
    // 重置玩家序号
    $player_nos = range(1, $player_num);
    shuffle($player_nos);
    $sql = "UPDATE t_user_avalon SET player_no = CASE player_no";
    foreach ($player_nos as $key => $new_player_no) {
      $old_player_no = $key + 1;
      $sql .= " WHEN $old_player_no THEN $new_player_no";
    }
    $range = '('.implode(",", range(1, $player_num)).')';
    $sql .= " END WHERE avalon_room_id = $room_no and player_no in $range";
    $this->db->query($sql);
  }

  public function changeDelay($room_no, $delay_max) {
    $sql = "UPDATE t_avalon_room set delay_max = $delay_max where id = $room_no";
    $this->db->query($sql);
  }

  public function changeBadKnowOthers($room_no, $bad_know_others) {
    $sql = "UPDATE t_avalon_room set bad_know_others = $bad_know_others where id = $room_no";
    $this->db->query($sql);
  }
  
  public function changeCaptainCanVote($room_no, $captainCanVote) {
    $sql = "UPDATE t_avalon_room set captain_can_vote = $captainCanVote where id = $room_no";
    $this->db->query($sql);
  }

  // Enter room and dismiss old room if exists
  public function enterRoom($room_no, $user_id) {
    $sql = "SELECT player_num from t_avalon_room where id = $room_no";
    $row = $this->db->resultRow($sql);
    if (isset($row['player_num'])) {
      $player_num = $row['player_num'];
      $sql = "SELECT max(player_no) as prev_no from t_user_avalon where avalon_room_id = $room_no";
      $row = $this->db->resultRow($sql);
      if (isset($row['prev_no'])) {
        $player_no = $row['prev_no'] + 1;
        if ($player_no <= $player_num) {
          $sql = "SELECT avalon_room_id from t_user_avalon where user_id = $user_id";
          $row = $this->db->resultRow($sql);
          if (isset($row['avalon_room_id'])) {
            if ($row['avalon_room_id'] == $room_no) {
              return true;
            } else {
              $this->dismissRoom($row['avalon_room_id']);
            }
          }
          $sql = "INSERT into t_user_avalon(avalon_room_id, user_id, player_no) values($room_no, $user_id, $player_no)";
          $this->db->query($sql);
          return true;
        }
      }
    }
    return false;
  }

  public function gameStart($room_no, $player_num) {
    // 随机身份
    $roleArray = array(
      5  => array('ml', 'pxwe', 'zc', 'mgn', 'ck'),
      6  => array('ml', 'pxwe', 'zc', 'zc', 'mgn', 'ck'),
      7  => array('ml', 'pxwe', 'zc', 'zc', 'mgn', 'abl', 'ck'),
      8  => array('ml', 'pxwe', 'zc', 'zc', 'zc', 'mgn', 'ck', 'zy'),
      9  => array('ml', 'pxwe', 'zc', 'zc', 'zc', 'zc', 'mdld', 'mgn', 'ck'),
      10 => array('ml', 'pxwe', 'zc', 'zc', 'zc', 'zc', 'mdld', 'mgn', 'abl', 'ck')
    );
    $roles = $roleArray[$player_num];
    shuffle($roles);
    $sql = "UPDATE t_user_avalon SET user_role = CASE player_no";
    foreach ($roles as $key => $role) {
      $player_no = $key + 1;
      $sql .= " WHEN $player_no THEN '$role'";
    }
    $range = '('.implode(",", range(1, $player_num)).')';
    $sql .= " END WHERE avalon_room_id = $room_no and player_no in $range";
    $this->db->query($sql);
    // 游戏开始
    $sql = "UPDATE t_avalon_room set turn = 1, mission_status = '[]', kill_player = 0 where id = $room_no";
    $this->db->query($sql);
  }

  // Return if has changed mission_status
  public function doMission($room_no, $player_num, $turn, $name, $player_no, $vote_type, $mission_status) {
    $mission_require = array(
      5  => array(2, 3, 2, 3, 3),
      6  => array(2, 3, 4, 3, 4),
      7  => array(2, 3, 3, 4, 4),
      8  => array(3, 4, 4, 5, 5),
      9  => array(3, 4, 4, 5, 5),
      10 => array(3, 4, 4, 5, 5),
    );
    // 首先做任务（检测本轮重复）
    $mission_status = json_decode($mission_status, true);
    $this_mission = isset($mission_status[$turn-1]) ? $mission_status[$turn-1] : array(
      'turn' => $turn,
      'win' => '',
      'good' => 0,
      'bad' => 0,
      'votes' => array()
    );
    foreach ($this_mission['votes'] as $vote) {
      if ($vote['playerNo'] == $player_no) {
        return false;
      }
    }
    $this_mission['votes'][] = array(
      'playerNo' => $player_no,
      'name' => $name,
      'voteType' => $vote_type
    );
    if ($vote_type == 1) {
      $this_mission['good']++;
    } else if ($vote_type == 0) {
      $this_mission['bad']++;
    }
    // 如果本轮任务完成，给出任务结果，在turn变化前写回
    if (count($this_mission['votes']) == $mission_require[$player_num][$turn-1]) {
      $win = 'bad';
      if ($this_mission['bad'] == 0 || $this_mission['bad'] == 1 && $turn == 4 && $player_num >= 7) {
        $win = 'good';
      }
      $this_mission['win'] = $win;
      $mission_status[$turn-1] = $this_mission;
      // turn变化
      $good_win_count = $bad_win_count = 0;
      foreach ($mission_status as $mission) {
        if ($mission['win'] == 'good') {
          $good_win_count++;
        } else if ($mission['win'] == 'bad') {
          $bad_win_count++;
        }
      }
      if ($bad_win_count == 3) {
        $turn = 7;
      } else if ($good_win_count == 3) {
        $turn = 6;
      } else {
        $turn++;
      }
    } else {
      $mission_status[$turn-1] = $this_mission;
    }
    $mission_status = json_encode($mission_status, JSON_UNESCAPED_UNICODE);
    $sql = "UPDATE t_avalon_room set turn = $turn, mission_status = '$mission_status' where id = $room_no";
    $this->db->query($sql);
    return true;
  }

  public function killPlayer($room_no, $kill_player) {
    $sql = "UPDATE t_avalon_room set kill_player = $kill_player where id = $room_no";
    $this->db->query($sql);
  }

  public function vote($room_no, $player_no, $name, $type, $player_num, $vote_status) {
    if ($type == 0 || $type == 2 || $type == -2) {
      // 重新发起投票
      $vote_status_parsed = array(
        array(
          'playerNo' => $player_no,
          'name' => $name,
          'type' => 0,
          'captainType' => $type,
          'exitVote' => false
        )
      );
      $vote_status = $this->writeBackVoteStatus($room_no, $vote_status_parsed);
      return $vote_status;
    }
    $vote_status_parsed = json_decode($vote_status, true);
    // 检测重复投票
    foreach ($vote_status_parsed as $one_vote) {
      if ($one_vote['playerNo'] == $player_no) {
        return $vote_status;
      }
    }
    // 增加投票
    $vote_status_parsed[] = array(
      'playerNo' => $player_no,
      'name' => $name,
      'type' => $type,
      'exitVote' => false
    );
    $vote_status = $this->writeBackVoteStatus($room_no, $vote_status_parsed);
    return $vote_status;
  }

  public function getVoteStatus($room_no) {
    $sql = "SELECT vote_status, captain_can_vote from t_avalon_room where id = $room_no";
    $row = $this->db->resultRow($sql);
    if (isset($row['vote_status'])) {
      return array(
        'voteStatus' => $row['vote_status'],
        'captainCanVote' => $row['captain_can_vote']
      );
    }
    return array(
      'voteStatus' => "[]",
      'captainCanVote' => 0
    );
  }

  public function exitVote($room_no, $player_no, $vote_status) {
    $vote_status_parsed = json_decode($vote_status, true);
    foreach ($vote_status_parsed as &$one_vote) {
      if ($one_vote['playerNo'] == $player_no) {
        $one_vote['exitVote'] = true;
        $vote_status = $this->writeBackVoteStatus($room_no, $vote_status_parsed);
        break;
      }
    }
    return $vote_status;
  }

  private function writeBackVoteStatus($room_no, $vote_status_parsed) {
    $vote_status = json_encode($vote_status_parsed, JSON_UNESCAPED_UNICODE);
    $sql = "UPDATE t_avalon_room set vote_status = '$vote_status' where id = $room_no";
    $this->db->query($sql);
    return $vote_status;
  }

  // return true if eq
  public function compareTime($room_no, $time) {
    $sql = "SELECT updated from t_avalon_room where id = $room_no";
    $row = $this->db->resultRow($sql);
    return (strtotime($row['updated']) == $time);
  }

}
