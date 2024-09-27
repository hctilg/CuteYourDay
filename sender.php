<?php

/********************** Importing Requirements **********************/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$telebot_path = __DIR__ . '/telebot@2.1.php';

// checking the exists "Telebot Library".
if (!file_exists($telebot_path)) {
  copy('https://raw.githubusercontent.com/hctilg/telebot/v2.1/index.php', $telebot_path);
}

// import telebot library
require_once $telebot_path;

/*********************** Main Section of Code ***********************/

$bot = new Telebot(TOKEN, false);

try {
  // Create a Sqlite database.
  $db = new Database();
  $db->init();
} catch (PDOException $e) {
  $msg = $bot->sendMessage(['chat_id'=> CREATOR, 'text'=> "⚠️ Connection to database failed!"]);
  $bot->sendMessage(['chat_id'=> CREATOR, 'text'=> 'Error: ' . $e->getMessage(), 'reply_to_message_id'=> $msg['result']['message_id']]);
  error_log("Error: " . $e->getMessage());
  exit;
}

$users = $db->get_users();

foreach($users as $user) {
  if ((time() - $user['last_date']) > (int)(6.1 * 60 * 60)) {
    $types = json_decode($user['data']);
    $rand_type = $types[array_rand($types)];
    $pic = $db->random($rand_type);
    if (!!empty($pic) || $user['id'] == CHACNNEL_MEDIA) continue;
    $bot->copyMessage(['chat_id'=> $user['id'], 'from_chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $pic, 'caption'=> "@CuteYourDayBot", 'protect_content'=> 'false']);
    $db->change_user($user['id'], 'last_date', time());
  }
}
