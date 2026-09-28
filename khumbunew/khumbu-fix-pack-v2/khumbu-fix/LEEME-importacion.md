# Bloques Elementor 4.2.4 — khumbu.pro (Pro instalado)
Generados contra el contrato + verificados contra la semilla real (post 7285):
envoltura `type: "flexbox"`, `isInner` en todos los elementos, `interactions` sin `version`, `link` sin `tag`.

## Orden de importación
1. (Ya hecho) Design System: variables e-gv-* + clase g-440fd6d.
2. Plantillas → Plantillas guardadas → Importar → cada `khumbu-*.json`.
3. Montaje home: WOW (HTML) → 04 → 05 → 06 → 07 → 08.

## khumbu-00-wow-html.html — EL EFECTO WOW
NO es un JSON: es el bloque HTML autocontenido (apertura con vídeo + manifiesto scroll completo:
knockout SOMOS KHUMBU, naranja, zoom por la U, credo, palabras que vuelan a columna).
Uso:
1. Subir `assets/hero-720.mp4` a la biblioteca de medios.
2. Copiar su URL y sustituir en el archivo las 2 URLs `https://khumbu.pro/wp-content/uploads/khumbu/hero-720.mp4`.
3. En la página de la home: plantilla **Elementor Canvas** (o Full Width), primer elemento = widget **HTML**, pegar el archivo entero.
4. El bloque es autocontenido (fuentes, estilos scoped bajo `.kwow`, JS propio) — no pisa nada del resto de la página.
Sustituye a los antiguos bloques 02 y 03 (hero estático + somos estático), que quedan como alternativa sin JS.

## Con Elementor Pro (nuevo)
- **Header (01) y Footer (09)**: montarlos en Theme Builder → crear parte Header/Footer e insertar dentro la plantilla importada (o reconstruir con ella a la vista). Asignar condición "Todo el sitio".
- **Formulario de contacto**: el widget e-form atómico ya está desbloqueado, pero su esquema NO está en el contrato (estaba capado al extraerlo). Antes de que genere el bloque de contacto: monta un e-form mínimo (Nombre/Email/Mensaje) en la página de pruebas, expórtalo y pásamelo — 1 minuto, y con eso genero contacto completo.
- Custom CSS por widget ahora disponible: lo usaremos solo si un bloque lo exige.

## Medios
Imágenes en placeholder attachment **7266**. Reemplazar tras importar:
- 01/09 → logo-blanco.svg · 07 → fotos de blog/podcast.
Todo está en `khumbu-web-v6/assets/`.

## Pedir más bloques
El Sistema, Departamento, industrias y resto de páginas: se generan por tandas contra este mismo contrato. Pide por sección.
