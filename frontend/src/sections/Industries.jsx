import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Img, Reveal, SmartLink, fadeLeft, fadeRight, fadeUp, zoomIn } from '../components/ui'

export default function Industries({ section, site }) {
  const d = section.data
  const items = section.items.industries || []
  return (
    <section id="industries" className="section dark industries">
      <div className="industries-map" aria-hidden="true" />
      <div className="container" style={{ position: 'relative' }}>
        <div className="section-head">
          <Eyebrow text={d.badge} pill light />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>

        <div className="ind-wrap">
        {/* one big globe behind the whole column of circle cards */}
        {d.bg_image?.url && <img className="industries-globe" src={d.bg_image.url} alt="" aria-hidden="true" loading="lazy" />}
        <div className="ind-list">
          {items.map((it, i) => {
            const flip = i % 2 === 1
            return (
              <div key={it.id} className={`ind-item ${flip ? 'flip' : ''}`}>
                <Reveal className="ind-text" variants={flip ? fadeRight : fadeLeft}>
                  <span className="ind-practice">{it.practice_label}</span>
                  <h3>{String(i + 1).padStart(2, '0')}. {it.title}</h3>
                  <p>{it.description}</p>
                  {it.tags?.length > 0 && (
                    <div className="tags">
                      {it.tags.map((t, k) => (
                        <motion.span key={t} className="tag" initial={{ opacity: 0, y: 10 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: 0.3 + k * 0.08 }}>{t}</motion.span>
                      ))}
                    </div>
                  )}
                </Reveal>
                <Reveal className="ind-media" variants={zoomIn} i={1}>
                  <motion.div className="ind-circle" whileHover={{ scale: 1.04 }}>
                    <Img image={it.image} />
                  </motion.div>
                </Reveal>
              </div>
            )
          })}
        </div>
        </div>

        {(d.cta_title || d.cta_btn_text) && (
          <Reveal className="glass ind-cta" variants={fadeUp}>
            <div>
              {d.cta_label && <small>{d.cta_label}</small>}
              <h3>{d.cta_title}</h3>
              {d.cta_text && <p>{d.cta_text}</p>}
            </div>
            {d.cta_btn_text && <SmartLink href={d.cta_btn_link} className="btn btn-accent btn-sm">{d.cta_btn_text} <ArrowRight size={16} /></SmartLink>}
          </Reveal>
        )}
      </div>
    </section>
  )
}
