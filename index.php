<?php

declare(strict_types=1);

$config = require __DIR__ . '/app/config.php';
require __DIR__ . '/app/Support.php';
require __DIR__ . '/app/WorldRepository.php';

$repo = new WorldRepository($config['vault_dir']);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            $path = normalize_relative_path((string) ($_POST['path'] ?? ''));
            $repo->save($path, (string) ($_POST['content'] ?? ''));
            header('Location: ' . url_for('entry', ['file' => $path, 'saved' => 1]));
            exit;
        }
        if ($action === 'create') {
            $templateKey = (string) ($_POST['template'] ?? 'blank');
            $template = $config['templates'][$templateKey] ?? $config['templates']['blank'];
            $category = normalize_relative_path((string) ($_POST['category'] ?? $template['category']));
            $title = (string) ($_POST['title'] ?? 'Новая запись');
            $path = $repo->create($category, $title, $template['body']);
            header('Location: ' . url_for('forge', ['file' => $path, 'created' => 1]));
            exit;
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$files = $repo->list();
$grouped = $repo->grouped($files);
$stats = $repo->stats($files, $config['categories']);
$world = world_time_data($config['world_epoch'], $config['speed'], $config['months']);
$page = (string) ($_GET['page'] ?? 'dashboard');
$validPages = ['dashboard', 'codex', 'entry', 'forge', 'new', 'map', 'timeline', 'systems'];
$page = in_array($page, $validPages, true) ? $page : 'dashboard';

$selectedPath = normalize_relative_path((string) ($_GET['file'] ?? ''));
$selected = $selectedPath !== '' ? $repo->find($files, $selectedPath) : null;
$selectedContent = '';
if (($page === 'entry' || $page === 'forge') && $selected) {
    $selectedContent = $repo->read($selected['path']);
}

$recent = $files;
usort($recent, static fn (array $a, array $b): int => $b['updated'] <=> $a['updated']);
$recent = array_slice($recent, 0, 6);
$locations = $repo->byPrefix($files, '07_WORLD');
$timeline = array_merge($repo->byPrefix($files, '03_TIMELINE'), $repo->byPrefix($files, '06_WORLD_STATE'));
$systemFiles = $repo->byPrefix($files, '02_SYSTEMS');

function active_nav(string $page, string $current): string
{
    return $page === $current ? ' is-active' : '';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Век Истока — RPG World Editor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;900&family=Inter:wght@400;500;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body data-epoch="<?= (int) $config['world_epoch'] ?>" data-speed="<?= (int) $config['speed'] ?>">
<div class="app-shell">
    <header class="game-header">
        <a class="sigil" href="<?= h(url_for('dashboard')) ?>">
            <img src="MD/05_VISUALS/Лого/Лого.png" alt="Логотип Века Истока">
            <span><b>Век Истока</b><small>World Master Console</small></span>
        </a>

        <nav class="main-nav" aria-label="Главная навигация">
            <a class="<?= active_nav($page, 'dashboard') ?>" href="<?= h(url_for('dashboard')) ?>"><span>✧</span>Командный зал</a>
            <a class="<?= active_nav($page, 'codex') ?>" href="<?= h(url_for('codex')) ?>"><span>▣</span>Кодекс записей</a>
            <a class="<?= active_nav($page, 'new') ?>" href="<?= h(url_for('new')) ?>"><span>✚</span>Создать сущность</a>
            <a class="<?= active_nav($page, 'map') ?>" href="<?= h(url_for('map')) ?>"><span>⌖</span>Карта мира</a>
            <a class="<?= active_nav($page, 'timeline') ?>" href="<?= h(url_for('timeline')) ?>"><span>⌛</span>Хроника</a>
            <a class="<?= active_nav($page, 'systems') ?>" href="<?= h(url_for('systems')) ?>"><span>⚙</span>Системы мира</a>
        </nav>

        <div class="header-right">
            <div class="time-orb <?= $world['isDay'] ? 'is-day' : 'is-night' ?>">
                <div class="time-orb-info">
                    <span><?= $world['isDay'] ? 'День Истока' : 'Ночь Истока' ?></span>
                    <small><?= h($world['date']) ?> • <?= h($world['monthName']) ?> • год <?= h($world['year']) ?></small>
                </div>
                <strong id="world-clock">00:00:00</strong>
            </div>

            <div class="sidebar-footer">
                <b><?= count($files) ?></b>
                <span>MD</span>
            </div>
        </div>
    </header>

    <main class="game-main">
        <?php if (isset($_GET['saved'])): ?><div class="toast success">Запись сохранена в Markdown-хранилище.</div><?php endif; ?>
        <?php if (isset($_GET['created'])): ?><div class="toast success">Сущность создана. Теперь можно заполнить карточку.</div><?php endif; ?>
        <?php if ($error): ?><div class="toast danger"><?= h($error) ?></div><?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
            <section class="hero-panel">
                <div>
                    <p class="eyebrow">RPG редактор мира</p>
                    <h1>Командный зал мастера мира</h1>
                    <p>Это уже не лендинг, а интерфейс как в игре: отдельные экраны для кодекса, карты, хроники, систем и кузницы сущностей. Тексты остаются в Markdown, поэтому их можно продолжать открывать в Obsidian.</p>
                    <div class="hero-actions">
                        <a class="game-button primary" href="<?= h(url_for('new')) ?>">Создать новую сущность</a>
                        <a class="game-button" href="<?= h(url_for('codex')) ?>">Открыть кодекс</a>
                    </div>
                </div>
                <div class="portal-card">
                    <span>Стабильная модель времени</span>
                    <strong id="hero-clock">00:00:00</strong>
                    <small>Скорость мира ×<?= h($config['speed']) ?></small>
                </div>
            </section>

            <section class="stat-grid">
                <?php foreach ($config['categories'] as $path => $category): ?>
                    <a class="stat-card" href="<?= h(url_for('codex', ['section' => $path])) ?>">
                        <span><?= h($category['icon']) ?></span>
                        <b><?= h($stats[$path] ?? 0) ?></b>
                        <small><?= h($category['label']) ?></small>
                    </a>
                <?php endforeach; ?>
            </section>

            <section class="two-column full-width">
                <div class="game-panel">
                    <div class="panel-title"><span>Последние изменения</span><a href="<?= h(url_for('codex')) ?>">Все записи</a></div>
                    <div class="quest-list">
                        <?php foreach ($recent as $file): ?>
                            <a class="quest-row" href="<?= h(url_for('entry', ['file' => $file['path']])) ?>">
                                <b><?= h($file['title']) ?></b>
                                <small><?= h($file['path']) ?> • <?= date('d.m.Y H:i', $file['updated']) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'codex'): ?>
            <section class="screen-header">
                <p class="eyebrow">Библиотека мира</p>
                <h1>Кодекс записей</h1>
                <p>Фильтруй, открывай и редактируй все Markdown-файлы хранилища.</p>
            </section>
            <section class="codex-layout">
                <aside class="codex-filter game-panel">
                    <input class="game-input" id="codex-search" type="search" placeholder="Поиск по названию, тегам, пути…">
                    <div class="filter-pills">
                        <button class="pill is-active" type="button" data-section="">Все</button>
                        <?php foreach ($config['categories'] as $path => $category): ?>
                            <button class="pill" type="button" data-section="<?= h(mb_strtolower($path)) ?>"><?= h($category['icon']) ?> <?= h($category['label']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </aside>
                <div class="codex-grid" id="codex-grid">
                    <?php foreach ($files as $file): ?>
                        <?php $search = mb_strtolower($file['title'] . ' ' . $file['path'] . ' ' . implode(' ', $file['tags'])); ?>
                        <article class="codex-card" data-search="<?= h($search) ?>" data-path="<?= h(mb_strtolower($file['path'])) ?>">
                            <small><?= h($file['folder']) ?></small>
                            <h2><?= h($file['title']) ?></h2>
                            <p><?= h($file['excerpt']) ?></p>
                            <div class="tagline">
                                <?php foreach (array_slice($file['tags'], 0, 3) as $tag): ?><span>#<?= h($tag) ?></span><?php endforeach; ?>
                            </div>
                            <div class="card-actions">
                                <a href="<?= h(url_for('entry', ['file' => $file['path']])) ?>">Читать</a>
                                <a href="<?= h(url_for('forge', ['file' => $file['path']])) ?>">Править</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'entry'): ?>
            <?php if (!$selected): ?>
                <section class="empty-state game-panel"><h1>Запись не найдена</h1><a class="game-button" href="<?= h(url_for('codex')) ?>">Вернуться в кодекс</a></section>
            <?php else: ?>
                <section class="reader-shell">
                    <aside class="game-panel dossier-panel">
                        <span class="dossier-label">Досье</span>
                        <h2><?= h($selected['title']) ?></h2>
                        <p><?= h($selected['path']) ?></p>
                        <div class="tagline"><?php foreach ($selected['tags'] as $tag): ?><span>#<?= h($tag) ?></span><?php endforeach; ?></div>
                        <a class="game-button primary" href="<?= h(url_for('forge', ['file' => $selected['path']])) ?>">Открыть кузницу</a>
                        <a class="game-button" href="<?= h(url_for('codex')) ?>">Назад в кодекс</a>
                    </aside>
                    <article class="markdown-reader game-panel"><?= render_markdown($selectedContent) ?></article>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($page === 'forge'): ?>
            <?php if (!$selected): ?>
                <section class="empty-state game-panel"><h1>Нечего редактировать</h1><a class="game-button" href="<?= h(url_for('codex')) ?>">Выбрать запись</a></section>
            <?php else: ?>
                <section class="screen-header compact">
                    <p class="eyebrow">Кузница текста</p>
                    <h1><?= h($selected['title']) ?></h1>
                    <p><?= h($selected['path']) ?></p>
                </section>
                <form class="forge-panel" method="post">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="path" value="<?= h($selected['path']) ?>">
                    <textarea name="content" spellcheck="true"><?= h($selectedContent) ?></textarea>
                    <div class="forge-actions">
                        <button class="game-button primary" type="submit">Сохранить в MD</button>
                        <a class="game-button" href="<?= h(url_for('entry', ['file' => $selected['path']])) ?>">Просмотр</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($page === 'new'): ?>
            <section class="screen-header">
                <p class="eyebrow">Создание сущности</p>
                <h1>Выбери архетип записи</h1>
                <p>Каждая карточка создаёт Markdown-файл в нужном разделе и сразу отправляет тебя в редактор.</p>
            </section>
            <form class="creation-board" method="post">
                <input type="hidden" name="action" value="create">
                <div class="creation-controls game-panel">
                    <label>Название сущности<input class="game-input" name="title" required placeholder="Например: Башня Пепельного Света"></label>
                    <label>Раздел<select class="game-input" name="category" id="category-select"><?php foreach ($config['categories'] as $path => $category): ?><option value="<?= h($path) ?>"><?= h($category['icon']) ?> <?= h($category['label']) ?></option><?php endforeach; ?></select></label>
                    <button class="game-button primary" type="submit">Создать</button>
                </div>
                <div class="template-grid">
                    <?php foreach ($config['templates'] as $key => $template): ?>
                        <label class="template-card">
                            <input type="radio" name="template" value="<?= h($key) ?>" data-category="<?= h($template['category']) ?>" <?= $key === 'character' ? 'checked' : '' ?>>
                            <span><?= h($template['icon']) ?></span>
                            <b><?= h($template['label']) ?></b>
                            <small><?= h($config['categories'][$template['category']]['hint'] ?? 'Свободная заметка') ?></small>
                        </label>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($page === 'map'): ?>
            <section class="screen-header">
                <p class="eyebrow">Экран разведки</p>
                <h1>Карта мира</h1>
                <p>Карта и список локаций живут отдельно от редактора, как экран в RPG-журнале.</p>
            </section>
            <section class="map-layout">
                <div class="map-frame game-panel"><img src="MD/05_VISUALS/Карты/Карта v.1.png" alt="Карта мира"></div>
                <aside class="game-panel location-list">
                    <div class="panel-title"><span>Локации</span><b><?= count($locations) ?></b></div>
                    <?php foreach ($locations as $location): ?>
                        <a class="quest-row" href="<?= h(url_for('entry', ['file' => $location['path']])) ?>"><b><?= h($location['title']) ?></b><small><?= h($location['excerpt']) ?></small></a>
                    <?php endforeach; ?>
                </aside>
            </section>
        <?php endif; ?>

        <?php if ($page === 'timeline'): ?>
            <section class="screen-header">
                <p class="eyebrow">Хроника</p>
                <h1>История и состояние мира</h1>
                <p>События, глобальный таймлайн и текущие состояния вынесены в отдельный экран.</p>
            </section>
            <section class="timeline-lane">
                <?php foreach ($timeline as $event): ?>
                    <article class="timeline-node">
                        <span></span>
                        <div class="game-panel">
                            <small><?= h($event['path']) ?></small>
                            <h2><?= h($event['title']) ?></h2>
                            <p><?= h($event['excerpt']) ?></p>
                            <a href="<?= h(url_for('entry', ['file' => $event['path']])) ?>">Открыть событие →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'systems'): ?>
            <section class="screen-header">
                <p class="eyebrow">Правила игры мира</p>
                <h1>Системы, ранги и механики</h1>
                <p>Здесь собраны заметки, которые задают правила Истока, ранги и ограничения.</p>
            </section>
            <section class="system-grid">
                <?php foreach ($systemFiles as $system): ?>
                    <article class="codex-card system-card">
                        <small><?= h($system['path']) ?></small>
                        <h2><?= h($system['title']) ?></h2>
                        <p><?= h($system['excerpt']) ?></p>
                        <div class="card-actions"><a href="<?= h(url_for('entry', ['file' => $system['path']])) ?>">Изучить</a><a href="<?= h(url_for('forge', ['file' => $system['path']])) ?>">Править</a></div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
<script src="public/assets/js/app.js"></script>
</body>
</html>
