<?php
/*
Programa 3:
Data creació: 20150103
Script  per a consultar el stock de la taula integració ICG i actualitzar-lo al Prestashop
*/
	require_once("Utils.php");

	$utils = new Utils();

	//Consulta productes creats nous
	$margeActualitzacio = strtotime("-60 minutes");
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$result_producte = $utils->nousStocks($timestampACercar);

	echo "PS_ICG_INTEGRATION: prestaStocks.php <br>\n";

	if( $utils->myDB->num_rows($result_producte) > 0 ){//Hi ha productes a crear/actualitzar
		while($row_producte = $utils->myDB->fetch_array($result_producte))
		{
			if($row_producte['ps_producte_atribut'] == 0){
				echo "Hi ha un problema amb el producte: ".$row_producte['icg_producte']."<br>\n";
			}else{
				$id = $utils->actualitzarStock($row_producte['ps_producte'], $row_producte['ps_producte_atribut'], $row_producte['stock_actual']);
				echo "<br>\n";

				$utils->flagActualitzatStock($row_producte);
			}
		}
	}else{
		echo date("Y-m-d H:i:s").": No	hi ha stocks a actualitzar.";
	}
?>
