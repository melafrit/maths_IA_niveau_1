# Dossier `tests/` — CSV fictifs pour valider le dashboard

Ce répertoire contient **10 CSV d'évaluation factices** générés automatiquement par un script Python reproductible. Ces CSV permettent de tester rapidement et de manière déterministe les pages `correction_personnelle.html` et `dashboard_enseignant_2026.html`.

---

## 📋 Contenu

| Fichier | Rôle |
|---|---|
| `_generate_test_csvs.py` | Script Python qui régénère les 10 CSV |
| `MANIFESTE_TESTS.json` | Liste des 10 CSV avec valeurs attendues (note, durée, signature) |
| `IPSSI_ALLEGRE_Emma_*.csv` | Excellente étudiante — 18,48/20 |
| `IPSSI_BERNARD_Louis_*.csv` | Bon élève équilibré — 17,17/20 |
| `IPSSI_CHEVALIER_Nora_*.csv` | J2 fort / J1 faible — 12,83/20 |
| `IPSSI_DURAND_Paul_*.csv` | Moyen — 11,96/20 |
| `IPSSI_FABRE_Sarah_*.csv` | J1 fort / J2 faible — 10,65/20 |
| `IPSSI_GARNIER_Tom_*.csv` | Passable — 9,57/20 |
| `IPSSI_HENRY_Julie_*.csv` | En difficulté, 15 blanches — 5,65/20 |
| `IPSSI_ISAAC_Mathis_*.csv` | **Cas limite parfait** — 20,00/20 |
| `IPSSI_JACQUES_Lea_*.csv` | **Cas limite zéro** — 0,00/20 |
| `IPSSI_KAHN_Samuel_*.csv` | **CSV falsifié** — signature invalide ❌ |

---

## 🔄 Régénérer les CSV

Les CSV sont **déterministes** grâce à `random.seed(seed)` par profil. Ré-exécuter le script produit exactement les mêmes fichiers.

```bash
cd qcm_eval_J1_J2/tests
python3 _generate_test_csvs.py
```

Sortie attendue : 10 lignes `[OK] …csv` + un résumé statistique de la classe.

---

## 📊 Statistiques attendues de la classe

| Métrique | Valeur |
|---|:---:|
| Nombre d'étudiants | 10 |
| Moyenne | 11,65/20 |
| Médiane | 11,30/20 |
| Minimum | 0,00/20 |
| Maximum | 20,00/20 |
| Signatures valides | 9/10 |

---

## 🧪 Comment utiliser ces CSV

1. Lancer un serveur HTTP local :

   ```bash
   cd qcm_eval_J1_J2
   python3 -m http.server 8000
   ```

2. Ouvrir <http://localhost:8000/dashboard_enseignant_2026.html>
3. Glisser-déposer tous les CSV du dossier `tests/` (sélection multiple)
4. Vérifier que les statistiques attendues (voir ci-dessus) correspondent

Pour un protocole de tests complet, voir [`../TESTS.md`](../TESTS.md).

---

## ⚠️ Notes importantes

- Les emails des étudiants fictifs utilisent le domaine `@eleve.ipssi.fr` (inventé). Aucun email réel n'est utilisé.
- Les noms sont classiques mais sans lien avec des personnes réelles.
- Ces CSV ne doivent **jamais** être partagés comme exemples de vrais passages — ils ne correspondent à aucun étudiant.

---

*© 2025 Mohamed EL AFRIT — IPSSI — Licence CC BY-NC-SA 4.0*
