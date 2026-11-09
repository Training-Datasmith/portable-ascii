<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @var list<string> $OLD_API_NAMES */
const OLD_API_NAMES = [
    'getAllLanguages',
    'charsArray',
    'charsArrayWithMultiLanguageValues',
    'charsArrayWithOneLanguage',
    'charsArrayWithSingleLanguageValues',
];

/** @var list<string> $FORBIDDEN_LITERALS */
const FORBIDDEN_LITERALS = [
    'get_constants',
    'Open_SSL',
];

/**
 * @return array<string, true>
 */
function load_allowlist(string $path): array
{
    $allow = [];
    if (!is_readable($path)) {
        return $allow;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
            continue;
        }
        $allow[$line] = true;
    }

    return $allow;
}

function has_camel_case_boundary(string $ident): bool
{
    return (bool) preg_match('/[a-z][A-Z]/', $ident);
}

function allowlist_permits(array $allow, string $site, string $name, string $relativePath): bool
{
    $candidates = [
        "{$relativePath} {$site} {$name}",
        "{$site} {$name}",
    ];
    foreach ($candidates as $key) {
        if (isset($allow[$key])) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function scan_php_content(string $relativePath, string $code, array $allow): array
{
    $violations = [];
    $class = null;
    $function = null;
    $tokens = token_get_all($code);

    for ($i = 0, $n = count($tokens); $i < $n; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        $id = token_name($t[0]);
        $text = $t[1];
        $line = $t[2];

        if ($id === 'T_CLASS') {
            $j = $i + 1;
            while ($j < $n && is_array($tokens[$j]) && token_name($tokens[$j][0]) === 'T_WHITESPACE') {
                $j++;
            }
            if ($j < $n && is_array($tokens[$j]) && token_name($tokens[$j][0]) === 'T_STRING') {
                $name = $tokens[$j][1];
                $class = $name;
                if (has_camel_case_boundary($name)) {
                    $violations[] = "{$relativePath}:{$line}: repo class name {$name}";
                }
            }
        }

        if ($id === 'T_FUNCTION') {
            $j = $i + 1;
            while ($j < $n && is_array($tokens[$j]) && token_name($tokens[$j][0]) === 'T_WHITESPACE') {
                $j++;
            }
            if ($j < $n && is_array($tokens[$j]) && token_name($tokens[$j][0]) === 'T_STRING') {
                $function = $tokens[$j][1];
                if (has_camel_case_boundary($function)) {
                    $violations[] = "{$relativePath}:{$line}: repo function {$function}";
                }
            }
        }

        if ($id === 'T_VARIABLE') {
            $name = substr($text, 1);
            if (has_camel_case_boundary($name)) {
                $violations[] = "{$relativePath}:{$line}: repo variable \${$name}";
            }
        }

        if ($id === 'T_STRING' && has_camel_case_boundary($text) && $class !== null && $function !== null) {
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && token_name($tokens[$prev][0]) === 'T_WHITESPACE') {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && token_name($tokens[$prev][0]) === 'T_DOUBLE_COLON') {
                $prev2 = $prev - 1;
                while ($prev2 >= 0 && is_array($tokens[$prev2]) && token_name($tokens[$prev2][0]) === 'T_WHITESPACE') {
                    $prev2--;
                }
                $isStatic = $prev2 >= 0 && is_array($tokens[$prev2]) && token_name($tokens[$prev2][0]) === 'T_STATIC';
                if ($isStatic && !allowlist_permits($allow, "{$class}::{$function}", $text, $relativePath)) {
                    $violations[] = "{$relativePath}:{$line}: upstream call {$text} outside allowlist ({$class}::{$function} {$text})";
                }
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && token_name($tokens[$prev][0]) === 'T_OBJECT_OPERATOR') {
                if (!allowlist_permits($allow, "{$class}::{$function}", $text, $relativePath)) {
                    $violations[] = "{$relativePath}:{$line}: upstream call {$text} outside allowlist ({$class}::{$function} {$text})";
                }
            }
        }
    }

    foreach (explode("\n", $code) as $num => $lineText) {
        foreach (OLD_API_NAMES as $old) {
            if (strpos($lineText, $old) !== false) {
                $violations[] = $relativePath . ':' . ($num + 1) . ": old API name {$old}";
            }
        }
        foreach (FORBIDDEN_LITERALS as $bad) {
            if (strpos($lineText, $bad) !== false) {
                $violations[] = $relativePath . ':' . ($num + 1) . ": forbidden literal {$bad}";
            }
        }
    }

    return $violations;
}

/**
 * @return list<string>
 */
function scan_path_markdown(string $relativePath, string $content, array $allow): array
{
    $violations = [];
    $inAlternative = false;
    foreach (explode("\n", $content) as $num => $line) {
        if (preg_match('/^##\s+Alternative\s*$/', trim($line))) {
            $inAlternative = true;
        }
        if ($inAlternative && preg_match('/^##\s+/', trim($line)) && stripos($line, 'Alternative') === false) {
            $inAlternative = false;
        }
        foreach (OLD_API_NAMES as $old) {
            if (strpos($line, $old) !== false) {
                $violations[] = "{$relativePath}:" . ($num + 1) . ": old API name {$old}";
            }
        }
        foreach (FORBIDDEN_LITERALS as $bad) {
            if (strpos($line, $bad) !== false) {
                $violations[] = "{$relativePath}:" . ($num + 1) . ": forbidden literal {$bad}";
            }
        }
        if (preg_match('/->toTransliterate\b/', $line) && !$inAlternative) {
            $violations[] = "{$relativePath}:" . ($num + 1) . ': toTransliterate outside Alternative section';
        }
    }

    return $violations;
}

/**
 * @param array<string, true> $allow
 * @return list<string>
 */
function scan_repository(string $root, array $allow): array
{
    $violations = [];
    $paths = ['src', 'tests', 'examples', 'build'];
    foreach ($paths as $dir) {
        $full = $root . '/' . $dir;
        if (!is_dir($full)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (substr($file->getFilename(), -4) !== '.php') {
                continue;
            }
            $rel = substr($file->getPathname(), strlen($root) + 1);
            $code = file_get_contents($file->getPathname());
            if ($code === false) {
                continue;
            }
            $violations = array_merge($violations, scan_php_content($rel, $code, $allow));
        }
    }

    foreach (['README.md', 'ARCHITECTURE.md'] as $doc) {
        $full = $root . '/' . $doc;
        if (!is_readable($full)) {
            continue;
        }
        $content = file_get_contents($full);
        if ($content === false) {
            continue;
        }
        $violations = array_merge($violations, scan_path_markdown($doc, $content, $allow));
    }

    if (is_readable($root . '/appveyor.yml')) {
        $appveyor = file_get_contents($root . '/appveyor.yml');
        if ($appveyor !== false) {
            foreach (FORBIDDEN_LITERALS as $bad) {
                if (strpos($appveyor, $bad) !== false) {
                    $violations[] = "appveyor.yml: forbidden literal {$bad}";
                }
            }
        }
    }

    return $violations;
}

/**
 * @param array<string, true> $allow
 */
function run_allowlist_demo(array $allow): void
{
    $bad = <<<'PHP'
<?php
namespace voku\tests;
final class Demo_Bad extends \PHPUnit\Framework\TestCase
{
    public function test_demo()
    {
        static::assertSame(1, 1);
    }
}
PHP;

    $good = <<<'PHP'
<?php
namespace voku\tests;
final class Ascii_Test extends \PHPUnit\Framework\TestCase
{
    public function test_utf8()
    {
        static::assertFalse(false);
    }
}
PHP;

    $badViolations = scan_php_content('demo/bad.php', $bad, $allow);
    $goodViolations = array_filter(
        scan_php_content('tests/Ascii_Test.php', $good, $allow),
        static function ($v) {
            return strpos($v, 'assertFalse') !== false;
        }
    );

    if ($badViolations === []) {
        fwrite(STDERR, "Allowlist demo: expected violation for assertSame outside listed site.\n");
        exit(1);
    }
    if ($goodViolations !== []) {
        fwrite(STDERR, "Allowlist demo: assertSame wrongly flagged at allowed site.\n");
        exit(1);
    }

    fwrite(STDOUT, "allowlist demo: upstream name rejected outside listed site\n");
}

$allow = load_allowlist(__DIR__ . '/rename-allowlist.txt');
run_allowlist_demo($allow);

$violations = scan_repository($root, $allow);
$violations = array_values(array_unique($violations));

if ($violations !== []) {
    foreach ($violations as $violation) {
        fwrite(STDERR, $violation . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "rename check: no camelCase repo-owned names remain\n");
exit(0);
