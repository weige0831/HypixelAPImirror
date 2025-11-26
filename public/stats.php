<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/HypixelKeyManager.php';
require_once __DIR__ . '/../src/CacheManager.php';
require_once __DIR__ . '/../src/HypixelAPI.php';

// 如果请求包含 format=html 参数，返回 HTML 页面
if (isset($_GET['format']) && $_GET['format'] === 'html') {
    header('Content-Type: text/html; charset=utf-8');
} else {
    header('Content-Type: application/json');
}

try {
    $api = new HypixelAPI();
    $stats = $api->getRequestStats();
    
    if (isset($_GET['format']) && $_GET['format'] === 'html') {
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Hypixel Mirror API 统计</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <meta http-equiv="refresh" content="60"> <!-- 每60秒自动刷新 -->
        </head>
        <body class="bg-light">
            <div class="container py-5">
                <h1 class="mb-4">Hypixel Mirror API 统计</h1>
                <p class="text-muted">最后更新时间: <?php echo $stats['timestamp']; ?></p>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">API Keys 状态</h5>
                            </div>
                            <div class="card-body">
                                <p>总数: <?php echo $stats['api_keys']['total']; ?></p>
                                <p>可用: <?php echo $stats['api_keys']['valid']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">今日统计 (<?php echo $stats['date']; ?>)</h5>
                            </div>
                            <div class="card-body">
                                <p>API 请求: <?php echo $stats['today_stats']['api_requests']; ?></p>
                                <p>缓存命中: <?php echo $stats['today_stats']['cache_hits']; ?></p>
                                <p>成功请求: <?php echo $stats['today_stats']['successful_requests']; ?></p>
                                <p>失败请求: <?php echo $stats['today_stats']['failed_requests']; ?></p>
                                <p>独立玩家: <?php echo $stats['today_stats']['unique_players']; ?></p>
                                <p>总请求数: <?php echo $stats['today_stats']['total_requests']; ?></p>
                                <p>最后更新: <?php echo $stats['today_stats']['last_updated'] ?: '无'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">系统状态</h5>
                            </div>
                            <div class="card-body">
                                <p>最后 API 请求时间: <?php echo $stats['last_request'] ?: '无'; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">缓存统计</h5>
                            </div>
                            <div class="card-body">
                                <p>总缓存条目: <?php echo $stats['cache_stats']['total_entries']; ?></p>
                                <p>有效缓存: <?php echo $stats['cache_stats']['valid_entries']; ?></p>
                                <p>过期缓存: <?php echo $stats['cache_stats']['expired_entries']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">API Keys 详情</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>所有者</th>
                                        <th>今日请求</th>
                                        <th>总请求数</th>
                                        <th>最后请求</th>
                                        <th>状态</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['keys_detail'] as $key): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($key['key']); ?></td>
                                        <td><?php echo htmlspecialchars($key['owner'] ?: '未知'); ?></td>
                                        <td><?php echo $key['requests_today']; ?></td>
                                        <td><?php echo $key['total_requests']; ?></td>
                                        <td><?php echo $key['last_request'] ?: '无'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $key['is_valid'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $key['is_valid'] ? '可用' : '不可用'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <footer class="text-center text-muted py-3">
                <small>页面每60秒自动刷新</small>
            </footer>
        </body>
        </html>
        <?php
    } else {
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    if (isset($_GET['format']) && $_GET['format'] === 'html') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    } else {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} 