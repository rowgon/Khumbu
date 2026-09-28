# Khumbu - Proyectos de WordPress

Este repositorio contiene el código fuente de los proyectos de WordPress (plantillas, plugins y configuraciones) alojados bajo la carpeta `Khumbu`.

## ⚠️ AVISO IMPORTANTE SOBRE LA RESTAURACIÓN ⚠️

Este repositorio **NO contiene las imágenes, videos ni archivos multimedia** de los sitios (la carpeta `wp-content/uploads/` está ignorada por límites de espacio de GitHub). 

Para restaurar cualquiera de estos proyectos en un nuevo servidor, sigue estos pasos:

### 1. Clonar el repositorio
```bash
git clone https://github.com/rowgon/Khumbu.git
cd Khumbu
```

### 2. Restaurar la Base de Datos
Cada subproyecto (ej. `khumbunew`, `teknopremium`, `bigsizemedia`) incluye su propio archivo de base de datos comprimido llamado `database_backup.sql.gz`.
Para importarlo en tu nuevo servidor de base de datos (por ejemplo, usando MySQL o WP-CLI):
```bash
# Descomprimir
gunzip database_backup.sql.gz
# Importar (ajusta el nombre de usuario y base de datos)
mysql -u TU_USUARIO -p TU_BASE_DE_DATOS < database_backup.sql
```

### 3. Restaurar las Imágenes (`uploads/`)
Debes copiar manualmente la carpeta `uploads/` que hayas respaldado en un disco externo o Google Drive, y pegarla dentro de la ruta:
`[nombre-del-proyecto]/wp-content/uploads/`

### 4. Configurar el acceso a la Base de Datos
Edita el archivo `wp-config.php` de cada proyecto para asegurarte de que las credenciales coincidan con las de tu nuevo servidor local o de producción.
