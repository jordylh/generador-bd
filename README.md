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
```

---
## ⚙️ Uso

```bash
# Levanta el servidor de desarrollo
php artisan serve

# Abre en tu navegador
http://127.0.0.1:8000/home
```

1️⃣ Sube tu archivo Excel.
2️⃣ Visualiza la estructura detectada.
3️⃣ Genera el SQL y cópialo para crear tu base de datos en MySQL.


---

## 🎨 Frontend
* Usa Bootstrap 5 y AdminLTE como panel de administración.

---

## 🧪 Tests

```bash
php artisan test
```
Incluye pruebas unitarias para validar la lectura de archivos de ejemplo y la generación de SQL.

---

## 📌 Estado actual

* Funcionalidad principal implementada.

* Generación de SQL básica (tipos de datos y claves primarias).

* Relaciones y detección avanzada de tipos de datos en desarrollo.

---

## ✅ Contribuciones

¡Pull requests y sugerencias son bienvenidos!
Por favor abre un issue para proponer mejoras.

---

Autor: jordylh

---

## ✨ Notas

Este proyecto es educativo y experimental.
Usar bajo tu propio riesgo en producción.

---

## 📢 Contacto

Para dudas o soporte:
* Abre un Issue
* O contáctame por GitHub