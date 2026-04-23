/* ============================================================================
   analytics-exports.jsx — Exports CSV + Excel + PDF

   Composants exportes :
     - ExportMenu : bouton dropdown avec 3 options
     - Utilitaires :
       * exportCSV(filename, rows, headers)
       * exportExcel(filename, sheets)   - SheetJS
       * exportPDF()                     - window.print

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useRef } = React;

  // ==========================================================================
  // UTILITAIRES EXPORTS
  // ==========================================================================

  /**
   * Escape valeur pour CSV (double les quotes, wrap si contient , " \n).
   */
  function csvEscape(val) {
    if (val === null || val === undefined) return '';
    const s = String(val);
    if (s.includes(',') || s.includes('"') || s.includes('\n') || s.includes('\r')) {
      return '"' + s.replace(/"/g, '""') + '"';
    }
    return s;
  }

  /**
   * Generer et declencher telechargement d'un CSV.
   * @param {string} filename - Nom du fichier (sans extension)
   * @param {Array<Array>} rows - Donnees [[col1, col2, ...], ...]
   * @param {Array<string>} headers - Optionnel : en-tetes
   */
  function exportCSV(filename, rows, headers) {
    const lines = [];
    if (headers && headers.length) {
      lines.push(headers.map(csvEscape).join(','));
    }
    for (const row of rows) {
      lines.push(row.map(csvEscape).join(','));
    }

    // BOM UTF-8 pour Excel Windows
    const content = '\uFEFF' + lines.join('\r\n');
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8' });
    triggerDownload(blob, filename + '.csv');
  }

  /**
   * Generer Excel .xlsx avec plusieurs feuilles.
   * @param {string} filename - Nom du fichier
   * @param {Array<{name: string, rows: Array<Array>, headers?: Array<string>}>} sheets
   */
  function exportExcel(filename, sheets) {
    if (!window.XLSX) {
      alert('⚠️ La bibliothèque XLSX n\'est pas chargée. Impossible d\'exporter en Excel.');
      return;
    }

    const wb = window.XLSX.utils.book_new();

    for (const sheet of sheets) {
      const data = [];
      if (sheet.headers && sheet.headers.length) {
        data.push(sheet.headers);
      }
      data.push(...sheet.rows);

      const ws = window.XLSX.utils.aoa_to_sheet(data);

      // Auto-width des colonnes (approximatif)
      if (data.length > 0) {
        const colWidths = data[0].map((_, colIdx) => {
          let maxLen = 10;
          for (const row of data) {
            const cell = String(row[colIdx] ?? '');
            if (cell.length > maxLen) maxLen = Math.min(cell.length, 50);
          }
          return { wch: maxLen + 2 };
        });
        ws['!cols'] = colWidths;
      }

      // Style headers (bold)
      if (sheet.headers && data.length > 0) {
        const range = window.XLSX.utils.decode_range(ws['!ref']);
        for (let col = range.s.c; col <= range.e.c; col++) {
          const addr = window.XLSX.utils.encode_cell({ r: 0, c: col });
          if (ws[addr]) {
            ws[addr].s = { font: { bold: true } };
          }
        }
      }

      const sheetName = (sheet.name || 'Feuille').slice(0, 31); // Max 31 chars
      window.XLSX.utils.book_append_sheet(wb, ws, sheetName);
    }

    window.XLSX.writeFile(wb, filename + '.xlsx');
  }

  /**
   * Declencher un print (conversion PDF via le navigateur).
   * Le CSS @media print s'occupe du formatage.
   */
  function exportPDF() {
    window.print();
  }

  /**
   * Utilitaire : declencher le download d'un blob.
   */
  function triggerDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }, 100);
  }

  /**
   * Formatter date pour nom de fichier.
   */
  function dateStamp() {
    const d = new Date();
    return d.getFullYear() +
      '-' + String(d.getMonth() + 1).padStart(2, '0') +
      '-' + String(d.getDate()).padStart(2, '0') +
      '_' + String(d.getHours()).padStart(2, '0') +
      'h' + String(d.getMinutes()).padStart(2, '0');
  }

  /**
   * Slug simple pour noms de fichiers.
   */
  function slugify(s) {
    return String(s || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-zA-Z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .toLowerCase()
      .slice(0, 50);
  }

  // ==========================================================================
  // BUILDERS : transforment les donnees API en rows pour exports
  // ==========================================================================

  /**
   * Passages d'un examen → rows CSV/Excel.
   */
  function buildPassagesRows(passages) {
    const headers = [
      'Passage ID', 'Date', 'Prénom', 'Nom', 'Email',
      'Score brut', 'Score max', 'Score %', 'Durée (s)', 'Status',
      'Anomalies', 'Events focus', 'Questions répondues',
    ];

    const rows = passages.map(p => [
      p.id,
      p.start_time,
      p.student_info?.prenom || '',
      p.student_info?.nom || '',
      p.student_info?.email || '',
      p.score_brut ?? '',
      p.score_max ?? '',
      p.score_pct ?? '',
      p.duration_sec ?? '',
      p.status,
      p.anomalies_count || 0,
      p.focus_events_count || 0,
      p.nb_answered || 0,
    ]);

    return { headers, rows };
  }

  /**
   * Stats par question → rows.
   */
  function buildQuestionsRows(questions) {
    const headers = [
      'Question ID', 'Énoncé', 'Difficulté', 'Type',
      'Total', 'Corrects', 'Non répondues', 'Taux réussite %',
      'A count', 'A %', 'A correct?',
      'B count', 'B %', 'B correct?',
      'C count', 'C %', 'C correct?',
      'D count', 'D %', 'D correct?',
    ];

    const rows = questions.map(q => {
      const opts = q.option_analysis || [];
      const row = [
        q.question_id,
        (q.enonce || '').replace(/\s+/g, ' ').slice(0, 200),
        q.difficulte || '',
        q.type || '',
        q.total || 0,
        q.correct || 0,
        q.not_answered || 0,
        q.success_rate_pct || 0,
      ];
      for (let i = 0; i < 4; i++) {
        const o = opts[i];
        row.push(o?.count ?? 0);
        row.push(o?.rate_pct ?? 0);
        row.push(o?.is_correct ? 'Oui' : 'Non');
      }
      return row;
    });

    return { headers, rows };
  }

  /**
   * Historique d'un étudiant → rows.
   */
  function buildStudentRows(passages) {
    const headers = [
      'Examen ID', 'Examen titre', 'Passage ID',
      'Date', 'Score brut', 'Score max', 'Score %',
      'Durée (s)', 'Status', 'Anomalies',
    ];

    const rows = passages.map(p => [
      p.examen_id,
      p.examen_titre || '',
      p.passage_id,
      p.start_time,
      p.score_brut ?? '',
      p.score_max ?? '',
      p.score_pct ?? '',
      p.duration_sec ?? '',
      p.status,
      p.anomalies_count || 0,
    ]);

    return { headers, rows };
  }

  // ==========================================================================
  // ExportMenu : composant dropdown avec 3 options
  // ==========================================================================

  function ExportMenu({
    getData,         // Function -> { csv?: {headers, rows}, excelSheets?: [...], filenameBase, title }
    disabled,
    label,
  }) {
    const [open, setOpen] = useState(false);
    const [exporting, setExporting] = useState(false);
    const menuRef = useRef(null);

    // Close on outside click
    useEffect(() => {
      function handleClick(e) {
        if (menuRef.current && !menuRef.current.contains(e.target)) {
          setOpen(false);
        }
      }
      if (open) {
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
      }
    }, [open]);

    async function handleExport(format) {
      setOpen(false);
      if (disabled) return;

      setExporting(true);
      try {
        const payload = await Promise.resolve(getData());
        if (!payload) {
          alert('Aucune donnée à exporter.');
          return;
        }

        const base = payload.filenameBase || 'analytics';
        const stamp = dateStamp();
        const filename = `${base}_${stamp}`;

        if (format === 'csv') {
          if (!payload.csv) {
            alert('Export CSV non disponible pour cette vue.');
            return;
          }
          exportCSV(filename, payload.csv.rows, payload.csv.headers);
        } else if (format === 'excel') {
          if (!payload.excelSheets || payload.excelSheets.length === 0) {
            // Fallback : utiliser le CSV
            if (payload.csv) {
              exportExcel(filename, [{
                name: 'Données',
                headers: payload.csv.headers,
                rows: payload.csv.rows,
              }]);
            } else {
              alert('Export Excel non disponible.');
              return;
            }
          } else {
            exportExcel(filename, payload.excelSheets);
          }
        } else if (format === 'pdf') {
          exportPDF();
        }
      } catch (e) {
        console.error('Erreur export:', e);
        alert('Erreur lors de l\'export : ' + (e.message || 'inconnue'));
      } finally {
        setExporting(false);
      }
    }

    const btnLabel = label || 'Exporter';

    return (
      <div className="export-menu-wrap print-hidden" ref={menuRef}>
        <button
          onClick={() => setOpen(!open)}
          disabled={disabled || exporting}
          style={{
            padding: '8px 16px',
            background: 'var(--color-bg-elevated)',
            border: '1px solid var(--color-border)',
            borderRadius: 'var(--radius-md)',
            cursor: disabled ? 'not-allowed' : 'pointer',
            fontSize: 13,
            fontWeight: 600,
            color: 'var(--color-text)',
            display: 'inline-flex',
            alignItems: 'center',
            gap: 6,
            transition: 'all 0.15s',
          }}
        >
          📤 {exporting ? 'Export...' : btnLabel} <span style={{ fontSize: 10 }}>▼</span>
        </button>

        {open && !exporting && (
          <div className="export-dropdown">
            <button
              className="export-item"
              onClick={() => handleExport('csv')}
            >
              <span className="export-item-icon">📄</span>
              <span>
                <span className="export-item-label">CSV</span>
                <span className="export-item-sub">Données brutes (Excel, Google Sheets)</span>
              </span>
            </button>
            <button
              className="export-item"
              onClick={() => handleExport('excel')}
            >
              <span className="export-item-icon">📊</span>
              <span>
                <span className="export-item-label">Excel (.xlsx)</span>
                <span className="export-item-sub">Feuilles multiples avec mise en forme</span>
              </span>
            </button>
            <button
              className="export-item"
              onClick={() => handleExport('pdf')}
            >
              <span className="export-item-icon">📑</span>
              <span>
                <span className="export-item-label">PDF (impression)</span>
                <span className="export-item-sub">Via la boîte de dialogue d'impression</span>
              </span>
            </button>
          </div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Exports
  // ==========================================================================

  root.ExportMenu = ExportMenu;
  root.analyticsExports = {
    exportCSV,
    exportExcel,
    exportPDF,
    buildPassagesRows,
    buildQuestionsRows,
    buildStudentRows,
    dateStamp,
    slugify,
  };

})(window);
