import subprocess
import os

php_wrapper = """<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/Khumbu/teknopremium/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require __DIR__ . '/index.php';
"""

with open(r"C:\xampp\htdocs\Khumbu\teknopremium\render_home.php", "w", encoding="utf-8") as f:
    f.write(php_wrapper)

res = subprocess.run(
    [r"C:\xampp\php\php.exe", "render_home.php"],
    cwd=r"C:\xampp\htdocs\Khumbu\teknopremium",
    capture_output=True,
    text=True,
    encoding="utf-8",
    errors="replace"
)

html = res.stdout
print("Total rendered HTML length:", len(html))

# Let's search for key phrases in the rendered HTML
checks = [
    "TECNOPREMIUM · WELLNESS",
    "El Arte de Vivir el Exterior sin Límites",
    "Colección Spas & Wellness",
    "Gama Profesional · Desde 12.600 €",
    "Ingeniería Wellness de Vanguardia",
    "Arquitectura Bioclimática Inteligente",
    "Materiales Nobles & Durabilidad Extrema",
    "Atención y Tarifas Exclusivas B2B",
    "FILOSOFÍA TECNOPREMIUM",
    "Redefiniendo el Lujo en Espacios Exteriores",
    "Tarifa Interiorista",
    "Tarifa Arquitecto",
    "Distribuidor VIP",
    "\\u00"
]

print("\n--- HTML CONTENT CHECKS ---")
for c in checks:
    found = c in html
    print(f"Contains '{c}': {found}")

os.remove(r"C:\xampp\htdocs\Khumbu\teknopremium\render_home.php")
