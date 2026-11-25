import { useState } from 'react'
import { motion } from 'framer-motion'
import { CheckCircle2, Clock, ArrowRight } from 'lucide-react'
import { Icon, Img, Reveal, SmartLink, ease, fadeLeft } from '../components/ui'
import Modal from '../components/Modal'

export default function Services({ section }) {
  const d = section.data
  const services = section.items.services || []
  const [open, setOpen] = useState(null)

  return (
    <section id="services" className="section services">
      {d.bg_image?.url && <img className="services-globe" src={d.bg_image.url} alt="" aria-hidden="true" loading="lazy" />}
      <div className="container services-grid">
        <Reveal className="services-left" variants={fadeLeft}>
          <motion.div className="bar" initial={{ width: 0 }} whileInView={{ width: 46 }} viewport={{ once: true }} transition={{ duration: 0.8, delay: 0.3 }} />
          <h2 className="services-title">{d.title_top}<span>{d.title_bottom}</span></h2>
          {d.description && <p>{d.description}</p>}
          {d.hint && <div className="services-hint"><span className="dot" />{d.hint}</div>}
        </Reveal>

        <div className="service-list">
          {services.map((s, i) => (
            <motion.div key={s.id} className="service-row service-card" role="button" tabIndex={0}
              onClick={() => setOpen(s)} onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && setOpen(s)}
              initial="hidden" whileInView="show" whileHover="hover" viewport={{ once: true, amount: 0.4 }}
              variants={{
                hidden: { opacity: 0, x: 60 },
                show: { opacity: 1, x: 0, transition: { duration: 0.7, ease, delay: i * 0.12 } },
                hover: { y: -3, boxShadow: '0 14px 34px rgba(17, 28, 80, .10)' },
              }}>
              {/* Icon pops in, then on hover it slides along the connector and "pins" into the card */}
              <motion.span className="hex"
                variants={{
                  hidden: { scale: 0, rotate: -90, opacity: 0 },
                  show: { scale: 1, rotate: 0, opacity: 1, transition: { type: 'spring', stiffness: 220, damping: 14, delay: i * 0.12 } },
                  hover: { x: 10, rotate: 0, scale: 1.05, transition: { type: 'spring', stiffness: 400, damping: 18 } },
                }}>
                <motion.span variants={{ hover: { rotate: [0, -12, 12, 0], transition: { duration: 0.5 } } }} style={{ display: 'grid' }}>
                  <Icon name={s.icon} size={26} />
                </motion.span>
              </motion.span>
              <motion.span className="connector" aria-hidden="true"
                variants={{
                  hidden: { scaleX: 0, opacity: 0 },
                  show: { scaleX: 1, opacity: 1, transition: { duration: 0.5, delay: 0.15 + i * 0.12 } },
                  hover: { scaleX: 0.55, opacity: 1, transition: { type: 'spring', stiffness: 400, damping: 18 } },
                }} style={{ transformOrigin: 'right' }} />
              <div className="service-body">
                <h3><span className="num">{String(i + 1).padStart(2, '0')}</span>{s.title}</h3>
                <p>{s.description}</p>
              </div>
            </motion.div>
          ))}
        </div>
      </div>

      <Modal open={!!open} onClose={() => setOpen(null)} title={open?.title} icon={open?.icon} subtitle="Full scope & SLA">
        {open && (
          <>
            <p>{open.description}</p>
            {open.scope?.length > 0 && (
              <ul className="scope-list">
                {open.scope.map((line, i) => (
                  <motion.li key={i} initial={{ opacity: 0, x: -20 }} animate={{ opacity: 1, x: 0 }} transition={{ delay: 0.15 + i * 0.07 }}>
                    <CheckCircle2 size={18} />{line}
                  </motion.li>
                ))}
              </ul>
            )}
            {open.sla && <div className="sla-box"><Clock size={20} /> SLA: {open.sla}</div>}
            <SmartLink href="#contact" className="btn btn-accent btn-sm" style={{ marginTop: 22 }} onClick={() => setOpen(null)}>Enquire about this service <ArrowRight size={16} /></SmartLink>
          </>
        )}
      </Modal>
    </section>
  )
}
