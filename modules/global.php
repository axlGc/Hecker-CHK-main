<?php
echo "¡Hola, esto es bot.php!";
$botToken = "8076521376:AAHE9Qxjwt6BpE-wFq8sazjD0fgvxyrApUQ"; // Enter ur bot token
$website = "https://api.telegram.org/bot".$botToken;
error_reporting(0);
$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);
$print = print_r($update);
$chatId = $update["message"]["chat"]["id"];
$gId = $update["message"]["from"]["id"];
$userId = $update["message"]["from"]["id"];
$firstname = $update["message"]["from"]["first_name"];
$lastname = $update["message"]["from"]["last_name"];
$username = $update["message"]["from"]["username"];
$message = $update["message"]["text"];
$message_id = $update["message"]["message_id"];
$t = 'True';
$k = 'Premium';
$f = 'False';
$z = 'Free';
$sat = 'PUBLIC RELEASE';
$botby = 'HECKER';
$pre = file_get_contents('pre.txt');
$pre = explode("\n", $pre);
if (in_array($userId, $pre)) {
  $usernam = ''.$username.'%0A<bPremium User</b>';
  $stat = $t;
  $statz = $k;
  }
else {
$usernam = ''.$username.'%0A Free User';
  $stat = $f;
  $statz = $z;
}

////////////////////////////////////////////////

// function multiexplode($delimiters, $string) {
//     $ready = str_replace($delimiters, $delimiters[0], $string);
//     return explode($delimiters[0], $ready);
// }

// function GetStr($string, $start, $end) {
//     $string = ' ' . $string;
//     $ini = strpos($string, $start);
//     if ($ini == 0) return '';
//     $ini += strlen($start);
//     $len = strpos($string, $end, $ini) - $ini;
//     return substr($string, $ini, $len);
// }

// function sendaction($chatId, $action) {
//     global $botToken;
//     $url = "https://api.telegram.org/bot$botToken/sendChatAction?chat_id=$chatId&action=$action";
//     file_get_contents($url);
// }

// function editMessage($chatId, $text, $messageId) {
//     global $botToken;
//     $url = "https://api.telegram.org/bot$botToken/editMessageText?chat_id=$chatId&message_id=$messageId&text=" . urlencode($text) . "&parse_mode=HTML";
//     file_get_contents($url);
// }

// function sendMessage($chatId, $text, $messageId = null) {
//     global $botToken;
//     $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($text) . "&parse_mode=HTML";
//     if ($messageId) {
//         $url .= "&reply_to_message_id=$messageId";
//     }
//     file_get_contents($url);
// }


?>