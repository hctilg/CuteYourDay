<?php

/***************************** Database *****************************/

class Database {
  public $connection = null;
  public function __construct() {
    // sudo apt-get install php-sqlite3
    $this->connection = new PDO("sqlite:database.db");
    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public function init() {
    $this->connection->query("CREATE TABLE IF NOT EXISTS `users` (`id` TEXT NOT NULL , `data` LONGTEXT NOT NULL , `turn` BOOLEAN NOT NULL, `last_date` BIGINT NOT NULL );");
    $this->connection->query("CREATE TABLE IF NOT EXISTS `media` (`type` TEXT NOT NULL , `msg_id` BIGINT NOT NULL );");
  }

  public function user_exists($chat_id) {
    $stmt = $this->connection->prepare("SELECT * FROM `users` WHERE `id` LIKE :id");
    $stmt->bindParam(':id', $chat_id);
    $stmt->execute();
    return !!$stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function add_user($chat_id) {
    if ($this->user_exists($chat_id)) return false;

    $data = json_encode(explode('|', "cat|dog|bird|boy|girl|anime|etc"));
    $date = time() - (24 * 60 * 60);
    
    $stmt = $this->connection->prepare("INSERT INTO `users` (`id`, `data`, `turn`, `last_date`) VALUES (:id, :data, 'true', :date)");
    $stmt->bindParam(':id', $chat_id, PDO::PARAM_STR);
    $stmt->bindParam(':data', $data, PDO::PARAM_STR);
    $stmt->bindParam(':date', $date, PDO::PARAM_INT);
    $stmt->execute();
    return true;
  }

  public function remove_user($chat_id) {
    if (!$this->user_exists($chat_id)) return false;
    
    $stmt = $this->connection->prepare("DELETE FROM `users` WHERE `id` = :id");
    $stmt->bindParam(':id', $chat_id, PDO::PARAM_INT);
    $stmt->execute();
    return true;
  }

  public function get_user($chat_id) {
    if (!$this->user_exists($chat_id)) return false;

    $stmt = $this->connection->prepare("SELECT * FROM `users` WHERE `id` = :id");
    $stmt->bindParam(':id', $chat_id);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['data'] = json_decode($user['data']) ?? [];
    $user['turn'] = in_array("$user[turn]", ['true', '1']);
    return $user;
  }
  
  public function add_media($type, $msg_id) {
    $stmt = $this->connection->prepare("INSERT INTO `media` (`type`, `msg_id`) VALUES (:type, :id)");
    $stmt->bindParam(':type', $type, PDO::PARAM_STR);
    $stmt->bindParam(':id', $msg_id, PDO::PARAM_INT);
    $stmt->execute();
    return true;
  }
  
  public function remove_media($msg_id) {
    $stmt = $this->connection->prepare("DELETE FROM `media` WHERE `msg_id` = :id");
    $stmt->bindParam(':id', $msg_id, PDO::PARAM_INT);
    $stmt->execute();
    return true;
  }
  
  public function info() {
    $query = $this->connection->query("SELECT count(*) FROM `users`;");
    $all_usrs = (int)$query->fetchColumn();
    
    $query = $this->connection->query("SELECT count(*) FROM `users` WHERE `turn` = 'true';");
    $active_usrs = (int)$query->fetchColumn();
    
    $query = $this->connection->query("SELECT count(*) FROM `media`;");
    $all_media = (int)$query->fetchColumn();
    
    return [
      'users'=> [
        'all'=> $all_usrs,
        'active'=> $active_usrs,
        'archive'=> $all_usrs - $active_usrs,
      ],
      'media'=> [
        'all'=> $all_media,
        'cat'=> $this->pic_count('cat'),
        'dog'=> $this->pic_count('dog'),
        'bird'=> $this->pic_count('bird'),
        'boy'=> $this->pic_count('boy'),
        'girl'=> $this->pic_count('girl'),
        'anime'=> $this->pic_count('anime'),
        'etc'=> $this->pic_count('etc')
      ]
    ];
  }
  
  public function pic_count($type) {
    $query = $this->connection->query("SELECT count(*) FROM `media` WHERE `type` = '$type';");
    return (int)$query->fetchColumn();
  }
  
  public function random($type) {
    $stmt = $this->connection->prepare("SELECT * FROM `media` WHERE `type` = '$type' ORDER BY RANDOM() LIMIT 1;");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) return $row['msg_id'];
  }

  public function get_users() {
    $stmt = $this->connection->prepare("SELECT * FROM `users` WHERE `turn` = 'true';");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function change_user($chat_id, $key, $value) {
    return $this->connection->query("UPDATE `users` SET `$key` = '$value' WHERE `id` = '$chat_id';");
  }
}
