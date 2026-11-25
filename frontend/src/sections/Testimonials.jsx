import { useCallback, useEffect, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { ChevronLeft, ChevronRight, Star } from 'lucide-react'
import { Eyebrow, Heading, Img, Reveal, SocialIcon, ease, socialLabel, zoomIn } from '../components/ui'

/** Client testimonials — Figma: 768-wide showcase card with a slider of partner stories. */
export default function Testimonials({ section, site, social = [] }) {
  const d = section.data
  const items = section.items.testimonials || []
  const [index, setIndex] = useState(0)
  const [dir, setDir] = useState(1)
  const count = items.length

  const go = useCallback((step) => {
    if (count < 2) return
    setDir(step)
    setIndex((i) => (i + step + count) % count)
  }, [count])

  useEffect(() => {
    if (!d.autoplay || count < 2) return
    const t = setInterval(() => go(1), 6000)
    return () => clearInterval(t)
  }, [d.autoplay, count, go, index])

  if (!count) return null
  const t = items[index]
  const rating = Math.max(0, Math.min(5, Math.round(Number(t.rating) || 5)))

  return (
    <section id="testimonials" className="section testimonials">
      <div className="container">
        <div className="section-head">
          <Eyebrow text={d.badge} center light />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>

        <Reveal className="t-card" variants={zoomIn}>
          <div className="t-stage">
            <AnimatePresence mode="wait" custom={dir}>
              <motion.div key={t.id} className="t-panel" custom={dir}
                initial={{ opacity: 0, x: dir * 60 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: dir * -60 }}
                transition={{ duration: 0.5, ease }}>
                <div className="t-top">
                  <div className="t-logo"><Img image={t.logo} alt={t.company ? `${t.company} logo` : `${t.name} company logo`} /></div>
                  <div className="t-head">
                    {t.story_label && <span className="t-story"><span className="dot" />{t.story_label}</span>}
                    <div className="t-heading"><em>{t.heading_top}</em><strong>{t.heading_bottom}</strong></div>
                    <div className="t-social">
                      {social.slice(0, 3).map((s) => <a key={s.key} href={s.url} target="_blank" rel="noopener noreferrer" aria-label={socialLabel(site?.name, s.key)}><SocialIcon name={s.key} size={11} /></a>)}
                    </div>
                  </div>
                </div>

                <div className="t-mid">
                  <motion.div className="t-photo" initial={{ rotate: -4, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} transition={{ delay: 0.15, duration: 0.6, ease }}>
                    <Img image={t.photo} alt={[t.name, t.role].filter(Boolean).join(", ")} />
                  </motion.div>
                  <div className="t-info">
                    {t.badge && <span className="t-badge">{t.badge}</span>}
                    <h3>{t.title}</h3>
                    <p>{t.description}</p>
                  </div>
                </div>

                <div className="t-quote">
                  <span className="q q-open" aria-hidden="true">“</span>
                  <span className="q q-close" aria-hidden="true">”</span>
                  <strong className="t-name">{t.name}</strong>
                  <span className="t-role">{t.role}</span>
                  <blockquote>“{t.quote}”</blockquote>
                  <div className="t-stars" aria-label={`${rating} out of 5 stars`}>
                    {Array.from({ length: 5 }).map((_, k) => <Star key={k} size={14} className={k < rating ? 'on' : ''} />)}
                  </div>
                </div>
              </motion.div>
            </AnimatePresence>
          </div>

          <div className="t-nav">
            <div className="t-dots">
              {items.map((it, k) => (
                <button key={it.id} className={k === index ? 'active' : ''} aria-label={`Show testimonial ${k + 1}`}
                  onClick={() => { setDir(k > index ? 1 : -1); setIndex(k) }} />
              ))}
            </div>
            <div className="t-arrows">
              <button className="prev" aria-label="Previous testimonial" onClick={() => go(-1)} disabled={count < 2}><ChevronLeft size={18} /></button>
              <button className="next" aria-label="Next testimonial" onClick={() => go(1)} disabled={count < 2}><ChevronRight size={18} /></button>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
