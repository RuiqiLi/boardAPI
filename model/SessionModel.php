<?php

require_once __DIR__ . '/DB.php';


class SessionModel {

  private $db;

  function __construct() {
    $this->db = DB::instance();
  }

  public function insertOrUpdateSession($session_id, $session_key, $open_id) {
    $sql = "UPDATE t_session set
      session_id = '$session_id',
      session_key = '$session_key'
    where open_id = '$open_id'";
    $rows_affected = $this->db->queryReturnRows($sql);
    if ($rows_affected != 1) {
      $sql = "INSERT into t_session(session_id, session_key, open_id)
              values('$session_id', '$session_key', '$open_id')";
      $this->db->query($sql);
    }
  }

  public function checkSession($session_id) {
    $sql = "SELECT updated, current_timestamp() as now from t_session where session_id = '$session_id'";
    $row = $this->db->resultRow($sql);
    if (!isset($row['updated']) || empty($row['updated'])) {
      return false;
    }
    $updated = strtotime($row['updated']);
    $now = strtotime($row['now']);
    if ($now - $updated > 7200) {
      return false;
    }
    return true;
  }

  public function resetClock($session_id) {
    $sql = "UPDATE t_session set updated = current_timestamp() where session_id = '$session_id'";
    $this->db->query($sql);
  }

  public function getOpenIdBySessionId($session_id) {
    $open_id = "";
    $sql = "SELECT open_id from t_session where session_id = '$session_id'";
    $row = $this->db->resultRow($sql);
    if (isset($row['open_id'])) {
      $open_id = $row['open_id'];
    }
    return $open_id;
  }

}
