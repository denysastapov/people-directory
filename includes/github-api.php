<?php

if (! defined('ABSPATH')) {
  exit;
}

function pd_get_github_data($username)
{
  $transient_key = 'pd_gh_' . $username;
  $data = get_transient($transient_key);
  if (false !== $data) {
    return $data;
  }

  $response = wp_remote_get(
    "https://api.github.com/users/{$username}",
    [
      'headers' => [
        'User-Agent' => 'WordPress People Directory',
      ],
      'timeout' => 10,
    ]
  );

  if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
    return null;
  }

  $body = wp_remote_retrieve_body($response);
  $data = json_decode($body, true);
  if (! is_array($data)) {
    return null;
  }

  set_transient($transient_key, $data, HOUR_IN_SECONDS);
  return $data;
}

function pd_get_github_repos($username, $count = 5)
{
  $transient_key = 'pd_gh_repos_' . $username;
  $repos = get_transient($transient_key);
  if (false !== $repos) {
    return $repos;
  }

  $url = "https://api.github.com/users/{$username}/repos?per_page={$count}&sort=updated";
  $response = wp_remote_get(
    $url,
    [
      'headers' => [
        'User-Agent' => 'WordPress People Directory',
      ],
      'timeout' => 10,
    ]
  );

  if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
    return [];
  }

  $data = json_decode(wp_remote_retrieve_body($response), true);
  if (! is_array($data)) {
    return [];
  }

  set_transient($transient_key, $data, HOUR_IN_SECONDS);
  return $data;
}
