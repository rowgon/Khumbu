import subprocess
import os

php_wrapper = """<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Khumbu/teknopremium/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require __DIR__ . '/index.php';
"""

with open(r"C:\xampp\htdocs\Khumbu\teknopremium\render_home.php", "w") as f:
    f.write(php_wrapper)

res = subprocess.run(
    [r"C:\xampp\php\php.exe", "render_home.php"],
    cwd=r"C:\xampp\htdocs\Khumbu\teknopremium",
    capture_output=True,
    text=True
)
print("STDOUT length:", len(res.stdout))
print("First 400 chars:")
print(res.stdout[:400])
if res.stderr:
    print("STDERR:")
    print(res.stderr[:400])
os.remove(r"C:\xampp\htdocs\Khumbu\teknopremium\render_home.php")
