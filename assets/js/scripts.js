jQuery(document).ready(function($) {
  $("#file").bind("change", function () {
    var filename = $("#file").val();
    if (/^\s*$/.test(filename)) {
      $(".file-upload").removeClass("active");
      $("#noFile").text("No file chosen...");
    } else {
      $(".file-upload").addClass("active");
      $("#noFile").text(filename.replace("C:\\fakepath\\", ""));
    }
  });  

  if ($("input[name='direction']:checked").val() == 'Б'){
    $('#course3').show();
    $('#course4').show();
  } else {
    $('#course3').hide();
    $('#course4').hide();
  }

  $("input[name='direction']").change(function(){
    // Do something interesting here
    if (this.value !== 'Б') {
      $('#course3').hide();
      $('#course4').hide();
    } else {
      $('#course3').show();
      $('#course4').show();
    }
  });
  
  if ($("directionSelect").val() !== 'Б'){
    $('#course3filter').hide();
    $('#course4filter').hide();
  } else {
    $('#course3filter').show();
    $('#course4filter').show();
  }

  $('#directionSelect').change(function(){
    // Do something interesting here
    if (this.value !== 'Б') {
      $('#course3filter').hide();
      $('#course4filter').hide();
    } else {
      $('#course3filter').show();
      $('#course4filter').show();
    }
  });

});