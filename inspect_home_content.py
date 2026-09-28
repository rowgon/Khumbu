import subprocess
import json

MYSQL_EXE = r"C:\xampp\mysql\bin\mysql.exe"

print("Checking post_content of ID 9:")
res_content = subprocess.run([
    MYSQL_EXE, "-u", "root", "teknopremium", "-e",
    "SELECT LEFT(post_content, 1000) FROM wp_posts WHERE ID = 9;"
], capture_output=True, text=True)
print(res_content.stdout)

print("\nChecking if _elementor_data exists for ID 9:")
res_meta = subprocess.run([
    MYSQL_EXE, "-u", "root", "teknopremium", "-e",
    "SELECT meta_key, LENGTH(meta_value) FROM wp_postmeta WHERE post_id = 9 AND meta_key IN ('_elementor_data', '_elementor_edit_mode', '_elementor_template_type');"
], capture_output=True, text=True)
print(res_meta.stdout)
