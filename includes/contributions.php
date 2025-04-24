<?php

if (! defined('ABSPATH')) {
  exit;
}

/**
 *
 * @param string $username GitHub username
 * @return array [['month'=>'YYYY-MM','count'=>int], ...]
 */
function pd_get_contributions_by_month($username)
{
  $transient = "pd_contrib_{$username}";
  if (false !== ($data = get_transient($transient))) {
    return $data;
  }

  $query = <<<'GQL'
query($login:String!) {
  user(login:$login) {
    contributionsCollection {
      contributionCalendar {
        weeks {
          contributionDays {
            date
            contributionCount
          }
        }
      }
    }
  }
}
GQL;

  $response = wp_remote_post('https://api.github.com/graphql', [
    'headers' => [
      'Content-Type'  => 'application/json',
      'User-Agent'    => 'WordPress People Directory',
      'Authorization' => 'Bearer ' . GITHUB_TOKEN,
    ],
    'body'    => wp_json_encode([
      'query'     => $query,
      'variables' => ['login' => $username],
    ]),
    'timeout' => 15,
  ]);

  if (is_wp_error($response)) {
    return [];
  }

  $json = json_decode(wp_remote_retrieve_body($response), true);
  $weeks = $json['data']['user']['contributionsCollection']['contributionCalendar']['weeks'] ?? [];
  if (! $weeks) {
    return [];
  }

  $months = [];
  foreach ($weeks as $week) {
    foreach ($week['contributionDays'] as $day) {
      $m = substr($day['date'], 0, 7);
      $months[$m] = ($months[$m] ?? 0) + intval($day['contributionCount']);
    }
  }

  ksort($months);
  $result = [];
  foreach ($months as $month => $count) {
    $result[] = ['month' => $month, 'count' => $count];
  }

  set_transient($transient, $result, DAY_IN_SECONDS);
  return $result;
}
