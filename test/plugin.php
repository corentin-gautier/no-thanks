<?php
// Checks of the plugin's PHP logic against minimal WordPress stubs: php test/plugin.php
define('ABSPATH', '/');
$GLOBALS['opt'] = []; $GLOBALS['actions'] = []; $GLOBALS['raw'] = true;
function add_action($h, $f, $p = 10) { $GLOBALS['actions'][$h][] = $f; }
function add_filter($h, $f, $p = 10) {}
function apply_filters($h, $v) { return $v; }
function __($s) { return $s; } function esc_html__($s) { return htmlspecialchars($s); } function esc_attr__($s) { return $s; }
function esc_html_e($s) { echo $s; } function esc_attr_e($s) { echo $s; }
function esc_html($s) { return htmlspecialchars($s); } function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_textarea($s) { return htmlspecialchars($s); } function esc_url($s) { return $s; }
function wp_kses($s) { return $s; } function wp_kses_post($s) { return $s; }
function sanitize_text_field($s) { return trim(strip_tags((string) $s)); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $s)); }
function sanitize_title($s) { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-'); }
function get_option($k, $d = []) { return $GLOBALS['opt'] ?: $d; }
function get_privacy_policy_url() { return '/privacy'; }
function current_user_can() { return $GLOBALS['raw']; }
function wp_json_encode($v) { return json_encode($v); }
function plugin_basename($f) { return 'no-thanks/no-thanks.php'; }
function load_plugin_textdomain() {} function register_setting() {} function settings_fields() {} function submit_button() {} function wp_editor() {}
function disabled($c) { if ($c) echo ' disabled'; }
function wp_add_privacy_policy_content($n, $c) { $GLOBALS['privacy'] = $c; }
$root = dirname(__DIR__);
require $root . '/no-thanks.php';
$failed = 0;
$check = function ($name, $ok) use (&$failed) { echo ($ok ? 'ok   ' : 'FAIL ') . $name . "\n"; $failed += $ok ? 0 : 1; };

// Old settings become one purpose
$GLOBALS['opt'] = ['head' => '<script src="https://cdn.matomo.cloud/x.js"></script>', 'body' => '', 'message' => 'Hi'];
$o = no_thanks_options();
$check('migration: one purpose', count($o['purposes']) === 1 && $o['purposes'][0]['id'] === 'audience' && $o['purposes'][0]['label'] === 'Audience measurement' && str_contains($o['purposes'][0]['head'], 'matomo'));
$check('migration: active', no_thanks_active());
ob_start(); no_thanks_print('head'); $out = ob_get_clean();
$check('print: template with the purpose id', str_contains($out, '<template data-no-thanks="audience">') && str_contains($out, 'matomo'));

// Saving: the existing one kept, a new one added, the empty one dropped
$in = ['purposes' => [
  ['id' => 'audience', 'label' => 'Audience measurement', 'description' => 'Matomo', 'head' => $o['purposes'][0]['head'], 'body' => ''],
  ['id' => '', 'label' => 'Vidéos YouTube', 'description' => '', 'head' => '', 'body' => ''],
  ['id' => '', 'label' => '', 'description' => '', 'head' => '', 'body' => ''],
], 'message' => 'Hi'];
$GLOBALS['opt'] = no_thanks_sanitize($in);
$o = no_thanks_options();
$check('save: two purposes, new id from the name', array_column($o['purposes'], 'id') === ['audience', 'vid-os-youtube']);
$check('save: old keys gone', !isset($GLOBALS['opt']['head']));

// Same name twice gets a distinct id
$in['purposes'][2] = ['id' => '', 'label' => 'Audience measurement', 'head' => '', 'body' => ''];
$ids = array_column(no_thanks_sanitize($in)['purposes'], 'id');
$check('save: duplicate name, distinct id', count(array_unique($ids)) === count($ids));

// Without unfiltered_html the scripts stay as they were
$GLOBALS['raw'] = false;
$in['purposes'][0]['head'] = '<script>evil()</script>';
$c = no_thanks_sanitize($in);
$check('save: scripts kept without unfiltered_html', str_contains($c['purposes'][0]['head'], 'matomo'));
$GLOBALS['raw'] = true;

// Emptying a block removes it
$in2 = $in; $in2['purposes'][1] = ['id' => 'x', 'label' => '', 'description' => '', 'head' => '', 'body' => '']; unset($in2['purposes'][2]);
$check('save: emptied block removed', count(no_thanks_sanitize($in2)['purposes']) === 1);

// Front config

function plugin_dir_path($f) { return dirname(__DIR__) . '/'; }
function plugins_url($p) { return '/' . $p; }
function wp_register_style() {} function wp_add_inline_style() {} function wp_enqueue_style() {} function wp_enqueue_script() {}
function wp_add_inline_script($h, $js) { $GLOBALS['inline'] = $js; }
foreach ($GLOBALS['actions']['wp_enqueue_scripts'] as $f) $f();
$cfg = json_decode(substr($GLOBALS['inline'], strlen('window.noThanksConfig = '), -1), true);
$check('front: purposes without scripts', count($cfg['purposes']) === 2 && !isset($cfg['purposes'][0]['head']) && $cfg['purposes'][0]['description'] === 'Matomo');
$check('front: texts', $cfg['texts']['choose'] === 'Choose' && $cfg['texts']['some'] === 'Some cookies allowed');

// Privacy text
foreach ($GLOBALS['actions']['admin_init'] as $f) $f();
$check('privacy: Matomo found under its purpose', str_contains($GLOBALS['privacy'], '<strong>Audience measurement</strong>: Matomo'));
$check('privacy: placeholder for the other purpose', str_contains($GLOBALS['privacy'], '[service]'));

// Settings page renders
ob_start(); no_thanks_settings_page(); $page = ob_get_clean();
$check('settings: one block per purpose plus a new one', substr_count($page, 'class="no-thanks-purpose') === 3);
$check('settings: template hint', str_contains($page, 'data-no-thanks=&quot;audience&quot;'));
exit($failed ? 1 : 0);
