# 🔧 KHUMBU FIX — Solución para Kit Elementor Vacío

## 📊 DIAGNÓSTICO

Tu kit de Elementor (`kit ID: 5`) está **vacío** (sin variables globales).

**Por eso:**
- ✅ Las plantillas JSON se cargan
- ❌ Pero sin colores (botones azul/violeta en lugar de naranja)
- ❌ Sin fuentes globales (default Elementor en lugar de Poppins/Montserrat)

**Solución:** Convertir todos los JSON para usar **colores hexadecimales directos** en lugar de referencias a variables `e-gv-XXXXX`.

---

## 🚀 SOLUCIÓN EN 2 PASOS

### **PASO 1: Ejecutar el script de corrección**

Elige UNO de estos métodos:

#### **Opción A: Bash (Recomendado — más rápido)**

```bash
# Desde cualquier parte:
cd /var/www/html/Khumbu/khumbunew
bash fix-khumbu.sh .

# O directamente:
bash fix-khumbu.sh /var/www/html/Khumbu/khumbunew
```

**Output esperado:**
```
==================================================
  KHUMBU FIX — Convertir e-gv- a colores hex
==================================================

📂 Archivos en: /var/www/html/Khumbu/khumbunew/wp-content/uploads/khumbu_assets

📄 khumbu-01-header.json
   ✓ Backup creado
   ✓ e-gv-04cb4c1 → #fcfcfd (1 refs)
   ✓ e-gv-546eddc → #0a0916 (2 refs)
   ✓ e-gv-46ead6b → #e46a25 (1 refs) ⭐ NARANJA
   ...

✅ LISTO
```

---

#### **Opción B: Python (Si bash no funciona)**

```bash
cd /var/www/html/Khumbu/khumbunew
python3 fix-khumbu-variables.py .
```

---

#### **Opción C: Manualmente en Claude Code**

Pega esto en Claude Code:

```bash
# Copia y pega esto en Claude Code terminal:
cd /var/www/html/Khumbu/khumbunew/wp-content/uploads/khumbu_assets

for json in khumbu-*.json; do
  cp "$json" "$json.bak"
  sed -i 's/e-gv-04cb4c1/#fcfcfd/g' "$json"
  sed -i 's/e-gv-546eddc/#0a0916/g' "$json"
  sed -i 's/e-gv-1f4162d/#cfcdd8/g' "$json"
  sed -i 's/e-gv-3c50991/#2a2842/g' "$json"
  sed -i 's/e-gv-46ead6b/#e46a25/g' "$json"
  sed -i 's/e-gv-68c8481/#c85a1c/g' "$json"
  sed -i 's/e-gv-6697b66/#131124/g' "$json"
  sed -i 's/e-gv-a4db658/#8f8da0/g' "$json"
  sed -i 's/e-gv-f836207/#4b4964/g' "$json"
  echo "✓ $json"
done

echo "✅ LISTO"
```

---

### **PASO 2: Verificar cambios en el navegador**

1. Abre http://localhost/Khumbu/khumbunew en tu navegador
2. Haz **refresh** (Ctrl+F5 o Cmd+Shift+R) para limpiar caché
3. **Comprueba:**
   - [ ] Botones ahora son **NARANJA** `#e46a25` 
   - [ ] Header/Footer con colores correctos
   - [ ] Tarjetas y secciones con colores del Design System

---

## 📋 MAPEO DE COLORES APLICADO

```
e-gv-04cb4c1 → #fcfcfd  (bone - blanco puro)
e-gv-546eddc → #0a0916  (void - fondo negro)
e-gv-1f4162d → #cfcdd8  (ash - gris claro)
e-gv-3c50991 → #2a2842  (graphite - gris oscuro)
e-gv-46ead6b → #e46a25  (accent - NARANJA ACCIÓN) ⭐
e-gv-68c8481 → #c85a1c  (accent-deep - naranja oscuro)
e-gv-6697b66 → #131124  (charcoal - carbón)
e-gv-a4db658 → #8f8da0  (pebble - piedra)
e-gv-f836207 → #4b4964  (iron - hierro)
```

---

## ✅ VERIFICACIÓN

### Si todo funciona:
- ✅ Botones naranja
- ✅ Headings en color correcto
- ✅ Fondos oscuros/claros aplicados
- ✅ Tarjetas con estilos

### Si NO funciona:
- Limpia caché del navegador (Ctrl+Shift+Del)
- Limpia caché de WordPress: wp cache flush
- Refresca CDN si hay uno

---

## 📂 BACKUPS

Si algo sale mal, se crean backups automáticamente:
```
/wp-content/uploads/khumbu_assets/
├── khumbu-01-header.json      (archivo corregido)
├── khumbu-01-header.json.bak  (backup original)
├── khumbu-02-hero-apertura.json
├── khumbu-02-hero-apertura.json.bak
└── ... (9 pares de archivo + backup)
```

Para restaurar:
```bash
cd /var/www/html/Khumbu/khumbunew/wp-content/uploads/khumbu_assets
for f in *.bak; do cp "$f" "${f%.bak}"; done
```

---

## 🎯 PRÓXIMOS PASOS (Opcional)

Una vez funcione, puedes:

1. **Subir imágenes reales**
   - Logo blanco → header/footer
   - metal.jpg, factory.jpg → blog/podcast
   - hero-720.mp4 → vídeo apertura

2. **Configurar Theme Builder**
   - Header/Footer en Elementor Pro

3. **Personalizar fuentes**
   - Si quieres Poppins/Montserrat en todo, usar Google Fonts en el tema

---

## 💬 ¿PREGUNTAS?

- ¿El script no corre? → Verifica permisos: `ls -l khumbu_assets/`
- ¿Los colores siguen mal? → Comprueba que la edición fue exitosa: `grep e-gv khumbu-01-header.json | head -5`
- ¿Caché de Elementor? → Borrar `/wp-content/cache/elementor/` si existe

---

**🚀 ¡Ahora sí, ejecuta el script y los botones deberían estar NARANJA!**
