<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';

$readme_generator = new \voku\PhpReadmeHelper\GenerateApi();
$readme_generator->templateMethod = <<<RAW
#### %name%
<a href="#voku-php-readme-class-methods">↑</a>
%description%

**Parameters:**
%params%

**Return:**
%return%

--------

RAW;
$readme_text = ($readme_generator)->generate(
    __DIR__ . '/../src/voku/helper/ASCII.php',
    __DIR__ . '/docs/base.md'
);

file_put_contents(__DIR__ . '/../README.md', $readme_text);
