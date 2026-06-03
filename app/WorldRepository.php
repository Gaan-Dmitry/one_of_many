<?php

declare(strict_types=1);

final class WorldRepository
{
    public function __construct(private readonly string $vaultDir)
    {
        $this->ensureVault();
    }

    public function list(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->vaultDir, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file->isFile() || mb_strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $relativePath = normalize_relative_path(substr($file->getPathname(), strlen($this->vaultDir) + 1));
            $content = file_get_contents($file->getPathname()) ?: '';
            $files[] = [
                'path' => $relativePath,
                'title' => extract_markdown_title($content, $relativePath),
                'folder' => dirname($relativePath) === '.' ? '' : dirname($relativePath),
                'tags' => extract_tags($content),
                'updated' => $file->getMTime(),
                'size' => $file->getSize(),
                'excerpt' => $this->excerpt($content),
            ];
        }

        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['path'], $b['path']));

        return $files;
    }

    public function grouped(array $files): array
    {
        $grouped = [];
        foreach ($files as $file) {
            $grouped[$file['folder']][] = $file;
        }

        return $grouped;
    }

    public function find(array $files, string $path): ?array
    {
        $path = normalize_relative_path($path);
        foreach ($files as $file) {
            if ($file['path'] === $path) {
                return $file;
            }
        }

        return null;
    }

    public function read(string $path): string
    {
        $absolute = $this->absolutePath($path);
        if (!is_file($absolute)) {
            throw new RuntimeException('Запись не найдена.');
        }

        return file_get_contents($absolute) ?: '';
    }

    public function save(string $path, string $content): void
    {
        $absolute = $this->absolutePath($path);
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }
        file_put_contents($absolute, $content);
    }

    public function create(string $category, string $title, string $templateBody): string
    {
        $path = normalize_relative_path($category . '/' . slugify_title($title) . '.md');
        $absolute = $this->absolutePath($path);
        if (file_exists($absolute)) {
            throw new RuntimeException('Такая запись уже существует: ' . $path);
        }

        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }
        file_put_contents($absolute, str_replace('{{title}}', slugify_title($title), $templateBody));

        return $path;
    }

    public function byPrefix(array $files, string $prefix): array
    {
        $prefix = normalize_relative_path($prefix);

        return array_values(array_filter($files, static fn (array $file): bool => str_starts_with($file['path'], $prefix)));
    }

    public function stats(array $files, array $categories): array
    {
        $stats = [];
        foreach ($categories as $path => $category) {
            $stats[$path] = count($this->byPrefix($files, $path));
        }

        return $stats;
    }

    private function ensureVault(): void
    {
        if (!is_dir($this->vaultDir)) {
            mkdir($this->vaultDir, 0775, true);
        }
    }

    private function absolutePath(string $relativePath): string
    {
        $relativePath = normalize_relative_path($relativePath);
        if ($relativePath === '' || !str_ends_with(mb_strtolower($relativePath), '.md')) {
            throw new RuntimeException('Можно работать только с Markdown-записями внутри MD.');
        }

        $absolute = $this->vaultDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $vaultReal = realpath($this->vaultDir) ?: $this->vaultDir;
        $dirReal = realpath(dirname($absolute)) ?: dirname($absolute);

        if (!str_starts_with($dirReal, $vaultReal)) {
            throw new RuntimeException('Путь выходит за пределы хранилища MD.');
        }

        return $absolute;
    }

    private function excerpt(string $content): string
    {
        [, $body] = split_front_matter($content);
        $body = preg_replace('/^#+\s+/m', '', $body) ?? $body;
        $body = preg_replace('/[*_`>#\[\]()\-]+/u', ' ', $body) ?? $body;
        $body = preg_replace('/\s+/u', ' ', trim($body)) ?? $body;

        return mb_substr($body, 0, 180);
    }
}
