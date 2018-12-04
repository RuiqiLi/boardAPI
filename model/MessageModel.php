<?php

require_once __DIR__ . '/DB.php';


class MessageModel {

  private $db;

  function __construct() {
    $this->db = DB::instance();
  }

  public function getAllMessages($user_id) {
    $sql = "SELECT id, type, message, created from t_message where user_id = $user_id and deleted = 0 order by created desc";
    $result = $this->db->resultArray($sql);
    return $result;
  }

  public function addMessage($user_id, $message) {
    $sql = "INSERT into t_message(user_id, message) values($user_id, '$message')";
    $this->db->query($sql);
  }

  public function getAdminMessages() {
    $sql = "SELECT id, user_id, type, message, created from t_message where deleted = 0 order by created desc";
    $result = $this->db->resultArray($sql);
    return $result;
  }

  public function getLetterList() {
    $sql = "SELECT id, title, created from t_letter where deleted = 0 order by created desc";
    $result = $this->db->resultArray($sql);
    return $result;
  }

  public function getLetterContentByID($letter_id) {
    $sql = "SELECT content from t_letter where id = $letter_id";
    $row = $this->db->resultRow($sql);
    if (isset($row['content'])) {
      return $row['content'];
    }
    return "{}";
  }

}
