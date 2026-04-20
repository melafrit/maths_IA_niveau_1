#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Génère une suite de CSV de test pour le dashboard enseignant.

Cette suite couvre une classe fictive de 10 étudiants avec des profils variés
(excellent, moyen, en difficulté, cas limites, signature falsifiée) pour valider
tous les écrans du dashboard : tableau, filtres, histogramme, top 5 ratées,
export CSV/XLSX, détection de fraude, etc.

Toutes les données sont déterministes (random.seed fixe par étudiant) donc les
résultats sont reproductibles. Le script peut être ré-exécuté sans surprise.

Usage :
    python3 _generate_test_csvs.py

Sortie : 10 fichiers CSV dans le même répertoire que ce script.

© 2025 Mohamed EL AFRIT — IPSSI
Licence CC BY-NC-SA 4.0
"""
import hashlib
import json
import random
import re
from datetime import datetime, timedelta, timezone
from pathlib import Path

# =============================================================================
# Configuration
# =============================================================================
SCRIPT_DIR = Path(__file__).resolve().parent
REPO_DIR = SCRIPT_DIR.parent
QUESTIONS_PATH = REPO_DIR / "questions.json"
CORRECTIONS_PATH = REPO_DIR / "corrections.json"
OUTPUT_DIR = SCRIPT_DIR
SALT = "IPSSI_SALT_2026"
LETTRES = ["A", "B", "C", "D"]
BAREME = {"vert": 1, "jaune": 2, "orange": 3, "rouge": 4}
DATE_REF = datetime(2026, 4, 26, 14, 0, 0, tzinfo=timezone.utc)


# =============================================================================
# Profils d'étudiants fictifs
# Chaque profil définit le comportement attendu : taux de bonnes réponses, taux
# de non-réponses, durée de passage, biais éventuel par jour, et flag "falsifié"
# pour simuler une fraude.
# =============================================================================
PROFILS = [
    {
        "nom": "ALLEGRE", "prenom": "Emma", "email": "emma.allegre@eleve.ipssi.fr",
        "taux_correct": 0.95, "taux_skip": 0.00,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 62, "seed": 101,
        "description": "Excellente etudiante, maitrise totale.",
    },
    {
        "nom": "BERNARD", "prenom": "Louis", "email": "louis.bernard@eleve.ipssi.fr",
        "taux_correct": 0.82, "taux_skip": 0.02,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 55, "seed": 202,
        "description": "Bon eleve, equilibre entre J1 et J2.",
    },
    {
        "nom": "CHEVALIER", "prenom": "Nora", "email": "nora.chevalier@eleve.ipssi.fr",
        "taux_correct": 0.72, "taux_skip": 0.04,
        "biais_j1": -0.15, "biais_j2": 0.10,
        "duree_min": 68, "seed": 303,
        "description": "Bonne en J2 mais faible en J1 (calculs vectoriels).",
    },
    {
        "nom": "DURAND", "prenom": "Paul", "email": "paul.durand@eleve.ipssi.fr",
        "taux_correct": 0.65, "taux_skip": 0.06,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 50, "seed": 404,
        "description": "Eleve moyen, comprehension partielle des concepts.",
    },
    {
        "nom": "FABRE", "prenom": "Sarah", "email": "sarah.fabre@eleve.ipssi.fr",
        "taux_correct": 0.58, "taux_skip": 0.08,
        "biais_j1": 0.15, "biais_j2": -0.15,
        "duree_min": 72, "seed": 505,
        "description": "Moyenne, mais bloque sur l'optimisation (J2).",
    },
    {
        "nom": "GARNIER", "prenom": "Tom", "email": "tom.garnier@eleve.ipssi.fr",
        "taux_correct": 0.48, "taux_skip": 0.10,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 45, "seed": 606,
        "description": "Tout juste passable, bases fragiles.",
    },
    {
        "nom": "HENRY", "prenom": "Julie", "email": "julie.henry@eleve.ipssi.fr",
        "taux_correct": 0.35, "taux_skip": 0.30,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 38, "seed": 707,
        "description": "En grande difficulte, beaucoup de questions blanches.",
    },
    {
        "nom": "ISAAC", "prenom": "Mathis", "email": "mathis.isaac@eleve.ipssi.fr",
        "taux_correct": 1.00, "taux_skip": 0.00,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 48, "seed": 808,
        "description": "Cas limite : 20/20 parfait.",
    },
    {
        "nom": "JACQUES", "prenom": "Lea", "email": "lea.jacques@eleve.ipssi.fr",
        "taux_correct": 0.00, "taux_skip": 0.00,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 28, "seed": 909,
        "description": "Cas limite : 0/20, toutes mauvaises.",
    },
    {
        "nom": "KAHN", "prenom": "Samuel", "email": "samuel.kahn@eleve.ipssi.fr",
        "taux_correct": 0.55, "taux_skip": 0.05,
        "biais_j1": 0.00, "biais_j2": 0.00,
        "duree_min": 54, "seed": 1010,
        "falsifie": True,
        "description": "CSV modifie manuellement apres export -> signature invalide.",
    },
]


# =============================================================================
# Utilitaires
# =============================================================================
def troncate_enonce(enonce: str, max_len: int = 180) -> str:
    """Reproduit le tronquage du champ EnonceTronque dans qcm_etudiant.html."""
    out = re.sub(r"\$[^$]*\$", "[formule]", enonce)
    out = re.sub(r"```[\s\S]*?```", "[code]", out)
    out = re.sub(r"`[^`]+`", "[code]", out)
    out = re.sub(r"\s+", " ", out).strip()
    if len(out) > max_len:
        out = out[:max_len - 1] + "…"
    return out


def csv_cell(value) -> str:
    """Echappement CSV identique a celui de qcm_etudiant.html."""
    if value is None:
        return ""
    s = str(value)
    if any(c in s for c in ['"', ",", "\n", "\r"]):
        return '"' + s.replace('"', '""') + '"'
    return s


def compute_signature(nom, prenom, email, date_debut_iso, date_fin_iso,
                      duree_sec, nb_questions, answers):
    """Recalcule la signature SHA-256 identique a celle de la page web."""
    parts = [
        nom, prenom, email, date_debut_iso, date_fin_iso,
        str(duree_sec), str(nb_questions), SALT,
    ]
    for a in answers:
        idx = "" if a["indexOriginalChoisi"] is None else str(a["indexOriginalChoisi"])
        parts.append(f"{a['ordre']}|{a['id']}|{'|'.join(str(p) for p in a['permutation'])}|{idx}")
    data = "||".join(parts)
    return hashlib.sha256(data.encode("utf-8")).hexdigest()


def shuffle_with_perm(arr, rng):
    """Fisher-Yates avec conservation de la permutation."""
    idx = list(range(len(arr)))
    for i in range(len(idx) - 1, 0, -1):
        j = rng.randint(0, i)
        idx[i], idx[j] = idx[j], idx[i]
    return idx


# =============================================================================
# Generation d'un CSV pour un profil
# =============================================================================
def generer_csv_pour(profil, questions, corrections):
    """Genere le contenu d'un CSV pour un profil donne et retourne aussi les
    statistiques attendues (pour verification automatique)."""

    rng = random.Random(profil["seed"])

    # Permutation globale des questions
    perm_q = shuffle_with_perm(questions, rng)
    q_shuffled = [questions[i] for i in perm_q]

    # Permutation des propositions pour chaque question
    q_prepared = []
    for q in q_shuffled:
        perm_p = shuffle_with_perm(q["propositions"], rng)
        q_prepared.append({**q, "perm_propositions": perm_p})

    # Simulation des reponses
    corr_by_id = {c["id"]: c for c in corrections}
    answers = []
    attendu_correct = 0
    attendu_points = 0
    pts_max_total = sum(BAREME[q["difficulte"]] for q in questions)  # = 92

    for ordre, q in enumerate(q_prepared, start=1):
        # Biais de jour
        biais = profil.get(f"biais_j{q['jour']}", 0.0)
        taux = max(0.0, min(1.0, profil["taux_correct"] + biais))

        # Non-reponse ?
        if rng.random() < profil["taux_skip"]:
            answers.append({
                "ordre": ordre, "id": q["id"], "difficulte": q["difficulte"],
                "type": q["type"], "jour": q["jour"],
                "enonceTronque": troncate_enonce(q["enonce"]),
                "permutation": q["perm_propositions"],
                "lettreChoisie": "",
                "indexOriginalChoisi": None,
            })
            continue

        # Reponse : correcte ou non ?
        cor = corr_by_id[q["id"]]
        bonne_orig_idx = cor["bonne_reponse_index"]
        bonne_vue_idx = q["perm_propositions"].index(bonne_orig_idx)

        if rng.random() < taux:
            # Bonne reponse
            chosen_vue_idx = bonne_vue_idx
        else:
            # Mauvaise reponse (choix random parmi les 3 autres)
            autres = [i for i in range(4) if i != bonne_vue_idx]
            chosen_vue_idx = rng.choice(autres)

        chosen_orig_idx = q["perm_propositions"][chosen_vue_idx]
        is_correct = (chosen_orig_idx == bonne_orig_idx)
        if is_correct:
            attendu_correct += 1
            attendu_points += BAREME[q["difficulte"]]

        answers.append({
            "ordre": ordre, "id": q["id"], "difficulte": q["difficulte"],
            "type": q["type"], "jour": q["jour"],
            "enonceTronque": troncate_enonce(q["enonce"]),
            "permutation": q["perm_propositions"],
            "lettreChoisie": LETTRES[chosen_vue_idx],
            "indexOriginalChoisi": chosen_orig_idx,
        })

    # Dates et duree
    duree_sec = profil["duree_min"] * 60
    date_fin = DATE_REF + timedelta(hours=profil["seed"] % 6)
    date_debut = date_fin - timedelta(seconds=duree_sec)
    date_debut_iso = date_debut.strftime("%Y-%m-%dT%H:%M:%S.000Z")
    date_fin_iso = date_fin.strftime("%Y-%m-%dT%H:%M:%S.000Z")

    # Signature
    signature = compute_signature(
        profil["nom"], profil["prenom"], profil["email"],
        date_debut_iso, date_fin_iso, duree_sec, len(answers), answers,
    )

    # Generation du CSV
    lines = []
    lines.append("# CSV d'evaluation QCM Jours 1-2 - IPSSI Bachelor 2 Info")
    lines.append("# Fichier de test genere automatiquement")
    lines.append("#")
    lines.append("# SECTION: METADATA")
    lines.append("cle,valeur")
    meta_lines = [
        ("Nom", profil["nom"]),
        ("Prenom", profil["prenom"]),
        ("Email", profil["email"]),
        ("DateDebut", date_debut_iso),
        ("DateFin", date_fin_iso),
        ("DureeSec", duree_sec),
        ("NbQuestions", len(answers)),
        ("Version", "1.0"),
        ("SaltVersion", SALT),
    ]
    for k, v in meta_lines:
        lines.append(f"{csv_cell(k)},{csv_cell(v)}")
    lines.append("#")
    lines.append("# SECTION: REPONSES")
    lines.append("Ordre,IDQuestion,Difficulte,Type,Jour,EnonceTronque,PermutationPropositions,LettreChoisie,IndexOriginalChoisi")
    for a in answers:
        row = [
            a["ordre"], a["id"], a["difficulte"], a["type"], a["jour"],
            csv_cell(a["enonceTronque"]),
            csv_cell("|".join(str(p) for p in a["permutation"])),
            a["lettreChoisie"],
            "" if a["indexOriginalChoisi"] is None else a["indexOriginalChoisi"],
        ]
        lines.append(",".join(str(x) for x in row))
    lines.append("#")
    lines.append("# SECTION: SIGNATURE")
    lines.append("champ,valeur")
    lines.append(f"SHA256,{signature}")

    csv_content = "\n".join(lines) + "\n"

    # Falsification eventuelle : on modifie une reponse sans recalculer la
    # signature pour simuler une fraude (la signature deviendra invalide)
    if profil.get("falsifie"):
        # On remplace le premier indexOriginalChoisi non-vide par une valeur
        # differente. Cela DOIT casser la signature.
        # Strategy : dans la section REPONSES, changer le dernier champ de la
        # premiere ligne de reponse dont l'indexOriginalChoisi est connu.
        lines_fals = csv_content.split("\n")
        in_reponses = False
        header_passed = False
        for idx, ln in enumerate(lines_fals):
            if ln.startswith("# SECTION: REPONSES"):
                in_reponses = True
                continue
            if in_reponses and not header_passed:
                header_passed = True
                continue
            if in_reponses and ln.startswith("#"):
                break
            if in_reponses and header_passed and ln.strip():
                cells = ln.split(",")
                if len(cells) >= 9 and cells[-1] and cells[-1] != "":
                    # On change l'indexOriginalChoisi (derniere cellule)
                    original = cells[-1]
                    new_val = str((int(original) + 1) % 4)
                    cells[-1] = new_val
                    lines_fals[idx] = ",".join(cells)
                    break
        csv_content = "\n".join(lines_fals)

    # Note attendue
    note_attendue = (attendu_points / pts_max_total) * 20
    stats = {
        "nb_correct": attendu_correct,
        "points_obtenus": attendu_points,
        "points_max": pts_max_total,
        "note_sur_20": note_attendue,
        "duree_sec": duree_sec,
        "nb_questions": len(answers),
        "signature_valide": not profil.get("falsifie", False),
    }

    return csv_content, stats


# =============================================================================
# Main
# =============================================================================
def main():
    print(f"[INFO] Repertoire de sortie : {OUTPUT_DIR}")
    print(f"[INFO] Chargement de {QUESTIONS_PATH.name}")
    questions = json.loads(QUESTIONS_PATH.read_text(encoding="utf-8"))["questions"]
    print(f"[INFO] Chargement de {CORRECTIONS_PATH.name}")
    corrections = json.loads(CORRECTIONS_PATH.read_text(encoding="utf-8"))["corrections"]
    print(f"[INFO] {len(questions)} questions, {len(corrections)} corrections chargees")
    print()

    manifeste = []
    for profil in PROFILS:
        csv_content, stats = generer_csv_pour(profil, questions, corrections)

        # Nom de fichier
        date_str = DATE_REF.strftime("%Y%m%d")
        filename = f"IPSSI_{profil['nom']}_{profil['prenom']}_QCM_J1J2_{date_str}.csv"
        path = OUTPUT_DIR / filename
        path.write_text(csv_content, encoding="utf-8")

        print(f"[OK] {filename}")
        print(f"     Profil : {profil['description']}")
        print(f"     Note attendue : {stats['note_sur_20']:.2f}/20")
        print(f"     {stats['nb_correct']}/{stats['nb_questions']} correctes "
              f"({stats['points_obtenus']}/{stats['points_max']} pts)")
        print(f"     Duree : {stats['duree_sec']} s")
        print(f"     Signature : {'VALIDE' if stats['signature_valide'] else 'INVALIDE (fraude simulee)'}")
        print()

        manifeste.append({
            "filename": filename,
            "description": profil["description"],
            "attendu": {
                "nom": profil["nom"],
                "prenom": profil["prenom"],
                "email": profil["email"],
                "note_sur_20": round(stats["note_sur_20"], 2),
                "nb_correct": stats["nb_correct"],
                "points_obtenus": stats["points_obtenus"],
                "duree_sec": stats["duree_sec"],
                "signature_valide": stats["signature_valide"],
            },
        })

    # Ecriture du manifeste JSON
    manifeste_path = OUTPUT_DIR / "MANIFESTE_TESTS.json"
    manifeste_path.write_text(
        json.dumps({"csv_tests": manifeste}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    print(f"[OK] Manifeste : {manifeste_path.name}")
    print()

    # Recapitulatif
    notes = [m["attendu"]["note_sur_20"] for m in manifeste]
    notes_tries = sorted(notes)
    print("=" * 70)
    print("RECAPITULATIF DE LA CLASSE DE TEST")
    print("=" * 70)
    print(f"Nombre d'etudiants  : {len(PROFILS)}")
    print(f"Moyenne             : {sum(notes)/len(notes):.2f}/20")
    mediane = (notes_tries[len(notes_tries)//2-1] + notes_tries[len(notes_tries)//2]) / 2 \
              if len(notes_tries) % 2 == 0 else notes_tries[len(notes_tries)//2]
    print(f"Mediane             : {mediane:.2f}/20")
    print(f"Note min            : {min(notes):.2f}/20")
    print(f"Note max            : {max(notes):.2f}/20")
    print(f"Signatures valides  : {sum(1 for m in manifeste if m['attendu']['signature_valide'])}/{len(manifeste)}")
    print("=" * 70)


if __name__ == "__main__":
    main()
