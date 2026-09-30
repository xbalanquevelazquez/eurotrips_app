$(document).ready(function(){
  console.log('¡App lista!');
  $('#menuControl').prop('checked',false);
  $('.controlBtnDespliegue').click(function(){
    if($('#menuControl').prop('checked')){
      $('.controlBtnDespliegue').removeClass('activo');
    }else{
      $('.controlBtnDespliegue').addClass('activo');

    }
  });

  $("input, textarea, button").focus(function(){
    $(this).addClass("glow");
  });
  $("input, textarea, button").blur(function(){
    $(this).removeClass("glow");
  });
  $("#btnSalir").click(function(){
    document.location.href = siteURL+"SALIR";
  });
});
