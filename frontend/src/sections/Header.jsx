import { useEffect, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { Menu, X, ArrowRight, Send } from 'lucide-react'
import { SmartLink, altFor } from '../components/ui'

export default function Header({ site, section }) {
  const nav = section?.items?.nav || []
  const d = section?.data || {}
  const [scrolled, setScrolled] = useState(false)
  const [open, setOpen] = useState(false)
  const [active, setActive] = useState('')

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  // Highlight the menu link of the section in view
  useEffect(() => {
    const ids = nav.map((n) => n.link).filter((l) => l?.startsWith('#')).map((l) => l.slice(1))
    const els = ids.map((id) => document.getElementById(id)).filter(Boolean)
    if (!els.length) return
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) setActive('#' + en.target.id) })
    }, { rootMargin: '-45% 0px -50% 0px' })
    els.forEach((el) => io.observe(el))
    return () => io.disconnect()
  }, [nav])

  useEffect(() => { document.body.style.overflow = open ? 'hidden' : '' }, [open])

  return (
    <>
      <motion.header className={`header ${scrolled ? 'scrolled' : ''}`} initial={{ y: -100 }} animate={{ y: 0 }} transition={{ duration: 0.7, ease: [0.22, 1, 0.36, 1] }}>
        <div className="container header-inner">
          <SmartLink href="#home" className="logo" aria-label={site.name}>
            {site.logo?.url && <img className="logo-img" src={site.logo.url} alt={altFor(site.logo, `${site.name} logo`)} />}
            <span>
              <span className="logo-name">{site.name}</span>
              {site.tagline && <span className="logo-tag">{site.tagline}</span>}
            </span>
          </SmartLink>
          {section && (
            <nav className="nav" aria-label="Main">
              {nav.map((n) => <SmartLink key={n.id} href={n.link} className={active === n.link ? 'active' : ''}>{n.label}</SmartLink>)}
            </nav>
          )}
          {section && d.cta_text && <SmartLink href={d.cta_link} className="btn btn-accent header-cta"><Send size={13} /> {d.cta_text}</SmartLink>}
          <button className="menu-toggle" aria-label="Open menu" onClick={() => setOpen(true)}><Menu /></button>
        </div>
      </motion.header>

      <AnimatePresence>
        {open && (
          <motion.div className="mobile-nav" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} onClick={() => setOpen(false)}>
            <motion.div className="mobile-panel" initial={{ x: '100%' }} animate={{ x: 0 }} exit={{ x: '100%' }} transition={{ type: 'spring', damping: 28, stiffness: 260 }} onClick={(e) => e.stopPropagation()}>
              <button className="close" aria-label="Close menu" onClick={() => setOpen(false)}><X /></button>
              {nav.map((n, i) => (
                <motion.div key={n.id} initial={{ opacity: 0, x: 30 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.05 * i + 0.1 }}>
                  <SmartLink href={n.link} className="m-link" onClick={() => setOpen(false)}>{n.label}</SmartLink>
                </motion.div>
              ))}
              {d.cta_text && <SmartLink href={d.cta_link} className="btn btn-accent" style={{ marginTop: 20 }} onClick={() => setOpen(false)}>{d.cta_text} <ArrowRight size={18} /></SmartLink>}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  )
}
