import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Icon, Img, Paragraphs, Reveal, SmartLink, fadeLeft, fadeRight, fadeUp, zoomIn } from '../components/ui'

export default function About({ section }) {
  const d = section.data
  const expertise = section.items.expertise || []
  return (
    <section id="about" className="section dark about">
      <div className="container about-grid">
        <Reveal className="about-visual" variants={fadeLeft}>
          {d.image_tag && (
            <motion.div className="about-tag" animate={{ y: [0, -10, 0] }} transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }}>
              <span className="dot" />{d.image_tag}{d.image_tag_badge && <em>{d.image_tag_badge}</em>}
            </motion.div>
          )}
          <Img image={d.image} className="about-person" alt="About G2R Global Solutions" />
        </Reveal>

        <div>
          <Eyebrow text={d.badge} light />
          <Reveal variants={fadeRight}><Heading className="about-title" text={d.title} /></Reveal>
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
