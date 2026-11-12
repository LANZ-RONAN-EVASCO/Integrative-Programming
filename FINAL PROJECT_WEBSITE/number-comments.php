<?php
// Renumbers every comment in the project's .php and .js files as //1, //2, //3 ...
//
// Preview only (changes nothing):   php tools/number-comments.php
// Apply the changes:                php tools/number-comments.php --write
//
// Before a file is changed, the original is copied to a backup folder NEXT TO the project
// (barangay-landayan-comment-backup), so nothing is lost and the backup is never uploaded.

if (PHP_SAPI !== 'cli') { exit("Run this from the command line.\n"); }

const KEEP_TEXT = false;  // true  = keep the words:  //1 Original comment text
const PER_FILE  = false;  // true  = numbering starts again at 1 in every file
const SKIP_DIRS = ['.git', '.vscode', 'vendor', 'node_modules', 'uploads', 'logs', 'tools'];

$write = in_array('--write', $argv, true);

// ---------------------------------------------------------------- helpers

function plain_text(string $text): string
{
    $text = preg_replace('~^(/\*+|//+|\#+)~', '', $text);
    $text = preg_replace('~\*+/$~', '', $text);
    $text = preg_replace('~^\s*\*+ ?~m', '', $text);
    $text = preg_replace('~\s+~', ' ', $text);
    return str_replace(['*/', '?>'], ['', '? >'], trim($text));
}

// $inline = true means code follows on the same line, so a /*N*/ marker is used (a // would hide that code).
function marker(int $n, string $text, bool $inline): string
{
    $label = (string)$n;
    if (KEEP_TEXT) {
        $p = plain_text($text);
        if ($p !== '') { $label .= ' ' . $p; }
    }
    return $inline ? "/*$label*/" : "//$label";
}

function code_follows(array $tokens, int $i): bool
{
    for ($j = $i + 1, $c = count($tokens); $j < $c; $j++) {
        $t = $tokens[$j];
        if (is_array($t) && $t[0] === T_CLOSE_TAG) { return false; }
        $s = is_array($t) ? $t[1] : $t;
        $nl = strpos($s, "\n");
        $part = $nl === false ? $s : substr($s, 0, $nl);
        if (trim($part) !== '') { return true; }
        if ($nl !== false) { return false; }
    }
    return false;
}

// ---------------------------------------------------------------- PHP files (uses PHP's own tokenizer, so strings and URLs are never touched)

function number_php(string $code, int &$n): string
{
    $tokens = token_get_all($code);
    $out = '';

    foreach ($tokens as $i => $t) {
        if (!is_array($t) || ($t[0] !== T_COMMENT && $t[0] !== T_DOC_COMMENT)) {
            $out .= is_array($t) ? $t[1] : $t;
            continue;
        }

        $text = $t[1];
        $block = strncmp($text, '/*', 2) === 0;
        $trail = '';
        if (!$block && preg_match('/\R+\z/', $text, $m)) { $trail = $m[0]; }   // older PHP keeps the newline inside the comment

        $n++;
        $out .= marker($n, $text, $block && code_follows($tokens, $i)) . $trail;
    }
    return $out;
}

// ---------------------------------------------------------------- JS files (small scanner that skips strings and regex literals)

function number_js(string $code, int &$n): string
{
    $regexWords = ['return', 'typeof', 'case', 'delete', 'void', 'throw', 'in', 'of', 'new', 'else', 'do', 'instanceof', 'yield', 'await'];
    $len = strlen($code);
    $out = '';
    $sig = '';          // what came before, without comments, used to tell a regex from a division
    $i = 0;

    while ($i < $len) {
        $c = $code[$i];
        $d = $i + 1 < $len ? $code[$i + 1] : '';

        // Strings and template literals
        if ($c === "'" || $c === '"' || $c === '`') {
            $j = $i + 1;
            while ($j < $len && $code[$j] !== $c) {
                if ($code[$j] === '\\') { $j++; }
                $j++;
            }
            $j = min($j + 1, $len);
            $out .= substr($code, $i, $j - $i);
            $sig .= '"';
            $i = $j;
            continue;
        }

        // Line comment
        if ($c === '/' && $d === '/') {
            $j = strpos($code, "\n", $i);
            if ($j === false) { $j = $len; }
            $text = substr($code, $i, $j - $i);
            $cr = substr($text, -1) === "\r" ? "\r" : '';
            $n++;
            $out .= marker($n, $text, false) . $cr;
            $i = $j;
            continue;
        }

        // Block comment
        if ($c === '/' && $d === '*') {
            $j = strpos($code, '*/', $i + 2);
            $j = $j === false ? $len : $j + 2;
            $text = substr($code, $i, $j - $i);
            $eol = strpos($code, "\n", $j);
            $rest = substr($code, $j, ($eol === false ? $len : $eol) - $j);
            $n++;
            $out .= marker($n, $text, trim($rest) !== '');
            $i = $j;
            continue;
        }

        // Regex literal or division?
        if ($c === '/') {
            $tail = rtrim($sig);
            $last = $tail === '' ? '' : substr($tail, -1);
            $isRegex = $last === '' || strpos('(,=:[!&|?{};+-*%<>~^', $last) !== false;
            if (substr($tail, -2) === '++' || substr($tail, -2) === '--') { $isRegex = false; }
            if (!$isRegex && preg_match('/[A-Za-z_$][\w$]*$/', $tail, $m)) { $isRegex = in_array($m[0], $regexWords, true); }

            if ($isRegex) {
                $j = $i + 1;
                $class = false;
                while ($j < $len) {
                    $ch = $code[$j];
                    if ($ch === '\\') { $j += 2; continue; }
                    if ($ch === "\n") { break; }
                    if ($ch === '[') { $class = true; }
                    elseif ($ch === ']') { $class = false; }
                    elseif ($ch === '/' && !$class) { break; }
                    $j++;
                }
                if ($j < $len && $code[$j] === '/') {
                    $j++;
                    while ($j < $len && ctype_alpha($code[$j])) { $j++; }   // flags such as g, i
                    $out .= substr($code, $i, $j - $i);
                    $sig .= ')';
                    $i = $j;
                    continue;
                }
            }
        }

        // Anything else
        $out .= $c;
        if (ctype_space($c)) {
            if (substr($sig, -1) !== ' ') { $sig .= ' '; }
        } else {
            $sig .= $c;
        }
        $sig = substr($sig, -60);
        $i++;
    }
    return $out;
}

// ---------------------------------------------------------------- run

$root = realpath(__DIR__ . '/..');
$self = realpath(__FILE__);
$backup = dirname($root) . DIRECTORY_SEPARATOR . basename($root) . '-comment-backup';

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    function (SplFileInfo $cur) { return !($cur->isDir() && in_array($cur->getFilename(), SKIP_DIRS, true)); }
));
foreach ($it as $f) {
    if ($f->isFile() && in_array(strtolower($f->getExtension()), ['php', 'js'], true) && $f->getRealPath() !== $self) {
        $files[] = $f->getPathname();
    }
}
sort($files);

$n = 0;
$changedFiles = 0;
$problems = 0;

foreach ($files as $path) {
    $rel = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
    $src = file_get_contents($path);
    if (PER_FILE) { $n = 0; }
    $before = $n;

    $new = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'js' ? number_js($src, $n) : number_php($src, $n);
    $count = $n - $before;
    if ($count === 0) { continue; }

    $changedFiles++;
    printf("%5d comments  %s\n", $count, $rel);

    if ($write && $new !== $src) {
        $dest = $backup . DIRECTORY_SEPARATOR . $rel;
        if (!is_dir(dirname($dest))) { mkdir(dirname($dest), 0777, true); }
        if (!is_file($dest)) { copy($path, $dest); }            // never overwrite an existing backup
        file_put_contents($path, $new, LOCK_EX);

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $o, $rc);
            if ($rc !== 0) { $problems++; echo "        ^ SYNTAX CHECK FAILED, restore it from the backup folder\n"; }
        }
    }
}

echo "\n" . ($write ? 'DONE' : 'PREVIEW ONLY (nothing was changed)') . ": $n comments in $changedFiles files.\n";
if ($write) {
    echo "Originals saved in: $backup\n";
    echo $problems === 0 ? "All changed PHP files passed the syntax check.\n" : "$problems file(s) failed the syntax check, see above.\n";
} else {
    echo "To apply, run:  php tools/number-comments.php --write\n";
}
