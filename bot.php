<?php
include "modules/include.php";

// Leer actualización del webhook
$update = json_decode(file_get_contents("php://input"), true);
file_put_contents("log.txt", json_encode($update, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

// Función para guardar logs de depuración
function debugLog($mensaje) {
    $fecha = date("Y-m-d H:i:s");
    file_put_contents("debug.log", "[$fecha] $mensaje" . PHP_EOL, FILE_APPEND);
}

// Obtener datos del mensaje
$message = $update["message"]["text"] ?? "";
$chatId = $update["message"]["chat"]["id"] ?? "";
$message_id = $update["message"]["message_id"] ?? "";
$userId = $update["message"]["from"]["id"] ?? "";
$firstname = $update["message"]["from"]["first_name"] ?? "";
$lastname = $update["message"]["from"]["last_name"] ?? "";
$username = $update["message"]["from"]["username"] ?? "N/A";

// Estados ficticios para respuesta
$statz = "Not Premium";
$sat = "200 OK";
$stat = "❌";

// Comandos disponibles
$command = strtolower(trim(explode(" ", $message)[0]));

// Logger
debugLog("Comando recibido: $command");

// START
if ($command == "/start" || $command == "!start") {
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡ - Status - $sat
━━━━━━━━━━━━━
Hey $firstname.
I'm still in development and some of my gates are not available for public.
Please use /cmds for available commands.
This Chat ID is: $chatId
Your Telegram ID is: $userId
And Your Status is: $statz</b>", $message_id);
}

// /pre <userId>
if ($command == "/pre" && $userId == '6240292246') {
    $uid = trim(substr($message, 5));
    if (empty($uid)) {
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Status - $sat
━━━━━━━━━━━━━
You need to provide a userid.</b>", $message_id);
    } else {
        file_put_contents("pre.txt", $uid . PHP_EOL, FILE_APPEND);
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Status - $sat
━━━━━━━━━━━━━
[ϟ] $uid is now a premium user.</b>", $message_id);
    }
}

// /status
if ($command == "/status") {
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Status - $sat
━━━━━━━━━━━━━
[ϟ] Full Name: $firstname $lastname
[ϟ] Username: @$username
[ϟ] Telegram ID: <code>$userId</code>
[ϟ] Chat ID: <code>$chatId</code>
[ϟ] Premium Activated: $stat</b>", $message_id);
}

// /cmds
if ($command == "/cmds") {
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Status - $sat
[ϟ] TOTAL CHECKER COMMANDS - 6
━━━━━━━━━━━━━
[ϟ] Stripe Sk Charge - /ch
[ϟ] Stripe Charge €4 - /spp [Site Based]
[ϟ] Stripe Killer \$750 [Site Based] - /sch
[ϟ] Stripe Auth [Site Based] - /au
[ϟ] Mass Stripe Sk Charge - /mch
[ϟ] Sk Checker - /sk
━━━━━━━━━━━━━
Hit /tools for list of available tools.</b>", $message_id);
}

// /tools
if ($command == "/tools") {
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Status - $sat
[ϟ] TOTAL TOOLS COMMANDS - 4
━━━━━━━━━━━━━
[ϟ] Bin Lookup - /bin
[ϟ] Iban Lookup - /iban
[ϟ] Check Account Status - /status
[ϟ] IP Details Lookup - /ip
━━━━━━━━━━━━━
Hit /cmds for list of available checker gates.</b>", $message_id);
}
?>
