#!/usr/bin/env python3
"""
FIX KHUMBU JSON — Corrige los JSONs de Elementor para la nueva instalación.

USO:
  1. Subir los medios a WordPress y anotar las URLs
  2. Editar las variables MEDIA_URLS abajo con las URLs reales
  3. Ejecutar: python3 fix-khumbu-json.py
  4. Los archivos corregidos se generan en ./fixed/

OPCIONALMENTE: Si los IDs de las variables del Design System cambiaron,
editar también la sección VARIABLE_MAP.
"""

import json
import os
import re
import sys
import copy

# ═══════════════════════════════════════════════════════════════════
# CONFIGURACIÓN — EDITAR ESTAS URLS CON LAS REALES DE TU WP
# ═══════════════════════════════════════════════════════════════════

# URL base de tu WordPress (sin / final)
WP_URL = "https://khumbu.pro"  # <-- Cambiar si es diferente en khumbunew

# URLs de los medios ya subidos a la biblioteca de WP
# Sustituir cada placeholder con la URL real después de subir
MEDIA_URLS = {
    "logo": "",          # ← URL de logo-blanco.svg (o .png) en la biblioteca
    "metal": "",         # ← URL de metal.jpg para tarjeta blog (bloque 07)
    "factory": "",       # ← URL de factory.jpg para tarjeta podcast (bloque 07)
    "hero_video": "",    # ← URL de hero-720.mp4 (para el HTML wow)
}

# IDs de attachment en la nueva instalación (los que WP asignó al subir)
# Ponerlos a 0 si no se saben — las imágenes funcionarán igualmente por URL
MEDIA_IDS = {
    "logo": 0,
    "metal": 0,
    "factory": 0,
}

# ═══════════════════════════════════════════════════════════════════
# MAPEO DE VARIABLES (solo si los IDs cambiaron al reimportar el DS)
# Formato: "id-viejo": "id-nuevo"
# Dejar vacío si el Design System se importó correctamente y los IDs coinciden
# ═══════════════════════════════════════════════════════════════════

VARIABLE_MAP = {
    # Ejemplo: "04cb4c1": "nuevo_id_aqui",
    # Colores:
    # "04cb4c1": "",  # color muy usado (¿bone/white?)
    # "546eddc": "",  # color muy usado (¿void?)
    # "1f4162d": "",
    # "3c50991": "",
    # "46ead6b": "",
    # "68c8481": "",
    # "6697b66": "",
    # "a4db658": "",
    # "f836207": "",
    # Fuentes:
    # "4599cec": "",
    # "5a400a0": "",
}

# ═══════════════════════════════════════════════════════════════════

INPUT_DIR = "."
OUTPUT_DIR = "./fixed"

# Archivos a procesar y qué imagen usa cada uno
FILE_IMAGE_MAP = {
    "khumbu-01-header.json": "logo",
    "khumbu-02-hero-apertura.json": "logo",  # background placeholder
    "khumbu-07-blog-podcast.json": ["metal", "factory"],  # 2 imágenes
    "khumbu-09-footer.json": "logo",
}

def fix_image_refs(data, url, attachment_id=0, placeholder_id=7266):
    """Reemplaza recursivamente refs al attachment placeholder con URL real."""
    if isinstance(data, dict):
        # Detectar el patrón V4 atómico de imagen
        if data.get("$$type") == "image-attachment-id" and data.get("value") == placeholder_id:
            if attachment_id > 0:
                data["value"] = attachment_id
            # El ID solo, sin URL, no renderiza — necesitamos también arreglar la URL
        
        if "url" in data and data["url"] is None:
            # Buscar si es hermano de un image-attachment-id
            if "id" in data and isinstance(data["id"], dict):
                if data["id"].get("$$type") == "image-attachment-id":
                    if data["id"].get("value") == placeholder_id or (attachment_id > 0 and data["id"].get("value") == attachment_id):
                        data["url"] = url
                        if attachment_id > 0:
                            data["id"]["value"] = attachment_id
        
        for k, v in data.items():
            fix_image_refs(v, url, attachment_id, placeholder_id)
    
    elif isinstance(data, list):
        for item in data:
            fix_image_refs(item, url, attachment_id, placeholder_id)


def fix_multiple_images(data, urls_list, ids_list, placeholder_id=7266):
    """Para archivos con múltiples imágenes (blog-podcast tiene 2)."""
    occurrences = []
    
    def find_null_urls(obj, path=[]):
        if isinstance(obj, dict):
            if obj.get("url") is None and "id" in obj:
                if isinstance(obj["id"], dict) and obj["id"].get("value") == placeholder_id:
                    occurrences.append(obj)
            for k, v in obj.items():
                find_null_urls(v, path + [k])
        elif isinstance(obj, list):
            for i, v in enumerate(obj):
                find_null_urls(v, path + [i])
    
    find_null_urls(data)
    
    for i, occ in enumerate(occurrences):
        if i < len(urls_list) and urls_list[i]:
            occ["url"] = urls_list[i]
            if i < len(ids_list) and ids_list[i] > 0:
                occ["id"]["value"] = ids_list[i]


def remap_variables(data, var_map):
    """Reemplaza IDs de variables globales si cambiaron."""
    if not var_map:
        return
    
    if isinstance(data, str):
        return data
    
    if isinstance(data, dict):
        for k, v in list(data.items()):
            if isinstance(v, str):
                for old_id, new_id in var_map.items():
                    if new_id and f"e-gv-{old_id}" in v:
                        data[k] = v.replace(f"e-gv-{old_id}", f"e-gv-{new_id}")
            else:
                remap_variables(v, var_map)
    elif isinstance(data, list):
        for item in data:
            remap_variables(item, var_map)


def fix_wow_html(input_path, output_path, video_url):
    """Corrige las URLs del vídeo en el HTML del bloque WOW."""
    with open(input_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old_url = "https://khumbu.pro/wp-content/uploads/khumbu/hero-720.mp4"
    if video_url:
        content = content.replace(old_url, video_url)
        print(f"  ✅ Sustituidas 2 URLs de vídeo → {video_url}")
    else:
        print(f"  ⚠️  URL de vídeo vacía — mantiene placeholder {old_url}")
    
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(content)


def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    print("=" * 60)
    print("  FIX KHUMBU JSON")
    print("=" * 60)
    
    # Validar que hay URLs configuradas
    empty_urls = [k for k, v in MEDIA_URLS.items() if not v]
    if empty_urls:
        print(f"\n⚠️  URLs vacías: {', '.join(empty_urls)}")
        print("   Edita MEDIA_URLS en este script con las URLs reales.")
        print("   Continuando de todos modos (los campos vacíos quedarán sin cambio)...\n")
    
    # Procesar cada JSON
    for fname in sorted(os.listdir(INPUT_DIR)):
        if not fname.startswith('khumbu-') or not fname.endswith('.json'):
            continue
        
        input_path = os.path.join(INPUT_DIR, fname)
        output_path = os.path.join(OUTPUT_DIR, fname)
        
        with open(input_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        print(f"\n📄 {fname}")
        
        # Fix images
        if fname in FILE_IMAGE_MAP:
            img_key = FILE_IMAGE_MAP[fname]
            
            if isinstance(img_key, list):
                # Multiple images (blog-podcast)
                urls = [MEDIA_URLS.get(k, "") for k in img_key]
                ids = [MEDIA_IDS.get(k, 0) for k in img_key]
                fix_multiple_images(data, urls, ids)
                for k, u in zip(img_key, urls):
                    if u:
                        print(f"  ✅ Imagen {k} → {u}")
                    else:
                        print(f"  ⚠️  Imagen {k} sin URL")
            else:
                url = MEDIA_URLS.get(img_key, "")
                mid = MEDIA_IDS.get(img_key, 0)
                if url:
                    fix_image_refs(data, url, mid)
                    print(f"  ✅ Imagen {img_key} → {url}")
                else:
                    print(f"  ⚠️  Imagen {img_key} sin URL (placeholder 7266 se mantiene)")
        else:
            print(f"  ✓ Sin imágenes placeholder")
        
        # Remap variables if needed
        active_map = {k: v for k, v in VARIABLE_MAP.items() if v}
        if active_map:
            remap_variables(data, active_map)
            print(f"  ✅ {len(active_map)} variable(s) remapeadas")
        
        # Save
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=1)
        
        print(f"  → Guardado en {output_path}")
    
    # Fix WOW HTML
    wow_input = os.path.join(INPUT_DIR, "khumbu-00-wow-html.html")
    wow_output = os.path.join(OUTPUT_DIR, "khumbu-00-wow-html.html")
    if os.path.exists(wow_input):
        print(f"\n📄 khumbu-00-wow-html.html")
        fix_wow_html(wow_input, wow_output, MEDIA_URLS.get("hero_video", ""))
        print(f"  → Guardado en {wow_output}")
    
    # Copy LEEME
    leeme = os.path.join(INPUT_DIR, "LEEME-importacion.md")
    if os.path.exists(leeme):
        import shutil
        shutil.copy2(leeme, os.path.join(OUTPUT_DIR, "LEEME-importacion.md"))
    
    print(f"\n{'='*60}")
    print(f"  LISTO — archivos corregidos en {OUTPUT_DIR}/")
    print(f"{'='*60}")
    print(f"\nSiguiente paso: importar los JSON de {OUTPUT_DIR}/ en Elementor")
    print(f"(Plantillas → Plantillas guardadas → Importar plantillas)")
    print()


if __name__ == "__main__":
    main()
