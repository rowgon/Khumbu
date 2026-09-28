# PROMPT PARA CLAUDE CODE — Khumbu Diagnosis & Fix

Copia TODO ESTO y pégalo en una nueva sesión de Claude Code:

---

Tengo un proyecto WordPress con Elementor en `/var/www/html/Khumbu/khumbunew/` que necesita diagnóstico y corrección de plantillas.

**TAREA 1: Diagnosis del Design System**
1. Ejecuta este comando PHP desde la raíz del WordPress:
```bash
cd /var/www/html/Khumbu/khumbunew
php -r "
require 'wp-load.php';
\$kit_id = get_option('elementor_active_kit');
\$kit_meta = get_post_meta(\$kit_id, '_elementor_page_settings', true);

echo \"=== DESIGN SYSTEM ===\n\";
echo \"Kit ID: \$kit_id\n\n\";

echo \"COLORES:\n\";
\$colors = array_merge(
  \$kit_meta['system_colors'] ?? [],
  \$kit_meta['custom_colors'] ?? []
);
foreach (\$colors as \$c) {
  echo '  e-gv-' . (\$c['_id'] ?? '?') . ' → ' . (\$c['title'] ?? '?') . ' = ' . (\$c['color'] ?? '?') . \"\n\";
}

echo \"\nFUENTES:\n\";
\$fonts = array_merge(
  \$kit_meta['system_typography'] ?? [],
  \$kit_meta['custom_typography'] ?? []
);
foreach (\$fonts as \$f) {
  echo '  e-gv-' . (\$f['_id'] ?? '?') . ' → ' . (\$f['title'] ?? '?') . \" = \" . (\$f['typography_font_family'] ?? '?') . \"\n\";
}

echo \"\nATTACHMENT 7266:\n\";
\$att = get_post(7266);
echo \$att ? 'EXISTS' : 'NOT FOUND';
echo \"\n\";
"
```

2. Copia TODO el output y muéstramelo.

**TAREA 2: Listar archivos en la carpeta**
```bash
ls -la /var/www/html/Khumbu/khumbunew/ | head -30
```

**TAREA 3: Revisar los JSON de Elementor**
```bash
cd /var/www/html/Khumbu/khumbunew
find . -name "*.json" -path "*/elementor*" -o -name "*khumbu*.json" 2>/dev/null | head -20
```

Una vez me des estos outputs, usaré Python para:
- Analizar el Design System actual
- Si los IDs no coinciden, remapipar todos los JSON automáticamente
- Generar un script corregido
- Darte instrucciones de qué medios subir y dónde

---

**O si prefieres acceso directo:**

Dame acceso a esa carpeta por UNO de estos métodos:

1. **SSH** — dame el host, puerto, usuario y contraseña (o puedo usar `localhost` si está en tu máquina)
2. **SFTP** — datos de conexión
3. **Descarga del zip** — comprime `/var/www/html/Khumbu/khumbunew/` y sube aquí

Así entraré directamente y haré todo sin necesidad de PHP.

---

**Recomendación:** Usa **TAREA 1 + 2 + 3** arriba. Es rápido (2 minutos) y me da todo lo que necesito.
