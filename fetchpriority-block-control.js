/**
 * Fetch Priority Enhancer for WordPress Block Editor
 *
 * This script extends the Gutenberg block editor by adding a "Fetch Priority"
 * setting to the core Image and Cover blocks. The setting allows users to
 * specify the fetchpriority attribute (high, auto, or default) for images,
 * helping optimize loading performance and Core Web Vitals.
 *
 * Features:
 * - Adds a new "fetchpriority" attribute to Image and Cover blocks.
 * - Registers a dropdown selector in the block settings sidebar.
 * - Allows users to set fetch priority directly from the editor.
 * - Improves page speed optimization by prioritizing critical images.
 *
 * Author: Your Name
 * Version: 1.0.0
 * License: MIT
 *
 * @package FetchPriorityEnhancer
 */

(function (blocks, element, components, blockEditor) {
  var el = element.createElement;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;

  /**
   * Adds the "fetchpriority" attribute to the core/image and core/cover blocks.
   * This allows users to set a fetch priority for images directly in the block editor.
   */
  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "fetchpriority-enhancer/add-fetchpriority-attribute",
    function (settings, name) {
      // Check if the block is an image or cover block
      if (name === "core/image" || name === "core/cover") {
        // Add a new attribute "fetchpriority" with a default empty value
        settings.attributes.fetchpriority = {
          type: "string",
          default: "",
        };
      }
      return settings;
    }
  );

  /**
   * Adds a "Fetch Priority" dropdown in the block settings sidebar for image and cover blocks.
   * This lets users select a priority for image loading.
   */
  wp.hooks.addFilter(
    "editor.BlockEdit",
    "fetchpriority-enhancer/add-fetchpriority-control",
    function (BlockEdit) {
      return function (props) {
        // Only apply this control to core/image and core/cover blocks
        if (props.name !== "core/image" && props.name !== "core/cover") {
          return el(BlockEdit, props);
        }

        return el(
          element.Fragment,
          {},
          el(BlockEdit, props), // Render the original block editor component
          el(
            InspectorControls, // WordPress' built-in control panel for block settings
            {},
            el(
              PanelBody, // Group UI elements inside a collapsible panel
              {
                title: "Fetch Priority", // Title displayed in the panel
                initialOpen: true, // Keep panel open by default
                className: "components-panel__body", // Styling class for consistency
              },
              el(SelectControl, {
                label: "Fetch Priority", // Label for the dropdown
                value: props.attributes.fetchpriority, // Current value
                options: [
                  { label: "Default", value: "" }, // No priority set
                  { label: "High", value: "high" }, // Prioritize loading
                  { label: "Auto", value: "auto" }, // Let the browser decide
                ],
                onChange: function (value) {
                  // Update the fetchpriority attribute when selection changes
                  props.setAttributes({ fetchpriority: value });
                },
              })
            )
          )
        );
      };
    }
  );
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.blockEditor
);
