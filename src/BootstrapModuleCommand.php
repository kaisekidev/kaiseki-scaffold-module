<?php

declare(strict_types=1);

namespace Kaiseki\ScaffoldModule;

use Laminas\Filter\Word\DashToCamelCase;
use Laminas\Filter\Word\DashToUnderscore;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

use function array_diff;
use function array_merge;
use function basename;
use function dirname;
use function filter_var;
use function in_array;
use function is_dir;
use function is_file;
use function mkdir;
use function pathinfo;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function realpath;
use function rename;
use function rmdir;
use function scandir;
use function sprintf;
use function str_replace;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_URL;
use const PATHINFO_DIRNAME;

#[AsCommand(
    name: 'bootstrap-module',
)]
class BootstrapModuleCommand extends Command
{
    private const TYPE_WORDPRESS = 'wordpress';
    private const TYPE_CORE = 'core';

    private string $type;
    private string $rootDir;
    private string $outputDir;
    private string $moduleName;
    private string $configBaseKey;
    private string $namespace;
    private string $repoUrl;
    private string $copyrightHolder;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->rootDir = (string)realpath(__DIR__ . '/..');
        $this->outputDir = $this->rootDir . '/output';

        /** @var QuestionHelper $question */
        $question = $this->getHelper('question');

        $this
            ->askForType($input, $output, $question)
            ->askForModuleName($input, $output, $question)
            ->askForConfigBase($input, $output, $question)
            ->askForNamespace($input, $output, $question)
            ->askForRepoUrl($input, $output, $question)
            ->askForCopyrightHolder($input, $output, $question);

        $sharedFiles = $this->getAllFilesInDirectory($this->rootDir . '/templates/shared');
        $typeFiles = $this->getAllFilesInDirectory($this->rootDir . '/templates/' . $this->getTypeFolder());

        $this->copyFiles(array_merge($sharedFiles, $typeFiles));

        $this->cleanUp();
        $this->copyOutput();
        $this->deleteDirectory($this->outputDir);

        return Command::SUCCESS;
    }

    private function copyOutput(): void
    {
        /** @var SplFileInfo[] $iterator */
        // @phpstan-ignore-next-line
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->outputDir));

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                continue;
            }

            $destination = str_replace($this->outputDir, $this->rootDir, $fileInfo->getPathname());
            $destinationDir = pathinfo($destination, PATHINFO_DIRNAME);

            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }

            rename($fileInfo->getPathname(), $destination);
        }
    }

    private function cleanUp(): void
    {
        $list = scandir($this->rootDir);

        if ($list === false) {
            return;
        }

        foreach (array_diff($list, ['..', '.']) as $name) {
            if (is_file($this->rootDir . DIRECTORY_SEPARATOR . $name)) {
                unlink($name);

                continue;
            }

            if (in_array($name, ['output'], true)) {
                continue;
            }

            $this->deleteDirectory($this->rootDir . DIRECTORY_SEPARATOR . $name);
        }
    }

    private function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        /** @var SplFileInfo[] $files */
        // @phpstan-ignore-next-line
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) {
                $this->deleteDirectory($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }

        return rmdir($path);
    }

    /**
     * @param list<string> $paths
     */
    private function copyFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $fileContent = new FileContent($path);
            $fileContent
                ->searchReplaceString(
                    $this->getArgTemplate('package_name_dash'),
                    $this->getModulePrefix() . $this->moduleName
                )
                ->searchReplaceString($this->getArgTemplate('config_base_key'), $this->configBaseKey)
                ->searchReplaceString($this->getArgTemplate('namespace'), $this->namespace)
                ->searchReplaceString($this->getArgTemplate('namespace_escaped'), str_replace('\\', '\\\\', $this->namespace))
                ->searchReplaceString($this->getArgTemplate('repo_url'), $this->repoUrl)
                ->searchReplaceString($this->getArgTemplate('copyright_holder'), $this->copyrightHolder)
                ->writeToFile($this->getOutputPath($path), basename($path));
        }
    }

    private function getOutputPath(string $path): string
    {
        $path = pathinfo($path, PATHINFO_DIRNAME);
        $dir = $this->rootDir. '/templates';
        $escapedDir = preg_quote($dir, '/');

        return preg_replace(
            '/'. $escapedDir . '\/(core|wordpress|shared)/',
            $this->rootDir . '/output',
            $path
        ) ?? '';
    }

    private function askForType(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        $question = new ChoiceQuestion(
            'Which type of module do you want to create (defaults to "wordpress")',
            [self::TYPE_WORDPRESS, self::TYPE_CORE],
            0
        );

        $type = $questionHelper->ask($input, $output, $question);

        $this->type = $type === self::TYPE_WORDPRESS ? self::TYPE_WORDPRESS : self::TYPE_CORE;

        return $this;
    }

    private function askForModuleName(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        $moduleName = $this->guessModuleName();

        $question = new Question(
            sprintf(
                'Module name (kaiseki/%s*) [default: %s]: ',
                $this->getModulePrefix(),
                $moduleName
            ),
            $moduleName
        );
        $question->setValidator(function (string $answer): string {
            if (preg_match('/^[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/', $answer) !== 1) {
                throw new RuntimeException(sprintf('%s is not a valid package name.', $answer));
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        /** @var string $moduleName */
        $moduleName = $questionHelper->ask($input, $output, $question);
        $this->moduleName = $moduleName;

        return $this;
    }

    private function askForConfigBase(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        $default = (new DashToUnderscore())->filter($this->moduleName);

        $question = new Question(
            sprintf(
                'Config base key [default: %s]: ',
                $default
            ),
            $default
        );

        /** @var string $configBaseKey */
        $configBaseKey = $questionHelper->ask($input, $output, $question);
        $this->configBaseKey = $configBaseKey;

        return $this;
    }

    private function askForNamespace(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        /** @var string $namespace */
        $namespace = (new DashToCamelCase())->filter($this->moduleName);
        $prefix = $this->type === self::TYPE_WORDPRESS ? 'WordPress\\' : '';

        $question = new Question(
            sprintf(
                'Module namespace (Kaiseki\\%s*) [default: %s]: ',
                $prefix,
                $namespace
            ),
            $namespace
        );
        $question->setValidator(function (string $answer): string {
            if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $answer) !== 1) {
                throw new RuntimeException(sprintf('%s is not a valid namespace.', $answer));
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        /** @var string $namespace */
        $namespace = $questionHelper->ask($input, $output, $question);
        $this->namespace = $namespace;

        return $this;
    }

    private function askForRepoUrl(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        $url = 'https://github.com/kaisekidev/kaiseki-' . $this->getModulePrefix() . $this->moduleName;

        $question = new Question(
            sprintf(
                'URL to repository [default: %s]: ',
                $url
            ),
            $url
        );
        $question->setValidator(function (string $answer): string {
            if (filter_var($answer, FILTER_VALIDATE_URL) === false) {
                throw new RuntimeException(sprintf('%s is not a URL.', $answer));
            }

            return $answer;
        });
        $question->setMaxAttempts(3);

        /** @var string $repoUrl */
        $repoUrl = $questionHelper->ask($input, $output, $question);
        $this->repoUrl = $repoUrl;

        return $this;
    }

    private function askForCopyrightHolder(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $questionHelper
    ): self {
        $default = 'woda - Software Development GmbH';
        $question = new Question(
            sprintf(
                'Copyright holder [default: %s]: ',
                $default
            ),
            $default
        );

        /** @var string $copyrightHolder */
        $copyrightHolder = $questionHelper->ask($input, $output, $question);
        $this->copyrightHolder = $copyrightHolder;

        return $this;
    }

    private function guessModuleName(): string
    {
        return str_replace(['kaiseki-', 'wp-'], '', basename(dirname(__DIR__)));
    }

    /**
     * @param string $directory
     *
     * @return list<string>
     */
    private function getAllFilesInDirectory(string $directory): array
    {
        $result = [];

        if (!is_dir($directory)) {
            return $result;
        }

        /** @var SplFileInfo[] $iterator */
        // @phpstan-ignore-next-line
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $filePath = realpath($fileInfo->getPathname());

                if ($filePath === false) {
                    continue;
                }

                $result[] = $filePath;
            }
        }

        return $result;
    }

    private function getArgTemplate(string $key): string
    {
        return "%{$key}%";
    }

    private function getTypeFolder(): string
    {
        return $this->type === self::TYPE_WORDPRESS ? 'wordpress' : 'core';
    }

    private function getModulePrefix(): string
    {
        return $this->type === self::TYPE_WORDPRESS ? 'wp-' : '';
    }
}
