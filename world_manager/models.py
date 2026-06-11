import os
import yaml
import re

class FileModel:
    def __init__(self, base_path):
        self.base_path = os.path.abspath(base_path)

    def _get_full_path(self, relative_path):
        # Prevent path traversal
        full_path = os.path.abspath(os.path.join(self.base_path, relative_path.lstrip('/')))
        if not full_path.startswith(self.base_path):
            raise ValueError("Access denied: Path traversal detected")
        return full_path

    def read_file(self, relative_path):
        try:
            full_path = self._get_full_path(relative_path)
        except ValueError:
            return None

        if not os.path.exists(full_path):
            return None

        with open(full_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Parse YAML frontmatter
        meta = {}
        body = content
        if content.startswith('---'):
            parts = content.split('---', 2)
            if len(parts) >= 3:
                try:
                    meta = yaml.safe_load(parts[1]) or {}
                    body = parts[2].strip()
                except yaml.YAMLError:
                    pass

        return {
            'path': relative_path,
            'meta': meta,
            'body': body
        }

    def save_file(self, relative_path, meta, body):
        full_path = self._get_full_path(relative_path)
        os.makedirs(os.path.dirname(full_path), exist_ok=True)

        content = "---\n"
        content += yaml.dump(meta, allow_unicode=True, default_flow_style=False)
        content += "---\n\n"
        content += body

        with open(full_path, 'w', encoding='utf-8') as f:
            f.write(content)

        return True

    def get_tree(self, path=''):
        full_path = self._get_full_path(path)
        tree = []
        if not os.path.exists(full_path):
            return tree

        for entry in sorted(os.listdir(full_path)):
            if entry.startswith('.') or entry == 'world_manager':
                continue

            entry_path = os.path.join(path, entry)
            abs_entry_path = os.path.join(full_path, entry)

            is_dir = os.path.isdir(abs_entry_path)
            item = {
                'name': entry,
                'path': entry_path,
                'is_dir': is_dir
            }
            if is_dir:
                item['children'] = self.get_tree(entry_path)

            tree.append(item)
        return tree

    def get_all_tags(self):
        tags = set()
        for root, dirs, files in os.walk(self.base_path):
            for file in files:
                if file.endswith('.md'):
                    res = self.read_file(os.path.relpath(os.path.join(root, file), self.base_path))
                    if res and 'tags' in res['meta'] and res['meta']['tags']:
                        if isinstance(res['meta']['tags'], list):
                            tags.update(res['meta']['tags'])
                        elif isinstance(res['meta']['tags'], str):
                            tags.add(res['meta']['tags'])
        return sorted(list(tags))

    def search_files(self, tag=None, query=None):
        results = []
        for root, dirs, files in os.walk(self.base_path):
            for file in files:
                if file.endswith('.md'):
                    rel_path = os.path.relpath(os.path.join(root, file), self.base_path)
                    res = self.read_file(rel_path)
                    if not res: continue

                    match = True
                    if tag:
                        file_tags = res['meta'].get('tags', [])
                        if not isinstance(file_tags, list):
                            file_tags = [file_tags]
                        if tag not in file_tags:
                            match = False

                    if query and match:
                        if query.lower() not in res['body'].lower() and query.lower() not in rel_path.lower():
                            match = False

                    if match:
                        results.append({
                            'path': rel_path,
                            'title': res['meta'].get('title', file),
                            'tags': res['meta'].get('tags', [])
                        })
        return results
