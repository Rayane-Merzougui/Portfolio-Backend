FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql && \
    a2enmod rewrite headers

# Créer un utilisateur non-root pour sécurité
RUN useradd -m -u 1000 appuser && chown -R appuser:appuser /var/www/html

# Basculer vers l'utilisateur non-root
USER appuser

# Copier les fichiers de l'application
COPY --chown=appuser:appuser . /var/www/html/

# Exposer le port
EXPOSE 8080

# Script de démarrage qui utilise le port dynamique
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
