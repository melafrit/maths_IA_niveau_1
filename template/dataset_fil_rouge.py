# © 2025 Mohamed EL AFRIT — IPSSI
# Ce contenu est distribué sous licence Creative Commons BY-NC-SA 4.0
# https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr
#
# Dataset fil rouge — Mathématiques appliquées à l'Intelligence Artificielle
# Prédiction du salaire d'un développeur
# ============================================================================

import numpy as np
import matplotlib.pyplot as plt
import matplotlib
matplotlib.rcParams['figure.dpi'] = 150

# ============================================================================
# 1. CRÉATION DU DATASET (30 développeurs fictifs)
# ============================================================================
# Chaque ligne = un développeur, chaque colonne = une caractéristique (feature)
#
# Colonnes :
#   0 - experience      : années d'expérience (entier, 0 à 20)
#   1 - nb_langages     : langages de programmation maîtrisés (entier, 1 à 8)
#   2 - niveau_etudes   : 1=Bac, 2=Bac+2, 3=Bac+3, 4=Bac+5, 5=Doctorat
#   3 - taille_entreprise : nombre d'employés (entier, 10 à 5000)
#   4 - remote          : pourcentage de télétravail (0 à 100)
#   5 - salaire         : salaire annuel brut en k€ (float)
#   6 - salaire_eleve   : 1 si salaire > 45k€, 0 sinon (classification Jour 3)

# Noms des colonnes pour l'affichage
noms_colonnes = [
    "experience", "nb_langages", "niveau_etudes",
    "taille_entreprise", "remote", "salaire", "salaire_eleve"
]

# --- Données brutes (listes Python, sans dépendance) ---
# Format : [experience, nb_langages, niveau_etudes, taille_entreprise, remote, salaire]
donnees_brutes = [
    # === Profils juniors (0-3 ans) ===
    [0,  2, 3,  150,  80, 28.0],   # 0  - Stagiaire devenu junior, Bac+3, petite boîte
    [1,  1, 2,   50, 100, 26.5],   # 1  - Autodidacte Bac+2, micro-entreprise, full remote
    [1,  3, 4,  800,  40, 35.0],   # 2  - Sortie école ingé, bon package ESN
    [2,  2, 3,  200,  60, 32.0],   # 3  - Développeur web junior, PME
    [2,  4, 4, 3000,  20, 38.0],   # 4  - Junior dans un grand groupe, Bac+5
    [0,  1, 1,   30,  50, 25.0],   # 5  - Reconversion, Bac seul, très petite structure
    [3,  3, 3,  100,  80, 34.5],   # 6  - Junior expérimenté, startup tech
    [1,  5, 4,   15, 100, 42.0],   # 7  - ★ ATYPIQUE : junior polyglotte, startup bien financée

    # === Profils intermédiaires (4-8 ans) ===
    [4,  3, 3,  500,  60, 38.5],   # 8  - Dev confirmé, ESN moyenne
    [5,  4, 4, 1200,  40, 44.0],   # 9  - Ingénieur logiciel, ETI industrielle
    [5,  3, 3,  300,  80, 41.0],   # 10 - Dev fullstack, PME tech
    [6,  5, 4, 2000,  30, 48.0],   # 11 - Dev senior débutant, grand groupe
    [6,  4, 3,  150,  90, 43.5],   # 12 - Dev remote, agence digitale
    [7,  5, 4,  800,  50, 50.0],   # 13 - Lead dev, scale-up
    [7,  3, 2,   80,  70, 39.0],   # 14 - ★ ATYPIQUE : expérimenté mais Bac+2, petite boîte
    [4,  6, 5,  400, 100, 47.0],   # 15 - Profil recherche appliquée, doctorat, startup IA
    [8,  4, 4, 1500,  40, 51.0],   # 16 - Dev senior, grande ESN
    [5,  2, 3, 4000,  10, 40.0],   # 17 - Dev dans grand groupe peu tech, peu de remote

    # === Profils seniors (9-14 ans) ===
    [9,  5, 4,  600,  60, 52.0],   # 18 - Lead technique, entreprise tech
    [10, 6, 4, 2500,  30, 55.0],   # 19 - Architecte logiciel, grand groupe
    [10, 4, 3,  200,  80, 48.5],   # 20 - Senior fullstack, PME
    [11, 5, 5, 1000,  50, 58.0],   # 21 - Expert technique, doctorat, ETI
    [12, 7, 4, 3500,  20, 60.0],   # 22 - Principal engineer, très grand groupe
    [9,  3, 3,   60,  90, 42.0],   # 23 - ★ ATYPIQUE : senior sous-payé, petite agence
    [13, 6, 4,  800,  70, 62.0],   # 24 - Engineering manager tech
    [11, 4, 4, 5000,  10, 54.0],   # 25 - Dev senior, multinationale, peu de remote

    # === Profils très expérimentés (15+ ans) ===
    [15, 8, 4, 1200,  50, 65.0],   # 26 - Staff engineer, entreprise tech
    [17, 6, 5, 2000,  40, 68.0],   # 27 - CTO adjoint, doctorat, ETI tech
    [20, 7, 4,  500,  60, 72.0],   # 28 - Consultant indépendant très expérimenté
    [18, 5, 3,  100,  80, 58.0],   # 29 - ★ ATYPIQUE : très expérimenté, Bac+3, petite boîte
]

# --- Conversion en tableau NumPy ---
data = np.array(donnees_brutes)

# Extraction des colonnes individuelles
experience        = data[:, 0].astype(int)
nb_langages       = data[:, 1].astype(int)
niveau_etudes     = data[:, 2].astype(int)
taille_entreprise = data[:, 3].astype(int)
remote            = data[:, 4].astype(int)
salaire           = data[:, 5]

# Colonne dérivée : classification binaire (Jour 3)
# 1 si salaire > 45k€, 0 sinon
salaire_eleve = (salaire > 45).astype(int)

# Dataset complet avec la colonne binaire
dataset = np.column_stack([data, salaire_eleve])

print("=" * 65)
print("  DATASET FIL ROUGE — Salaire des développeurs (30 observations)")
print("=" * 65)
print(f"  Dimensions : {dataset.shape[0]} observations × {dataset.shape[1]} colonnes\n")

# Affichage tabulaire
header = f"{'#':>3} {'Exp':>4} {'Lang':>5} {'Études':>7} {'Entrep.':>8} {'Remote':>7} {'Salaire':>8} {'Élevé':>6}"
print(header)
print("-" * len(header))
for i in range(dataset.shape[0]):
    print(f"{i:>3} {int(dataset[i,0]):>4} {int(dataset[i,1]):>5} "
          f"{int(dataset[i,2]):>7} {int(dataset[i,3]):>8} "
          f"{int(dataset[i,4]):>7} {dataset[i,5]:>8.1f} {int(dataset[i,6]):>6}")


# ============================================================================
# 2. RÉSUMÉ STATISTIQUE
# ============================================================================
print("\n" + "=" * 65)
print("  RÉSUMÉ STATISTIQUE")
print("=" * 65)

for j, nom in enumerate(noms_colonnes):
    col = dataset[:, j]
    print(f"\n  {nom:20s} | min={col.min():8.1f}  max={col.max():8.1f}  "
          f"moy={col.mean():8.2f}  écart-type={col.std():7.2f}")

# Répartition de la classification binaire
n_eleve = int(salaire_eleve.sum())
n_bas = len(salaire_eleve) - n_eleve
print(f"\n  Classification binaire (seuil 45k€) :")
print(f"    → salaire_eleve = 0 (≤ 45k€) : {n_bas} développeurs ({100*n_bas/30:.0f}%)")
print(f"    → salaire_eleve = 1 (> 45k€)  : {n_eleve} développeurs ({100*n_eleve/30:.0f}%)")


# ============================================================================
# 3. VÉRIFICATIONS DE COHÉRENCE
# ============================================================================
print("\n" + "=" * 65)
print("  VÉRIFICATIONS")
print("=" * 65)

# Corrélation experience-salaire (doit être positive et significative)
corr_exp_sal = np.corrcoef(experience, salaire)[0, 1]
print(f"  Corrélation expérience ↔ salaire : {corr_exp_sal:.3f}")

# Corrélation études-salaire
corr_etu_sal = np.corrcoef(niveau_etudes, salaire)[0, 1]
print(f"  Corrélation études ↔ salaire     : {corr_etu_sal:.3f}")

# Corrélation langages-salaire
corr_lang_sal = np.corrcoef(nb_langages, salaire)[0, 1]
print(f"  Corrélation langages ↔ salaire   : {corr_lang_sal:.3f}")

# Régression linéaire simple experience → salaire (aperçu Jour 1)
x_mean = experience.mean()
y_mean = salaire.mean()
w = np.sum((experience - x_mean) * (salaire - y_mean)) / np.sum((experience - x_mean) ** 2)
b = y_mean - w * x_mean
print(f"\n  Régression linéaire (expérience → salaire) :")
print(f"    ŷ = {w:.2f} × expérience + {b:.2f}")
print(f"    → Chaque année d'expérience ≈ +{w:.2f} k€ de salaire")

# MSE de la régression simple
y_pred = w * experience + b
mse = np.mean((salaire - y_pred) ** 2)
print(f"    → MSE = {mse:.2f}")


# ============================================================================
# 4. GRAPHIQUE : Expérience vs Salaire (coloré par classe)
# ============================================================================
fig, ax = plt.subplots(figsize=(10, 6))

# Nuage de points coloré par salaire_eleve
couleurs = ['#e74c3c' if s == 0 else '#2ecc71' for s in salaire_eleve]
scatter = ax.scatter(experience, salaire, c=couleurs, s=80, edgecolors='white',
                     linewidth=0.8, zorder=3, alpha=0.9)

# Droite de régression linéaire
x_line = np.linspace(0, 21, 100)
y_line = w * x_line + b
ax.plot(x_line, y_line, color='#3498db', linewidth=2, linestyle='--',
        label=f'Régression : ŷ = {w:.2f}x + {b:.2f}', zorder=2)

# Seuil de classification à 45k€
ax.axhline(y=45, color='#95a5a6', linewidth=1.5, linestyle=':',
           label='Seuil classification : 45 k€', zorder=1)

# Annotation des cas atypiques
cas_atypiques = {
    7:  "Junior polyglotte\n(startup)",
    14: "Expérimenté\nsous-payé (Bac+2)",
    23: "Senior\nsous-payé (agence)",
    29: "Très exp.\nBac+3 petite boîte",
}
for idx, texte in cas_atypiques.items():
    ax.annotate(texte,
                xy=(experience[idx], salaire[idx]),
                xytext=(12, -8), textcoords='offset points',
                fontsize=7, color='#7f8c8d', fontstyle='italic',
                arrowprops=dict(arrowstyle='-', color='#bdc3c7', lw=0.5))

# Légende manuelle pour les couleurs
from matplotlib.lines import Line2D
legend_elements = [
    Line2D([0], [0], marker='o', color='w', markerfacecolor='#2ecc71',
           markersize=10, label='Salaire > 45k€ (classe 1)'),
    Line2D([0], [0], marker='o', color='w', markerfacecolor='#e74c3c',
           markersize=10, label='Salaire ≤ 45k€ (classe 0)'),
    Line2D([0], [0], color='#3498db', linewidth=2, linestyle='--',
           label=f'Régression : ŷ = {w:.2f}x + {b:.2f}'),
    Line2D([0], [0], color='#95a5a6', linewidth=1.5, linestyle=':',
           label='Seuil classification : 45 k€'),
]
ax.legend(handles=legend_elements, loc='upper left', fontsize=9,
          framealpha=0.9, edgecolor='#ddd')

# Mise en forme
ax.set_xlabel("Années d'expérience", fontsize=12, fontweight='bold')
ax.set_ylabel("Salaire annuel brut (k€)", fontsize=12, fontweight='bold')
ax.set_title("Dataset fil rouge — Salaire des développeurs\n"
             "Expérience vs Salaire (coloré par classification binaire)",
             fontsize=13, fontweight='bold', pad=15)
ax.set_xlim(-0.5, 21)
ax.set_ylim(22, 77)
ax.grid(True, alpha=0.3, linestyle='-')
ax.spines['top'].set_visible(False)
ax.spines['right'].set_visible(False)

# Annotation copyright
fig.text(0.99, 0.01, '© 2025 Mohamed EL AFRIT — IPSSI | CC BY-NC-SA 4.0',
         fontsize=7, color='#aaa', ha='right', va='bottom')

plt.tight_layout()
plt.savefig('/home/claude/dataset_fil_rouge.png', dpi=150, bbox_inches='tight',
            facecolor='white')
plt.show()
print("\n  ✅ Graphique sauvegardé : dataset_fil_rouge.png")


# ============================================================================
# 5. EXPORT — FORMAT BRUT POUR PAGES WEB (JavaScript)
# ============================================================================
print("\n" + "=" * 65)
print("  DONNÉES AU FORMAT JAVASCRIPT (copier-coller dans les pages web)")
print("=" * 65)

print("\nconst DATASET = {")
print('  colonnes: ["experience", "nb_langages", "niveau_etudes", '
      '"taille_entreprise", "remote", "salaire", "salaire_eleve"],')
print("  donnees: [")
for i in range(dataset.shape[0]):
    ligne = (f"    [{int(dataset[i,0]):>2}, {int(dataset[i,1])}, "
             f"{int(dataset[i,2])}, {int(dataset[i,3]):>4}, "
             f"{int(dataset[i,4]):>3}, {dataset[i,5]:>5.1f}, "
             f"{int(dataset[i,6])}]"
             + ("," if i < dataset.shape[0]-1 else ""))
    print(ligne)
print("  ]")
print("};")


# ============================================================================
# 6. EXPORT — FORMAT NUMPY COMPACT (copier-coller dans Colab)
# ============================================================================
print("\n" + "=" * 65)
print("  DONNÉES AU FORMAT NUMPY (copier-coller dans Google Colab)")
print("=" * 65)

print("""
# --- Dataset fil rouge : Salaire des développeurs ---
# Colonnes : experience, nb_langages, niveau_etudes, taille_entreprise, remote, salaire
import numpy as np

data = np.array([""")
for i, row in enumerate(donnees_brutes):
    virgule = "," if i < len(donnees_brutes)-1 else ""
    print(f"    [{row[0]:>2}, {row[1]}, {row[2]}, {row[3]:>4}, {row[4]:>3}, {row[5]:>5.1f}]{virgule}")
print("""])

# Extraction des colonnes
experience        = data[:, 0]  # Années d'expérience
nb_langages       = data[:, 1]  # Nombre de langages maîtrisés
niveau_etudes     = data[:, 2]  # 1=Bac, 2=Bac+2, 3=Bac+3, 4=Bac+5, 5=Doctorat
taille_entreprise = data[:, 3]  # Nombre d'employés
remote            = data[:, 4]  # Pourcentage de télétravail
salaire           = data[:, 5]  # Salaire annuel brut en k€

# Variable cible binaire pour la classification (Jour 3)
salaire_eleve = (salaire > 45).astype(int)  # 1 si salaire > 45k€
""")

print("\n✅ Script terminé avec succès.")
