import unittest
import os
import shutil
import tempfile
import yaml
from models import FileModel

class TestFileModel(unittest.TestCase):
    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.model = FileModel(self.test_dir)

    def tearDown(self):
        shutil.rmtree(self.test_dir)

    def test_save_and_read_file(self):
        path = "test.md"
        meta = {"tags": ["a", "b"], "title": "Test"}
        body = "Content"
        self.model.save_file(path, meta, body)

        res = self.model.read_file(path)
        self.assertEqual(res['meta']['tags'], ["a", "b"])
        self.assertEqual(res['body'], "Content")

    def test_get_tree(self):
        os.makedirs(os.path.join(self.test_dir, "folder"))
        with open(os.path.join(self.test_dir, "folder", "file.md"), "w") as f:
            f.write("test")

        tree = self.model.get_tree()
        self.assertEqual(len(tree), 1)
        self.assertEqual(tree[0]['name'], "folder")
        self.assertEqual(len(tree[0]['children']), 1)

    def test_get_all_tags(self):
        self.model.save_file("f1.md", {"tags": ["tag1"]}, "b")
        self.model.save_file("f2.md", {"tags": ["tag2", "tag1"]}, "b")

        tags = self.model.get_all_tags()
        self.assertEqual(tags, ["tag1", "tag2"])

if __name__ == "__main__":
    unittest.main()
