/* ============================================================================
   layouts.js — Layouts React
   Plateforme d'examens IPSSI — Phase P2.4

   Layouts standardisés pour les différentes zones de la plateforme :
     - PublicLayout  : pages publiques (login, mentions, 404, 500)
     - ProfLayout    : espace enseignant (sidebar + header + main)
     - StudentLayout : espace étudiant pendant un examen (immersif, focus-lock)
     - AdminLayout   : extension de ProfLayout avec menus admin

   Exposés sur window.UILayouts.

   Dépendances : window.UI (composants base + advanced), window.UIHooks

   © 2026 Mohamed EL AFRIT — IPSSI — CC BY-NC-SA 4.0
============================================================================ */

(function (root) {
  'use strict';

  if (!root.UI || !root.UIHooks) {
    console.error('layouts.js requires components-base.js, components-advanced.js et hooks.js');
    return;
  }

  const { useState, useEffect, useRef, Fragment } = React;
  const { Avatar, Badge, ThemeToggle, Tooltip } = root.UI;
  const { useAuth, useTranslation, useKeyboardShortcut, useModal } = root.UIHooks;

  /* ==========================================================================
     1. PUBLICLAYOUT - Pages publiques (login, 404, 500, mentions)
     Structure : centré, fond uni, footer minimal
     Props : centered (bool), maxWidth (px), showFooter (bool)
     ========================================================================== */

  function PublicLayout(props) {
    const {
      children,
      centered = true,
      maxWidth = 480,
      showFooter = true,
      showThemeToggle = true,
      ...rest
    } = props;
    const { t } = useTranslation();

    return (
      <div style={{
        minHeight: '100vh',
        display: 'flex', flexDirection: 'column',
        background: 'var(--color-bg)',
      }} {...rest}>
        {showThemeToggle && (
          <div style={{
            position: 'fixed', top: 'var(--space-4)', right: 'var(--space-4)',
            zIndex: 'var(--z-fixed)',
          }}>
            <ThemeToggle />
          </div>
        )}

        <main style={{
          flex: 1,
          display: 'flex',
          alignItems: centered ? 'center' : 'flex-start',
          justifyContent: 'center',
          padding: 'var(--space-6) var(--space-4)',
        }}>
          <div style={{ width: '100%', maxWidth }}>
            {children}
          </div>
        </main>

        {showFooter && (
          <footer style={{
            padding: 'var(--space-4)',
            textAlign: 'center',
            fontSize: 'var(--text-xs)',
            color: 'var(--color-text-muted)',
            borderTop: '1px solid var(--color-border)',
          }}>
            {t('app.copyright')}
            {' • '}
            <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.fr"
               target="_blank" rel="noopener"
               style={{ color: 'inherit' }}>
              CC BY-NC-SA 4.0
            </a>
          </footer>
        )}
      </div>
    );
  }

  /* ==========================================================================
     2. PROFLAYOUT - Espace enseignant (sidebar + header + main)
     Sidebar : navigation principale + logo + user
     Header  : titre de page + actions + user menu
     Main    : contenu de la page
     Props : title, breadcrumbs, actions, sidebarItems
     ========================================================================== */

  function ProfLayout(props) {
    const {
      children,
      title = '',
      subtitle = null,
      breadcrumbs = [],
      actions = null,
      sidebarItems = null,
      ...rest
    } = props;

    const { user, isAdmin, logout } = useAuth();
    const { t } = useTranslation();
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Détection mobile
    const [isMobile, setIsMobile] = useState(() => window.innerWidth < 1024);
    useEffect(() => {
      const onResize = () => setIsMobile(window.innerWidth < 1024);
      window.addEventListener('resize', onResize);
      return () => window.removeEventListener('resize', onResize);
    }, []);

    // Sidebar items par défaut (override possible via prop)
    const defaultSidebarItems = [
      { key: 'dashboard',  label: t('nav.dashboard'),  icon: '🏠', href: '/admin/dashboard.html' },
      { key: 'examens',    label: t('nav.examens'),    icon: '📝', href: '/admin/examens.html' },
      { key: 'banque',     label: t('nav.banque'),     icon: '📚', href: '/admin/banque.html' },
      { key: 'analytics',  label: t('nav.analytics'),  icon: '📊', href: '/admin/analytics.html' },
      { key: 'separator', divider: true },
      ...(isAdmin ? [{ key: 'monitoring', label: 'Monitoring', icon: '🩺', href: '/admin/monitoring.html' }] : []),
    ];

    const items = sidebarItems || defaultSidebarItems;

    // Détection de l'item actif d'après l'URL courante
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '/';
    const isItemActive = (item) => item.href && currentPath.includes(item.href.replace('.html', ''));

    const sidebarWidth = sidebarOpen ? 260 : 72;
    const sidebarVisible = !isMobile || mobileMenuOpen;

    const handleLogout = async () => {
      await logout();
      window.location.href = '/login.html';
    };

    return (
      <div style={{
        minHeight: '100vh',
        display: 'flex',
        background: 'var(--color-bg)',
      }} {...rest}>

        {/* Overlay mobile */}
        {isMobile && mobileMenuOpen && (
          <div onClick={() => setMobileMenuOpen(false)} style={{
            position: 'fixed', inset: 0, zIndex: 199,
            background: 'var(--color-bg-overlay)',
          }} />
        )}

        {/* Sidebar */}
        <aside style={{
          width: isMobile ? 280 : sidebarWidth,
          background: 'var(--color-bg-card)',
          borderRight: '1px solid var(--color-border)',
          display: sidebarVisible ? 'flex' : 'none',
          flexDirection: 'column',
          position: isMobile ? 'fixed' : 'sticky',
          top: 0,
          left: 0,
          height: '100vh',
          zIndex: 200,
          transition: 'width var(--transition-normal)',
          overflow: 'hidden',
        }}>
          {/* Logo + brand */}
          <div style={{
            display: 'flex', alignItems: 'center', gap: 'var(--space-2)',
            padding: 'var(--space-4)',
            borderBottom: '1px solid var(--color-border)',
            minHeight: 64,
          }}>
            <span style={{ fontSize: '1.5rem' }}>🎓</span>
            {(sidebarOpen || isMobile) && (
              <div style={{ flex: 1, overflow: 'hidden' }}>
                <div style={{
                  fontSize: 'var(--text-sm)',
                  fontWeight: 'var(--font-bold)',
                  fontFamily: 'var(--font-heading)',
                  whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis',
                }}>
                  IPSSI Examens
                </div>
                <div style={{
                  fontSize: 'var(--text-xs)',
                  color: 'var(--color-text-muted)',
                }}>
                  {isAdmin ? 'Admin' : 'Enseignant'}
                </div>
              </div>
            )}
          </div>

          {/* Navigation */}
          <nav style={{ flex: 1, padding: 'var(--space-2)', overflowY: 'auto' }}>
            {items.map((item, i) => {
              if (item.divider) {
                return <div key={`div-${i}`} style={{
                  height: 1, background: 'var(--color-border)',
                  margin: 'var(--space-2) var(--space-2)',
                }} />;
              }
              const active = isItemActive(item);
              return (
                <a
                  key={item.key}
                  href={item.href}
                  title={!sidebarOpen ? item.label : ''}
                  style={{
                    display: 'flex', alignItems: 'center', gap: 'var(--space-3)',
                    padding: 'var(--space-2) var(--space-3)',
                    borderRadius: 'var(--radius-md)',
                    color: active ? 'var(--color-primary)' : 'var(--color-text)',
                    background: active ? 'var(--color-primary-soft)' : 'transparent',
                    textDecoration: 'none',
                    fontSize: 'var(--text-sm)',
                    fontWeight: active ? 'var(--font-semibold)' : 'var(--font-medium)',
                    transition: 'all var(--transition-fast)',
                    marginBottom: '2px',
                    whiteSpace: 'nowrap',
                  }}
                  onMouseEnter={(e) => { if (!active) e.currentTarget.style.background = 'var(--color-bg-subtle)'; }}
                  onMouseLeave={(e) => { if (!active) e.currentTarget.style.background = 'transparent'; }}
                >
                  <span style={{ fontSize: '1.1rem', flexShrink: 0, width: 24, textAlign: 'center' }}>
                    {item.icon}
                  </span>
                  {(sidebarOpen || isMobile) && <span>{item.label}</span>}
                  {(sidebarOpen || isMobile) && item.badge !== undefined && (
                    <Badge variant="primary" size="sm" style={{ marginLeft: 'auto' }}>{item.badge}</Badge>
                  )}
                </a>
              );
            })}
          </nav>

          {/* Footer sidebar : toggle + version */}
          {!isMobile && (
            <div style={{
              padding: 'var(--space-2)',
              borderTop: '1px solid var(--color-border)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: sidebarOpen ? 'space-between' : 'center',
            }}>
              <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                title={sidebarOpen ? 'Réduire le menu' : 'Étendre le menu'}
                style={{
                  background: 'transparent', border: 'none', cursor: 'pointer',
                  padding: 'var(--space-2)', borderRadius: 'var(--radius-md)',
                  color: 'var(--color-text-muted)', fontSize: 'var(--text-sm)',
                }}
              >
                {sidebarOpen ? '◀' : '▶'}
              </button>
              {sidebarOpen && (
                <span style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)' }}>
                  v0.2.0
                </span>
              )}
            </div>
          )}
        </aside>

        {/* Main area : header + content */}
        <div style={{
          flex: 1, display: 'flex', flexDirection: 'column',
          minWidth: 0, // important pour flex shrinking
        }}>
          {/* Header */}
          <header style={{
            background: 'var(--color-bg-card)',
            borderBottom: '1px solid var(--color-border)',
            padding: 'var(--space-3) var(--space-6)',
            display: 'flex', alignItems: 'center', justifyContent: 'space-between',
            position: 'sticky', top: 0, zIndex: 100,
            minHeight: 64,
            gap: 'var(--space-4)',
          }}>
            {/* Mobile menu button */}
            {isMobile && (
              <button
                onClick={() => setMobileMenuOpen(true)}
                aria-label="Ouvrir le menu"
                style={{
                  background: 'transparent', border: 'none', cursor: 'pointer',
                  padding: 'var(--space-2)', borderRadius: 'var(--radius-md)',
                  color: 'var(--color-text)', fontSize: 'var(--text-xl)',
                }}
              >
                ☰
              </button>
            )}

            {/* Titre + breadcrumbs */}
            <div style={{ flex: 1, minWidth: 0 }}>
              {breadcrumbs.length > 0 && (
                <nav aria-label="Fil d'Ariane" style={{
                  fontSize: 'var(--text-xs)',
                  color: 'var(--color-text-muted)',
                  marginBottom: '2px',
                  display: 'flex', alignItems: 'center', gap: 'var(--space-1)',
                  overflow: 'hidden',
                }}>
                  {breadcrumbs.map((crumb, i) => (
                    <Fragment key={i}>
                      {i > 0 && <span>›</span>}
                      {crumb.href ? (
                        <a href={crumb.href} style={{ color: 'inherit' }}>{crumb.label}</a>
                      ) : (
                        <span>{crumb.label}</span>
                      )}
                    </Fragment>
                  ))}
                </nav>
              )}
              {title && (
                <h1 style={{
                  margin: 0, fontSize: 'var(--text-xl)',
                  fontWeight: 'var(--font-semibold)',
                  fontFamily: 'var(--font-heading)',
                  color: 'var(--color-text-strong)',
                  whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis',
                }}>
                  {title}
                </h1>
              )}
              {subtitle && (
                <p style={{ margin: 0, fontSize: 'var(--text-sm)', color: 'var(--color-text-muted)' }}>
                  {subtitle}
                </p>
              )}
            </div>

            {/* Actions de page */}
            {actions && (
              <div style={{ display: 'flex', gap: 'var(--space-2)', flexShrink: 0 }}>
                {actions}
              </div>
            )}

            {/* Theme toggle */}
            <ThemeToggle size="sm" />

            {/* User menu */}
            {user && (
              <div style={{ position: 'relative', flexShrink: 0 }}>
                <button
                  onClick={() => setUserMenuOpen(!userMenuOpen)}
                  style={{
                    display: 'flex', alignItems: 'center', gap: 'var(--space-2)',
                    background: 'transparent', border: 'none', cursor: 'pointer',
                    padding: 'var(--space-1)',
                    borderRadius: 'var(--radius-md)',
                  }}
                >
                  <Avatar name={`${user.prenom} ${user.nom}`} size={36} />
                  {!isMobile && (
                    <div style={{ textAlign: 'left' }}>
                      <div style={{ fontSize: 'var(--text-sm)', fontWeight: 'var(--font-medium)' }}>
                        {user.prenom} {user.nom}
                      </div>
                      <div style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)' }}>
                        {isAdmin ? '🔑 Admin' : '👨‍🏫 Enseignant'}
                      </div>
                    </div>
                  )}
                </button>

                {userMenuOpen && (
                  <>
                    <div onClick={() => setUserMenuOpen(false)} style={{
                      position: 'fixed', inset: 0, zIndex: 99,
                    }} />
                    <div style={{
                      position: 'absolute', top: 'calc(100% + 8px)', right: 0,
                      background: 'var(--color-bg-card)',
                      border: '1px solid var(--color-border)',
                      borderRadius: 'var(--radius-md)',
                      boxShadow: 'var(--shadow-lg)',
                      minWidth: 200, padding: 'var(--space-1)',
                      zIndex: 100,
                      animation: 'fadeInDown 150ms ease-out',
                    }}>
                      <div style={{
                        padding: 'var(--space-3)',
                        borderBottom: '1px solid var(--color-border)',
                        marginBottom: 'var(--space-1)',
                      }}>
                        <div style={{ fontSize: 'var(--text-sm)', fontWeight: 'var(--font-semibold)' }}>
                          {user.prenom} {user.nom}
                        </div>
                        <div style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)' }}>
                          {user.email}
                        </div>
                      </div>
                      <a href="/admin/dashboard.html" style={{
                        display: 'block', padding: 'var(--space-2) var(--space-3)',
                        fontSize: 'var(--text-sm)', color: 'var(--color-text)',
                        textDecoration: 'none', borderRadius: 'var(--radius-sm)',
                      }}
                         onMouseEnter={(e) => e.currentTarget.style.background = 'var(--color-bg-subtle)'}
                         onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}>
                        👤 Tableau de bord
                      </a>
                      <a href="/admin/monitoring.html" style={{
                        display: 'block', padding: 'var(--space-2) var(--space-3)',
                        fontSize: 'var(--text-sm)', color: 'var(--color-text)',
                        textDecoration: 'none', borderRadius: 'var(--radius-sm)',
                      }}
                         onMouseEnter={(e) => e.currentTarget.style.background = 'var(--color-bg-subtle)'}
                         onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}>
                        🩺 Monitoring
                      </a>
                      <div style={{
                        height: 1, background: 'var(--color-border)',
                        margin: 'var(--space-1) 0',
                      }} />
                      <button onClick={handleLogout} style={{
                        width: '100%', textAlign: 'left',
                        padding: 'var(--space-2) var(--space-3)',
                        fontSize: 'var(--text-sm)', color: 'var(--color-danger)',
                        background: 'transparent', border: 'none', cursor: 'pointer',
                        borderRadius: 'var(--radius-sm)', fontFamily: 'inherit',
                      }}
                              onMouseEnter={(e) => e.currentTarget.style.background = 'var(--color-danger-soft)'}
                              onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}>
                        🚪 {t('nav.deconnexion')}
                      </button>
                    </div>
                  </>
                )}
              </div>
            )}
          </header>

          {/* Main content */}
          <main style={{
            flex: 1,
            padding: 'var(--space-6)',
            overflow: 'auto',
            background: 'var(--color-bg)',
          }}>
            {children}
          </main>
        </div>
      </div>
    );
  }

  /* ==========================================================================
     3. STUDENTLAYOUT - Espace étudiant pendant un examen
     Structure : minimale, immersive, focus sur le contenu
     - Pas de sidebar, pas de menu (focus-lock)
     - Header simple avec : titre examen + chrono + progression
     - Footer : signature + état (sauvegardé...)
     ========================================================================== */

  function StudentLayout(props) {
    const {
      children,
      examTitle = '',
      chronoComponent = null,
      progressComponent = null,
      footerLeft = null,
      footerRight = null,
      isFullscreen = false,
      ...rest
    } = props;

    return (
      <div style={{
        minHeight: '100vh',
        display: 'flex', flexDirection: 'column',
        background: 'var(--color-bg)',
      }} {...rest}>

        {/* Header immersif */}
        <header style={{
          background: 'var(--color-bg-card)',
          borderBottom: '1px solid var(--color-border)',
          padding: 'var(--space-3) var(--space-6)',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between',
          gap: 'var(--space-4)',
          position: 'sticky', top: 0, zIndex: 100,
          flexWrap: 'wrap',
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-3)' }}>
            <span style={{ fontSize: '1.25rem' }}>🎓</span>
            <div>
              <div style={{
                fontSize: 'var(--text-base)',
                fontWeight: 'var(--font-semibold)',
                fontFamily: 'var(--font-heading)',
                color: 'var(--color-text-strong)',
              }}>
                {examTitle || 'Examen en cours'}
              </div>
              {isFullscreen && (
                <div style={{ fontSize: 'var(--text-xs)', color: 'var(--color-success)' }}>
                  🛡️ Mode sécurisé activé
                </div>
              )}
            </div>
          </div>

          <div style={{
            display: 'flex', alignItems: 'center',
            gap: 'var(--space-4)',
            flexWrap: 'wrap',
          }}>
            {progressComponent}
            {chronoComponent}
          </div>
        </header>

        {/* Main content centré */}
        <main style={{
          flex: 1,
          padding: 'var(--space-6)',
          maxWidth: 880,
          width: '100%',
          margin: '0 auto',
          boxSizing: 'border-box',
        }}>
          {children}
        </main>

        {/* Footer informatif */}
        {(footerLeft || footerRight) && (
          <footer style={{
            background: 'var(--color-bg-card)',
            borderTop: '1px solid var(--color-border)',
            padding: 'var(--space-3) var(--space-6)',
            display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)',
            flexWrap: 'wrap', gap: 'var(--space-2)',
          }}>
            <div>{footerLeft}</div>
            <div>{footerRight}</div>
          </footer>
        )}
      </div>
    );
  }

  /* ==========================================================================
     4. ADMINLAYOUT - Variation de ProfLayout avec menus admin
     Pour l'instant : alias avec sidebarItems étendus.
     ========================================================================== */

  function AdminLayout(props) {
    const { t } = useTranslation();
    const adminSidebar = [
      { key: 'dashboard',  label: t('nav.dashboard'),    icon: '🏠', href: '/admin/dashboard.html' },
      { key: 'examens',    label: t('nav.examens'),       icon: '📝', href: '/admin/examens.html' },
      { key: 'banque',     label: t('nav.banque'),        icon: '📚', href: '/admin/banque.html' },
      { key: 'analytics',  label: t('nav.analytics'),     icon: '📊', href: '/admin/analytics.html' },
      { key: 'separator', divider: true },
      { key: 'monitoring', label: 'Monitoring',           icon: '🩺', href: '/admin/monitoring.html' },
    ];
    return <ProfLayout {...props} sidebarItems={adminSidebar} />;
  }

  /* ==========================================================================
     EXPORTS
     ========================================================================== */

  root.UILayouts = {
    PublicLayout,
    ProfLayout,
    StudentLayout,
    AdminLayout,
  };

})(window);
