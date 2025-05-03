<?php

declare(strict_types=1);
/**
 * Plugin Name: People Directory
 * Description: Phantom profiles from CSV + GitHub API + Contributions Chart
 * Version: 0.2
 * Author: Denys Astapov
 */

if (! defined('ABSPATH')) {
  exit;
}

if (! function_exists('plugin_dir_url')) {
  require_once ABSPATH . WPINC . '/plugin.php';
}

if (! defined('PD_PLUGIN_URL')) {
  define('PD_PLUGIN_URL', \plugin_dir_url(__FILE__));
}

require_once __DIR__ . '/includes/github-api.php';
require_once __DIR__ . '/includes/contributions.php';

add_filter('query_vars', function (array $vars): array {
  $vars[] = 'people_profile';
  $vars[] = 'pd_sitemap';
  return $vars;
});


add_action('init', 'pd_register_rewrites');
function pd_register_rewrites(): void
{
  add_rewrite_rule(
    '^people/([^/]+)/?$',
    'index.php?people_profile=$matches[1]',
    'top'
  );
  add_rewrite_rule(
    '^people-sitemap\.xml$',
    'index.php?pd_sitemap=1',
    'top'
  );
}

register_activation_hook(__FILE__, 'pd_activate_plugin');
function pd_activate_plugin(): void
{
  pd_register_rewrites();
  flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'pd_deactivate_plugin');
function pd_deactivate_plugin(): void
{
  flush_rewrite_rules();
}

/**
 * Load profiles from CSV file.
 *
 * @return array<string, array<string, string>>
 */
function pd_load_profiles(): array
{
  $file = plugin_dir_path(__FILE__) . 'profiles.csv';
  if (! file_exists($file)) {
    return [];
  }
  $rows   = array_map('str_getcsv', file($file));
  $header = array_shift($rows);
  $data   = [];
  foreach ($rows as $row) {
    // Skip rows with mismatched columns
    if (count($header) !== count($row)) {
      continue;
    }
    $item = array_combine($header, $row);
    if (false === $item) {
      continue;
    }
    $slug = (string) $item['slug'];
    $data[$slug] = $item;
  }
  return $data;
}

add_action('template_redirect', 'pd_handle_profile');
function pd_handle_profile(): void
{
  $slug = get_query_var('people_profile');
  if (empty($slug)) {
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

  $githubData       = pd_get_github_data($profile['github_username']);
  $profile['github'] = is_array($githubData) ? $githubData : [];

  if (empty($profile['location']) && ! empty($profile['github']['location'])) {
    $profile['location'] = $profile['github']['location'];
  }

  $blog = trim((string) ($profile['github']['blog'] ?? ''));
  if ($blog && stripos($blog, 'linkedin.com/') !== false) {
    $parsed = wp_parse_url($blog);
    $path   = trim((string) ($parsed['path'] ?? ''), '/');
    $profile['linkedin'] = $path ? 'in/' . $path : '';
  } else {
    $profile['linkedin'] = $profile['linkedin'] ?? '';
  }

  $profile['repos']   = pd_get_github_repos($profile['github_username'], 6) ?: [];
  $profile['contrib'] = pd_get_contributions_by_month($profile['github_username']) ?: [];

  set_query_var('pd_profile', $profile);

  $template = plugin_dir_path(__FILE__) . 'templates/profile.php';
  if (file_exists($template)) {
    include $template;
  }
  exit;
}

add_action('template_redirect', 'pd_output_sitemap');
function pd_output_sitemap(): void
{
  if (0 === intval(get_query_var('pd_sitemap'))) {
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

add_filter('robots_txt', 'pd_add_sitemap_to_robots', 10, 2);
/**
 * Append People Directory sitemap to robots.txt output.
 *
 * @param string $output
 * @param bool   $public
 * @return string
 */
function pd_add_sitemap_to_robots(string $output, bool $public): string
{
  if ($public) {
    $output .= "\nSitemap: " . home_url('/people-sitemap.xml') . "\n";
  }
  return $output;
}
