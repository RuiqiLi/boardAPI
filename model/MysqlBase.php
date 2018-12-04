<?php

/**
 * Mysql base functions using mysqli
 * 2017-05-02 By Ricky
 */
class MysqlBase {

  private $mysqli;

  protected function __construct($db_host, $db_name, $db_user, $db_pwd) {
    $this->connect($db_host, $db_name, $db_user, $db_pwd);
  }

  private function connect($db_host, $db_name, $db_user, $db_pwd) {
    $mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
    if ($mysqli->connect_errno) {
      die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
    }
    $mysqli->query("set names 'utf8'");
    $this->mysqli = $mysqli;
  }

  /**
   * Execute sql statment, return true if succeed.
   */
  public function query($sql) {
    $r = $this->mysqli->query($sql);
    if ($r) {
      return true;
    }
    return false;
  }

  /**
   * Execute sql statment, returns the auto generated id used in the latest query.
   */
  public function queryReturnKey($sql) {
    $r = $this->mysqli->query($sql);
    if ($r) {
      return $this->mysqli->insert_id;
    }
    return false;
  }

  /**
   * Execute sql statment, return rows affected.
   */
  public function queryReturnRows($sql) {
    $this->mysqli->query($sql);
    return $this->mysqli->affected_rows;
  }

  /**
   * Execute sql statment and return an array of a row.
   */
  public function resultRow($sql) {
    $row = NULL;
    if ($r = $this->mysqli->query($sql)) {
      $row = $r->fetch_array();
    }
    return $row;
  }

  /**
   * Execute sql statment and return an array of rows.
   */
  public function resultArray($sql) {
    $result = array();
    if ($r = $this->mysqli->query($sql)) {
      while ($row = $r->fetch_array()) {
        $result[] = $row;
      }
    }
    return $result;
  }

  public function lockTableRead($table_name) {
    $sql = "lock table $table_name read";
    $this->mysqli->query($sql);
  }

  public function lockTableWrite($table_name) {
    $sql = "lock table $table_name write";
    $this->mysqli->query($sql);
  }

  public function lockTables($tables_and_types) {
    $s = implode(',', $tables_and_types);
    $sql = "lock tables $s";
    $this->mysqli->query($sql);
  }

  public function unlockTables() {
    $sql = "unlock tables";
    $this->mysqli->query($sql);
  }

  public function getError() {
    return $this->mysqli->error;
  }

}
