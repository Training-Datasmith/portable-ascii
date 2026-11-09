<?php

declare(strict_types=1);

/**
 * Baseline method inventory (pre-rename upstream test surface, snake_case names).
 * Used by scripts/check-baseline.php — not executed in normal runs.
 */
return [
    'Ascii_Global_Test::test_chars_array_with_multi_language_values',
    'Ascii_Global_Test::test_chars_array_with_one_language',
    'Ascii_Global_Test::test_chars_array_with_single_language_values',
    'Ascii_Global_Test::test_chars_array',
    'Ascii_Global_Test::test_filter_file',
    'Ascii_Global_Test::test_slugify',
    'Ascii_Global_Test::test_to_ascii',
    'Ascii_Global_Test::test_is_ascii_test',
    'Ascii_Global_Test::test_clean_parameter',
    'Ascii_Global_Test::test_language_files',
    'Ascii_Global_Test::test_normalize_msword',
    'Ascii_Global_Test::test_normalize_whitespace',
    'Ascii_Test::test_to_ascii_remap',
    'Ascii_Test::test_utf8',
    'Ascii_Test::test_ascii',
    'Ascii_Test::test_invalid_char',
    'Ascii_Test::test_empty_str',
    'Ascii_Test::test_new_line',
    'Ascii_Test::test_tab',
    'Ascii_Test::test_utf8_to_ascii',
    'Ascii_Test::test_ascii_to_ascii',
    'Ascii_Test::test_to_ascii_empty_language',
    'Ascii_Test::test_to_ascii',
    'Ascii_Test::test_remove_invisible_characters',
    'Ascii_Test::test_get_supported_languages',
    'Ascii_Test::test_invalid_char_to_ascii',
    'Ascii_Test::test_empty_str_to_ascii',
    'Ascii_Test::test_nul_and_non7_bit',
    'Ascii_Test::test_nul',
    'Ascii_Test::test_new_line_to_ascii',
    'Ascii_Test::test_tab_to_ascii',
    'Transliterate_Test::test_utf8',
    'Transliterate_Test::test_ascii',
    'Transliterate_Test::test_invalid_char',
    'Transliterate_Test::test_empty_str',
    'Transliterate_Test::test_nul_and_non7_bit',
    'Transliterate_Test::test_nul',
    'Transliterate_Test::test_to_transliterate',
    'Transliterate_Test::test_currency',
    'Transliterate_Test::test_keep_invalid_chars_strict',
    'Transliterate_Test::test_keep_invalid_chars',
];
