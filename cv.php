<?php
/**
 * Serves the active CV file uploaded via the admin File Library.
 * Falls back to legacy /assets/files/anik-sen-cv.pdf or a generated placeholder.
 */
declare(strict_types=1);

require __DIR__ . "/bootstrap.php";

use App\FileLibrary;

$config = $GLOBALS["APP_CONFIG"];

$cv = FileLibrary::activeCv();

if ($cv) {
    $path = $config["paths"]["doc_dir"] . "/" . $cv["filename"];
    if (is_file($path)) {
        $mime = $cv["mime"] ?: "application/octet-stream";
        $download = $cv["original_name"] ?: $cv["filename"];
        header("Content-Type: " . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($download) . '"');
        header("Content-Length: " . (string) filesize($path));
        header("Cache-Control: no-cache, must-revalidate");
        readfile($path);
        exit;
    }
}

$legacy = __DIR__ . "/assets/files/anik-sen-cv.pdf";
header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="Anik-Sen-CV.pdf"');
header("Cache-Control: no-cache, must-revalidate");

if (is_file($legacy)) {
    header("Content-Length: " . (string) filesize($legacy));
    readfile($legacy);
    exit;
}

/* ---------- minimal placeholder PDF ---------- */
function pdfEscape(string $s): string {
    return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $s);
}

$content = "BT\n/F2 30 Tf\n72 740 Td\n(" . pdfEscape("Anik Sen") . ") Tj\n"
    . "0 -34 Td\n/F1 16 Tf\n(" . pdfEscape("Professional Video Editor & Graphic Designer") . ") Tj\n"
    . "0 -28 Td\n/F1 11 Tf\n(" . pdfEscape("Bangladesh  -  Crafting visuals since 2020") . ") Tj\n"
    . "0 -48 Td\n/F2 14 Tf\n(" . pdfEscape("Resume") . ") Tj\n"
    . "0 -22 Td\n/F1 11 Tf\n(" . pdfEscape("Upload your real CV from the Admin -> File Library and mark it Active.") . ") Tj\nET";

$objects = [
    "<< /Type /Catalog /Pages 2 0 R >>",
    "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
    "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 6 0 R "
        . "/Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> >>",
    "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
    "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>",
    "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
];

$pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
$offsets = [];
foreach ($objects as $i => $obj) {
    $offsets[] = strlen($pdf);
    $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
}
$xrefPos = strlen($pdf);
$total   = count($objects) + 1;
$pdf    .= "xref\n0 {$total}\n0000000000 65535 f \n";
foreach ($offsets as $off) {
    $pdf .= sprintf("%010d 00000 n \n", $off);
}
$pdf .= "trailer\n<< /Size {$total} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

header("Content-Length: " . (string) strlen($pdf));
echo $pdf;
