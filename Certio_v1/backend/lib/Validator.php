<?php
/**
 * Validator — Validation des entrées utilisateur
 *
 * Usage :
 *   $v = new Validator($data);
 *   $v->required('email')->email('email');
 *   $v->required('password')->minLength('password', 8);
 *   if (!$v->isValid()) {
 *     Response::badRequest('Validation échouée', $v->errors());
 *   }
 *
 * © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
 */

declare(strict_types=1);

namespace Examens\Lib;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->data;
    }

    /**
     * Vérifie qu'un champ est présent et non vide.
     */
    public function required(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $message ?? "Le champ « {$field} » est obligatoire.");
        }
        return $this;
    }

    /**
     * Vérifie que le champ est une adresse email valide.
     */
    public function email(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "Le champ « {$field} » doit être une adresse email valide.");
        }
        return $this;
    }

    /**
     * Longueur minimum.
     */
    public function minLength(string $field, int $min, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, $message ?? "Le champ « {$field} » doit contenir au moins {$min} caractères.");
        }
        return $this;
    }

    /**
     * Longueur maximum.
     */
    public function maxLength(string $field, int $max, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, $message ?? "Le champ « {$field} » ne doit pas dépasser {$max} caractères.");
        }
        return $this;
    }

    /**
     * Valeur dans une liste autorisée.
     */
    public function in(string $field, array $allowed, string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->addError($field, $message ?? "Le champ « {$field} » a une valeur invalide.");
        }
        return $this;
    }

    /**
     * Correspond à un pattern regex.
     */
    public function matches(string $field, string $pattern, string $message = null): self
    {
        $value = $this->data[$field] ?? '';
        if (is_string($value) && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, $message ?? "Le champ « {$field} » a un format invalide.");
        }
        return $this;
    }

    /**
     * Type booléen.
     */
    public function boolean(string $field, string $message = null): self
    {
        $value = $this->data[$field] ?? null;
        if ($value !== null && !is_bool($value)) {
            // Accepter les strings "true"/"false" et les 0/1
            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                $this->addError($field, $message ?? "Le champ « {$field} » doit être un booléen.");
            } else {
                $this->data[$field] = $bool;
            }
        }
        return $this;
    }

    /**
     * Nettoie une chaîne (trim, optionnellement lowercase).
     */
    public function sanitize(string $field, bool $lowercase = false): self
    {
        if (isset($this->data[$field]) && is_string($this->data[$field])) {
            $this->data[$field] = trim($this->data[$field]);
            if ($lowercase) {
                $this->data[$field] = \mb_strtolower($this->data[$field]);
            }
        }
        return $this;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
