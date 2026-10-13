<?php
/**
 * AJAX Endpoint for Real-time Traffic & Analytics
 * Fetches dynamic chart data (Today 24H, 7D, 14D, 30D),
 * active visitors, traffic acquisition channels, and device breakdown.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $action = trim($_GET['action'] ?? ($_POST['action'] ?? 'get_all_stats'));
    $db = getDBConnection();

    if ($action === 'get_chart_data') {
        $period = trim($_GET['period'] ?? '30');
        if ($period === 'today' || $period === '24h') {
            $data = get_hourly_traffic_chart_data();
        } else {
            $days = in_array((int)$period, [7, 14, 30]) ? (int)$period : 30;
            $data = get_daily_traffic_chart_data($days);
        }

        echo json_encode([
            'status' => 'success',
            'period' => $period,
            'data' => $data
        ]);
        exit;
    }

    // Default: get_all_stats for seamless polling and live dashboard sync
    $chartToday = get_hourly_traffic_chart_data();
    $chart7d    = get_daily_traffic_chart_data(7);
    $chart14d   = get_daily_traffic_chart_data(14);
    $chart30d   = get_daily_traffic_chart_data(30);

    $activeNow = get_real_active_visitors_count();
    $activePages = get_real_active_pages(6);
    $todayVisitors = (int)$db->query("SELECT COUNT(*) FROM active_visitors WHERE DATE(last_activity) = CURDATE()")->fetchColumn();
    $totalUniqueVisitors = (int)$db->query("SELECT COUNT(DISTINCT ip_address) FROM active_visitors")->fetchColumn();

    $trafficChannels = get_traffic_acquisition_stats();
    $deviceBreakdown = get_device_breakdown_stats();

    echo json_encode([
        'status' => 'success',
        'timestamp' => time(),
        'charts' => [
            'today' => $chartToday,
            '7' => $chart7d,
            '14' => $chart14d,
            '30' => $chart30d
        ],
        'realtime' => [
            'active_now' => $activeNow,
            'today_visitors' => $todayVisitors,
            'total_unique' => $totalUniqueVisitors,
            'active_pages' => $activePages
        ],
        'traffic_channels' => $trafficChannels,
        'device_breakdown' => $deviceBreakdown
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
