# Plugin Load Profiler (Accurate)

A lightweight WordPress plugin for profiling admin-side plugin load times and key hook timings, designed for diagnostic use on live sites.

## Description

This plugin tracks the performance of all active plugins on the admin side of a WordPress site. It records:

- The load time of each individual plugin.
- The total time spent loading all plugins.
- The time taken to reach major WordPress admin hooks (`admin_init`, `current_screen`, and `admin_footer`).
- Total page load time from start to shutdown.

To reduce unnecessary log volume, the plugin only logs full reports if the total load time exceeds **5 seconds**. If the load time is under this threshold, a brief summary line is added to the log with the request URI and measured time.

## Logging Format

When a request exceeds the 5-second threshold:

```
--------------------------------------------------
Plugin Load Report - YYYY-MM-DD HH:MM:SS
Request URI: /wp-admin/...

plugin-folder/plugin-file.php             0.123456s
...

Total plugin load time:                   3.123456s
Post-plugin render time:                  2.456789s
Full page load time:                      5.580245s

Time to admin_init:                       0.743251s
Time to current_screen:                   0.203917s
Time to admin_footer:                     1.095324s
--------------------------------------------------
```

For requests under 5 seconds:

```
[YYYY-MM-DD HH:MM:SS] /wp-admin/... - Load time: 4.682943s. Full logging disabled.
```

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Usage Notes

- This plugin runs **admin-side only**.
- It will **not** run on:
  - AJAX requests
  - REST API requests
  - WP Cron
  - WP-CLI
- Output is written to:  
  `wp-content/plugin-load-times.log`

## Installation

1. Upload the plugin file to `wp-content/mu-plugins/` for must-use status, or install as a standard plugin and activate it.
2. Use your WordPress admin dashboard as normal.
3. Review the `plugin-load-times.log` file to analyse load performance.
