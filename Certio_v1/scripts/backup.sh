#!/bin/bash
#
# backup.sh — Script de backup automatique IPSSI Examens
#
# Crée un archive tar.gz du dossier data/ avec :
#   - Nom horodaté : backup_YYYY-MM-DD_HHmmss.tar.gz
#   - Hash SHA-256 stocké à côté
#   - Lock file pour éviter backups concurrents
#   - Rotation automatique (garde N derniers)
#   - Logging dans data/logs/backups.log
#
# Usage :
#   ./scripts/backup.sh                    # Backup standard
#   ./scripts/backup.sh --keep=30          # Garde 30 derniers
#   ./scripts/backup.sh --quiet            # Mode silencieux (cron)
#   ./scripts/backup.sh --verify-only=FILE # Verifier hash d'un backup existant
#
# Exit codes :
#   0 : OK
#   1 : Erreur generale
#   2 : Lock file present (backup deja en cours)
#   3 : Disque plein / permissions
#
# © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
#

set -euo pipefail

# ============================================================================
# Configuration
# ============================================================================

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
EXAMENS_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
DATA_DIR="$EXAMENS_ROOT/data"
BACKUPS_DIR="$EXAMENS_ROOT/data/backups"
LOG_FILE="$EXAMENS_ROOT/data/logs/backups.log"
LOCK_FILE="/tmp/ipssi_examens_backup.lock"

DEFAULT_KEEP=14  # Garde les 14 derniers par défaut (2 semaines de daily)

# ============================================================================
# Arguments CLI
# ============================================================================

KEEP="$DEFAULT_KEEP"
QUIET=false
VERIFY_ONLY=""

for arg in "$@"; do
  case "$arg" in
    --keep=*)
      KEEP="${arg#*=}"
      ;;
    --quiet|-q)
      QUIET=true
      ;;
    --verify-only=*)
      VERIFY_ONLY="${arg#*=}"
      ;;
    --help|-h)
      head -30 "$0" | grep -E '^#' | sed 's/^# ?//'
      exit 0
      ;;
    *)
      echo "Argument inconnu : $arg" >&2
      exit 1
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
  if [ "$QUIET" = false ]; then
    echo "$msg"
  fi
}

err() {
  local msg="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
  mkdir -p "$(dirname "$LOG_FILE")"
  echo "$msg" >> "$LOG_FILE"
  echo "$msg" >&2
}

bytes_human() {
  local bytes=$1
  if [ "$bytes" -lt 1024 ]; then
    echo "${bytes}B"
  elif [ "$bytes" -lt 1048576 ]; then
    echo "$((bytes / 1024))K"
  elif [ "$bytes" -lt 1073741824 ]; then
    echo "$((bytes / 1048576))M"
  else
    echo "$((bytes / 1073741824))G"
  fi
}

# Cross-platform: macOS `stat -f` vs Linux `stat -c`
file_size() {
  if stat -c%s "$1" 2>/dev/null; then
    return
  fi
  stat -f%z "$1" 2>/dev/null || echo 0
}

cleanup_lock() {
  if [ -f "$LOCK_FILE" ]; then
    rm -f "$LOCK_FILE" 2>/dev/null || true
  fi
}

trap cleanup_lock EXIT INT TERM

# ============================================================================
# Mode verify-only
# ============================================================================

if [ -n "$VERIFY_ONLY" ]; then
  if [ ! -f "$VERIFY_ONLY" ]; then
    err "Fichier introuvable : $VERIFY_ONLY"
    exit 1
  fi
  HASH_FILE="${VERIFY_ONLY}.sha256"
  if [ ! -f "$HASH_FILE" ]; then
    err "Fichier hash introuvable : $HASH_FILE"
    exit 1
  fi
  log "Verification hash de : $VERIFY_ONLY"
  STORED_HASH=$(cut -d' ' -f1 < "$HASH_FILE")
  CURRENT_HASH=$(sha256sum "$VERIFY_ONLY" | cut -d' ' -f1)
  if [ "$STORED_HASH" = "$CURRENT_HASH" ]; then
    log "✅ Hash OK : $CURRENT_HASH"
    exit 0
  else
    err "❌ Hash mismatch : stored=$STORED_HASH current=$CURRENT_HASH"
    exit 1
  fi
fi

# ============================================================================
# Verification prerequis
# ============================================================================

if [ ! -d "$DATA_DIR" ]; then
  err "DATA_DIR introuvable : $DATA_DIR"
  exit 1
fi

# ============================================================================
# Lock
# ============================================================================

if [ -f "$LOCK_FILE" ]; then
  LOCK_AGE=$(($(date +%s) - $(stat -c %Y "$LOCK_FILE" 2>/dev/null || stat -f %m "$LOCK_FILE" 2>/dev/null || echo 0)))
  if [ "$LOCK_AGE" -lt 3600 ]; then
    err "Backup deja en cours (lock : ${LOCK_AGE}s). Attendre ou supprimer $LOCK_FILE"
    exit 2
  else
    log "Lock obsolete (${LOCK_AGE}s), nettoyage"
    rm -f "$LOCK_FILE"
  fi
fi

echo $$ > "$LOCK_FILE"

# ============================================================================
# Creation du backup
# ============================================================================

mkdir -p "$BACKUPS_DIR"

TIMESTAMP=$(date '+%Y-%m-%d_%H%M%S')
BACKUP_FILE="$BACKUPS_DIR/backup_${TIMESTAMP}.tar.gz"
HASH_FILE="${BACKUP_FILE}.sha256"

log "=== Backup IPSSI Examens ==="
log "Source      : $DATA_DIR"
log "Destination : $BACKUP_FILE"

# Liste des sous-dossiers a sauvegarder (exclure backups et logs archives volumineux)
INCLUDE_DIRS=(
  "examens"
  "passages"
  "comptes"
  "banque"
  "sessions"
  "config"
)

# Construire la liste des chemins a inclure (seulement ceux qui existent)
TO_INCLUDE=()
for dir in "${INCLUDE_DIRS[@]}"; do
  if [ -d "$DATA_DIR/$dir" ]; then
    TO_INCLUDE+=("$dir")
  fi
done

if [ ${#TO_INCLUDE[@]} -eq 0 ]; then
  err "Aucun dossier a sauvegarder dans $DATA_DIR"
  exit 1
fi

log "Inclus : ${TO_INCLUDE[*]}"

# Creer l'archive
START=$(date +%s)

# tar avec compression gzip (pas trop aggressive pour vitesse)
if ! tar -czf "$BACKUP_FILE" -C "$DATA_DIR" "${TO_INCLUDE[@]}" 2>>"$LOG_FILE"; then
  err "tar a echoue"
  rm -f "$BACKUP_FILE"
  exit 3
fi

END=$(date +%s)
DURATION=$((END - START))

# Calculer hash SHA-256
sha256sum "$BACKUP_FILE" > "$HASH_FILE"
HASH=$(cut -d' ' -f1 < "$HASH_FILE")

# Taille finale
BACKUP_SIZE=$(file_size "$BACKUP_FILE")
BACKUP_SIZE_H=$(bytes_human "$BACKUP_SIZE")

log "✅ Backup cree : $BACKUP_SIZE_H en ${DURATION}s"
log "   Hash SHA-256 : $HASH"

# ============================================================================
# Rotation
# ============================================================================

log "Rotation (garde les $KEEP derniers)"

# Lister les backups par date desc, garder les N premiers
ALL_BACKUPS=$(ls -1t "$BACKUPS_DIR"/backup_*.tar.gz 2>/dev/null || true)

if [ -n "$ALL_BACKUPS" ]; then
  NB_BACKUPS=$(echo "$ALL_BACKUPS" | wc -l)
  if [ "$NB_BACKUPS" -gt "$KEEP" ]; then
    TO_DELETE=$(echo "$ALL_BACKUPS" | tail -n +$((KEEP + 1)))
    DELETED=0
    while IFS= read -r file; do
      if [ -n "$file" ]; then
        rm -f "$file" "${file}.sha256" 2>/dev/null && DELETED=$((DELETED + 1))
      fi
    done <<< "$TO_DELETE"
    log "Supprime $DELETED ancien(s) backup(s)"
  fi
fi

# ============================================================================
# Resume final
# ============================================================================

TOTAL_BACKUPS=$(ls -1 "$BACKUPS_DIR"/backup_*.tar.gz 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sb "$BACKUPS_DIR" 2>/dev/null | cut -f1 || echo 0)
TOTAL_SIZE_H=$(bytes_human "$TOTAL_SIZE")

log "=== Termine ==="
log "Backups actifs : $TOTAL_BACKUPS (taille totale : $TOTAL_SIZE_H)"

exit 0
