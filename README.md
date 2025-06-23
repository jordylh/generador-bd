<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

# Generador de Base de Datos (Laravel + Excel)

**Generador-BD** es un proyecto Laravel que permite:
- Subir un archivo Excel (`.xlsx` o `.xls`).
- Analizar cada hoja del archivo como una tabla de base de datos.
- Inferir columnas, tipos de datos, claves primarias y foráneas.
- Generar un script SQL para crear la base de datos en MySQL.
- Mostrar una vista previa de los datos antes de crear la base.
- Copiar fácilmente el SQL generado al portapapeles.

---

## 🚀 **Características actuales**

✅ Subida de archivos Excel.  
✅ Análisis de múltiples hojas (cada hoja = tabla).  
✅ Detección automática de:
  - Nombre de tabla.
  - Nombres de columnas.
  - Tipos de datos básicos (`INT`, `VARCHAR`).
  - Claves primarias (`id`).
  - Posibles claves foráneas (`*_id`).

✅ Vista previa de todas las tablas y datos.  
✅ Generación del script SQL `CREATE TABLE`.  
✅ Botón para copiar SQL al portapapeles.

---

## 📂 **Instalación rápida**

```bash
# Clona el repositorio
git clone https://github.com/tu_usuario/generador-bd.git
cd generador-bd

# Instala dependencias
composer install

# Configura tu archivo .env y genera la key de aplicación
cp .env.example .env
php artisan key:generate

# Configura la conexión a tu base de datos en el archivo .env

---
## ⚙️ Uso