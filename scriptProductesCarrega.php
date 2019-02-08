<?php
/*
Programa principal per a carregar les novetats (articles, stocks i preus) a ICG a les taula d'integració ICG 
*/
	require_once("DBMySQL.php");
	require_once("DBMSSQLServer.php");

	$myDB = new MySQL();
	//$margeActualitzacio = strtotime("-1440 minutes");//24hores
	$margeActualitzacio = strtotime("-14400 minutes");//2400hores
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T' AND Fecha_Modificado BETWEEN '".date("Y-m-d H:i:s",$margeActualitzacio)."' AND '".date("Y-m-d H:i:s")."'";
	//$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T'";
	$msDB = new MSSQL();
	echo "PS_ICG_INTEGRATION: scriptProductesCarrega.php <br>\n\n";
	//Consulta links desats i explora el primer buscant nous links i els guarda a enllacos
	//$result = $msDB->consulta($query);


	$dbh = $msDB->getConexion();
	// FETCH_ASSOC
	$stmt = $dbh->prepare($query);
	// Especificamos el fetch mode antes de llamar a fetch()
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	// Ejecutamos
	$stmt->execute();

	$result = 1;
	if( $result > 0 ){//Hi ha productes a crear/actualitzar
		while ($row = $stmt->fetch())
		//while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ean13;
			$result3 = $myDB->consulta("SELECT * FROM icgps.icg_ps_producte WHERE icg_producte = ".$row['CODARTICULO']." AND icg_color = '".$row['COLOR']."' AND icg_talla = '".$row['TALLA']."'");
			$rows_prestashop = $myDB->num_rows($result3);
			if( $rows_prestashop > 0 ){ //És una actualització de producte
				echo "Ja existeix el producte ".$row['DESCRIPCION'].": ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']." a la taula temporal d'enllaç icg_ps_producte <br>";
			}else{
					if($row['Codproveedor'] == 0){
						$result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, ean13) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,'".$row['CODBARRAS']."')");
					}else{
						$result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, fabricant, ean13, nom_fabricant) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,".$row['Codigo_Marca'].",'".$row['CODBARRAS']."','".$row['Descripcion_Marca']."')");
					}

					if($myDB->mysql_affected_rows() > 0){
						echo "S'ha creat el producte: ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']."<br>\n";
					}

					/**
					Aprofitem per inserir també el preu
					*/
					$result_preu = $msDB->consulta("SELECT * FROM view_imp_preus WHERE Codarticulo = ".$row['CODARTICULO']." AND Talla = '".$row['TALLA']."' AND Color = '".$row['COLOR']."'");

					//Per a cada nou stock
					$row_preu = $result_preu->fetch(PDO::FETCH_ASSOC);

					$myDB->consulta("INSERT INTO icgps.icg_ps_preus (tarifa, icg_producte, icg_talla, icg_color, timestamp, pvp, dto_percent, preu_oferta, dto_euros, iva, pvp_siva, preu_oferta_siva, dto_euros_siva, flag_actualitzat) VALUES (1,".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".date("Y-m-d H:i:s")."',".$row_preu['Pbruto_iva'].",".$row_preu['Dto_porc'].",".$row_preu['Pneto_iva'].",".$row_preu['Dto_impote_iva'].",".$row_preu['Iva'].",".$row_preu['Pbruto_s_iva'].",".$row_preu['Pneto_s_iva'].",".$row_preu['Dto_importe_s_iva'].",1)");

					if($myDB->mysql_affected_rows() > 0){
						echo "S'ha actualitzat els preus del producte correctament: ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']."<br>\n";
					}else{
						//echo "El producte ".$row['Codarticulo']."  =>  ".$row['Talla']." - ".$row['Color']." no exiteix encara.<br>\n";
						echo "No s'ha actualitzat el preu del producte<br>\n";
					}


					/**
					Aprofitem per inserir també l'stock
					*/
					$result_stock = $msDB->consulta("SELECT * FROM view_imp_stocks WHERE Codarticulo = ".$row['CODARTICULO']." AND Talla = '".$row['TALLA']."' AND Color = '".$row['COLOR']."'");

					//Per a cada nou stock
					$row_stock = $result_stock->fetch(PDO::FETCH_ASSOC);

					$myDB->consulta("INSERT INTO icgps.icg_ps_stocks (icg_producte, icg_talla, icg_color, timestamp,ean13,stock_actual,flag_actualitzat) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".date("Y-m-d H:i:s")."','".$row_stock['CODBARRAS']."',".intval($row_stock['Stock_disponible']).",1)");

					if($myDB->mysql_affected_rows() > 0){
						echo "S'ha actualitzat l'stock del producte correctament: ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']."<br>\n";
					}else{
						//echo "El producte ".$row['Codarticulo']."  =>  ".$row['Talla']." - ".$row['Color']." no exiteix encara.<br>\n";
						echo "No s'ha actualitzat el stock del producte<br>\n";
					}
			}
		}
	}else{
		echo date("Y-m-d H:i:s").": No hi ha productes modificats a ICG.";
	}
?>
