# DocCompiler — Phase 1 demo image
# Includes poppler-utils (pdftotext/pdfinfo/pdftoppm) for native PDF extraction.
# OCR (tesseract) and LibreOffice are NOT installed here yet.

FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        poppler-utils \
    && docker-php-ext-install pdo_pgsql pgsql pcntl posix zip bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Source is bind-mounted in compose.yml; install deps at container start if needed.
COPY docker/entrypoint.sh /usr/local/bin/doccompiler-entrypoint
RUN chmod +x /usr/local/bin/doccompiler-entrypoint

EXPOSE 8000

ENTRYPOINT ["doccompiler-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
