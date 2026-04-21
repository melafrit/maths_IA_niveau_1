# Tests automatisés

## Structure

- **`backend/`** — Tests unitaires PHPUnit (PHP)
- **`e2e/`** — Tests end-to-end Playwright (JavaScript)
- **`fixtures/`** — Jeux de données de test (CSV, JSON)

## Lancement

### Tests unitaires PHPUnit

```bash
cd examens
vendor/bin/phpunit tests/backend --colors=always
```

### Tests E2E Playwright

```bash
cd examens/tests/e2e
npm install
npx playwright install --with-deps
npx playwright test
```

### CI/CD

Les tests sont exécutés automatiquement via GitHub Actions à chaque push
sur `main` (cf. `.github/workflows/ci.yml`).

*À compléter en Phase P8*
