<?php
// Activamos el almacenamiento en el buffer
ob_start();
if (strlen(session_id()) < 1) 
  session_start();

if (!isset($_SESSION["nombre"]))
{
  echo 'Debe ingresar al sistema correctamente para visualizar el reporte';
}
else
{
  if ($_SESSION['ventas'] == 1)
  {
    // Incluimos el archivo Factura.php
    require('Factura.php');

    // Establecemos los datos de la empresa
    $logo = "logo.jpg";
    $ext_logo = "jpg";
    $empresa = "Minisoft.";
    $documento = "20477157772";
    $direccion = "Urb.San juan Chiclayo";
    $telefono = "932375500";
    $email = "cristiandavilavalle@gmail.com";

    // Obtenemos los datos de la cabecera de la venta actual
    require_once "../modelos/Venta.php";
    $venta = new Venta();
    $rsptav = $venta->ventacabecera($_GET["id"]);
    
    // Recorremos todos los valores obtenidos
    $regv = $rsptav->fetch_object();

    // Establecemos la configuración de la factura
    $pdf = new PDF_Invoice('P', 'mm', 'A4');
    $pdf->AddPage();

    // Enviamos los datos de la empresa al método addSociete de la clase Factura
    $pdf->addSociete(utf8_decode($empresa),
                    $documento."\n" .
                    utf8_decode("Dirección: ").utf8_decode($direccion)."\n".
                    utf8_decode("Teléfono: ").$telefono."\n" .
                    "Email : ".$email,$logo,$ext_logo);
    
    $pdf->fact_dev("$regv->tipo_comprobante", "$regv->serie_comprobante-$regv->num_comprobante"); 
    $pdf->addDate($regv->fecha);

    // AGREGAR DATOS DEL CLIENTE MANUALMENTE SI NO EXISTE EL MÉTODO addClientAdresse()
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(10, 90); // Establece la posición donde quieres que empiece la información del cliente
    $pdf->Cell(0, 10, "Cliente: " . utf8_decode($regv->cliente), 0, 1);
    $pdf->Cell(0, 10, "Domicilio: " . utf8_decode($regv->direccion), 0, 1);
    $pdf->Cell(0, 10, "Documento: " . $regv->tipo_documento . ": " . $regv->num_documento, 0, 1);
    $pdf->Cell(0, 10, "Email: " . $regv->email, 0, 1);
    $pdf->Cell(0, 10, "Teléfono: " . $regv->telefono, 0, 1);

    // Ahora vamos a agregar las columnas manualmente, en lugar de usar addCols()
    $pdf->SetXY(10, 120); // Posición inicial
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(23, 10, "CODIGO", 1, 0, 'C');
    $pdf->Cell(78, 10, "DESCRIPCION", 1, 0, 'C');
    $pdf->Cell(22, 10, "CANTIDAD", 1, 0, 'C');
    $pdf->Cell(25, 10, "P.U.", 1, 0, 'C');
    $pdf->Cell(20, 10, "DSCTO", 1, 0, 'C');
    $pdf->Cell(22, 10, "SUBTOTAL", 1, 1, 'C');

    // Establecemos los datos para las líneas de la venta
    $pdf->SetFont('Arial', '', 10);
    $y = 130; // Empezamos a agregar los detalles desde aquí

    // Obtenemos todos los detalles de la venta actual
    $rsptad = $venta->ventadetalle($_GET["id"]);
    
    while ($regd = $rsptad->fetch_object()) {
      $pdf->SetXY(10, $y); // Ajustamos la posición de cada línea de detalle
      $pdf->Cell(23, 10, $regd->codigo, 1, 0, 'C');
      $pdf->Cell(78, 10, utf8_decode($regd->articulo), 1, 0, 'L');
      $pdf->Cell(22, 10, $regd->cantidad, 1, 0, 'C');
      $pdf->Cell(25, 10, $regd->precio_venta, 1, 0, 'R');
      $pdf->Cell(20, 10, $regd->descuento, 1, 0, 'R');
      $pdf->Cell(22, 10, $regd->subtotal, 1, 1, 'R');
      
      $y += 10; // Avanzamos la posición para la siguiente línea
    }

    // Convertimos el total en letras
    require_once "Letras.php";
    $V = new EnLetras(); 
    $con_letra = strtoupper($V->ValorEnLetras($regv->total_venta, "NUEVOS SOLES"));

    // Mostramos el total en letras
    $pdf->SetXY(10, $y); // Ajustamos la posición de la siguiente línea
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, "Total en Letras: " . $con_letra, 0, 1);

    // Mostramos el impuesto y el total en números
    $pdf->SetXY(10, $y + 10); // Ajustamos la posición para los totales
    $pdf->Cell(100, 10, "Subtotal:", 0, 0, 'L');
    $pdf->Cell(30, 10, "S/ " . number_format($regv->total_venta - $regv->impuesto, 2), 0, 1, 'R');
    
    $pdf->Cell(100, 10, "IGV (" . $regv->impuesto . "%):", 0, 0, 'L');
    $pdf->Cell(30, 10, "S/ " . number_format($regv->impuesto, 2), 0, 1, 'R');
    
    $pdf->Cell(100, 10, "TOTAL:", 0, 0, 'L');
    $pdf->Cell(30, 10, "S/ " . number_format($regv->total_venta, 2), 0, 1, 'R');
    
    // Generamos el reporte
    $pdf->Output('Reporte de Venta', 'I');
  }
  else
  {
    echo 'No tiene permiso para visualizar el reporte';
  }
}

ob_end_flush();
?>