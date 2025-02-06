<?php
/**
 * RG Git Plugin Updater
 * Handles automatic updates from GitHub.
 */

class RgGitUpdater {
    private $plugin_file;
    private $github_repo;
    private $github_api_url = 'https://api.github.com/repos/';
    private $plugin_version;
    private $plugin_name;
    private $plugin_description;
    private $plugin_author;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;

        // Hämta plugin-information från huvudinformationsblocket
        $plugin_data = get_file_data($this->plugin_file, [
            'Version'    => 'Version',
            'Author'     => 'Author',
            'PluginURI'  => 'Plugin URI',
            'PluginName' => 'Plugin Name',
            'Description' => 'Description',
        ]);

        $this->plugin_version    = $plugin_data['Version'];
        $this->plugin_author     = $plugin_data['Author'];
        $this->plugin_name       = $plugin_data['PluginName'];
        $this->plugin_description = $plugin_data['Description'];
        $this->github_repo       = trim(parse_url($plugin_data['PluginURI'], PHP_URL_PATH), '/');

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_update'], 10, 3);
    }

    /**
     * Check for updates from GitHub.
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Hämta den senaste versionen från GitHub
        $response = wp_remote_get($this->github_api_url . $this->github_repo . '/releases/latest', [
            'headers' => ['Accept' => 'application/vnd.github.v3+json'],
        ]);

        if (is_wp_error($response)) {
            return $transient;
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release->tag_name)) {
            return $transient;
        }

        $new_version = $release->tag_name;

        if (version_compare($this->plugin_version, $new_version, '<')) {
            $plugin_slug = plugin_basename($this->plugin_file);

            $transient->response[$plugin_slug] = (object) [
                'slug'        => $plugin_slug,
                'new_version' => $new_version,
                'package'     => $release->zipball_url,
                'url'         => $release->html_url,
            ];
        }

        return $transient;
    }

    /**
     * Display plugin update information in the WordPress updater.
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug)) {
            return $result;
        }

        if ($args->slug !== plugin_basename($this->plugin_file)) {
            return $result;
        }

        $response = wp_remote_get($this->github_api_url . $this->github_repo . '/releases/latest');

        if (is_wp_error($response)) {
            return $result;
        }

        $release = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release->tag_name)) {
            return $result;
        }

        $result = (object) [
            'name'        => $this->plugin_name,
            'slug'        => plugin_basename($this->plugin_file),
            'version'     => $release->tag_name,
            'author'      => $this->plugin_author,
            'homepage'    => $release->html_url,
            'sections'    => [
                'description' => $this->plugin_description,
                'changelog'   => isset($release->body) ? nl2br($release->body) : '',
            ],
            'download_link' => $release->zipball_url,
        ];

        return $result;
    }

    /**
     * Ensure correct installation after updating the plugin.
     */
    public function after_update($response, $hook_extra, $result) {
        global $wp_filesystem;

        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === plugin_basename($this->plugin_file)) {
            $plugin_folder = WP_PLUGIN_DIR . '/' . dirname(plugin_basename($this->plugin_file));
            $wp_filesystem->move($result['destination'], $plugin_folder);
            $result['destination'] = $plugin_folder;
        }

        return $result;
    }
}

// Initiera uppdateraren i pluginets huvudfil
new RgGitUpdater(__FILE__);
