<?php
/*
Plugin Name: Rätt Grafiska Fetch Priority
Plugin URI: https://github.com/Ratt-Grafiska/
Update URI: https://github.com/Ratt-Grafiska/rg-fetch-priority
Description: This plugin enhances the WordPress block editor by adding a fetchpriority attribute to the Image and Cover blocks. The fetchpriority attribute helps optimize loading behavior by prioritizing specific images for faster rendering, improving Core Web Vitals and overall page performance.

With this plugin, users can easily configure the fetchpriority setting directly from the block settings panel in the editor. Available options include:

- Default (empty) – No priority set, follows browser behavior.
- High – Marks the image as a high priority, ensuring it loads sooner.
- Auto – Allows the browser to decide the priority dynamically.

By using this feature, developers and content creators can fine-tune image loading strategies to improve page speed and user experience.
Version: 1.0.6
Author: Johan Wistbacka
Author URI: https://wistbacka.se
License: GPL2
*/

// Initiera uppdateraren
if (!class_exists("RgGitUpdater")) {
  require_once plugin_dir_path(__FILE__) . "rg-git-updater.php";
}

require_once plugin_dir_path(__FILE__) . "fetch-priority-plugin.php";

// Se till att JavaScript-filen laddas korrekt
function fetchpriority_enqueue_assets()
{
  wp_enqueue_script(
    "fetchpriority-block-control",
    plugin_dir_url(__FILE__) . "fetchpriority-block-control.js",
    ["wp-blocks", "wp-element", "wp-edit-post", "wp-components", "wp-data"],
    filemtime(plugin_dir_path(__FILE__) . "fetchpriority-block-control.js")
  );
}
add_action("enqueue_block_editor_assets", "fetchpriority_enqueue_assets");

// Skapa en meny för plugininställningar
add_action("admin_menu", function () {
  add_options_page(
    "Fetch Priority Enhancer",
    "Fetch Priority",
    "manage_options",
    "fetchpriority-settings",
    "fetchpriority_settings_page"
  );
});

// Visa plugin-information på inställningssidan
function fetchpriority_settings_page()
{
  $plugin_data = get_plugin_data(__FILE__); ?>
    <div class="wrap">
        <h1>Fetch Priority Enhancer</h1>
        <p><strong>Version:</strong> <?php echo esc_html(
          $plugin_data["Version"]
        ); ?></p>
        <p><strong>Author:</strong> <?php echo $plugin_data["Author"]; ?></p>
        <p><strong>GitHub Repository:</strong> <a href="<?php echo esc_url(
          $plugin_data["UpdateURI"]
        ); ?>" target="_blank"><?php echo esc_html(
  $plugin_data["UpdateURI"]
); ?></a></p>
    </div>
    <?php
}
