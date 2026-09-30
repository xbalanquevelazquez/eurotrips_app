<?php
//requiere se haya llamado la clase mysql = $mysqlconn
class Paginacion
{
	var $regInicial = 0;
	var $printResult = 0;
	var $totalRegistros = 0;
	var $pagActual = 0;
	var $conexion = '';
	var $data1 = '';
	var $data2 = '';
	var $data3 = '';
	var $data4 = '';


	function init($conexion){
		$this->conexion = $conexion;
		$this->data1 = NULL;
		if(isset($_GET['data1']) && $_GET['data1'] != ''){ $this->data1 = $_GET['data1']; }else{ $this->data1 = 'LOGIN'; }
		$this->data2 = NULL;
		if(isset($_GET['data2']) && $_GET['data2'] != ''){ $this->data2 = $_GET['data2']; }
		$this->data3 = NULL;
		if(isset($_GET['data3']) && $_GET['data3'] != ''){ $this->data3 = $_GET['data3']; }
		$this->data4 = NULL;
		if(isset($_GET['data4']) && $_GET['data4'] != ''){ $this->data4 = $_GET['data4']; }
	}

	function paginar($totalRegistros,$regXPag,$paginasAMostrar,$totalPaginas,$pagActual,$pagVar=''){
		#echo "totalRegistros: $totalRegistros,regXPag: $regXPag,paginasAMostrar: $paginasAMostrar,totalPaginas: $totalPaginas,pagActual: $pagActual,pagVar: $pagVar";
		$resultadoHTML = '';
		//PAGINACION
		//$totalRegs = $this->conexion->query($queryPagTotal);
		/*if(isset($this->data2) && $this->data2 == 'pag'){
			$pagActual = $this->data3;
		}else if(isset($this->data3) && $this->data3 == 'pag'){
			$pagActual = $this->data4;
		}else if(isset($this->data4) && $this->data4 == 'pag'){
			$pagActual = $this->data5;
		}else{
			$pagActual = 1;
		}*/
		
		//$resultadoHTML .= "<span>Total de registros:</span> $totalRegistros<br />";
	
		$this->totalRegistros = $totalRegistros;
		if($totalPaginas != 0){//hay páginas
			$this->printResult = 1;
			if ($pagActual > $totalPaginas) $pagActual = $totalPaginas;
			
			if($pagActual > floor($paginasAMostrar/2)){
				$pagInicial = $pagActual - floor($paginasAMostrar/2);
			} else {
				$pagInicial = 1;
			}
			
			$vinculosHTML = ' ';
			
			for($i=$pagInicial; $i<$pagInicial+$paginasAMostrar; $i++){
				if ($i <= $totalPaginas){
					if($i != $pagActual){//no es la página actual
						$currHREF = ' page="'.$i.'"';
						$currState = '';

						//$vinculosHTML .= '<a href="'.WEB_URL.$pagVar.'/pag/'.$i.'">'.$i.'</a>  &nbsp;&nbsp;';
					} else {
						$currHREF = '';
						$currState = ' active';

						//$vinculosHTML .= '<span>'.$i.'</span>  &nbsp;&nbsp;';
					}
					$vinculosHTML .= '<li class="page-item'.$currState.'"><a class="page-link" '.$currHREF.'>'.$i.'</a></li>';
				}
			}
			$registroInicial = ($pagActual-1)*$regXPag;
			$this->regInicial = $registroInicial;
				
			$resultadoHTML .= '
				<ul class="pagination">
					';

			if($pagActual == 1) {
				$resultadoHTML .= '
					<li class="page-item">
						<a class="page-link bg-grisclaro texto-grismedio" disabled>
						<span>&laquo;</span>
						</a>
					</li>
					<li class="page-item">
						<a class="page-link bg-grisclaro texto-grismedio" disabled>
						<span>&lsaquo;</span>
						</a>
					</li>';
			} else {
				$prePag = $pagActual-1;
				$resultadoHTML .= '
					<li class="page-item">
						<a class="page-link" page="1">
						<span>&laquo;</span>
						</a>
					</li>
					<li class="page-item">
						<a class="page-link" page="'.$prePag.'">
						<span>&lsaquo;</span>
						</a>
					</li>';
			}
			$resultadoHTML .= $vinculosHTML;
			if($pagActual == $totalPaginas) {//ULTIMA PAGINA
				$resultadoHTML .= '
					<li class="page-item">
						<a class="page-link bg-grisclaro texto-grismedio" disabled>
						<span>&rsaquo;</span>
						</a>
					</li>
					<li class="page-item">
						<a class="page-link bg-grisclaro texto-grismedio" disabled>
						<span>&raquo;</span>
						</a>
					</li>';
			} else {
				$sigPag = $pagActual+1;
				$resultadoHTML .= '
					<li class="page-item">
						<a class="page-link"  page="'.$sigPag.'">
						<span>&rsaquo;</span>
						</a>
					</li>
					<li class="page-item">
						<a class="page-link"  page="'.$totalPaginas.'">
						<span>&raquo;</span>
						</a>
					</li>';

			}

			$resultadoHTML .= '
				</ul>';

				
			#$pagLimit = ($pagActual-1)*$regXPag;
			//devolver el arra de resultado
		}else{//fin de hay páginas

		}
			$this->pagActual = $pagActual;
			$arrResultado = array("HTML"=>$resultadoHTML);
			return $arrResultado;
	}
}
?>