<?php
/*
Plugin Name: People Directory
Description: Phantom profiles from CSV + GitHub API + Contributions Chart
Version: 0.2
Author: Denys Astapov
*/

if (! defined('ABSPATH')) {
  exit;
}

if (! defined('PD_PLUGIN_URL')) {
  define('PD_PLUGIN_URL', plugin_dir_url(__FILE__));
}

require_once __DIR__ . '/includes/github-api.php';
require_once __DIR__ . '/includes/contributions.php';

function pd_register_rewrites()
{
  add_rewrite_rule(
    '^people/([^/]+)/?$',
    'index.php?people_profile=$matches[1]',
    'top'
  );
  add_rewrite_tag('%people_profile%', '([^&]+)');

  add_rewrite_rule(
    '^people-sitemap\.xml$',
    'index.php?pd_sitemap=1',
    'top'
  );
  add_rewrite_tag('%pd_sitemap%', '([01])');
}
add_action('init', 'pd_register_rewrites');

function pd_activate_plugin()
{
  pd_register_rewrites();
  flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'pd_activate_plugin');

function pd_deactivate_plugin()
{
  flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'pd_deactivate_plugin');

/**
 *
 * @return array [ slug => [ 'name'=>…, 'slug'=>…, 'github_username'=>… ], … ]
 */
function pd_load_profiles()
{
  $file = plugin_dir_path(__FILE__) . 'profiles.csv';
  if (! file_exists($file)) {
    return [];
  }
  $rows   = array_map('str_getcsv', file($file));
  $header = array_shift($rows);
  $data   = [];
  foreach ($rows as $row) {
    $item = array_combine($header, $row);
    $data[$item['slug']] = $item;
  }
  return $data;
}

function pd_handle_profile()
{
  $slug = get_query_var('people_profile');
  if (! $slug) {
    return;
  }

  $profiles = pd_load_profiles();
  if (! isset($profiles[$slug])) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    include get_query_template('404');
    exit;
  }

  $profile = $profiles[$slug];

  $github_data         = pd_get_github_data($profile['github_username']);
  $profile['github']   = $github_data ?: [];

  $profile['location'] = ! empty($github_data['location'])
    ? $github_data['location']
    : ($profile['location'] ?? '');

  $blog = trim($github_data['blog'] ?? '');
  if ($blog && stripos($blog, 'linkedin.com/') !== false) {
    $parsed = wp_parse_url($blog);
    $path   = trim($parsed['path'] ?? '', '/');
    $profile['linkedin'] = $path ? 'in/' . $path : '';
  } else {
    $profile['linkedin'] = $profile['linkedin'] ?? '';
  }

  $profile['repos']    = pd_get_github_repos($profile['github_username'], 6) ?: [];
  $profile['contrib']  = pd_get_contributions_by_month($profile['github_username']) ?: [];

  set_query_var('pd_profile', $profile);
  include plugin_dir_path(__FILE__) . 'templates/profile.php';
  exit;
}
add_action('template_redirect', 'pd_handle_profile');

function pd_output_sitemap()
{
  if (! intval(get_query_var('pd_sitemap'))) {
    return;
  }

  header('Content-Type: application/xml; charset=utf-8');
  echo '<?xml version="1.0" encoding="UTF-8"?>';
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  $profiles = pd_load_profiles();
  $base     = home_url('/people/');

  foreach ($profiles as $slug => $data) {
    $loc     = esc_url($base . $slug . '/');
    $lastmod = date('Y-m-d');
    echo "<url>\n";
    echo "  <loc>{$loc}</loc>\n";
    echo "  <lastmod>{$lastmod}</lastmod>\n";
    echo "  <changefreq>weekly</changefreq>\n";
    echo "  <priority>0.7</priority>\n";
    echo "</url>\n";
  }

  echo '</urlset>';
  exit;
}
add_action('template_redirect', 'pd_output_sitemap');

function pd_add_sitemap_to_robots($output, $public)
{
  if ($public) {
    $output .= "\nSitemap: " . home_url('/people-sitemap.xml') . "\n";
  }
  return $output;
}
add_filter('robots_txt', 'pd_add_sitemap_to_robots', 10, 2);
