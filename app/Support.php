<?php

declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_relative_path(string $path): string
{
    $path = str_replace('\\', '/', trim($path));
    $path = preg_replace('#/+#', '/', $path) ?? '';
    $segments = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $segment;
    }

    return implode('/', $segments);
}

function slugify_title(string $title): string
{
    $title = trim($title);
    $title = preg_replace('/[\\/:*?"<>|]+/u', ' ', $title) ?? $title;
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    $title = trim($title, " .\t\n\r\0\x0B");

    return $title !== '' ? $title : 'Новая запись';
}

function split_front_matter(string $content): array
{
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n?/s', $content, $matches)) {
        return [$matches[1], substr($content, strlen($matches[0]))];
    }

    return ['', $content];
}

function extract_markdown_title(string $content, string $fallback): string
{
    if (preg_match('/^#\s+(.+)$/mu', $content, $matches)) {
        return trim($matches[1]);
    }

    return pathinfo($fallback, PATHINFO_FILENAME);
}

function extract_tags(string $content): array
{
    $tags = [];
    if (preg_match('/^---\s*(.*?)\s*---/s', $content, $matches)) {
        $frontMatter = $matches[1];
        if (preg_match('/tags:\s*(.*?)(?:\n\S|$)/s', $frontMatter . "\n", $tagBlock)) {
            preg_match_all('/-\s*([^\n]+)/u', $tagBlock[1], $items);
            $tags = array_map(static fn (string $tag): string => trim($tag), $items[1] ?? []);
        }
    }

    return array_values(array_filter($tags));
}

function render_inline_markdown(string $text): string
{
    $text = h($text);
    $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $text) ?? $text;
    $text = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $text) ?? $text;
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/u', '<a href="$2" target="_blank" rel="noreferrer">$1</a>', $text) ?? $text;

    return $text;
}

function render_markdown(string $markdown): string
{
    [, $body] = split_front_matter($markdown);
    $lines = preg_split('/\R/u', $body) ?: [];
    $html = '';
    $listOpen = false;
    $paragraph = [];

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        if ($paragraph !== []) {
            $html .= '<p>' . render_inline_markdown(implode(' ', $paragraph)) . '</p>';
            $paragraph = [];
        }
    };
    $closeList = static function () use (&$html, &$listOpen): void {
        if ($listOpen) {
            $html .= '</ul>';
            $listOpen = false;
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            $flushParagraph();
            $closeList();
            continue;
        }
        if (preg_match('/^(#{1,4})\s+(.+)$/u', $trimmed, $matches)) {
            $flushParagraph();
            $closeList();
            $level = strlen($matches[1]);
            $html .= '<h' . $level . '>' . render_inline_markdown($matches[2]) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^-\s+(.*)$/u', $trimmed, $matches)) {
            $flushParagraph();
            if (!$listOpen) {
                $html .= '<ul>';
                $listOpen = true;
            }
            $html .= '<li>' . render_inline_markdown($matches[1]) . '</li>';
            continue;
        }
        if (preg_match('/^>\s*(.*)$/u', $trimmed, $matches)) {
            $flushParagraph();
            $closeList();
            $html .= '<blockquote>' . render_inline_markdown($matches[1]) . '</blockquote>';
            continue;
        }
        $paragraph[] = $trimmed;
    }

    $flushParagraph();
    $closeList();

    return $html;
}

function world_time_data(int $epoch, int $speed, array $months): array
{
    $elapsed = time() - $epoch;
    $worldSeconds = $elapsed * $speed;
    $worldDays = (int) floor($worldSeconds / 86400);
    $year = (int) floor($worldDays / 360) + 1;
    $dayOfYear = $worldDays % 360;
    $month = (int) floor($dayOfYear / 30);
    $day = ($dayOfYear % 30) + 1;
    $secOfDay = $worldSeconds % 86400;
    $hour = (int) floor($secOfDay / 3600);

    return [
        'date' => sprintf('%02d.%02d.%02d', $day, $month + 1, $year),
        'monthName' => $months[$month] ?? '',
        'isDay' => $hour >= 6 && $hour < 18,
        'worldDays' => $worldDays,
        'year' => $year,
    ];
}

function url_for(string $page, array $params = []): string
{
    return '?' . http_build_query(['page' => $page] + $params);
}
