<?php
/**
 * RG Git Plugin Updater
 * Handles automatic updates from GitHub.
 */

class RgGitUpdater
{
  private $plugin_file;
  private $github_repo;
  private $github_api_url = "https://api.github.com/repos/";
  private $github_raw_url = "https://raw.githubusercontent.com/";
  private $plugin_version;
  private $plugin_name;
  private $plugin_description;
  private $plugin_author;

  public function __construct($plugin_path)
  {
    $this->plugin_file = $plugin_path . "/index.php";

    // Hämta plugin-information från huvudinformationsblocket
    $plugin_data = get_file_data($this->plugin_file, [
      "Version" => "Version",
      "Author" => "Author",
      "PluginURI" => "Plugin URI",
      "PluginName" => "Plugin Name",
      "Description" => "Description",
    ]);
    // var_dump($plugin_data);
    $this->plugin_version = $plugin_data["Version"];
    $this->plugin_author = $plugin_data["Author"];
    $this->plugin_name = $plugin_data["PluginName"];
    $this->plugin_description = $plugin_data["Description"];
    $this->github_repo = trim(
      parse_url($plugin_data["PluginURI"], PHP_URL_PATH),
      "/"
    );
    add_filter("pre_set_site_transient_update_plugins", [
      $this,
      "check_for_update",
    ]);
    add_filter("plugins_api", [$this, "plugin_info"], 10, 3);
    add_filter("upgrader_post_install", [$this, "after_update"], 10, 3);
  }

  /**
   * Check for updates from GitHub.
   */
  public function check_for_update($transient)
  {
    // Se till att $transient är ett objekt
    if (!is_object($transient)) {
      $transient = new stdClass();
    }

    // Se till att 'checked' finns i $transient
    if (!isset($transient->checked) || !is_array($transient->checked)) {
      $transient->checked = [];
    }

    // Se till att vårt plugin finns i 'checked'
    $plugin_slug = plugin_basename($this->plugin_file);
    if (!isset($transient->checked[$plugin_slug])) {
      error_log(
        "Vårt plugin ($plugin_slug) saknades i 'checked', lägger till det!"
      );
      $transient->checked[$plugin_slug] = $this->plugin_version;
    }
    if (empty($transient->checked)) {
      return $transient;
    }

    // Debug: Se vilken URL vi använder
    $github_url =
      $this->github_api_url . $this->github_repo . "/releases/latest";
    error_log("Försöker hämta version från GitHub: " . $github_url);

    // Hämta den senaste versionen från GitHub
    $response = wp_remote_get($github_url, [
      "headers" => [
        "Accept" => "application/vnd.github.v3+json",
        "User-Agent" =>
          "WordPress/" . get_bloginfo("version") . "; " . home_url(),
      ],
    ]);

    if (is_wp_error($response)) {
      error_log("GitHub API misslyckades: " . $response->get_error_message());
      return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    // error_log("GitHub API returnerade: " . print_r($release, true));

    if (!isset($release->tag_name)) {
      error_log("⚠️ Ingen version hittades på GitHub.");
      return $transient;
    }

    $new_version = $release->tag_name;
    if (version_compare($this->plugin_version, $new_version, "<")) {
      error_log("Ny version tillgänglig: " . $new_version);
      $transient->response[$plugin_slug] = (object) [
        "slug" => $plugin_slug,
        "new_version" => $new_version,
        "package" => $release->zipball_url,
        "url" => $release->html_url,
      ];
    } else {
      error_log("Pluginet är redan uppdaterat.");
    }

    return $transient;
  }

  /**
   * Display plugin update information in the WordPress updater.
   */
  public function plugin_info($result, $action, $args)
  {
    if ($action !== "plugin_information" || !isset($args->slug)) {
      return $result;
    }

    if ($args->slug !== plugin_basename($this->plugin_file)) {
      return $result;
    }

    $response = wp_remote_get(
      $this->github_api_url . $this->github_repo . "/releases/latest"
    );

    if (is_wp_error($response)) {
      return $result;
    }

    $release = json_decode(wp_remote_retrieve_body($response));

    if (!isset($release->tag_name)) {
      return $result;
    }

    $result = (object) [
      "name" => $this->plugin_name,
      "slug" => plugin_basename($this->plugin_file),
      "version" => $release->tag_name,
      "author" => $this->plugin_author,
      "homepage" => $release->html_url,
      "sections" => [
        "description" => $this->plugin_description,
        "changelog" => isset($release->body) ? nl2br($release->body) : "",
      ],
      "download_link" => $release->zipball_url,
    ];

    return $result;
  }

  /**
   * Ensure correct installation after updating the plugin.
   */
  public function after_update($response, $hook_extra, $result)
  {
    global $wp_filesystem;

    if (
      isset($hook_extra["plugin"]) &&
      $hook_extra["plugin"] === plugin_basename($this->plugin_file)
    ) {
      $plugin_folder =
        WP_PLUGIN_DIR . "/" . dirname(plugin_basename($this->plugin_file));
      $wp_filesystem->move($result["destination"], $plugin_folder);
      $result["destination"] = $plugin_folder;
    }

    return $result;
  }
}

// Initiera uppdateraren i pluginets huvudfil
new RgGitUpdater(__DIR__);
