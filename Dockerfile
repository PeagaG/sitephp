# Dockerfile para servir um site PHP simples
FROM php:8.3-cli

WORKDIR /app
COPY . /app

# O EasyPanel injeta a variável $PORT; usamos ela no CMD
ENV PORT=${PORT:-8080}
EXPOSE 8080

# Servidor embutido do PHP atendendo a pasta atual
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t /app"]
