# Backend PHP

Code serveur de la plateforme d'examens IPSSI.

## Structure

- **`api/`** — Endpoints REST JSON (auth, examens, banque, ia, emails, rgpd)
- **`lib/`** — Classes utilitaires (Auth, Logger, CsvWriter, Mailer, ScoreCalculator...)
- **`public/`** — Point d'entrée web (`index.php` + `.htaccess`)
- **`config.sample.php`** — Template de configuration (à copier en `config.php`)
- **`bootstrap.php`** — Chargement initial (autoload, session)

## Version PHP requise

PHP 7.4 minimum, **PHP 8.0+ recommandé** pour de meilleures performances.

## Dépendances

Aucune dépendance externe en Phase P1 (PHP natif).
Ajouts prévus en P4 : SDK Anthropic + OpenAI via Composer.

*À compléter de P1 à P9*
