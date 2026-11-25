import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Icon, Reveal, SmartLink, fadeUp } from '../components/ui'

export default function States({ section }) {
  const d = section.data
  const hubs = section.items.hubs || []
  return (
    <section id="states" className="section dark states">
      <div className="container">
        <div className="section-head">
          <Eyebrow text={d.badge} pill light />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>

        <div className="hub-grid">
          {hubs.map((h, i) => (
            <Reveal key={h.id} className={`glass hub ${h.highlight ? 'highlight' : ''}`} variants={fadeUp} i={i}>
              <div className="hub-head">
                <span className="h-icon"><Icon name={h.icon || 'map-pin'} size={20} /></span>
                <div><h4>{h.name}</h4><small>{h.subtitle}</small></div>
                {h.status && <span className="status">{h.status}</span>}
              </div>
              <div style={{ display: 'grid', gap: 8 }}>
                {(h.states || []).map((s, k) => (
                  <motion.div key={k} className="state-row" initial={{ opacity: 0, x: -14 }} whileInView={{ opacity: 1, x: 0 }} viewport={{ once: true }} transition={{ delay: 0.2 + i * 0.1 + k * 0.06 }}>
                    <span>{s.name}</span>
                    {s.tag && <em className={/head\s*quarter/i.test(s.tag) ? 'hq' : ''}>{s.tag}</em>}
                  </motion.div>
                ))}
              </div>
              {h.note && <p className="hub-note">{h.note}</p>}
            </Reveal>
          ))}
        </div>

        {(d.pan_states?.length > 0 || d.pan_btn_text) && (
          <Reveal className="glass pan-strip" variants={fadeUp}>
            <div>
              <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 8 }}>
                {d.pan_label && <span className="p-label">{d.pan_label}</span>}
                {(d.pan_states || []).map((s) => <span key={s} className="tag">{s}</span>)}
              </div>
              {d.pan_note && <p>{d.pan_note}</p>}
            </div>
            {d.pan_btn_text && <SmartLink href={d.pan_btn_link} className="btn btn-accent btn-sm">{d.pan_btn_text} <ArrowRight size={16} /></SmartLink>}
          </Reveal>
        )}
      </div>
    </section>
  )
}
