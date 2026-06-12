<?php
/**
 * Certificate PDF Generator — Pure PHP, zero dependencies
 * Generates real binary PDF (ISO-8859-1 encoded, Helvetica built-in font)
 */
class CertificatePDF {

    private const W = 595.28;
    private const H = 841.89;
    private const M = 40.0;

    // === PUBLIC ===

    public static function generate(array $product, array $history, string $orgName, string $userEmail): void {
        $cert_date = date('j F Y a H\hi', strtotime($product['cert_updated_at'] ?? 'now'));
        $add_date  = date('j F Y', strtotime($product['created_at'] ?? 'now'));
        $mod_count = count($history);

        $filename = 'certificat_' . preg_replace('/[^a-z0-9]/i', '-', $product['name']) . '_' . date('Ymd') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf = new self();
        echo $pdf->build($product, $history, $orgName, $userEmail, $add_date, $cert_date, $mod_count);
        exit;
    }

    // Legacy stub kept for any external reference
    public static function buildHTML(array $product, array $history, string $orgName, string $userEmail, string $add_date, string $cert_date, int $mod_count): string {
        return '';
    }

    // === BUILD ===

    private function build(array $product, array $history, string $orgName, string $userEmail, string $add_date, string $cert_date, int $mod_count): string {
        $p1 = $this->page1($product, $orgName, $userEmail, $add_date, $cert_date, $mod_count);
        $p2 = $this->page2($product, $history, $mod_count);
        return $this->assemblePDF([$p1, $p2]);
    }

    // === PAGE 1: CERTIFICATE ===

    private function page1(array $product, string $orgName, string $userEmail, string $add_date, string $cert_date, int $mod_count): string {
        $W = self::W; $H = self::H; $m = self::M;
        $c = '';

        // Top accent bar
        $c .= $this->rgb(107, 110, 249);
        $c .= $this->fillRect($m, 22, $W - 2 * $m, 4);

        // MULTIAPP title
        $c .= $this->rgb(107, 110, 249);
        $c .= $this->textCenter('MULTIAPP', 68, 'HB', 26);

        // Subtitle
        $c .= $this->rgb(51, 51, 51);
        $c .= $this->textCenter("CERTIFICAT D'AFFICHAGE", 88, 'HB', 13);

        // Badge pill
        $bw = 112.0; $bh = 16.0; $bx = ($W - $bw) / 2.0;
        $c .= $this->rgb(107, 110, 249);
        $c .= $this->fillRect($bx, 95, $bw, $bh);
        $c .= $this->rgb(255, 255, 255);
        $c .= $this->textCenter('Borne Numerique', 107, 'HR', 9);

        // Section: Product info
        $y = 137;
        $c .= $this->sectionTitle('INFORMATIONS DU PRODUIT', $y, $m);
        $y += 24;

        $status = $product['status'] === 'active' ? 'Actif' : 'Inactif';
        $rows = [
            ["Titre :",         $this->enc($product['name'])],
            ["Date d'ajout :",  $this->enc($add_date)],
            ["Statut :",        $status],
            ["Modifie :",       $mod_count . ' fois'],
        ];
        foreach ($rows as [$lbl, $val]) {
            $c .= $this->rgb(120, 120, 120);
            $c .= $this->textAt($m, $y, $lbl, 'HR', 10);
            $c .= $this->rgb(30, 30, 30);
            $c .= $this->textAt($m + 150, $y, $val, 'HB', 10);
            $y += 18;
        }

        // Signature box
        $y += 14;
        $boxH = 102.0;
        $c .= $this->rgb(249, 249, 249);
        $c .= $this->fillRect($m, $y, $W - 2 * $m, $boxH);
        $c .= $this->strokeColor(220, 220, 220);
        $c .= $this->strokeRect($m, $y, $W - 2 * $m, $boxH);

        $c .= $this->sectionTitle('SIGNATURE', $y + 16, $m + 14);
        $c .= $this->rgb(100, 100, 100);
        $c .= $this->textAt($m + 14, $y + 38, 'Le document a ete ajoute par :', 'HR', 10);
        $c .= $this->rgb(30, 30, 30);
        $c .= $this->textAt($m + 14, $y + 56, $this->enc($userEmail), 'HB', 11);
        $c .= $this->rgb(100, 100, 100);
        $c .= $this->textAt($m + 14, $y + 72, 'Fait pour servir et valoir ce que de droit.', 'HR', 9);
        $c .= $this->textAt($m + 14, $y + 87, $this->enc('Certificat genere le ' . $cert_date), 'HR', 9);

        // Footer
        $c .= $this->rgb(180, 180, 180);
        $c .= $this->textRight($this->enc('(c) ' . date('Y') . ' MediaPush -- ' . $orgName), $W - $m, $H - 25, 'HR', 8);

        return $c;
    }

    // === PAGE 2: HISTORY ===

    private function page2(array $product, array $history, int $mod_count): string {
        $W = self::W; $H = self::H; $m = self::M;
        $c = '';

        $c .= $this->rgb(30, 30, 30);
        $c .= $this->textAt($m, 50, 'Historique des modifications', 'HB', 13);
        $prodShort = '"' . substr($this->enc($product['name']), 0, 48) . '"';
        $c .= $this->textAt($m, 66, $prodShort, 'HB', 11);
        $c .= $this->rgb(130, 130, 130);
        $c .= $this->textAt($m, 82, $mod_count . ' modifications apportees au cours du temps', 'HR', 10);

        $cols  = [80, 100, 90, 65, 90, 90];
        $heads = ['Date/heure', 'Entite', 'Utilisateur', 'Champ', 'Ancienne val.', 'Nouvelle val.'];

        // Header row
        $y = 100; $x = $m;
        foreach ($cols as $i => $cw) {
            $c .= $this->rgb(107, 110, 249);
            $c .= $this->fillRect($x, $y, $cw, 15);
            $c .= $this->rgb(255, 255, 255);
            $c .= $this->textAt($x + 3, $y + 11, $heads[$i], 'HB', 8);
            $x += $cw;
        }
        $y += 15;

        // Data rows
        foreach ($history as $i => $h) {
            if ($y > $H - 55) break;
            $date  = date('d/m/Y H:i', strtotime($h['created_at']));
            $ent   = substr($this->enc($product['name']), 0, 12);
            $user  = substr($this->enc($h['user_email'] ?? ''), 0, 18);
            $field = substr($this->enc($h['field_name'] ?? 'Creation'), 0, 12);
            $old   = substr($this->enc($h['old_value'] ?? '-'), 0, 16);
            $new   = substr($this->enc($h['new_value'] ?? $product['name']), 0, 16);
            $row   = [$date, $ent, $user, $field, $old, $new];
            $bg    = ($i % 2 === 1) ? [245, 245, 248] : [255, 255, 255];

            $x = $m;
            foreach ($cols as $j => $cw) {
                $c .= $this->rgb(...$bg);
                $c .= $this->fillRect($x, $y, $cw, 13);
                $c .= $this->rgb(30, 30, 30);
                $c .= $this->textAt($x + 3, $y + 10, $row[$j], 'HR', 8);
                $x += $cw;
            }
            $y += 13;
        }

        $c .= $this->rgb(180, 180, 180);
        $c .= $this->textCenter("Certificat -- historique des modifications (total : {$mod_count})", $H - 25, 'HR', 8);

        return $c;
    }

    // === PRIMITIVES ===

    private function enc(string $s): string {
        $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        }
        return utf8_decode($s);
    }

    private function esc(string $s): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    private function rgb(int $r, int $g, int $b): string {
        return sprintf("%.4f %.4f %.4f rg\n", $r / 255, $g / 255, $b / 255);
    }

    private function strokeColor(int $r, int $g, int $b): string {
        return sprintf("%.4f %.4f %.4f RG\n", $r / 255, $g / 255, $b / 255);
    }

    private function fillRect(float $x, float $y, float $w, float $h): string {
        $py = self::H - $y - $h;
        return sprintf("%.2f %.2f %.2f %.2f re f\n", $x, $py, $w, $h);
    }

    private function strokeRect(float $x, float $y, float $w, float $h): string {
        $py = self::H - $y - $h;
        return sprintf("%.2f %.2f %.2f %.2f re S\n", $x, $py, $w, $h);
    }

    private function textAt(float $x, float $y, string $txt, string $font, float $size): string {
        if ($txt === '') return '';
        $py = self::H - $y;
        return "BT /{$font} {$size} Tf " . sprintf("%.2f %.2f", $x, $py) . " Td (" . $this->esc($txt) . ") Tj ET\n";
    }

    private function textCenter(string $txt, float $y, string $font, float $size): string {
        if ($txt === '') return '';
        $tw = strlen($txt) * $size * 0.52;
        return $this->textAt((self::W - $tw) / 2.0, $y, $txt, $font, $size);
    }

    private function textRight(string $txt, float $rx, float $y, string $font, float $size): string {
        if ($txt === '') return '';
        $tw = strlen($txt) * $size * 0.52;
        return $this->textAt($rx - $tw, $y, $txt, $font, $size);
    }

    private function sectionTitle(string $title, float $y, float $x): string {
        $c  = $this->rgb(107, 110, 249);
        $c .= $this->textAt($x, $y, strtoupper($title), 'HB', 9);
        $lineY = self::H - ($y + 4);
        $c .= $this->strokeColor(200, 200, 200);
        $c .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x, $lineY, self::W - $x, $lineY);
        return $c;
    }

    // === PDF ASSEMBLY ===

    private function assemblePDF(array $streams): string {
        // Fixed object layout for 2-page document:
        // 1: Font Helvetica Regular   2: Font Helvetica Bold
        // 3: Page 1 stream            4: Page 2 stream
        // 5: Page 1 object            6: Page 2 object
        // 7: Pages dictionary         8: Catalog

        $res = "<< /Font << /HR 1 0 R /HB 2 0 R >> >>";
        $box = "[0 0 595 842]";

        $parts = [
            1 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>",
            2 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>",
        ];

        foreach ($streams as $i => $stream) {
            $parts[$i + 3] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";
        }

        $parts[5] = "<< /Type /Page /Parent 7 0 R /MediaBox {$box} /Contents 3 0 R /Resources {$res} >>";
        $parts[6] = "<< /Type /Page /Parent 7 0 R /MediaBox {$box} /Contents 4 0 R /Resources {$res} >>";
        $parts[7] = "<< /Type /Pages /Kids [5 0 R 6 0 R] /Count 2 >>";
        $parts[8] = "<< /Type /Catalog /Pages 7 0 R >>";

        $out = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $offsets = [];

        for ($n = 1; $n <= 8; $n++) {
            $offsets[$n] = strlen($out);
            $out .= "{$n} 0 obj\n{$parts[$n]}\nendobj\n";
        }

        $xref = strlen($out);
        $out .= "xref\n0 9\n";
        $out .= "0000000000 65535 f \n";
        for ($n = 1; $n <= 8; $n++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $out .= "trailer\n<< /Size 9 /Root 8 0 R >>\n";
        $out .= "startxref\n{$xref}\n%%EOF\n";

        return $out;
    }
}
