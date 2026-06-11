let currentFile = null;
let currentTags = [];
let allTags = [];

async function init() {
    await loadTree();
    await loadTags();
    setupEventListeners();
}

async function loadTree() {
    const res = await fetch('/api/tree');
    const tree = await res.json();
    const container = document.getElementById('file-tree');
    container.innerHTML = '';
    renderTree(tree, container);

    // Fill folder select for modal
    const folderSelect = document.getElementById('folder-select');
    folderSelect.innerHTML = '<option value="">(Root)</option>';
    fillFolderSelect(tree, folderSelect);

    // Look for templates
    const templateSelect = document.getElementById('template-select');
    templateSelect.innerHTML = '<option value="">Без шаблона</option>';
    findTemplates(tree, templateSelect);
}

function renderTree(items, container) {
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'tree-item ' + (item.is_dir ? 'tree-folder' : 'tree-file');
        div.textContent = (item.is_dir ? '📁 ' : '📄 ') + item.name;
        div.onclick = (e) => {
            e.stopPropagation();
            if (item.is_dir) {
                const childCont = div.nextElementSibling;
                if (childCont && childCont.classList.contains('tree-children')) {
                    childCont.classList.toggle('hidden');
                }
            } else {
                loadFile(item.path);
            }
        };
        container.appendChild(div);
        if (item.is_dir && item.children && item.children.length > 0) {
            const childrenDiv = document.createElement('div');
            childrenDiv.className = 'tree-children hidden';
            renderTree(item.children, childrenDiv);
            container.appendChild(childrenDiv);
        }
    });
}

function fillFolderSelect(items, select, prefix = '') {
    items.forEach(item => {
        if (item.is_dir) {
            const option = document.createElement('option');
            option.value = item.path;
            option.textContent = prefix + item.name;
            select.appendChild(option);
            if (item.children) {
                fillFolderSelect(item.children, select, prefix + item.name + '/');
            }
        }
    });
}

function findTemplates(items, select) {
    items.forEach(item => {
        if (item.is_dir && item.children) {
            findTemplates(item.children, select);
        } else if (item.name.includes('Шаблон')) {
            const option = document.createElement('option');
            option.value = item.path;
            option.textContent = item.path;
            select.appendChild(option);
        }
    });
}

async function loadTags() {
    const res = await fetch('/api/tags');
    allTags = await res.json();
    const container = document.getElementById('tag-cloud');
    container.innerHTML = '';
    allTags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'tag';
        span.textContent = tag;
        span.onclick = () => {
            span.classList.toggle('active');
            filterFiles();
        };
        container.appendChild(span);
    });
}

async function loadFile(path) {
    const res = await fetch(`/api/file?path=${encodeURIComponent(path)}`);
    currentFile = await res.json();

    document.getElementById('editor-section').classList.remove('hidden');
    document.getElementById('list-section').classList.add('hidden');

    document.getElementById('file-path-display').value = currentFile.path;
    document.getElementById('markdown-body').value = currentFile.body;

    renderMetadataFields();
}

function renderMetadataFields() {
    const container = document.getElementById('metadata-fields');
    container.innerHTML = '';

    const meta = currentFile.meta || {};

    // Specifically handle tags
    const tags = meta.tags || [];
    currentTags = Array.isArray(tags) ? tags : [tags];

    const tagDiv = document.createElement('div');
    tagDiv.innerHTML = '<strong>Теги:</strong>';
    currentTags.forEach((tag, idx) => {
        const input = document.createElement('input');
        input.value = tag;
        input.onchange = (e) => currentTags[idx] = e.target.value;
        tagDiv.appendChild(input);
    });
    container.appendChild(tagDiv);

    // Other meta fields
    Object.keys(meta).forEach(key => {
        if (key === 'tags') return;
        const div = document.createElement('div');
        div.innerHTML = `<label>${key}:</label>`;
        const input = document.createElement('input');
        input.value = meta[key];
        input.onchange = (e) => currentFile.meta[key] = e.target.value;
        div.appendChild(input);
        container.appendChild(div);
    });
}

async function saveFile() {
    if (!currentFile) return;

    currentFile.meta.tags = currentTags.filter(t => t.trim() !== '');
    currentFile.body = document.getElementById('markdown-body').value;

    const res = await fetch('/api/file', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(currentFile)
    });

    if (res.ok) {
        alert('Сохранено!');
        loadTree();
        loadTags();
    }
}

function setupEventListeners() {
    document.getElementById('save-btn').onclick = saveFile;

    document.getElementById('add-tag-btn').onclick = () => {
        currentTags.push('');
        renderMetadataFields();
    };

    document.getElementById('new-file-btn').onclick = () => {
        document.getElementById('new-object-modal').classList.remove('hidden');
    };

    document.getElementById('create-cancel').onclick = () => {
        document.getElementById('new-object-modal').classList.add('hidden');
    };

    document.getElementById('create-confirm').onclick = async () => {
        const folder = document.getElementById('folder-select').value;
        const filename = document.getElementById('new-filename').value;
        const templatePath = document.getElementById('template-select').value;

        if (!filename) return alert('Имя файла обязательно');

        const path = (folder ? folder + '/' : '') + (filename.endsWith('.md') ? filename : filename + '.md');

        let meta = { tags: [] };
        let body = '';

        if (templatePath) {
            const res = await fetch(`/api/file?path=${encodeURIComponent(templatePath)}`);
            const template = await res.json();
            meta = template.meta;
            body = template.body;
        }

        const res = await fetch('/api/file', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ path, meta, body })
        });

        if (res.ok) {
            document.getElementById('new-object-modal').classList.add('hidden');
            await loadTree();
            loadFile(path);
        }
    };

    document.getElementById('upload-btn').onclick = () => {
        document.getElementById('image-input').click();
    };

    document.getElementById('image-input').onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        const res = await fetch('/api/upload', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        if (data.success) {
        const serverFilename = data.path.split('/').pop();
        const imgMarkdown = `\n![[${serverFilename}]]\n`;
            document.getElementById('markdown-body').value += imgMarkdown;
        document.getElementById('upload-status').textContent = 'Загружено: ' + serverFilename;
        }
    };

    document.getElementById('search-input').oninput = filterFiles;
}

async function filterFiles() {
    const query = document.getElementById('search-input').value;
    const activeTags = Array.from(document.querySelectorAll('.tag.active')).map(t => t.textContent);

    if (query || activeTags.length > 0) {
        document.getElementById('editor-section').classList.add('hidden');
        document.getElementById('list-section').classList.remove('hidden');

        let url = `/api/files?query=${encodeURIComponent(query)}`;
        if (activeTags.length > 0) {
            url += `&tag=${encodeURIComponent(activeTags[0])}`; // Backend currently supports one tag filter at a time in the simple search
        }

        const res = await fetch(url);
        const files = await res.json();

        renderFileList(files);
    } else {
        document.getElementById('list-section').classList.add('hidden');
    }
}

function renderFileList(files) {
    const container = document.getElementById('file-list');
    container.innerHTML = `<h3>Результаты (${files.length})</h3>`;
    files.forEach(file => {
        const div = document.createElement('div');
        div.className = 'file-list-item';
        div.innerHTML = `<strong>${file.path}</strong> <br> <small>${file.tags.join(', ')}</small>`;
        div.onclick = () => loadFile(file.path);
        container.appendChild(div);
    });
}

init();
