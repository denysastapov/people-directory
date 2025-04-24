# People Directory

A lightweight WordPress plugin that generates “phantom” profile pages from a CSV, enriches them with GitHub data (REST & GraphQL), and exposes only those pages in a custom sitemap for programmatic SEO.

![screencapture-pd-local-people-robert-shaw-2025-04-24-12_55_34](https://github.com/user-attachments/assets/0dab44e9-092e-4ce0-9d65-02367a17f796)

## Features

- **Phantom pages**: virtual `/people/{slug}/` URLs, no real `post` or `page` entries.
- **CSV-driven**: load `profiles.csv` with `name,slug,github_username`.
- **GitHub enrichment**: bio, avatar, repo count, followers via REST API.
- **Monthly contributions chart**: uses GitHub GraphQL to fetch daily contributions, aggregates by month, and renders a bar chart via Chart.js.
- **Latest repos grid**: displays latest 6 repos in a 3×2 card layout.
- **Custom sitemap**: only `/people/…` URLs appear in `/people-sitemap.xml`; discoverable by Google without on-site links.
- **Caching**: transients for REST & GraphQL responses (1 hour/day).
- **Theming**: simple customizable CSS with dark‐purple background and yellow accents.

## Installation

1. **Upload plugin** to your site’s `wp-content/plugins/` directory (retain subfolders: `includes/`, `templates/`, `css/`).
2. Place a sample `profiles.csv` in the plugin root:
   ```csv
   name,slug,github_username
   Jonh Doe,john-doe,johndoe
   Jane Doe,jane-doe,janedoe```

Activate the “People Directory” plugin in WP Admin → Plugins.
Flush permalinks: WP Admin → Settings → Permalinks → Save Changes.

## Configuration
GitHub GraphQL Token (optional)
To enable the contributions chart, define a GitHub Personal Access Token in your `wp-config.php`:

```define( 'GITHUB_TOKEN', 'your_token_here' );```

If no token is provided, the plugin will skip the GraphQL query and omit the chart.
REST‐API calls (bio, avatar, repos) work without authentication (60 requests/hour).

## Usage
Visit any profile URL, e.g. `https://example.com/people/xiaoluoboding/`.
View your custom sitemap at `https://example.com/people-sitemap.xml`.
Submit the sitemap in Google Search Console to index these pages.
