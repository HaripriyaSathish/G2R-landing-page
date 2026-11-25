# One Render Web Service: React website + PHP API + admin panel on a single URL.
#   https://<your-service>.onrender.com/        -> website
#   https://<your-service>.onrender.com/admin   -> admin panel
#   https://<your-service>.onrender.com/api/... -> API
# Required env var on Render: DATABASE_URL (Neon PostgreSQL connection string)

# ---- 1. Build the React frontend ----
FROM node:20-alpine AS web
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
# Same origin as the API, so no VITE_API_URL is needed
RUN npm run build

# ---- 2. PHP runtime ----
FROM php:8.2-cli-alpine
RUN apk add --no-cache postgresql-libs \
 && apk add --no-cache --virtual .build-deps postgresql-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
 && apk del .build-deps

RUN { \
      echo 'upload_max_filesize=12M'; \
      echo 'post_max_size=14M'; \
      echo 'memory_limit=256M'; \
      echo 'expose_php=Off'; \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'session.cookie_httponly=1'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /app
COPY backend/ ./backend/
COPY --from=web /app/frontend/dist ./frontend/dist
RUN chown -R www-data:www-data /app/backend/uploads

USER www-data
ENV PORT=10000 \
    PHP_CLI_SERVER_WORKERS=4 \
    FRONTEND_DIST=/app/frontend/dist
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t backend backend/router.php"]
