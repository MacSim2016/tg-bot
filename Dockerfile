FROM php:8.0-cli
WORKDIR /app
COPY . /app
CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
EXPOSE 80
