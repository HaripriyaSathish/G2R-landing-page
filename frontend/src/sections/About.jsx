import { useEffect, useRef } from 'react'
import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Icon, Img, Paragraphs, Reveal, SmartLink, fadeLeft, fadeRight, fadeUp, zoomIn } from '../components/ui'

// The cutout person fills x 20.2%..88.8% of the About PNG (the rest is transparent)
const PERSON_LEFT = 0.202
const PERSON_RIGHT = 0.888

export default function About({ section }) {
  const d = section.data
  const expertise = section.items.expertise || []
  const ref = useRef(null)

  // Desktop: size the photo so the person's head starts at the heading's last line
  // ("Workforces"), standing on the section bottom, and never reaching the cards.
  useEffect(() => {
    const sec = ref.current
    if (!sec) return
    const fit = () => {
      const img = sec.querySelector('.about-person')
      const title = sec.querySelector('.about-title')
      const vis = sec.querySelector('.about-visual')
      if (!img || !title || !vis) return
      if (window.innerWidth <= 1024 || !img.naturalWidth) { img.style.removeProperty('--pw'); return }
      const range = document.createRange()
      range.selectNodeContents(title)
      const lines = [...range.getClientRects()].filter((r) => r.width > 1 && r.height > 1)
      if (!lines.length) return
      const lastLineTop = Math.max(...lines.map((r) => r.top))
      const box = vis.getBoundingClientRect()
      const height = box.bottom - lastLineTop
      let width = height * (img.naturalWidth / img.naturalHeight)
      // keep the whole person on screen inside the left column (right edge is anchored in CSS)
      const maxWidth = (box.right - 8) / (PERSON_RIGHT - PERSON_LEFT)
      width = Math.max(360, Math.min(width, maxWidth))
      img.style.setProperty('--pw', `${Math.round(width)}px`)
    }
    fit()
    const ro = new ResizeObserver(fit)
    ro.observe(sec)
    const img = sec.querySelector('.about-person')
    img?.addEventListener('load', fit)
    document.fonts?.ready.then(fit)
    window.addEventListener('resize', fit)
    return () => { ro.disconnect(); img?.removeEventListener('load', fit); window.removeEventListener('resize', fit) }
  }, [d.title, d.image?.url])

  return (
    <section id="about" className="section dark about" ref={ref}>
      <div className="container about-grid">
        <Reveal className="about-visual" variants={fadeLeft}>
          {d.image_tag && (
            <motion.div className="about-tag" animate={{ y: [0, -10, 0] }} transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }}>
              <span className="dot" />{d.image_tag}{d.image_tag_badge && <em>{d.image_tag_badge}</em>}
            </motion.div>
          )}
          <Img image={d.image} className="about-person" alt="About G2R Global Solutions" />
        </Reveal>

        <div className="about-content">
          <Eyebrow text={d.badge} light />
          <Reveal className="about-head" variants={fadeRight}><Heading className="about-title" text={d.title} /></Reveal>
          <Reveal className="about-text" variants={fadeUp} i={1}><Paragraphs text={d.description} /></Reveal>
          {d.expertise_label && <Reveal className="label-accent" i={2}>{d.expertise_label}</Reveal>}
          <div className="expertise-grid">
            {expertise.map((x, i) => (
              <Reveal key={x.id} className="glass exp-card" variants={zoomIn} i={i}>
                <h4><span className="num">{String(i + 1).padStart(2, '0')}</span>{x.title}</h4>
                <p>{x.description}</p>
              </Reveal>
            ))}
          </div>
          {(d.belief_title || d.belief_text) && (
            <Reveal className="belief" variants={fadeUp} i={1}>
              <span className="b-icon"><Icon name={d.belief_icon || 'heart-handshake'} size={20} /></span>
              <div><strong>{d.belief_title}</strong><p>{d.belief_text}</p></div>
            </Reveal>
          )}
          {d.btn_text && (
            <Reveal i={2}><SmartLink href={d.btn_link} className="btn btn-ghost-light">{d.btn_text} <ArrowRight size={16} /></SmartLink></Reveal>
          )}
        </div>
      </div>
    </section>
  )
}
