<?php

declare(strict_types=1);

$WORLD_EPOCH = strtotime('2026-06-01 00:00:00 UTC');
$SPEED = 5;

$MONTHS = [
    'Лютохлад', 'Ветровей', 'Таловод',
    'Зеленорост', 'Цветель', 'Влажник',
    'Ярило', 'Зрельник', 'Страдник',
    'Листопад', 'Грязовец', 'Студень',
];

$ROOT_DIR = __DIR__;
$VAULT_DIR = $ROOT_DIR . DIRECTORY_SEPARATOR . 'MD';

$CATEGORIES = [
    '00_CORE' => ['label' => 'Ядро мира', 'icon' => '✦', 'hint' => 'Законы, космология, физика Истока'],
    '02_SYSTEMS' => ['label' => 'Системы', 'icon' => '⚙', 'hint' => 'Магия, ранги, механики, профили силы'],
    '03_TIMELINE/События' => ['label' => 'События', 'icon' => '⌛', 'hint' => 'Отдельные исторические события'],
    '04_CHARACTERS' => ['label' => 'Персонажи', 'icon' => '☉', 'hint' => 'Анкеты героев, NPC и важных фигур'],
    '07_WORLD' => ['label' => 'Локации', 'icon' => '⌂', 'hint' => 'Регионы, поселения, реки, горы, карты'],
    '08_FRACTION' => ['label' => 'Фракции', 'icon' => '⚑', 'hint' => 'Государства, союзы, кланы, организации'],
    '12_ITEMS' => ['label' => 'Предметы', 'icon' => '◈', 'hint' => 'Оружие, артефакты, ресурсы, реликвии'],
    '13_ABILITIES' => ['label' => 'Способности', 'icon' => '✺', 'hint' => 'Приёмы, пассивные и активные способности'],
    '14_SPELLS' => ['label' => 'Заклинания', 'icon' => '✹', 'hint' => 'Формулы, эффекты, цена применения'],
    '30_FLORA_FAUNA' => ['label' => 'Флора и фауна', 'icon' => '♣', 'hint' => 'Растения, звери, монстры, экология'],
    '50_STORIES' => ['label' => 'Истории', 'icon' => '✎', 'hint' => 'Арки, главы, сцены, сюжетные заметки'],
    '99_META' => ['label' => 'Мета', 'icon' => '☷', 'hint' => 'Календарь, синхронизация, служебные заметки'],
];

$TEMPLATES = [
    'blank' => [
        'label' => 'Пустая заметка',
        'category' => '00_CORE',
        'body' => "---\ntags:\n  - черновик\n---\n# {{title}}\n\n## Кратко\n\n## Подробности\n",
    ],
    'character' => [
        'label' => 'Персонаж',
        'category' => '04_CHARACTERS',
        'body' => "---\ntags:\n  - персонажи\n  - черновик\nstatus: draft\n---\n# {{title}}\n\n- **Ранг:** \n- **Профиль:** \n- **Стихия:** \n- **Фракция:** \n- **Состояние:** \n- **Местоположение:** \n\n## Описание\n\n## Мотивация\n\n## Снаряжение\n\n## Связи\n\n## Текущие задачи\n- [ ] \n",
    ],
    'location' => [
        'label' => 'Локация',
        'category' => '07_WORLD',
        'body' => "---\ntags:\n  - мир\n  - локации\nstatus: draft\n---\n# {{title}}\n\n- **Тип:** \n- **Регион:** \n- **Контроль:** \n- **Опасность:** \n\n## Атмосфера\n\n## География\n\n## Жители\n\n## Ресурсы и угрозы\n\n## Сюжетные зацепки\n",
    ],
    'item' => [
        'label' => 'Предмет',
        'category' => '12_ITEMS',
        'body' => "---\ntags:\n  - предметы\nstatus: draft\n---\n# {{title}}\n\n- **Тип:** \n- **Редкость:** \n- **Владелец:** \n- **Происхождение:** \n\n## Описание\n\n## Свойства\n\n## Ограничения\n\n## История\n",
    ],
    'ability' => [
        'label' => 'Способность',
        'category' => '13_ABILITIES',
        'body' => "---\ntags:\n  - способности\nstatus: draft\n---\n# {{title}}\n\n- **Тип:** \n- **Профиль:** \n- **Ранг:** \n- **Цена:** \n\n## Эффект\n\n## Условия применения\n\n## Риски\n\n## Вариации\n",
    ],
    'spell' => [
        'label' => 'Заклинание',
        'category' => '14_SPELLS',
        'body' => "---\ntags:\n  - заклинания\nstatus: draft\n---\n# {{title}}\n\n- **Школа/стихия:** \n- **Ранг:** \n- **Дальность:** \n- **Цена Истока:** \n\n## Формула\n\n## Эффект\n\n## Побочные эффекты\n\n## Контрмеры\n",
    ],
    'faction' => [
        'label' => 'Фракция',
        'category' => '08_FRACTION',
        'body' => "---\ntags:\n  - фракции\nstatus: draft\n---\n# {{title}}\n\n- **Тип:** \n- **Столица/центр:** \n- **Лидер:** \n- **Союзники:** \n- **Враги:** \n\n## Идеология\n\n## Структура\n\n## Ресурсы\n\n## Текущие цели\n",
    ],
    'story' => [
        'label' => 'История / глава',
        'category' => '50_STORIES',
        'body' => "---\ntags:\n  - история\nstatus: draft\n---\n# {{title}}\n\n## Синопсис\n\n## Сцены\n\n## Конфликт\n\n## Последствия для мира\n",
    ],
];

function ensureVault(string $vaultDir): void
{
    if (!is_dir($vaultDir)) {
        mkdir($vaultDir, 0775, true);
    }
}

function normalizeRelativePath(string $path): string
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

function pathToAbsolute(string $vaultDir, string $relativePath): string
{
    $relativePath = normalizeRelativePath($relativePath);
    if ($relativePath === '' || !str_ends_with(mb_strtolower($relativePath), '.md')) {
        throw new RuntimeException('Можно работать только с .md файлами внутри MD.');
    }

    $absolute = $vaultDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $vaultReal = realpath($vaultDir) ?: $vaultDir;
    $dirReal = realpath(dirname($absolute)) ?: dirname($absolute);

    if (!str_starts_with($dirReal, $vaultReal)) {
        throw new RuntimeException('Путь выходит за пределы хранилища MD.');
    }

    return $absolute;
}

function slugifyTitle(string $title): string
{
    $title = trim($title);
    $title = preg_replace('/[\\/:*?"<>|]+/u', ' ', $title) ?? $title;
    $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
    $title = trim($title, " .\t\n\r\0\x0B");

    return $title !== '' ? $title : 'Новая заметка';
}

function makeRelativeFileName(string $category, string $title): string
{
    $category = normalizeRelativePath($category);
    $fileName = slugifyTitle($title) . '.md';

    return normalizeRelativePath($category . '/' . $fileName);
}

function extractTitle(string $content, string $fallback): string
{
    if (preg_match('/^#\s+(.+)$/mu', $content, $matches)) {
        return trim($matches[1]);
    }

    return pathinfo($fallback, PATHINFO_FILENAME);
}

function extractTags(string $content): array
{
    $tags = [];
    if (preg_match('/^---\s*(.*?)\s*---/s', $content, $matches)) {
        $frontMatter = $matches[1];
        if (preg_match('/tags:\s*(.*?)(?:\n\S|$)/s', $frontMatter . "\n", $tagBlock)) {
            preg_match_all('/-\s*([^\n]+)/u', $tagBlock[1], $items);
            $tags = array_map(static fn ($tag): string => trim($tag), $items[1] ?? []);
        }
    }

    return array_values(array_filter($tags));
}

function listMarkdownFiles(string $vaultDir): array
{
    ensureVault($vaultDir);
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vaultDir, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (!$file->isFile() || mb_strtolower($file->getExtension()) !== 'md') {
            continue;
        }
        $relativePath = normalizeRelativePath(substr($file->getPathname(), strlen($vaultDir) + 1));
        $content = file_get_contents($file->getPathname()) ?: '';
        $files[] = [
            'path' => $relativePath,
            'title' => extractTitle($content, $relativePath),
            'folder' => dirname($relativePath) === '.' ? '' : dirname($relativePath),
            'tags' => extractTags($content),
            'updated' => $file->getMTime(),
            'size' => $file->getSize(),
        ];
    }

    usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['path'], $b['path']));

    return $files;
}

function splitFrontMatter(string $content): array
{
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n?/s', $content, $matches)) {
        return [$matches[1], substr($content, strlen($matches[0]))];
    }

    return ['', $content];
}

function renderInline(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $text) ?? $text;
    $text = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $text) ?? $text;
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/u', '<a href="$2" target="_blank" rel="noreferrer">$1</a>', $text) ?? $text;

    return $text;
}

function renderMarkdown(string $markdown): string
{
    [, $body] = splitFrontMatter($markdown);
    $lines = preg_split('/\R/u', $body) ?: [];
    $html = '';
    $listOpen = false;
    $paragraph = [];

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        if ($paragraph !== []) {
            $html .= '<p>' . renderInline(implode(' ', $paragraph)) . '</p>';
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
            $html .= '<h' . $level . '>' . renderInline($matches[2]) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^-\s+(.*)$/u', $trimmed, $matches)) {
            $flushParagraph();
            if (!$listOpen) {
                $html .= '<ul>';
                $listOpen = true;
            }
            $html .= '<li>' . renderInline($matches[1]) . '</li>';
            continue;
        }
        if (preg_match('/^>\s*(.*)$/u', $trimmed, $matches)) {
            $flushParagraph();
            $closeList();
            $html .= '<blockquote>' . renderInline($matches[1]) . '</blockquote>';
            continue;
        }
        $paragraph[] = $trimmed;
    }

    $flushParagraph();
    $closeList();

    return $html;
}

function worldTimeData(int $epoch, int $speed, array $months): array
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
    ];
}

ensureVault($VAULT_DIR);
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $path = normalizeRelativePath((string) ($_POST['path'] ?? ''));
            $content = (string) ($_POST['content'] ?? '');
            $absolute = pathToAbsolute($VAULT_DIR, $path);
            if (!is_dir(dirname($absolute))) {
                mkdir(dirname($absolute), 0775, true);
            }
            file_put_contents($absolute, $content);
            header('Location: ?file=' . rawurlencode($path) . '&mode=view&saved=1');
            exit;
        }

        if ($action === 'create') {
            $templateKey = (string) ($_POST['template'] ?? 'blank');
            $template = $TEMPLATES[$templateKey] ?? $TEMPLATES['blank'];
            $category = (string) ($_POST['category'] ?? $template['category']);
            $title = slugifyTitle((string) ($_POST['title'] ?? 'Новая заметка'));
            $path = makeRelativeFileName($category, $title);
            $absolute = pathToAbsolute($VAULT_DIR, $path);
            if (file_exists($absolute)) {
                throw new RuntimeException('Файл уже существует: ' . $path);
            }
            if (!is_dir(dirname($absolute))) {
                mkdir(dirname($absolute), 0775, true);
            }
            $content = str_replace('{{title}}', $title, $template['body']);
            file_put_contents($absolute, $content);
            header('Location: ?file=' . rawurlencode($path) . '&mode=edit&created=1');
            exit;
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$files = listMarkdownFiles($VAULT_DIR);
$selectedPath = normalizeRelativePath((string) ($_GET['file'] ?? ($files[0]['path'] ?? '')));
$selectedContent = '';
$selectedMeta = null;
if ($selectedPath !== '') {
    try {
        $selectedAbsolute = pathToAbsolute($VAULT_DIR, $selectedPath);
        if (is_file($selectedAbsolute)) {
            $selectedContent = file_get_contents($selectedAbsolute) ?: '';
            foreach ($files as $fileInfo) {
                if ($fileInfo['path'] === $selectedPath) {
                    $selectedMeta = $fileInfo;
                    break;
                }
            }
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $selectedPath = '';
    }
}

$mode = (string) ($_GET['mode'] ?? 'view');
$mode = $mode === 'edit' ? 'edit' : 'view';
$world = worldTimeData($WORLD_EPOCH, $SPEED, $MONTHS);
$grouped = [];
foreach ($files as $fileInfo) {
    $grouped[$fileInfo['folder']][] = $fileInfo;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ВЕК ИСТОКА — редактор мира</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #06070c;
    --panel: rgba(13, 16, 26, 0.86);
    --panel-strong: rgba(22, 26, 40, 0.94);
    --line: rgba(223, 190, 115, 0.18);
    --line-cold: rgba(122, 162, 255, 0.18);
    --text: #f2eadb;
    --muted: #a9a59b;
    --gold: #d4ad5c;
    --gold-soft: rgba(212, 173, 92, 0.12);
    --blue: #71a7ff;
    --danger: #ff7d7d;
    --success: #8ee59b;
    --shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
}
* { box-sizing: border-box; }
body {
    min-height: 100vh;
    margin: 0;
    background:
        radial-gradient(circle at 12% 8%, rgba(212, 173, 92, 0.18), transparent 34%),
        radial-gradient(circle at 80% 18%, rgba(113, 167, 255, 0.14), transparent 32%),
        linear-gradient(135deg, #05060a, #10131e 58%, #07080d);
    color: var(--text);
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
a { color: inherit; text-decoration: none; }
button, input, select, textarea { font: inherit; }
.shell {
    display: grid;
    grid-template-columns: 330px minmax(0, 1fr);
    min-height: 100vh;
}
.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: auto;
    padding: 22px;
    border-right: 1px solid var(--line);
    background: rgba(5, 7, 12, 0.72);
    backdrop-filter: blur(18px);
}
.brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}
.brand h1 {
    margin: 0;
    font-size: 22px;
    line-height: 1.1;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.brand span { color: var(--gold); }
.clock-card, .panel, .editor-card, .viewer-card {
    border: 1px solid var(--line);
    border-radius: 22px;
    background: var(--panel);
    box-shadow: var(--shadow);
}
.clock-card {
    padding: 15px;
    margin-bottom: 18px;
}
.clock-main {
    color: var(--gold);
    font-family: "JetBrains Mono", monospace;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.08em;
}
.clock-meta { margin-top: 6px; color: var(--muted); font-size: 13px; }
.search {
    width: 100%;
    margin: 0 0 14px;
    padding: 13px 14px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: rgba(255,255,255,0.04);
    color: var(--text);
    outline: none;
}
.folder { margin-bottom: 14px; }
.folder-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
    color: var(--gold);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.file-link {
    display: block;
    padding: 10px 12px;
    margin-bottom: 6px;
    border: 1px solid transparent;
    border-radius: 13px;
    color: #ddd5c8;
    background: rgba(255,255,255,0.025);
}
.file-link:hover, .file-link.active {
    border-color: var(--line);
    background: var(--gold-soft);
}
.file-link small {
    display: block;
    margin-top: 4px;
    overflow: hidden;
    color: var(--muted);
    font-size: 11px;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.main {
    min-width: 0;
    padding: 28px clamp(22px, 4vw, 54px);
}
.topbar {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 22px;
}
.kicker {
    margin: 0 0 8px;
    color: var(--gold);
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.topbar h2 {
    margin: 0;
    font-size: clamp(28px, 4vw, 48px);
    line-height: 1.05;
}
.actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 10px 15px;
    border: 1px solid var(--line);
    border-radius: 13px;
    background: rgba(255,255,255,0.045);
    color: var(--text);
    cursor: pointer;
}
.btn.primary {
    border-color: rgba(212, 173, 92, 0.6);
    background: linear-gradient(135deg, rgba(212,173,92,0.28), rgba(212,173,92,0.08));
}
.btn:hover { transform: translateY(-1px); }
.layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 22px;
    align-items: start;
}
.viewer-card, .editor-card, .panel { padding: 22px; }
.editor-card form { display: grid; gap: 14px; }
textarea {
    width: 100%;
    min-height: 64vh;
    padding: 18px;
    border: 1px solid var(--line-cold);
    border-radius: 18px;
    resize: vertical;
    background: rgba(2, 4, 8, 0.72);
    color: #f8f0df;
    font-family: "JetBrains Mono", monospace;
    font-size: 14px;
    line-height: 1.65;
    outline: none;
}
.viewer {
    color: #efe5d5;
    font-size: 16px;
    line-height: 1.75;
}
.viewer h1, .viewer h2, .viewer h3, .viewer h4 { line-height: 1.2; color: #fff3d7; }
.viewer h1 { margin-top: 0; font-size: 38px; }
.viewer h2 { margin-top: 32px; color: var(--gold); }
.viewer p, .viewer ul { max-width: 880px; }
.viewer code {
    padding: 2px 6px;
    border-radius: 7px;
    background: rgba(255,255,255,0.08);
    color: #ffe1a1;
}
.viewer blockquote {
    margin: 18px 0;
    padding: 13px 16px;
    border-left: 3px solid var(--gold);
    background: rgba(212,173,92,0.08);
    color: #fff1d0;
}
.meta-grid {
    display: grid;
    gap: 10px;
}
.meta-row {
    padding: 12px;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    background: rgba(255,255,255,0.035);
}
.meta-row b { display: block; margin-bottom: 3px; color: var(--gold); font-size: 12px; text-transform: uppercase; }
.tags { display: flex; flex-wrap: wrap; gap: 7px; }
.tag {
    padding: 5px 9px;
    border: 1px solid rgba(212,173,92,0.3);
    border-radius: 999px;
    background: var(--gold-soft);
    color: #ffe3a8;
    font-size: 12px;
}
.create-form {
    display: grid;
    gap: 12px;
}
.create-form label { display: grid; gap: 7px; color: var(--muted); font-size: 13px; }
.create-form input, .create-form select {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    color: var(--text);
    outline: none;
}
.create-form option { color: #171717; }
.alert {
    margin-bottom: 18px;
    padding: 13px 15px;
    border-radius: 14px;
}
.alert.success { border: 1px solid rgba(142,229,155,0.4); background: rgba(142,229,155,0.1); color: var(--success); }
.alert.error { border: 1px solid rgba(255,125,125,0.45); background: rgba(255,125,125,0.1); color: var(--danger); }
.advice {
    margin-top: 22px;
    border-color: rgba(113,167,255,0.23);
}
.advice h3, .panel h3 { margin-top: 0; }
.advice li { margin-bottom: 9px; color: #dcd7cc; line-height: 1.55; }
.empty {
    padding: 42px;
    text-align: center;
    color: var(--muted);
}
@media (max-width: 1120px) {
    .shell { grid-template-columns: 1fr; }
    .sidebar { position: relative; height: auto; }
    .layout { grid-template-columns: 1fr; }
    .topbar { flex-direction: column; }
    .actions { justify-content: flex-start; }
}
</style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <h1>Век <span>Истока</span></h1>
        </div>
        <div class="clock-card">
            <div class="clock-main" id="world-clock">00:00:00</div>
            <div class="clock-meta"><?= htmlspecialchars($world['date']) ?> • <?= htmlspecialchars($world['monthName']) ?> • <?= $world['isDay'] ? 'День Истока' : 'Ночь Истока' ?></div>
        </div>
        <input class="search" id="search" type="search" placeholder="Поиск по названию, тегам и пути…">
        <nav id="file-list">
            <?php foreach ($grouped as $folder => $folderFiles): ?>
                <section class="folder">
                    <div class="folder-title">
                        <span><?= htmlspecialchars($folder !== '' ? $folder : 'Корень') ?></span>
                        <span><?= count($folderFiles) ?></span>
                    </div>
                    <?php foreach ($folderFiles as $fileInfo): ?>
                        <?php $haystack = mb_strtolower($fileInfo['title'] . ' ' . $fileInfo['path'] . ' ' . implode(' ', $fileInfo['tags'])); ?>
                        <a class="file-link <?= $fileInfo['path'] === $selectedPath ? 'active' : '' ?>" href="?file=<?= rawurlencode($fileInfo['path']) ?>" data-search="<?= htmlspecialchars($haystack) ?>">
                            <?= htmlspecialchars($fileInfo['title']) ?>
                            <small><?= htmlspecialchars($fileInfo['path']) ?></small>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="main">
        <?php if (isset($_GET['saved'])): ?><div class="alert success">Заметка сохранена.</div><?php endif; ?>
        <?php if (isset($_GET['created'])): ?><div class="alert success">Заметка создана. Можно сразу редактировать.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="topbar">
            <div>
                <p class="kicker">Веб-редактор Obsidian-хранилища</p>
                <h2><?= $selectedMeta ? htmlspecialchars($selectedMeta['title']) : 'Создай первую заметку мира' ?></h2>
            </div>
            <?php if ($selectedPath !== ''): ?>
                <div class="actions">
                    <a class="btn <?= $mode === 'view' ? 'primary' : '' ?>" href="?file=<?= rawurlencode($selectedPath) ?>&mode=view">Просмотр</a>
                    <a class="btn <?= $mode === 'edit' ? 'primary' : '' ?>" href="?file=<?= rawurlencode($selectedPath) ?>&mode=edit">Редактировать</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="layout">
            <section class="<?= $mode === 'edit' && $selectedPath !== '' ? 'editor-card' : 'viewer-card' ?>">
                <?php if ($selectedPath === ''): ?>
                    <div class="empty">В хранилище пока нет Markdown-файлов. Создай заметку через панель справа.</div>
                <?php elseif ($mode === 'edit'): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="path" value="<?= htmlspecialchars($selectedPath) ?>">
                        <textarea name="content" spellcheck="true"><?= htmlspecialchars($selectedContent) ?></textarea>
                        <div class="actions">
                            <button class="btn primary" type="submit">Сохранить Markdown</button>
                            <a class="btn" href="?file=<?= rawurlencode($selectedPath) ?>&mode=view">Отмена</a>
                        </div>
                    </form>
                <?php else: ?>
                    <article class="viewer">
                        <?= renderMarkdown($selectedContent) ?>
                    </article>
                <?php endif; ?>
            </section>

            <aside>
                <section class="panel">
                    <h3>Новая сущность</h3>
                    <form class="create-form" method="post">
                        <input type="hidden" name="action" value="create">
                        <label>Название
                            <input name="title" required placeholder="Например: Меч Серого Великана">
                        </label>
                        <label>Шаблон
                            <select name="template" id="template-select">
                                <?php foreach ($TEMPLATES as $key => $template): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" data-category="<?= htmlspecialchars($template['category']) ?>"><?= htmlspecialchars($template['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Раздел
                            <select name="category" id="category-select">
                                <?php foreach ($CATEGORIES as $key => $category): ?>
                                    <option value="<?= htmlspecialchars($key) ?>"><?= $category['icon'] ?> <?= htmlspecialchars($category['label']) ?> — <?= htmlspecialchars($category['hint']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="btn primary" type="submit">Создать и открыть</button>
                    </form>
                </section>

                <?php if ($selectedMeta): ?>
                    <section class="panel" style="margin-top: 22px;">
                        <h3>Паспорт заметки</h3>
                        <div class="meta-grid">
                            <div class="meta-row"><b>Путь</b><?= htmlspecialchars($selectedMeta['path']) ?></div>
                            <div class="meta-row"><b>Обновлено</b><?= date('d.m.Y H:i', $selectedMeta['updated']) ?></div>
                            <div class="meta-row"><b>Размер</b><?= number_format($selectedMeta['size'], 0, ',', ' ') ?> байт</div>
                            <div class="meta-row"><b>Теги</b>
                                <div class="tags">
                                    <?php foreach ($selectedMeta['tags'] as $tag): ?><span class="tag">#<?= htmlspecialchars($tag) ?></span><?php endforeach; ?>
                                    <?php if ($selectedMeta['tags'] === []): ?><span class="tag">нет тегов</span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="panel advice">
                    <h3>Markdown или база?</h3>
                    <ul>
                        <li><b>Сейчас лучше оставить Markdown</b>: у тебя уже есть Obsidian-хранилище, его легко читать, переносить, версионировать в Git и редактировать без веб-приложения.</li>
                        <li><b>SQLite стоит добавить позже</b>, когда появятся сложные связи: персонаж находится в локации, предмет принадлежит персонажу, событие меняет состояние фракции.</li>
                        <li><b>MySQL/phpMyAdmin не нужен на старте</b>: для личного редактора это лишняя инфраструктура. Гибридный путь практичнее — Markdown как источник текстов, SQLite как индекс связей, поиска и метаданных.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </main>
</div>
<script>
const WORLD_EPOCH = <?= (int) $WORLD_EPOCH ?> * 1000;
const SPEED = <?= (int) $SPEED ?>;
const search = document.getElementById('search');
const templateSelect = document.getElementById('template-select');
const categorySelect = document.getElementById('category-select');

function updateClock() {
    const elapsedSec = (Date.now() - WORLD_EPOCH) / 1000;
    const worldSec = elapsedSec * SPEED;
    const secOfDay = Math.floor(((worldSec % 86400) + 86400) % 86400);
    const h = Math.floor(secOfDay / 3600);
    const m = Math.floor((secOfDay % 3600) / 60);
    const s = Math.floor(secOfDay % 60);
    document.getElementById('world-clock').innerText =
        String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    requestAnimationFrame(updateClock);
}

search?.addEventListener('input', () => {
    const query = search.value.trim().toLowerCase();
    document.querySelectorAll('.file-link').forEach((link) => {
        link.style.display = link.dataset.search.includes(query) ? 'block' : 'none';
    });
});

templateSelect?.addEventListener('change', () => {
    const category = templateSelect.selectedOptions[0]?.dataset.category;
    if (category && categorySelect) {
        categorySelect.value = category;
    }
});

templateSelect?.dispatchEvent(new Event('change'));
updateClock();
</script>
</body>
</html>
