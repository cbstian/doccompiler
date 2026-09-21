# Guía de instalación — DocCompiler

## Requisitos

- PHP 8.3+, Composer 2, Node (solo si compilas assets Filament/Vite)
- PostgreSQL 16+ (o MySQL 8 / SQLite para demos)
- Redis
- `poppler-utils` para PDF nativo

## Docker Compose

```bash
git clone https://github.com/procodigo/doccompiler.git
cd doccompiler
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan admin:create-user
```

Servicios: `app` (HTTP), `horizon` (cola), `postgres`, `redis`.

Binarios en la imagen: `pdftotext`, `pdfinfo`, `pdftoppm`. **No** incluye Tesseract ni LibreOffice en Phase 1.

## Sin Docker

1. `composer install`
2. Copia `.env.example` → `.env`, genera `APP_KEY`, configura DB y Redis
3. `php artisan migrate`
4. `sudo apt-get install -y poppler-utils` (Ubuntu)
5. `php artisan system:check-binaries`
6. `php artisan admin:create-user`
7. `php artisan serve` + `php artisan horizon`

## Crear un API client

1. Entra al panel Filament (`/admin` por defecto de Filament)
2. Crea un **API Client** y copia el token en claro (solo se muestra una vez)
3. Usa `Authorization: Bearer <token>` en la API

## Formatos

- **Soportados ahora:** TXT, MD, PDF (capa de texto nativa)
- **Validados pero no extraídos aún:** DOCX, DOC, XLSX, XLS, CSV (el job termina en `failed` / `UNSUPPORTED_FORMAT`)
- **OCR:** no implementado

## Sitio público

Cuando el proyecto se transfiera a la org: https://github.com/procodigo/doccompiler y https://doccompiler.procodigo.cl
