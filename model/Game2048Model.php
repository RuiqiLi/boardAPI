<?php

require_once __DIR__ . '/DB.php';


class Game2048Model {

  private $db;

  function __construct() {
    $this->db = DB::instance();
  }

  public function recordScore($user_id, $score, $max) {
    $sql = "SELECT score, max from t_game2048 where user_id = $user_id";
    $row = $this->db->resultRow($sql);
    if (isset($row['score'])) {
      if ($score > $row['score']) {
        $sql = "UPDATE t_game2048 set score = $score, max = $max where user_id = $user_id";
        $this->db->query($sql);
      }
    } else {
      $sql = "INSERT into t_game2048(user_id, score, max) values($user_id, $score, $max)";
      $this->db->query($sql);
    }
  }

  public function getBestScore($user_id) {
    $best_score = 0;
    $sql = "SELECT score from t_game2048 where user_id = $user_id";
    $row = $this->db->resultRow($sql);
    if (isset($row['score'])) {
      $best_score = $row['score'];
    }
    return $best_score;
  }

  public function getRank($limit) {
    $sql = "SELECT user_id, score, max, updated from t_game2048 order by score desc limit $limit";
    $rank = $this->db->resultArray($sql);
    return $rank;
  }
}
