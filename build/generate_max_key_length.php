<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$languages = \voku\helper\ASCII::get_all_languages();

$languages_key_lengths = [];
foreach ($languages as $language) {
    $lang_specific = \voku\helper\ASCII::chars_array_with_one_language($language, false, false);

    $lang_specific_key_length = \array_map('\mb_strlen', \array_keys($lang_specific));

    if (count($lang_specific_key_length) === 0) {
        $languages_key_lengths[$language] = 0;
    } else {
        $languages_key_lengths[$language] = \max($lang_specific_key_length);
    }
}

//var_export($languages_key_lengths);
