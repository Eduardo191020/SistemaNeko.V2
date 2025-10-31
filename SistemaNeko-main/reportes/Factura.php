<?php
require('../fpdf181/fpdf.php');

define('EURO', chr(128));
define('EURO_VAL', 6.55957);

class PDF_Invoice extends FPDF
{
    var $colonnes;
    var $format;
    var $angle = 0;

    // Dibuja un rectángulo con bordes redondeados
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';

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
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', 
            $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k,
            $x3 * $this->k, ($h - $y3) * $this->k
        ));
    }

    function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
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

    // Devuelve cantidad de líneas de texto
    function sizeOfText(string $texte, float $largeur): int
    {
        $nb_lines = 0;
        $lines = explode("\n", $texte);
        foreach ($lines as $ligne) {
            $length = floor($this->GetStringWidth($ligne));
            $res = 1 + floor($length / $largeur);
            $nb_lines += $res;
        }
        return $nb_lines;
    }

    function addSociete(string $nom, string $adresse, string $logo, string $ext_logo)
    {
        $x1 = 30;
        $y1 = 8;
        $this->Image($logo, 5, 3, 25, 25, $ext_logo);
        $this->SetXY($x1, $y1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell($this->GetStringWidth($nom), 2, $nom);
        $this->SetXY($x1, $y1 + 4);
        $this->SetFont('Arial', '', 10);
        $length = $this->GetStringWidth($adresse);
        $lignes = $this->sizeOfText($adresse, $length);
        $this->MultiCell($length, 4, $adresse);
    }

    function fact_dev(string $libelle, string $num)
    {
        $r1 = $this->w - 80;
        $r2 = $r1 + 68;
        $y1 = 6;
        $y2 = $y1 + 2;
        $texte = $libelle . $num;
        $szfont = 12;
        do {
            $this->SetFont("Arial", "B", $szfont);
            $sz = $this->GetStringWidth($texte);
            $szfont--;
        } while (($r1 + $sz) > $r2);

        $this->SetLineWidth(0.1);
        $this->SetFillColor(72, 209, 204);
        $this->RoundedRect($r1, $y1, ($r2 - $r1), $y2, 2.5, 'DF');
        $this->SetXY($r1 + 1, $y1 + 2);
        $this->Cell($r2 - $r1 - 1, 5, $texte, 0, 0, "C");
    }

    function addDevis(int $numdev)
    {
        $this->fact_dev("Devis", sprintf("DEV%04d", $numdev));
    }

    function addFacture(int $numfact)
    {
        $this->fact_dev("Facture", sprintf("FA%04d", $numfact));
    }

    function addDate(string $date)
    {
        $r1 = $this->w - 61;
        $r2 = $r1 + 49;
        $y1 = 17;
        $mid = $y1 + 2;

        $this->RoundedRect($r1, $y1, ($r2 - $r1), 12, 3.5, 'D');
        $this->Line($r1, $mid + 3, $r2, $mid + 3);
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 3);
        $this->SetFont("Arial", "B", 10);
        $this->Cell(10, 5, "Fecha", 0, 0, "C");
        $this->SetXY($r1 + ($r2 - $r1) / 2 - 5, $y1 + 9);
        $this->SetFont("Arial", "", 10);
        $this->Cell(10, 5, $date, 0, 0, "C");
    }
}