# 📧 Système d'emails — Documentation

Système d'envoi d'emails automatiques pour la plateforme IPSSI.

> **Modes** : disabled / dev / prod
> **Format** : HTML + fallback texte auto
> **Pas de dépendance externe** : utilise PHP natif (mail / socket SMTP)

---

## 🎯 Vue d'ensemble

Le système repose sur deux classes :

| Classe | Rôle |
|---|---|
| `Mailer` | Wrapper d'envoi avec 3 modes (disabled/dev/prod) |
| `EmailTemplate` | Rendu de templates avec variables |

Et **6 templates** dans `backend/templates/emails/` :
- `base.php` — Layout HTML commun
- `etudiant_submission.php` — Confirmation soumission étudiant
- `etudiant_correction.php` — Correction disponible
- `prof_examen_cree.php` — Examen publié (après `publish()`)
- `prof_premier_passage.php` — Premier passage reçu
- `prof_cloture.php` — Examen clôturé + stats

---

## ⚙️ Configuration

Dans `config.php` :

```php
// Mode d'envoi
define('MAILER_MODE', 'dev'); // disabled | dev | prod

// Expéditeur
define('MAILER_FROM', 'noreply@ipssi.fr');
define('MAILER_FROM_NAME', 'IPSSI Examens');
define('MAILER_REPLY_TO', 'm.elafrit@ecole-ipssi.net');

// SMTP optionnel (sinon mail() standard)
define('MAILER_SMTP_HOST', 'smtp.ovh.net');
define('MAILER_SMTP_PORT', 587);
define('MAILER_SMTP_USER', 'noreply@ipssi.fr');
define('MAILER_SMTP_PASS', 'xxx');

// URL de base pour les liens dans les emails
define('BASE_URL', 'https://examens.ipssi.fr');
```

### 📊 Modes disponibles

| Mode | Comportement | Usage |
|---|---|---|
| `disabled` | Aucun envoi, `send()` retourne `true` silencieusement | Par défaut, ou tests sans logs |
| `dev` | Écrit dans `data/logs/emails.log` (JSON lines) | Développement, tests locaux |
| `prod` | Envoi réel via SMTP ou `mail()` | Production |

---

## 🔔 Triggers automatiques (hooks)

### 📤 `ExamenManager::publish()`
→ Email **prof** avec `prof_examen_cree` :
- Confirmation de publication
- Code d'accès en gros (gradient)
- Récap (durée, nb questions, dates)
- Lien vers le tableau de bord admin

### 📤 `PassageManager::submit()` 
Deux emails :

**A. Email étudiant** (systématique) avec `etudiant_submission` :
- Score géant (X/Y) + pourcentage + mention
- Durée et horodatage
- Bouton **"Voir la correction"** si disponible
- OU message "Correction dans X minutes" si délai

**B. Email prof** (seulement si premier passage !) avec `prof_premier_passage` :
- Nom de l'étudiant + score
- Lien vers les résultats

> 💡 **Anti-spam** : le prof ne reçoit QUE le 1er passage. Ensuite, il consulte les stats dans le dashboard.

---

## 💻 Usage dans le code

### Envoi direct

```php
use Examens\Lib\Mailer;

$mailer = new Mailer();

$mailer->send(
    'user@test.fr',
    'Sujet de l\'email',
    '<p>Contenu <strong>HTML</strong></p>',
    [
        'reply_to' => 'custom@test.fr',
        'cc' => ['manager@test.fr'],
    ]
);
```

### Avec template

```php
use Examens\Lib\EmailTemplate;
use Examens\Lib\Mailer;

$tpl = new EmailTemplate();
$mailer = new Mailer();

$rendered = $tpl->render('etudiant_submission', [
    'studentName' => 'Jean Dupont',
    'examTitle' => 'Contrôle Maths',
    'scoreBrut' => 15,
    'scoreMax' => 20,
    'scorePct' => 75.0,
    'durationSec' => 2700,
    'submittedAt' => date('c'),
    'correctionUrl' => 'https://ipssi.fr/etudiant/correction.html?token=...',
]);

$mailer->send('etudiant@test.fr', $rendered['subject'], $rendered['html']);
```

---

## 📝 Variables par template

### `etudiant_submission`
| Variable | Type | Description |
|---|---|---|
| `studentName` | string | "Prénom Nom" |
| `examTitle` | string | Titre |
| `scoreBrut` | int | ex. 15 |
| `scoreMax` | int | ex. 20 |
| `scorePct` | float | ex. 75.0 |
| `durationSec` | int | secondes |
| `submittedAt` | string | ISO 8601 |
| `correctionUrl` | string? | URL si dispo |
| `correctionDelay` | int? | min si différée |

### `etudiant_correction`
| Variable | Type |
|---|---|
| `studentName` | string |
| `examTitle` | string |
| `correctionUrl` | string |

### `prof_examen_cree`
| Variable | Type |
|---|---|
| `profName` | string |
| `examTitle` | string |
| `examId` | string |
| `accessCode` | string |
| `nbQuestions` | int |
| `dureeSec` | int |
| `dateOuverture` | string (ISO) |
| `dateCloture` | string (ISO) |
| `maxPassages` | int |
| `adminUrl` | string |

### `prof_premier_passage`
| Variable | Type |
|---|---|
| `profName` | string |
| `examTitle` | string |
| `studentName` | string |
| `scoreBrut` | int |
| `scoreMax` | int |
| `scorePct` | float |
| `adminUrl` | string |

### `prof_cloture`
| Variable | Type |
|---|---|
| `profName` | string |
| `examTitle` | string |
| `nbPassages` | int |
| `avgScorePct` | float |
| `minScorePct` | float |
| `maxScorePct` | float |
| `anomaliesCount` | int |
| `adminUrl` | string |

---

## 🧪 Tests

```bash
# Tests unitaires Mailer + EmailTemplate
php backend/test_mailer.php
# → 18/18 ✅

# Voir les emails loggés (mode dev)
tail -f data/logs/emails.log
```

### Lecture du log de dev

Chaque email est une ligne JSON :

```json
{
  "to": ["user@test.fr"],
  "from": "noreply@ipssi.fr",
  "from_name": "IPSSI Examens",
  "reply_to": "m.elafrit@ecole-ipssi.net",
  "subject": "Confirmation de soumission — ...",
  "html_body": "<!DOCTYPE html>...",
  "text_body": "Bonjour Jean...",
  "cc": [],
  "bcc": [],
  "sent_at": "2026-05-01T10:45:00+02:00",
  "mode": "dev"
}
```

Pour consulter facilement :

```php
$mailer = new Mailer();
$emails = $mailer->readLog(10); // 10 derniers
```

---

## 🛡️ Sécurité

### Échappement HTML
Les templates utilisent `EmailTemplate::e()` qui appelle `htmlspecialchars()` :

```php
use Examens\Lib\EmailTemplate;

$safe = EmailTemplate::e($userInput); // Échappe &, <, >, ", '
```

### Validation emails
Avant d'envoyer, le `Mailer` valide chaque adresse avec `FILTER_VALIDATE_EMAIL`. Si une adresse est invalide, l'envoi est annulé (`false`) et loggé.

### Fallback texte
Tous les emails HTML sont accompagnés d'une version texte brut générée automatiquement (ou fournie via `options.text_body`). Cela :
- Améliore la délivrabilité (SPF/DKIM)
- Permet la lecture dans des clients texte
- Évite les filtres anti-spam

### Encodage des headers
Les sujets et noms contenant des caractères non-ASCII sont encodés en **RFC 2047 Base64** (`=?UTF-8?B?...?=`).

---

## 📊 Gestion d'erreurs

Tous les hooks dans les managers sont **try-catchés** : si l'envoi d'un email échoue, cela **n'interrompt pas** l'action principale (publish, submit).

```php
// Dans PassageManager::submit()
try {
    $mailer->send(...);
} catch (\Throwable $e) {
    $this->logger->error('Hook email submit failed', ['error' => $e->getMessage()]);
    // ← mais le passage reste soumis !
}
```

---

## 🎨 Design des templates

Tous les templates partagent le layout `base.php` :

- ✅ **Responsive** : `<table role="presentation">` avec `width="600"`
- ✅ **Compatible Outlook/Gmail/Apple Mail** : inline CSS partout
- ✅ **Gradient header** (bleu → violet)
- ✅ **Footer** avec licence CC BY-NC-SA 4.0
- ✅ **Preheader** (texte d'aperçu)

### Extrait du layout

```html
<table role="presentation" width="600" style="max-width:600px;background:#ffffff;border-radius:12px;">
  <tr>
    <td style="background:linear-gradient(135deg,#3b82f6,#8b5cf6);padding:32px 40px;">
      <h1 style="color:#ffffff;">{title}</h1>
    </td>
  </tr>
  <tr>
    <td style="padding:32px 40px;">{content}</td>
  </tr>
  <!-- Footer -->
</table>
```

---

## 🚀 Déploiement

### Checklist prod

- [ ] `MAILER_MODE = 'prod'` dans config.php
- [ ] SMTP configuré (OVH, SendGrid, etc.)
- [ ] `BASE_URL` défini pour les liens
- [ ] `MAILER_FROM` et `MAILER_REPLY_TO` personnalisés
- [ ] Testé avec un vrai envoi
- [ ] DNS SPF/DKIM configurés pour le domaine

### Commandes utiles

```bash
# Tester en dev
MAILER_MODE=dev php backend/test_mailer.php

# Vérifier le log (dev)
tail -f data/logs/emails.log | jq '.subject'

# Purger le log
> data/logs/emails.log
```

---

© 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
