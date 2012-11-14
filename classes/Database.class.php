<?php

  class Database {
    const CHARSET = 'UTF8';
    private static $cfg = NULL;
    private static $dbh = NULL;
    private static $sth = NULL;

    public function __construct($config) {
      if (is_null(self::$dbh)) {
        try {
          self::$cfg = (object) $config;
          self::db_connect(self::$cfg->server, self::$cfg->username, self::$cfg->password, self::$cfg->database);

        } catch (PDOException $e) {
          // We need to catch the PDO exception, as its error message will contain the MySQL login information.
          throw new Exception('Could not establish a database connection.');
        }
      }
    }

    public function db_connect($host, $user, $pass, $db) {
      self::$dbh = new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
      self::$sth = NULL;
      
      self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      self::db_exec_single('SET CHARACTER SET "' . self::CHARSET . '"');
    }

    public function db_disconnect() {
      self::$dbh = NULL;
      self::$sth = NULL;
    }

    public function db_exec_single($sql) {
      return self::$dbh->exec($sql);
    }

    public function db_query($sql, $params = FALSE) {
      if ($params === FALSE) {
        self::$sth = self::$dbh->query($sql);

      } else if (is_array($params) || is_object($params)) {
        $params = (array) $params;  //make sure objects become assoc. arrays
        self::$sth = self::$dbh->prepare($sql);
        self::$sth->execute($params);
      
      } else {
        return FALSE;
      }

      self::$sth->setFetchMode(PDO::FETCH_OBJ);
      return self::$sth->rowCount();
    }

    public function db_fetch() {
      return self::$sth->fetch();
    }
  }

?>