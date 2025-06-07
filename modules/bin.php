<?php

$time_start = microtime(true);

// Si el mensaje empieza con /bin, !bin o .bin
if (preg_match('/^(\/|!|\.)bin\s+(\d{6,})$/i', $message, $matches)) {
    sendaction($chatId, 'typing');

    $bin = substr($matches[2], 0, 6); // Solo los primeros 6 dígitos

    // Hacer solicitud a binlist.net
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://lookup.binlist.net/{$bin}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "TelegramBot/1.0");
    $fim = curl_exec($ch);
    curl_close($ch);

    // Extraer datos
    $bank    = GetStr($fim, '"bank":{"name":"', '"');
    $name    = strtoupper(GetStr($fim, '"name":"', '"'));
    $brand   = strtoupper(GetStr($fim, '"brand":"', '"'));
    $country = strtoupper(GetStr($fim, '"country":{"name":"', '"'));
    $scheme  = strtoupper(GetStr($fim, '"scheme":"', '"'));
    $emoji   = GetStr($fim, '"emoji":"', '"');
    $type    = strtoupper(GetStr($fim, '"type":"', '"'));

    $time_end = microtime(true);
    $execution_time = round($time_end - $time_start, 2);

    // Valores falsos por ahora (hasta que los definas)
    $sat = "LIVE";
    $stat = "NO";
    $username = $username ?? "undefined";
    $userId = $userId ?? "0";

    $msg = "<b>HECKER CHK ⚡️ - Status - $sat
━━━━━━━━━━━━━
[ϟ] TOOL: BIN LOOKUP
[ϟ] STATUS: VALID BIN ✅
[ϟ] BIN: $bin
[ϟ] COUNTRY: $country $emoji
[ϟ] BRAND: $brand
[ϟ] LEVEL: $scheme
[ϟ] TYPE: $type
[ϟ] BANK: $bank
━━━━━━━━━━━━━
[ϟ] TIME TAKEN: {$execution_time}s
[ϟ] Checked By: @$username
[ϟ] UserID: $userId
[ϟ] Premium Activated: $stat</b>";

    sendMessage($chatId, urlencode($msg), $message_id);
}

?>