#!/bin/bash
# KHUMBU FIX SHELL — Ejecutar directamente en terminal

WP_ROOT="${1:-.}"
ASSETS_DIR="$WP_ROOT/wp-content/uploads/khumbu_assets"

echo "=================================================="
echo "  KHUMBU FIX — Convertir e-gv- a colores hex"
echo "=================================================="
echo ""

if [ ! -d "$ASSETS_DIR" ]; then
    echo "❌ No existe: $ASSETS_DIR"
    exit 1
fi

# Mapeo de variables → hex
declare -A COLORS=(
    ["04cb4c1"]="#fcfcfd"      # bone/blanco
    ["546eddc"]="#0a0916"      # void/negro
    ["1f4162d"]="#cfcdd8"      # ash
    ["3c50991"]="#2a2842"      # graphite
    ["46ead6b"]="#e46a25"      # accent naranja ⭐
    ["68c8481"]="#c85a1c"      # accent-deep
    ["6697b66"]="#131124"      # charcoal
    ["a4db658"]="#8f8da0"      # pebble
    ["f836207"]="#4b4964"      # iron
)

cd "$ASSETS_DIR"
echo "📂 Archivos en: $ASSETS_DIR"
echo ""

for json_file in khumbu-*.json; do
    if [ ! -f "$json_file" ]; then
        continue
    fi
    
    echo "📄 $json_file"
    
    # Backup
    if [ ! -f "$json_file.bak" ]; then
        cp "$json_file" "$json_file.bak"
        echo "   ✓ Backup creado"
    fi
    
    # Reemplazar cada variable
    for var_id in "${!COLORS[@]}"; do
        hex="${COLORS[$var_id]}"
        old_pattern="e-gv-$var_id"
        
        # Contar occurrencias
        count=$(grep -o "$old_pattern" "$json_file" | wc -l)
        if [ $count -gt 0 ]; then
            # Reemplazar (compatible con sed en Linux y macOS)
            if [[ "$OSTYPE" == "darwin"* ]]; then
                sed -i "" "s/$old_pattern/$hex/g" "$json_file"
            else
                sed -i "s/$old_pattern/$hex/g" "$json_file"
            fi
            echo "   ✓ $old_pattern → $hex ($count refs)"
        fi
    done
    echo ""
done

echo "=================================================="
echo "  ✅ LISTO"
echo "=================================================="
echo ""
echo "Los JSONs ahora usan colores hexadecimales directos"
echo "Visita: http://localhost/Khumbu/khumbunew"
echo "Los botones deberían estar NARANJA #e46a25 ahora"
echo ""
