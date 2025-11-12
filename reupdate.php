<?php

/**
 ** Media Channel & Database updater, from the old source to the new source. **
 ** Run this in the Terminal
 */

/********************** Importing Requirements **********************/

require_once __DIR__ . '/config.php';

$telebot_path = __DIR__ . '/telebot@2.1.php';

// checking the exists "Telebot Library".
if (!file_exists($telebot_path)) {
  copy('https://raw.githubusercontent.com/hctilg/telebot/v2.1/index.php', $telebot_path);
}

// import telebot library
require_once $telebot_path;

/*********************** Main Section of Code ***********************/

// Update Database :

$connection = new PDO("sqlite:database.db");
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Update `media` table
foreach ([
  'animals' => ['cat', 'dog', 'bird'],
  'kids' => ['girl', 'boy']
] as $new_type => $old_types) {
  $old_types_list = implode("', '", $old_types);

  $query = "UPDATE `media` SET `type` = :new_type WHERE `type` IN ('$old_types_list')";
  $stmt = $connection->prepare($query);
  $stmt->execute(['new_type' => $new_type]);
}

// Update `users` table
$data = json_encode(CONTENT_TYPES);
$updateStmt = $connection->prepare("UPDATE `users` SET `data` = :data");
$updateStmt->execute(['data' => $data]);

// Media Channel :

$bot = new Telebot(TOKEN, false);

$i = 0;
$stmt = $connection->query("SELECT `type`, `msg_id` FROM `media`");
while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  $media_id = $post['msg_id'];
  
  if (($i % 50) == 0) sleep(1);

  request:
  $res = $bot->editMessageCaption([
    'chat_id'=> CHACNNEL_MEDIA,
    'message_id'=> $media_id,
    'caption'=> "#" . $post['type'],
    'reply_markup'=> Telebot::inline_keyboard("[Change 🎨|change_$media_id][Delete ✖️|remove_$media_id]")
  ]);
  if (!$res['ok'] && $res['error_code'] == 429) {
    sleep($res['parameters']['retry_after'] + 1);
    goto request;
  }

  printf("$i\n");
}