<?php

// 语言代码自动检测
$lang_code = 'zh-cn'; // 默认

// 1. 优先用cookie中的country_code
if (isset($_COOKIE['country_code'])) {
    $country_code = strtolower($_COOKIE['country_code']);
    if ($country_code === 'us') {
        $lang_code = 'en';
    } elseif ($country_code === 'tw' || $country_code === 'hk' || $country_code === 'mo') {
        $lang_code = 'zh-tw';
    } elseif ($country_code === 'cn') {
        $lang_code = 'zh-cn';
    }
} else {
    // 2. 否则用浏览器语言
    $accept_lang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if (strpos($accept_lang, 'en') === 0) {
        $lang_code = 'en';
    } elseif (strpos($accept_lang, 'zh-tw') === 0 || strpos($accept_lang, 'zh-hk') === 0) {
        $lang_code = 'zh-tw';
    }
}

// 读取对应语言文件
$lang_file = __DIR__ . "/languages/{$lang_code}.json";
if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/zh-cn.json"; // 回退
}
$lang = json_decode(@file_get_contents($lang_file), true) ?? [];

?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_code); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Test</title>
    </head>

        <body>
            <h1><?php echo htmlspecialchars($lang['title'] ?? ''); ?>123</h1>
        </body>
</html>