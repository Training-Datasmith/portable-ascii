# Architecture: portable-ascii

## Purpose

Converts Unicode and multi-language characters to ASCII equivalents, producing
clean ASCII representations of strings for use in URLs, filenames, and identifiers.
Supports language-specific transliteration (German, French, Russian, Chinese, etc.).

## Directory Structure

```
src/voku/helper/
  ASCII.php   # Main class: all public API as static methods

  data/
    ascii_by_languages.php       # Per-language transliteration tables (e.g., ä → ae for German)
    ascii_extras_by_languages.php  # Extended per-language mappings
    ascii_language_max_key.php   # Precomputed max key lengths for fast lookup
    ascii_ord.php                # Ordinal value → ASCII replacement for generic conversion
    x000.php ... x0ff.php ...   # Unicode block data files (loaded lazily by block)
```

## Key Design Decisions

### Lazy Data Loading

Unicode transliteration data is split into ~200 data files organized by Unicode block
(`x000.php`, `x001.php`, etc.). Only the blocks needed for the input string's characters
are loaded, avoiding memory overhead for the common case of primarily ASCII input.

### Language-Specific Transliteration

The `ascii_by_languages.php` table provides language-aware mappings. For example,
`ö` → `o` generically, but `ö` → `oe` in German. Callers specify the language code
to get locale-correct output. The default language is "en" (English).

### Static API Only

The `ASCII` class uses only static methods and has no instance state. All transliteration
is deterministic given the same input and language parameters.

## Extension Points

- Language tables in `data/` can be extended with custom entries for domain-specific
  transliteration needs.
- The `ASCII` class can be extended to add custom static methods.

## Common Use Cases

- Generating URL slugs: `ASCII::to_slugify("Héllo Wörld")` → `"hello-world"`
- Stripping accents: `ASCII::to_ascii("café", 'en')` → `"cafe"`
- Creating safe filenames from user-provided titles
