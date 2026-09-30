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
});
