<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;
use Stripe\Stripe;
use Stripe\Token;
use Stripe\Charge;
use Stripe\Refund;

// Habilitar depuración
ini_set('display_errors', 0);
error_reporting(E_ALL);
file_put_contents("debug.log", "[" . date("Y-m-d H:i:s") . "] Script iniciado, REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Enrutador simple
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
debugLog("Path procesado: $path");

// Normalizar rutas para subdirectorio /Hecker-CHK-main
$basePath = '/Hecker-CHK-main';
$normalizedPath = preg_replace('#^' . preg_quote($basePath) . '#', '', $path);
debugLog("Normalized Path: $normalizedPath");

// Manejar rutas dinámicas
if (preg_match('#^/form/([a-f0-9]{32})$#', $normalizedPath, $matches) || $normalizedPath === '/submit-token' || $normalizedPath === '/form.js') {
    debugLog("Ruta dinámica detectada: $normalizedPath");
} else {
    // Manejar webhook de Telegram
    $update = json_decode(file_get_contents("php://input"), true);
    if (empty($update)) {
        debugLog("No se recibió update de Telegram");
        http_response_code(200);
        exit;
    }
}

// Cargar variables de entorno
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    debugLog("Error al cargar .env: " . $e->getMessage());
    exit;
}

$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
$stripePublishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
$botToken = $_ENV['BOT_TOKEN'] ?? '';
$ngrokUrl = $_ENV['NGROK_URL'] ?? '';

if (empty($botToken)) {
    debugLog("BOT_TOKEN no configurado");
    exit;
}
if (empty($stripeSecretKey)) {
    debugLog("STRIPE_SECRET_KEY no configurado");
    exit;
}
if (empty($stripePublishableKey)) {
    debugLog("STRIPE_PUBLISHABLE_KEY no configurado");
    exit;
}
if (empty($ngrokUrl)) {
    debugLog("NGROK_URL no configurado");
    exit;
}

// Sesiones temporales para formularios (en archivo)
$sessionFile = __DIR__ . '/sessions.json';
function loadSessions() {
    global $sessionFile;
    if (file_exists($sessionFile)) {
        $data = json_decode(file_get_contents($sessionFile), true);
        return is_array($data) ? $data : [];
    }
    return [];
}
function saveSessions($sessions) {
    global $sessionFile;
    try {
        $result = file_put_contents($sessionFile, json_encode($sessions, JSON_PRETTY_PRINT));
        if ($result === false) {
            debugLog("Error al escribir en sessions.json");
        } else {
            debugLog("Sesiones guardadas en sessions.json");
        }
    } catch (Exception $e) {
        debugLog("Excepción al guardar sessions.json: " . $e->getMessage());
    }
}
$cardSessions = loadSessions();

// Leer actualización del webhook
if (!empty($update)) {
    file_put_contents("log.txt", json_encode($update, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    debugLog("Update recibido: " . json_encode($update));
} else {
    debugLog("No se recibió update");
}

// Función para guardar logs de depuración
function debugLog($mensaje) {
    $fecha = date("Y-m-d H:i:s");
    file_put_contents("debug.log", "[$fecha] $mensaje\n", FILE_APPEND);
}

// Obtener datos del mensaje
$message = $update["message"]["text"] ?? "";
$chatId = $update["message"]["chat"]["id"] ?? "";
$message_id = $update["message"]["message_id"] ?? "";
$userId = $update["message"]["from"]["id"] ?? "";
$firstname = $update["message"]["from"]["first_name"] ?? "";
$lastname = $update["message"]["from"]["last_name"] ?? "";
$username = $update["message"]["from"]["username"] ?? "N/A";

// Verificar estado premium
$pro = file_get_contents('pre.txt') ?: '';
$pros = array_filter(explode("\n", $pro));
$statz = in_array($userId, $pros) ? "Premium" : "No Premium";
$stat = in_array($userId, $pros) ? "✅" : "❌";
$sat = "200 OK";

// Comandos disponibles
$command = strtolower(trim(explode(" ", $message)[0]));

// Logger
debugLog("Comando recibido: $command");

// /start
if ($command == "/start" || $command == "!start") {
    debugLog("Procesando comando /start para userId: $userId");
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡ - Estado - $sat\n━━━━━━━━━━━━━\nHola $firstname.\nEstoy en desarrollo y algunas funciones no están disponibles.\nUsa /cmds para ver los comandos disponibles.\nTu ID de chat es: $chatId\nTu ID de Telegram es: $userId\nTu estado es: $statz</b>", $message_id);
}

// /pre <userId>
if ($command == "/pre" && $userId == '6240292246') {
    debugLog("Procesando comando /pre para userId: $userId");
    $uid = trim(substr($message, 5));
    if (empty($uid) || !preg_match('/^\d+$/', $uid)) {
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n━━━━━━━━━━━━━\n[ϟ] Debes proporcionar un ID de usuario numérico válido.</b>", $message_id);
    } else {
        if (!in_array($uid, $pros)) {
            file_put_contents("pre.txt", $uid . PHP_EOL, FILE_APPEND);
            sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n━━━━━━━━━━━━━\n[ϟ] El usuario $uid ahora es premium.</b>", $message_id);
        } else {
            sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n━━━━━━━━━━━━━\n[ϟ] El usuario $uid ya es premium.</b>", $message_id);
        }
    }
}

// /status
if ($command == "/status") {
    debugLog("Procesando comando /status para userId: $userId");
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n━━━━━━━━━━━━━\n[ϟ] Nombre completo: $firstname $lastname\n[ϟ] Nombre de usuario: @$username\n[ϟ] ID de Telegram: <code>$userId</code>\n[ϟ] ID de chat: <code>$chatId</code>\n[ϟ] Premium activado: $stat</b>", $message_id);
}

// /cmds
if ($command == "/cmds") {
    debugLog("Procesando comando /cmds para userId: $userId");
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n[ϟ] TOTAL DE COMANDOS - 2\n━━━━━━━━━━━━━\n[ϟ] Verificación de tarjeta Stripe - /ch\n[ϟ] Verificación de SK Stripe - /sk\n━━━━━━━━━━━━━\nUsa /tools para ver herramientas disponibles.</b>", $message_id);
}

// /tools
if ($command == "/tools") {
    debugLog("Procesando comando /tools para userId: $userId");
    sendaction($chatId, "typing");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - $sat\n[ϟ] TOTAL DE HERRAMIENTAS - 5\n━━━━━━━━━━━━━\n[ϟ] Consulta de BIN - /bin\n[ϟ] Consulta de IBAN - /iban\n[ϟ] Estado de cuenta - /status\n[ϟ] Consulta de IP - /ip\n[ϟ] Validación Luhn - /luhn\n━━━━━━━━━━━━━\nUsa /cmds para ver comandos de verificación.</b>", $message_id);
}

// /bin
if ($command == "/bin") {
    debugLog("Procesando comando /bin para userId: $userId");
    if (!in_array($userId, $pros)) {
        debugLog("Usuario no es premium: $userId");
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes ser usuario premium para usar este comando.</b>", $message_id);
        exit;
    }

    sendaction($chatId, "typing");
    $binInput = trim(substr($message, 5));
    $bin = '';
    $cardDisplay = '';

    // Procesar entrada
    if (preg_match('/^(\d{6,8})$/', $binInput)) {
        // BIN directo
        $bin = $binInput;
        $cardDisplay = $bin;
    } elseif (preg_match('/^(\d{12,19})\|(\d{1,2})\|(\d{2,4})\|(\d{3,4})$/', $binInput, $matches)) {
        // Formato completo: número|mes|año|cvv
        $cardNumber = $matches[1];
        $bin = substr($cardNumber, 0, 6);
        $last4 = substr($cardNumber, -4);
        $cardDisplay = $bin . 'xxxx' . $last4;
    } else {
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes proporcionar un BIN válido (6-8 dígitos) o una tarjeta en formato número|mes|año|cvv (ej. 4242424242424242|12|2026|123).</b>", $message_id);
        exit;
    }

    $time_start = microtime(true);

    // Consulta a binlist.net
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://lookup.binlist.net/' . $bin);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $fim = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        debugLog("Error en consulta a binlist.net para BIN $bin: $curl_error");
    } elseif ($http_code !== 200 || !$fim || strpos($fim, '"error"') !== false) {
        debugLog("Respuesta inválida de binlist.net para BIN $bin: HTTP $http_code, Respuesta: " . ($fim ?: 'vacía'));
        // API alternativa: binlookup.io
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.binlookup.io/bin/' . $bin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $fim_alt = curl_exec($ch);
        $curl_error_alt = curl_error($ch);
        $http_code_alt = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($curl_error_alt) {
            debugLog("Error en consulta a binlookup.io para BIN $bin: $curl_error_alt");
        } elseif ($http_code_alt !== 200 || !$fim_alt) {
            debugLog("Respuesta inválida de binlookup.io para BIN $bin: HTTP $http_code_alt, Respuesta: " . ($fim_alt ?: 'vacía'));
            // Tercera API: bincheck.io
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://bincheck.io/api/' . $bin);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $fim_bincheck = curl_exec($ch);
            $curl_error_bincheck = curl_error($ch);
            $http_code_bincheck = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($curl_error_bincheck) {
                debugLog("Error en consulta a bincheck.io para BIN $bin: $curl_error_bincheck");
                $bank = $name = $brand = $country = $type = $scheme = 'Desconocido';
            } elseif ($http_code_bincheck !== 200 || !$fim_bincheck) {
                debugLog("Respuesta inválida de bincheck.io para BIN $bin: HTTP $http_code_bincheck, Respuesta: " . ($fim_bincheck ?: 'vacía'));
                $bank = $name = $brand = $country = $type = $scheme = 'Desconocido';
            } else {
                $data = json_decode($fim_bincheck, true);
                $bank = strtoupper($data['bank'] ?? 'Desconocido');
                $name = strtoupper($data['card'] ?? 'Desconocido');
                $brand = strtoupper($data['card'] ?? 'Desconocido');
                $country = strtoupper($data['country_name'] ?? 'Desconocido');
                $type = strtoupper($data['type'] ?? 'Desconocido');
                $scheme = strtoupper($data['level'] ?? 'Desconocido');
            }
        } else {
            $data = json_decode($fim_alt, true);
            $bank = $data['bank']['name'] ?? 'Desconocido';
            $name = strtoupper($data['card']['brand'] ?? 'Desconocido');
            $brand = strtoupper($data['card']['brand'] ?? 'Desconocido');
            $country = strtoupper($data['country']['name'] ?? 'Desconocido');
            $type = strtoupper($data['card']['type'] ?? 'Desconocido');
            $scheme = strtoupper($data['card']['scheme'] ?? 'Desconocido');
        }
    } else {
        $bank = getStr($fim, '"bank":{"name":"', '"') ?: 'Desconocido';
        $name = strtoupper(getStr($fim, '"name":"', '"') ?: 'Desconocido');
        $brand = strtoupper(getStr($fim, '"brand":"', '"') ?: 'Desconocido');
        $country = strtoupper(getStr($fim, '"country":{"name":"', '"') ?: 'Desconocido');
        $emoji = getStr($fim, '"emoji":"', '"') ?: '';
        $type = strtoupper(getStr($fim, '"type":"', '"') ?: 'Desconocido');
        $scheme = strtoupper(getStr($fim, '"scheme":"', '"') ?: 'Desconocido');
    }

    $bug = file_get_contents('bugdb.txt') ?: '';
    $bugs = array_filter(explode("\n", $bug));
    $isbug = in_array($bin, $bugs) ? 'Sí' : 'No';

    $binrs = "<b>\n━━━━━━━━━━━━━\n[ϟ] BIN: $bin\n[ϟ] TARJETA: $cardDisplay\n[ϟ] PAÍS: $country $emoji\n[ϟ] MARCA: $brand\n[ϟ] NIVEL: $scheme\n[ϟ] TIPO: $type\n[ϟ] BANCO: $bank\n[ϟ] BIN CON ERROR: $isbug</b>";

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);

    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] CONSULTA: BIN\n$binrs\n━━━━━━━━━━━━━\n[ϟ] TIEMPO: $execution_time s\n[ϟ] Verificado por: @$username\n[ϟ] ID de usuario: $userId\n[ϟ] Premium activado: $stat</b>", $message_id);
    exit;
}

// /luhn
if ($command == "/luhn") {
    debugLog("Procesando comando /luhn para userId: $userId");
    if (!in_array($userId, $pros)) {
        debugLog("Usuario no es premium: $userId");
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes ser usuario premium para usar este comando.</b>", $message_id);
        exit;
    }

    sendaction($chatId, "typing");
    $cardInput = trim(substr($message, 6));
    $cardNumber = '';
    $cardDisplay = '';

    // Procesar entrada
    if (preg_match('/^(\d{12,19})$/', $cardInput)) {
        // Solo número de tarjeta
        $cardNumber = $cardInput;
        $bin = substr($cardNumber, 0, 6);
        $last4 = substr($cardNumber, -4);
        $cardDisplay = $bin . 'xxxx' . $last4;
    } elseif (preg_match('/^(\d{12,19})\|(\d{1,2})\|(\d{2,4})\|(\d{3,4})$/', $cardInput, $matches)) {
        // Formato completo: número|mes|año|cvv
        $cardNumber = $matches[1];
        $bin = substr($cardNumber, 0, 6);
        $last4 = substr($cardNumber, -4);
        $cardDisplay = $bin . 'xxxx' . $last4;
    } else {
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes proporcionar un número de tarjeta válido (12-19 dígitos) o en formato número|mes|año|cvv .</b>", $message_id);
        exit;
    }

    $time_start = microtime(true);

    // Algoritmo de Luhn
    $digits = str_split($cardNumber);
    $sum = 0;
    $isEven = false;
    for ($i = count($digits) - 1; $i >= 0; $i--) {
        $digit = (int)$digits[$i];
        if ($isEven) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $isEven = !$isEven;
    }
    $isValid = ($sum % 10 === 0) ? 'SÍ' : 'NO';
    debugLog("Validación Luhn para tarjeta $cardDisplay: Válido: $isValid");

    $luhnrs = "<b>\n━━━━━━━━━━━━━\n[ϟ] TARJETA: $cardDisplay\n[ϟ] VÁLIDO: $isValid</b>";

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);

    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] CONSULTA: VALIDACIÓN LUHN\n$luhnrs\n━━━━━━━━━━━━━\n[ϟ] TIEMPO: $execution_time s\n[ϟ] Verificado por: @$username\n[ϟ] ID de usuario: $userId\n[ϟ] Premium activado: $stat</b>", $message_id);
    exit;
}

// /iban
if ($command == "/iban") {
    debugLog("Procesando comando /iban para userId: $userId");
    if (!in_array($userId, $pros)) {
        debugLog("Usuario no es premium: $userId");
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes ser usuario premium para usar este comando.</b>", $message_id);
        exit;
    }

    sendaction($chatId, "typing");
    $ibanInput = strtoupper(trim(substr($message, 6)));
    if (empty($ibanInput) || !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/', $ibanInput)) {
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes proporcionar un IBAN válido (ej. ES9121000418450200051332).</b>", $message_id);
        exit;
    }

    $time_start = microtime(true);

    // Validación manual del IBAN
    $iban = str_replace(' ', '', $ibanInput);
    $countryCode = substr($iban, 0, 2);
    $checkDigits = substr($iban, 2, 2);
    $accountNumber = substr($iban, 4);

    // Mover primeros 4 caracteres al final y convertir letras a números
    $numericIban = $accountNumber . $countryCode . $checkDigits;
    $numericIban = preg_replace_callback('/[A-Z]/', function($match) {
        return ord($match[0]) - 55;
    }, $numericIban);

    // Validar módulo 97
    $isValid = bcmod($numericIban, '97') == 1 ? 'SÍ' : 'NO';
    debugLog("Validación manual IBAN: $iban, Válido: $isValid");

    // Consulta a iban.com para detalles
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.iban.com/validate/$iban");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        debugLog("Error en consulta a iban.com: $curl_error");
        $bank = $country = 'Desconocido';
        $validity = $isValid;
    } elseif ($response) {
        $data = json_decode($response, true);
        $validity = ($data['valid'] ?? false) ? 'SÍ' : 'NO';
        $bank = $data['bank_data']['bank'] ?? 'Desconocido';
        $country = strtoupper($data['bank_data']['country'] ?? $countryCode);
    } else {
        debugLog("Respuesta inválida de iban.com");
        $bank = $country = 'Desconocido';
        $validity = $isValid;
    }

    $ibanrs = "<b>\n━━━━━━━━━━━━━\n[ϟ] IBAN: $iban\n[ϟ] VÁLIDO: $validity\n[ϟ] PAÍS: $country\n[ϟ] BANCO: $bank</b>";

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);

    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] CONSULTA: IBAN\n$ibanrs\n━━━━━━━━━━━━━\n[ϟ] TIEMPO: $execution_time s\n[ϟ] Verificado por: @$username\n[ϟ] ID de usuario: $userId\n[ϟ] Premium activado: $stat</b>", $message_id);
    exit;
}

// /ch
if ($command == "/ch") {
    global $cardSessions, $ngrokUrl, $stripePublishableKey, $botToken, $sessionFile;
    debugLog("Procesando comando /ch para userId: $userId");
    if (!in_array($userId, $pros)) {
        debugLog("Usuario no es premium: $userId");
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Debes ser usuario premium para usar este comando.</b>", $message_id);
        exit;
    }

    sendaction($chatId, "typing");
    $cardInput = trim(substr($message, 4));
    debugLog("CardInput recibido: $cardInput");
    if (!empty($cardInput)) {
        debugLog("CardInput no debe contener datos para entrada manual");
        sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Por favor, usa /ch sin detalles de tarjeta. Ingresa los datos en el formulario seguro.</b>", $message_id);
        exit;
    }

    // Generar ID único para la sesión
    $formId = bin2hex(random_bytes(16));
    $cardSessions[$formId] = [
        'chatId' => $chatId,
        'userId' => $userId,
        'username' => $username,
        'messageId' => $message_id,
        'card' => '',
        'timestamp' => time()
    ];
    saveSessions($cardSessions);
    debugLog("Sesión creada para formId: $formId");

    // Enviar URL del formulario
    $formUrl = rtrim($ngrokUrl, '/') . "/Hecker-CHK-main/form/$formId";
    debugLog("URL del formulario generada: $formUrl");
    sendMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Abre este enlace para ingresar los detalles de tu tarjeta:\n<a href=\"$formUrl\">$formUrl</a></b>", $message_id);
    exit;
}

// Endpoint para servir formulario dinámico
if (preg_match('#^/form/([a-f0-9]{32})$#', $normalizedPath, $matches)) {
    global $cardSessions, $stripePublishableKey;
    $cardSessions = loadSessions();
    $formId = $matches[1];
    debugLog("Manejando formulario para formId: $formId");

    if (!isset($cardSessions[$formId])) {
        debugLog("FormId inválido: $formId");
        http_response_code(400);
        echo "ID de formulario inválido";
        exit;
    }

    header('Content-Type: text/html');
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Validación de Tarjeta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://js.stripe.com/v3/"></script>
    <script src="/Hecker-CHK-main/form.js"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 20px auto; padding: 10px; }
        #card-element { border: 1px solid #ccc; padding: 12px; border-radius: 4px; margin-bottom: 10px; }
        #submit-button { background: #5469d4; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; width: 100%; }
        #submit-button:disabled { background: #cccccc; cursor: not-allowed; }
        #error-message { color: red; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Ingresar Datos de Tarjeta</h2>
    <form id="payment-form">
        <div id="card-element"></div>
        <button id="submit-button" type="button">Enviar Tarjeta</button>
        <div id="error-message"></div>
    </form>
    <script>
        console.log('Iniciando script inline para formId: $formId');
        const formId = '$formId';
        try {
            tokenizeCard('$stripePublishableKey', formId);
        } catch (e) {
            console.error('Error al llamar tokenizeCard:', e);
            document.getElementById('error-message').textContent = 'Error: ' + e.message;
        }
    </script>
</body>
</html>
HTML;
    exit;
}

// Endpoint para servir form.js
if ($normalizedPath === '/form.js') {
    debugLog("Sirviendo form.js");
    header('Content-Type: application/javascript');
    if (file_exists('public/form.js')) {
        readfile('public/form.js');
    } else {
        debugLog("Error: form.js no encontrado en public/");
        http_response_code(404);
        echo "Archivo no encontrado";
    }
    exit;
}

// Endpoint para recibir el token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $normalizedPath === '/submit-token') {
    global $cardSessions, $stripeSecretKey, $botToken, $sessionFile;
    $cardSessions = loadSessions();
    $input = json_decode(file_get_contents("php://input"), true);
    $formId = $input['formId'] ?? '';
    $tokenId = $input['token'] ?? '';
    debugLog("Recibiendo token para formId: $formId, token: " . ($tokenId ?: 'vacío'));

    if (!isset($cardSessions[$formId])) {
        debugLog("FormId inválido en submit-token: $formId");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID de formulario inválido']);
        exit;
    }

    if (empty($tokenId)) {
        debugLog("Token inválido recibido para formId: $formId");
        sendMessage($cardSessions[$formId]['chatId'], "<b>Token inválido recibido.</b>", $cardSessions[$formId]['messageId']);
        unset($cardSessions[$formId]);
        saveSessions($cardSessions);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Token inválido']);
        exit;
    }

    $chatId = $cardSessions[$formId]['chatId'];
    $userId = $cardSessions[$formId]['userId'];
    $username = $cardSessions[$formId]['username'];
    $message_id = $cardSessions[$formId]['messageId'];
    debugLog("Procesando token para formId: $formId, token: $tokenId");

    $time_start = microtime(true);
    sendaction($chatId, "typing");

    $sendmes = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=" . urlencode($chatId) . "&text=" . urlencode("<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] Token: $tokenId\n[ϟ] ESTADO: VERIFICANDO, POR FAVOR ESPERA.\n━━━━━━━━━━━━━</b>") . "&parse_mode=HTML";
    $sent = json_decode(file_get_contents($sendmes), true);
    $mes_id = $sent['result']['message_id'] ?? $message_id;
    debugLog("Mensaje enviado, message_id: $mes_id");

    Stripe::setApiKey($stripeSecretKey);

    try {
        $token = Token::retrieve($tokenId);
        $bin = substr($token->card->number, 0, 6) ?: 'xxxxxx';
        $cc = $token->card->last4;
        $lista = "$bin|{$token->card->exp_month}|{$token->card->exp_year}|xxx";

        $charge = Charge::create([
            'amount' => 50,
            'currency' => 'usd',
            'source' => $tokenId,
            'description' => 'Verificación de tarjeta real',
            'capture' => false
        ]);

        $pass = 'VERIFICACIÓN EXITOSA ✅';
        $msg = 'La tarjeta es válida y se autorizó $0.50 correctamente';
        Refund::create(['charge' => $charge->id]);
    } catch (\Stripe\Exception\CardException $e) {
        $errorCode = $e->getError()->code;
        $msg = $e->getError()->message;
        debugLog("Error de Stripe: $errorCode - $msg");

        if ($errorCode === 'insufficient_funds') {
            $pass = 'CVV COINCIDE ✅';
            $msg = 'La tarjeta tiene fondos insuficientes, pero el CVV es correcto';
        } elseif ($errorCode === 'incorrect_cvc') {
            $pass = 'CVV NO COINCIDE ❌';
            $msg = 'El código CVV ingresado es incorrecto';
        } elseif ($errorCode === 'card_declined') {
            $pass = 'TARJETA RECHAZADA ❌';
            $msg = $e->getError()->decline_code === 'fraudulent' ? 'Tarjeta rechazada por sospecha de fraude' : 'La tarjeta fue rechazada por el emisor';
        } else {
            $pass = 'ERROR ❌';
            $msg = "Error de procesamiento: $msg";
        }
        $bin = $bin ?: 'xxxxxx';
        $lista = "$bin|{$token->card->exp_month}|{$token->card->exp_year}|xxx";
    } catch (\Exception $e) {
        $pass = 'ERROR ❌';
        $msg = $e->getMessage();
        debugLog("Excepción general: $msg");
        $bin = 'xxxxxx';
        $lista = 'xxxx|xx|xxxx|xxx';
    }

    // Consulta a binlist.net con manejo de errores
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://lookup.binlist.net/' . $bin);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $fim = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curl_error) {
        debugLog("Error en consulta a binlist.net para BIN $bin: $curl_error");
    } elseif ($http_code !== 200 || !$fim || strpos($fim, '"error"') !== false) {
        debugLog("Respuesta inválida de binlist.net para BIN $bin: HTTP $http_code, Respuesta: " . ($fim ?: 'vacía'));
        // API alternativa: binlookup.io
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.binlookup.io/bin/' . $bin);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $fim_alt = curl_exec($ch);
        $curl_error_alt = curl_error($ch);
        $http_code_alt = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($curl_error_alt) {
            debugLog("Error en consulta a binlookup.io para BIN $bin: $curl_error_alt");
        } elseif ($http_code_alt !== 200 || !$fim_alt) {
            debugLog("Respuesta inválida de binlookup.io para BIN $bin: HTTP $http_code_alt, Respuesta: " . ($fim_alt ?: 'vacía'));
            // Tercera API: bincheck.io
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://bincheck.io/api/' . $bin);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $fim_bincheck = curl_exec($ch);
            $curl_error_bincheck = curl_error($ch);
            $http_code_bincheck = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($curl_error_bincheck) {
                debugLog("Error en consulta a bincheck.io para BIN $bin: $curl_error_bincheck");
                $bank = $name = $brand = $country = $type = $scheme = 'Desconocido';
            } elseif ($http_code_bincheck !== 200 || !$fim_bincheck) {
                debugLog("Respuesta inválida de bincheck.io para BIN $bin: HTTP $http_code_bincheck, Respuesta: " . ($fim_bincheck ?: 'vacía'));
                $bank = $name = $brand = $country = $type = $scheme = 'Desconocido';
            } else {
                $data = json_decode($fim_bincheck, true);
                $bank = strtoupper($data['bank'] ?? 'Desconocido');
                $name = strtoupper($data['card'] ?? 'Desconocido');
                $brand = strtoupper($data['card'] ?? 'Desconocido');
                $country = strtoupper($data['country_name'] ?? 'Desconocido');
                $type = strtoupper($data['type'] ?? 'Desconocido');
                $scheme = strtoupper($data['level'] ?? 'Desconocido');
            }
        } else {
            $data = json_decode($fim_alt, true);
            $bank = $data['bank']['name'] ?? 'Desconocido';
            $name = strtoupper($data['card']['brand'] ?? 'Desconocido');
            $brand = strtoupper($data['card']['brand'] ?? 'Desconocido');
            $country = strtoupper($data['country']['name'] ?? 'Desconocido');
            $type = strtoupper($data['card']['type'] ?? 'Desconocido');
            $scheme = strtoupper($data['card']['scheme'] ?? 'Desconocido');
        }
    } else {
        $bank = getStr($fim, '"bank":{"name":"', '"') ?: 'Desconocido';
        $name = strtoupper(getStr($fim, '"name":"', '"') ?: 'Desconocido');
        $brand = strtoupper(getStr($fim, '"brand":"', '"') ?: 'Desconocido');
        $country = strtoupper(getStr($fim, '"country":{"name":"', '"') ?: 'Desconocido');
        $emoji = getStr($fim, '"emoji":"', '"') ?: '';
        $type = strtoupper(getStr($fim, '"type":"', '"') ?: 'Desconocido');
        $scheme = strtoupper(getStr($fim, '"scheme":"', '"') ?: 'Desconocido');
    }

    $bug = file_get_contents('bugdb.txt') ?: '';
    $bugs = array_filter(explode("\n", $bug));
    $isbug = in_array($bin, $bugs) ? 'Sí' : 'No';

    $binrs = "<b>\n━━━━━━━━━━━━━\n[ϟ] BIN: $bin\n[ϟ] PAÍS: $country $emoji\n[ϟ] MARCA: $brand\n[ϟ] NIVEL: $scheme\n[ϟ] TIPO: $type\n[ϟ] BANCO: $bank</b>";

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);

    // Recargar estado premium para consistencia
    $pro = file_get_contents('pre.txt') ?: '';
    $pros = array_filter(explode("\n", $pro));
    $stat = in_array($userId, $pros) ? "✅" : "❌";

    editMessage($chatId, "<b>HECKER CHK ⚡️ - Estado - OK\n━━━━━━━━━━━━━\n[ϟ] PUERTA: VERIFICACIÓN DE TARJETA STRIPE\n[ϟ] CC: <code>$lista</code>\n[ϟ] ESTADO: $pass\n[ϟ] RESPUESTA: $msg\n[ϟ] BIN CON ERROR: $isbug\n$binrs\n━━━━━━━━━━━━━\n[ϟ] TIEMPO: $execution_time s\n[ϟ] Verificado por: @$username\n[ϟ] ID de usuario: $userId\n[ϟ] Premium activado: $stat\n</b>", $mes_id);

    unset($cardSessions[$formId]);
    saveSessions($cardSessions);
    debugLog("Sesión eliminada para formId: $formId");
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Funciones auxiliares
function sendaction($chatId, $action) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/sendChatAction?chat_id=" . urlencode($chatId) . "&action=" . urlencode($action);
    $response = file_get_contents($url);
    debugLog("Respuesta de sendChatAction: $response");
}

function sendMessage($chatId, $text, $message_id) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=" . urlencode($chatId) . "&text=" . urlencode($text) . "&parse_mode=HTML&reply_to_message_id=" . urlencode($message_id);
    $response = file_get_contents($url);
    debugLog("Respuesta de sendMessage: $response");
}

function editMessage($chatId, $text, $message_id) {
    global $botToken;
    $url = "https://api.telegram.org/bot$botToken/editMessageText?chat_id=" . urlencode($chatId) . "&message_id=" . urlencode($message_id) . "&text=" . urlencode($text) . "&parse_mode=HTML";
    $response = file_get_contents($url);
    debugLog("Respuesta de editMessage: $response");
}

function multiexplode($delimiters, $string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    return explode($delimiters[0], $ready);
}

function getStr($string, $start, $end) {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
?>