# Frontend React

Code client de la plateforme d'examens IPSSI.

## Structure

- **`assets/`** — Design system (tokens CSS, composants React, i18n)
- **`enseignant/`** — Pages enseignant (dashboard, banque, création, historique)
- **`etudiant/`** — Pages étudiant (accès, QCM, correction)
- **`commun/`** — Pages partagées (login, 404, mentions légales)

## Stack

- React 18 (via CDN + Babel in-browser, pas de build)
- KaTeX pour les formules mathématiques
- Recharts pour les graphiques
- Polices : Inter + Manrope + JetBrains Mono
- i18n custom (FR par défaut, extensible)

## Philosophie

- Pas de bundler (pas de Webpack/Vite) pour simplicité
- Composants React en JS classique (pas de TypeScript en v1)
- Design system centralisé dans `assets/components/`

*À compléter de P2 à P9*
