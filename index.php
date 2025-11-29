<?php

/********************** Importing Requirements **********************/

$telebot_path = 'telebot@2.1.php';

// checking the exists "Telebot Library".
if (!file_exists($telebot_path)) {
  copy('https://raw.githubusercontent.com/hctilg/telebot/v2.1/index.php', $telebot_path);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

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

$bot->on('text', function($data) use ($bot, $db) {
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $msg_id = $data['message_id'] ?? -1;
  $text = $data['text'] ?? '';
  
  if ($chat_type != 'private' || $chat_id == 'unknown') return;
  
  $is_new = $db->add_user($chat_id);
  $user = $db->get_user($chat_id);
  
  if ($chat_id == CREATOR) {
    if (startsWith('/backup', $text)) {
      date_default_timezone_set('Asia/Tehran');
      $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
        ['type' => 'emoji', 'emoji'=> '👀']
      ]), 'message_id'=> $msg_id]);
      $bot->sendDocument([
        'chat_id'=> $chat_id,
        'document'=> 'database.db',
        'reply_markup'=> Telebot::inline_keyboard("[Show Info|get_info]"),
        'reply_to_message_id'=> $msg_id
      ]);
      return;
    }
  }
  
  if ($user['turn']) {
    $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
      ['type' => 'emoji', 'emoji'=> '💘']
    ]), 'message_id'=> $msg_id]);
    $bot->sendMessage([
      'chat_id'=> $chat_id,
      'text'=> "Hey, welcome to the _Cute Your Day_ bot!\n\nThanks for starting the bot. From now on, I'll be sending you some cute pictures every day to make your day cute.",
      'reply_markup'=> Telebot::inline_keyboard("[Customize media... ✨ |customize]\n[Turn: On|turn_off]"),
      'reply_to_message_id'=> $msg_id,
      'parse_mode'=> "Markdown"
    ]);
  } else {
    $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
      ['type' => 'emoji', 'emoji'=> '😍']
    ]), 'message_id'=> $msg_id]);
    $bot->sendMessage([
      'chat_id'=> $chat_id,
      'text'=> "Umm.. please first, turn me on, cute!",
      'reply_markup'=> Telebot::inline_keyboard("[Turn: Off|turn_on]"),
      'reply_to_message_id'=> $msg_id,
      'parse_mode'=> "Markdown"
    ]);
  }
  
  if ($is_new) {
    $types = ['animals', 'sweetie', 'etc'];
    $rand_type = $types[array_rand($types)];
    $pic = $db->random($rand_type);
    if (!empty($pic)) $bot->copyMessage(['chat_id'=> $chat_id, 'from_chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $pic, 'caption'=> "first photo for you :)", 'protect_content'=> 'false']);
  }
  
});

$bot->on('photo', function($data) use ($bot) {
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $photo = end($data['photo'])['file_id'] ?? 'false';
  $msg_id = $data['message_id'] ?? -1;
  
  if ($chat_type != 'private' || $chat_id != CREATOR) return;
  
  $mc = $bot->sendPhoto(['chat_id'=> CHACNNEL_MEDIA, 'photo'=> $photo]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Sweetie 🎀|set_sweetie_$mc_id] [Foods 🍩|set_foods_$mc_id]
[Animals 🐈|set_animals_$mc_id] [Kids 👼🏻|set_kids_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Other...|set_etc_$mc_id]
[Reject ✖️|reject_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
  
  $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
    ['type' => 'emoji', 'emoji'=> '👍']
  ]), 'message_id'=> $msg_id]);
  
  if (isset($data["media_group_id"])) sleep(1);
  
});

$bot->on('video', function($data) use ($bot) {
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $video = $data["video"]["file_id"] ?? 'false';
  $msg_id = $data['message_id'] ?? -1;
  
  if ($chat_type != 'private' || $chat_id != CREATOR) return;
  
  $mc = $bot->sendVideo(['chat_id'=> CHACNNEL_MEDIA, 'video'=> $video]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Sweetie 🎀|set_sweetie_$mc_id] [Foods 🍩|set_foods_$mc_id]
[Animals 🐈|set_animals_$mc_id] [Kids 👼🏻|set_kids_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Other...|set_etc_$mc_id]
[Reject ✖️|reject_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
  
  $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
    ['type' => 'emoji', 'emoji'=> '👍']
  ]), 'message_id'=> $msg_id]);
  
  if (isset($data["media_group_id"])) sleep(1);
});

$bot->on('animation', function($data) use ($bot) { 
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $gif = $data["animation"]["file_id"] ?? 'false';
  $msg_id = $data['message_id'] ?? -1;
  
  if ($chat_type != 'private' || $chat_id != CREATOR) return;
  
  $mc = $bot->sendAnimation(['chat_id'=> CHACNNEL_MEDIA, 'animation'=> $gif]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Sweetie 🎀|set_sweetie_$mc_id] [Foods 🍩|set_foods_$mc_id]
[Animals 🐈|set_animals_$mc_id] [Kids 👼🏻|set_kids_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Other...|set_etc_$mc_id]
[Reject ✖️|reject_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
  
  $bot->setMessageReaction(['chat_id'=> $chat_id, 'reaction'=> json_encode([
    ['type' => 'emoji', 'emoji'=> '👍']
  ]), 'message_id'=> $msg_id]);
  
  if (isset($data["media_group_id"])) sleep(1);
});

$bot->on('callback_query', function($callback_query) use ($bot, $db) {
  $query_id = $callback_query['id'];
  $query_data = $callback_query['data'];
  $chat_id = $callback_query['message']['chat']['id'];
  $msg_id = $callback_query['message']['message_id'];
  $keyboard = $callback_query['message']['reply_markup'];
  $li = array_flip(CONTENT_TYPES);
  
  if (startsWith('reject_', $query_data)) {
    $bot->deleteMessage(['chat_id'=> $chat_id, 'message_id'=> $msg_id]);
    $bot->deleteMessage(['chat_id'=> $chat_id, 'message_id'=> substr($query_data, strlen('reject_'))]);
    $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Rejected 🎈", 'show_alert'=> false]);
    return;
  }
  
  if (startsWith('set_', $query_data) || startsWith('reset_', $query_data)) {
    $k = explode('_', $query_data);
    $type = $k[1];
    $media_id = $k[2];
    $db->add_media($type, $media_id);

    if ($k[0] == 'set')
      $bot->deleteMessage(['chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $msg_id]);

    $bot->editMessageCaption([
      'chat_id'=> CHACNNEL_MEDIA,
      'message_id'=> $media_id,
      'caption'=> "#$type",
      'reply_markup'=> Telebot::inline_keyboard("[Change 🎨|change_$media_id][Delete ✖️|remove_$media_id]")
    ]);
    $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Resolved 🧸", 'show_alert'=> false]);
    return;
  }
  
  if (startsWith('change_', $query_data)) {
    $media_id = substr($query_data, strlen('change_'));
    $db->remove_media($media_id);
    $bot->editMessageCaption([
      'chat_id'=> CHACNNEL_MEDIA,
      'message_id'=> $media_id,
      'text'=> "Choose Type or Delete:",
      'reply_markup'=> Telebot::inline_keyboard("
[Animals 🐈|reset_animals_$media_id]
[Sweetie 🎀|reset_sweetie_$media_id]
[Anime 🎗|reset_anime_$media_id]
[Foods 🍩|reset_foods_$media_id]
[Kids 👼🏻|reset_kids_$media_id]
[Other...|reset_etc_$media_id]
[Delete ✖️|remove_$media_id]
")
    ]);
    return;
  }
  
  if (startsWith('remove_', $query_data)) {
    $media_id = substr($query_data, strlen('remove_'));
    $db->remove_media($media_id);
    $bot->deleteMessage(['chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $media_id]);
    $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Deleted 🗑", 'show_alert'=> true]);
    return;
  }

  $db->add_user($chat_id);
  $user = $db->get_user($chat_id);
  
  $info = $db->info();
  $users = $info['users'];
  $media = $info['media'];
  
   if ($query_data == 'get_info') {
      $bot->editMessageCaption([
        'chat_id'=> $chat_id,
        'caption'=> "
All Users: $users[all]
+ Active: $users[active]
+ Archive: $users[archive]

All media: $media[all]
+ Animals: $media[animals]
+ Sweetie: $media[sweetie]
+ Anime: $media[anime]
+ Foods: $media[foods]
+ Kids: $media[kids]
+ Other: $media[etc]
",
        'reply_markup'=> Telebot::inline_keyboard("[Update  🔄|get_info]"),
        'message_id'=> $msg_id,
        'parse_mode'=> "Markdown"
      ]);
    }
  
  if ($user['turn']) {
    if ($query_data == 'turn_off') {
      $db->change_user($chat_id, 'turn', 'false');
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "Send me a message if you need me.\n\n- bye bye :)",
        'reply_markup'=> Telebot::inline_keyboard(''),
        'message_id'=> $msg_id
      ]);
    } elseif ($query_data == 'customize') {
      $ch_animals = in_array('animals', $user['data']) ? '✅' : '❌';
      $ch_sweetie = in_array('sweetie', $user['data']) ? '✅' : '❌';
      $ch_anime = in_array('anime', $user['data']) ? '✅' : '❌';
      $ch_foods = in_array('foods', $user['data']) ? '✅' : '❌';
      $ch_kids = in_array('kids', $user['data']) ? '✅' : '❌';
      $ch_etc = in_array('etc', $user['data']) ? '✅' : '❌';
      
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "please choose items 👇🏻",
        'reply_markup'=> Telebot::inline_keyboard("
[Animals 🐈|toggle_animals] [$ch_animals|toggle_animals]
[Sweetie 💖|toggle_sweetie] [$ch_sweetie|toggle_sweetie]
[Anime 🎗|toggle_anime] [$ch_anime|toggle_anime]
[Foods 🍩|toggle_foods] [$ch_foods|toggle_foods]
[Kids 👼🏻|toggle_kids] [$ch_animals|toggle_kids]
[Other...|toggle_etc] [$ch_etc|toggle_etc]
[Cancel ✖️|cancel] [Confirm ✨|confirm]"
),
        'message_id'=> $msg_id
      ]);
    } elseif (startsWith('toggle_', $query_data)) {
      $k = substr($query_data, strlen('toggle_'));
      $keyboard['inline_keyboard'][$li[$k]][1]['text'] = ($keyboard['inline_keyboard'][$li[$k]][1]['text'] == '❌') ? '✅' : '❌';
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "please choose items 👇🏻",
        'reply_markup'=> json_encode($keyboard),
        'message_id'=> $msg_id
      ]);
      $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Changed 💫", 'show_alert'=> false]);
    } elseif ($query_data == 'confirm') {
      unset($keyboard['inline_keyboard'][count(CONTENT_TYPES)]);
      $lb = [];
      foreach ($keyboard['inline_keyboard'] as $key => $val) $lb[CONTENT_TYPES[$key]] = $val[1]['text'] == '✅';
      if (!in_array(true, $lb, true)) {
        return $bot->answerCallbackQuery([
          'callback_query_id'=> $query_id,
          'text'=> 'Choose at least a item!',
          'show_alert'=> true
        ]);
        $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Applied 🫶🏻", 'show_alert'=> false]);
      } else {
        $lt = array_keys($lb, true);
        $db->change_user($chat_id, 'data', json_encode($lt));
        $bot->editMessageText([
          'chat_id'=> $chat_id,
          'text'=> "Hey, welcome to the _Cute Your Day_ bot!\n\nThanks for starting the bot. From now on, I'll be sending you some cute pictures every day to make your day cute.",
          'reply_markup'=> Telebot::inline_keyboard("[Customize media... ✨ |customize]\n[Turn: On|turn_off]"),
          'message_id'=> $msg_id,
          'parse_mode'=> "Markdown"
        ]);
      }
    } elseif ($query_data == 'cancel') {
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "Hey, welcome to the _Cute Your Day_ bot!\n\nThanks for starting the bot. From now on, I'll be sending you some cute pictures every day to make your day cute.",
        'reply_markup'=> Telebot::inline_keyboard("[Customize media... ✨ |customize]\n[Turn: On|turn_off]"),
        'message_id'=> $msg_id,
        'parse_mode'=> "Markdown"
      ]);
    }
  } else {
    if ($query_data == 'turn_on') {
      $db->change_user($chat_id, 'turn', 'true');
      $bot->answerCallbackQuery([
        'callback_query_id'=> $query_id,
        'text'=> 'Thanks 🥳',
        'show_alert'=> false
      ]);
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "Hey, welcome to the _Cute Your Day_ bot!\n\nThanks for starting the bot. From now on, I'll be sending you some cute pictures every day to make your day cute.",
        'reply_markup'=> Telebot::inline_keyboard("[Customize media... ✨ |customize]\n[Turn: On|turn_off]"),
        'message_id'=> $msg_id,
        'parse_mode'=> "Markdown"
      ]);
    }
  }
});

$bot->run();
