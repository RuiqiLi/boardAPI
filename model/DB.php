<?php

require_once __DIR__ . "/MysqlBase.php";


class DB extends MysqlBase {

  private static $dbcon = false;

  protected function __construct() {
    $config = parse_ini_file(__DIR__ . '/../config/config.ini', true);
    $hostname = $config['mysql']['hostname'];
    $database = $config['mysql']['database'];
    $username = $config['mysql']['username'];
    $password = $config['mysql']['password'];
    parent::__construct($hostname, $database, $username, $password);
  }

  public static function instance() {
    if (self::$dbcon == false) {
      self::$dbcon = new self;
    }
    return self::$dbcon;
  }

}
