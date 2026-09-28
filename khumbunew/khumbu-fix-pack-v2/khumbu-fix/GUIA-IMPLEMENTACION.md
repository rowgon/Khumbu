# Guía de implementación — Khumbu Rediseño (khumbunew)

## Diagnóstico realizado

Analicé los 9 JSON + el HTML del bloque WOW. Estos son los problemas detectados y cómo resolverlos:

### Problema 1: Imágenes no cargan (CRÍTICO)
**Causa:** Los JSON referencian `attachment ID 7266` (del khumbu.pro original) con `"url": null`. En la instalación nueva ese ID no existe → imágenes invisibles.

**Archivos afectados:**
- `khumbu-01-header.json` → logo (1 imagen)
- `khumbu-02-hero-apertura.json` → imagen de fondo (1 imagen)
- `khumbu-07-blog-podcast.json` → tarjetas blog/podcast (2 imágenes)
- `khumbu-09-footer.json` → logo (1 imagen)

**Solución:** Ejecutar `fix-khumbu-json.py` después de subir los medios (ver Paso 1).

### Problema 2: Design System (POTENCIALMENTE CRÍTICO)
**Causa:** Los JSON usan 11 variables globales con IDs específicos (`e-gv-04cb4c1`, etc.). Si el Design System no se importó, o si se reimportó (los IDs se regeneran), los colores y fuentes aparecerán por defecto (gris, Arial).

**Solución:** Ejecutar `diagnostico-khumbu.php` para verificar que los IDs coinciden.

### Problema 3: Video del bloque WOW
**Causa:** Las 2 URLs del vídeo apuntan a `khumbu.pro/wp-content/uploads/khumbu/hero-720.mp4` — puede no existir en la nueva instalación.

**Solución:** Sustituir URLs tras subir el vídeo (el script lo hace automáticamente).

### Problema 4: Header/Footer no aparecen
**Causa:** Falta configurar Theme Builder de Elementor Pro.

---

## Paso 0 · Diagnóstico del WordPress (5 min)

```bash
cd /var/www/html/Khumbu/khumbunew
php diagnostico-khumbu.php
```

Esto verifica:
- Elementor + Pro activos
- Design System (variables globales con IDs correctos)
- Medios subidos
- Plantillas importadas
- Theme Builder configurado
- Soporte SVG

**Si el diagnóstico reporta que faltan variables del Design System** → PARA. Necesitas importar el `design-system-export.zip` desde Elementor > Sistema de diseño. Si ya lo importaste pero los IDs no coinciden, avísame con la salida del diagnóstico y remap los JSON.

---

## Paso 1 · Subir medios a WordPress (10 min)

Subir a Medios → Biblioteca:

1. **`logo-blanco.svg`** (o `.png` si SVG no se acepta)  
   → Si SVG falla: Elementor → Ajustes → Avanzado → Activar "Subida de archivos sin filtrar"
2. **`hero-720.mp4`** — el vídeo de apertura
3. **`img/metal.jpg`** — para la tarjeta de blog
4. **`img/factory.jpg`** — para la tarjeta de podcast

**Tras subir cada archivo, copia su URL** (clic en el archivo → campo URL a la derecha).

---

## Paso 2 · Corregir los JSON (5 min)

1. Copiar todos los archivos del zip + los scripts de corrección a una carpeta:
   ```
   khumbu-work/
   ├── khumbu-00-wow-html.html
   ├── khumbu-01-header.json ... khumbu-09-footer.json
   ├── fix-khumbu-json.py
   └── fixed/   (se crea automáticamente)
   ```

2. Editar `fix-khumbu-json.py` — pegar las URLs reales:
   ```python
   MEDIA_URLS = {
       "logo": "https://khumbu.pro/wp-content/uploads/2026/09/logo-blanco.svg",
       "metal": "https://khumbu.pro/wp-content/uploads/2026/09/metal.jpg",
       "factory": "https://khumbu.pro/wp-content/uploads/2026/09/factory.jpg",
       "hero_video": "https://khumbu.pro/wp-content/uploads/2026/09/hero-720.mp4",
   }
   ```

3. Ejecutar:
   ```bash
   python3 fix-khumbu-json.py
   ```

4. Los archivos corregidos estarán en `./fixed/`

---

## Paso 3 · Importar plantillas (10 min)

En WordPress admin → **Plantillas → Plantillas guardadas → Importar plantillas**

**Prueba primero** con `khumbu-04-conversaciones.json` (de la carpeta `fixed/`):
- Importar → insertar en una página de prueba
- ¿Se ve fondo oscuro, títulos Poppins blancos, tarjetas con borde?
- ¿Los colores son variables (`accent`, `bone`...) en el panel, no hex sueltos?

Si funciona, importar los 9 JSON restantes de `fixed/`.

---

## Paso 4 · Montar la Home (20 min)

1. Crear página **"Inicio"** → Editar con Elementor
2. Ajustes de página → Diseño: **Elementor Canvas**
3. Primer elemento: widget **HTML** → pegar el contenido completo de `fixed/khumbu-00-wow-html.html`
4. Debajo, insertar desde la biblioteca en orden:
   - `khumbu · 04 conversaciones`
   - `khumbu · 05 soluciones`
   - `khumbu · 06 casos`
   - `khumbu · 07 blog + podcast`
   - `khumbu · 08 cta final`
5. En bloque 07: verificar que las imágenes de `metal.jpg` y `factory.jpg` aparecen
6. Guardar como borrador y previsualizar

---

## Paso 5 · Header y Footer con Theme Builder (20 min)

1. **Plantillas → Theme Builder → Header** → crear nuevo → insertar plantilla `khumbu · header` → Publicar con condición **Todo el sitio**
2. **Theme Builder → Footer** → crear nuevo → insertar `khumbu · footer` → Publicar con condición **Todo el sitio**
3. En ambos: verificar que el logo aparece (debería estar ya con la URL correcta del JSON corregido)

**Importante para la Home (Canvas):** Canvas oculta header/footer del Theme Builder. Para que la home tenga navegación:
- Insertar manualmente la plantilla `khumbu · header` como primer elemento (encima del widget HTML)
- Insertar `khumbu · footer` como último elemento (debajo de 08 cta final)

---

## Paso 6 · Verificación

- [ ] Vídeo de apertura se reproduce (silencio, en bucle)
- [ ] Secuencia del manifiesto: knockout → naranja → zoom → credo → columna
- [ ] Botones naranja `#E46A25` con hover más oscuro
- [ ] Textos en Poppins/Montserrat (no Arial/serif por defecto)
- [ ] Vista tablet y móvil: tarjetas en columna, sin scroll horizontal
- [ ] Logo visible en header y footer
- [ ] Imágenes de blog/podcast visibles en bloque 07

---

## Si algo falla

| Síntoma | Causa probable | Solución |
|---------|---------------|----------|
| Colores incorrectos (gris, azul default) | Design System no importado o IDs no coinciden | Ejecutar `diagnostico-khumbu.php`, verificar IDs |
| Imágenes vacías/placeholder gris | URL null en el JSON | Re-ejecutar `fix-khumbu-json.py` con URLs correctas |
| Fuentes Arial/sans-serif genéricas | Variables de fuente no coinciden | Verificar `e-gv-4599cec` y `e-gv-5a400a0` en el kit |
| Header/Footer no aparecen | Theme Builder no configurado | Paso 5 |
| Error al importar JSON | Versión de Elementor incompatible | No editar a mano, enviar el error exacto |
| SVG rechazado | Subida sin filtrar desactivada | Elementor → Ajustes → Avanzado → Activar |
| Vídeo no reproduce | URL incorrecta en el HTML | Verificar URL del vídeo en biblioteca de medios |
