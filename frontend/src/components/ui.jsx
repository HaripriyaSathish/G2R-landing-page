import { Fragment, Suspense, memo } from 'react'
import { motion } from 'framer-motion'
import { DynamicIcon, iconNames } from 'lucide-react/dynamic'

/* ---------- Text helpers ---------- */

/** "{orange} [indigo]" + line breaks, as typed in the admin heading fields */
export function Heading({ text = '', as: Tag = 'h2', className = '', ...rest }) {
  const lines = String(text).split('\n')
  return (
    <Tag className={className} {...rest}>
      {lines.map((line, li) => (
        <Fragment key={li}>
          {li > 0 && <>{' '}<br /></>}
          {line.split(/(\{[^}]+\}|\[[^\]]+\])/g).map((part, i) => {
            if (/^\{.*\}$/.test(part)) return <span key={i} className="hl-accent">{part.slice(1, -1)}</span>
            if (/^\[.*\]$/.test(part)) return <span key={i} className="hl-indigo">{part.slice(1, -1)}</span>
            return <Fragment key={i}>{part}</Fragment>
          })}
        </Fragment>
      ))}
    </Tag>
  )
}

/** **bold** support */
export function Rich({ text = '' }) {
  return String(text).split(/(\*\*[^*]+\*\*)/g).map((p, i) =>
    /^\*\*.*\*\*$/.test(p) ? <strong key={i}>{p.slice(2, -2)}</strong> : <Fragment key={i}>{p}</Fragment>
  )
}

/** Paragraphs separated by blank lines */
export function Paragraphs({ text = '', className = '' }) {
  return String(text).split(/\n\s*\n/).filter(Boolean).map((p, i) => (
    <p key={i} className={className}><Rich text={p.trim()} /></p>
  ))
}

/** Minimal markdown: ## headings, - bullets, paragraphs, **bold** */
export function MiniMarkdown({ text = '' }) {
  const blocks = String(text).split(/\n\s*\n/)
  return blocks.map((block, i) => {
    const lines = block.split('\n').filter((l) => l.trim() !== '')
    if (!lines.length) return null
    const out = []
    let list = []
    const flush = (k) => {
      if (list.length) out.push(<ul key={`u${k}`}>{list.map((l, j) => <li key={j}><Rich text={l} /></li>)}</ul>)
      list = []
    }
    lines.forEach((l, k) => {
      if (/^##\s+/.test(l)) { flush(k); out.push(<h4 key={k}>{l.replace(/^##\s+/, '')}</h4>) }
      else if (/^[-*]\s+/.test(l)) list.push(l.replace(/^[-*]\s+/, ''))
      else { flush(k); out.push(<p key={k}><Rich text={l} /></p>) }
    })
    flush('end')
    return <Fragment key={i}>{out}</Fragment>
  })
}

/* ---------- Icons ---------- */

const ICONS = new Set(iconNames)
export const Icon = memo(function Icon({ name, size = 22, strokeWidth = 2, className = '' }) {
  const n = String(name || '').trim().toLowerCase()
  const safe = ICONS.has(n) ? n : 'circle'
  return (
    <Suspense fallback={<span style={{ width: size, height: size, display: 'inline-block' }} />}>
      <DynamicIcon name={safe} size={size} strokeWidth={strokeWidth} className={className} aria-hidden="true" />
    </Suspense>
  )
})

const SOCIAL_PATHS = {
  linkedin: 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45z',
  instagram: 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z',
  facebook: 'M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.33l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07z',
  youtube: 'M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.5A3.02 3.02 0 0 0 .5 6.19 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3.02 3.02 0 0 0 2.12 2.14c1.88.5 9.38.5 9.38.5s7.5 0 9.38-.5a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81zM9.55 15.57V8.43L15.82 12l-6.27 3.57z',
  twitter: 'M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.63 7.58H.48l8.6-9.83L0 1.15h7.59l5.24 6.93 6.07-6.93zm-1.29 19.5h2.04L6.48 3.24H4.3l13.31 17.4z',
}
export function SocialIcon({ name, size = 16 }) {
  const d = SOCIAL_PATHS[name]
  if (!d) return null
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d={d} /></svg>
}

/* ---------- Motion presets ---------- */

export const ease = [0.22, 1, 0.36, 1]
export const fadeUp = {
  hidden: { opacity: 0, y: 40 },
  show: (i = 0) => ({ opacity: 1, y: 0, transition: { duration: 0.7, ease, delay: i * 0.12 } }),
}
// On phones a sideways slide starts 60px off-screen, so text looks cut while it moves in.
// Small screens use a short upward fade instead.
const SMALL = typeof window !== 'undefined' && window.matchMedia('(max-width: 720px)').matches
export const fadeLeft = {
  hidden: SMALL ? { opacity: 0, x: 0, y: 30 } : { opacity: 0, x: -60 },
  show: (i = 0) => ({ opacity: 1, x: 0, y: 0, transition: { duration: 0.8, ease, delay: i * 0.12 } }),
}
export const fadeRight = {
  hidden: SMALL ? { opacity: 0, x: 0, y: 30 } : { opacity: 0, x: 60 },
  show: (i = 0) => ({ opacity: 1, x: 0, y: 0, transition: { duration: 0.8, ease, delay: i * 0.12 } }),
}
export const zoomIn = {
  hidden: { opacity: 0, scale: 0.85 },
  show: (i = 0) => ({ opacity: 1, scale: 1, transition: { duration: 0.7, ease, delay: i * 0.1 } }),
}
export const viewport = { once: true, amount: 0.2 }

/** Scroll-reveal wrapper */
export function Reveal({ as = 'div', variants = fadeUp, i = 0, className = '', children, amount = 0.2, ...rest }) {
  const M = motion[as] || motion.div
  return (
    <M className={className} variants={variants} custom={i} initial="hidden" whileInView="show" viewport={{ once: true, amount }} {...rest}>
      {children}
    </M>
  )
}

/** Small uppercase label with animated line ("— RECRUITMENT & HR SOLUTIONS") */
export function Eyebrow({ text, light = false, center = false, pill = false }) {
  if (!text) return null
  if (pill) {
    return (
      <Reveal className={`eyebrow-pill ${light ? 'light' : ''}`} variants={zoomIn}>
        <span className="dot" />{text}
      </Reveal>
    )
  }
  // The orange bar slides in from the left and PUSHES the whole label along with it
  // (one block, left → right). Afterwards a light glint keeps sweeping across the bar.
  const PUSH = { duration: 1, ease }
  return (
    <motion.div className={`eyebrow ${light ? 'light' : ''} ${center ? 'center' : ''}`}
      initial="hidden" whileInView="show" viewport={{ once: true, amount: 0.6 }}>
      <span className="eyebrow-push">
        <motion.span className="eyebrow-push-inner"
          variants={{ hidden: { x: '-100%' }, show: { x: '0%', transition: PUSH } }}>
          <span className="eyebrow-line" aria-hidden="true" />
          <span className="eyebrow-text">{text}</span>
          {center && <span className="eyebrow-line" aria-hidden="true" />}
        </motion.span>
      </span>
    </motion.div>
  )
}

/** True when an alt text is empty or just a file name ("Image (5)", "1630623505493", "5logo", "94da5e7e…") */
export function isWeakAlt(alt) {
  const a = String(alt || '').trim()
  if (a.length < 3 || a.length > 150) return true
  if (/^(image|images|img|photo|picture|pic|logo|screenshot|untitled|download|twittermeta)s?[\s_()\-\d]*$/i.test(a)) return true
  if (/^[\d\s_\-()]+(logo)?$/i.test(a) || /^\d+\s*logo$/i.test(a)) return true
  if (/\b[a-f0-9]{12,}\b/i.test(a) || /\b\d{6,}\w*\b/.test(a)) return true
  return false
}

const SOCIAL_NAMES = { linkedin: 'LinkedIn', instagram: 'Instagram', facebook: 'Facebook', youtube: 'YouTube', twitter: 'X (Twitter)' }
/** Accessible label for a social icon link, e.g. "G2R Global Solutions on LinkedIn" */
export function socialLabel(siteName, key) {
  const net = SOCIAL_NAMES[key] || key
  return siteName ? `${siteName} on ${net}` : net
}

/** Use the media library alt when it is meaningful, otherwise the given context text */
export function altFor(image, fallback = '') {
  return isWeakAlt(image?.alt) ? fallback : image.alt
}

/** alt: context alt used when the media library alt is weak; decorative images pass alt="" explicitly */
export function Img({ image, className = '', eager = false, alt, ...rest }) {
  if (!image?.url) return null
  const text = alt === '' ? '' : altFor(image, alt || '')
  return <img src={image.url} alt={text} width={image.width || undefined} height={image.height || undefined} loading={eager ? 'eager' : 'lazy'} decoding="async" className={className} {...rest} />
}

/** Smooth-scroll for in-page links; returns true if handled */
export function scrollToHash(href) {
  if (!href || !href.startsWith('#') || href.length < 2) return false
  const el = document.getElementById(href.slice(1))
  if (!el) return false
  const top = el.getBoundingClientRect().top + window.scrollY - 80
  window.scrollTo({ top, behavior: 'smooth' })
  return true
}

export function SmartLink({ href = '#', children, className = '', onClick, ...rest }) {
  const external = /^https?:\/\//.test(href)
  return (
    <a
      href={href || '#'}
      className={className}
      target={external ? '_blank' : undefined}
      rel={external ? 'noopener noreferrer' : undefined}
      onClick={(e) => {
        onClick?.(e)
        if (e.defaultPrevented) return
        if (href === '#privacy' || href === '#terms') {
          e.preventDefault()
          window.dispatchEvent(new CustomEvent('g2r:legal', { detail: href.slice(1) }))
          return
        }
        if (scrollToHash(href)) { e.preventDefault(); history.replaceState(null, '', href) }
      }}
      {...rest}
    >
      {children}
    </a>
  )
}
