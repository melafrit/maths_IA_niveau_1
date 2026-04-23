# 🎯 Prompts VS Code optimisés — Phases 5, 6 et 7

> **Livrable 4/5 — Prompts pour les améliorations, tests et déploiement final**

| Champ | Valeur |
|---|---|
| **Livrable** | 4/5 |
| **Phases couvertes** | P5 (5 sous-phases), P6, P7 |
| **Version** | 1.0 |
| **Auteur** | Mohamed EL AFRIT |
| **Licence** | CC BY-NC-SA 4.0 |

---

## 📖 Guide d'utilisation

Même principe que le livrable 3 :
1. Copier le prompt de la phase/sous-phase
2. Coller dans Claude Code / Cursor / Copilot Agent
3. Review + valider + commit

### 🤖 IA recommandées

| Phase | IA recommandée | Pourquoi |
|:-:|---|---|
| **5A** Sécurité | Claude Code | Crypto, TOTP, nuances sécurité |
| **5B** Multi-tenant | Claude Code | Refactoring isolation data |
| **5C** LMS | Cursor ou Claude | Parsers Moodle/Word, SCORM gen |
| **5D** A11y/i18n | Cursor | Parcours UI exhaustif |
| **5E** Community | Claude Code | Logique métier + modération |
| **P6** Tests | Claude Code | Expertise test strategy |
| **P7** Déploiement | Copilot terminal | Scripts bash, SSH |

---

## Sommaire

1. [Prompt Phase 5A — Sécurité avancée](#prompt-phase-5a--sécurité-avancée)
2. [Prompt Phase 5B — Multi-tenant Workspaces + SSO](#prompt-phase-5b--multi-tenant-workspaces--sso)
3. [Prompt Phase 5C — Intégrations LMS](#prompt-phase-5c--intégrations-lms)
4. [Prompt Phase 5D — Accessibilité + i18n + PWA](#prompt-phase-5d--accessibilité--i18n--pwa)
5. [Prompt Phase 5E — Banque communautaire](#prompt-phase-5e--banque-communautaire)
6. [Prompt Phase 6 — Tests complets](#prompt-phase-6--tests-complets)
7. [Prompt Phase 7 — Migration & Déploiement](#prompt-phase-7--migration--déploiement)

---

## Prompt Phase 5A — Sécurité avancée

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 5A : SÉCURITÉ AVANCÉE

Les phases 0-4 sont terminées. Tu as maintenant :
- Certio v2.0 avec CBM, 7 types questions, analytics, doc interactive
- 389 tests v1 + ~150 tests v2 qui passent

## 🎯 Objectif Phase 5A (2 jours)

Implémenter 3 couches de sécurité avancée :
1. **2FA TOTP** (Google Authenticator) pour admins/profs
2. **Audit log complet** (qui fait quoi, quand, IP)
3. **Anti-triche renforcé** avec score de confiance

## 📚 Documents de référence
- `examens/docs/certio_v2/01_NOTE_DE_CADRAGE.md` (section 7 : risques)
- `examens/docs/certio_v2/02_PLANNING_8_PHASES.md` (Phase 5A)
- Managers skeleton créés en P0 : `TotpManager.php`, `AuditLogger.php`, `AntiCheatAnalyzer.php`

---

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — TotpManager (TOTP / 2FA)

Implémenter dans `backend/lib/TotpManager.php` :

```php
<?php
declare(strict_types=1);
namespace Certio\Lib;

/**
 * Time-based One-Time Password (RFC 6238) - Compatible Google Authenticator
 * 
 * @package Certio\Lib
 * @author Mohamed EL AFRIT
 * @license CC-BY-NC-SA-4.0
 */
class TotpManager
{
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const ALGORITHM = 'sha1';
    private const WINDOW = 1; // ±1 period pour drift clock
    
    private FileStorage $storage;
    private Logger $logger;
    private string $secretsDir;
    
    /**
     * Génère un secret TOTP Base32 (20 bytes = 160 bits).
     */
    public function generateSecret(): string;
    
    /**
     * URL de provisioning pour Google Authenticator (otpauth://).
     * Format: otpauth://totp/Certio:user@mail.com?secret=XXX&issuer=Certio
     */
    public function getProvisioningUri(string $secret, string $userEmail, string $issuer = 'Certio'): string;
    
    /**
     * Génère un QR Code SVG à afficher à l'utilisateur.
     * Utiliser une lib QR code simple (pure PHP) ou générer SVG manuellement.
     */
    public function generateQrCodeSvg(string $provisioningUri): string;
    
    /**
     * Génère le TOTP actuel à partir du secret.
     */
    public function generateToken(string $secret, ?int $timestamp = null): string;
    
    /**
     * Valide un code TOTP avec fenêtre de drift (±1 période par défaut).
     *
     * @param string $code      6 chiffres saisis par l'utilisateur
     * @param string $secret    Secret de l'utilisateur
     * @return bool
     */
    public function validate(string $code, string $secret): bool;
    
    /**
     * Stocke le secret TOTP d'un utilisateur (chiffré).
     * Utilise openssl_encrypt avec une clé maître dans config.
     */
    public function storeUserSecret(string $userId, string $secret): bool;
    
    /**
     * Récupère et déchiffre le secret d'un utilisateur.
     */
    public function getUserSecret(string $userId): ?string;
    
    /**
     * Révoque le 2FA d'un utilisateur (supprime secret).
     */
    public function revokeUserSecret(string $userId): bool;
    
    /**
     * Génère des codes de backup (8 codes de 8 chiffres).
     * Stockés hashés (bcrypt).
     */
    public function generateBackupCodes(string $userId): array;
    
    /**
     * Valide un code de backup (single-use).
     */
    public function validateBackupCode(string $userId, string $code): bool;
}
```

**Implémentation TOTP (RFC 6238)** sans dépendance Composer :
```php
public function generateToken(string $secret, ?int $timestamp = null): string
{
    $timestamp = $timestamp ?? time();
    $counter = intdiv($timestamp, self::PERIOD);
    $binaryCounter = pack('J', $counter); // 64-bit big-endian
    
    $secretBytes = $this->base32Decode($secret);
    $hash = hash_hmac(self::ALGORITHM, $binaryCounter, $secretBytes, true);
    
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);
    
    return str_pad((string)($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
}
```

### Tâche 2 — Endpoints 2FA

Créer `backend/api/auth.php` nouveaux endpoints :
- `POST /api/auth/2fa/setup` — génère secret + QR code (retour SVG)
- `POST /api/auth/2fa/enable` — active 2FA après validation d'un code
- `POST /api/auth/2fa/validate` — valide un code pendant login
- `POST /api/auth/2fa/disable` — désactive (nécessite mot de passe)
- `POST /api/auth/2fa/backup-codes` — génère nouveaux codes

Modifier le flow de login :
1. Email + password valides
2. Si user a `totp_enabled=true`, demander code TOTP
3. Valider TOTP → login réussi
4. Sinon → 401

### Tâche 3 — UI Configuration 2FA

Créer page `/frontend/admin/settings.html` (ou section dans page settings existante) :

```jsx
function TwoFactorSetup({ user }) {
  const [step, setStep] = React.useState('start'); // start | qr | verify | backup
  const [qrSvg, setQrSvg] = React.useState('');
  const [secret, setSecret] = React.useState('');
  const [code, setCode] = React.useState('');
  const [backupCodes, setBackupCodes] = React.useState([]);
  
  async function startSetup() {
    const res = await fetch('/api/auth/2fa/setup', {
      method: 'POST',
      headers: { 'X-CSRF-Token': getCsrfToken() },
      credentials: 'include'
    });
    const data = await res.json();
    setQrSvg(data.qr_code_svg);
    setSecret(data.secret);
    setStep('qr');
  }
  
  async function verifyCode() {
    const res = await fetch('/api/auth/2fa/enable', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
      credentials: 'include',
      body: JSON.stringify({ code })
    });
    if (res.ok) {
      const data = await res.json();
      setBackupCodes(data.backup_codes);
      setStep('backup');
    } else {
      alert('Code invalide');
    }
  }
  
  return (
    <div className="two-factor-setup">
      <h2>🔐 Authentification à deux facteurs (2FA)</h2>
      
      {user.totp_enabled ? (
        <div>
          <p>✅ 2FA activé sur votre compte</p>
          <button onClick={disable2FA}>Désactiver</button>
          <button onClick={regenerateBackupCodes}>Régénérer codes de backup</button>
        </div>
      ) : (
        <>
          {step === 'start' && (
            <>
              <p>Protégez votre compte avec un code à 6 chiffres généré par une app mobile.</p>
              <p>Apps compatibles : Google Authenticator, Authy, Microsoft Authenticator, 1Password</p>
              <button onClick={startSetup}>Activer 2FA</button>
            </>
          )}
          
          {step === 'qr' && (
            <>
              <h3>Étape 1 : Scannez ce QR code</h3>
              <div dangerouslySetInnerHTML={{__html: qrSvg}} />
              <p>Ou entrez ce code manuellement : <code>{secret}</code></p>
              
              <h3>Étape 2 : Entrez le code à 6 chiffres</h3>
              <input type="text" maxLength="6" pattern="[0-9]{6}" 
                     value={code} onChange={e => setCode(e.target.value)} />
              <button onClick={verifyCode}>Vérifier et activer</button>
            </>
          )}
          
          {step === 'backup' && (
            <>
              <h3>✅ 2FA activé !</h3>
              <p>⚠️ <strong>Conservez ces codes de backup en lieu sûr.</strong></p>
              <p>Ils vous permettront de vous connecter si vous perdez votre téléphone.</p>
              <div className="backup-codes">
                {backupCodes.map(c => <code key={c}>{c}</code>)}
              </div>
              <button onClick={() => window.print()}>Imprimer</button>
              <button onClick={() => setStep('start')}>J'ai conservé les codes</button>
            </>
          )}
        </>
      )}
    </div>
  );
}
```

### Tâche 4 — AuditLogger complet

Implémenter `backend/lib/AuditLogger.php` :

```php
class AuditLogger
{
    private string $auditDir;
    private Logger $logger;
    
    public function __construct(string $auditDir, Logger $logger)
    {
        $this->auditDir = $auditDir;
        $this->logger = $logger;
    }
    
    /**
     * Log une action auditée.
     *
     * @param string $action    Ex: 'exam.create', 'user.delete', 'login.success'
     * @param string $userId    Utilisateur qui fait l'action
     * @param string $subjectType Ex: 'exam', 'user', 'workspace'
     * @param string $subjectId   ID de l'objet affecté
     * @param array  $context   Données additionnelles
     */
    public function log(string $action, string $userId, string $subjectType = '', string $subjectId = '', array $context = []): void;
    
    /**
     * Liste les entrées d'audit avec filtres.
     *
     * @param array $filters ['user_id' => ..., 'action' => ..., 'from' => ts, 'to' => ts]
     */
    public function query(array $filters = [], int $limit = 100, int $offset = 0): array;
    
    /**
     * Statistiques d'audit.
     */
    public function stats(?string $userId = null, ?int $fromTs = null): array;
    
    /**
     * Export des logs en CSV pour compliance.
     */
    public function exportCsv(array $filters = []): string;
}
```

Structure d'une entrée JSON :
```json
{
  "id": "AUD-XXXX-YYYY",
  "timestamp": "2026-05-01T14:30:25Z",
  "action": "exam.create",
  "user_id": "USR-123",
  "user_email": "prof1@mail.com",
  "user_role": "enseignant",
  "subject_type": "exam",
  "subject_id": "EXM-ABC-DEF",
  "ip_address": "1.2.3.4",
  "user_agent": "Mozilla/5.0...",
  "workspace_id": "WKS-XXXX",
  "context": {
    "exam_title": "...",
    "questions_count": 10
  }
}
```

### Tâche 5 — Middleware auto-audit

Dans `backend/public/index.php`, après le dispatch, auto-logger les actions sensibles :

```php
// Après dispatch API
$sensitiveActions = [
    'POST:/api/examens'       => 'exam.create',
    'DELETE:/api/examens/*'   => 'exam.delete',
    'POST:/api/examens/*/publish' => 'exam.publish',
    'POST:/api/comptes'       => 'user.create',
    'DELETE:/api/comptes/*'   => 'user.delete',
    'POST:/api/auth/login'    => 'login.success',
    'POST:/api/backups'       => 'backup.create',
    'DELETE:/api/passages/*/invalidate' => 'passage.invalidate',
    // ...
];
```

### Tâche 6 — Page admin consultation audit

Créer `/frontend/admin/audit.html` :

```jsx
function AuditViewer() {
  const [entries, setEntries] = React.useState([]);
  const [filters, setFilters] = React.useState({});
  const [stats, setStats] = React.useState(null);
  
  async function loadEntries() {
    const params = new URLSearchParams(filters);
    const res = await fetch(`/api/audit?${params}`, { credentials: 'include' });
    setEntries((await res.json()).data);
  }
  
  return (
    <div>
      <h1>🔍 Audit Log</h1>
      
      <FilterBar filters={filters} onChange={setFilters} />
      
      <StatsPanel stats={stats} />
      
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Utilisateur</th>
            <th>Action</th>
            <th>Sujet</th>
            <th>IP</th>
            <th>Détails</th>
          </tr>
        </thead>
        <tbody>
          {entries.map(e => (
            <tr key={e.id}>
              <td>{formatDate(e.timestamp)}</td>
              <td>{e.user_email} ({e.user_role})</td>
              <td><span className={`action-${e.action.split('.')[0]}`}>{e.action}</span></td>
              <td>{e.subject_type} / {e.subject_id}</td>
              <td>{e.ip_address}</td>
              <td><button onClick={() => showContext(e.context)}>📋</button></td>
            </tr>
          ))}
        </tbody>
      </table>
      
      <button onClick={exportCsv}>📤 Export CSV</button>
    </div>
  );
}
```

### Tâche 7 — AntiCheatAnalyzer

Implémenter `backend/lib/AntiCheatAnalyzer.php` :

```php
class AntiCheatAnalyzer
{
    /**
     * Analyse un passage et retourne un score de confiance 0-1.
     *
     * Facteurs considérés:
     * - Nombre de focus loss (blur) — pondéré par durée
     * - Nombre de copy/paste détectés
     * - DevTools ouvert ?
     * - Fingerprint navigateur stable ?
     * - Vitesse de réponse anormale (trop rapide = guess, trop lent = recherche)
     * - Patterns de réponses (all A, random)
     *
     * @return array ['score' => float, 'signals' => [...], 'risk_level' => 'low|medium|high']
     */
    public function analyzePassage(array $passage): array;
    
    /**
     * Détecte les patterns suspects dans les réponses.
     */
    private function detectAnswerPatterns(array $answers): array;
    
    /**
     * Analyse la vitesse de réponse (outliers).
     */
    private function analyzeResponseTimes(array $answers): array;
}
```

Score calculé :
- Base = 1.0
- −0.1 par focus_loss > 5s
- −0.2 par copy_paste détecté
- −0.3 si devtools opened
- −0.5 si fingerprint changed pendant passage
- −0.2 si patterns suspects (toutes mêmes lettres)
- Clamp [0, 1]

### Tâche 8 — Tests

Créer tests :
- `test_totp_manager.php` : generate/validate, drift, backup codes (15 tests)
- `test_audit_logger.php` : log, query, filters, export (10 tests)
- `test_anticheat_analyzer.php` : divers scénarios (15 tests)

## ✅ CRITÈRES D'ACCEPTATION

- [ ] TOTP fonctionne avec Google Authenticator réel
- [ ] Backup codes fonctionnels
- [ ] Audit log capture toutes les actions sensibles
- [ ] Page admin audit avec filtres
- [ ] Score anti-cheat calculé sur chaque passage
- [ ] Tests > 90% coverage pour nouvelles classes

## 📝 COMMITS ATTENDUS

- `feat(auth): add TotpManager with RFC 6238`
- `feat(auth): add 2FA setup UI`
- `feat(auth): integrate 2FA in login flow`
- `feat(audit): add AuditLogger with JSON persistence`
- `feat(audit): add auto-audit middleware`
- `feat(audit): add admin audit viewer`
- `feat(security): implement AntiCheatAnalyzer`
- `test(security): add 40+ security tests`

## 🚀 FINALISATION

Commit partiel de la Phase 5 (pas de tag, tag à la fin de P5 complète).
```

---

## Prompt Phase 5B — Multi-tenant Workspaces + SSO

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 5B : MULTI-TENANT + SSO

Phase 5A terminée (Sécurité). Tu vas maintenant transformer Certio en vraie plateforme SaaS multi-écoles.

## 🎯 Objectif Phase 5B (2 jours)

1. **Multi-tenant Workspaces** avec isolation stricte
2. **SSO Google OAuth 2.0**
3. **SSO Microsoft OAuth 2.0**
4. Branding par workspace

---

## 📋 TÂCHES DÉTAILLÉES

### Tâche 1 — WorkspaceManager complet

Implémenter `backend/lib/WorkspaceManager.php` :

```php
class WorkspaceManager
{
    private FileStorage $storage;
    private Logger $logger;
    private AuditLogger $audit;
    
    public function create(array $data): array;
    public function get(string $id): ?array;
    public function getBySlug(string $slug): ?array;
    public function list(array $filters = []): array;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function suspend(string $id): bool;
    public function reactivate(string $id): bool;
    
    /**
     * Vérifie si un user appartient au workspace.
     */
    public function hasUser(string $workspaceId, string $userId): bool;
    
    /**
     * Ajoute un user (admin ou membre) au workspace.
     */
    public function addUser(string $workspaceId, string $userId, string $role = 'member'): bool;
    public function removeUser(string $workspaceId, string $userId): bool;
    
    /**
     * Vérifie les limites du plan.
     */
    public function checkLimit(string $workspaceId, string $resource): array;
    // ['within_limit' => bool, 'current' => int, 'max' => int]
    
    /**
     * Stats workspace (nb users, nb examens, nb passages, storage used).
     */
    public function getStats(string $workspaceId): array;
}
```

### Tâche 2 — Isolation des données par workspace

Ajouter un champ `workspace_id` sur tous les schémas :
- Examens : `examen.workspace_id`
- Questions : `question.workspace_id`
- Passages : `passage.workspace_id`
- Comptes utilisateurs : `user.workspace_id` ou `user.workspaces` (array pour super-admin)

**Middleware d'isolation** : injecter automatiquement le scope workspace dans toutes les requêtes :

```php
// Dans backend/public/index.php, après auth
$currentUser = Auth::getCurrentUser();
if ($currentUser['role'] !== 'super_admin') {
    // Force workspace scope
    $_REQUEST['_workspace_id'] = $currentUser['workspace_id'];
}
```

Modifier `ExamenManager`, `PassageManager`, `BanqueManager` :
- Toutes les requêtes `list()` filtrent par `workspace_id`
- `get()` vérifie que l'objet appartient au workspace de l'user
- `create()` stampe automatiquement `workspace_id` depuis l'user

### Tâche 3 — Page admin Workspaces

Créer `/frontend/admin/workspaces.html` (super-admin only) :
- Liste des workspaces
- Créer workspace (nom, slug, plan, admin email)
- Édition (limites, branding, settings)
- Stats par workspace
- Suspendre / réactiver

### Tâche 4 — SsoManager Google OAuth 2.0

Implémenter `backend/lib/SsoManager.php` :

```php
class SsoManager
{
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';
    
    /**
     * Génère l'URL d'autorisation Google OAuth.
     */
    public function getGoogleAuthUrl(string $redirectUri, string $state): string;
    
    /**
     * Échange le code d'autorisation contre un access token.
     */
    public function exchangeGoogleCode(string $code, string $redirectUri): array;
    
    /**
     * Récupère les infos utilisateur depuis Google.
     */
    public function getGoogleUserInfo(string $accessToken): array;
    
    /**
     * Similaire pour Microsoft.
     */
    public function getMicrosoftAuthUrl(string $redirectUri, string $state): string;
    public function exchangeMicrosoftCode(string $code, string $redirectUri): array;
    public function getMicrosoftUserInfo(string $accessToken): array;
    
    /**
     * Associe ou crée un compte utilisateur depuis infos SSO.
     */
    public function linkOrCreateAccount(array $userInfo, string $provider, string $workspaceSlug): array;
}
```

**Config OAuth** dans `backend/config/sso.php` (à .gitignorer) :
```php
return [
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID'),
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => 'https://certio.app/api/auth/google/callback',
    ],
    'microsoft' => [
        'client_id' => getenv('MICROSOFT_CLIENT_ID'),
        'client_secret' => getenv('MICROSOFT_CLIENT_SECRET'),
        'redirect_uri' => 'https://certio.app/api/auth/microsoft/callback',
        'tenant' => 'common',
    ],
];
```

### Tâche 5 — Endpoints SSO

Dans `backend/api/auth.php` :
- `GET /api/auth/google/init` — redirect vers Google auth
- `GET /api/auth/google/callback` — callback après auth
- `GET /api/auth/microsoft/init`
- `GET /api/auth/microsoft/callback`

Flow :
1. User clique "Sign in with Google"
2. Redirect vers Google consent
3. Google redirige vers callback avec `code`
4. Backend échange code → token → user info
5. Si email existe dans DB : login + créer session
6. Si nouveau : créer compte + associer workspace (selon politique)

### Tâche 6 — UI Login avec SSO

Modifier page login :

```jsx
function LoginPage() {
  return (
    <div className="login">
      <h1>Se connecter à Certio</h1>
      
      <form>
        <input type="email" placeholder="Email" />
        <input type="password" placeholder="Mot de passe" />
        <button type="submit">Se connecter</button>
      </form>
      
      <div className="sso-divider">ou</div>
      
      <div className="sso-buttons">
        <a href="/api/auth/google/init" className="btn btn-google">
          <img src="/assets/img/google.svg" /> Continuer avec Google
        </a>
        <a href="/api/auth/microsoft/init" className="btn btn-microsoft">
          <img src="/assets/img/microsoft.svg" /> Continuer avec Microsoft
        </a>
      </div>
    </div>
  );
}
```

### Tâche 7 — Migration : tous les users v1 → workspace DEFAULT

Modifier le script `scripts/migrate-v1-to-v2.php` :
- Tous les comptes user v1 → `workspace_id = "WKS-DEFAULT"`
- Tous les examens v1 → `workspace_id = "WKS-DEFAULT"`
- Tous les passages v1 → `workspace_id = "WKS-DEFAULT"`
- Workspace DEFAULT créé en P0 avec limites illimitées

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Isolation stricte : un prof de WKS-A ne voit pas examens WKS-B
- [ ] SSO Google fonctionne end-to-end
- [ ] SSO Microsoft fonctionne end-to-end
- [ ] Super-admin peut créer/gérer workspaces
- [ ] Migration v1 → workspace DEFAULT sans perte

## 📝 COMMITS ATTENDUS

- `feat(workspace): add WorkspaceManager with CRUD`
- `feat(workspace): scope data by workspace in all managers`
- `feat(workspace): add workspace admin UI`
- `feat(sso): add SsoManager for Google OAuth`
- `feat(sso): add Microsoft OAuth support`
- `feat(sso): add login buttons UI`
- `feat(migration): migrate users/exams to default workspace`
```

---

## Prompt Phase 5C — Intégrations LMS

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 5C : INTÉGRATIONS LMS

Phases 5A (Sécurité) et 5B (Multi-tenant) terminées.

## 🎯 Objectif Phase 5C (2 jours)

1. **Import** questions depuis Moodle XML, Word (.docx), Excel (.xlsx)
2. **Export** SCORM 1.2, SCORM 2004, xAPI (Tin Can)
3. **LTI 1.3** endpoint (minimal compatible)
4. **API REST publique** documentée (OpenAPI 3.1 + Swagger UI)

---

## 📋 TÂCHES

### Tâche 1 — ImportManager : Moodle XML

Implémenter `backend/lib/ImportManager.php` :

```php
class ImportManager
{
    /**
     * Parse un fichier Moodle XML Quiz export.
     *
     * Format Moodle:
     * <quiz>
     *   <question type="multichoice">
     *     <name><text>Q1</text></name>
     *     <questiontext format="html"><text>Statement</text></questiontext>
     *     <single>true</single>
     *     <answer fraction="100"><text>Correct</text></answer>
     *     <answer fraction="0"><text>Wrong</text></answer>
     *     ...
     *   </question>
     * </quiz>
     *
     * @return array Liste de questions au format Certio v2
     */
    public function importMoodleXml(string $xmlContent, string $workspaceId, string $creatorId): array;
    
    /**
     * Parse un fichier Word .docx au format Aiken :
     *
     * Q1. Question text?
     * A. Option 1
     * B. Option 2 *
     * C. Option 3
     * D. Option 4
     * ANSWER: B
     */
    public function importWordAiken(string $docxContent, string $workspaceId, string $creatorId): array;
    
    /**
     * Parse un fichier Excel .xlsx avec format standardisé.
     * Template Certio à fournir.
     */
    public function importExcel(string $xlsxPath, string $workspaceId, string $creatorId): array;
    
    /**
     * Valide les questions importées avant persistance.
     */
    public function validateImported(array $questions): array;
    
    /**
     * Persiste en batch les questions importées.
     */
    public function persistImported(array $questions, string $workspaceId): array;
}
```

### Tâche 2 — ExportManager : SCORM

```php
class ExportManager
{
    /**
     * Génère un package SCORM 1.2 (.zip) depuis un examen.
     *
     * Structure:
     * package.zip
     * ├── imsmanifest.xml
     * ├── adlcp_rootv1p2.xsd
     * ├── imscp_rootv1p1p2.xsd
     * ├── imsmd_rootv1p2p1.xsd
     * ├── index.html (SCO entry point)
     * └── resources/
     *     ├── questions.json
     *     ├── scorm-api.js
     *     └── ...
     *
     * @return string Path to generated .zip file
     */
    public function exportScorm12(string $examenId): string;
    
    /**
     * SCORM 2004 (plus récent, preferred).
     */
    public function exportScorm2004(string $examenId): string;
    
    /**
     * xAPI statements pour un passage.
     */
    public function generateXApiStatements(string $passageId): array;
    
    /**
     * Endpoint xAPI pour réception de statements externes.
     */
    public function receiveXApiStatement(array $statement): bool;
}
```

Le SCORM généré est un mini-package HTML/JS qui :
- Affiche les questions
- Communique avec le LMS parent via SCORM API
- Envoie score final à la fin

### Tâche 3 — LTI 1.3 (minimal)

LTI 1.3 implémentation minimaliste (launch seulement) :

```php
// backend/api/lti.php

// POST /api/lti/launch — reçoit JWT LTI et lance un examen
// GET  /api/lti/jwks — public key pour verification
// GET  /api/lti/tool-config.json — config JSON pour LMS
```

### Tâche 4 — OpenAPI 3.1 + Swagger UI

Générer `api-docs.json` (OpenAPI 3.1) depuis annotations PHP :

Option 1 : manuel (plus rapide, adapté au projet)
Option 2 : via composer swagger-php (si on accepte Composer)

Recommandation : **manuel** pour garder zero-dependency.

Créer `backend/config/openapi.json` (~800 lignes, décrit toutes les routes).

Servir Swagger UI à `/api-docs` :

```html
<!-- frontend/api-docs.html -->
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script>
    SwaggerUIBundle({
      url: '/backend/config/openapi.json',
      dom_id: '#swagger-ui',
    });
  </script>
</body>
</html>
```

### Tâche 5 — UI Import/Export

Dans la banque de questions prof, ajouter :
- Bouton "📥 Importer" → modal avec :
  - Sélecteur source (Moodle XML / Word / Excel)
  - Upload fichier
  - Preview des questions parsées
  - Validation + choix du module/chapitre cible
  - Import batch

Dans la page examen :
- Bouton "📤 Exporter" → choix format (PDF / SCORM 1.2 / SCORM 2004 / xAPI JSON)

### Tâche 6 — Templates Excel

Créer `templates/import_questions_template.xlsx` avec colonnes :
- `type` (true_false / mcq_single_4 / mcq_single_5 / mcq_multiple_4 / ...)
- `statement` 
- `option_A`, `option_B`, ..., `option_J`
- `correct_A`, `correct_B`, ... (Y/N)
- `explanation`
- `difficulty` (easy / medium / hard)
- `tags` (comma-separated)
- `module`, `chapitre`, `theme`

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Import Moodle XML d'un fichier sample fonctionne
- [ ] Import Word Aiken fonctionne
- [ ] Import Excel avec template fonctionne
- [ ] Export SCORM 1.2 importable dans Moodle test
- [ ] Export SCORM 2004 importable
- [ ] Swagger UI accessible et fonctionnel
- [ ] LTI launch basique OK (test dans Moodle)

## 📝 COMMITS ATTENDUS

- `feat(import): add Moodle XML parser`
- `feat(import): add Word Aiken parser`
- `feat(import): add Excel import`
- `feat(import): add import UI with preview`
- `feat(export): add SCORM 1.2 generator`
- `feat(export): add SCORM 2004 generator`
- `feat(export): add xAPI statements`
- `feat(lti): add LTI 1.3 endpoints`
- `docs(api): add OpenAPI 3.1 + Swagger UI`
```

---

## Prompt Phase 5D — Accessibilité + i18n + PWA

### 🎯 À copier-coller (1 jour)

```
# CONTEXTE CERTIO v2.0 — PHASE 5D : A11y + i18n + PWA

Phases 5A, 5B, 5C terminées. Dernière phase "améliorations cross-cutting".

## 🎯 Objectif Phase 5D (1 jour)

1. **Accessibilité WCAG AA complète** (axe-core validé)
2. **i18n FR/EN** complet (toutes les strings externalisées)
3. **PWA fonctionnelle** (installation + offline mode pour passages)

---

## 📋 TÂCHES

### Tâche 1 — Audit axe-core

Dans Chrome DevTools, onglet Lighthouse → Accessibility audit.

Corriger toutes les violations :
- `<img>` sans `alt`
- Contrastes insuffisants
- Ordre tab désordonné
- aria-labels manquants
- Form inputs sans label
- Buttons avec icône seule sans aria-label
- `<table>` sans `scope` headers
- Liens discordants (ex: "cliquez ici")
- `role="..."` manquants où nécessaire
- `<main>`, `<nav>`, `<aside>` sémantique

### Tâche 2 — Navigation clavier 100%

Vérifier que toutes les actions sont accessibles au clavier :
- Tab order logique
- Focus visible (outline CSS)
- Raccourcis clavier pour actions fréquentes :
  - `Ctrl+S` : sauvegarder
  - `Escape` : fermer modal
  - `Arrow keys` : navigation questions pendant passage
  - `Enter` : valider
- Skip links : "Aller au contenu principal"

### Tâche 3 — i18n : externaliser toutes les strings

Parcourir tout le code React et remplacer strings hardcodées par `I18n.t('key')`.

Structure recommandée :
```
frontend/assets/i18n/
├── fr.json (principal)
├── en.json
└── keys.md (doc des clés)
```

Exemple transformation :

```jsx
// AVANT
<button>Enregistrer</button>
<h1>Mes examens</h1>
<p>Vous avez {count} examens en cours.</p>

// APRÈS
<button>{I18n.t('common.save')}</button>
<h1>{I18n.t('exam.my_exams')}</h1>
<p>{I18n.t('exam.in_progress_count', { count })}</p>
```

Fichier FR complet (~500 clés) dans `frontend/assets/i18n/fr.json`.
Traduire EN en miroir dans `en.json`.

### Tâche 4 — Sélecteur de langue

Ajouter dans le header de l'app :

```jsx
function LanguageSelector() {
  const [currentLang, setCurrentLang] = React.useState(I18n.getLocale());
  
  function switchLang(lang) {
    I18n.loadLocale(lang).then(() => {
      setCurrentLang(lang);
      window.location.reload(); // Force re-render
    });
  }
  
  return (
    <div className="lang-selector">
      <button className={currentLang === 'fr' ? 'active' : ''} onClick={() => switchLang('fr')}>🇫🇷 FR</button>
      <button className={currentLang === 'en' ? 'active' : ''} onClick={() => switchLang('en')}>🇬🇧 EN</button>
    </div>
  );
}
```

### Tâche 5 — PWA Service Worker complet

Étendre `service-worker.js` créé en P0 :

```javascript
const CACHE_NAME = 'certio-v2.0.0';
const STATIC_CACHE = 'certio-static-v2';
const API_CACHE = 'certio-api-v2';

const STATIC_URLS = [
  '/',
  '/assets/css/main.css',
  '/assets/branding.js',
  '/assets/i18n/fr.json',
  '/assets/i18n/en.json',
  '/assets/img/logo.svg',
  '/assets/img/icons/icon-192.png',
  '/assets/img/icons/icon-512.png',
  'https://unpkg.com/react@18/umd/react.production.min.js',
  'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
];

// Install: cache statique
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then(cache => cache.addAll(STATIC_URLS))
  );
  self.skipWaiting();
});

// Activate: nettoyage anciens caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => 
      Promise.all(keys
        .filter(k => k.startsWith('certio-') && k !== STATIC_CACHE && k !== API_CACHE)
        .map(k => caches.delete(k))
      )
    )
  );
  self.clients.claim();
});

// Fetch: Cache First pour static, Network First pour API
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  if (STATIC_URLS.some(s => event.request.url.endsWith(s))) {
    // Cache first pour statique
    event.respondWith(
      caches.match(event.request).then(r => r || fetch(event.request))
    );
  } else if (url.pathname.startsWith('/api/passages/answer')) {
    // Network first, fallback to IndexedDB queue (offline save)
    event.respondWith(
      fetch(event.request).catch(() => saveOfflineAnswer(event.request))
    );
  }
});

async function saveOfflineAnswer(request) {
  // Queue pour retry quand online
  const body = await request.text();
  const db = await openIndexedDB();
  await db.add('offline_answers', { url: request.url, body, timestamp: Date.now() });
  return new Response(JSON.stringify({ ok: true, offline: true }), {
    headers: { 'Content-Type': 'application/json' }
  });
}

// Sync en background quand online retour
self.addEventListener('sync', event => {
  if (event.tag === 'sync-offline-answers') {
    event.waitUntil(syncOfflineAnswers());
  }
});
```

### Tâche 6 — Installation PWA

Prompt d'installation dans header :

```jsx
function InstallPrompt() {
  const [deferredPrompt, setDeferredPrompt] = React.useState(null);
  const [installed, setInstalled] = React.useState(false);
  
  React.useEffect(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      setDeferredPrompt(e);
    });
    window.addEventListener('appinstalled', () => setInstalled(true));
  }, []);
  
  if (!deferredPrompt || installed) return null;
  
  return (
    <button onClick={async () => {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      if (outcome === 'accepted') setInstalled(true);
      setDeferredPrompt(null);
    }}>
      📱 Installer Certio
    </button>
  );
}
```

### Tâche 7 — Responsive mobile

Tester sur 320px, 768px, 1024px, 1920px.

Corriger les layouts cassés :
- Sidebar admin devient menu hamburger sur mobile
- Tableaux scrollables horizontalement
- Formulaires verticaux sur mobile
- Fontes ajustées

## ✅ CRITÈRES D'ACCEPTATION

- [ ] axe-core DevTools : 0 erreur critique
- [ ] Lighthouse Accessibility ≥ 95
- [ ] Lighthouse PWA = 100
- [ ] Navigation clavier 100% fonctionnelle
- [ ] Toutes les strings externalisées (i18n)
- [ ] Traduction EN complète
- [ ] Installation PWA sur mobile testée
- [ ] Mode hors-ligne passage : réponses sauvées localement
- [ ] Responsive 320px-1920px OK

## 📝 COMMITS ATTENDUS

- `a11y(wcag): fix axe-core violations`
- `a11y(keyboard): ensure 100% keyboard navigation`
- `feat(i18n): externalize all UI strings`
- `feat(i18n): add complete EN translation`
- `feat(i18n): add language selector`
- `feat(pwa): enhance service worker with offline support`
- `feat(pwa): add install prompt`
- `style(responsive): fix mobile layouts`
```

---

## Prompt Phase 5E — Banque communautaire

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 5E : BANQUE COMMUNAUTAIRE

Dernière sous-phase de P5 ! Terminera la Phase 5 et déclenchera le tag v2.0.0-rc.1.

## 🎯 Objectif Phase 5E (2 jours)

Implémenter la **banque de questions partagée** inter-écoles :
- 3 niveaux de visibilité (privé / workspace / communauté)
- Fork de questions communautaires
- Système de votes et modération
- Seed initial 100+ questions

---

## 📋 TÂCHES

### Tâche 1 — CommunityBankManager

```php
class CommunityBankManager
{
    private FileStorage $storage;
    private Logger $logger;
    private AuditLogger $audit;
    
    /**
     * Publie une question en communauté (action explicite du prof).
     *
     * @param string $questionId    Question à publier
     * @param string $userId        Auteur publiant
     * @param string $license       CC-BY | CC-BY-SA | CC-BY-NC | CC-0
     * @return array La community entry créée
     */
    public function publish(string $questionId, string $userId, string $license): array;
    
    /**
     * Retire une question de la communauté.
     */
    public function unpublish(string $communityId, string $userId): bool;
    
    /**
     * Fork : importe une question communautaire dans la banque perso du prof.
     * Garde l'attribution de l'auteur original.
     */
    public function fork(string $communityId, string $targetWorkspaceId, string $userId): array;
    
    /**
     * Vote (rating 1-5 étoiles).
     */
    public function rate(string $communityId, string $userId, int $stars, ?string $comment = null): bool;
    
    /**
     * Signaler une question inappropriée.
     */
    public function flag(string $communityId, string $userId, string $reason): bool;
    
    /**
     * Liste les questions communautaires avec filtres.
     */
    public function listPublic(array $filters = [], int $limit = 20, int $offset = 0): array;
    
    /**
     * Recherche full-text.
     */
    public function search(string $query, array $filters = []): array;
    
    /**
     * Modération : approuver/rejeter une question en pending.
     */
    public function moderate(string $communityId, string $decision, string $moderatorId, ?string $reason = null): bool;
    
    /**
     * Stats globales de la banque communautaire.
     */
    public function getGlobalStats(): array;
    
    /**
     * Top auteurs (les plus contributeurs).
     */
    public function getTopContributors(int $limit = 10): array;
}
```

### Tâche 2 — Schéma CommunityBank

```json
{
  "id": "CBK-XXXX-YYYY",
  "question_id": "QST-XXXX",
  "question_snapshot": { /* copie complète de la question au moment du publish */ },
  "original_workspace_id": "WKS-XXXX",
  "original_author_id": "USR-XXXX",
  "original_author_name": "Jean Dupont",
  "published_at": "2026-06-01T10:00:00Z",
  "license": "CC-BY-SA",
  "review_status": "pending|approved|rejected",
  "reviewed_by_user_id": null,
  "reviewed_at": null,
  "review_reason": null,
  "stats": {
    "view_count": 0,
    "fork_count": 0,
    "usage_count": 0,
    "success_rate_average": null,
    "avg_time_spent": null
  },
  "ratings": [
    { "user_id": "USR-ZZZZ", "stars": 5, "comment": "Excellente", "at": "2026-06-05" }
  ],
  "rating_average": 4.7,
  "rating_count": 12,
  "flags": [
    { "user_id": "USR-ABCD", "reason": "duplicate", "at": "2026-06-10" }
  ]
}
```

### Tâche 3 — API endpoints

`backend/api/community.php` :
- `GET /api/community/questions` — liste publique
- `GET /api/community/questions/{id}` — détail
- `POST /api/community/questions/{id}/publish` — publier (auteur only)
- `DELETE /api/community/questions/{id}/publish` — retirer
- `POST /api/community/questions/{id}/fork` — fork vers workspace cible
- `POST /api/community/questions/{id}/rate` — voter
- `POST /api/community/questions/{id}/flag` — signaler
- `POST /api/community/search` — recherche full-text
- `GET /api/community/stats` — stats globales
- `GET /api/community/top-contributors` — top auteurs
- `POST /api/community/moderate/{id}` — modération (super-admin only)
- `GET /api/community/pending` — questions en attente de modération

### Tâche 4 — UI publier une question

Dans l'éditeur de question, ajouter section :

```jsx
function PublishToCommunitySection({ question, onPublish }) {
  const [license, setLicense] = React.useState('CC-BY-SA');
  const [agreed, setAgreed] = React.useState(false);
  
  if (question.community?.published_at) {
    return (
      <div className="already-published">
        <p>✅ Question publiée le {formatDate(question.community.published_at)}</p>
        <p>👁️ Vues : {question.community.stats.view_count}</p>
        <p>🔀 Forks : {question.community.stats.fork_count}</p>
        <p>⭐ Rating : {question.community.rating_average || 'N/A'}</p>
        <button onClick={unpublish}>Retirer de la communauté</button>
      </div>
    );
  }
  
  return (
    <div className="publish-section">
      <h3>🌐 Partager avec la communauté</h3>
      <p>Publier cette question permettra aux autres profs de l'utiliser.</p>
      
      <label>
        Licence :
        <select value={license} onChange={e => setLicense(e.target.value)}>
          <option value="CC-BY">CC BY — Attribution</option>
          <option value="CC-BY-SA">CC BY-SA — Attribution + Partage identique (recommandé)</option>
          <option value="CC-BY-NC">CC BY-NC — Attribution + Pas commercial</option>
          <option value="CC-0">CC0 — Domaine public</option>
        </select>
      </label>
      
      <label>
        <input type="checkbox" checked={agreed} onChange={e => setAgreed(e.target.checked)} />
        J'accepte que cette question soit visible et réutilisable par d'autres profs selon la licence choisie.
      </label>
      
      <button disabled={!agreed} onClick={() => onPublish(license)}>
        🌐 Publier dans la communauté
      </button>
    </div>
  );
}
```

### Tâche 5 — UI Browse communauté

Page `/admin/community.html` (ou intégrée dans banque) :

```jsx
function CommunityBrowser() {
  const [questions, setQuestions] = React.useState([]);
  const [filters, setFilters] = React.useState({});
  const [sortBy, setSortBy] = React.useState('recent');
  
  return (
    <div className="community-browser">
      <h1>🌐 Banque communautaire Certio</h1>
      
      <FiltersBar filters={filters} onChange={setFilters} 
                  facets={['module', 'difficulty', 'license', 'language']} />
      
      <SortSelector value={sortBy} onChange={setSortBy}
                    options={['recent', 'rating', 'most_forked', 'most_used']} />
      
      <div className="question-grid">
        {questions.map(q => (
          <CommunityQuestionCard 
            key={q.id} 
            question={q}
            onFork={() => forkQuestion(q.id)}
            onRate={(stars) => rateQuestion(q.id, stars)}
            onFlag={() => flagQuestion(q.id)}
          />
        ))}
      </div>
    </div>
  );
}

function CommunityQuestionCard({ question, onFork, onRate, onFlag }) {
  return (
    <div className="community-card">
      <div className="header">
        <span className="type-badge">{question.question_snapshot.type}</span>
        <span className="license-badge">{question.license}</span>
      </div>
      <p className="statement">{question.question_snapshot.statement.substring(0, 150)}...</p>
      <div className="meta">
        <span>👤 {question.original_author_name}</span>
        <span>📅 {formatDate(question.published_at)}</span>
      </div>
      <div className="stats">
        <Rating value={question.rating_average} count={question.rating_count} />
        <span>🔀 {question.stats.fork_count} forks</span>
        <span>👁️ {question.stats.view_count}</span>
      </div>
      <div className="actions">
        <button onClick={onFork}>🔀 Fork vers ma banque</button>
        <RateButton onRate={onRate} />
        <button onClick={onFlag}>🚩</button>
      </div>
    </div>
  );
}
```

### Tâche 6 — Dashboard modération

Page `/admin/community-moderation.html` (super-admin only) :
- Liste questions pending
- Preview chaque question
- Boutons Approve / Reject avec raison
- Traitement rapide (keyboard shortcuts)

### Tâche 7 — Seed initial

Créer script `scripts/seed-community-questions.php` qui :
- Prend 100 questions du prof Mohamed (banque IPSSI existante)
- Les publie en communauté avec licence CC-BY-SA
- Variété de sujets : maths, informatique, logique, etc.

## ✅ CRITÈRES D'ACCEPTATION

- [ ] Prof peut publier/retirer ses questions
- [ ] Autre prof peut fork une question communautaire
- [ ] Système de votes fonctionne
- [ ] Modération super-admin OK
- [ ] Filtres et recherche OK
- [ ] 100+ questions seed disponibles
- [ ] Workspace peut opt-out (config `allow_community_publish: false`)

## 📝 COMMITS ATTENDUS

- `feat(community): add CommunityBankManager`
- `feat(community): add /api/community endpoints`
- `feat(community): add publish UI in question editor`
- `feat(community): add community browser UI`
- `feat(community): add moderation dashboard`
- `feat(community): add rating and flag system`
- `feat(community): add fork workflow`
- `chore(community): seed 100 initial questions`

## 🚀 FINALISATION PHASE 5 COMPLÈTE

Après Phase 5E :

1. Merge toutes les PR 5A-5E dans `develop`
2. Tag `v2.0.0-rc.1` (Release Candidate 1 !)
3. Deploy staging pour tests pilotes
4. Communication : "Certio v2.0 Beta pour testeurs pilotes"
```

---

## Prompt Phase 6 — Tests complets

### 🎯 À copier-coller (4 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 6 : TESTS COMPLETS

Phases 0-5 terminées. Toutes les features codées. Focus sur **qualité**.

## 🎯 Objectif Phase 6 (4 jours)

1. **Couverture tests ≥ 85%** global
2. **Tests E2E** des 5 workflows critiques
3. **Tests sécurité** OWASP Top 10
4. **Tests de charge** (100 req/s)
5. **Tests accessibilité** axe-core automatisés

---

## 📋 TÂCHES DÉTAILLÉES

### Jour 1 — Compléter couverture unitaire

**Audit coverage actuelle** :
```bash
php backend/tests/coverage.php > coverage_report.txt
```

Identifier managers < 85% et ajouter tests manquants :

Target par manager :
- CbmManager : 90%+
- WorkspaceManager : 90%+
- TotpManager : 95% (sécu critique)
- AuditLogger : 85%
- CommunityBankManager : 85%
- QuestionTypeResolver : 95%
- DocumentationManager : 85%
- ImportManager : 80% (fichiers test)
- ExportManager : 80%
- I18nManager : 95%

Pour chaque manager, ajouter :
- Happy path tests
- Edge cases (null, empty, invalid)
- Error handling
- Boundary conditions

### Jour 2 — Tests E2E

Créer `backend/tests/e2e/` avec 5 workflows :

#### E2E 1 : Flux complet examen CBM + multi-réponses
`test_e2e_cbm_workflow.php`
```php
// 1. Super-admin crée workspace
// 2. Crée admin workspace
// 3. Admin crée compte prof
// 4. Prof se connecte
// 5. Prof crée matrice CBM 5 niveaux
// 6. Prof crée 10 questions (5 types différents)
// 7. Prof crée examen avec CBM + shuffle
// 8. Prof publie examen
// 9. Étudiant accède par code
// 10. Étudiant répond aux 10 questions avec certitudes variées
// 11. Étudiant soumet
// 12. Prof consulte analytics
// 13. Prof exporte CSV
// 14. Prof clôture et archive
```

#### E2E 2 : Import Moodle + utilisation
`test_e2e_import_export.php`
- Import sample Moodle XML
- Validation questions importées
- Création examen avec ces questions
- Export SCORM
- Re-import dans Moodle de test (mock)

#### E2E 3 : SSO + Workspace creation
`test_e2e_sso_workspace.php`
- User clique "Sign in with Google"
- Mock callback Google
- Compte créé + associé à workspace DEFAULT
- Super-admin crée nouveau workspace
- Move user vers nouveau workspace

#### E2E 4 : Community publish + fork
`test_e2e_community.php`
- Prof A publie une question
- Modération approve
- Prof B (autre workspace) fork
- Prof B modifie et utilise
- Attribution auteur original conservée

#### E2E 5 : 2FA activation + utilisation
`test_e2e_2fa.php`
- Activation 2FA
- Simulate TOTP code valid
- Login avec 2FA
- Utilisation backup code
- Désactivation 2FA

### Jour 3 — Sécurité OWASP + Charge

#### OWASP Top 10 review

Créer `backend/tests/security/test_owasp_top10.php` :

1. **A01 Broken Access Control** : tester qu'un prof ne peut pas voir examens autre workspace
2. **A02 Cryptographic Failures** : vérifier bcrypt cost 12, HMAC signatures
3. **A03 Injection** : tester JSON injection, command injection
4. **A04 Insecure Design** : review rate limiting efficace
5. **A05 Security Misconfiguration** : .env pas versionné, permissions dossiers
6. **A06 Vulnerable Components** : audit CDN versions (React, KaTeX)
7. **A07 Auth Failures** : brute force protection, session fixation
8. **A08 Data Integrity** : HMAC signatures passages
9. **A09 Logging Failures** : audit log complet
10. **A10 SSRF** : URL validation dans SSO redirects

#### Tests de charge

```bash
# Apache Bench
ab -n 1000 -c 10 -H "Cookie: PHPSESSID=..." \
   -p answer_payload.json -T application/json \
   http://localhost/api/passages/answer

# 100 req/s pendant 60s
wrk -t4 -c100 -d60s http://localhost/api/examens
```

Target :
- 100 req/s tenus < 200ms p95
- 500 req/s tenus < 500ms p95
- Pas de memory leak après 1h

Documenter dans `docs/PERFORMANCE_BENCHMARKS.md`.

### Jour 4 — Accessibilité + Régression finale

#### axe-core automatisé

Créer `backend/tests/accessibility/test_a11y.php` :
- Puppeteer script qui visite chaque page
- Exécute axe-core
- Export rapport

Ou manuellement via Chrome DevTools Lighthouse sur chaque page clé.

#### Test lecteurs d'écran

- NVDA (Windows) ou VoiceOver (Mac) test sur :
  - Login
  - Création examen
  - Passage examen
  - Correction

#### Régression finale complète

```bash
php backend/tests/run_all.php --verbose
```

Target : 100% pass, **zéro régression v1**.

## ✅ CRITÈRES D'ACCEPTATION FINAUX

- [ ] Coverage global ≥ 85%
- [ ] 5 workflows E2E passent
- [ ] 0 vulnérabilité OWASP critique
- [ ] 100 req/s tenus < 200ms
- [ ] axe-core : 0 erreur bloquante
- [ ] Lighthouse : Performance ≥90, A11y ≥95, PWA=100
- [ ] NVDA/VoiceOver : navigation OK
- [ ] 630+ tests total passent (389 v1 + ~250 v2)
- [ ] Rapport de tests dans `docs/TEST_REPORT_V2.md`

## 📝 COMMITS ATTENDUS

- `test(cbm): improve CbmManager coverage to 92%`
- `test(workspace): add WorkspaceManager edge cases`
- `test(totp): cover all TOTP paths`
- `test(e2e): add 5 critical workflows`
- `test(security): add OWASP Top 10 checks`
- `test(perf): add load testing scripts`
- `test(a11y): automate axe-core`
- `docs(tests): add comprehensive test report`

## 🚀 FINALISATION PHASE 6

1. PR `feat/p6-tests-complets` → `develop`
2. Tag `v2.0.0-rc.2` (Release Candidate 2, quasi-final)
```

---

## Prompt Phase 7 — Migration & Déploiement

### 🎯 À copier-coller (2 jours)

```
# CONTEXTE CERTIO v2.0 — PHASE 7 : MIGRATION & DÉPLOIEMENT

🎉 DERNIÈRE PHASE ! Toutes les features OK, tous les tests OK. Il reste à déployer en production.

## 🎯 Objectif Phase 7 (2 jours)

1. Finaliser script de **migration v1 → v2** (dry-run, rollback)
2. Tester migration sur copie de prod
3. **Déployer v2.0.0** en production OVH
4. Migrer les données réelles
5. Release notes + communication

---

## 📋 TÂCHES DÉTAILLÉES

### Jour 1 — Script migration final

Finaliser `scripts/migrate-v1-to-v2.php` :

```php
<?php
declare(strict_types=1);

/**
 * Migration Certio v1 → v2
 * Usage: php scripts/migrate-v1-to-v2.php [--dry-run] [--rollback] [--verbose]
 */

// Options
$dryRun = in_array('--dry-run', $argv);
$rollback = in_array('--rollback', $argv);
$verbose = in_array('--verbose', $argv);

// 1. Backup complet
$backupPath = createFullBackup();
echo "✅ Backup créé : $backupPath\n";

if ($rollback) {
    echo "⏪ Rollback depuis $backupPath\n";
    restoreFromBackup($backupPath);
    exit(0);
}

// 2. Validation pré-migration
$preCheck = runPreMigrationChecks();
if (!$preCheck['ok']) {
    echo "❌ Pré-check échoué: " . json_encode($preCheck['errors']) . "\n";
    exit(1);
}

// 3. Migration des entités
$results = [];
$results['users']      = migrateUsers($dryRun);
$results['questions']  = migrateQuestions($dryRun);
$results['exams']      = migrateExams($dryRun);
$results['passages']   = migratePassages($dryRun);
$results['sessions']   = migrateSessions($dryRun);
$results['workspace']  = createDefaultWorkspace($dryRun);

// 4. Validation post-migration
if (!$dryRun) {
    $postCheck = runPostMigrationChecks();
    if (!$postCheck['ok']) {
        echo "❌ Post-check échoué, rollback automatique\n";
        restoreFromBackup($backupPath);
        exit(1);
    }
}

// 5. Rapport
printReport($results, $dryRun);

echo $dryRun ? "✅ DRY RUN COMPLET - aucune donnée modifiée\n" : "✅ MIGRATION RÉUSSIE\n";
```

**Fonctions clés** :
- `createFullBackup()` : tar.gz complet de data/ avec timestamp
- `restoreFromBackup($path)` : décompresse et remplace
- `runPreMigrationChecks()` : 
  - Vérifie permissions écriture
  - Vérifie espace disque disponible
  - Vérifie intégrité JSON
  - Vérifie pas de migration en cours
- `runPostMigrationChecks()` :
  - Tous les schémas v2 valides
  - Aucune donnée v1 orpheline
  - Test de lecture sur échantillons
  - Compteurs cohérents

**Test sur copie prod** :
```bash
# Télécharger backup prod
scp user@prod-server:/var/www/certio/data/backups/latest.tar.gz .

# Décompresser en local
tar -xzf latest.tar.gz -C /tmp/certio-test/

# Dry-run
php scripts/migrate-v1-to-v2.php --dry-run --verbose

# Run réel en local
php scripts/migrate-v1-to-v2.php --verbose

# Vérifier
php backend/tests/run_all.php
```

### Jour 2 — Déploiement production

#### Pré-déploiement

Créer `docs/DEPLOYMENT_CHECKLIST_V2.md` :
- [ ] `v2.0.0-rc.2` validé par tests
- [ ] Backup prod v1 complet fait
- [ ] Script migration testé sur copie prod
- [ ] Release notes rédigées
- [ ] Email utilisateurs préparé
- [ ] Fenêtre de maintenance communiquée
- [ ] Monitoring actif
- [ ] Rollback plan clair

#### Procédure de déploiement

```bash
# 1. SSH sur prod OVH
ssh user@certio.app

# 2. Mode maintenance
echo "MAINTENANCE" > /var/www/certio/MAINTENANCE

# 3. Backup prod
cd /var/www/certio
sudo bash scripts/backup.sh --keep=30

# 4. Pull du tag v2.0.0-rc.2
git fetch --tags
git checkout v2.0.0-rc.2

# 5. Run migration (dry-run puis réel)
sudo -u www-data php scripts/migrate-v1-to-v2.php --dry-run --verbose
# Si OK :
sudo -u www-data php scripts/migrate-v1-to-v2.php --verbose

# 6. Vérifications smoke tests
curl -I https://certio.app
curl -s https://certio.app/api/health | jq

# 7. Retirer mode maintenance
rm /var/www/certio/MAINTENANCE

# 8. Tag final
git tag -a v2.0.0 -m "Certio v2.0.0 - Production Release"
git push origin v2.0.0

# 9. Reload Nginx (si config changée)
sudo systemctl reload nginx
```

#### Post-déploiement

**Surveillance 48h** :
- Monitoring uptime (UptimeRobot)
- Logs en temps réel :
  ```bash
  tail -f /var/www/certio/data/logs/app.log
  tail -f /var/www/certio/data/logs/audit.log
  ```
- Slack/email alertes si erreur 5xx
- Dashboard analytics : vérifier trafic normal

**Communication** :
- Email aux utilisateurs v1 :
  ```
  Sujet : Certio v2.0 est disponible 🎉
  
  Bonjour,
  
  Votre plateforme IPSSI Examens devient Certio v2.0.
  
  Nouveautés :
  - Certainty-Based Marking (CBM) pour évaluation plus fine
  - 7 types de questions
  - Mode multi-écoles (Workspaces)
  - Banque de questions communautaire
  - Et bien plus...
  
  Vos comptes et examens existants sont migrés automatiquement.
  
  Explorez : https://certio.app
  Documentation : https://certio.app/docs
  
  Cordialement,
  L'équipe Certio
  ```
- Post blog / LinkedIn / réseaux sociaux
- Update README GitHub
- ProductHunt launch (optionnel)

#### Release notes CHANGELOG_V2.md

```markdown
# Certio v2.0.0 — Release Notes

🎉 **Major release** : IPSSI Examens devient Certio, plateforme multi-écoles avec CBM.

## ✨ Nouveautés

### 🎲 Certainty-Based Marking (CBM)
- Matrice de scoring 100% paramétrable par prof
- Presets réutilisables
- Import/export JSON
- Calibration étudiant (over/underconfidence)

### 🎯 7 types de questions
- Vrai/Faux
- QCM 4, 5, N options (radio)
- QCM 4, 5, N options (checkbox, multi-réponses)
- 3 modes scoring multi : all-or-nothing, proportional, proportional normalized

### 🏫 Multi-tenant
- Workspaces isolés par école
- SSO Google + Microsoft
- Branding par workspace

### 🔐 Sécurité
- 2FA TOTP (Google Authenticator)
- Audit log complet
- Anti-triche avec score de confiance

### 📤 Intégrations LMS
- Import Moodle XML
- Import Word (Aiken)
- Import Excel
- Export SCORM 1.2 et 2004
- Export xAPI
- LTI 1.3
- API REST publique (Swagger)

### 🌍 Accessibilité & i18n
- WCAG AA complet
- Traduction FR/EN
- Mode sombre
- PWA installable (mobile + desktop)
- Mode hors-ligne pour passages

### 🌐 Banque communautaire
- Publier questions avec licence CC
- Fork avec attribution
- Système de votes
- Modération

### 📚 Documentation interactive
- Intégrée dans l'admin
- Accès par rôle (RBAC)
- 27+ pages initiales

## 🔄 Migration

Migration automatique depuis v1.0.
- Tous les comptes, examens, questions migrés
- Assignés au workspace DEFAULT
- Historique complet préservé

## 📊 Stats

- +250 nouveaux tests (total 630+)
- Coverage global : 87%
- Lighthouse : Perf 92, A11y 97, PWA 100
- Nouveaux managers : 13
- Nouveaux endpoints : 6
- Nouveaux schémas : 5

## 🙏 Crédits

Développé par Mohamed EL AFRIT - Licence CC BY-NC-SA 4.0

## 🐛 Known issues

- [aucun bug bloquant connu au 2026-08-XX]

## ⏭️ Roadmap v2.1

- Génération IA de questions
- Mode adaptatif IRT
- App mobile native
- Monitoring Grafana
- Plans tarifaires (Stripe)
```

## ✅ CRITÈRES D'ACCEPTATION FINAUX

- [ ] Script migration dry-run OK sur prod copy
- [ ] Migration prod réelle sans erreur
- [ ] Tous les utilisateurs v1 peuvent se connecter
- [ ] Tous les examens v1 consultables
- [ ] Nouvelles features v2 fonctionnelles
- [ ] Monitoring vert 48h post-deploy
- [ ] Release notes publiées
- [ ] Email utilisateurs envoyé

## 📝 COMMITS ATTENDUS

- `feat(migration): finalize v1 to v2 migration script`
- `test(migration): validate on prod copy`
- `docs(changelog): add v2.0.0 release notes`
- `chore(release): tag v2.0.0`
- `deploy(prod): certio v2.0.0 released`

## 🎉 RELEASE CHECKLIST FINAL

- [ ] Tag `v2.0.0` créé et pushé
- [ ] GitHub release créée avec notes
- [ ] Prod déployée et fonctionnelle
- [ ] Email utilisateurs envoyé
- [ ] README à jour
- [ ] Social media posts
- [ ] Monitoring dashboard ok
- [ ] Documentation publiée

## 🎊 CÉLÉBRATION !

Bravo, tu as livré **Certio v2.0** ! 🚀

- 4 mois de dev (ou 36 jours actifs)
- 15 000+ lignes de code ajoutées
- 250+ tests ajoutés
- 13 nouveaux managers
- Passage de produit interne IPSSI à plateforme SaaS universelle

Prochaine étape : **v2.1** avec génération IA de questions, mode adaptatif IRT, et monétisation.
```

---

## Conclusion du Livrable 4

Tu as maintenant tous les prompts pour **construire Certio v2.0 de bout en bout** :

- **Livrable 3** (P0-P4) : 21 jours — features core
- **Livrable 4** (P5-P7) : 15 jours — améliorations + tests + déploiement

**Total estimé** : 36 jours avec assistance IA, 4 mois calendaires avec rythme 2j/semaine.

### 📦 Prochain livrable (5/5)

Le **dernier livrable** contiendra :
- 📦 **Kit d'inputs à préparer** (assets design, data test, credentials)
- ✅ **Checklists de validation** par phase
- 🎯 **Guide de suivi** du projet (KPIs journaliers, rituels)
- 🗂️ **Templates** prêts à remplir (ex: email annonce, release notes)

---

© 2026 Mohamed EL AFRIT — mohamed@elafrit.com  
Certio v2.0 — CC BY-NC-SA 4.0
