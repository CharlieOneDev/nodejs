FROM php:8.2-apache

# 把根目录下的所有文件复制进去（而不是 public 文件夹）
COPY . /var/www/html/

# 开启 Apache 重写模块（如果你使用 Laravel 或类似框架）
RUN a2enmod rewrite
