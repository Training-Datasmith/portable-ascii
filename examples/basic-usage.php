<?php

declare(strict_types=1);

/**
 * Example: Converting Unicode strings to ASCII with portable-ascii.
 *
 * Install:
 *   composer require voku/portable-ascii
 */

use voku\helper\ASCII;

// --- Basic transliteration ---

// Generic (language-independent) conversion
echo ASCII::to_ascii("naïve café");       // "naive cafe"
echo ASCII::to_ascii("Héllo Wörld");      // "Hello World"

// Language-specific conversion (German: ö → oe, ü → ue, ä → ae)
echo ASCII::to_ascii("Wörter", 'de');     // "Woerter" (not "Worter")
echo ASCII::to_ascii("Wörter", 'en');     // "Worter"

// --- URL slug generation ---

echo ASCII::to_slugify("Hello World! This is a test.");
// "hello-world-this-is-a-test"

echo ASCII::to_slugify("Héllo Wörld");
// "hello-world"

// --- Filename sanitization ---

$title = "Report: Q1/2024 — Sales & Marketing";
$filename = ASCII::to_filename($title);
// "Report-Q12024-Sales-Marketing"  (or similar safe form)

// --- Transliteration of scripts ---

// Cyrillic
echo ASCII::to_ascii("Москва");  // "Moskva"

// Chinese (generic — no pronunciation info, maps to romanization tables)
echo ASCII::to_ascii("北京");    // "Bei Jing" (approximate)

// Arabic
echo ASCII::to_ascii("مرحبا");   // approximate Latin equivalent
