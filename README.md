# 📦 PrestaShop Module: Filtro de Código Postal

Este es un módulo personalizado para PrestaShop (compatible con versiones 1.7.x y 8.x) diseñado para restringir las compras basándose en el código postal del cliente durante el proceso de pago (Checkout).

## ✨ Características principales
* **Bloqueo dinámico:** Impide finalizar la compra si el código postal del cliente no está en la lista de permitidos.
* **Configuración sencilla:** Panel de administración nativo (Backoffice) para añadir o eliminar los códigos postales autorizados separados por comas.
* **Integración nativa:** Utiliza los *Hooks* estándar de PrestaShop (`displayPaymentTop` / `actionValidateOrder`) para garantizar máxima compatibilidad con el core y otras plantillas.

## 🛠️ Entorno de Desarrollo
Este módulo ha sido desarrollado utilizando un entorno aislado con **Docker** (Apache, PHP 8.1 y MariaDB) para garantizar unas pruebas limpias y un rendimiento óptimo, siguiendo las mejores prácticas de desarrollo.

## 🚀 Instalación y Pruebas

### Opción A: Probar en Entorno de Desarrollo (Docker) 🐳
Este repositorio incluye un entorno completo y preconfigurado para que puedas probar el módulo en 2 minutos sin ensuciar tu máquina local:

1. Clona este repositorio: `git clone https://github.com/robertocmt13/prestashop-filtro-codigopostal.git`
2. Copia el archivo de entorno: `cp .env.example .env`
3. Levanta el servidor: `docker compose up -d`
4. Accede a `http://localhost:8081/admin_dev` (Usuario: `demo@prestashop.com` / Pass: `prestashop_demo`).
5. Si no deja entrar en el backoffice, debes eliminar la carpeta `install` de la carpeta `ps_data`.
6. El módulo ya estará disponible en la carpeta `/modules/codigopostal` listo para instalar y probar.

### Opción B: Instalación Estándar (Tienda en Producción) 🛒
1. Descarga el contenido de la carpeta `src_modulo` y comprímelo en un archivo llamado `codigopostal.zip`.
2. En el Backoffice de tu PrestaShop, ve a **Módulos > Gestor de Módulos**.
3. Haz clic en **Subir un módulo** y selecciona el archivo `.zip`.

---
**Autor:** Roberto Carlos Moyano  
**Rol:** Fullstack Developer & SysAdmin