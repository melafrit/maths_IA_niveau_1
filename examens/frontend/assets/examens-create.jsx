/* ============================================================================
   examens-create.jsx — Formulaire de création/édition d'examen

   Plateforme d'examens IPSSI — Phase P6.3

   Fonctionnalités :
     - Mode Create (nouveau) / Edit (charger un examen)
     - Formulaire complet avec toutes les options
     - Sélecteur de questions depuis la banque (via GET /api/banque/questions)
     - Validation client temps réel
     - Auto-save draft localStorage

   Composant exporté : window.ExamensCreate

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useMemo } = React;

  const MathText = root.MathText;
  const LevelBadge = root.LevelBadge;

  const DRAFT_KEY = 'examens_create_draft';

  function emptyExam() {
    // Date par défaut : demain 9h
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(9, 0, 0, 0);
    const end = new Date(tomorrow);
    end.setHours(11, 0, 0, 0);

    return {
      titre: '',
      description: '',
      questions: [],
      duree_sec: 3600,
      date_ouverture: toDateTimeLocal(tomorrow),
      date_cloture: toDateTimeLocal(end),
      max_passages: 1,
      shuffle_questions: true,
      shuffle_options: true,
      show_correction_after: true,
      correction_delay_min: 0,
    };
  }

  // datetime-local input value : "YYYY-MM-DDTHH:mm"
  function toDateTimeLocal(date) {
    const pad = n => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }

  // datetime-local value → ISO 8601 for API
  function toISO(datetimeLocal) {
    if (!datetimeLocal) return '';
    const d = new Date(datetimeLocal);
    if (isNaN(d.getTime())) return '';
    return d.toISOString();
  }

  // ISO → datetime-local (pour charger en édit)
  function fromISO(iso) {
    if (!iso) return '';
    try {
      return toDateTimeLocal(new Date(iso));
    } catch { return ''; }
  }

  function validateExam(exam) {
    const errors = {};
    if (!exam.titre?.trim()) errors.titre = 'Titre obligatoire';
    if (exam.titre && exam.titre.length > 200) errors.titre = 'Max 200 caractères';

    if (!exam.questions || exam.questions.length === 0) {
      errors.questions = 'Au moins 1 question requise';
    }

    const duree = parseInt(exam.duree_sec, 10);
    if (!duree || duree < 60) errors.duree_sec = 'Minimum 60 secondes';
    if (duree > 4 * 3600) errors.duree_sec = 'Maximum 4 heures';

    if (!exam.date_ouverture) errors.date_ouverture = 'Date d\'ouverture requise';
    if (!exam.date_cloture) errors.date_cloture = 'Date de clôture requise';

    if (exam.date_ouverture && exam.date_cloture) {
      const open = new Date(exam.date_ouverture).getTime();
      const close = new Date(exam.date_cloture).getTime();
      if (close <= open) errors.date_cloture = 'Doit être après la date d\'ouverture';
      const diffMin = (close - open) / 60000;
      if (diffMin < 5) errors.date_cloture = 'Fenêtre minimale : 5 minutes';
    }

    const np = parseInt(exam.max_passages, 10);
    if (!np || np < 1 || np > 10) errors.max_passages = 'Entre 1 et 10';

    return errors;
  }

  // ==========================================================================
  // Modal sélecteur de questions (depuis banque)
  // ==========================================================================

  function QuestionSelector({ selectedIds, onChange, onClose }) {
    const { Button, Input, Select, Modal } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();

    const [allQuestions, setAllQuestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [filters, setFilters] = useState({ chapitre: '', difficulte: '', type: '', search: '' });
    const [availableChapitres, setAvailableChapitres] = useState([]);

    useEffect(() => {
      (async () => {
        setLoading(true);
        const res = await api.request('GET', '/api/banque/maths-ia/chapitres');
        if (res.ok) setAvailableChapitres(res.data?.chapitres || []);

        const res2 = await api.request('GET', '/api/banque/questions?limit=1000');
        if (res2.ok) setAllQuestions(res2.data?.questions || []);
        setLoading(false);
      })();
    }, []);

    const filtered = useMemo(() => {
      return allQuestions.filter(q => {
        if (filters.chapitre && q._chapitre !== filters.chapitre) return false;
        if (filters.difficulte && q.difficulte !== filters.difficulte) return false;
        if (filters.type && q.type !== filters.type) return false;
        if (filters.search) {
          const s = filters.search.toLowerCase();
          if (!q.enonce?.toLowerCase().includes(s) &&
              !q.id?.toLowerCase().includes(s)) return false;
        }
        return true;
      });
    }, [allQuestions, filters]);

    function toggleQuestion(qId) {
      if (selectedIds.includes(qId)) {
        onChange(selectedIds.filter(id => id !== qId));
      } else {
        onChange([...selectedIds, qId]);
      }
    }

    return (
      <Modal isOpen={true} onClose={onClose} title="📚 Sélectionner des questions" size="lg">
        {/* Filtres */}
        <div style={{
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          marginBottom: 'var(--space-3)',
          display: 'grid',
          gridTemplateColumns: 'repeat(4, 1fr)',
          gap: 8,
        }}>
          <Input
            placeholder="🔍 Recherche..."
            value={filters.search}
            onChange={e => setFilters(f => ({ ...f, search: e.target.value }))}
          />
          <Select
            value={filters.chapitre}
            onChange={e => setFilters(f => ({ ...f, chapitre: e.target.value }))}
            options={[
              { value: '', label: '— Tous chapitres —' },
              ...availableChapitres.map(c => ({ value: c, label: c })),
            ]}
          />
          <Select
            value={filters.difficulte}
            onChange={e => setFilters(f => ({ ...f, difficulte: e.target.value }))}
            options={[
              { value: '', label: '— Toutes difficultés —' },
              { value: 'facile', label: '🟢 Facile' },
              { value: 'moyen', label: '🟡 Moyen' },
              { value: 'difficile', label: '🟠 Difficile' },
              { value: 'expert', label: '🔴 Expert' },
            ]}
          />
          <Select
            value={filters.type}
            onChange={e => setFilters(f => ({ ...f, type: e.target.value }))}
            options={[
              { value: '', label: '— Tous types —' },
              { value: 'conceptuel', label: 'conceptuel' },
              { value: 'calcul', label: 'calcul' },
              { value: 'code', label: 'code' },
              { value: 'formule', label: 'formule' },
            ]}
          />
        </div>

        {/* Info */}
        <div style={{
          fontSize: 13,
          marginBottom: 'var(--space-2)',
          padding: '0 4px',
          display: 'flex',
          justifyContent: 'space-between',
        }}>
          <span>
            {loading ? '⏳ Chargement...' : (
              <>
                <strong>{filtered.length}</strong> question{filtered.length > 1 ? 's' : ''} affichée{filtered.length > 1 ? 's' : ''}
              </>
            )}
          </span>
          <span style={{ color: 'var(--color-primary)', fontWeight: 600 }}>
            ✓ {selectedIds.length} sélectionnée{selectedIds.length > 1 ? 's' : ''}
          </span>
        </div>

        {/* Liste */}
        <div style={{ maxHeight: 400, overflow: 'auto', paddingRight: 4 }}>
          {filtered.map(q => {
            const selected = selectedIds.includes(q.id);
            return (
              <div
                key={q.id}
                className={`question-picker-item ${selected ? 'selected' : ''}`}
                onClick={() => toggleQuestion(q.id)}
                style={{ cursor: 'pointer' }}
              >
                <input
                  type="checkbox"
                  checked={selected}
                  onChange={() => toggleQuestion(q.id)}
                  onClick={e => e.stopPropagation()}
                  style={{ marginTop: 2, flexShrink: 0 }}
                />
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 4, flexWrap: 'wrap' }}>
                    <code style={{ fontSize: 11, fontWeight: 600 }}>{q.id}</code>
                    <LevelBadge level={q.difficulte} />
                    <span style={{ fontSize: 10, color: 'var(--color-text-muted)' }}>{q.type}</span>
                    <span style={{ fontSize: 10, color: 'var(--color-text-muted)' }}>
                      · {q._chapitre}
                    </span>
                  </div>
                  <div style={{ fontSize: 12, lineHeight: 1.4 }}>
                    <MathText
                      text={q.enonce?.length > 150 ? q.enonce.substring(0, 150) + '...' : q.enonce}
                      as="span"
                    />
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 'var(--space-3)' }}>
          <Button variant="ghost" onClick={onClose}>Fermer</Button>
          <Button variant="primary" onClick={onClose}>
            ✅ Valider ({selectedIds.length})
          </Button>
        </div>
      </Modal>
    );
  }

  // ==========================================================================
  // Champ avec label + erreur
  // ==========================================================================

  function Field({ label, error, hint, children, required }) {
    return (
      <div style={{ marginBottom: 'var(--space-3)' }}>
        <label style={{
          display: 'block',
          fontSize: 'var(--text-sm)',
          fontWeight: 600,
          marginBottom: 4,
        }}>
          {label} {required && <span style={{ color: '#dc2626' }}>*</span>}
        </label>
        {children}
        {hint && !error && (
          <div style={{ fontSize: 11, color: 'var(--color-text-muted)', marginTop: 2 }}>{hint}</div>
        )}
        {error && (
          <div style={{ fontSize: 11, color: '#dc2626', marginTop: 2, fontWeight: 500 }}>⚠️ {error}</div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Composant principal
  // ==========================================================================

  function ExamensCreate({ editingExamen, onSaved, onCancel }) {
    const { Button, Input, Textarea, Select, Checkbox, Box, useToast } = root.UI;
    const { useApi } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    const [exam, setExam] = useState(() => {
      if (editingExamen) {
        return {
          titre: editingExamen.titre || '',
          description: editingExamen.description || '',
          questions: editingExamen.questions || [],
          duree_sec: editingExamen.duree_sec || 3600,
          date_ouverture: fromISO(editingExamen.date_ouverture),
          date_cloture: fromISO(editingExamen.date_cloture),
          max_passages: editingExamen.max_passages || 1,
          shuffle_questions: editingExamen.shuffle_questions !== false,
          shuffle_options: editingExamen.shuffle_options !== false,
          show_correction_after: editingExamen.show_correction_after !== false,
          correction_delay_min: editingExamen.correction_delay_min || 0,
        };
      }

      // Tentative de restauration du draft
      try {
        const saved = localStorage.getItem(DRAFT_KEY);
        if (saved) {
          const d = JSON.parse(saved);
          const age = (Date.now() - new Date(d.savedAt).getTime()) / 60000;
          if (age < 60) return d.exam;
        }
      } catch {}
      return emptyExam();
    });

    const [showPicker, setShowPicker] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [selectedQuestionsData, setSelectedQuestionsData] = useState([]);

    const errors = useMemo(() => validateExam(exam), [exam]);
    const isValid = Object.keys(errors).length === 0;
    const isEdit = !!editingExamen;

    // Auto-save draft (create mode only)
    useEffect(() => {
      if (isEdit) return;
      if (!exam.titre) return;
      try {
        localStorage.setItem(DRAFT_KEY, JSON.stringify({ exam, savedAt: new Date().toISOString() }));
      } catch {}
    }, [exam, isEdit]);

    // Charger les données complètes des questions sélectionnées
    useEffect(() => {
      (async () => {
        if (exam.questions.length === 0) {
          setSelectedQuestionsData([]);
          return;
        }
        const fetched = [];
        for (const qId of exam.questions) {
          const res = await api.request('GET', `/api/banque/questions/${qId}`);
          if (res.ok && res.data?.question) {
            fetched.push(res.data.question);
          }
        }
        setSelectedQuestionsData(fetched);
      })();
    }, [exam.questions.join(',')]);

    function setField(key, value) {
      setExam(e => ({ ...e, [key]: value }));
    }

    function removeQuestion(qId) {
      setField('questions', exam.questions.filter(id => id !== qId));
    }

    async function handleSubmit() {
      if (!isValid) {
        toast({ title: 'Validation échouée', message: `${Object.keys(errors).length} erreur(s)`, type: 'error' });
        return;
      }

      setSubmitting(true);

      const payload = {
        titre: exam.titre.trim(),
        description: exam.description.trim(),
        questions: exam.questions,
        duree_sec: parseInt(exam.duree_sec, 10),
        date_ouverture: toISO(exam.date_ouverture),
        date_cloture: toISO(exam.date_cloture),
        max_passages: parseInt(exam.max_passages, 10),
        shuffle_questions: !!exam.shuffle_questions,
        shuffle_options: !!exam.shuffle_options,
        show_correction_after: !!exam.show_correction_after,
        correction_delay_min: parseInt(exam.correction_delay_min, 10) || 0,
      };

      let res;
      if (isEdit) {
        res = await api.request('PUT', `/api/examens/${editingExamen.id}`, { updates: payload });
      } else {
        res = await api.request('POST', '/api/examens', payload);
      }

      setSubmitting(false);

      if (res.ok) {
        toast({
          title: isEdit ? 'Examen modifié' : 'Examen créé',
          message: res.data?.examen?.titre || '',
          type: 'success',
        });
        if (!isEdit) {
          try { localStorage.removeItem(DRAFT_KEY); } catch {}
        }
        if (onSaved) onSaved(res.data?.examen);
      } else {
        toast({
          title: 'Erreur',
          message: res.error?.message || 'Impossible de sauvegarder',
          type: 'error',
        });
      }
    }

    // Durée rapide : 15/30/60/90/120 minutes
    const quickDurees = [
      { label: '15 min', value: 15 * 60 },
      { label: '30 min', value: 30 * 60 },
      { label: '1h', value: 60 * 60 },
      { label: '1h30', value: 90 * 60 },
      { label: '2h', value: 120 * 60 },
    ];

    // ==========================================================================
    // Render
    // ==========================================================================

    return (
      <div>
        <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
          {isEdit ? '✏️ Modifier l\'examen' : '➕ Nouvel examen'}
          {isEdit && <code style={{ fontSize: 13, fontWeight: 400, color: 'var(--color-text-muted)' }}>{editingExamen.id}</code>}
        </h3>

        {/* Infos générales */}
        <div className="form-section">
          <h4 className="form-section-title">📝 Informations générales</h4>

          <Field label="Titre" required error={errors.titre}>
            <Input
              value={exam.titre}
              onChange={e => setField('titre', e.target.value)}
              placeholder="ex: Contrôle continu Maths IA - Printemps 2026"
            />
          </Field>

          <Field label="Description" hint="Instructions pour les étudiants (optionnel)">
            <Textarea
              value={exam.description}
              onChange={e => setField('description', e.target.value)}
              rows={3}
              placeholder="Description de l'examen, consignes particulières..."
            />
          </Field>
        </div>

        {/* Questions */}
        <div className="form-section">
          <h4 className="form-section-title">
            📚 Questions ({exam.questions.length})
          </h4>

          {errors.questions && (
            <Box type="error" style={{ marginBottom: 8 }}>{errors.questions}</Box>
          )}

          <Button variant="secondary" onClick={() => setShowPicker(true)}>
            📚 Choisir dans la banque ({exam.questions.length} sélectionnée{exam.questions.length > 1 ? 's' : ''})
          </Button>

          {selectedQuestionsData.length > 0 && (
            <div style={{ marginTop: 'var(--space-3)', maxHeight: 300, overflow: 'auto' }}>
              {selectedQuestionsData.map((q, i) => (
                <div key={q.id} style={{
                  padding: '6px 10px',
                  background: 'var(--color-bg-subtle)',
                  borderRadius: 4,
                  marginBottom: 4,
                  display: 'flex',
                  alignItems: 'center',
                  gap: 8,
                  fontSize: 12,
                }}>
                  <span style={{
                    background: 'var(--color-primary)',
                    color: 'white',
                    padding: '1px 6px',
                    borderRadius: 3,
                    fontSize: 10,
                    fontWeight: 600,
                  }}>{i + 1}</span>
                  <code>{q.id}</code>
                  <LevelBadge level={q.difficulte} />
                  <span style={{ flex: 1, color: 'var(--color-text-muted)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {q.enonce?.substring(0, 80)}...
                  </span>
                  <button
                    onClick={() => removeQuestion(q.id)}
                    style={{
                      background: 'none',
                      border: 'none',
                      cursor: 'pointer',
                      color: '#dc2626',
                      fontSize: 14,
                    }}
                    title="Retirer"
                  >✕</button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Durée et dates */}
        <div className="form-section">
          <h4 className="form-section-title">⏱️ Durée et fenêtre d'ouverture</h4>

          <Field label="Durée" required error={errors.duree_sec} hint="En minutes (entre 1 et 240)">
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 8 }}>
              <Input
                type="number"
                value={Math.floor(exam.duree_sec / 60)}
                onChange={e => setField('duree_sec', (parseInt(e.target.value) || 0) * 60)}
                min={1}
                max={240}
                style={{ width: 100 }}
              />
              <span style={{ color: 'var(--color-text-muted)' }}>minutes</span>
              <div style={{ display: 'flex', gap: 4, marginLeft: 8 }}>
                {quickDurees.map(qd => (
                  <button
                    key={qd.value}
                    onClick={() => setField('duree_sec', qd.value)}
                    style={{
                      padding: '2px 8px',
                      fontSize: 11,
                      background: exam.duree_sec === qd.value ? 'var(--color-primary)' : 'var(--color-bg-elevated)',
                      color: exam.duree_sec === qd.value ? 'white' : 'var(--color-text)',
                      border: '1px solid var(--color-border)',
                      borderRadius: 4,
                      cursor: 'pointer',
                    }}
                  >{qd.label}</button>
                ))}
              </div>
            </div>
          </Field>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <Field label="Date d'ouverture" required error={errors.date_ouverture}>
              <Input
                type="datetime-local"
                value={exam.date_ouverture}
                onChange={e => setField('date_ouverture', e.target.value)}
              />
            </Field>

            <Field label="Date de clôture" required error={errors.date_cloture}>
              <Input
                type="datetime-local"
                value={exam.date_cloture}
                onChange={e => setField('date_cloture', e.target.value)}
              />
            </Field>
          </div>
        </div>

        {/* Options */}
        <div className="form-section">
          <h4 className="form-section-title">⚙️ Options avancées</h4>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <Field label="Passages max par étudiant" error={errors.max_passages}>
              <Input
                type="number"
                value={exam.max_passages}
                onChange={e => setField('max_passages', parseInt(e.target.value) || 1)}
                min={1}
                max={10}
              />
            </Field>

            <Field label="Délai avant correction (min)" hint="0 = immédiat après soumission">
              <Input
                type="number"
                value={exam.correction_delay_min}
                onChange={e => setField('correction_delay_min', parseInt(e.target.value) || 0)}
                min={0}
                max={10080}
              />
            </Field>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginTop: 12 }}>
            <label style={{ display: 'flex', gap: 8, alignItems: 'center', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={exam.shuffle_questions}
                onChange={e => setField('shuffle_questions', e.target.checked)}
              />
              <span style={{ fontSize: 13 }}>Mélanger les questions (ordre différent par étudiant)</span>
            </label>
            <label style={{ display: 'flex', gap: 8, alignItems: 'center', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={exam.shuffle_options}
                onChange={e => setField('shuffle_options', e.target.checked)}
              />
              <span style={{ fontSize: 13 }}>Mélanger les options (A/B/C/D différents par étudiant)</span>
            </label>
            <label style={{ display: 'flex', gap: 8, alignItems: 'center', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={exam.show_correction_after}
                onChange={e => setField('show_correction_after', e.target.checked)}
              />
              <span style={{ fontSize: 13 }}>Afficher la correction à l'étudiant après soumission</span>
            </label>
          </div>
        </div>

        {/* Validation status + actions */}
        {!isValid && (
          <Box type="warning" style={{ marginBottom: 12 }}>
            ⚠️ <strong>{Object.keys(errors).length}</strong> champ(s) à corriger : {Object.keys(errors).join(', ')}
          </Box>
        )}

        <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
          <Button variant="ghost" onClick={onCancel} disabled={submitting}>
            Annuler
          </Button>
          <Button
            variant="primary"
            onClick={handleSubmit}
            disabled={!isValid || submitting}
          >
            {submitting ? '⏳ Sauvegarde...' : (isEdit ? '✅ Enregistrer modifications' : '✅ Créer l\'examen')}
          </Button>
        </div>

        {/* Modal sélecteur de questions */}
        {showPicker && (
          <QuestionSelector
            selectedIds={exam.questions}
            onChange={ids => setField('questions', ids)}
            onClose={() => setShowPicker(false)}
          />
        )}
      </div>
    );
  }

  root.ExamensCreate = ExamensCreate;

})(window);
