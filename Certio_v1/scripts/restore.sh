#!/bin/bash
#
# restore.sh — Script de restauration depuis un backup
#
# Features :
#   - Liste interactive des backups disponibles
#   - Verification hash SHA-256 avant restore
#   - Backup de securite avant overwrite
#   - Support restore partiel (--only=examens)
#   - Confirmation utilisateur
#
# Usage :
#   ./scripts/restore.sh                            # Interactif : choisir backup
#   ./scripts/restore.sh FILE.tar.gz                # Restore d'un fichier precis
#   ./scripts/restore.sh --list                     # Lister uniquement
#   ./scripts/restore.sh --latest                   # Restore du plus recent
#   ./scripts/restore.sh FILE --only=examens        # Restore partiel
#   ./scripts/restore.sh FILE --yes                 # Sans confirmation
#   ./scripts/restore.sh FILE --no-safety-backup    # Skip backup de securite
#
# © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
#

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
EXAMENS_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
DATA_DIR="$EXAMENS_ROOT/data"
BACKUPS_DIR="$EXAMENS_ROOT/data/backups"
LOG_FILE="$EXAMENS_ROOT/data/logs/backups.log"

# ============================================================================
# Arguments
# ============================================================================

BACKUP_FILE=""
LIST_ONLY=false
USE_LATEST=false
ONLY_DIR=""
AUTO_YES=false
SAFETY_BACKUP=true

for arg in "$@"; do
  case "$arg" in
    --list)
      LIST_ONLY=true
      ;;
    --latest)
      USE_LATEST=true
      ;;
    --only=*)
      ONLY_DIR="${arg#*=}"
      ;;
    --yes|-y)
      AUTO_YES=true
      ;;
    --no-safety-backup)
      SAFETY_BACKUP=false
      ;;
    --help|-h)
      head -22 "$0" | grep -E '^#' | sed 's/^# ?//'
      exit 0
      ;;
    -*)
      echo "Option inconnue : $arg" >&2
      exit 1
      ;;
    *)
      BACKUP_FILE="$arg"
      ;;
  esac
done

# ============================================================================
# Helpers
# ============================================================================

log() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
  mkdir -p "$(dirname "$LOG_FILE")"
  echo "$msg" >> "$LOG_FILE"
  echo "$msg"
}

err() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
  mkdir -p "$(dirname "$LOG_FILE")"
  echo "$msg" >> "$LOG_FILE"
  echo "$msg" >&2
}

file_size() {
  stat -c%s "$1" 2>/dev/null || stat -f%z "$1" 2>/dev/null || echo 0
}

bytes_human() {
  local bytes=$1
  if [ "$bytes" -lt 1048576 ]; then echo "$((bytes / 1024))K"
  elif [ "$bytes" -lt 1073741824 ]; then echo "$((bytes / 1048576))M"
  else echo "$((bytes / 1073741824))G"; fi
}

list_backups() {
  if [ ! -d "$BACKUPS_DIR" ]; then
    echo "Aucun backup dans $BACKUPS_DIR"
    return
  fi

  local found=false
  echo ""
  echo "=== Backups disponibles ==="
  printf "%-3s %-30s %-10s %-20s\n" "#" "Fichier" "Taille" "Date"
  printf "%s\n" "------------------------------------------------------------"

  local i=0
  for file in $(ls -1t "$BACKUPS_DIR"/backup_*.tar.gz 2>/dev/null); do
    i=$((i + 1))
    local basename=$(basename "$file")
    local size=$(file_size "$file")
    local size_h=$(bytes_human "$size")
    local date_str=$(echo "$basename" | sed 's/backup_\(.*\)\.tar\.gz/\1/' | tr '_' ' ')
    printf "%-3s %-30s %-10s %-20s\n" "$i" "$basename" "$size_h" "$date_str"
    found=true
  done

  if [ "$found" = false ]; then
    echo "(aucun backup)"
  fi
  echo ""
}

# ============================================================================
# Mode list-only
# ============================================================================

if [ "$LIST_ONLY" = true ]; then
  list_backups
  exit 0
fi

# ============================================================================
# Selection du backup
# ============================================================================

if [ "$USE_LATEST" = true ]; then
  BACKUP_FILE=$(ls -1t "$BACKUPS_DIR"/backup_*.tar.gz 2>/dev/null | head -1 || echo "")
  if [ -z "$BACKUP_FILE" ]; then
    err "Aucun backup disponible"
    exit 1
  fi
  log "Utilisation du plus recent : $(basename "$BACKUP_FILE")"
fi

if [ -z "$BACKUP_FILE" ]; then
  list_backups

  read -p "Numéro du backup à restaurer (ou 'q' pour quitter) : " choice

  if [ "$choice" = "q" ] || [ "$choice" = "Q" ]; then
    echo "Annulé"
    exit 0
  fi

  if ! [[ "$choice" =~ ^[0-9]+$ ]]; then
    err "Choix invalide"
    exit 1
  fi

  BACKUP_FILE=$(ls -1t "$BACKUPS_DIR"/backup_*.tar.gz 2>/dev/null | sed -n "${choice}p")

  if [ -z "$BACKUP_FILE" ] || [ ! -f "$BACKUP_FILE" ]; then
    err "Backup #$choice introuvable"
    exit 1
  fi
fi

# Resoudre chemin relatif
if [[ "$BACKUP_FILE" != /* ]]; then
  if [ -f "$BACKUPS_DIR/$BACKUP_FILE" ]; then
    BACKUP_FILE="$BACKUPS_DIR/$BACKUP_FILE"
  elif [ ! -f "$BACKUP_FILE" ]; then
    err "Fichier introuvable : $BACKUP_FILE"
    exit 1
  fi
fi

if [ ! -f "$BACKUP_FILE" ]; then
  err "Fichier introuvable : $BACKUP_FILE"
  exit 1
fi

# ============================================================================
# Verification hash
# ============================================================================

HASH_FILE="${BACKUP_FILE}.sha256"

if [ -f "$HASH_FILE" ]; then
  log "Verification hash SHA-256..."
  STORED_HASH=$(cut -d' ' -f1 < "$HASH_FILE")
  CURRENT_HASH=$(sha256sum "$BACKUP_FILE" | cut -d' ' -f1)
  if [ "$STORED_HASH" != "$CURRENT_HASH" ]; then
    err "❌ Hash mismatch ! Le backup est corrompu ou a ete altere."
    err "   Stored  : $STORED_HASH"
    err "   Current : $CURRENT_HASH"
    exit 1
  fi
  log "✅ Hash OK"
else
  log "⚠  Pas de fichier hash, verification d'integrite sautee"
fi

# ============================================================================
# Verification contenu du tar
# ============================================================================

log "Analyse du backup..."
CONTENT=$(tar -tzf "$BACKUP_FILE" 2>/dev/null | head -100 || true)
if [ -z "$CONTENT" ]; then
  err "Archive vide ou invalide"
  exit 1
fi

TOP_DIRS=$(echo "$CONTENT" | awk -F'/' '{print $1}' | sort -u)
log "Dossiers : $(echo "$TOP_DIRS" | tr '\n' ' ')"

# ============================================================================
# Confirmation
# ============================================================================

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║                    ATTENTION RESTORE                        ║"
echo "╠═══════════════════════════════════════════════════════════╣"
echo "║  Fichier : $(basename "$BACKUP_FILE")"
echo "║  Cible   : $DATA_DIR"
if [ -n "$ONLY_DIR" ]; then
  echo "║  Mode    : Restore PARTIEL ($ONLY_DIR uniquement)"
else
  echo "║  Mode    : Restore COMPLET"
fi
echo "║"
echo "║  ⚠  Les donnees actuelles seront remplacees."
echo "║  ⚠  Un backup de securite sera cree avant si active."
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

if [ "$AUTO_YES" = false ]; then
  read -p "Confirmer restore (tapez 'yes' pour continuer) : " confirm
  if [ "$confirm" != "yes" ]; then
    echo "Annulé"
    exit 0
  fi
fi

# ============================================================================
# Backup de securite avant overwrite
# ============================================================================

if [ "$SAFETY_BACKUP" = true ]; then
  SAFETY_FILE="$BACKUPS_DIR/safety_before_restore_$(date '+%Y-%m-%d_%H%M%S').tar.gz"
  log "Backup de securite : $(basename "$SAFETY_FILE")"

  INCLUDE_DIRS=("examens" "passages" "comptes" "banque" "sessions" "config")
  TO_INCLUDE=()
  for dir in "${INCLUDE_DIRS[@]}"; do
    if [ -d "$DATA_DIR/$dir" ]; then
      TO_INCLUDE+=("$dir")
    fi
  done

  if [ ${#TO_INCLUDE[@]} -gt 0 ]; then
    tar -czf "$SAFETY_FILE" -C "$DATA_DIR" "${TO_INCLUDE[@]}" 2>>"$LOG_FILE"
    sha256sum "$SAFETY_FILE" > "${SAFETY_FILE}.sha256"
    log "✅ Backup de securite cree"
  fi
fi

# ============================================================================
# Restore
# ============================================================================

log "=== Restore en cours ==="

mkdir -p "$DATA_DIR"

if [ -n "$ONLY_DIR" ]; then
  # Restore partiel
  log "Extraction de '$ONLY_DIR' uniquement"

  # Supprimer le dossier cible existant
  if [ -d "$DATA_DIR/$ONLY_DIR" ]; then
    log "Suppression de l'ancien $DATA_DIR/$ONLY_DIR"
    rm -rf "$DATA_DIR/$ONLY_DIR"
  fi

  tar -xzf "$BACKUP_FILE" -C "$DATA_DIR" "$ONLY_DIR" 2>>"$LOG_FILE" || {
    err "Extraction partielle echouee"
    exit 1
  }
else
  # Restore complet : extraire tout
  log "Extraction complete"

  # Lister les dossiers dans l'archive
  DIRS_IN_ARCHIVE=$(echo "$TOP_DIRS" | grep -v '^$')

  # Supprimer d'abord les dossiers correspondants
  for dir in $DIRS_IN_ARCHIVE; do
    if [ -d "$DATA_DIR/$dir" ]; then
      log "Nettoyage $DATA_DIR/$dir"
      rm -rf "$DATA_DIR/$dir"
    fi
  done

  tar -xzf "$BACKUP_FILE" -C "$DATA_DIR" 2>>"$LOG_FILE" || {
    err "Extraction complete echouee"
    exit 1
  }
fi

log "✅ Restore termine avec succes"

# ============================================================================
# Post-restore : stats
# ============================================================================

for dir in examens passages comptes banque; do
  if [ -d "$DATA_DIR/$dir" ]; then
    COUNT=$(find "$DATA_DIR/$dir" -maxdepth 2 -name '*.json' 2>/dev/null | wc -l)
    log "  $dir : $COUNT fichier(s) JSON"
  fi
done

exit 0
