<?php
/*
Plugin Name: Fetch Priority Hook
Plugin URI: https://github.com/donjohann/fetch-priority
Description: This plugin enhances the WordPress block editor by adding a fetchpriority attribute to the Image and Cover blocks. The fetchpriority attribute helps optimize loading behavior by prioritizing specific images for faster rendering, improving Core Web Vitals and overall page performance.
  
  With this plugin, users can easily configure the fetchpriority setting directly from the block settings panel in the editor. Available options include:
  
  Default (empty) – No priority set, follows browser behavior.
  High – Marks the image as a high priority, ensuring it loads sooner.
  Auto – Allows the browser to decide the priority dynamically.
  By using this feature, developers and content creators can fine-tune image loading strategies to improve page speed and user experience.
Version: 1.1
Author: Johan Wistbacka
Author URI: https://wistbacka.se
License: GPL2
*/
?>
<?php // Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock // Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock // Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock // Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock
// Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock // Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock
// Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock
// Registrera hookar för att lägga till fetchpriority-attribut på bild- och omslagsblock

add_filter(
  "render_block",
  "add_fetchpriority_attribute_to_image_blocks",
  10,
  2
);
function add_fetchpriority_attribute_to_image_blocks($block_content, $block)
{
  // Kontrollera blocktyp (bild och omslag)
  if (!in_array($block["blockName"], ["core/image", "core/cover"])) {
    return $block_content;
  } // Kontrollera om inställningen för fetchpriority finns i blockets data
  $fetchpriority = isset($block["attrs"]["fetchpriority"])
    ? $block["attrs"]["fetchpriority"]
    : ""; // Om inställningen inte är satt, returnera oförändrat innehåll
  if (empty($fetchpriority) || !in_array($fetchpriority, ["high", "auto"])) {
    return $block_content;
  } // Lägg till fetchpriority-attributet i img-taggar
  $block_content = preg_replace_callback(
    "/<img\s[^>]+>/i",
    function ($matches) use ($fetchpriority) {
      $img_tag = $matches[0]; // Kontrollera om fetchpriority redan finns
      if (strpos($img_tag, "fetchpriority=") !== false) {
        return $img_tag;
      } // Lägg till fetchpriority-attributet
      return preg_replace(
        "/<img/",
        '<img fetchpriority="' . esc_attr($fetchpriority) . '"',
        $img_tag,
        1
      );
    },
    $block_content
  );
  return $block_content;
} // Hook för att manipulera blockdata
add_filter("block_type_metadata", function ($metadata) {
  if (in_array($metadata["name"], ["core/image", "core/cover"])) {
    $metadata["attributes"]["fetchpriority"] = [
      "type" => "string",
      "enum" => ["high", "auto"],
    ];
  }
  return $metadata;
}); // Ladda JavaScript för att skapa inställningsfältet i redigeraren
add_action("enqueue_block_editor_assets", function () {
  wp_enqueue_script(
    "fetchpriority-block-control",
    plugin_dir_url(__FILE__) . "fetchpriority-block-control.js",
    ["wp-blocks", "wp-element", "wp-edit-post", "wp-components", "wp-data"],
    filemtime(plugin_dir_path(__FILE__) . "fetchpriority-block-control.js")
  );
});

