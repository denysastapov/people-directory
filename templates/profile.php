<?php
$profile = get_query_var('pd_profile');
$gh      = $profile['github'] ?? [];
$repos   = $profile['repos'] ?? [];
$contrib = $profile['contrib'] ?? [];

echo '<link rel="stylesheet" href="' . esc_url(PD_PLUGIN_URL . 'css/style.css') . '">';
?>

<body class="pd-profile-page">

  <div class="pd-profile-header">
    <div class="pd-avatar">
      <img
        src="<?php echo esc_url($gh['avatar_url'] ?? ''); ?>"
        alt="<?php echo esc_attr($profile['name']); ?>">
    </div>
    <div class="pd-info">
      <h1><?php echo esc_html($profile['name']); ?></h1>
      <div class="pd-meta">
        <p>Bio: <?php echo esc_html($gh['bio'] ?? '—'); ?></p>

        <?php if (! empty($profile['location'])) : ?>
          <p>Location: <?php echo esc_html($profile['location']); ?></p>
        <?php endif; ?>

        <?php if (! empty($profile['linkedin'])) : ?>
          <p>
            LinkedIn:
            <a
              href="https://www.linkedin.com/<?php echo esc_attr($profile['linkedin']); ?>"
              target="_blank"><?php echo esc_html($profile['linkedin']); ?></a>
          </p>
        <?php endif; ?>

        <p>Repos: <?php echo esc_html($gh['public_repos'] ?? '—'); ?></p>
        <p>Followers: <?php echo esc_html($gh['followers'] ?? '—'); ?></p>
      </div>
      <a
        class="pd-github-button"
        href="https://github.com/<?php echo esc_attr($profile['github_username']); ?>"
        target="_blank">View on GitHub</a>
    </div>
  </div>

  <?php if ($repos) : ?>
    <div class="pd-repos-section">
      <h2>Latest Repositories</h2>
      <div class="pd-repos-grid">
        <?php foreach ($repos as $repo) : ?>
          <div class="pd-repo-card">
            <h3>
              <a href="<?php echo esc_url($repo['html_url']); ?>" target="_blank">
                <?php echo esc_html($repo['name']); ?>
              </a>
            </h3>
            <p><?php echo esc_html($repo['description'] ?: 'No description.'); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (! empty($contrib)) : ?>
    <div class="pd-repos-section">
      <h2>Monthly Contributions</h2>
      <canvas id="pd-contrib-chart" width="800" height="200" style="max-width:100%;"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      window.pdContribData = {
        counts: <?php echo wp_json_encode(array_column($contrib, 'count')); ?>,
        months: <?php echo wp_json_encode(array_column($contrib, 'month')); ?>
      };
    </script>

    <script src="<?php echo esc_url(PD_PLUGIN_URL . 'js/contrib-chart.js'); ?>"></script>
  <?php endif; ?>

</body>