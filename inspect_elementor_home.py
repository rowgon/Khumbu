import subprocess
import json

MYSQL_EXE = r"C:\xampp\mysql\bin\mysql.exe"

res = subprocess.run([
    MYSQL_EXE, "-u", "root", "teknopremium", "-e",
    "SELECT meta_value FROM wp_postmeta WHERE post_id = 9 AND meta_key = '_elementor_data';"
], capture_output=True, text=True)

# First line is header 'meta_value', rest is JSON
lines = res.stdout.split('\n', 1)
if len(lines) > 1:
    raw_json = lines[1]
    try:
        data = json.loads(raw_json)
        # Recursively search for text, title, editor, heading
        texts = []
        def extract_text(node):
            if isinstance(node, dict):
                widget_type = node.get('widgetType', '')
                settings = node.get('settings', {})
                if 'title' in settings and isinstance(settings['title'], str):
                    texts.append((widget_type or 'heading', settings['title']))
                if 'editor' in settings and isinstance(settings['editor'], str):
                    texts.append((widget_type or 'text_editor', settings['editor']))
                if 'text' in settings and isinstance(settings['text'], str):
                    texts.append((widget_type or 'button', settings['text']))
                for k, v in node.items():
                    extract_text(v)
            elif isinstance(node, list):
                for item in node:
                    extract_text(item)
        extract_text(data)
        print(f"Extracted {len(texts)} text elements:")
        for idx, (t, val) in enumerate(texts[:30]):
            clean_val = val.replace('\n', ' ')[:100]
            print(f"{idx+1}. [{t}] {clean_val}")
    except Exception as e:
        print("JSON parse error:", e)
