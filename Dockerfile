# Используем официальный образ PHP с Apache
FROM php:7.4-apache

# Установка необходимых расширений
RUN docker-php-ext-install mysqli

# Включаем модуль headers
RUN a2enmod headers

# Установка ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Копирование файлов проекта в контейнер
COPY . /var/www/html/

# Открываем порт 80 для доступа к приложению
EXPOSE 80
