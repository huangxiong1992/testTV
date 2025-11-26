<?php
// https://jzb123.huajiaedu.com/tvs
error_reporting(0);

// 频道ID映射表
$n = [
    "cctv1" => 578, //CCTV1综合
    "cctv2" => 579, //CCTV2财经
    "cctv3" => 580, //CCTV3综艺
    "cctv4" => 581, //CCTV4中文国际
    "cctv4a" => 595, //CCTV4中文国际-美洲
    "cctv4o" => 596, //CCTV4中文国际-欧洲
    "cctv5" => 582, //CCTV5体育
    "cctv5p" => 583, //CCTV5+体育赛事
    "cctv6" => 584, //CCTV6电影
    "cctv7"=> 585, //CCTV7国防军事
    "cctv8" => 586, //CCTV8电视剧
    "cctv9" => 587, //CCTV9纪录
    "cctv10" => 588, //CCTV10科教
    "cctv11" => 589, //CCTV11戏曲
    "cctv12" => 590, //CCTV12社会与法
    "cctv13" => 591, //CCTV13新闻
    "cctv14" => 592, //CCTV14少儿
    "cctv15" => 593, //CCTV15音乐
    "cctv17" => 594, //CCTV17农业农村
    "bjws" => 608, //北京卫视
    "dfws" => 597, //东方卫视
    "tjws" => 611, //天津卫视
    "cqws" => 607, //重庆卫视
    "hljws" => 621, //黑龙江卫视
    "jlws" => 601, //吉林卫视
    "lnws" => 620, //辽宁卫视
    "gsws" => 622, //甘肃卫视
    "qhws" => 605, //青海卫视
    "sxws" => 603, //陕西卫视
    "hbws" => 615, //河北卫视
    "sdws" => 613, //山东卫视
    "ahws" => 612, //安徽卫视
    "hnws" => 616, //河南卫视
    "hubws" => 604, //湖北卫视
    "hunws" => 609, //湖南卫视
    "jxws" => 602, //江西卫视
    "jsws" => 599, //江苏卫视
    "zjws" => 617, //浙江卫视
    "dnws" => 618, //东南卫视
    "gdws" => 598, //广东卫视
    "szws" => 606, //深圳卫视
    "gxws" => 614, //广西卫视
    "gzws" => 619, //贵州卫视
    "scws" => 610, //四川卫视   
    "xjws" => 623, //新疆卫视
    "btws" => 624, //兵团卫视
    "hinws" => 600, //海南卫视
];

// 获取请求参数
$id = $_GET['id'] ?? 'cctv1';
$fmt = $_GET['fmt'] ?? 'hls'; // 支持 hls/flv 格式
$channelId = $n[$id] ?? 0;

// 检查频道是否存在
if ($channelId === 0) {
    header("HTTP/1.1 404 Not Found");
    echo "频道不存在或暂时不可用";
    exit;
}

// 缓存配置 - 缓存整个API响应
$cacheDir = './api_cache/'; // API响应缓存目录
$cacheFile = $cacheDir . 'iptv_list_cache.json'; // 缓存文件名
$cacheExpire = 120; // 缓存有效期：30分钟（单位：秒）

// 尝试从缓存获取API数据
$data = null;
if (file_exists($cacheFile)) {
    // 检查缓存是否过期
    $fileTime = filemtime($cacheFile);
    if (time() - $fileTime < $cacheExpire) {
        // 读取缓存数据
        $cacheContent = file_get_contents($cacheFile);
        $data = json_decode($cacheContent, true);
    }
}

// 缓存不存在或已过期，重新获取API数据
if ($data === null) {
    $apiUrl = "https://jzb123.huajiaedu.com/prod-api/iptv/getIptvList?liveType&deviceType=1";
    
    // 发起远程请求，带超时控制
    $options = [
        'http' => [
            'timeout' => 10, // 超时10秒
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36"
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        
        // 保存到缓存
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheFile, $response);
    } else {
        // API请求失败，如果有旧缓存则使用
        if (file_exists($cacheFile)) {
            $cacheContent = file_get_contents($cacheFile);
            $data = json_decode($cacheContent, true);
        }
    }
}

// 查找匹配的频道
$streamUrl = null;
if (isset($data['list']) && is_array($data['list'])) {
    foreach ($data['list'] as $channel) {
        if (isset($channel['id']) && $channel['id'] == $channelId) {
            $streamUrl = $channel['play_source_url'] ?? '';
            break;
        }
    }
}

// 处理URL格式
if (!empty($streamUrl)) {
    $streamUrl = preg_replace('/^https/', 'http', $streamUrl); // HTTPS转HTTP
    
    if ($fmt === 'flv') {
        $streamUrl = preg_replace('/\.m3u8$/', '.flv', $streamUrl);
    }
    
    // 输出流地址
    header("Location: " . $streamUrl);
    exit;
} else {
    // 频道未找到的处理
    header("HTTP/1.1 404 Not Found");
    echo "频道不存在或暂时不可用";
    exit;
}
?>
