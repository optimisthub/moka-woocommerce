# Twig Dump Extension

Standalone Symfony Var Dumper Twig extension.

## Installation

```bash
composer require hellonico/twig-dump-extension
```

## Usage

```php
$twig = new Twig_Environment($loader, $options);
$twig->addExtension(new HelloNico\Twig\DumpExtension());
```

In Twig templates:

```twig
{{ dump(foo) }}
{% dump foo %}
{% dump foo, bar %}
```
