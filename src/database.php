<?php

class Database {
  
  public function __construct(
    private string $host,
    private string $dbName,
    private string $username,
    private string $password
  ) {
      // Database connection setup
  }
  
  public function getConnection(): PDO
  {
      $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
      return new PDO($dsn, $this->username, $this->password, [
          PDO::ATTR_EMULATE_PREPARES => false,
          PDO::ATTR_STRINGIFY_FETCHES => false,
      ]);
  }
}