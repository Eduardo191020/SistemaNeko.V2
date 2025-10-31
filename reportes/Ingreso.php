<?php
require('../fpdf181/fpdf.php');
define('EURO', chr(128));
define('EURO_VAL', 6.55957);

class PDF_Invoice extends FPDF
{
    // private variables
    var $colonnes;
    var $format;
    var $angle = 0;

    // private functions
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));

        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k, $x3 * $this->k, ($h - $y3) * $this->k));
    }

    function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    // public functions
    function sizeOfText($texte, $largeur)
    {
        $nb_lines = 0;
        $loop = TRUE;
        while ($loop) {
            $pos = strpos($texte, "\n");
            if ($pos === false) {
                $loop = FALSE;
                $ligne = $texte;
            } else {
                $ligne = substr($texte, 0, $pos);
                $texte = substr($texte, $pos + 1);
            }
            $length = floor($this->GetStringWidth($ligne));
            $res = 1 + floor($length / $largeur);
            $nb_lines += $res;
        }
        return $nb_lines;
    }

    // Company
    function addSociete($nom, $adresse, $logo, $ext_logo)
    {
        $x1 = 30;
        $y1 = 8;
        $this->Image($logo, 5, 3, 25, 25, $ext_logo);
        $this->SetXY($x1, $y1);
        $this->SetFont('Arial', 'B', 12);
        $length = $this->GetStringWidth($nom);
        $this->Cell($length, 2, $nom);
        $this->SetXY($x1, $y1 + 4);
        $this->SetFont('Arial', '', 10);
        $length = $this->GetStringWidth($adresse);
        $lignes = $this->sizeOfText($adresse, $length);
        $this->MultiCell($length, 4, $adresse);
    }

    // Label and number of invoice/estimate
    function fact_dev($libelle, $num)
    {
        $r1 = $this->w - 80;
        $r2 = $r1 + 68;
        $y1 = 6;
        $y2 = $y1 + 2;
        $mid = ($r1 + $r2) / 2;

        $texte = $libelle . $num;
        $szfont = 12;

        while (true) {
            $this->SetFont("Arial", "B", $szfont);
            $sz = $this->GetStringWidth($texte);
            if (($r1 + $sz) > $r2) $szfont--;
            else break;
        }

        $this->SetLineWidth(0.1);
        $this->SetFillColor(72, 209, 204);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 2.5, 'DF');
        $this->SetXY($r1 + 1, $y1 + 2);
        $this->Cell($r2 - $r1 - 1, 5, $texte, 0, 0, "C");
    }

    function addDevis($numdev)
    {
        $string = sprintf("DEV%04d", $numdev);
        $this->fact_dev("Devis", $string);
    }

    function addFacture($numfact)
    {
        $string = sprintf("FA%04d", $numfact);
        $this->fact_dev("Facture", $string);
    }

    function addDate($date)
    {
        $r1 = $this->w - 61;
        $r2 = $r1 + 49;
        $y1 = 17;
        $y2 = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 3);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 5, "Fecha", 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 9);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $date, 0, 0, "C");
    }

    function addClient($ref)
    {
        $r1 = $this->w - 31;
        $r2 = $r1 + 19;
        $y1 = 17;
        $y2 = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 3);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 5, "CLIENT", 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 9);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $ref, 0, 0, "C");
    }

    function addPageNumber($page)
    {
        $r1 = $this->w - 80;
        $r2 = $r1 + 19;
        $y1 = 17;
        $y2 = $y1;
        $mid = $y1 + ($y2 / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 3.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 3);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 5, "PAGE", 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 9);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $page, 0, 0, "C");
    }

    // Client address
    function addClientAdresse($cliente, $domicilio, $num_documento, $email, $telefono)
    {
        $r1 = $this->w - 180;
        $r2 = $r1 + 68;
        $y1 = 40;
        $this->SetXY($r1, $y1);
        $this->SetFont("Arial", "B", 10);
        $this->MultiCell(60, 4, "Proveedor");
        $this->SetXY($r1, $y1 + 5);
        $this->SetFont("Arial", "", 10);
        $this->MultiCell(150, 4, $cliente);
        $this->SetXY($r1, $y1 + 10);
        $this->MultiCell(150, 4, $domicilio);
        $this->SetXY($r1, $y1 + 15);
        $this->MultiCell(150, 4, $num_documento);
        $this->SetXY($r1, $y1 + 20);
        $this->MultiCell(150, 4, $email);
        $this->SetXY($r1, $y1 + 25);
        $this->MultiCell(150, 4, $telefono);
    }

    // Mode of payment
    function addReglement($mode)
    {
        $r1 = 10;
        $r2 = $r1 + 60;
        $y1 = 80;
        $y2 = $y1 + 10;
        $mid = $y1 + (($y2 - $y1) / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2 - $y1), 2.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 1);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 4, "PROVEEDOR", 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 5);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $mode, 0, 0, "C");
    }

    // Expiry date
    function addEcheance($documento, $numero)
    {
        $r1 = 80;
        $r2 = $r1 + 40;
        $y1 = 80;
        $y2 = $y1 + 10;
        $mid = $y1 + (($y2 - $y1) / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2 - $y1), 2.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 1);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 4, $numero, 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 5);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $numero, 0, 0, "C");
    }

    // VAT number
    function addNumTVA($tva)
    {
        $this->SetFont("Arial", "B", 10);
        $r1 = $this->w - 80;
        $r2 = $r1 + 70;
        $y1 = 80;
        $y2 = $y1 + 10;
        $mid = $y1 + (($y2 - $y1) / 2);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2 - $y1), 2.5, 'D');
        $this->Line($r1, $mid, $r2, $mid);
        $this->SetXY($r1 + 16, $y1 + 1);
        $this->Cell(40, 4, "DIRECCIÓN", '', '', "C");
        $this->SetFont("Arial", "", 10);
        $this->SetXY($r1 + 16, $y1 + 5);
        $this->Cell(40, 5, $tva, '', '', "C");
    }

    function addReference($ref)
    {
        $this->SetFont("Arial", "", 10);
        $length = $this->GetStringWidth("Références : " . $ref);
        $r1 = 10;
        $r2 = $r1 + $length;
        $y1 = 92;
        $y2 = $y1 + 5;
        $this->SetXY($r1, $y1);
        $this->Cell($length, 4, "Références : " . $ref);
    }

    function addCols($tab)
    {
        global $colonnes;

        $r1 = 10;
        $r2 = $this->w - ($r1 * 2);
        $y1 = 79;
        $y2 = $this->h - 50 - $y1;

        $this->SetXY($r1, $y1);
        $this->Rect($r1, $y1, $r2, $y2, "D");
        $this->Line($r1, $y1 + 6, $r1 + $r2, $y1 + 6);

        $colX = $r1;
        $colonnes = $tab;

        foreach ($tab as $lib => $pos) {
            $this->SetXY($colX, $y1 + 2);
            $this->Cell($pos, 1, $lib, 0, 0, "C");
            $colX += $pos;
            $this->Line($colX, $y1, $colX, $y1 + $y2);
        }
    }

    function addLineFormat($tab)
    {
        global $format, $colonnes;

        foreach ($colonnes as $lib => $pos) {
            if (isset($tab[$lib])) {
                $format[$lib] = $tab[$lib];
            }
        }
    }

    function lineVert($tab)
    {
        global $colonnes;

        $maxSize = 0;

        foreach ($colonnes as $lib => $pos) {
            $texte = isset($tab[$lib]) ? $tab[$lib] : '';
            $longCell = $pos - 2;
            $size = $this->sizeOfText($texte, $longCell);
            if ($size > $maxSize) {
                $maxSize = $size;
            }
        }

        return $maxSize;
    }

    // add a line to the invoice/estimate
    function addLine($ligne, $tab)
    {
        global $colonnes, $format;

        $ordonnee = 10;
        $maxSize = $ligne;

        foreach ($colonnes as $lib => $pos) {
            $longCell = $pos - 2;
            $texte = isset($tab[$lib]) ? $tab[$lib] : '';
            $formText = isset($format[$lib]) ? $format[$lib] : 'L';

            $this->SetXY($ordonnee, $ligne - 1);
            $this->MultiCell($longCell, 4, $texte, 0, $formText);

            if ($maxSize < $this->GetY()) {
                $maxSize = $this->GetY();
            }
            $ordonnee += $pos;
        }
        return ($maxSize - $ligne);
    }

    function addRemarque($remarque)
    {
        $this->SetFont("Arial", "", 10);
        $length = $this->GetStringWidth("Remarque : " . $remarque);
        $r1 = 10;
        $r2 = $r1 + $length;
        $y1 = $this->h - 45.5;
        $y2 = $y1 + 5;
        $this->SetXY($r1, $y1);
        $this->Cell($length, 4, "Remarque : " . $remarque);
    }

    function addCadreTVAs($monto)
    {
        $this->SetFont("Arial", "B", 8);
        $r1 = 10;
        $r2 = $r1 + 120;
        $y1 = $this->h - 40;
        $y2 = $y1 + 20;
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2 - $y1), 2.5, 'D');
        $this->SetXY($r1 + 9, $y1 + 3);
        $this->Cell(10, 4, "IMPORTE TOTAL CON LETRA");
        $this->SetFont("Arial", "", 8);
        $this->SetXY($r1 + 9, $y1 + 7);
        $this->MultiCell(100, 4, $monto);
    }

    function addCadreEurosFrancs($impuesto)
    {
        $r1 = $this->w - 70;
        $r2 = $r1 + 60;
        $y1 = $this->h - 40;
        $y2 = $y1 + 20;
        $this->RoundedRect($r1, $y1, ($r2 - $r1), ($y2 - $y1), 2.5, 'D');
        $this->Line($r1 + 20, $y1, $r1 + 20, $y2);
        $this->SetFont("Arial", "B", 8);
        $this->SetXY($r1 + 22, $y1);
        $this->Cell(15, 4, "TOTALES", 0, 0, "C");
        $this->SetFont("Arial", "", 8);
        $this->SetFont("Arial", "B", 6);
        $this->SetXY($r1, $y1 + 5);
        $this->Cell(20, 4, "SUBTOTAL", 0, 0, "C");
        $this->SetXY($r1, $y1 + 10);
        $this->Cell(20, 4, $impuesto, 0, 0, "C");
        $this->SetXY($r1, $y1 + 15);
        $this->Cell(20, 4, "TOTAL A PAGAR", 0, 0, "C");
    }

    function addTVAs($igv, $total, $moneda)
    {
        $this->SetFont('Arial', '', 8);

        $re = $this->w - 30;
        $rf = $this->w - 29;
        $y1 = $this->h - 40;
        $this->SetFont("Arial", "", 8);
        $this->SetXY($re, $y1 + 5);
        $this->Cell(17, 4, $moneda . sprintf("%0.2F", $total - ($total * $igv / ($igv + 100))), '', '', 'R');
        $this->SetXY($re, $y1 + 10);
        $this->Cell(17, 4, $moneda . sprintf("%0.2F", ($total * $igv / ($igv + 100))), '', '', 'R');
        $this->SetXY($re, $y1 + 14.8);
        $this->Cell(17, 4, $moneda . sprintf("%0.2F", $total), '', '', 'R');
    }

    // watermark
    function temporaire($texte)
    {
        $this->SetFont('Arial', 'B', 50);
        $this->SetTextColor(203, 203, 203);
        $this->Rotate(45, 55, 190);
        $this->Text(55, 190, $texte);
        $this->Rotate(0);
        $this->SetTextColor(0, 0, 0);
    }
}
?>

