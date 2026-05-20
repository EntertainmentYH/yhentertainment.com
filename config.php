<?php
/**
 * Entertainment_YH — Bootstrap / Configuration
 *
 * Handles: error reporting, mbstring fallbacks, config loading,
 * language detection, statistics tracking, CSRF, and captcha.
 *
 * Sets up all variables needed by handlers and templates.
 */

// ── Error handling ──────────────────────────────────────────────
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$statistics_dir = __DIR__ . '/statistics';
if (!is_dir($statistics_dir)) @mkdir($statistics_dir, 0755, true);
ini_set('error_log', $statistics_dir . '/php_error.log');

// ── Session ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Math captcha ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['captcha_a'], $_SESSION['captcha_b'], $_SESSION['captcha_answer'])) {
    $_SESSION['captcha_a'] = rand(1, 20);
    $_SESSION['captcha_b'] = rand(1, 20);
    $_SESSION['captcha_answer'] = $_SESSION['captcha_a'] + $_SESSION['captcha_b'];
    // HMAC-signed token so captcha verification doesn't depend on session persistence
    $_SESSION['captcha_hash'] = hash_hmac('sha256', (string)$_SESSION['captcha_answer'], 'yh_captcha_secret_2025');
}

// ── CSRF token ───────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Owner token (cookie-based identity for message ownership) ─────
// Each visitor gets a unique long-lived token. Messages are tied to this
// token instead of IP, so only the same browser can edit/delete them.
$visitor_token = $_COOKIE['yh_owner_token'] ?? '';
if (empty($visitor_token) || strlen($visitor_token) < 32) {
    $visitor_token = bin2hex(random_bytes(32));
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('yh_owner_token', $visitor_token, time() + 86400 * 365, '/', '', $secure, true);
}

// ── mbstring fallbacks ───────────────────────────────────────────
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
        if ($encoding !== 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encoding);
        }
        $arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        return $arr === false ? strlen($str) : count($arr);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = NULL, $encoding = 'UTF-8') {
        if ($encoding !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($str, 'UTF-8', $encoding);
        }
        $arr = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        if ($arr === false) return '';
        if ($length === NULL) {
            $slice = array_slice($arr, $start);
        } else {
            $slice = array_slice($arr, $start, $length);
        }
        return implode('', $slice);
    }
}
if (!function_exists('mb_stripos')) {
    function mb_stripos($haystack, $needle, $offset = 0, $encoding = 'UTF-8') {
        if ($needle === '') return 0;
        if ($encoding !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $haystack = mb_convert_encoding($haystack, 'UTF-8', $encoding);
            $needle = mb_convert_encoding($needle, 'UTF-8', $encoding);
            $encoding = 'UTF-8';
        }
        $lower = function($s) use ($encoding) {
            if (function_exists('mb_strtolower')) return mb_strtolower($s, $encoding);
            return strtolower($s);
        };
        $hay_len = mb_strlen($haystack, $encoding);
        $needle_len = mb_strlen($needle, $encoding);
        if ($offset < 0) $offset = 0;
        if ($needle_len === 0) return 0;
        $hay_lower = $lower($haystack);
        $needle_lower = $lower($needle);
        for ($i = $offset; $i <= $hay_len - $needle_len; $i++) {
            $chunk = mb_substr($hay_lower, $i, $needle_len, $encoding);
            if ($chunk === $needle_lower) return $i;
        }
        return false;
    }
}

// ── Configuration ────────────────────────────────────────────────
$config_file = __DIR__ . '/configuration.json';
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}

// ── Language detection ───────────────────────────────────────────
$lang_code = 'zh-cn';
$ip = $_SERVER['REMOTE_ADDR'];

$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$json = @file_get_contents("https://ip-api.com/json/$ip", false, $ctx);
$data = json_decode($json, true);

if ($data && $data['status'] === 'success') {
    $country_code = strtoupper($data['countryCode']);
    $visitor_timezone = !empty($data['timezone']) ? $data['timezone'] : null;
    if ($visitor_timezone) setcookie('visitor_tz', $visitor_timezone, time() + 3600 * 24 * 30, '/');
} elseif (isset($_COOKIE['country_code'])) {
    $country_code = strtoupper($_COOKIE['country_code']);
    $visitor_timezone = $_COOKIE['visitor_tz'] ?? null;
} else {
    $country_code = '';
    $visitor_timezone = $_COOKIE['visitor_tz'] ?? null;
}

switch ($country_code) {
    case 'US': case 'CA': case 'GB': case 'AU': case 'NZ': case 'IE': case 'SG': case 'EN':
        $lang_code = 'en'; break;
    case 'TW': case 'HK': case 'MO':
        $lang_code = 'zh-tw'; break;
    case 'CN':
        $lang_code = 'zh-cn'; break;
    case 'JP':
        $lang_code = 'jp'; break;
    case 'RU': case 'UA':
        $lang_code = 'ru'; break;
    default:
        $lang_code = 'zh-cn'; break;
}

if (empty($visitor_timezone)) {
    try {
        $visitor_timezone = date_default_timezone_get();
    } catch (Exception $e) {
        $visitor_timezone = 'UTC';
    }
}

// GET parameter override
if (isset($_GET['lang'])) {
    $lang = strtolower($_GET['lang']);
    $lang_map = ['zh-cn' => 'CN', 'en' => 'EN', 'zh-tw' => 'TW', 'jp' => 'JP', 'ru' => 'RU'];
    if (isset($lang_map[$lang])) {
        $lang_code = $lang;
        $country_code = $lang_map[$lang];
    }
}

if (!empty($country_code)) {
    setcookie('country_code', $country_code, time() + 3600 * 24 * 30, '/');
}

// ── Language pack ────────────────────────────────────────────────
$lang_file = __DIR__ . "/languages/{$lang_code}.json";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/zh-cn.json";
}
$lang = json_decode(@file_get_contents($lang_file), true) ?? [];

// ── Statistics file paths ────────────────────────────────────────
$daily_counter_file = "{$statistics_dir}/daily_counter.json";
$total_counter_file = "{$statistics_dir}/total_counter.json";
$online_file = "{$statistics_dir}/online.txt";
$today = date('Y-m-d');

// ── IP limiter (visit counting throttle) ─────────────────────────
$ip_limit_seconds = 10;
$ip_limiter_file = "{$statistics_dir}/ip_limiter.json";
$ip_limiter = [];
if (file_exists($ip_limiter_file)) {
    $ip_limiter = json_decode(file_get_contents($ip_limiter_file), true);
    if (!is_array($ip_limiter)) $ip_limiter = [];
}

$now_time = time();
$limiter_cutoff = $now_time - 3600;
$ip_limiter = array_filter($ip_limiter, function($ts) use ($limiter_cutoff) {
    return $ts > $limiter_cutoff;
});

if (!isset($ip_limiter[$ip]) || ($now_time - $ip_limiter[$ip]) > $ip_limit_seconds) {
    $ip_limiter[$ip] = $now_time;
    file_put_contents($ip_limiter_file, json_encode($ip_limiter, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
}

// ── Daily counter ────────────────────────────────────────────────
$daily_data = [];
if (file_exists($daily_counter_file)) {
    $daily_data = json_decode(file_get_contents($daily_counter_file), true);
    if (!is_array($daily_data)) $daily_data = [];
}
if (!isset($daily_data[$today])) {
    $daily_data[$today] = 1;
} else {
    $daily_data[$today]++;
}
file_put_contents($daily_counter_file, json_encode($daily_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
$today_count = $daily_data[$today];

// ── Total counter ────────────────────────────────────────────────
$total_count = 0;
if (file_exists($total_counter_file)) {
    $total_count = (int) file_get_contents($total_counter_file);
}
$total_count++;
file_put_contents($total_counter_file, $total_count);

// ── Online users ─────────────────────────────────────────────────
$timeout = 300;
$now = time();
$new_onlines = [];
$found = false;

if (file_exists($online_file)) {
    $onlines = file($online_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($onlines as $line) {
        [$online_ip, $last_time] = explode('|', $line);
        if ($now - $last_time < $timeout) {
            if ($online_ip == $ip) {
                if (!$found) {
                    $new_onlines[] = "$ip|$now";
                    $found = true;
                }
            } else {
                $new_onlines[] = "$online_ip|$last_time";
            }
        }
    }
}
if (!$found) {
    $new_onlines[] = "$ip|$now";
}
file_put_contents($online_file, implode("\n", $new_onlines));
$online_count = count($new_onlines);

// ── Site running days ────────────────────────────────────────────
$site_days = '';
if (!empty($config['site_start_date'])) {
    $site_days = '1';
    $start = strtotime($config['site_start_date']);
    $now = strtotime(date('Y-m-d'));
    if ($start && $now >= $start) {
        $site_days = floor(($now - $start) / 86400) + 1;
    }
}
