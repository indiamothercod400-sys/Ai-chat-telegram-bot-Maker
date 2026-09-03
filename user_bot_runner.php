<?php
if ($argc < 2) {
    die("Usage: php user_bot_runner.php <BOT_TOKEN>\n");
}

$bot_token = $argv[1];
$openrouter_key = getenv('OPENROUTER_API_KEY');

$api_url = "https://api.telegram.org/bot{$bot_token}/";
$last_update_id = 0;

echo "User Bot Started [Token: " . substr($bot_token, 0, 10) . "...]\n";

while (true) {
    $response = @file_get_contents($api_url . "getUpdates?offset=" . ($last_update_id + 1) . "&timeout=20");
    if (!$response) {
        sleep(2);
        continue;
    }

    $data = json_decode($response, true);

    if (isset($data['result'])) {
        foreach ($data['result'] as $update) {
            $last_update_id = $update['update_id'];

            if (isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $user_text = $update['message']['text'];

                if ($user_text === '/start') {
                    send_message($api_url, $chat_id, "Hello! I am an AI Assistant. Ask me anything!");
                    continue;
                }

                // Call OpenRouter API using openrouter/free
                $ai_reply = ask_openrouter($openrouter_key, $user_text);
                send_message($api_url, $chat_id, $ai_reply);
            }
        }
    }
    sleep(1);
}

function ask_openrouter($api_key, $prompt) {
    $url = "https://openrouter.ai/api/v1/chat/completions";
    
    $data = [
        "model" => "openrouter/free",
        "messages" => [
            ["role" => "system", "content" => "You are a helpful AI assistant."],
            ["role" => "user", "content" => $prompt]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $api_key,
        "Content-Type: application/json",
        "HTTP-Referer: https://render.com",
        "X-Title: AI Bot Maker"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? "Sorry, I couldn't process your request right now.";
}

function send_message($api_url, $chat_id, $text) {
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text
    ];
    
    $ch = curl_init($api_url . "sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    curl_close($ch);
}
