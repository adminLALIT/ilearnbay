function getdomain() {
    var idpreffix = "id_";
   var companyid =  $("#"+idpreffix+"companyid").val();
  $.ajax({
    method: "POST",
    url : 'ajax/ajax.php',
    dataType : 'json',
    data : {
        'companyid': companyid
    },
    async : false,
    success : function(json){
        $("#"+idpreffix+"domain").html(json.options);
        if ($("#"+idpreffix+"course").length > 0) {
          $("#"+idpreffix+"course").html(json.courseoptions);
        } 
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  var deleteButtons = document.querySelectorAll('.delete');

  deleteButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      var separateDiv = this.closest('.separate');
      if (separateDiv) {
        var fieldRepeatsInput = document.querySelector('input[name="field_repeats"]');
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

