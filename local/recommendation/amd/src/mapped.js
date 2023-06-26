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
    }

  });
}