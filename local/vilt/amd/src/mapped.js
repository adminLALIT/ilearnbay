document.addEventListener('DOMContentLoaded', function() {
  var deleteButtons = document.querySelectorAll('.delete');

  deleteButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      var separateDiv = this.closest('.separate');
      if (separateDiv) {
        var fieldRepeatsInput = document.querySelector('input[name="profile_repeats"]');
        var currentValue = parseInt(fieldRepeatsInput.value);
      
        // Decrease the value by 1
        var newValue = currentValue - 1;
      
        // Update the value of the input field
        fieldRepeatsInput.value = newValue.toString();
        separateDiv.remove();
      }
    });
  });
});

