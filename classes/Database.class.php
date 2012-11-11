<?php

  class Database {
    const CHARSET = 'UTF8';
    private static $dbh     = NULL;
    private static $sth     = NULL;
    private static $q_count = 0;
    private static $q_timer = 0;

    public function __construct() {
      if (is_null(self::$dbh)) {
        try {
          $this->db_connect(Config::MYSQL_HOSTNAME, Config::MYSQL_USERNAME, Config::MYSQL_PASSWORD, Config::MYSQL_DATABASE);

        } catch (PDOException $e) {
          // We need to catch the PDO exception, as its error message will contain the MySQL login information.
          throw new Exception('Could not establish a database connection.');
        }
      }
    }

    public function db_connect($host, $user, $pass, $db) {
      self::$sth     = NULL;
      self::$q_count = 0;
      self::$q_timer = 0;

      self::$dbh = new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
      self::$dbh->exec('SET CHARACTER SET "' . self::CHARSET . '"');
    }

    public function db_disconnect() {
      self::$dbh = NULL;
      self::$sth = NULL;
    }

    public function db_exec_single($sql) {
      $start_time = microtime(TRUE);

      $affected_rows = self::$dbh->exec($sql);

      self::$q_timer += (microtime(TRUE) - $start_time);
      self::$q_count++;

      return $affected_rows;
    }

    public function db_query($sql, $params = FALSE) {
      $start_time = microtime(TRUE);

      if ($params === FALSE) {
        self::$sth = self::$dbh->query($sql);

      } else if (is_array($params)) {
        self::$sth = self::$dbh->prepare($sql);
        self::$sth->execute($params);
      }

      self::$sth->setFetchMode(PDO::FETCH_OBJ);

      self::$q_timer += (microtime(TRUE) - $start_time);
      self::$q_count++;

      return self::$sth->rowCount();
    }

    public function db_fetch() {
      return self::$sth->fetch();
    }

    public function db_fetch_all() {
      return self::$sth->fetchAll();
    }

    public function db_fetch_eav_to_object($key_column, $value_column) {
      $obj = new StdClass();

      while ($row = self::$sth->fetch()) {
        $key = $row->$key_column;
        $obj->$key = $row->$value_column;
      }

      return $obj;
    }

    public function db_last_insert_id() {
      return self::$dbh->lastInsertId();
    }
    
    public function db_last_error() {
      return self::$sth->errorInfo();
    }

    public function db_query_count() {
      return self::$q_count;
    }

    public function db_query_msec() {
      return round(self::$q_timer * 1000, 2);  //convert usec to msec
    }
  }

?>