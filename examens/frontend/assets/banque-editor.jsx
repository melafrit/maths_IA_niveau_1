/* ============================================================================
   banque-editor.jsx — Éditeur CRUD de questions

   Plateforme d'examens IPSSI — Phase P5.2

   Fonctionnalités :
     - Mode Create : nouveau formulaire vide
     - Mode Edit : chargement + modification d'une question existante
     - Mode Delete : confirmation + suppression
     - Validation client temps réel (11 champs)
     - Aperçu KaTeX live (split-view)
     - Auto-save draft dans localStorage

   Composant exporté : window.BanqueEditor

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useCallback, useMemo, useRef } = React;

  // Récupérer MathText du Browser (déjà chargé)
  const MathText = root.MathText;
  const LevelBadge = root.LevelBadge;

  // Constantes (miroir de BanqueManager::LEVELS, TYPES, REQUIRED_FIELDS)
  const LEVELS = ['facile', 'moyen', 'difficile', 'expert'];
  const TYPES = ['conceptuel', 'calcul', 'code', 'formule'];
  const DRAFT_KEY = 'banque_editor_draft';

  // Question vide (template)
  function emptyQuestion() {
    return {
      id: '',
      enonce: '',
      options: ['', '', '', ''],
      correct: 0,
      difficulte: 'facile',
      type: 'conceptuel',
      tags: [],
      hint: '',
      explanation: '',
      traps: '',
      references: '',
    };
  }

  // ==========================================================================
  // Validation client (miroir de BanqueManager::validateQuestion)
  // ==========================================================================

  function validateQuestion(q) {
    const errors = {};

    // ID format
    if (!q.id || q.id.trim() === '') {
      errors.id = 'ID obligatoire';
    } else if (!/^[a-z0-9]{3}-[a-z]{4}-\d{2,}$/.test(q.id)) {
      errors.id = 'Format attendu : xxx-yyyy-NN (ex: vec-faci-01)';
    }

    // Énoncé
    if (!q.enonce || q.enonce.trim() === '') {
      errors.enonce = 'Énoncé obligatoire';
    }

    // Options
    if (!Array.isArray(q.options) || q.options.length !== 4) {
      errors.options = 'Exactement 4 options requises';
    } else {
      const emptyIdx = q.options.findIndex(o => !o || o.trim() === '');
      if (emptyIdx !== -1) {
        errors.options = `Option ${String.fromCharCode(65 + emptyIdx)} vide`;
      }
    }

    // Correct
    if (typeof q.correct !== 'number' || q.correct < 0 || q.correct > 3) {
      errors.correct = 'Doit être 0, 1, 2 ou 3';
    }

    // Difficulté
    if (!LEVELS.includes(q.difficulte)) {
      errors.difficulte = 'Niveau invalide';
    }

    // Type
    if (!TYPES.includes(q.type)) {
      errors.type = 'Type invalide';
    }

    // Tags
    if (!Array.isArray(q.tags)) {
      errors.tags = 'Doit être une liste';
    }

    // Strings obligatoires
    ['hint', 'explanation', 'traps', 'references'].forEach(field => {
      if (!q[field] || q[field].trim() === '') {
        errors[field] = 'Champ obligatoire';
      }
    });

    return errors;
  }

  // ==========================================================================
  // Champ de formulaire avec label + erreur
  // ==========================================================================

  function Field({ label, error, hint, children, required = false }) {
    return (
      <div style={{ marginBottom: 'var(--space-4)' }}>
        <label style={{
          display: 'block',
          fontSize: 'var(--text-sm)',
          fontWeight: 600,
          marginBottom: 'var(--space-1)',
          color: 'var(--color-text)',
        }}>
          {label} {required && <span style={{ color: '#dc2626' }}>*</span>}
        </label>
        {children}
        {hint && !error && (
          <div style={{
            fontSize: 'var(--text-xs)',
            color: 'var(--color-text-muted)',
            marginTop: 4,
          }}>{hint}</div>
        )}
        {error && (
          <div style={{
            fontSize: 'var(--text-xs)',
            color: '#dc2626',
            marginTop: 4,
            fontWeight: 500,
          }}>⚠️ {error}</div>
        )}
      </div>
    );
  }

  // ==========================================================================
  // Éditeur principal
  // ==========================================================================

  function BanqueEditor() {
    const { Button, Input, Textarea, Select, Box, Modal, useToast } = root.UI;
    const { useApi, useDebounce } = root.UIHooks;
    const api = useApi();
    const { toast } = useToast();

    // État principal
    const [mode, setMode] = useState('create'); // 'create' | 'edit' | 'delete'
    const [question, setQuestion] = useState(emptyQuestion());
    const [location, setLocation] = useState({
      module: 'maths-ia',
      chapitre: 'j1-representation',
      theme: 'vecteurs',
    });
    const [availableChapitres, setAvailableChapitres] = useState([]);
    const [availableThemes, setAvailableThemes] = useState([]);
    const [loadId, setLoadId] = useState(''); // ID pour mode edit
    const [tagInput, setTagInput] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);

    // Validation continue
    const errors = useMemo(() => validateQuestion(question), [question]);
    const isValid = Object.keys(errors).length === 0;

    // Debounce pour l'auto-save draft
    const debouncedQuestion = useDebounce(question, 1000);

    // Charger les chapitres quand le module change
    useEffect(() => {
      async function fetch() {
        const res = await api.request('GET', `/api/banque/${location.module}/chapitres`);
        if (res.ok) {
          setAvailableChapitres(res.data?.chapitres || []);
        }
      }
      if (location.module) fetch();
    }, [location.module]);

    // Charger les thèmes quand le chapitre change
    useEffect(() => {
      async function fetch() {
        const res = await api.request('GET', `/api/banque/${location.module}/${location.chapitre}/themes`);
        if (res.ok) {
          setAvailableThemes(res.data?.themes || []);
        }
      }
      if (location.module && location.chapitre) fetch();
    }, [location.module, location.chapitre]);

    // Auto-save draft dans localStorage
    useEffect(() => {
      if (mode === 'create' && debouncedQuestion.enonce) {
        try {
          localStorage.setItem(DRAFT_KEY, JSON.stringify({
            question: debouncedQuestion,
            location,
            savedAt: new Date().toISOString(),
          }));
        } catch (e) {
          // localStorage peut être bloqué
        }
      }
    }, [debouncedQuestion, location, mode]);

    // Restaurer un draft au mount (mode create)
    useEffect(() => {
      if (mode === 'create') {
        try {
          const saved = localStorage.getItem(DRAFT_KEY);
          if (saved) {
            const data = JSON.parse(saved);
            if (data.question && data.question.enonce) {
              const age = (Date.now() - new Date(data.savedAt).getTime()) / 1000 / 60;
              if (age < 60) { // draft < 1h
                // On propose de restaurer via toast
                setTimeout(() => {
                  if (confirm(`Un brouillon de question non sauvegardé a été trouvé (il y a ${Math.round(age)} min). Le restaurer ?`)) {
                    setQuestion(data.question);
                    if (data.location) setLocation(data.location);
                  }
                }, 500);
              }
            }
          }
        } catch (e) {
          // ignore
        }
      }
    }, []); // run once at mount

    // Helper pour modifier un champ
    function setField(key, value) {
      setQuestion(q => ({ ...q, [key]: value }));
    }

    function setOption(i, value) {
      setQuestion(q => {
        const newOpts = [...q.options];
        newOpts[i] = value;
        return { ...q, options: newOpts };
      });
    }

    function addTag() {
      const t = tagInput.trim().toLowerCase();
      if (t && !question.tags.includes(t)) {
        setField('tags', [...question.tags, t]);
      }
      setTagInput('');
    }

    function removeTag(tag) {
      setField('tags', question.tags.filter(x => x !== tag));
    }

    // ==========================================================================
    // Actions : Charger / Créer / Modifier / Supprimer
    // ==========================================================================

    async function loadQuestion() {
      if (!loadId.trim()) {
        toast({ title: 'ID manquant', message: 'Saisissez un ID pour charger', type: 'warning' });
        return;
      }
      setSubmitting(true);
      const res = await api.request('GET', `/api/banque/questions/${loadId.trim()}`);
      setSubmitting(false);

      if (res.ok && res.data?.question) {
        const q = res.data.question;
        setQuestion({
          id: q.id,
          enonce: q.enonce,
          options: q.options || ['', '', '', ''],
          correct: q.correct,
          difficulte: q.difficulte,
          type: q.type,
          tags: q.tags || [],
          hint: q.hint,
          explanation: q.explanation,
          traps: q.traps,
          references: q.references,
        });
        setLocation({
          module: q._module,
          chapitre: q._chapitre,
          theme: q._theme,
        });
        setMode('edit');
        toast({ title: 'Question chargée', message: q.id, type: 'success' });
      } else {
        toast({
          title: 'Question introuvable',
          message: res.error?.message || `Aucune question avec ID ${loadId}`,
          type: 'error',
        });
      }
    }

    async function submitCreate() {
      if (!isValid) {
        toast({ title: 'Validation échouée', message: 'Corrigez les erreurs avant de soumettre', type: 'error' });
        return;
      }
      setSubmitting(true);
      const res = await api.request('POST', '/api/banque/questions', {
        module: location.module,
        chapitre: location.chapitre,
        theme: location.theme,
        question,
      });
      setSubmitting(false);

      if (res.ok) {
        toast({ title: 'Question créée !', message: question.id, type: 'success' });
        try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
        setQuestion(emptyQuestion());
      } else {
        const err = res.error;
        if (err?.code === 'conflict') {
          toast({ title: 'ID déjà existant', message: 'Choisissez un autre ID unique', type: 'error' });
        } else {
          toast({
            title: 'Erreur création',
            message: err?.message || 'Impossible de créer',
            type: 'error',
          });
        }
      }
    }

    async function submitUpdate() {
      if (!isValid) {
        toast({ title: 'Validation échouée', message: 'Corrigez les erreurs avant de soumettre', type: 'error' });
        return;
      }
      setSubmitting(true);
      // On retire id des updates (immutable côté serveur)
      const { id, ...updates } = question;
      const res = await api.request('PUT', `/api/banque/questions/${id}`, { updates });
      setSubmitting(false);

      if (res.ok) {
        toast({ title: 'Question modifiée !', message: id, type: 'success' });
      } else {
        toast({
          title: 'Erreur modification',
          message: res.error?.message || 'Impossible de modifier',
          type: 'error',
        });
      }
    }

    async function submitDelete() {
      setDeleteModalOpen(false);
      setSubmitting(true);
      const res = await api.request('DELETE', `/api/banque/questions/${question.id}`);
      setSubmitting(false);

      if (res.ok) {
        toast({ title: 'Question supprimée', message: question.id, type: 'success' });
        resetForm();
      } else {
        toast({
          title: 'Erreur suppression',
          message: res.error?.message || 'Impossible de supprimer',
          type: 'error',
        });
      }
    }

    function resetForm() {
      setQuestion(emptyQuestion());
      setMode('create');
      setLoadId('');
      try { localStorage.removeItem(DRAFT_KEY); } catch(e) {}
    }

    // ==========================================================================
    // Render
    // ==========================================================================

    const letters = ['A', 'B', 'C', 'D'];
    const isCreate = mode === 'create';
    const isEdit = mode === 'edit';

    return (
      <div>
        {/* Header : choix du mode */}
        <div style={{
          marginBottom: 'var(--space-4)',
          padding: 'var(--space-3)',
          background: 'var(--color-bg-subtle)',
          borderRadius: 'var(--radius-md)',
          display: 'flex',
          gap: 'var(--space-3)',
          alignItems: 'flex-end',
          flexWrap: 'wrap',
        }}>
          <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
            <Button
              variant={isCreate ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => { resetForm(); }}
            >
              ➕ Créer
            </Button>
            <Button
              variant={isEdit ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setMode('edit')}
            >
              ✏️ Modifier
            </Button>
          </div>

          {/* Zone pour charger une question */}
          {isEdit && (
            <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'flex-end', flex: 1 }}>
              <div style={{ flex: 1, maxWidth: 280 }}>
                <label style={{
                  fontSize: 'var(--text-xs)',
                  fontWeight: 600,
                  color: 'var(--color-text-muted)',
                  display: 'block',
                  marginBottom: 4,
                }}>
                  ID question à charger
                </label>
                <Input
                  placeholder="ex: vec-faci-01"
                  value={loadId}
                  onChange={e => setLoadId(e.target.value)}
                  onKeyDown={e => { if (e.key === 'Enter') loadQuestion(); }}
                />
              </div>
              <Button variant="secondary" size="sm" onClick={loadQuestion} disabled={submitting}>
                🔍 Charger
              </Button>
              {question.id && (
                <Button
                  variant="danger"
                  size="sm"
                  onClick={() => setDeleteModalOpen(true)}
                  disabled={submitting}
                >
                  🗑️ Supprimer cette question
                </Button>
              )}
            </div>
          )}
        </div>

        {/* Split view : formulaire | preview */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)',
          gap: 'var(--space-5)',
          alignItems: 'flex-start',
        }}>
          {/* === Colonne gauche : Formulaire === */}
          <div>
            <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
              📝 {isCreate ? 'Nouvelle question' : 'Modifier question'}
            </h3>

            {/* Localisation (create only) */}
            {isCreate && (
              <div style={{
                padding: 'var(--space-3)',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                marginBottom: 'var(--space-4)',
                borderLeft: '3px solid var(--color-primary)',
              }}>
                <div style={{
                  fontSize: 'var(--text-sm)',
                  fontWeight: 600,
                  marginBottom: 'var(--space-2)',
                }}>📍 Localisation</div>

                <Field label="Module" required>
                  <Input
                    value={location.module}
                    onChange={e => setLocation(l => ({ ...l, module: e.target.value }))}
                  />
                </Field>

                <Field label="Chapitre" required hint={availableChapitres.length ? `Disponibles : ${availableChapitres.join(', ')}` : null}>
                  <Select
                    value={location.chapitre}
                    onChange={e => setLocation(l => ({ ...l, chapitre: e.target.value }))}
                    options={availableChapitres.map(c => ({ value: c, label: c }))}
                  />
                </Field>

                <Field label="Thème" required>
                  <Select
                    value={location.theme}
                    onChange={e => setLocation(l => ({ ...l, theme: e.target.value }))}
                    options={availableThemes.map(t => ({ value: t, label: t }))}
                  />
                </Field>
              </div>
            )}

            {/* ID */}
            <Field
              label="ID unique"
              required
              error={errors.id}
              hint="Format : xxx-yyyy-NN (ex: vec-faci-01, bck-expe-05)"
            >
              <Input
                value={question.id}
                onChange={e => setField('id', e.target.value.toLowerCase())}
                disabled={isEdit} // ID immuable en mode edit
                placeholder="vec-faci-01"
              />
            </Field>

            {/* Énoncé */}
            <Field
              label="Énoncé"
              required
              error={errors.enonce}
              hint="Supporte KaTeX : $x^2$, $$\\nabla L$$, **gras**, `code`"
            >
              <Textarea
                rows={4}
                value={question.enonce}
                onChange={e => setField('enonce', e.target.value)}
                placeholder="Énoncé complet de la question..."
              />
            </Field>

            {/* Options + correct */}
            <Field
              label="4 options"
              required
              error={errors.options}
              hint="Cliquez sur le bouton radio pour marquer la bonne réponse"
            >
              <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
                {question.options.map((opt, i) => (
                  <div
                    key={i}
                    style={{
                      display: 'flex',
                      gap: 'var(--space-2)',
                      alignItems: 'flex-start',
                    }}
                  >
                    <label style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: 6,
                      padding: '8px 12px',
                      border: `2px solid ${question.correct === i ? '#16a34a' : 'var(--color-border)'}`,
                      borderRadius: 'var(--radius-md)',
                      background: question.correct === i ? 'rgba(34, 197, 94, 0.08)' : 'transparent',
                      cursor: 'pointer',
                      minWidth: 55,
                      justifyContent: 'center',
                      fontWeight: 600,
                      transition: 'all 0.15s',
                    }}>
                      <input
                        type="radio"
                        name="correct"
                        checked={question.correct === i}
                        onChange={() => setField('correct', i)}
                        style={{ margin: 0 }}
                      />
                      {letters[i]}
                    </label>
                    <Textarea
                      rows={2}
                      value={opt}
                      onChange={e => setOption(i, e.target.value)}
                      placeholder={`Option ${letters[i]}`}
                      style={{ flex: 1 }}
                    />
                  </div>
                ))}
              </div>
            </Field>

            {/* Difficulté + type (side by side) */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-3)' }}>
              <Field label="Difficulté" required error={errors.difficulte}>
                <Select
                  value={question.difficulte}
                  onChange={e => setField('difficulte', e.target.value)}
                  options={LEVELS.map(l => ({ value: l, label: l }))}
                />
              </Field>

              <Field label="Type" required error={errors.type}>
                <Select
                  value={question.type}
                  onChange={e => setField('type', e.target.value)}
                  options={TYPES.map(t => ({ value: t, label: t }))}
                />
              </Field>
            </div>

            {/* Tags */}
            <Field
              label="Tags"
              error={errors.tags}
              hint="Ajoutez des tags en appuyant sur Entrée"
            >
              <div style={{ display: 'flex', gap: 8, marginBottom: 8 }}>
                <Input
                  placeholder="ex: vecteurs, calcul, fil-rouge"
                  value={tagInput}
                  onChange={e => setTagInput(e.target.value)}
                  onKeyDown={e => { if (e.key === 'Enter') { e.preventDefault(); addTag(); } }}
                />
                <Button variant="secondary" size="sm" onClick={addTag} type="button">
                  Ajouter
                </Button>
              </div>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                {question.tags.map(tag => (
                  <span
                    key={tag}
                    style={{
                      padding: '2px 8px 2px 10px',
                      background: 'var(--color-primary-subtle)',
                      color: 'var(--color-primary)',
                      borderRadius: 999,
                      fontSize: 12,
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 6,
                    }}
                  >
                    {tag}
                    <button
                      onClick={() => removeTag(tag)}
                      style={{
                        background: 'none',
                        border: 'none',
                        cursor: 'pointer',
                        color: 'var(--color-primary)',
                        fontSize: 14,
                        padding: 0,
                        lineHeight: 1,
                      }}
                    >×</button>
                  </span>
                ))}
                {question.tags.length === 0 && (
                  <span style={{ color: 'var(--color-text-muted)', fontSize: 12 }}>
                    Aucun tag
                  </span>
                )}
              </div>
            </Field>

            {/* Hint / Explanation / Traps */}
            <Field label="💡 Indice (hint)" required error={errors.hint}>
              <Textarea
                rows={2}
                value={question.hint}
                onChange={e => setField('hint', e.target.value)}
                placeholder="Petit indice pour guider l'étudiant..."
              />
            </Field>

            <Field label="📖 Explication détaillée" required error={errors.explanation}>
              <Textarea
                rows={6}
                value={question.explanation}
                onChange={e => setField('explanation', e.target.value)}
                placeholder="Explication complète avec formules, étapes, etc."
              />
            </Field>

            <Field label="⚠️ Pièges à éviter" required error={errors.traps}>
              <Textarea
                rows={3}
                value={question.traps}
                onChange={e => setField('traps', e.target.value)}
                placeholder="Erreurs classiques et pièges à éviter..."
              />
            </Field>

            <Field label="📚 Référence au cours" required error={errors.references}>
              <Input
                value={question.references}
                onChange={e => setField('references', e.target.value)}
                placeholder="ex: Cours J1, section 1.2 Vecteurs"
              />
            </Field>

            {/* Actions */}
            <div style={{ marginTop: 'var(--space-4)', display: 'flex', gap: 'var(--space-2)' }}>
              {isCreate && (
                <Button
                  variant="primary"
                  onClick={submitCreate}
                  disabled={!isValid || submitting}
                >
                  {submitting ? '⏳ Création...' : '✅ Créer la question'}
                </Button>
              )}
              {isEdit && (
                <Button
                  variant="primary"
                  onClick={submitUpdate}
                  disabled={!isValid || submitting || !question.id}
                >
                  {submitting ? '⏳ Mise à jour...' : '✅ Enregistrer modifications'}
                </Button>
              )}
              <Button variant="ghost" onClick={resetForm} disabled={submitting}>
                Réinitialiser
              </Button>
            </div>

            {!isValid && (
              <Box type="warning" style={{ marginTop: 'var(--space-3)' }}>
                ⚠️ {Object.keys(errors).length} champ(s) à corriger avant soumission
              </Box>
            )}
          </div>

          {/* === Colonne droite : Preview KaTeX live === */}
          <div style={{
            position: 'sticky',
            top: 'var(--space-4)',
            maxHeight: 'calc(100vh - 100px)',
            overflow: 'auto',
          }}>
            <h3 style={{ marginTop: 0, display: 'flex', alignItems: 'center', gap: 8 }}>
              👁️ Aperçu live
            </h3>

            <div style={{
              padding: 'var(--space-4)',
              background: 'var(--color-bg-elevated)',
              border: '1px solid var(--color-border)',
              borderRadius: 'var(--radius-md)',
            }}>
              {/* Header */}
              <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 'var(--space-3)', flexWrap: 'wrap' }}>
                {question.id && <code style={{ fontSize: 'var(--text-sm)' }}>{question.id}</code>}
                <LevelBadge level={question.difficulte} />
                <span style={{
                  padding: '2px 10px',
                  background: 'var(--color-primary-subtle)',
                  color: 'var(--color-primary)',
                  borderRadius: 999,
                  fontSize: 11,
                }}>{question.type}</span>
              </div>

              {/* Énoncé */}
              <div style={{
                padding: 'var(--space-3)',
                background: 'var(--color-bg-subtle)',
                borderRadius: 'var(--radius-md)',
                marginBottom: 'var(--space-3)',
                borderLeft: '3px solid var(--color-primary)',
              }}>
                <div style={{
                  fontSize: 'var(--text-xs)',
                  textTransform: 'uppercase',
                  fontWeight: 600,
                  color: 'var(--color-text-muted)',
                  marginBottom: 8,
                }}>ÉNONCÉ</div>
                {question.enonce ? (
                  <MathText text={question.enonce} as="div" />
                ) : (
                  <em style={{ color: 'var(--color-text-muted)' }}>Énoncé vide</em>
                )}
              </div>

              {/* Options */}
              <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                {question.options.map((opt, i) => (
                  <div
                    key={i}
                    style={{
                      padding: 'var(--space-2)',
                      border: `1px solid ${i === question.correct ? '#16a34a' : 'var(--color-border)'}`,
                      borderRadius: 'var(--radius-md)',
                      background: i === question.correct ? 'rgba(34, 197, 94, 0.08)' : 'transparent',
                      display: 'flex',
                      gap: 8,
                      alignItems: 'flex-start',
                      fontSize: 'var(--text-sm)',
                    }}
                  >
                    <span style={{
                      fontWeight: 700,
                      color: i === question.correct ? '#16a34a' : 'var(--color-primary)',
                      minWidth: 20,
                    }}>{letters[i]}</span>
                    {opt ? (
                      <MathText text={opt} as="span" style={{ flex: 1 }} />
                    ) : (
                      <em style={{ color: 'var(--color-text-muted)', flex: 1 }}>Option {letters[i]} vide</em>
                    )}
                    {i === question.correct && (
                      <span style={{ color: '#16a34a', fontWeight: 700 }}>✓</span>
                    )}
                  </div>
                ))}
              </div>

              {/* Tags */}
              {question.tags.length > 0 && (
                <div style={{ marginTop: 'var(--space-3)', display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                  {question.tags.map(t => (
                    <span key={t} style={{
                      padding: '2px 8px',
                      background: 'var(--color-bg-subtle)',
                      borderRadius: 999,
                      fontSize: 11,
                      color: 'var(--color-text-muted)',
                    }}>{t}</span>
                  ))}
                </div>
              )}

              {/* Hint preview */}
              {question.hint && (
                <div style={{
                  marginTop: 'var(--space-3)',
                  padding: 'var(--space-2)',
                  background: 'rgba(168, 85, 247, 0.08)',
                  borderLeft: '3px solid #a855f7',
                  borderRadius: 4,
                  fontSize: 'var(--text-sm)',
                }}>
                  <strong>💡 Hint :</strong> <MathText text={question.hint} as="span" />
                </div>
              )}
            </div>

            {/* État validation */}
            <div style={{
              marginTop: 'var(--space-3)',
              padding: 'var(--space-2)',
              background: isValid ? 'rgba(34, 197, 94, 0.08)' : 'rgba(249, 115, 22, 0.08)',
              borderRadius: 'var(--radius-md)',
              fontSize: 'var(--text-sm)',
              border: `1px solid ${isValid ? '#16a34a' : '#f97316'}`,
            }}>
              {isValid ? (
                <>✅ <strong>Question valide</strong> — prête à être soumise</>
              ) : (
                <>⚠️ <strong>{Object.keys(errors).length} erreur(s)</strong> : {Object.keys(errors).join(', ')}</>
              )}
            </div>
          </div>
        </div>

        {/* Modal de confirmation suppression */}
        <Modal
          isOpen={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          title="⚠️ Supprimer cette question ?"
        >
          <div style={{ marginBottom: 'var(--space-4)' }}>
            <p>Vous êtes sur le point de supprimer définitivement la question :</p>
            <div style={{
              padding: 'var(--space-3)',
              background: 'var(--color-bg-subtle)',
              borderRadius: 'var(--radius-md)',
              marginTop: 'var(--space-2)',
            }}>
              <code style={{ fontWeight: 600 }}>{question.id}</code>
              <div style={{ marginTop: 8, fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)' }}>
                {question.enonce.substring(0, 100)}...
              </div>
            </div>
            <p style={{ marginTop: 'var(--space-3)', color: '#dc2626' }}>
              ⚠️ <strong>Cette action est irréversible.</strong>
            </p>
          </div>
          <div style={{ display: 'flex', gap: 'var(--space-2)', justifyContent: 'flex-end' }}>
            <Button variant="ghost" onClick={() => setDeleteModalOpen(false)}>
              Annuler
            </Button>
            <Button variant="danger" onClick={submitDelete}>
              🗑️ Supprimer définitivement
            </Button>
          </div>
        </Modal>
      </div>
    );
  }

  // EXPORT
  root.BanqueEditor = BanqueEditor;

})(window);
