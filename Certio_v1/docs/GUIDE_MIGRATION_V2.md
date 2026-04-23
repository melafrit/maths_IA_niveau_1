# 🔄 Guide de migration v2 : fichiers → MySQL

> ⚠️ **Document placeholder — sera rédigé en v2**
>
> Ce guide concerne la migration future du stockage fichiers (v1) vers MySQL (v2).

## 📋 Contenu prévu

Cette migration ne sera effectuée **que si nécessaire**, quand le volume atteint :
- > 500 examens archivés, OU
- > 10 000 passages archivés, OU
- Besoin de statistiques SQL avancées

### Partie 1 — Pré-migration
- Évaluer la nécessité
- Faire un backup complet v1 (avant toute modification)
- Commander MySQL sur l'offre OVH (inclus ou +1 €/mois)

### Partie 2 — Schéma MySQL
```sql
-- Exemple de structure prévue
CREATE TABLE questions (
  id VARCHAR(20) PRIMARY KEY,
  module VARCHAR(50),
  chapitre VARCHAR(30),
  theme VARCHAR(100),
  difficulte ENUM('vert','jaune','orange','rouge'),
  type ENUM('conceptuel','calcul','code','formule'),
  enonce TEXT,
  propositions JSON,
  bonne_reponse_index INT,
  -- ...
);

CREATE TABLE examens (
  code VARCHAR(30) PRIMARY KEY,
  titre VARCHAR(255),
  enseignant_id INT,
  -- ...
);

-- etc.
```

### Partie 3 — Scripts de migration
- `scripts/migrate_to_mysql.php` — Lit les JSON et insère en MySQL
- Vérifications d'intégrité (checksums)
- Rollback en cas d'erreur

### Partie 4 — Bascule du code
- Remplacer `FileStorage.php` par `MysqlStorage.php`
- Interfaces identiques (pattern Repository)
- Aucun changement frontend nécessaire

### Partie 5 — Tests post-migration
- Comparer les stats avant/après
- Vérifier que tous les examens sont accessibles
- Valider l'intégrité des signatures

### Partie 6 — Rollback possible
- Si la migration échoue, retour au stockage fichiers
- Les CSV physiques sont toujours conservés en parallèle

---

*À rédiger en v2 (si et quand nécessaire)*

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
