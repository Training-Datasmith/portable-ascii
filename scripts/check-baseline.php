<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/check-baseline.php <junit-xml>\n");
    exit(2);
}

$junitPath = $argv[1];
if (!is_readable($junitPath)) {
    fwrite(STDERR, "Cannot read {$junitPath}\n");
    exit(2);
}

/** @var list<string> $expected */
$expected = require __DIR__ . '/baseline-upstream-tests.php';

$xml = new DOMDocument();
if (!$xml->load($junitPath)) {
    fwrite(STDERR, "Invalid JUnit XML\n");
    exit(2);
}

$xpath = new DOMXPath($xml);

$tests = 0;
$assertions = 0;
foreach ($xpath->query('//testsuite[@tests]') as $suite) {
    if (!$suite instanceof DOMElement) {
        continue;
    }
    $suiteTests = (int) $suite->getAttribute('tests');
    if ($suiteTests >= $tests) {
        $tests = $suiteTests;
        $assertions = (int) $suite->getAttribute('assertions');
    }
}

$skipped = (int) $xpath->evaluate('count(//skipped)');

$methodPresent = [];
foreach ($expected as $method) {
    $methodPresent[$method] = false;
}

foreach ($xpath->query('//testcase') as $case) {
    if (!$case instanceof DOMElement) {
        continue;
    }
    $class = $case->getAttribute('class');
    $name = $case->getAttribute('name');
    if ($class === '' || $name === '') {
        continue;
    }
    $shortClass = $class;
    if (strrpos($class, '\\') !== false) {
        $shortClass = substr($class, strrpos($class, '\\') + 1);
    }
    $plainName = $name;
    if (strpos($name, ' with data set ') !== false) {
        $plainName = strstr($name, ' with data set ', true);
    }
    $key = $shortClass . '::' . $plainName;
    if (isset($methodPresent[$key])) {
        $methodPresent[$key] = true;
    }
}

foreach ($xpath->query('//testsuite') as $suite) {
    if (!$suite instanceof DOMElement) {
        continue;
    }
    $name = $suite->getAttribute('name');
    if (strpos($name, '::') === false) {
        continue;
    }
    $parts = explode('\\', $name);
    $tail = end($parts);
    if (isset($methodPresent[$tail])) {
        $methodPresent[$tail] = true;
    }
}

$failures = [];
foreach ($methodPresent as $method => $present) {
    if (!$present) {
        $failures[] = "Missing baseline method: {$method}";
    }
}

if ($tests !== 236) {
    $failures[] = "Expected 236 tests, got {$tests}";
}
if ($assertions !== 1850) {
    $failures[] = "Expected 1850 assertions, got {$assertions}";
}
if ($skipped !== 0) {
    $failures[] = "Expected 0 skipped, got {$skipped}";
}

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "baseline check: 41 methods, 236 tests, 1850 assertions, 0 skipped\n");
exit(0);
