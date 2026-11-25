import { useEffect, useMemo, useState } from 'react'
import { AnimatePresence, motion, useScroll, useSpring } from 'framer-motion'
import { ArrowUp } from 'lucide-react'
import { fetchSite } from './api'
import Header from './sections/Header'
import Hero from './sections/Hero'
import About from './sections/About'
import Services from './sections/Services'
import Goals from './sections/Goals'
import Process from './sections/Process'
import Industries from './sections/Industries'
import Training from './sections/Training'
import Careers from './sections/Careers'
import Partners from './sections/Partners'
import Testimonials from './sections/Testimonials'
import States from './sections/States'
import Contact from './sections/Contact'
import Footer from './sections/Footer'
import LegalModal from './sections/LegalModal'

const SECTION_COMPONENTS = {
  hero: Hero, about: About, services: Services, goals: Goals, process: Process,
  industries: Industries, training: Training, careers: Careers, testimonials: Testimonials, partners: Partners,
  states: States, contact: Contact,
}

function applyTheme(theme, seo, site) {
  if (!theme) return
  const r = document.documentElement.style
  const map = { primary: '--primary', indigo: '--indigo', secondary: '--secondary', dark: '--dark', accent: '--accent', light: '--light', muted: '--muted' }
  Object.entries(map).forEach(([k, v]) => theme[k] && r.setProperty(v, theme[k]))
  const fonts = [theme.font_heading, theme.font_body].filter(Boolean)
  if (fonts.some((f) => !['Manrope', 'Inter'].includes(f))) {
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = `https://fonts.googleapis.com/css2?${fonts.map((f) => `family=${encodeURIComponent(f)}:wght@400;500;600;700;800`).join('&')}&display=swap`
    document.head.appendChild(link)
  }
  if (theme.font_heading) r.setProperty('--font-h', `'${theme.font_heading}', system-ui, sans-serif`)
  if (theme.font_body) r.setProperty('--font-b', `'${theme.font_body}', system-ui, sans-serif`)
  document.body.classList.toggle('no-anim', theme.animations === false)

  if (seo?.title) document.title = seo.title
  const meta = (name, content, attr = 'name') => {
    if (!content) return
    let el = document.head.querySelector(`meta[${attr}="${name}"]`)
    if (!el) { el = document.createElement('meta'); el.setAttribute(attr, name); document.head.appendChild(el) }
    el.setAttribute('content', content)
  }
  meta('description', seo?.description)
  meta('keywords', seo?.keywords)
  meta('theme-color', theme.primary)
  meta('og:title', seo?.title, 'property')
  meta('og:description', seo?.description, 'property')
  meta('og:image', seo?.og_image?.url, 'property')
  if (site?.favicon?.url) {
    let link = document.head.querySelector('link[rel="icon"]')
    if (!link) { link = document.createElement('link'); link.rel = 'icon'; document.head.appendChild(link) }
    link.href = site.favicon.url
  }
}

export default function App() {
  const [data, setData] = useState(null)
  const [error, setError] = useState(null)
  const [showTop, setShowTop] = useState(false)
  const { scrollYProgress } = useScroll()
  const progress = useSpring(scrollYProgress, { stiffness: 120, damping: 30 })

  useEffect(() => {
    fetchSite()
      .then((d) => { applyTheme(d.theme, d.seo, d.site); setData(d) })
      .catch((e) => setError(e.message))
  }, [])

  useEffect(() => {
    const onScroll = () => setShowTop(window.scrollY > 700)
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  // Scroll to #hash after content loads
  useEffect(() => {
    if (!data || !location.hash) return
    const id = location.hash.slice(1)
    if (id === 'privacy' || id === 'terms') return
    setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }), 400)
  }, [data])

  const bySlug = useMemo(() => Object.fromEntries((data?.sections || []).map((s) => [s.slug, s])), [data])

  if (error) {
    return (
      <div className="load-error">
        <div>
          <h2>We couldn’t load the website</h2>
          <p>{error}. Please make sure the backend server is running.</p>
          <button className="btn btn-accent" style={{ marginTop: 20 }} onClick={() => location.reload()}>Try again</button>
        </div>
      </div>
    )
  }

  return (
    <>
      <AnimatePresence>
        {!data && (
          <motion.div className="loader" key="loader" exit={{ opacity: 0, transition: { duration: 0.5 } }}>
            <div className="loader-inner"><div className="loader-ring" /><p>Loading</p></div>
          </motion.div>
        )}
      </AnimatePresence>

      {data && (
        <>
          <motion.div className="scroll-progress" style={{ scaleX: progress }} />
          <Header site={data.site} section={bySlug.header} />
          <main>
            {data.sections.map((s) => {
              const C = SECTION_COMPONENTS[s.slug]
              return C ? <C key={s.slug} section={s} site={data.site} social={data.social} /> : null
            })}
          </main>
          <Footer site={data.site} social={data.social} section={bySlug.footer} nav={bySlug.header?.items?.nav || []} />
          <LegalModal section={bySlug.legal} />

          {data.site.whatsapp && (
            <a className="whatsapp" href={`https://wa.me/${data.site.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.4 9.4 0 0 1-1.44-5.01c0-5.2 4.23-9.43 9.44-9.43 2.52 0 4.89.98 6.67 2.77a9.36 9.36 0 0 1 2.76 6.67c0 5.2-4.24 9.43-9.44 9.43zm8.03-17.46A11.3 11.3 0 0 0 12.05.7C5.8.7.7 5.8.7 12.05c0 2 .52 3.95 1.52 5.67L.6 23.3l5.72-1.5a11.33 11.33 0 0 0 5.42 1.38h.01c6.25 0 11.34-5.09 11.35-11.34 0-3.03-1.18-5.88-3.32-8.02z" /></svg>
            </a>
          )}
          <AnimatePresence>
            {showTop && (
              <motion.button className="to-top" aria-label="Back to top" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
                initial={{ opacity: 0, scale: 0.5, y: 20 }} animate={{ opacity: 1, scale: 1, y: 0 }} exit={{ opacity: 0, scale: 0.5, y: 20 }} whileHover={{ y: -4 }}>
                <ArrowUp size={22} />
              </motion.button>
            )}
          </AnimatePresence>
        </>
      )}
    </>
  )
}
