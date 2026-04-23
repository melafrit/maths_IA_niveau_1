/* ============================================================================
   components-base.js — Composants React de base
   Plateforme d'examens IPSSI — Phase P2.2

   Bibliothèque de composants React fondamentaux, exposés sur `window.UI`.
   Usage dans une page :

   <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
   <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
   <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
   <script src="/assets/components-base.js" type="text/babel"></script>

   const { Button, Input, Modal, Toast, ... } = window.UI;

   Composants exportés (15) :
     Form     : Button, Input, Select, Checkbox, Radio, Textarea
     Layout   : Card, Box, Badge, Avatar
     Feedback : Modal, Toast, Tooltip, Spinner, Skeleton
     Misc     : ProgressBar

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  const { useState, useEffect, useRef, useCallback, createContext, useContext, Fragment } = React;

  /* ==========================================================================
     1. BUTTON
     Variants : primary | secondary | success | danger | ghost | outline
     Sizes    : sm | md | lg
     Props    : variant, size, disabled, loading, icon, iconRight, fullWidth, onClick
     ========================================================================== */

  function Button(props) {
    const {
      variant = 'primary',
      size = 'md',
      disabled = false,
      loading = false,
      icon = null,
      iconRight = null,
      fullWidth = false,
      onClick,
      children,
      type = 'button',
      ...rest
    } = props;

    const sizeStyles = {
      sm: { padding: '6px 12px', fontSize: 'var(--text-sm)', height: '32px' },
      md: { padding: '8px 16px', fontSize: 'var(--text-base)', height: '40px' },
      lg: { padding: '12px 24px', fontSize: 'var(--text-lg)', height: '48px' },
    };

    const variantStyles = {
      primary: {
        background: 'var(--color-primary)',
        color: 'var(--color-text-on-primary)',
        border: '1px solid var(--color-primary)',
      },
      secondary: {
        background: 'var(--color-bg-card)',
        color: 'var(--color-text)',
        border: '1px solid var(--color-border)',
      },
      success: {
        background: 'var(--color-success)',
        color: '#ffffff',
        border: '1px solid var(--color-success)',
      },
      danger: {
        background: 'var(--color-danger)',
        color: '#ffffff',
        border: '1px solid var(--color-danger)',
      },
      ghost: {
        background: 'transparent',
        color: 'var(--color-text)',
        border: '1px solid transparent',
      },
      outline: {
        background: 'transparent',
        color: 'var(--color-primary)',
        border: '1px solid var(--color-primary)',
      },
    };

    const baseStyle = {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      gap: '8px',
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--font-medium)',
      borderRadius: 'var(--radius-md)',
      cursor: disabled || loading ? 'not-allowed' : 'pointer',
      opacity: disabled ? 0.6 : 1,
      transition: 'all var(--transition-fast)',
      width: fullWidth ? '100%' : 'auto',
      whiteSpace: 'nowrap',
      ...sizeStyles[size],
      ...variantStyles[variant],
    };

    return (
      <button
        type={type}
        onClick={onClick}
        disabled={disabled || loading}
        style={baseStyle}
        {...rest}
      >
        {loading ? <Spinner size={size === 'sm' ? 12 : 16} /> : icon}
        {children}
        {iconRight}
      </button>
    );
  }

  /* ==========================================================================
     2. INPUT (text, email, password, number, tel, url)
     Props : label, type, value, onChange, error, hint, icon, fullWidth, required
     ========================================================================== */

  function Input(props) {
    const {
      id,
      label,
      type = 'text',
      value = '',
      onChange,
      error = null,
      hint = null,
      icon = null,
      placeholder = '',
      required = false,
      disabled = false,
      autoComplete,
      autoFocus = false,
      fullWidth = true,
      ...rest
    } = props;

    const inputId = id || `input-${Math.random().toString(36).slice(2, 8)}`;
    const inputRef = useRef(null);

    // Garantir que value est toujours un string pour le controlled input
    var safeValue = typeof value === 'string' ? value : (value != null ? '' + value : '');
    if (safeValue === '[object Object]') safeValue = '';

    const wrapperStyle = {
      width: fullWidth ? '100%' : 'auto',
      marginBottom: 'var(--space-1)',
    };

    const labelStyle = {
      display: 'block',
      fontSize: 'var(--text-sm)',
      fontWeight: 'var(--font-medium)',
      color: 'var(--color-text)',
      marginBottom: 'var(--space-1_5)',
    };

    const inputWrapperStyle = {
      position: 'relative',
      display: 'flex',
      alignItems: 'center',
    };

    const iconStyle = {
      position: 'absolute',
      left: '12px',
      color: 'var(--color-text-muted)',
      pointerEvents: 'none',
      display: 'flex',
      alignItems: 'center',
    };

    const inputStyle = {
      width: '100%',
      padding: icon ? '8px 12px 8px 40px' : '8px 12px',
      fontSize: 'var(--text-base)',
      fontFamily: 'var(--font-sans)',
      color: 'var(--color-text)',
      background: 'var(--color-bg-input)',
      border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
      borderRadius: 'var(--radius-md)',
      outline: 'none',
      transition: 'border-color var(--transition-fast), box-shadow var(--transition-fast)',
      height: '40px',
    };

    const [focused, setFocused] = useState(false);
    if (focused) {
      inputStyle.borderColor = error ? 'var(--color-danger)' : 'var(--color-border-focus)';
      inputStyle.boxShadow = error ? 'var(--shadow-focus-danger)' : 'var(--shadow-focus)';
    }

    return (
      <div style={wrapperStyle}>
        {label && (
          <label htmlFor={inputId} style={labelStyle}>
            {label}{required && <span style={{ color: 'var(--color-danger)', marginLeft: 4 }}>*</span>}
          </label>
        )}
        <div style={inputWrapperStyle}>
          {icon && <span style={iconStyle}>{icon}</span>}
          <input
            ref={inputRef}
            id={inputId}
            type={type}
            value={safeValue}
            onChange={function(e) {
              if (!onChange) return;
              // e.target.value fonctionne sur les <input> natifs (prouvé par login.html)
              // Fallback sur ref si e.target est indisponible
              var val;
              try { val = e.target.value; } catch(ex) {}
              if (typeof val !== 'string') {
                try { val = inputRef.current ? inputRef.current.value : ''; } catch(ex2) {}
              }
              if (typeof val !== 'string') val = '';
              onChange(val, e);
            }}
            placeholder={placeholder}
            required={required}
            disabled={disabled}
            autoComplete={autoComplete}
            autoFocus={autoFocus}
            onFocus={function() { setFocused(true); }}
            onBlur={function() { setFocused(false); }}
            style={inputStyle}
            {...rest}
          />
        </div>
        {error && (
          <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-danger)', marginTop: 'var(--space-1)' }}>
            {error}
          </div>
        )}
        {!error && hint && (
          <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)', marginTop: 'var(--space-1)' }}>
            {hint}
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     3. TEXTAREA
     ========================================================================== */

  function Textarea(props) {
    const {
      id,
      label,
      value = '',
      onChange,
      error = null,
      hint = null,
      placeholder = '',
      rows = 4,
      required = false,
      disabled = false,
      fullWidth = true,
      ...rest
    } = props;

    const inputId = id || `textarea-${Math.random().toString(36).slice(2, 8)}`;
    const textareaRef = useRef(null);
    const [focused, setFocused] = useState(false);

    var safeValue = typeof value === 'string' ? value : (value != null ? '' + value : '');
    if (safeValue === '[object Object]') safeValue = '';

    const wrapperStyle = { width: fullWidth ? '100%' : 'auto', marginBottom: 'var(--space-1)' };
    const labelStyle = {
      display: 'block', fontSize: 'var(--text-sm)', fontWeight: 'var(--font-medium)',
      color: 'var(--color-text)', marginBottom: 'var(--space-1_5)',
    };
    const textareaStyle = {
      width: '100%', padding: '8px 12px', fontSize: 'var(--text-base)',
      fontFamily: 'var(--font-sans)', color: 'var(--color-text)',
      background: 'var(--color-bg-input)',
      border: `1px solid ${error ? 'var(--color-danger)' :
                         focused ? 'var(--color-border-focus)' : 'var(--color-border)'}`,
      borderRadius: 'var(--radius-md)', outline: 'none',
      transition: 'border-color var(--transition-fast), box-shadow var(--transition-fast)',
      resize: 'vertical', minHeight: '80px',
      boxShadow: focused ? (error ? 'var(--shadow-focus-danger)' : 'var(--shadow-focus)') : 'none',
    };

    return (
      <div style={wrapperStyle}>
        {label && (
          <label htmlFor={inputId} style={labelStyle}>
            {label}{required && <span style={{ color: 'var(--color-danger)', marginLeft: 4 }}>*</span>}
          </label>
        )}
        <textarea
          ref={textareaRef}
          id={inputId}
          value={safeValue}
          onChange={function(e) {
            if (!onChange) return;
            var val;
            try { val = e.target.value; } catch(ex) {}
            if (typeof val !== 'string') {
              try { val = textareaRef.current ? textareaRef.current.value : ''; } catch(ex2) {}
            }
            if (typeof val !== 'string') val = '';
            onChange(val, e);
          }}
          placeholder={placeholder}
          rows={rows}
          required={required}
          disabled={disabled}
          onFocus={function() { setFocused(true); }}
          onBlur={function() { setFocused(false); }}
          style={textareaStyle}
          {...rest}
        />
        {error && <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-danger)', marginTop: 'var(--space-1)' }}>{error}</div>}
        {!error && hint && <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)', marginTop: 'var(--space-1)' }}>{hint}</div>}
      </div>
    );
  }

  /* ==========================================================================
     4. SELECT
     Props : options (array of {value, label}), value, onChange, label, error
     ========================================================================== */

  function Select(props) {
    const {
      id,
      label,
      options = [],
      value = '',
      onChange,
      error = null,
      hint = null,
      placeholder = '— Choisir —',
      required = false,
      disabled = false,
      fullWidth = true,
      ...rest
    } = props;

    const selectId = id || `select-${Math.random().toString(36).slice(2, 8)}`;
    const selectRef = useRef(null);
    const [focused, setFocused] = useState(false);

    var safeValue = typeof value === 'string' ? value : (value != null ? '' + value : '');

    const selectStyle = {
      width: '100%', padding: '8px 32px 8px 12px', fontSize: 'var(--text-base)',
      fontFamily: 'var(--font-sans)', color: 'var(--color-text)',
      background: 'var(--color-bg-input)',
      border: `1px solid ${error ? 'var(--color-danger)' :
                         focused ? 'var(--color-border-focus)' : 'var(--color-border)'}`,
      borderRadius: 'var(--radius-md)', outline: 'none',
      transition: 'border-color var(--transition-fast), box-shadow var(--transition-fast)',
      height: '40px', appearance: 'none',
      backgroundImage: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='%2364748b' d='M5 6L0 0h10L5 6z'/%3E%3C/svg%3E\")",
      backgroundRepeat: 'no-repeat', backgroundPosition: 'right 12px center',
      boxShadow: focused ? (error ? 'var(--shadow-focus-danger)' : 'var(--shadow-focus)') : 'none',
    };

    return (
      <div style={{ width: fullWidth ? '100%' : 'auto', marginBottom: 'var(--space-1)' }}>
        {label && (
          <label htmlFor={selectId} style={{
            display: 'block', fontSize: 'var(--text-sm)', fontWeight: 'var(--font-medium)',
            color: 'var(--color-text)', marginBottom: 'var(--space-1_5)',
          }}>
            {label}{required && <span style={{ color: 'var(--color-danger)', marginLeft: 4 }}>*</span>}
          </label>
        )}
        <select
          ref={selectRef}
          id={selectId}
          value={safeValue}
          onChange={function(e) {
            if (!onChange) return;
            var val;
            try { val = e.target.value; } catch(ex) {}
            if (typeof val !== 'string') {
              try { val = selectRef.current ? selectRef.current.value : ''; } catch(ex2) {}
            }
            if (typeof val !== 'string') val = '';
            onChange(val, e);
          }}
          required={required}
          disabled={disabled}
          onFocus={function() { setFocused(true); }}
          onBlur={function() { setFocused(false); }}
          style={selectStyle}
          {...rest}
        >
          {placeholder && <option value="" disabled>{placeholder}</option>}
          {options.map((opt) => (
            <option key={opt.value} value={opt.value} disabled={opt.disabled}>
              {opt.label}
            </option>
          ))}
        </select>
        {error && <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-danger)', marginTop: 'var(--space-1)' }}>{error}</div>}
        {!error && hint && <div style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)', marginTop: 'var(--space-1)' }}>{hint}</div>}
      </div>
    );
  }

  /* ==========================================================================
     5. CHECKBOX
     ========================================================================== */

  function Checkbox(props) {
    const { id, label, checked = false, onChange, disabled = false, hint = null, ...rest } = props;
    const checkboxId = id || `checkbox-${Math.random().toString(36).slice(2, 8)}`;

    return (
      <div style={{ marginBottom: 'var(--space-2)' }}>
        <label htmlFor={checkboxId} style={{
          display: 'inline-flex', alignItems: 'flex-start', gap: 'var(--space-2)',
          cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.6 : 1,
        }}>
          <input
            id={checkboxId}
            type="checkbox"
            checked={checked}
            onChange={(e) => onChange && onChange(e.target.checked, e)}
            disabled={disabled}
            style={{
              accentColor: 'var(--color-primary)',
              width: '16px', height: '16px', marginTop: '2px',
              cursor: disabled ? 'not-allowed' : 'pointer',
            }}
            {...rest}
          />
          <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text)', userSelect: 'none' }}>
            {label}
            {hint && <div style={{ color: 'var(--color-text-muted)', fontSize: 'var(--text-xs)', marginTop: 2 }}>{hint}</div>}
          </span>
        </label>
      </div>
    );
  }

  /* ==========================================================================
     6. RADIO
     Props : name, value, options ({value, label, hint}), onChange
     ========================================================================== */

  function Radio(props) {
    const { name, value, options = [], onChange, disabled = false, label, ...rest } = props;
    return (
      <div style={{ marginBottom: 'var(--space-2)' }}>
        {label && (
          <div style={{ fontSize: 'var(--text-sm)', fontWeight: 'var(--font-medium)', marginBottom: 'var(--space-2)' }}>
            {label}
          </div>
        )}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-2)' }}>
          {options.map((opt) => {
            const id = `${name}-${opt.value}`;
            return (
              <label key={opt.value} htmlFor={id} style={{
                display: 'inline-flex', alignItems: 'flex-start', gap: 'var(--space-2)',
                cursor: disabled ? 'not-allowed' : 'pointer', opacity: disabled ? 0.6 : 1,
              }}>
                <input
                  id={id}
                  type="radio"
                  name={name}
                  value={opt.value}
                  checked={value === opt.value}
                  onChange={() => onChange && onChange(opt.value)}
                  disabled={disabled || opt.disabled}
                  style={{ accentColor: 'var(--color-primary)', width: '16px', height: '16px', marginTop: '2px', cursor: 'pointer' }}
                />
                <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text)', userSelect: 'none' }}>
                  {opt.label}
                  {opt.hint && <div style={{ color: 'var(--color-text-muted)', fontSize: 'var(--text-xs)', marginTop: 2 }}>{opt.hint}</div>}
                </span>
              </label>
            );
          })}
        </div>
      </div>
    );
  }

  /* ==========================================================================
     7. CARD - Conteneur générique
     Props : title, subtitle, footer, padding, hoverable, onClick
     ========================================================================== */

  function Card(props) {
    const {
      title = null, subtitle = null, footer = null,
      padding = 'var(--space-6)', hoverable = false, onClick,
      children, style: customStyle = {}, ...rest
    } = props;

    const [hovered, setHovered] = useState(false);

    const style = {
      background: 'var(--color-bg-card)',
      border: '1px solid var(--color-border)',
      borderRadius: 'var(--radius-xl)',
      boxShadow: hoverable && hovered ? 'var(--shadow-md)' : 'var(--shadow-sm)',
      transition: 'box-shadow var(--transition-fast), transform var(--transition-fast)',
      transform: hoverable && hovered ? 'translateY(-2px)' : 'translateY(0)',
      cursor: onClick ? 'pointer' : 'default',
      overflow: 'hidden',
      ...customStyle,
    };

    return (
      <div
        style={style}
        onClick={onClick}
        onMouseEnter={() => hoverable && setHovered(true)}
        onMouseLeave={() => hoverable && setHovered(false)}
        {...rest}
      >
        {(title || subtitle) && (
          <div style={{ padding, paddingBottom: title || subtitle ? 'var(--space-4)' : padding, borderBottom: footer ? '1px solid var(--color-border)' : 'none' }}>
            {title && (
              <h3 style={{
                margin: 0, fontSize: 'var(--text-lg)', fontWeight: 'var(--font-semibold)',
                fontFamily: 'var(--font-heading)', color: 'var(--color-text-strong)'
              }}>{title}</h3>
            )}
            {subtitle && (
              <p style={{ margin: '4px 0 0 0', fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)' }}>
                {subtitle}
              </p>
            )}
          </div>
        )}
        <div style={{ padding: title || subtitle || footer ? padding : 0 }}>
          {children}
        </div>
        {footer && (
          <div style={{
            padding, paddingTop: 'var(--space-4)', borderTop: '1px solid var(--color-border)',
            background: 'var(--color-bg-subtle)',
          }}>
            {footer}
          </div>
        )}
      </div>
    );
  }

  /* ==========================================================================
     8. BOX - Encadré pédagogique (6 types)
     Types : definition | retenir | attention | intuition | exemple | python
     ========================================================================== */

  function Box(props) {
    const { type = 'definition', title = null, children, ...rest } = props;

    const typeConfig = {
      definition: { color: 'var(--c-box-definition)', emoji: '📐', label: 'Définition' },
      retenir:    { color: 'var(--c-box-retenir)',    emoji: '⚡', label: 'À retenir' },
      attention:  { color: 'var(--c-box-attention)',  emoji: '⚠️', label: 'Attention' },
      intuition:  { color: 'var(--c-box-intuition)',  emoji: '💡', label: 'Intuition' },
      exemple:    { color: 'var(--c-box-exemple)',    emoji: '📊', label: 'Exemple' },
      python:     { color: 'var(--c-box-python)',     emoji: '🐍', label: 'Code Python' },
    };
    const config = typeConfig[type] || typeConfig.definition;
    const isPython = type === 'python';

    return (
      <div style={{
        background: isPython ? 'var(--c-neutral-900)' : `color-mix(in srgb, ${config.color} 8%, var(--color-bg-card))`,
        borderLeft: `4px solid ${config.color}`,
        borderRadius: 'var(--radius-md)',
        padding: 'var(--space-4)',
        margin: 'var(--space-4) 0',
        color: isPython ? 'var(--c-neutral-100)' : 'var(--color-text)',
      }} {...rest}>
        <div style={{
          display: 'flex', alignItems: 'center', gap: 'var(--space-2)',
          fontWeight: 'var(--font-semibold)', fontSize: 'var(--text-sm)',
          color: isPython ? 'var(--c-neutral-300)' : config.color,
          marginBottom: 'var(--space-2)',
          textTransform: 'uppercase', letterSpacing: 'var(--tracking-wider)',
        }}>
          <span style={{ fontSize: 'var(--text-base)' }}>{config.emoji}</span>
          <span>{title || config.label}</span>
        </div>
        <div style={{ fontFamily: isPython ? 'var(--font-mono)' : 'inherit', fontSize: isPython ? 'var(--text-sm)' : 'inherit' }}>
          {children}
        </div>
      </div>
    );
  }

  /* ==========================================================================
     9. BADGE
     Variants : primary | success | warning | danger | neutral
     ========================================================================== */

  function Badge(props) {
    const { variant = 'neutral', size = 'md', children, ...rest } = props;

    const variantStyles = {
      primary: { background: 'var(--color-primary-soft)', color: 'var(--color-primary-active)' },
      success: { background: 'var(--color-success-soft)', color: 'var(--c-success-700)' },
      warning: { background: 'var(--color-warning-soft)', color: 'var(--c-warning-700)' },
      danger:  { background: 'var(--color-danger-soft)',  color: 'var(--c-danger-700)' },
      neutral: { background: 'var(--color-bg-subtle)',    color: 'var(--color-text-muted)' },
    };
    const sizeStyles = {
      sm: { padding: '1px 6px', fontSize: 'var(--text-xs)' },
      md: { padding: '2px 10px', fontSize: 'var(--text-xs)' },
      lg: { padding: '4px 12px', fontSize: 'var(--text-sm)' },
    };

    return (
      <span style={{
        display: 'inline-flex', alignItems: 'center', gap: '4px',
        borderRadius: 'var(--radius-full)', fontWeight: 'var(--font-semibold)',
        whiteSpace: 'nowrap', lineHeight: 1.4,
        ...sizeStyles[size], ...variantStyles[variant],
      }} {...rest}>
        {children}
      </span>
    );
  }

  /* ==========================================================================
     10. AVATAR (initiales colorées)
     ========================================================================== */

  function Avatar(props) {
    const { name = '?', size = 40, src = null, ...rest } = props;

    // Initiales depuis "Mohamed EL AFRIT" -> "ME"
    const initials = name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase() || '?';

    // Couleur déterministe depuis le nom
    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#06b6d4'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) & 0xfffffff;
    const bgColor = colors[hash % colors.length];

    if (src) {
      return (
        <img src={src} alt={name} style={{
          width: size, height: size, borderRadius: '50%', objectFit: 'cover',
        }} {...rest} />
      );
    }

    return (
      <div style={{
        width: size, height: size, borderRadius: '50%',
        background: bgColor, color: '#ffffff',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontWeight: 'var(--font-semibold)', fontSize: size * 0.4,
        fontFamily: 'var(--font-sans)', userSelect: 'none', flexShrink: 0,
      }} {...rest}>
        {initials}
      </div>
    );
  }

  /* ==========================================================================
     11. SPINNER (loader rotatif)
     ========================================================================== */

  function Spinner(props) {
    const { size = 16, color = 'currentColor', thickness = 2, ...rest } = props;
    return (
      <span style={{
        display: 'inline-block',
        width: `${size}px`, height: `${size}px`,
        border: `${thickness}px solid ${color}`,
        borderTopColor: 'transparent',
        borderRadius: '50%',
        animation: 'spin 0.8s linear infinite',
      }} {...rest} />
    );
  }

  /* ==========================================================================
     12. SKELETON (placeholder de chargement)
     ========================================================================== */

  function Skeleton(props) {
    const { width = '100%', height = 16, circle = false, ...rest } = props;
    return (
      <div className="skeleton-shimmer" style={{
        width, height, borderRadius: circle ? '50%' : 'var(--radius-md)',
        display: 'inline-block',
      }} {...rest} />
    );
  }

  /* ==========================================================================
     13. PROGRESSBAR
     ========================================================================== */

  function ProgressBar(props) {
    const { value = 0, max = 100, label = null, color = 'var(--color-primary)', height = 8, showValue = false, ...rest } = props;
    const pct = Math.max(0, Math.min(100, (value / max) * 100));
    return (
      <div style={{ width: '100%' }} {...rest}>
        {(label || showValue) && (
          <div style={{
            display: 'flex', justifyContent: 'space-between',
            fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)',
            marginBottom: 'var(--space-1)',
          }}>
            {label && <span>{label}</span>}
            {showValue && <span>{Math.round(pct)} %</span>}
          </div>
        )}
        <div style={{
          width: '100%', height: `${height}px`,
          background: 'var(--color-bg-subtle)', borderRadius: 'var(--radius-full)',
          overflow: 'hidden',
        }}>
          <div style={{
            width: `${pct}%`, height: '100%', background: color,
            borderRadius: 'var(--radius-full)',
            transition: 'width var(--transition-normal)',
          }} />
        </div>
      </div>
    );
  }

  /* ==========================================================================
     14. MODAL
     Props : open, onClose, title, footer, size (sm/md/lg/xl), closeOnOverlay
     ========================================================================== */

  function Modal(props) {
    const {
      open = false, onClose, title = null, footer = null,
      size = 'md', closeOnOverlay = true, closeOnEsc = true,
      children, ...rest
    } = props;

    useEffect(() => {
      if (!open) return;
      const onKey = (e) => { if (closeOnEsc && e.key === 'Escape' && onClose) onClose(); };
      window.addEventListener('keydown', onKey);
      const prevOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      return () => {
        window.removeEventListener('keydown', onKey);
        document.body.style.overflow = prevOverflow;
      };
    }, [open, closeOnEsc, onClose]);

    if (!open) return null;

    const sizes = { sm: 400, md: 560, lg: 760, xl: 960 };
    const maxWidth = sizes[size] || sizes.md;

    return (
      <div
        onClick={() => closeOnOverlay && onClose && onClose()}
        style={{
          position: 'fixed', inset: 0,
          background: 'var(--color-bg-overlay)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          padding: 'var(--space-4)', zIndex: 'var(--z-modal)',
          animation: 'fadeIn 150ms ease-out',
        }}
      >
        <div
          onClick={(e) => e.stopPropagation()}
          style={{
            width: '100%', maxWidth: `${maxWidth}px`, maxHeight: '90vh',
            background: 'var(--color-bg-card)',
            borderRadius: 'var(--radius-xl)',
            boxShadow: 'var(--shadow-2xl)',
            display: 'flex', flexDirection: 'column',
            animation: 'scaleIn 150ms ease-out',
          }}
          {...rest}
        >
          {(title || onClose) && (
            <div style={{
              display: 'flex', alignItems: 'center', justifyContent: 'space-between',
              padding: 'var(--space-5) var(--space-6)',
              borderBottom: '1px solid var(--color-border)',
            }}>
              {title && (
                <h3 style={{
                  margin: 0, fontSize: 'var(--text-xl)', fontWeight: 'var(--font-semibold)',
                  fontFamily: 'var(--font-heading)', color: 'var(--color-text-strong)',
                }}>{title}</h3>
              )}
              {onClose && (
                <button onClick={onClose} aria-label="Fermer" style={{
                  background: 'transparent', border: 'none', cursor: 'pointer',
                  padding: 'var(--space-2)', borderRadius: 'var(--radius-md)',
                  color: 'var(--color-text-muted)', fontSize: 'var(--text-xl)',
                  lineHeight: 1, fontWeight: 'var(--font-bold)',
                }}>×</button>
              )}
            </div>
          )}
          <div style={{ padding: 'var(--space-6)', overflow: 'auto', flex: 1 }}>
            {children}
          </div>
          {footer && (
            <div style={{
              padding: 'var(--space-4) var(--space-6)',
              borderTop: '1px solid var(--color-border)',
              display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end',
              background: 'var(--color-bg-subtle)',
              borderRadius: '0 0 var(--radius-xl) var(--radius-xl)',
            }}>
              {footer}
            </div>
          )}
        </div>
      </div>
    );
  }

  /* ==========================================================================
     15. TOAST + ToastProvider
     Usage :
       <ToastProvider>
         <App />
       </ToastProvider>
       const { toast } = useToast();
       toast.success('Enregistré !');
     ========================================================================== */

  const ToastContext = createContext(null);

  function ToastProvider({ children }) {
    const [toasts, setToasts] = useState([]);

    const add = useCallback((message, type = 'info', duration = 4000) => {
      const id = Math.random().toString(36).slice(2);
      setToasts((prev) => [...prev, { id, message, type, duration }]);
      if (duration > 0) {
        setTimeout(() => {
          setToasts((prev) => prev.filter(t => t.id !== id));
        }, duration);
      }
      return id;
    }, []);

    const remove = useCallback((id) => {
      setToasts((prev) => prev.filter(t => t.id !== id));
    }, []);

    // toast est CALLABLE en tant que fonction ET a des méthodes .success/.error/etc.
    // Supporte les deux patterns : toast.success('msg') ET toast({message:'msg', type:'error'})
    var toast = function(opts) {
      if (typeof opts === 'string') {
        add(opts, 'info');
      } else if (opts && typeof opts === 'object') {
        add(opts.message || opts.title || '', opts.type || 'info', opts.duration);
      }
    };
    toast.success = function(msg, dur) { return add(msg, 'success', dur); };
    toast.error   = function(msg, dur) { return add(msg, 'error', dur); };
    toast.warning = function(msg, dur) { return add(msg, 'warning', dur); };
    toast.info    = function(msg, dur) { return add(msg, 'info', dur); };

    return (
      <ToastContext.Provider value={{ toast, remove }}>
        {children}
        <div style={{
          position: 'fixed', top: 'var(--space-4)', right: 'var(--space-4)',
          zIndex: 'var(--z-toast)', display: 'flex', flexDirection: 'column',
          gap: 'var(--space-2)', maxWidth: '400px',
        }}>
          {toasts.map(t => (
            <ToastItem key={t.id} {...t} onClose={() => remove(t.id)} />
          ))}
        </div>
      </ToastContext.Provider>
    );
  }

  function ToastItem({ message, type, onClose }) {
    const config = {
      success: { bg: 'var(--color-success-soft)', color: 'var(--c-success-700)', border: 'var(--color-success)', icon: '✓' },
      error:   { bg: 'var(--color-danger-soft)',  color: 'var(--c-danger-700)',  border: 'var(--color-danger)',  icon: '✕' },
      warning: { bg: 'var(--color-warning-soft)', color: 'var(--c-warning-700)', border: 'var(--color-warning)', icon: '⚠' },
      info:    { bg: 'var(--color-primary-soft)', color: 'var(--c-primary-700)', border: 'var(--color-primary)', icon: 'ℹ' },
    }[type] || { bg: 'var(--color-bg-card)', color: 'var(--color-text)', border: 'var(--color-border)', icon: '' };

    return (
      <div style={{
        display: 'flex', alignItems: 'flex-start', gap: 'var(--space-3)',
        padding: 'var(--space-3) var(--space-4)',
        background: config.bg, color: config.color,
        border: `1px solid ${config.border}`,
        borderLeft: `4px solid ${config.border}`,
        borderRadius: 'var(--radius-md)',
        boxShadow: 'var(--shadow-md)',
        animation: 'slideDown 200ms ease-out',
        minWidth: '280px',
      }}>
        <span style={{ fontSize: 'var(--text-base)', fontWeight: 'var(--font-bold)' }}>{config.icon}</span>
        <span style={{ flex: 1, fontSize: 'var(--text-sm)' }}>{message}</span>
        <button onClick={onClose} aria-label="Fermer" style={{
          background: 'transparent', border: 'none', cursor: 'pointer',
          color: 'inherit', opacity: 0.7, padding: 0, fontSize: 'var(--text-base)', lineHeight: 1,
        }}>×</button>
      </div>
    );
  }

  function useToast() {
    const ctx = useContext(ToastContext);
    if (!ctx) {
      console.warn('useToast() utilisé hors d\'un <ToastProvider>. Les toasts ne s\'afficheront pas.');
      var noop = function() {};
      noop.success = function() {};
      noop.error = function() {};
      noop.warning = function() {};
      noop.info = function() {};
      return { toast: noop, remove: function() {} };
    }
    return ctx;
  }

  /* ==========================================================================
     16. TOOLTIP (au survol, top par défaut)
     ========================================================================== */

  function Tooltip(props) {
    const { content, position = 'top', children, ...rest } = props;
    const [shown, setShown] = useState(false);

    const positions = {
      top:    { bottom: '100%', left: '50%', transform: 'translateX(-50%)', marginBottom: '6px' },
      bottom: { top: '100%',    left: '50%', transform: 'translateX(-50%)', marginTop: '6px' },
      left:   { right: '100%',  top: '50%',  transform: 'translateY(-50%)', marginRight: '6px' },
      right:  { left: '100%',   top: '50%',  transform: 'translateY(-50%)', marginLeft: '6px' },
    };

    return (
      <span
        style={{ position: 'relative', display: 'inline-block' }}
        onMouseEnter={() => setShown(true)}
        onMouseLeave={() => setShown(false)}
        onFocus={() => setShown(true)}
        onBlur={() => setShown(false)}
        {...rest}
      >
        {children}
        {shown && content && (
          <span style={{
            position: 'absolute', ...positions[position],
            background: 'var(--c-neutral-900)', color: 'var(--c-neutral-50)',
            padding: '4px 8px', borderRadius: 'var(--radius-sm)',
            fontSize: 'var(--text-xs)', whiteSpace: 'nowrap',
            zIndex: 'var(--z-tooltip)', pointerEvents: 'none',
            animation: 'fadeIn 150ms ease-out',
          }}>
            {content}
          </span>
        )}
      </span>
    );
  }

  /* ==========================================================================
     17. ERROR BOUNDARY
     Attrape les erreurs de rendu React et affiche un message d'erreur
     au lieu d'une page blanche.
     Usage :
       <ErrorBoundary>
         <App />
       </ErrorBoundary>
     ========================================================================== */

  class ErrorBoundary extends React.Component {
    constructor(props) {
      super(props);
      this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
      return { hasError: true, error: error };
    }

    componentDidCatch(error, info) {
      console.error('[ErrorBoundary] Erreur attrapée :', error, info);
    }

    render() {
      if (this.state.hasError) {
        return React.createElement('div', {
          style: {
            maxWidth: '600px',
            margin: '80px auto',
            padding: '32px',
            textAlign: 'center',
            background: 'var(--color-bg-elevated, #fff)',
            border: '1px solid var(--color-border, #e5e7eb)',
            borderRadius: '12px',
            fontFamily: 'system-ui, sans-serif',
          },
        },
          React.createElement('div', {
            style: { fontSize: '48px', marginBottom: '16px', opacity: 0.5 },
          }, '\u26A0\uFE0F'),
          React.createElement('h2', {
            style: { margin: '0 0 8px 0', fontSize: '20px', color: '#dc2626' },
          }, 'Une erreur est survenue'),
          React.createElement('p', {
            style: { margin: '0 0 16px 0', fontSize: '14px', color: '#6b7280' },
          }, this.state.error ? String(this.state.error.message || this.state.error) : 'Erreur inattendue'),
          React.createElement('button', {
            onClick: function () { window.location.reload(); },
            style: {
              padding: '8px 24px',
              fontSize: '14px',
              fontWeight: 600,
              background: 'var(--color-primary, #3b82f6)',
              color: 'white',
              border: 'none',
              borderRadius: '6px',
              cursor: 'pointer',
            },
          }, 'Recharger la page')
        );
      }
      return this.props.children;
    }
  }

  /* ==========================================================================
     EXPORTS
     ========================================================================== */

  root.UI = {
    Button, Input, Textarea, Select, Checkbox, Radio,
    Card, Box, Badge, Avatar,
    Modal, Tooltip,
    ToastProvider, useToast,
    Spinner, Skeleton, ProgressBar,
    ErrorBoundary,
  };

})(window);
