<?php
/**
 * Plugin Name: Plugin Load Profiler
 * Description: Logs plugin load times and admin hook timings (admin only, logs only if total load > 5s).
 */

if (
    !is_admin() ||
    defined('DOING_AJAX') ||
    defined('REST_REQUEST') ||
    defined('DOING_CRON') ||
    (defined('WP_CLI') && WP_CLI)
) {
    return;
}

$GLOBALS['timestart'] = microtime(true);
$GLOBALS['plugin_timings'] = [];
$GLOBALS['plugin_loaded_at'] = [];

add_action('muplugins_loaded', function () {
    $GLOBALS['plugin_load_start'] = microtime(true);
});

add_action('plugin_loaded', function ($plugin) {
    $now = microtime(true);
    if (!isset($GLOBALS['plugin_loaded_at'][$plugin])) {
        $load_time = round($now - $GLOBALS['plugin_load_start'], 6);
        $GLOBALS['plugin_timings'][] = [
            'plugin' => plugin_basename($plugin),
            'time'   => $load_time
        ];
        $GLOBALS['plugin_loaded_at'][$plugin] = true;
        $GLOBALS['plugin_load_start'] = $now;
    }
});

add_action('init', function () {
    $GLOBALS['post_plugin_init_time'] = microtime(true);
});

add_action('admin_init', function () {
    $GLOBALS['hook_time_admin_init'] = microtime(true);
});

add_action('current_screen', function () {
    $GLOBALS['hook_time_current_screen'] = microtime(true);
});

add_action('admin_footer', function () {
    $GLOBALS['hook_time_admin_footer'] = microtime(true);
});

add_action('shutdown', function () {
    $log_file = WP_CONTENT_DIR . '/plugin-load-times.log';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '[unknown]';
    $now = date('Y-m-d H:i:s');
    $total_runtime = round(microtime(true) - $GLOBALS['timestart'], 6);

    if ($total_runtime < 5.0) {
        $line = "[$now] $request_uri - Load time: {$total_runtime}s. Full logging disabled.\n";
        file_put_contents($log_file, $line, FILE_APPEND);
        return;
    }

    $output = [];
    $output[] = str_repeat('-', 50);
    $output[] = "Plugin Load Report - $now";
    $output[] = "Request URI: $request_uri";
    $output[] = '';

    usort($GLOBALS['plugin_timings'], fn($a, $b) => $b['time'] <=> $a['time']);
    foreach ($GLOBALS['plugin_timings'] as $entry) {
        $output[] = str_pad($entry['plugin'], 50) . str_pad($entry['time'] . 's', 10, ' ', STR_PAD_LEFT);
    }

    $output[] = '';

    $total_plugin_load = round(($GLOBALS['post_plugin_init_time'] ?? microtime(true)) - $GLOBALS['timestart'], 6);
    $post_plugin_load  = round($total_runtime - $total_plugin_load, 6);

    $output[] = str_pad('Total plugin load time:', 36) . str_pad($total_plugin_load . 's', 14, ' ', STR_PAD_LEFT);
    $output[] = str_pad('Post-plugin render time:', 36) . str_pad($post_plugin_load . 's', 14, ' ', STR_PAD_LEFT);
    $output[] = str_pad('Full page load time:', 36) . str_pad($total_runtime . 's', 14, ' ', STR_PAD_LEFT);
    $output[] = '';

    $hook_times = [
        'Time to admin_init:'     => $GLOBALS['hook_time_admin_init']     ?? null,
        'Time to current_screen:' => $GLOBALS['hook_time_current_screen'] ?? null,
        'Time to admin_footer:'   => $GLOBALS['hook_time_admin_footer']   ?? null,
    ];

    $last = $GLOBALS['post_plugin_init_time'] ?? $GLOBALS['timestart'];

    foreach ($hook_times as $label => $time) {
        if ($time !== null) {
            $diff = round($time - $last, 6);
            $output[] = str_pad($label, 36) . str_pad($diff . 's', 14, ' ', STR_PAD_LEFT);
            $last = $time;
        } else {
            $output[] = str_pad($label, 36) . str_pad('[not reached]', 14, ' ', STR_PAD_LEFT);
        }
    }

    $output[] = str_repeat('-', 50) . "\n";
    file_put_contents($log_file, implode(PHP_EOL, $output), FILE_APPEND);
});
