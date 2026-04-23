<?php
/**
 * Configuration de la plateforme d'examens IPSSI
 *
 * ⚠️ Copier ce fichier en config.php et remplir les vraies valeurs.
 * Le fichier config.php est dans .gitignore (ne jamais commiter).
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

return [

    // ========================================================================
    // Environnement
    // ========================================================================

    /** @var string 'development' | 'production' */
    'environment' => 'development',

    /** @var bool Affiche les erreurs PHP en cas de problème (dev uniquement) */
    'debug' => true,

    /** @var string URL de base de la plateforme (sans slash final) */
    'base_url' => 'http://localhost:8000',

    /** @var string Fuseau horaire */
    'timezone' => 'Europe/Paris',

    // ========================================================================
    // Chemins (relatifs à ce fichier)
    // ========================================================================

    'paths' => [
        'data' => __DIR__ . '/../data',
        'logs' => __DIR__ . '/../data/logs',
        'sessions' => __DIR__ . '/../data/sessions',
    ],

    // ========================================================================
    // Sécurité
    // ========================================================================

    'security' => [
        /** @var int Coût bcrypt (10 par défaut, 12 pour prod sécurisée) */
        'bcrypt_cost' => 10,

        /** @var int Durée de vie d'une session en secondes (2h par défaut) */
        'session_lifetime' => 7200,

        /** @var bool Force cookies HTTPS (à activer en production) */
        'session_secure' => false,

        /** @var int Nombre max de tentatives login avant blocage */
        'rate_limit_max_attempts' => 5,

        /** @var int Durée du blocage en secondes (15 min par défaut) */
        'rate_limit_window' => 900,

        /** @var string Clé secrète pour CSRF (GÉNÉRER ALÉATOIREMENT !) */
        'csrf_secret' => 'CHANGER_EN_PRODUCTION_AVEC_UNE_CLE_ALEATOIRE_64_CHARS',

        /** @var string Clé pour chiffrer les clés API IA des enseignants */
        'encryption_key' => 'CHANGER_EN_PRODUCTION_AVEC_UNE_CLE_ALEATOIRE_32_BYTES',
    ],

    // ========================================================================
    // Email (Phase P6)
    // ========================================================================

    'mail' => [
        'from_email' => 'no-reply@examens.votre-domaine.fr',
        'from_name' => 'Plateforme Examens IPSSI',
        'smtp_host' => 'ssl0.ovh.net',
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'smtp_user' => '',
        'smtp_pass' => '',
    ],

    // ========================================================================
    // IA (Phase P4) — les clés sont par enseignant, pas ici
    // ========================================================================

    'ia' => [
        /** @var array Modèles disponibles */
        'models' => [
            'claude-opus-4.7' => [
                'provider' => 'anthropic',
                'label' => 'Claude Opus 4.7 (Premium)',
                'cost_per_question' => 0.020,
            ],
            'claude-sonnet-4.6' => [
                'provider' => 'anthropic',
                'label' => 'Claude Sonnet 4.6 (Rapide)',
                'cost_per_question' => 0.005,
            ],
            'gpt-4o' => [
                'provider' => 'openai',
                'label' => 'GPT-4o',
                'cost_per_question' => 0.010,
            ],
            'gpt-4-turbo' => [
                'provider' => 'openai',
                'label' => 'GPT-4 Turbo',
                'cost_per_question' => 0.020,
            ],
        ],
    ],

    // ========================================================================
    // Backup (Phase P8)
    // ========================================================================

    'backup' => [
        'enabled' => true,
        'local_retention_days' => 30,
        'github_repo' => 'melafrit/examens-backups',
        'github_token' => '', // GitHub PAT, à configurer pour backup auto
    ],

    // ========================================================================
    // Logs
    // ========================================================================

    'logs' => [
        'level' => 'info', // debug | info | warning | error
        'max_file_size_mb' => 10,
        'rotation_days' => 30,
    ],

];
