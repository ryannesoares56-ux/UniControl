FROM php:8.2-cli

WORKDIR /var/www/html

# Instala extensões PDO caso necessário
RUN docker-php-ext-install pdo pdo_mysql 2>/dev/null || true

# Copia os arquivos da aplicação
COPY . /var/www/html

# Configura a porta dinâmica do Render
ENV PORT=10000
EXPOSE 10000

# Inicia o servidor embutido do PHP apontando para a raiz
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /var/www/html"]
