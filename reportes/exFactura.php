<?php
ob_start();
if (strlen(session_id()) < 1) 
  session_start();

if (!isset($_SESSION["nombre"])) {
  echo 'Debe ingresar al sistema correctamente para visualizar el reporte';
} else {
  if ($_SESSION['ventas'] == 1) {

    require('Factura.php');
    require_once "../modelos/Venta.php";
    require_once "Letras.php";

    // ==== DATOS DE LA EMPRESA ====
    $logo = "logo.jpg";
    $ext_logo = "jpg";
    $empresa = "Ferretería neko";
    $documento = "10406980788";
    $direccion = "Carretera a lambayeque";
    $telefono = "921263349";
    $email = "nekosaccix@gmail.com";

    // ==== DATOS DE LA VENTA ====
    $venta = new Venta();
    $rsptav = $venta->ventacabecera($_GET["id"]);
    $regv = $rsptav->fetch_object();

    // ==== CONFIGURACIÓN PDF ====
    $pdf = new PDF_Invoice('P', 'mm', 'A4');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // ==== ENCABEZADO EMPRESA ====
    $pdf->addSociete(
      utf8_decode($empresa),
      $documento . "\n" .
      utf8_decode("Dirección: ") . utf8_decode($direccion) . "\n" .
      utf8_decode("Teléfono: ") . $telefono . "\n" .
      "Email: " . $email,
      $logo,
      $ext_logo
    );

    $pdf->fact_dev("$regv->tipo_comprobante", "$regv->serie_comprobante-$regv->num_comprobante");
    $pdf->addDate($regv->fecha);

    // ==== DATOS DEL CLIENTE ====
    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(60); // separación del encabezado
    $pdf->Cell(0, 6, "Cliente: " . utf8_decode($regv->cliente), 0, 1);
    $pdf->Cell(0, 6, "Domicilio: " . utf8_decode($regv->direccion), 0, 1);
    $pdf->Cell(0, 6, "Documento: " . $regv->tipo_documento . ": " . $regv->num_documento, 0, 1);
    if (!empty($regv->email)) $pdf->Cell(0, 6, "Email: " . $regv->email, 0, 1);
    if (!empty($regv->telefono)) $pdf->Cell(0, 6, "Teléfono: " . $regv->telefono, 0, 1);

    $pdf->Ln(5);

    // ==== ENCABEZADO DE TABLA ====
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 8, "CODIGO", 1, 0, 'C');
    $pdf->Cell(80, 8, "DESCRIPCION", 1, 0, 'C');
    $pdf->Cell(20, 8, "CANT.", 1, 0, 'C');
    $pdf->Cell(25, 8, "P.U.", 1, 0, 'C');
    $pdf->Cell(20, 8, "DSCTO", 1, 0, 'C');
    $pdf->Cell(25, 8, "SUBTOTAL", 1, 1, 'C');

    // ==== DETALLES ====
    $pdf->SetFont('Arial', '', 9);
    $rsptad = $venta->ventadetalle($_GET["id"]);

    while ($regd = $rsptad->fetch_object()) {
      $yInicio = $pdf->GetY();
      $xInicio = 10;

      // CODIGO
      $pdf->SetXY($xInicio, $yInicio);
      $pdf->Cell(20, 8, $regd->codigo, 1, 0, 'C');

      // DESCRIPCION (MULTILÍNEA)
      $pdf->SetXY($xInicio + 20, $yInicio);
      $pdf->MultiCell(80, 8, utf8_decode($regd->articulo), 1, 'L');

      // Reajustar altura por si hay varias líneas
      $yFinal = $pdf->GetY();
      $altura = $yFinal - $yInicio;

      // Volvemos a la derecha de la descripción
      $pdf->SetXY($xInicio + 100, $yInicio);
      $pdf->Cell(20, $altura, $regd->cantidad, 1, 0, 'C');
      $pdf->Cell(25, $altura, number_format($regd->precio_venta, 2), 1, 0, 'R');
      $pdf->Cell(20, $altura, number_format($regd->descuento, 2), 1, 0, 'R');
      $pdf->Cell(25, $altura, number_format($regd->subtotal, 2), 1, 1, 'R');
    }

    // ==== TOTAL EN LETRAS ====
    $pdf->Ln(5);
    $V = new EnLetras(); 
    $con_letra = strtoupper($V->ValorEnLetras($regv->total_venta, "NUEVOS SOLES"));
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell(0, 8, "Total en Letras: " . $con_letra, 0, 'L');

    // ==== CÁLCULOS CORREGIDOS ====
    $igv = ($regv->total_venta * $regv->impuesto) / 100;
    $subtotal = $regv->total_venta - $igv;

    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(130, 8, "Subtotal:", 0, 0, 'R');
    $pdf->Cell(40, 8, "S/ " . number_format($subtotal, 2), 0, 1, 'R');

    $pdf->Cell(130, 8, "IGV (" . $regv->impuesto . "%):", 0, 0, 'R');
    $pdf->Cell(40, 8, "S/ " . number_format($igv, 2), 0, 1, 'R');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(130, 8, "TOTAL:", 0, 0, 'R');
    $pdf->Cell(40, 8, "S/ " . number_format($regv->total_venta, 2), 0, 1, 'R');

    // ==== SALIDA PDF ====
    $pdf->Output('Reporte_de_Venta.pdf', 'I');

  } else {
    echo 'No tiene permiso para visualizar el reporte';
  }
}

ob_end_flush();
?>
