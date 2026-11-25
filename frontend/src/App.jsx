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
import FloatingContact from './components/FloatingContact'

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

          <FloatingContact site={data.site} />
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
