#!/usr/bin/env python3
"""
KHUMBU FIX — Convierte JSONs Elementor eliminando e-gv- variables
y usando colores hexadecimales directos del Design System.

USO:
  python3 fix-khumbu-variables.py /var/www/html/Khumbu/khumbunew

Esto:
1. Lee los 9 JSON de khumbu_assets/
2. Reemplaza cada e-gv-XXXXX con su valor hex real
3. Guarda los JSON corregidos en la misma carpeta (con backup)
4. Listo para usar sin necesidad de importar el kit
"""

import json
import os
import sys
import re
from pathlib import Path
from datetime import datetime

# ════════════════════════════════════════════════════════════════════
# MAPEO DE VARIABLES → COLORES HEXADECIMALES (del Design System Khumbu)
# ════════════════════════════════════════════════════════════════════

VARIABLE_MAP = {
    # Colores del sistema (extraídos del khumbu-00-wow-html.html)
    # e-gv-04cb4c1: color PRINCIPAL muy usado (bone/white dominante)
    "04cb4c1": "#fcfcfd",  # bone - blanco puro
    
    # e-gv-546eddc: color SECUNDARIO muy usado (void/charcoal)
    "546eddc": "#0a0916",  # void - fondo oscuro principal
    
    # Otros colores del sistema
    "1f4162d": "#cfcdd8",  # ash - gris claro
    "3c50991": "#2a2842",  # graphite - gris oscuro
    "46ead6b": "#e46a25",  # accent - naranja acción
    "68c8481": "#c85a1c",  # accent-deep - naranja oscuro
    "6697b66": "#131124",  # charcoal - carbón
    "a4db658": "#8f8da0",  # pebble - piedra gris
    "f836207": "#4b4964",  # iron - hierro
    
    # Fuentes (estos sí se dejan como están, no se pueden reemplazar fácilmente)
    # "4599cec": "Poppins",   # display font
    # "5a400a0": "Montserrat", # body font
}

# ════════════════════════════════════════════════════════════════════

def normalize_var_id(var_id):
    """Extrae el ID de una variable e-gv-XXXXX."""
    match = re.search(r'e-gv-([a-f0-9]+)', var_id)
    return match.group(1) if match else var_id

def replace_variables_in_obj(obj, replacements):
    """Reemplaza recursivamente e-gv-XXXXX con valores hex en un objeto."""
    if isinstance(obj, dict):
        for key, val in obj.items():
            if isinstance(val, str):
                # Reemplazar e-gv-XXXXX por hex
                for var_id, hex_color in replacements.items():
                    pattern = f"e-gv-{var_id}"
                    if pattern in val:
                        obj[key] = val.replace(pattern, hex_color)
                        print(f"    ✓ {key}: {pattern} → {hex_color}")
            else:
                replace_variables_in_obj(val, replacements)
    elif isinstance(obj, list):
        for item in obj:
            replace_variables_in_obj(item, replacements)

def fix_json_file(filepath, output_backup=True):
    """Corrige un archivo JSON Khumbu."""
    if not os.path.exists(filepath):
        print(f"  ❌ No existe: {filepath}")
        return False
    
    # Backup
    if output_backup:
        backup_path = filepath + ".bak"
        if not os.path.exists(backup_path):
            import shutil
            shutil.copy2(filepath, backup_path)
            print(f"  📦 Backup: {os.path.basename(backup_path)}")
    
    # Leer JSON
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(f"  ❌ Error leyendo JSON: {e}")
        return False
    
    # Contar variables antes
    with open(filepath, 'r') as f:
        raw = f.read()
    var_count = len(re.findall(r'e-gv-[a-f0-9]+', raw))
    
    # Reemplazar
    replace_variables_in_obj(data, VARIABLE_MAP)
    
    # Guardar
    try:
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=1)
        print(f"  ✅ Guardado (contenía {var_count} variables)")
        return True
    except Exception as e:
        print(f"  ❌ Error guardando: {e}")
        return False

def main():
    if len(sys.argv) < 2:
        print("USO: python3 fix-khumbu-variables.py /ruta/a/khumbunew")
        print("\nEjemplo:")
        print("  python3 fix-khumbu-variables.py /var/www/html/Khumbu/khumbunew")
        sys.exit(1)
    
    wp_root = sys.argv[1].rstrip('/')
    khumbu_assets = os.path.join(wp_root, 'wp-content/uploads/khumbu_assets')
    
    print("=" * 70)
    print("  KHUMBU FIX — Eliminar variables globales e inyectar colores")
    print("=" * 70)
    print(f"\n📂 Ruta: {khumbu_assets}\n")
    
    if not os.path.isdir(khumbu_assets):
        print(f"❌ No existe: {khumbu_assets}")
        sys.exit(1)
    
    # Buscar JSON
    json_files = sorted([f for f in os.listdir(khumbu_assets) if f.endswith('.json')])
    
    if not json_files:
        print("❌ No hay archivos .json en khumbu_assets/")
        sys.exit(1)
    
    print(f"Encontrados: {len(json_files)} archivos JSON\n")
    
    success = 0
    for fname in json_files:
        print(f"📄 {fname}")
        fpath = os.path.join(khumbu_assets, fname)
        if fix_json_file(fpath):
            success += 1
        print()
    
    print("=" * 70)
    print(f"  RESUMEN: {success}/{len(json_files)} corregidos")
    print("=" * 70)
    print(f"\n✅ Los JSONs ahora usan colores hexadecimales directos")
    print(f"   No dependen del Kit de Elementor vacío\n")
    print(f"📋 MAPEO APLICADO:")
    print(f"   • e-gv-04cb4c1 → #fcfcfd (bone/blanco)")
    print(f"   • e-gv-546eddc → #0a0916 (void/negro)")
    print(f"   • e-gv-46ead6b → #e46a25 (naranja acción) ⭐")
    print(f"   • e-gv-68c8481 → #c85a1c (naranja oscuro)")
    print(f"   • + 5 colores más\n")
    print(f"Backups guardados como *.bak en la misma carpeta\n")

if __name__ == "__main__":
    main()
