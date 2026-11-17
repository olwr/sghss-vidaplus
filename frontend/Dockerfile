# syntax=docker/dockerfile:1
# ---- dev: Vite com HMR (docker compose) ----
FROM node:22-alpine AS dev
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ---- build: gera dist/ com a URL da API embutida (VITE_* é resolvido em tempo de build) ----
FROM dev AS build
ARG VITE_API_URL=http://localhost:8000/api/v1
ARG VITE_JITSI_DOMAIN=8x8.vc
ENV VITE_API_URL=$VITE_API_URL VITE_JITSI_DOMAIN=$VITE_JITSI_DOMAIN
RUN npm run build-only

# ---- prod: arquivos estáticos servidos pelo Apache httpd (fallback de SPA p/ vue-router history) ----
FROM httpd:2.4-alpine AS prod
COPY docker/httpd-spa.conf /usr/local/apache2/conf/extra/httpd-spa.conf
RUN echo 'Include conf/extra/httpd-spa.conf' >> /usr/local/apache2/conf/httpd.conf \
    && sed -i 's/^Listen 80$/Listen 8080/; s/^#LoadModule rewrite_module/LoadModule rewrite_module/; s/^#LoadModule headers_module/LoadModule headers_module/' /usr/local/apache2/conf/httpd.conf
COPY --from=build /app/dist/ /usr/local/apache2/htdocs/
EXPOSE 8080
