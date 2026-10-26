# Nuxt frontend for Dokploy (Build Type: Dockerfile, Build Path: /frontend)
# 1) build the static site
FROM node:22-alpine AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# optional: override the backend URL at build time (Dokploy → Build-time Arguments)
ARG NUXT_PUBLIC_API_BASE=https://resbn.devams.site/api
ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE
RUN npm run generate

# 2) serve it with nginx
FROM nginx:alpine
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/.output/public /usr/share/nginx/html
EXPOSE 80
