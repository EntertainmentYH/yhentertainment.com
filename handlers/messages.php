<?php
//POST 用户留言逻辑
$submit_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $messages_file = $statistics_dir . '/messages.json';
    if (!file_exists($messages_file)) @file_put_contents($messages_file, json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // load messages
    $messages = [];
    $contents = @file_get_contents($messages_file);
    if ($contents !== false) {
        $decoded = json_decode($contents, true);
        if (is_array($decoded)) $messages = $decoded;
    }

    // CSRF token validation for all mutating actions
    $csrf_actions = ['submit_message', 'reply_message', 'edit_message', 'delete_message'];
    if (in_array($action, $csrf_actions, true)) {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'csrf_invalid'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $submit_result = ['ok' => false, 'error' => 'csrf_invalid'];
        }
    }

    // delete action: remove messages owned by this visitor (cookie token)
    if ($action === 'delete_message') {
        $owner_token = $_COOKIE['yh_owner_token'] ?? '';
        $delete_id = $_POST['delete_id'] ?? null;
        // remove only the message that matches both id and owner token
        $new = array_values(array_filter($messages, function($itm) use ($owner_token, $delete_id) {
            if (!isset($itm['id'])) return true;
            if ($delete_id !== null) {
                // keep items that are NOT the one to delete
                $msg_token = $itm['owner_token'] ?? '';
                $ip_match = isset($itm['ip']) && $itm['ip'] === ($_SERVER['REMOTE_ADDR'] ?? '');
                // Allow deletion by token match, or fall back to IP match for legacy messages without a token
                $is_owner = ($msg_token !== '' && hash_equals($msg_token, $owner_token)) || ($msg_token === '' && $ip_match);
                return !($itm['id'] === $delete_id && $is_owner);
            }
            // fallback: if no id provided, require token match (never mass-delete by IP alone)
            $msg_token = $itm['owner_token'] ?? '';
            return !($msg_token !== '' && hash_equals($msg_token, $owner_token));
        }));
        $ok = @file_put_contents($messages_file, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        if ($ok === false) {
            $submit_result = ['ok' => false, 'error' => 'delete_failed'];
        } else {
            // also remove any ip limiter entry so the IP is not blocked for an hour after delete
            if (file_exists($ip_limiter_file)) {
                $myip = $_SERVER['REMOTE_ADDR'] ?? '';
                $lim = json_decode(@file_get_contents($ip_limiter_file), true);
                if (is_array($lim) && isset($lim[$myip])) {
                    unset($lim[$myip]);
                    @file_put_contents($ip_limiter_file, json_encode($lim, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
                }
            }
            $anchor_in = $_POST['anchor'] ?? null;
            $submit_result = ['ok' => true, 'deleted' => true, 'anchor' => $anchor_in];
        }
    }

    // submit action: enforce per-IP 1 per hour and remove older same-IP messages when posting allowed
    if ($action === 'submit_message') {
        $title = trim($_POST['title'] ?? '');
        $search = trim($_POST['search'] ?? '');
        $user_id = trim($_POST['user_id'] ?? '');
        $myip = $_SERVER['REMOTE_ADDR'] ?? '';

        // ===== 数学验证码检测（HMAC签名，不依赖session持久化） =====
        $captcha_input = intval($_POST['captcha'] ?? 0);
        $captcha_hash = $_POST['captcha_hash'] ?? '';
        $expected_hash = hash_hmac('sha256', (string)$captcha_input, 'yh_captcha_secret_2025');
        if ($captcha_hash === '' || !hash_equals($captcha_hash, $expected_hash) || $captcha_input < 2) {
            // 重新生成验证码
            $_SESSION['captcha_a'] = rand(1, 20);
            $_SESSION['captcha_b'] = rand(1, 20);
            $_SESSION['captcha_answer'] = $_SESSION['captcha_a'] + $_SESSION['captcha_b'];
            $_SESSION['captcha_hash'] = hash_hmac('sha256', (string)$_SESSION['captcha_answer'], 'yh_captcha_secret_2025');
            $submit_result = ['ok' => false, 'error' => 'wrong_captcha'];
        }

        // missing fields check
        if (empty($submit_result) && ($search === '' || $user_id === '')) {
            $submit_result = ['ok' => false, 'error' => 'missing_fields'];
        }
        // continue if not blocked
        if (!empty($submit_result) && $submit_result['ok'] === false) {
            // blocked or error already set, skip persisting
        } else {
            if (mb_strlen($title) > 150) $title = mb_substr($title, 0, 150);
            if (mb_strlen($search) > 500) $search = mb_substr($search, 0, 500);
            if (mb_strlen($user_id) > 100) $user_id = mb_substr($user_id, 0, 100);

            // find latest message from this IP
            $last_ts = 0;
            foreach ($messages as $itm) {
                if (isset($itm['ip']) && $itm['ip'] === $myip && isset($itm['ts'])) {
                    $last_ts = max($last_ts, (int)$itm['ts']);
                }
            }
            $now = time();
            // allow disabling rate limit for testing via configuration.json -> "disable_rate_limit": true
            $rate_limit_seconds = !empty($config['disable_rate_limit']) ? 0 : 3600;
            if ($rate_limit_seconds > 0 && $last_ts > 0 && ($now - $last_ts) < $rate_limit_seconds) {
                $remain = $rate_limit_seconds - ($now - $last_ts);
                $submit_result = ['ok' => false, 'error' => 'rate_limited', 'retry_after' => $remain];
            } else {
                $search_clean = htmlspecialchars(strip_tags($search), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $user_id_clean = htmlspecialchars(strip_tags($user_id), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $title_clean = htmlspecialchars(strip_tags($title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $record = [
                    'title' => $title_clean !== '' ? $title_clean : '',
                    'id' => uniqid('', true),
                    'user_id' => $user_id_clean,
                    'search' => $search_clean,
                    'ip' => $myip,
                    'ts' => $now,
                    'replies' => [],
                    'owner_token' => $visitor_token ?? '',
                ];

                // append new message (no longer removes old ones from same IP)
                $messages[] = $record;
                $ok = @file_put_contents($messages_file, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
                if ($ok === false) {
                    $submit_result = ['ok' => false, 'error' => 'write_failed'];
                } else {
                    $submit_result = ['ok' => true, 'record' => $record];
                }
            }
        }
    }

    // ===== 回复留言 =====
    if ($action === 'reply_message' && empty($submit_result)) {
        $parent_id = trim($_POST['parent_id'] ?? '');
        $search = trim($_POST['search'] ?? '');
        $user_id = trim($_POST['user_id'] ?? '');
        $myip = $_SERVER['REMOTE_ADDR'] ?? '';

        // captcha check (HMAC-signed, doesn't depend on session persistence)
        $captcha_input = intval($_POST['captcha'] ?? 0);
        $captcha_hash = $_POST['captcha_hash'] ?? '';
        $expected_hash = hash_hmac('sha256', (string)$captcha_input, 'yh_captcha_secret_2025');
        if ($captcha_hash === '' || !hash_equals($captcha_hash, $expected_hash) || $captcha_input < 2) {
            $_SESSION['captcha_a'] = rand(1, 20);
            $_SESSION['captcha_b'] = rand(1, 20);
            $_SESSION['captcha_answer'] = $_SESSION['captcha_a'] + $_SESSION['captcha_b'];
            $_SESSION['captcha_hash'] = hash_hmac('sha256', (string)$_SESSION['captcha_answer'], 'yh_captcha_secret_2025');
            $submit_result = ['ok' => false, 'error' => 'wrong_captcha'];
        }

        // rate limit for replies (30-second cooldown between replies from same IP)
        if (empty($submit_result)) {
            $reply_last_ts = 0;
            foreach ($messages as $itm) {
                if (isset($itm['replies']) && is_array($itm['replies'])) {
                    foreach ($itm['replies'] as $r) {
                        if (isset($r['ip']) && $r['ip'] === $myip && isset($r['ts'])) {
                            $reply_last_ts = max($reply_last_ts, (int)$r['ts']);
                        }
                    }
                }
            }
            $reply_rate_limit = !empty($config['disable_rate_limit']) ? 0 : 30;
            if ($reply_rate_limit > 0 && $reply_last_ts > 0 && (time() - $reply_last_ts) < $reply_rate_limit) {
                $remain = $reply_rate_limit - (time() - $reply_last_ts);
                $submit_result = ['ok' => false, 'error' => 'rate_limited', 'retry_after' => $remain];
            }
        }

        if (empty($submit_result) && ($search === '' || $user_id === '')) {
            $submit_result = ['ok' => false, 'error' => 'missing_fields'];
        }

        if (empty($submit_result)) {
            // Find parent message
            $parent_idx = null;
            foreach ($messages as $idx => $msg) {
                if (isset($msg['id']) && $msg['id'] === $parent_id) {
                    $parent_idx = $idx;
                    break;
                }
            }

            if ($parent_idx === null) {
                $submit_result = ['ok' => false, 'error' => 'parent_not_found'];
            } else {
                if (empty($submit_result)) {
                    if (mb_strlen($search) > 500) $search = mb_substr($search, 0, 500);
                    if (mb_strlen($user_id) > 100) $user_id = mb_substr($user_id, 0, 100);

                    $search_clean = htmlspecialchars(strip_tags($search), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $user_id_clean = htmlspecialchars(strip_tags($user_id), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $reply = [
                        'id' => uniqid('reply_', true),
                        'user_id' => $user_id_clean,
                        'search' => $search_clean,
                        'ip' => $myip,
                        'ts' => time(),
                    ];

                    if (!isset($messages[$parent_idx]['replies'])) {
                        $messages[$parent_idx]['replies'] = [];
                    }
                    $messages[$parent_idx]['replies'][] = $reply;

                    $ok = @file_put_contents($messages_file, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
                    if ($ok === false) {
                        $submit_result = ['ok' => false, 'error' => 'write_failed'];
                    } else {
                        $submit_result = ['ok' => true, 'reply' => $reply, 'parent_id' => $parent_id];
                    }
                }
            }
        }
    }

    // ===== 编辑留言 =====
    if ($action === 'edit_message' && empty($submit_result)) {
        $message_id = trim($_POST['message_id'] ?? '');
        $new_title = trim($_POST['title'] ?? '');
        $new_search = trim($_POST['search'] ?? '');
        $myip = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($new_search === '') {
            $submit_result = ['ok' => false, 'error' => 'missing_fields'];
        }

        // rate limit for edits (10-second cooldown between edits from same IP)
        if (empty($submit_result)) {
            $edit_last_ts = 0;
            foreach ($messages as $itm) {
                if (isset($itm['ip']) && $itm['ip'] === $myip && isset($itm['edited_ts'])) {
                    $edit_last_ts = max($edit_last_ts, (int)$itm['edited_ts']);
                }
            }
            $edit_rate_limit = !empty($config['disable_rate_limit']) ? 0 : 10;
            if ($edit_rate_limit > 0 && $edit_last_ts > 0 && (time() - $edit_last_ts) < $edit_rate_limit) {
                $remain = $edit_rate_limit - (time() - $edit_last_ts);
                $submit_result = ['ok' => false, 'error' => 'rate_limited', 'retry_after' => $remain];
            }
        }

        if (empty($submit_result)) {
            $msg_idx = null;
            foreach ($messages as $idx => $msg) {
                if (isset($msg['id']) && $msg['id'] === $message_id) {
                    $msg_idx = $idx;
                    break;
                }
            }

            if ($msg_idx === null) {
                $submit_result = ['ok' => false, 'error' => 'message_not_found'];
            } else {
                // Check ownership: token match preferred, fall back to IP for legacy messages
                $msg_token = $messages[$msg_idx]['owner_token'] ?? '';
                $owner_token = $_COOKIE['yh_owner_token'] ?? '';
                $ip_match = isset($messages[$msg_idx]['ip']) && $messages[$msg_idx]['ip'] === ($_SERVER['REMOTE_ADDR'] ?? '');
                $is_owner = ($msg_token !== '' && hash_equals($msg_token, $owner_token))
                         || ($msg_token === '' && $ip_match);
                if (!$is_owner) {
                    $submit_result = ['ok' => false, 'error' => 'not_owner'];
                } else {
                // 5-minute edit window
                $edit_window = 300;
                $msg_ts = intval($messages[$msg_idx]['ts'] ?? 0);
                if (time() - $msg_ts > $edit_window) {
                    $submit_result = ['ok' => false, 'error' => 'edit_window_expired'];
                } else {
                    if (mb_strlen($new_title) > 150) $new_title = mb_substr($new_title, 0, 150);
                    if (mb_strlen($new_search) > 500) $new_search = mb_substr($new_search, 0, 500);

                    $messages[$msg_idx]['title'] = htmlspecialchars(strip_tags($new_title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $messages[$msg_idx]['search'] = htmlspecialchars(strip_tags($new_search), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $messages[$msg_idx]['edited'] = true;
                    $messages[$msg_idx]['edited_ts'] = time();

                    $ok = @file_put_contents($messages_file, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
                    if ($ok === false) {
                        $submit_result = ['ok' => false, 'error' => 'write_failed'];
                    } else {
                        $submit_result = ['ok' => true, 'edited' => true, 'message' => $messages[$msg_idx]];
                    }
                }
            }
        }
    }
    }

    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

    // On successful non-AJAX POST, use PRG: store flash and redirect to avoid resubmission on refresh
    if (!$is_ajax && !empty($submit_result['ok'])) {
        $_SESSION['flash_submit_result'] = $submit_result;
        // redirect to current URL (preserve query string)
        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . strtok($_SERVER['REQUEST_URI'], '?');
        // priority: explicit anchor returned by handler (delete or custom), then newly created record id
        $redirect = $base;
        if (!empty($submit_result['anchor'])) {
            $a = (string)$submit_result['anchor'];
            if (strpos($a, '#') !== 0) $a = '#' . ltrim($a, '#');
            $redirect = $base . $a;
        } elseif (!empty($submit_result['record']['id'])) {
            $anchor = '#msg-' . urlencode($submit_result['record']['id']);
            $redirect = $base . $anchor;
        } elseif (!empty($submit_result['parent_id'])) {
            $anchor = '#msg-' . urlencode($submit_result['parent_id']);
            $redirect = $base . $anchor;
        } elseif (!empty($submit_result['message']['id'])) {
            $anchor = '#msg-' . urlencode($submit_result['message']['id']);
            $redirect = $base . $anchor;
        }
        header('Location: ' . $redirect);
        exit;
    }

    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($submit_result, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// On GET, restore flash message if set
if (empty($submit_result) && !empty($_SESSION['flash_submit_result'])) {
    $submit_result = $_SESSION['flash_submit_result'];
    unset($_SESSION['flash_submit_result']);
}