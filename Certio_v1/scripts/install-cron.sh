#!/bin/bash
#
# install-cron.sh — Helper pour installer le cron job de backup
#
# Usage :
#   ./scripts/install-cron.sh                 # Afficher la ligne cron à ajouter
#   ./scripts/install-cron.sh --install       # Installer automatiquement
#   ./scripts/install-cron.sh --remove        # Desinstaller
#   ./scripts/install-cron.sh --time="02:00"  # Heure de backup (defaut 03:00)
#
# © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
#

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BACKUP_SCRIPT="$SCRIPT_DIR/backup.sh"

# ============================================================================
# Arguments
# ============================================================================

MODE="show"
CRON_TIME="03:00"

for arg in "$@"; do
  case "$arg" in
    --install) MODE="install" ;;
    --remove)  MODE="remove" ;;
    --time=*)  CRON_TIME="${arg#*=}" ;;
    --help|-h)
      head -12 "$0" | grep -E '^#' | sed 's/^# ?//'
      exit 0
      ;;
  esac
done

# Parser CRON_TIME (HH:MM)
IFS=':' read -r CRON_H CRON_M <<< "$CRON_TIME"
CRON_H=${CRON_H#0}
CRON_M=${CRON_M#0}
CRON_H=${CRON_H:-0}
CRON_M=${CRON_M:-0}

CRON_LINE="$CRON_M $CRON_H * * * $BACKUP_SCRIPT --quiet --keep=14"
CRON_MARKER="# IPSSI_EXAMENS_BACKUP"

# ============================================================================
# Modes
# ============================================================================

case "$MODE" in
  show)
    echo ""
    echo "=== Installation du cron de backup quotidien ==="
    echo ""
    echo "Ligne à ajouter dans votre crontab (crontab -e) :"
    echo ""
    echo "$CRON_MARKER"
    echo "$CRON_LINE"
    echo ""
    echo "Explication :"
    echo "  - Quotidiennement à $(printf '%02d:%02d' "$CRON_H" "$CRON_M")"
    echo "  - Mode quiet (pas d'output si OK)"
    echo "  - Garde les 14 derniers backups"
    echo ""
    echo "Pour installer automatiquement :"
    echo "  $0 --install"
    echo ""
    echo "Pour tester manuellement :"
    echo "  $BACKUP_SCRIPT"
    echo ""
    ;;

  install)
    if ! command -v crontab >/dev/null 2>&1; then
      echo "ERROR: commande 'crontab' introuvable" >&2
      exit 1
    fi

    # Recuperer crontab existant
    EXISTING=$(crontab -l 2>/dev/null || echo "")

    if echo "$EXISTING" | grep -q "$CRON_MARKER"; then
      echo "Le cron IPSSI est déjà installé. Utilisez --remove pour supprimer avant."
      echo ""
      echo "Lignes actuelles :"
      echo "$EXISTING" | grep -A 1 "$CRON_MARKER" || true
      exit 0
    fi

    # Ajouter
    {
      echo "$EXISTING"
      echo ""
      echo "$CRON_MARKER"
      echo "$CRON_LINE"
    } | crontab -

    echo "✅ Cron installé : $(printf '%02d:%02d' "$CRON_H" "$CRON_M") quotidien"
    echo ""
    echo "Verifier : crontab -l"
    echo "Logs     : tail -f data/logs/backups.log"
    ;;

  remove)
    if ! command -v crontab >/dev/null 2>&1; then
      echo "ERROR: commande 'crontab' introuvable" >&2
      exit 1
    fi

    EXISTING=$(crontab -l 2>/dev/null || echo "")
    if ! echo "$EXISTING" | grep -q "$CRON_MARKER"; then
      echo "Le cron IPSSI n'est pas installé"
      exit 0
    fi

    # Supprimer le marker + la ligne suivante
    echo "$EXISTING" | awk -v marker="$CRON_MARKER" '
      $0 == marker { skip = 2; next }
      skip > 0 { skip--; next }
      { print }
    ' | crontab -

    echo "✅ Cron désinstallé"
    ;;
esac
