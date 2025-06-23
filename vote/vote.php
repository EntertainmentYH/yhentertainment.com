<?php
// filepath: f:\Personal website\yhentertainment.com\vote.php
header('Content-Type: application/json');

$vote_file = __DIR__ . '/statistics/vote.json';
$ip_file = __DIR__ . '/statistics/vote_ip.json';

// 获取投票选项
$option = $_POST['option'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];

// 读取已投票IP
$ip_list = [];
if (file_exists($ip_file)) {
    $ip_list = json_decode(file_get_contents($ip_file), true);
    if (!is_array($ip_list))
        $ip_list = [];
}

// 检查是否已投票
if (in_array($ip, $ip_list)) {
    echo json_encode(['status' => 'error', 'msg' => '您已投票！']);
    exit;
}

// 读取投票数据
$data = [];
if (file_exists($vote_file)) {
    $data = json_decode(file_get_contents($vote_file), true);
    if (!is_array($data))
        $data = [];
}

// 处理投票
if ($option && isset($data[$option])) {
    $data[$option]++;
    file_put_contents($vote_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $ip_list[] = $ip;
    file_put_contents($ip_file, json_encode($ip_list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'ok', 'msg' => '投票成功！', 'data' => $data]);
} else {
    echo json_encode(['status' => 'error', 'msg' => '参数错误']);
}
?>