import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Icon, Reveal, SmartLink, fadeUp } from '../components/ui'

const DEFAULT_COLORS = ['#F5A623', '#F36B16', '#E11D48', '#1D138C', '#0EA5E9']
const STEP = 0.45 // seconds between stages

export default function Process({ section }) {
  const d = section.data
  const stages = section.items.stages || []
  const total = stages.length

  return (
    <section id="process" className="section process">
      <div className="container">
        <div className="section-head">
          <Eyebrow text={d.badge} center light />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>

        <motion.div className="timeline" initial="hidden" whileInView="show" viewport={{ once: true, amount: 0.25 }}>
          <motion.span className="timeline-line" aria-hidden="true"
            variants={{ hidden: { scaleX: 0 }, show: { scaleX: 1, transition: { duration: STEP * total, ease: 'linear' } } }} />
          {stages.map((s, i) => {
            const color = s.color || DEFAULT_COLORS[i % DEFAULT_COLORS.length]
            const grad = `linear-gradient(160deg, ${color} 0%, ${s.color2 || color} 100%)`
            const delay = i * STEP
            const up = i % 2 === 0 // odd stages (1,3,5): label on top, card below
            const label = (
              <motion.div className="stage-label" style={{ background: grad }}
                variants={{ hidden: { opacity: 0, y: up ? -16 : 16 }, show: { opacity: 1, y: 0, transition: { delay: delay + 0.25 } } }}>
                {s.stage_label}
              </motion.div>
            )
            const card = (
              <motion.div className="stage-card"
                variants={{ hidden: { opacity: 0, y: up ? 30 : -30 }, show: { opacity: 1, y: 0, transition: { delay: delay + 0.35, duration: 0.6 } } }}>
                <h4>{s.title}</h4>
                <p>{s.description}</p>
              </motion.div>
            )
            const pin = <motion.span className="stage-pin" style={{ color }} variants={{ hidden: { scaleY: 0 }, show: { scaleY: 1, transition: { delay: delay + 0.2 } } }} />
            return (
              <div className={`stage ${up ? "" : "down"}`} key={s.id}>
                <div className="stage-top">{up ? <>{label}{pin}</> : <>{card}{pin}</>}</div>
                <div className="stage-hex-wrap">
                  {/* 1) appears one by one  2) then keeps beating slowly like a heart — solid colour, no blur */}
                  <motion.div
                    variants={{
                      hidden: { scale: 0, rotate: -120, opacity: 0 },
                      show: {
                        scale: [0, 1.18, 1],
                        rotate: 0,
                        opacity: 1,
                        transition: {
                          scale: { delay, duration: 0.7, ease: 'easeOut' },
                          rotate: { delay, duration: 0.6 },
                          opacity: { delay, duration: 0.3 },
                        },
                      },
                    }}>
                    <motion.div className="stage-hex" style={{ background: grad }}
                      animate={{ scale: [1, 1.08, 1, 1.05, 1] }}
                      transition={{ delay: total * STEP + 0.8 + i * 0.35, duration: 2.6, times: [0, 0.14, 0.3, 0.44, 1], repeat: Infinity, ease: 'easeInOut' }}
                      whileHover={{ rotate: 15 }}>
                      <Icon name={s.icon} size={34} />
                    </motion.div>
                  </motion.div>
                </div>
                <div className="stage-bottom">{up ? <>{pin}{card}</> : <>{pin}{label}</>}</div>
              </div>
            )
          })}
        </motion.div>

        {(d.cta_title || d.cta_btn_text) && (
          <Reveal className="process-cta" variants={fadeUp}>
            <div className="c-text">
              <motion.span className="c-icon" animate={{ rotate: [0, -10, 10, 0] }} transition={{ duration: 1.2, repeat: Infinity, repeatDelay: 2 }}><Icon name={d.cta_icon || 'timer'} size={22} /></motion.span>
              <div><strong>{d.cta_title}</strong><p>{d.cta_text}</p></div>
            </div>
            {d.cta_btn_text && <SmartLink href={d.cta_btn_link} className="btn btn-navy btn-sm">{d.cta_btn_text} <ArrowRight size={16} /></SmartLink>}
          </Reveal>
        )}
      </div>
    </section>
  )
}
