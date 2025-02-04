jQuery(document).ready(function ($) {
  // Enable sortable for steps.
  $("#ajdwp_steps_body").sortable({
    placeholder: "ajdwp-sortable-placeholder",
    forcePlaceholderSize: true,
  });

  // Add new step row.
  $("#ajdwp-add-step").on("click", function () {
    var newRow =
      '<tr class="ajdwp_step">' +
      '<td><input type="text" name="step_title[]" value="" /></td>' +
      '<td><input type="text" name="step_nickname[]" value="" placeholder="Optional" /></td>' +
      '<td><input type="text" name="step_link[]" value="" /></td>' +
      '<td><button class="button ajdwp-delete-step" type="button">Delete</button></td>' +
      "</tr>";
    $("#ajdwp_steps_body").append(newRow);
  });

  // Remove step row.
  $(document).on("click", ".ajdwp-delete-step", function () {
    $(this).closest("tr").remove();
  });
});
