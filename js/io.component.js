$(document).ready(function(){
	activateIOChecks();
});
function makeIOControl(obj){
	var isChecked = obj.is(':checked');
	var originID = obj.attr('id');
	var currentEstatus = 'ioOFF';
	var currentValue = 0;
	var currentlyDisabled = '';
	var disabled = obj.attr('disabled');
	if(disabled == 'disabled'){
		currentlyDisabled = 'ioDisabled';
	}
	if(isChecked){
		currentEstatus = 'ioON';
		currentValue = 1;
	}
	var controlIO = '<div class="ioCheck '+currentEstatus+' '+currentlyDisabled+'" estatus="'+currentValue+'" reference="'+originID+'"><div><span>&nbsp;</span></div><span class="onLabel">ON</span><span>OFF</span></div>';
	obj.after(controlIO);
	obj.css({display:'none'});
}
function switchEstatusIO(obj){
	var placa = obj.find('div');
	var reference = $('#'+obj.attr('reference'));
	var anchoElemento = obj.width();
	var anchoPlaca = placa.width();
	var disabled = reference.attr('disabled');
	if(disabled == undefined){
		obj.toggleClass('ioOFF').toggleClass('ioON');
		if(obj.attr('estatus') == 0){//ESTA APAGADO -> ENCENDER
			obj.attr('estatus',1);
			reference.prop('checked', true);
		}else{//ESTA ENCENDIDO -> APAGAR
			obj.attr('estatus',0);
			reference.prop('checked', false);
		}
	}
}
function activateIOChecks(){
	$('.makeIO').each(function(){
		makeIOControl($(this));
	});
	$('.ioCheck').click(function(e){
		switchEstatusIO($(this));
	});
}