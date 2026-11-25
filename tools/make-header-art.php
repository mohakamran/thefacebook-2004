<?php
/**
 * Generates public/assets/header-art.svg: columns of binary digits that fade left-to-right,
 * brighter inside an abstract head-and-shoulders silhouette. Original artwork for this project.
 * Run: php tools/make-header-art.php
 */
mt_srand(2004);
$W = 360; $H = 80;
$out = [];
$out[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $W . '" height="' . $H . '" viewBox="0 0 ' . $W . ' ' . $H . '">';
$out[] = '<defs>
  <linearGradient id="fade" x1="0" x2="1" y1="0" y2="0">
    <stop offset="0" stop-color="#fff" stop-opacity="1"/>
    <stop offset="0.55" stop-color="#fff" stop-opacity="0.55"/>
    <stop offset="1" stop-color="#fff" stop-opacity="0"/>
  </linearGradient>
  <mask id="m"><rect width="' . $W . '" height="' . $H . '" fill="url(#fade)"/></mask>
  <clipPath id="sil"><path d="M58 8c13 0 22 10 22 24 0 9-4 16-9 20 2 4 6 6 14 9 12 4 18 10 20 19H8c2-9 8-15 20-19 8-3 12-5 14-9-5-4-9-11-9-20 0-14 9-24 25-24z"/></clipPath>
</defs>';
$out[] = '<rect width="' . $W . '" height="' . $H . '" fill="#3B5998"/>';
// faint silhouette fill
$out[] = '<g clip-path="url(#sil)"><rect width="' . $W . '" height="' . $H . '" fill="#6D84B4" opacity="0.55"/></g>';

$digits = function (float $opacity, int $step, string $clip = '') use ($W, $H): string {
    $s = '<g font-family="Courier New, monospace" font-size="7" fill="#D8DFEA" opacity="' . $opacity . '"' . ($clip ? ' clip-path="url(#' . $clip . ')"' : '') . '>';
    for ($y = 7; $y < $H; $y += 7) {
        $row = '';
        $x = mt_rand(0, 4);
        while ($x < $W) {
            $len = mt_rand(1, 5);
            $str = '';
            for ($i = 0; $i < $len; $i++) $str .= mt_rand(0, 1);
            $s .= '<text x="' . $x . '" y="' . $y . '">' . $str . '</text>';
            $x += $len * 4.3 + mt_rand(2, $step);
        }
    }
    return $s . '</g>';
};
$out[] = '<g mask="url(#m)">';
$out[] = $digits(0.35, 22);
$out[] = $digits(0.95, 3, 'sil');
$out[] = '</g>';
$out[] = '</svg>';
file_put_contents(__DIR__ . '/../public/assets/header-art.svg', implode("\n", $out) . "\n");
echo "wrote header-art.svg\n";
