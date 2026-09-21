# DocCompiler

API REST self-hosted para convertir documentos en texto limpio listo para pipelines de IA.

- **Repositorio canónico (futuro):** https://github.com/procodigo/doccompiler  
- **Sitio / docs (futuro):** https://doccompiler.procodigo.cl  
- **Licencia:** MIT  
- **Estado:** Phase 1 — usable para TXT, MD y PDF nativo; **no** es “production ready” para todos los formatos anunciados en el diseño.

## Qué es

DocCompiler es una aplicación Laravel (Filament + Horizon + Pulse) pensada como servicio M2M: subes un archivo, recibes un job asíncrono y luego el texto extraído. Autenticación por token de cliente API (no Sanctum de usuarios finales).

## Madurez (sé honesto)

| Formato | Estado Phase 1 |
|---------|----------------|
| `.txt` | ✅ Implementado |
| `.md` | ✅ Implementado (normalización ligera) |
| `.pdf` | ✅ Texto nativo vía `pdftotext` (poppler). **Sin OCR** aún |
| `.docx` / `.doc` | ❌ Aceptados por validación MIME, pero el job falla con `UNSUPPORTED_FORMAT` |
| `.xlsx` / `.xls` / `.csv` | ❌ Igual que arriba |
| OCR de PDF escaneado | ❌ Pendiente (`tesseract` + `pdftoppm`) |

La plantilla de marketing y la configuración aceptan más extensiones de las que el pipeline realmente extrae. Eso es intencional: la API y el esquema ya están listos; los drivers se irán completando.

## Requisitos

- PHP 8.3+
- Composer 2
- PostgreSQL (recomendado) o MySQL/SQLite
- Redis (cola Horizon)
- Binarios para PDF: `poppler-utils` (`pdftotext`, `pdfinfo`; `pdftoppm` reservado para OCR futuro)

Opcional más adelante: `tesseract-ocr`, LibreOffice (`soffice`).

## Quickstart (Docker)

```bash
git clone https://github.com/procodigo/doccompiler.git
cd doccompiler
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan admin:create-user
```

La app queda en `http://localhost:8080` (o `APP_PORT`). Horizon corre en el servicio `horizon`.

Documentación detallada: [docs/installation.md](docs/installation.md).

## Quickstart (Composer / local)

```bash
git clone https://github.com/procodigo/doccompiler.git
cd doccompiler
composer install
cp .env.example .env
php artisan key:generate
# configura DB_* y REDIS_* en .env
php artisan migrate
php artisan admin:create-user
# terminal 1
php artisan serve
# terminal 2
php artisan horizon
```

En Ubuntu: `sudo apt-get install -y poppler-utils`. Verifica con `php artisan system:check-binaries`.

## API (resumen)

Autenticación: `Authorization: Bearer <token>` emitido desde el panel Filament (API Clients).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/health` | Binarios, cola, disco libre |
| `POST` | `/api/v1/documents` | Sube `document` (multipart) → `202` + `uuid` |
| `GET` | `/api/v1/documents/{uuid}` | Estado del job |
| `GET` | `/api/v1/documents/{uuid}/content` | Texto extraído (si `completed`) |
| `DELETE` | `/api/v1/documents/{uuid}` | Borra job y archivos |

Documentación OpenAPI interactiva vía [Scramble](https://github.com/dedoc/scramble) (ruta típica `/docs/api` según tu despliegue).

Ejemplo:

```bash
curl -X POST http://localhost:8080/api/v1/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "document=@nota.txt"
```

## Variables de entorno relevantes

Ver `.env.example`. Destacadas:

- `DOC_EXTRACTOR_MAX_KB`, `DOC_EXTRACTOR_TIMEOUT`
- `DOC_EXTRACTOR_RETENTION_DAYS`, `DOC_EXTRACTOR_DISK`, `DOC_EXTRACTOR_RESULT_SPILLOVER_BYTES`
- `DOC_EXTRACTOR_QUEUE_CONNECTION`, `DOC_EXTRACTOR_QUEUE_NAME`
- `BIN_PDFTOTEXT`, `BIN_PDFINFO`, `BIN_PDFTOPPM`, `BIN_TESSERACT`, `BIN_SOFFICE`

## Seguridad

Reporta vulnerabilidades a **security@procodigo.cl** o vía [GitHub Security Advisories](https://github.com/procodigo/doccompiler/security/advisories) cuando el repo esté en la org. No uses contactos de Laravel upstream.

## Licencia

MIT — ver [LICENSE](LICENSE). Copyright Procodigo / Sebastián Aguilera Vallejos.

## Docs internas

Los archivos `docs/01-*.md`, `docs/02-*.md` y `docs/schema.md` son notas de diseño internas. La guía de usuario es [docs/installation.md](docs/installation.md). Ver [docs/README.md](docs/README.md).
