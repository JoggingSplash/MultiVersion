<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

function main(): Generator {
    $start = microtime(true);

    $opts = getopt("", ['out:', 'release']);

    $basePath = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR;
    $targetPath = ($opts['out'] ?? $basePath) . DIRECTORY_SEPARATOR;

    $array = readAndUpdatePluginYml($basePath, isset($opts['release']));

    if(empty($array)) {
        throw new InvalidArgumentException("plugin.yml was not successful read");
    }

    $pharName = $array['name'] . '.phar';

    if (file_exists($targetPath . $pharName)) {
        yield 'Phar file already exists, overwriting...';

        try {
            Phar::unlinkArchive($targetPath . $pharName);
        } catch (PharException) {
            unlink($targetPath . $pharName);
        }
    }

    yield 'Adding files...';

    $files = [];
    $exclusions = [
        '.idea', '.gitignore', 'composer.json', 'composer.lock',
        'make-phar.php', '.git', 'composer.phar',
        'TODO.md', 'README.md', $pharName, 'convert_recipes.py', 'parse_hardcoded_nbt.py',
        '.php-cs-fixer.php', 'php-cs-fixer.cache'
    ];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath)) as $path => $file) {
        $bool = true;

        foreach ($exclusions as $exclusion) {
            if (str_contains($path, $exclusion)) {
                $bool = false;
                break;
            }
        }

        if (!$bool || !$file->isFile()) {
            continue;
        }

        $string = str_replace($basePath, '', $path);
        yield 'Adding ' . $string;

        $files[$string] = $path;
    }

    yield 'Compressing...' . PHP_EOL;

    $phar = new Phar($targetPath . $pharName);
    $phar->startBuffering();
    $phar->setSignatureAlgorithm(Phar::SHA1);

    $array = readAndUpdatePluginYml($basePath, isset($opts['release']));
    $phar->setMetadata($array);

    yield '------------------------------------------------';
    yield 'BUILD SUCCESS';
    yield '------------------------------------------------';

    $count = count($phar->buildFromIterator(new ArrayIterator($files)));
    yield 'Added ' . $count . ' files';

    $phar->compressFiles(Phar::GZ);
    $phar->stopBuffering();

    yield 'Done in ' . round(microtime(true) - $start, 1, PHP_ROUND_HALF_UP) . 's';
}

function readAndUpdatePluginYml(string $ymlPath, bool $updateVersion): array {
    $plYml = $ymlPath . 'plugin.yml';

    if(!file_exists($plYml)) {
        return [];
    }

    $array = yaml_parse_file($plYml);

    if(!is_array($array)) {
        return [];
    }

    if (empty($array)) {
        return [];
    }

    if(!isset($array['version'])) {
        throw new LogicException("'version' parameter is required in the plugin.yml");
    }

    $matches = array_map('intval', explode('.', $array['version']));

    if (count($matches) < 2) {
        throw new InvalidArgumentException("Invalid version '{$array['version']}'");
    }

    if ($updateVersion) {
        if ($matches[1] === 9) {
            $matches[0]++;
            $matches[1] = 0;
        } else {
            $matches[1]++;
        }
    }

    $array['version'] = "{$matches[0]}.{$matches[1]}";

    yaml_emit_file($ymlPath . 'plugin.yml', $array);
    return $array;
}

foreach (main() as $line) {
    echo $line . PHP_EOL;
}
