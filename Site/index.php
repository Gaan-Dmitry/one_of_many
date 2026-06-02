<?php
session_start();

$WORLD_EPOCH = strtotime('2026-06-01 00:00:00 UTC');
$SPEED = 5;

$MONTHS = [
    'Лютохлад', 'Ветровей', 'Таловод',
    'Зеленорост', 'Цветель', 'Влажник',
    'Ярило', 'Зрельник', 'Страдник',
    'Листопад', 'Грязовец', 'Студень',
];

$now = time();
$elapsed = max(0, $now - $WORLD_EPOCH);
$worldSeconds = $elapsed * $SPEED;
$worldDays = (int) floor($worldSeconds / 86400);
$year = (int) floor($worldDays / 360) + 1;
$dayOfYear = $worldDays % 360;
$month = (int) floor($dayOfYear / 30);
$day = ($dayOfYear % 30) + 1;
$secOfDay = $worldSeconds % 86400;
$h = (int) floor($secOfDay / 3600);
$isDay = ($h >= 6 && $h < 18);

$storageDir = __DIR__ . '/data';
$storageFile = $storageDir . '/stories.json';

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function readStories(string $storageFile): array
{
    if (!file_exists($storageFile)) {
        return [];
    }

    $contents = file_get_contents($storageFile);
    if ($contents === false || trim($contents) === '') {
        return [];
    }

    $decoded = json_decode($contents, true);
    return is_array($decoded) ? $decoded : [];
}

function writeStories(string $storageFile, array $stories): void
{
    file_put_contents(
        $storageFile,
        json_encode(array_values($stories), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

function excerpt(string $text, int $limit = 210): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($plain) <= $limit) {
        return $plain;
    }

    return mb_substr($plain, 0, $limit - 1) . '…';
}

function loadLoreCards(): array
{
    $root = dirname(__DIR__);
    $sources = [
        'Глобальный таймлайн' => '03_TIMELINE/Глобальный таймлайн.md',
        'Активные события' => '07_WORLD_STATE/Активные события.md',
        'Флора и фауна' => '01_WORLD/Флора и Фауна.md',
        'Арка 1' => '05_STORIES/Арка_1.md',
    ];

    $cards = [];
    foreach ($sources as $title => $relativePath) {
        $path = $root . '/' . $relativePath;
        if (!file_exists($path)) {
            continue;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }

        $cards[] = [
            'title' => $title,
            'path' => $relativePath,
            'excerpt' => excerpt($content, 360),
        ];
    }

    return $cards;
}

function buildPrompt(string $mode, string $title, string $content, string $notes, array $loreCards): string
{
    $labels = [
        'story' => 'Продолжи сцену и предложи 3 варианта развития истории.',
        'event' => 'Опиши событие: причины, участников, последствия и крючки для сюжета.',
        'flora_fauna' => 'Опиши флору или фауну: внешний вид, поведение, среду обитания, опасности и применение в сюжете.',
    ];

    $context = array_map(
        fn(array $card): string => $card['title'] . ': ' . $card['excerpt'],
        $loreCards
    );

    return implode("\n\n", [
        'Ты — литературный помощник для мира «Век Истока». Пиши атмосферно, по-русски, без противоречий с контекстом.',
        'Задача: ' . ($labels[$mode] ?? $labels['story']),
        'Название: ' . $title,
        'Черновик автора: ' . $content,
        'Заметки автора: ' . $notes,
        'Контекст мира: ' . implode("\n", $context),
    ]);
}

function requestAiSuggestion(string $prompt): array
{
    $apiKey = getenv('DASHSCOPE_API_KEY') ?: '';
    $endpoint = getenv('DASHSCOPE_API_ENDPOINT') ?: 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions';
    $model = getenv('DASHSCOPE_MODEL') ?: 'qwen-plus';

    if ($apiKey === '') {
        return [
            'ok' => false,
            'message' => 'AI-помощник готов к подключению. Укажите переменные окружения DASHSCOPE_API_KEY, при необходимости DASHSCOPE_API_ENDPOINT и DASHSCOPE_MODEL.',
            'prompt' => $prompt,
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'message' => 'Для обращения к API нужна PHP-библиотека cURL.',
            'prompt' => $prompt,
        ];
    }

    $payload = json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Ты помогаешь автору писать художественные истории и лор мира.'],
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.8,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status >= 400) {
        return [
            'ok' => false,
            'message' => 'API не вернул успешный ответ' . ($error ? ': ' . $error : '.') . ' HTTP: ' . $status,
            'prompt' => $prompt,
        ];
    }

    $decoded = json_decode($response, true);
    $text = $decoded['choices'][0]['message']['content'] ?? '';

    return [
        'ok' => $text !== '',
        'message' => $text !== '' ? $text : 'Ответ API получен, но текст подсказки не найден.',
        'prompt' => $prompt,
    ];
}

$stories = readStories($storageFile);
$loreCards = loadLoreCards();
$notice = '';
$aiResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $notice = 'Сессия устарела. Обновите страницу и повторите действие.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_story') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? 'story'));
            $content = trim((string) ($_POST['content'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));

            if ($title === '' || $content === '') {
                $notice = 'Укажите название и текст записи.';
            } else {
                array_unshift($stories, [
                    'id' => bin2hex(random_bytes(8)),
                    'title' => mb_substr($title, 0, 140),
                    'category' => in_array($category, ['story', 'event', 'flora_fauna'], true) ? $category : 'story',
                    'content' => $content,
                    'notes' => $notes,
                    'world_date' => sprintf('%02d.%02d.%02d', $day, $month + 1, $year),
                    'created_at' => gmdate('c'),
                ]);
                writeStories($storageFile, $stories);
                $notice = 'Запись сохранена в хронику.';
            }
        }

        if ($action === 'ask_ai') {
            $title = trim((string) ($_POST['title'] ?? 'Без названия'));
            $mode = trim((string) ($_POST['category'] ?? 'story'));
            $content = trim((string) ($_POST['content'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $prompt = buildPrompt($mode, $title, $content, $notes, $loreCards);
            $aiResult = requestAiSuggestion($prompt);
        }
    }
}

$categoryLabels = [
    'story' => 'История',
    'event' => 'Событие',
    'flora_fauna' => 'Флора / фауна',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ВЕК ИСТОКА — хроника историй</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@300;500;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #c9a24b;
    --gold-soft: rgba(201, 162, 75, 0.18);
    --blue: #4aa3ff;
    --ink: #05060a;
    --panel: rgba(12, 15, 23, 0.82);
    --panel-strong: rgba(20, 24, 35, 0.92);
    --text: #eae6dc;
    --muted: #a9a399;
    --line: rgba(234, 230, 220, 0.12);
}

* { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    background: var(--ink);
    color: var(--text);
    font-family: 'Unbounded', sans-serif;
    overflow-x: hidden;
}

.bg {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at var(--mx, 50%) var(--my, 50%), rgba(201, 162, 75, 0.20), transparent 34%),
        radial-gradient(circle at 15% 20%, rgba(74, 163, 255, 0.12), transparent 28%),
        linear-gradient(180deg, #05060a, #0a0d14 48%, #111018);
    z-index: -3;
}

.bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: linear-gradient(to bottom, black, transparent 80%);
}

canvas {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: -2;
}

.page {
    width: min(1440px, calc(100% - 40px));
    margin: 0 auto;
    padding: 30px 0 52px;
}

.hero {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) auto;
    gap: 24px;
    align-items: stretch;
    margin-bottom: 28px;
}

.brand, .clock, .panel {
    border: 1px solid var(--line);
    background: var(--panel);
    backdrop-filter: blur(18px);
    border-radius: 28px;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
}

.brand {
    padding: 32px;
    position: relative;
    overflow: hidden;
}

.brand::before {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: var(--gold-soft);
    filter: blur(22px);
    right: -90px;
    top: -110px;
}

.eyebrow {
    color: var(--gold);
    letter-spacing: 0.22em;
    text-transform: uppercase;
    font-size: 12px;
    margin: 0 0 14px;
}

h1 {
    margin: 0;
    font-size: clamp(34px, 6vw, 82px);
    line-height: 0.95;
    max-width: 900px;
}

.lead {
    color: var(--muted);
    font-size: 15px;
    line-height: 1.8;
    max-width: 820px;
    margin: 22px 0 0;
}

.clock {
    min-width: 310px;
    padding: 28px;
    display: grid;
    align-content: center;
    text-align: right;
}

.time {
    font-family: 'Orbitron', monospace;
    font-size: clamp(42px, 6vw, 76px);
    font-weight: 700;
    letter-spacing: 4px;
    color: var(--gold);
    text-shadow: 0 0 35px rgba(201, 162, 75, 0.4);
}

.cycle {
    margin-top: 10px;
    font-size: 12px;
    letter-spacing: 0.36em;
    color: <?= $isDay ? 'var(--gold)' : 'var(--blue)' ?>;
}

.date {
    margin-top: 16px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

.grid {
    display: grid;
    grid-template-columns: minmax(320px, 1fr) minmax(340px, 0.82fr);
    gap: 24px;
    align-items: start;
}

.panel {
    padding: 24px;
}

.panel h2, .panel h3 {
    margin: 0 0 18px;
}

.notice {
    margin-bottom: 18px;
    padding: 14px 16px;
    border: 1px solid rgba(201, 162, 75, 0.32);
    border-radius: 16px;
    background: rgba(201, 162, 75, 0.10);
    color: #f4d990;
}

.form-grid {
    display: grid;
    gap: 16px;
}

label {
    display: grid;
    gap: 8px;
    color: var(--muted);
    font-size: 13px;
}

input, select, textarea {
    width: 100%;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.055);
    color: var(--text);
    padding: 14px 16px;
    font: inherit;
    outline: none;
}

select option { background: #111018; }
textarea { min-height: 280px; resize: vertical; line-height: 1.7; }
textarea.notes { min-height: 120px; }

input:focus, select:focus, textarea:focus {
    border-color: rgba(201, 162, 75, 0.72);
    box-shadow: 0 0 0 4px rgba(201, 162, 75, 0.10);
}

.actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

button, .ghost-button {
    border: 0;
    border-radius: 999px;
    padding: 14px 20px;
    color: #14110a;
    background: linear-gradient(135deg, #f1d17a, var(--gold));
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
}

button.secondary, .ghost-button {
    color: var(--text);
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid var(--line);
}

.hint {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
}

.story-list {
    display: grid;
    gap: 14px;
}

.story-card, .lore-card, .ai-box {
    border: 1px solid var(--line);
    border-radius: 20px;
    background: var(--panel-strong);
    padding: 18px;
}

.story-card h3 {
    margin: 0 0 10px;
    font-size: 18px;
}

.meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 12px;
}

.badge {
    border: 1px solid rgba(201, 162, 75, 0.35);
    color: #f3d27c;
    border-radius: 999px;
    padding: 5px 9px;
}

.story-text {
    color: #d8d2c4;
    font-size: 13px;
    line-height: 1.8;
    white-space: pre-wrap;
}

.sidebar {
    display: grid;
    gap: 24px;
}

.lore-list {
    display: grid;
    gap: 12px;
}

.lore-card h3 {
    font-size: 15px;
    margin: 0 0 10px;
}

.lore-card p, .ai-box p, .ai-box pre {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
    margin: 0;
}

.ai-box pre {
    white-space: pre-wrap;
    margin-top: 12px;
    padding: 12px;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.28);
    max-height: 280px;
    overflow: auto;
}

.empty {
    border: 1px dashed rgba(234, 230, 220, 0.20);
    border-radius: 20px;
    padding: 24px;
    color: var(--muted);
    text-align: center;
    line-height: 1.7;
}

@media (max-width: 980px) {
    .hero, .grid { grid-template-columns: 1fr; }
    .clock { text-align: left; min-width: 0; }
}

@media (max-width: 620px) {
    .page { width: min(100% - 24px, 1440px); padding-top: 16px; }
    .brand, .clock, .panel { border-radius: 22px; padding: 20px; }
    .actions { display: grid; }
    button { width: 100%; }
}
</style>
</head>
<body>
<div class="bg" id="bg"></div>
<canvas id="fx"></canvas>

<main class="page">
    <section class="hero">
        <div class="brand">
            <p class="eyebrow">Век Истока • интерактивная хроника</p>
            <h1>Пишите историю мира и сохраняйте её в живую летопись.</h1>
            <p class="lead">Веб-приложение объединяет редактор сюжетов, события, флору и фауну. Записи сохраняются на сайте, отображаются ниже и могут отправляться в AI-помощник для развития сцен, описаний и лора.</p>
        </div>
        <aside class="clock" aria-label="Мировое время">
            <div class="time" id="time"></div>
            <div class="cycle"><?= $isDay ? 'ДЕНЬ ИСТОКА' : 'НОЧЬ ИСТОКА' ?></div>
            <div class="date">
                <?= e(sprintf('%02d.%02d.%02d', $day, $month + 1, $year)) ?><br>
                Месяц: <?= e($MONTHS[$month]) ?> • скорость ×<?= e((string) $SPEED) ?>
            </div>
        </aside>
    </section>

    <section class="grid">
        <div class="panel">
            <h2>Редактор хроники</h2>
            <?php if ($notice !== ''): ?>
                <div class="notice"><?= e($notice) ?></div>
            <?php endif; ?>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <label>
                    Название записи
                    <input name="title" maxlength="140" placeholder="Например: Первый переход через Быстрицу" value="<?= e((string) ($_POST['title'] ?? '')) ?>">
                </label>
                <label>
                    Тип материала
                    <select name="category">
                        <?php $selectedCategory = (string) ($_POST['category'] ?? 'story'); ?>
                        <?php foreach ($categoryLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $selectedCategory === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Текст истории
                    <textarea name="content" placeholder="Пишите сцену, заметку о событии или описание существа…"><?= e((string) ($_POST['content'] ?? '')) ?></textarea>
                </label>
                <label>
                    Заметки для AI-помощника
                    <textarea class="notes" name="notes" placeholder="Тон, конфликт, персонажи, ограничения лора…"><?= e((string) ($_POST['notes'] ?? '')) ?></textarea>
                </label>
                <div class="actions">
                    <button type="submit" name="action" value="save_story">Сохранить в хронику</button>
                    <button class="secondary" type="submit" name="action" value="ask_ai">Попросить AI помочь</button>
                </div>
                <p class="hint">Для подключения Alibaba Cloud Model Studio задайте на сервере <strong>DASHSCOPE_API_KEY</strong>. По желанию можно переопределить <strong>DASHSCOPE_API_ENDPOINT</strong> и <strong>DASHSCOPE_MODEL</strong>.</p>
            </form>

            <?php if ($aiResult !== null): ?>
                <div class="ai-box" style="margin-top: 18px;">
                    <h3><?= $aiResult['ok'] ? 'Ответ AI-помощника' : 'Подготовка AI-запроса' ?></h3>
                    <p><?= e($aiResult['message']) ?></p>
                    <?php if (!$aiResult['ok']): ?>
                        <pre><?= e($aiResult['prompt']) ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sidebar">
            <div class="panel">
                <h2>Опора на лор</h2>
                <div class="lore-list">
                    <?php foreach ($loreCards as $card): ?>
                        <article class="lore-card">
                            <h3><?= e($card['title']) ?></h3>
                            <p><?= e($card['excerpt']) ?></p>
                            <p class="hint" style="margin-top: 10px;">Источник: <?= e($card['path']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </section>

    <section class="panel" style="margin-top: 24px;">
        <h2>Сохранённая история</h2>
        <?php if (count($stories) === 0): ?>
            <div class="empty">Пока хроника пуста. Создайте первую запись — она появится здесь после сохранения.</div>
        <?php else: ?>
            <div class="story-list">
                <?php foreach ($stories as $story): ?>
                    <article class="story-card">
                        <div class="meta">
                            <span class="badge"><?= e($categoryLabels[$story['category']] ?? 'История') ?></span>
                            <span>Дата мира: <?= e((string) ($story['world_date'] ?? '—')) ?></span>
                            <span>Сохранено: <?= e(date('d.m.Y H:i', strtotime((string) ($story['created_at'] ?? 'now')))) ?> UTC</span>
                        </div>
                        <h3><?= e((string) ($story['title'] ?? 'Без названия')) ?></h3>
                        <div class="story-text"><?= e((string) ($story['content'] ?? '')) ?></div>
                        <?php if (!empty($story['notes'])): ?>
                            <p class="hint" style="margin-top: 12px;">Заметки: <?= e((string) $story['notes']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
const WORLD_EPOCH = <?= $WORLD_EPOCH ?> * 1000;
const SPEED = <?= $SPEED ?>;

function updateTime() {
    const elapsedSec = Math.max(0, (Date.now() - WORLD_EPOCH) / 1000);
    const worldSec = elapsedSec * SPEED;
    const secOfDay = Math.floor(worldSec % 86400);
    const h = Math.floor(secOfDay / 3600);
    const m = Math.floor((secOfDay % 3600) / 60);
    const s = Math.floor(secOfDay % 60);

    document.getElementById('time').innerText =
        String(h).padStart(2, '0') + ':' +
        String(m).padStart(2, '0') + ':' +
        String(s).padStart(2, '0');

    requestAnimationFrame(updateTime);
}
updateTime();

document.addEventListener('mousemove', (event) => {
    const x = (event.clientX / innerWidth) * 100;
    const y = (event.clientY / innerHeight) * 100;
    document.documentElement.style.setProperty('--mx', x + '%');
    document.documentElement.style.setProperty('--my', y + '%');
});

const canvas = document.getElementById('fx');
const ctx = canvas.getContext('2d');
let particles = [];

function resizeCanvas() {
    canvas.width = innerWidth;
    canvas.height = innerHeight;
    particles = Array.from({ length: Math.min(180, Math.floor(innerWidth / 8)) }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        r: Math.random() * 1.8 + 0.2,
        d: Math.random() * 1.2 + 0.3,
    }));
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = 'rgba(201, 162, 75, 0.20)';

    particles.forEach((particle) => {
        ctx.beginPath();
        ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
        ctx.fill();
        particle.y -= 0.18 + particle.d;
        particle.x += Math.sin(particle.y * 0.01) * 0.35;

        if (particle.y < -5) {
            particle.y = canvas.height + 5;
            particle.x = Math.random() * canvas.width;
        }
    });

    requestAnimationFrame(draw);
}

resizeCanvas();
draw();
window.addEventListener('resize', resizeCanvas);
</script>
</body>
</html>
