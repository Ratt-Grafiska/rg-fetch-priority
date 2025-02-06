(function (blocks, element, components, blockEditor) {
  var el = element.createElement;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;

  // Lägg till fetchpriority-attributet till core/image och core/cover
  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "fetchpriority-enhancer/add-fetchpriority-attribute",
    function (settings, name) {
      if (name === "core/image" || name === "core/cover") {
        settings.attributes.fetchpriority = {
          type: "string",
          default: "",
        };
      }
      return settings;
    }
  );

  // Lägg till inställningsfält i sidopanelen
  wp.hooks.addFilter(
    "editor.BlockEdit",
    "fetchpriority-enhancer/add-fetchpriority-control",
    function (BlockEdit) {
      return function (props) {
        if (props.name !== "core/image" && props.name !== "core/cover") {
          return el(BlockEdit, props);
        }

        return el(
          element.Fragment,
          {},
          el(BlockEdit, props),
          el(
            InspectorControls,
            {},
            el(
              PanelBody,
              {
                title: "Fetch Priority",
                initialOpen: true,
                className: "components-panel__body",
              },
              el(SelectControl, {
                label: "Fetch Priority",
                value: props.attributes.fetchpriority,
                options: [
                  { label: "Default", value: "" },
                  { label: "High", value: "high" },
                  { label: "Auto", value: "auto" },
                ],
                onChange: function (value) {
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
