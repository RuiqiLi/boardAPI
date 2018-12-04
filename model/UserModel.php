<?php

require_once __DIR__ . '/DB.php';


class UserModel {

  private $db;

  function __construct() {
    $this->db = DB::instance();
  }

  public function insertOrUpdateUserInfo($open_id, $nick_name, $gender, $city, $province, $country, $avatar_url, $union_id) {
    $sql = "UPDATE t_user set
      nick_name = '$nick_name',
      gender = $gender,
      city = '$city',
      province = '$province',
      country = '$country',
      avatar_url = '$avatar_url',
      union_id = '$union_id'
    where open_id = '$open_id'";
    $rows_affected = $this->db->queryReturnRows($sql);
    if ($rows_affected != 1) {
      $sql = "INSERT into t_user(open_id, nick_name, gender, city, province, country, avatar_url, union_id)
              values('$open_id', '$nick_name', $gender, '$city', '$province', '$country', '$avatar_url', '$union_id')";
      $this->db->query($sql);
    }
  }

  public function getUidByOpenId($open_id) {
    $uid = -1;
    $sql = "SELECT id from t_user where open_id = '$open_id'";
    $row = $this->db->resultRow($sql);
    if (isset($row['id'])) {
      $uid = $row['id'];
    }
    return $uid;
  }

  public function getBasicUserInfoByUid($user_id) {
    $sql = "SELECT nick_name, gender, avatar_url, real_name from t_user where id = $user_id";
    $row = $this->db->resultRow($sql);
    if (isset($row['nick_name'])) {
      return $row;
    }
    return NULL;
  }

  public function getRealNameByOpenId($open_id) {
    $sql = "SELECT real_name from t_user where open_id = '$open_id'";
    $row = $this->db->resultRow($sql);
    if (isset($row['real_name'])) {
      return $row['real_name'];
    }
    return "";
  }

  public function setRealName($user_id, $real_name) {
    $sql = "UPDATE t_user set real_name = '$real_name' where id = $user_id";
    $this->db->query($sql);
  }

  public function checkAdminByOpenId($open_id) {
    $sql = "SELECT is_admin from t_user where open_id = '$open_id'";
    $row = $this->db->resultRow($sql);
    if (isset($row['is_admin'])) {
      return $row['is_admin'] == 1 ? true : false;
    }
    return false;
  }

  public function addLoginTimesByOpenId($open_id) {
    $sql = "UPDATE t_user set login_times = login_times + 1 where open_id = '$open_id'";
    $this->db->query($sql);
  }
}
