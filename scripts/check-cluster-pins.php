<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @var array<string, string> $expected */
$expected = [];

/**
 * @param array<string, mixed> $installedPhp
 * @param array<string, mixed> $installedJson
 * @param array<string, string> $expectedMap
 * @return list<array{side: string, package: string, message: string}>
 */
function evaluate_cluster_pins(array $installedPhp, array $installedJson, array $expectedMap): array
{
    $errors = [];

    $versions = $installedPhp['versions'] ?? [];
    $jsonPackages = [];
    foreach (array_merge($installedJson['packages'] ?? [], $installedJson['packages-dev'] ?? []) as $package) {
        if (!isset($package['name'], $package['source']['reference'])) {
            continue;
        }
        $jsonPackages[$package['name']] = strtolower((string) $package['source']['reference']);
    }

    foreach ($expectedMap as $package => $sha) {
        $want = strtolower($sha);
        if (!isset($versions[$package])) {
            $errors[] = ['side' => 'installed.php', 'package' => $package, 'message' => 'package is not installed'];
            continue;
        }
        $entry = $versions[$package];
        $reference = isset($entry['reference']) ? strtolower((string) $entry['reference']) : '';
        if ($reference === '') {
            $errors[] = ['side' => 'installed.php', 'package' => $package, 'message' => 'missing reference'];
        } elseif ($reference !== $want) {
            $errors[] = ['side' => 'installed.php', 'package' => $package, 'message' => "reference {$reference} != expected {$want}"];
        }
        $aliases = $entry['aliases'] ?? [];
        if ($aliases === [] && strpos($package, '/') !== false) {
            // aliases optional when not using inline alias
        }
        if (!isset($jsonPackages[$package])) {
            $errors[] = ['side' => 'installed.json', 'package' => $package, 'message' => 'package is not installed'];
            continue;
        }
        if ($jsonPackages[$package] !== $want) {
            $errors[] = [
                'side' => 'installed.json',
                'package' => $package,
                'message' => "source.reference {$jsonPackages[$package]} != expected {$want}",
            ];
        }
    }

    foreach ($jsonPackages as $name => $_ref) {
        foreach (array_merge($installedJson['packages'] ?? [], $installedJson['packages-dev'] ?? []) as $package) {
            if (($package['name'] ?? '') !== $name) {
                continue;
            }
            $url = (string) ($package['source']['url'] ?? '');
            if (stripos($url, 'github.com/Training-Datasmith/') !== false && !isset($expectedMap[$name])) {
                $errors[] = [
                    'side' => 'guard',
                    'package' => $name,
                    'message' => 'Training-Datasmith source installed but not listed in expected map',
                ];
            }
        }
    }

    return $errors;
}

/**
 * @return array{installedPhp: array<string, mixed>, installedJson: array<string, mixed>}
 */
function load_vendor_pin_sources(string $root): array
{
    $installedPhpPath = $root . '/vendor/composer/installed.php';
    $installedJsonPath = $root . '/vendor/composer/installed.json';
    if (!is_readable($installedPhpPath)) {
        fwrite(STDERR, "Missing {$installedPhpPath}; run composer update first.\n");
        exit(1);
    }
    if (!is_readable($installedJsonPath)) {
        fwrite(STDERR, "Missing {$installedJsonPath}; run composer update first.\n");
        exit(1);
    }

    /** @var array<string, mixed> $installedPhp */
    $installedPhp = require $installedPhpPath;
    $raw = file_get_contents($installedJsonPath);
    if ($raw === false) {
        fwrite(STDERR, "Could not read {$installedJsonPath}.\n");
        exit(1);
    }
    /** @var array<string, mixed>|null $installedJson */
    $installedJson = json_decode($raw, true);
    if (!is_array($installedJson)) {
        fwrite(STDERR, "Invalid JSON in {$installedJsonPath}.\n");
        exit(1);
    }

    return ['installedPhp' => $installedPhp, 'installedJson' => $installedJson];
}

/**
 * @return array{installedPhp: array<string, mixed>, installedJson: array<string, mixed>}
 */
function demo_fixture(): array
{
    $sha = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    $installedPhp = [
        'root' => __DIR__,
        'versions' => [
            'demo/pkg' => [
                'pretty_version' => 'dev-demo as 1.2.3',
                'version' => 'dev-demo',
                'reference' => $sha,
                'aliases' => ['1.2.3'],
            ],
        ],
    ];
    $installedJson = [
        'packages' => [],
        'packages-dev' => [
            [
                'name' => 'demo/pkg',
                'version' => 'dev-demo',
                'source' => [
                    'type' => 'git',
                    'url' => 'https://github.com/example/demo.git',
                    'reference' => $sha,
                ],
            ],
        ],
    ];

    return ['installedPhp' => $installedPhp, 'installedJson' => $installedJson];
}

function run_wrong_sha_demos(): void
{
    $fixture = demo_fixture();
    $map = ['demo/pkg' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'];

    $phpWrong = $fixture;
    $phpWrong['installedPhp']['versions']['demo/pkg']['reference'] = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    $errsPhp = evaluate_cluster_pins($phpWrong['installedPhp'], $phpWrong['installedJson'], $map);
    $phpSides = array_unique(array_column($errsPhp, 'side'));
    if ($errsPhp === [] || $phpSides !== ['installed.php']) {
        fwrite(STDERR, "Wrong-SHA demo: installed.php mutation must fail only installed.php side.\n");
        exit(1);
    }
    $jsonWrong = $fixture;
    $jsonWrong['installedJson']['packages-dev'][0]['source']['reference'] = 'cccccccccccccccccccccccccccccccccccccccc';
    $errsJson = evaluate_cluster_pins($jsonWrong['installedPhp'], $jsonWrong['installedJson'], $map);
    $jsonSides = array_unique(array_column($errsJson, 'side'));
    if ($errsJson === [] || $jsonSides !== ['installed.json']) {
        fwrite(STDERR, "Wrong-SHA demo: installed.json mutation must fail only installed.json side.\n");
        exit(1);
    }

    fwrite(STDOUT, "Wrong-SHA demo failed on installed.php only.\n");
    fwrite(STDOUT, "Wrong-SHA demo failed on installed.json only.\n");
}

run_wrong_sha_demos();

$vendor = load_vendor_pin_sources($root);
$errors = evaluate_cluster_pins($vendor['installedPhp'], $vendor['installedJson'], $expected);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(
            STDERR,
            sprintf(
                "Cluster pin check failed (%s) for %s: %s\n",
                $error['side'],
                $error['package'],
                $error['message']
            )
        );
    }
    exit(1);
}

fwrite(STDOUT, 'Cluster pin check passed for ' . count($expected) . " packages.\n");
exit(0);
