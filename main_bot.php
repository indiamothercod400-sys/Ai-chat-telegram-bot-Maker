<?php
require_once 'database.php';

$master_token = getenv('MAIN_BOT_TOKEN');
if (!$master_token) {
    die("Error: MAIN_BOT_TOKEN environment variable not set.\n");
}

$api_url = "https://api.telegram.org/bot{$master_token}/";
$last_update_id = 0;

$user_states = [];

echo "Master Bot Running...\n";

while (true) {
    $response = @file_get_contents($api_url . "getUpdates?offset=" . ($last_update_id + 1) . "&timeout=10");
    if (!$response) {
        sleep(2);
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['result'])) {
        foreach ($data['result'] as $update) {
            $last_update_id = $update['update_id'];
            
            // Callback Query (Button Press)
            if (isset($update['callback_query'])) {
                $chat_id = $update['callback_query']['message']['chat']['id'];
                $data_code = $update['callback_query']['data'];
                
                if ($data_code === 'create_bot') {
                    $user_states[$chat_id] = 'awaiting_token';
                    send_message($api_url, $chat_id, "🤖 আপনার Telegram Bot Token টি এখানে পাঠান:\n\n(BotFather থেকে টোকেন কপি করে আনুন)");
                }
                continue;
            }

            // Regular Message
            if (isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $text = trim($update['message']['text']);

                if ($text === '/start') {
                    $keyboard = [
                        'inline_keyboard' => [
                            [['text' => '➕ Create your own AI chat bot', 'callback_data' => 'create_bot']]
                        ]
                    ];
                    send_message($api_url, $chat_id, "👋 **AI Chatbot Maker-এ স্বাগতম!**\n\nনিচের বাটনে ক্লিক করে আপনার নিজের AI Bot তৈরি করুন।", $keyboard);
                } 
                elseif (isset($user_states[$chat_id]) && $user_states[$chat_id] === 'awaiting_token') {
                    // Token verification pattern (simple check)
                    if (preg_match('/^[0-9]+:[a-zA-Z0-9_-]+$/', $text)) {
                        save_bot($chat_id, $text);
                        unset($user_states[$chat_id]);
                        
                        // Launch the new user bot background process
                        exec("php user_bot_runner.php " . escapeshellarg($text) . " > /dev/null 2>&1 &");
                        
                        send_message($api_url, $chat_id, "✅ **অভিনন্দন!** আপনার AI Bot টি চালু হয়ে গেছে। আপনার বটে মেসেজ দিয়ে পরীক্ষা করুন।");
                    } else {
                        send_message($api_url, $chat_id, "❌ **ভুল টোকেন!** সঠিক Bot Token দিন (যেমন: `123456789:ABCdefGhIJKlmNoPQRstuVWXyz`).");
                    }
                }
            }
        }
    }
    sleep(1);
}

function send_message($api_url, $chat_id, $text, $keyboard = null) {
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode($keyboard);
    }
    
    $ch = curl_init($api_url . "sendMessage");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    curl_close($ch);
}
