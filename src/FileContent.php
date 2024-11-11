<?php

declare(strict_types=1);

namespace Kaiseki\ScaffoldModule;

use RuntimeException;

use function array_filter;
use function array_map;
use function fclose;
use function file_exists;
use function file_get_contents;
use function fopen;
use function fwrite;
use function implode;
use function is_dir;
use function mkdir;
use function pathinfo;
use function str_replace;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class FileContent
{
    private string $fileContents;

    public function __construct(string $path)
    {
        $this->fileContents = $this->fetchFileContents($path);
    }

    /**
     * @param string $destination
     * @param string $fileName
     */
    public function writeToFile(string $destination, string $fileName): void
    {
        $destinationFile = $this->joinPaths([$destination, $fileName]);

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        if (fopen($destinationFile, "wb") === false) {
            return;
        }

        $resource = fopen($destinationFile, "wb");

        if ($resource === false) {
            return;
        }

        fwrite($resource, $this->fileContents);
        fclose($resource);
    }

    public function searchReplaceString(string $oldString, string $newString): self
    {
        $this->fileContents = str_replace($oldString, $newString, $this->fileContents);

        return $this;
    }

    /**
     * @param list<string> $paths
     *
     * @return string
     */
    private function joinPaths(array $paths): string
    {
        $paths = array_filter(
            array_map(
                fn($path) => trim($path, DIRECTORY_SEPARATOR),
                $paths
            ),
            fn($path) => $path !== ''
        );

        $path = implode(DIRECTORY_SEPARATOR, $paths);
        $path = DIRECTORY_SEPARATOR . $path;

        if (pathinfo($path, PATHINFO_EXTENSION) === '') {
            $path = $path . DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    private function fetchFileContents(string $path): string
    {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        return (string)file_get_contents($path);
    }
}
