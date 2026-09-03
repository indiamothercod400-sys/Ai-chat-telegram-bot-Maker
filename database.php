<?php
$db = new SQLite3('/tmp/bots.db');

// Table to store user bots
$db->exec("CREATE TABLE IF NOT EXISTS user_bots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    bot_token TEXT UNIQUE NOT NULL
)");

function save_bot($user_id, $token) {
    global $db;
    $stmt = $db->prepare("INSERT OR IGNORE INTO user_bots (user_id, bot_token) VALUES (:user_id, :token)");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_TEXT);
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    return $stmt->execute();
}

function get_all_bots() {
    global $db;
    $results = $db->query("SELECT bot_token FROM user_bots");
    $tokens = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $tokens[] = $row['bot_token'];
    }
    return $tokens;
}
