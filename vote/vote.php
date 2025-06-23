<?php
header('Content-Type: application/json');

$vote_file = __DIR__ . '/statistics/vote.json';
$ip_file = __DIR__ . '/statistics/vote_ip.json';

$option = $_POST['option'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];
$limit_seconds = 600; // 10分钟
$now = time();

// 读取已投票IP（关联数组：ip => 最后投票时间）
$ip_list = [];
if (file_exists($ip_file)) {
    $ip_list = json_decode(file_get_contents($ip_file), true);
    if (!is_array($ip_list)) $ip_list = [];
}

// 检查是否已投票且未超时
if (isset($ip_list[$ip]) && ($now - $ip_list[$ip]) < $limit_seconds) {
    $left = $limit_seconds - ($now - $ip_list[$ip]);
    echo json_encode(['status' => 'error', 'msg' => "您已投票，请{$left}秒后再试！"]);
    exit;
}

// 读取投票数据
$data = [];
if (file_exists($vote_file)) {
    $data = json_decode(file_get_contents($vote_file), true);
    if (!is_array($data)) $data = [];
}

// 处理投票
if ($option && isset($data[$option])) {
    $data[$option]++;
    file_put_contents($vote_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $ip_list[$ip] = $now; // 记录本次投票时间
    file_put_contents($ip_file, json_encode($ip_list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'ok', 'msg' => '投票成功！', 'data' => $data]);
} else {
    echo json_encode(['status' => 'error', 'msg' => '参数错误']);
}