<?php

/********************** Importing Requirements **********************/

$telebot_path = __DIR__ . '/telebot@2.1.php';

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
    $bot->sendMessage([
      'chat_id'=> $chat_id,
      'text'=> "Hey, welcome to the _Cute Your Day_ bot!\n\nThanks for starting the bot. From now on, I'll be sending you some cute pictures every day to make your day cute.",
      'reply_markup'=> Telebot::inline_keyboard("[Customize media... ✨ |customize]\n[Turn: On|turn_off]"),
      'reply_to_message_id'=> $msg_id,
      'parse_mode'=> "Markdown"
    ]);
  } else {
    $bot->sendMessage([
      'chat_id'=> $chat_id,
      'text'=> "Umm.. please first, turn me on, cute!",
      'reply_markup'=> Telebot::inline_keyboard("[Turn: Off|turn_on]"),
      'reply_to_message_id'=> $msg_id,
      'parse_mode'=> "Markdown"
    ]);
  }
  
  if ($is_new) {
    $types = ['cat', 'bird', 'etc'];
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
  
  $bot->sendMessage(['chat_id'=> CREATOR, 'text'=> "✅", 'reply_to_message_id'=> $msg_id]);
  
  $mc = $bot->sendPhoto(['chat_id'=> CHACNNEL_MEDIA, 'photo'=> $photo]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Dog 🐶|set_dog_$mc_id] [Cat 🐈|set_cat_$mc_id]
[Boy 🧑🏻‍🦱|set_boy_$mc_id] [Girl 👱🏻‍♀|set_girl_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Bird 🐣|set_bird_$mc_id]
[Reject ✖️|reject_$mc_id] [Others...|set_etc_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
});

$bot->on('video', function($data) use ($bot) {
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $video = $data["video"]["file_id"] ?? 'false';
  $msg_id = $data['message_id'] ?? -1;
  
  if ($chat_type != 'private' || $chat_id != CREATOR) return;
  
  $bot->sendMessage(['chat_id'=> CREATOR, 'text'=> "✅", 'reply_to_message_id'=> $msg_id]);
  
  $mc = $bot->sendVideo(['chat_id'=> CHACNNEL_MEDIA, 'video'=> $video]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Dog 🐶|set_dog_$mc_id] [Cat 🐈|set_cat_$mc_id]
[Boy 🧑🏻‍🦱|set_boy_$mc_id] [Girl 👱🏻‍♀|set_girl_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Bird 🐣|set_bird_$mc_id]
[Reject ✖️|reject_$mc_id] [Others...|set_etc_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
});

$bot->on('animation', function($data) use ($bot) { 
  $chat_type = $data['chat']['type'] ?? $data['chat_type'] ?? 'unknown';
  $chat_id = $data['chat']['id'] ?? $data['from']['id'] ?? 'unknown';
  $gif = $data["animation"]["file_id"] ?? 'false';
  $msg_id = $data['message_id'] ?? -1;
  
  if ($chat_type != 'private' || $chat_id != CREATOR) return;
  
  $bot->sendMessage(['chat_id'=> CREATOR, 'text'=> "✅", 'reply_to_message_id'=> $msg_id]);
  
  $mc = $bot->sendAnimation(['chat_id'=> CHACNNEL_MEDIA, 'animation'=> $gif]);
  $mc_id = $mc['result']['message_id'];
  
  $bot->sendMessage([
    'chat_id'=> CHACNNEL_MEDIA,
    'text'=> "Choose Type or Reject:",
    'reply_markup'=> Telebot::inline_keyboard("
[Dog 🐶|set_dog_$mc_id] [Cat 🐈|set_cat_$mc_id]
[Boy 🧑🏻‍🦱|set_boy_$mc_id] [Girl 👱🏻‍♀|set_girl_$mc_id]
[Anime 🎗|set_anime_$mc_id] [Bird 🐣|set_bird_$mc_id]
[Reject ✖️|reject_$mc_id] [Others...|set_etc_$mc_id]
"),
    'reply_to_message_id'=> $mc_id
  ]);
});

$bot->on('callback_query', function($callback_query) use ($bot, $db) {
  $query_id = $callback_query['id'];
  $query_data = $callback_query['data'];
  $chat_id = $callback_query['message']['chat']['id'];
  $msg_id = $callback_query['message']['message_id'];
  $keyboard = $callback_query['message']['reply_markup'];
  $li = ['cat'=> 0, 'dog'=> 1, 'bird'=> 2, 'boy'=> 3, 'girl'=> 4, 'anime'=> 5, 'etc'=> 6];
  
  if (startsWith('reject_', $query_data)) {
    $bot->deleteMessage(['chat_id'=> $chat_id, 'message_id'=> $msg_id]);
    $bot->deleteMessage(['chat_id'=> $chat_id, 'message_id'=> substr($query_data, strlen('reject_'))]);
    $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Rejected 🎈", 'show_alert'=> false]);
    return;
  }
  
  if (startsWith('set_', $query_data)) {
    $k = explode('_', substr($query_data, strlen('set_')));
    $type = $k[0];
    $pic_id = $k[1];
    $db->add_media($type, $pic_id);
    $bot->deleteMessage(['chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $msg_id]);
    $bot->editMessageCaption(['chat_id'=> CHACNNEL_MEDIA, 'message_id'=> $pic_id,'caption'=> "#$type", 'reply_markup'=> Telebot::inline_keyboard("[Delete ✖️|remove_$pic_id]")]);
    $bot->answerCallbackQuery(['callback_query_id'=> $query_id, 'text'=> "Resolved 🧸", 'show_alert'=> false]);
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
+ Cat: $media[cat]
+ Dog: $media[dog]
+ Bird: $media[bird]
+ Boy: $media[boy]
+ Girl: $media[girl]
+ Anime: $media[anime]
+ Etc: $media[etc]
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
      $ch_cat = in_array('cat', $user['data']) ? '✅' : '❌';
      $ch_dog = in_array('dog', $user['data']) ? '✅' : '❌';
      $ch_bird = in_array('bird', $user['data']) ? '✅' : '❌';
      $ch_boy = in_array('boy', $user['data']) ? '✅' : '❌';
      $ch_girl = in_array('girl', $user['data']) ? '✅' : '❌';
      $ch_anime = in_array('anime', $user['data']) ? '✅' : '❌';
      $ch_etc = in_array('etc', $user['data']) ? '✅' : '❌';
      
      $bot->editMessageText([
        'chat_id'=> $chat_id,
        'text'=> "please choose items 👇🏻",
        'reply_markup'=> Telebot::inline_keyboard("
[Cat 🐈|toggle_cat] [$ch_cat|toggle_cat]
[Dog 🐶|toggle_dog] [$ch_dog|toggle_dog]
[Bird 🐣|toggle_bird] [$ch_bird|toggle_bird]
[Boy 🧑🏻‍🦱|toggle_boy] [$ch_boy|toggle_boy]
[Girl 👱🏻‍♀|toggle_girl] [$ch_girl|toggle_girl]
[Anime 🎗|toggle_anime] [$ch_anime|toggle_anime]
[Others...|toggle_etc] [$ch_etc|toggle_etc]
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
    } elseif ($query_data == 'confirm') {
      unset($keyboard['inline_keyboard'][7]);
      $il = array_keys($li);
      $lb = [];
      foreach ($keyboard['inline_keyboard'] as $key => $val) $lb[$il[$key]] = $val[1]['text'] == '✅';
      if (!in_array(true, $lb, true)) {
        return $bot->answerCallbackQuery([
          'callback_query_id'=> $query_id,
          'text'=> 'Choose at least a item!',
          'show_alert'=> true
        ]);
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