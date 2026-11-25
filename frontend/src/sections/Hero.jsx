import { motion, useScroll, useTransform } from 'framer-motion'
import { useEffect, useRef } from 'react'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Icon, Img, Rich, SmartLink, ease } from '../components/ui'

/** Navy crescent arc: 5px thick in the middle, tapering to sharp points (Figma). */
function Crescent({ size, className }) {
  const r = size / 2
  const id = `cres-${size}`
  return (
    <svg className={className} width={size} height={size} viewBox={`0 0 ${size} ${size}`} aria-hidden="true">
      <defs>
        <mask id={id}>
          <circle cx={r} cy={r} r={r} fill="#fff" />
          <circle cx={r - 2.6} cy={r} r={r - 1.4} fill="#000" />
        </mask>
      </defs>
      <circle cx={r} cy={r} r={r} fill="var(--indigo)" mask={`url(#${id})`} />
    </svg>
  )
}

export default function Hero({ section }) {
  const d = section.data
  const floating = section.items.floating || []
  const features = section.items.features || []
  const ref = useRef(null)
  const visualRef = useRef(null)

  // Figma: hero 1280 x 768 under a 78px header; image stage 590 x 768 on the right.
  // Scale the stage so the whole hero stays on one screen.
  useEffect(() => {
    const hero = ref.current
    const vis = visualRef.current
    if (!hero || !vis) return
    const fit = () => {
      const desktop = window.innerWidth > 1024
      // Desktop: the stage's top ~114px is empty space, so fit the visible part (654px)
      // to the hero height and let it grow up to 112% on wide screens to fill the centre gap.
      const w = hero.clientWidth
      const widthCap = Math.max(Math.min(1, w / 1280), (w * 1.12) / 1700)
      const s = desktop
        ? Math.min(1.12, (hero.clientHeight - 78 - 8) / 654, widthCap)
        : Math.min(vis.clientWidth / 590, 0.9)
      hero.style.setProperty('--hs', String(Math.max(s, 0.4)))
    }
    fit()
    const ro = new ResizeObserver(fit)
    ro.observe(hero)
    ro.observe(vis)
    window.addEventListener('resize', fit)
    return () => { ro.disconnect(); window.removeEventListener('resize', fit) }
  }, [])
  const { scrollYProgress } = useScroll({ target: ref, offset: ['start start', 'end start'] })
  const yImg = useTransform(scrollYProgress, [0, 1], [0, 80])
  const yText = useTransform(scrollYProgress, [0, 1], [0, -40])

  const words = (d.title || '').split('\n')

  return (
    <section id="home" className="hero" ref={ref}>
      <div className="container hero-grid">
        <motion.div className="hero-copy" style={{ y: yText }}>
          <Eyebrow text={d.badge} />
          <h1 className="hero-title" aria-label={(d.title || '').replace(/[{}[\]]/g, '')}>
            {words.map((line, i) => (
              <motion.span key={i} style={{ display: 'block' }} initial={{ opacity: 0, y: 50, filter: 'blur(8px)' }} animate={{ opacity: 1, y: 0, filter: 'blur(0px)' }} transition={{ duration: 0.8, ease, delay: 0.25 + i * 0.12 }}>
                <Heading as="span" text={line} />
              </motion.span>
            ))}
          </h1>
          <motion.p className="hero-desc" initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, delay: 0.8, ease }}>
            <Rich text={d.description} />
          </motion.p>
          <motion.div className="hero-actions" initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.8, delay: 0.95, ease }}>
            {d.primary_btn_text && <SmartLink href={d.primary_btn_link} className="btn btn-accent">{d.primary_btn_text} <ArrowRight size={18} /></SmartLink>}
            {d.secondary_btn_text && <SmartLink href={d.secondary_btn_link} className="btn btn-outline">{d.secondary_btn_text}</SmartLink>}
          </motion.div>
          {features.length > 0 && (
            <div className="hero-features">
              {features.map((f, i) => (
                <motion.div className="hero-feature" key={f.id} initial={{ opacity: 0, y: 30 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.7, delay: 1.1 + i * 0.15, ease }}>
                  <motion.span className="f-icon" whileHover={{ rotate: 12, scale: 1.08 }}><Icon name={f.icon} size={24} /></motion.span>
                  <div><h3>{f.title}</h3><p>{f.description}</p></div>
                </motion.div>
              ))}
            </div>
          )}
        </motion.div>

        {/* Fixed 670×870 stage measured 1:1 from the Figma frame, scaled on smaller screens */}
        <div className="hero-visual" ref={visualRef}>
          <div className="hero-stage">
            <span className="hs-ring-grey" aria-hidden="true" />
            <span className="hs-ring-orange" aria-hidden="true" />
            <motion.span className="hs-circle" aria-hidden="true" initial={{ scale: 0.6, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} transition={{ duration: 1.1, ease, delay: 0.2 }} />
            <Crescent className="hs-arc hs-arc-tr" size={92} />
            <Crescent className="hs-arc hs-arc-bl" size={80} />
            <motion.div className="hs-person" style={{ y: yImg }} initial={{ opacity: 0, y: 80 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 1.1, ease, delay: 0.4 }}>
              <Img image={d.image} className="hero-person" eager />
            </motion.div>
          {floating.map((b, i) => (
            <motion.div key={b.id} className={`float-badge fb-${i % 3} ${i % 2 ? 'row' : ''}`}
              initial={{ opacity: 0, scale: 0.6 }} animate={{ opacity: 1, scale: 1, y: [0, -12, 0] }}
              transition={{ opacity: { delay: 1 + i * 0.2 }, scale: { delay: 1 + i * 0.2, type: 'spring' }, y: { duration: 4 + i, repeat: Infinity, ease: 'easeInOut', delay: 1.5 + i * 0.3 } }}
              whileHover={{ scale: 1.08 }}>
              <span className="b-icon"><Icon name={b.icon} size={20} /></span>
              <span><strong>{b.title}</strong><small>{b.subtitle}</small></span>
            </motion.div>
          ))}
          </div>
        </div>
      </div>
      <svg className="wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 70 C 240 20, 480 110, 720 70 S 1200 20, 1440 60 L1440 120 L0 120 Z" fill="var(--accent)" opacity=".9" />
        <path d="M0 85 C 260 40, 520 120, 760 85 S 1220 40, 1440 80 L1440 120 L0 120 Z" fill="#161A80" />
      </svg>
    </section>
  )
}
