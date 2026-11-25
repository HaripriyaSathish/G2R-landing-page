import { motion } from 'framer-motion'
import { Heading, Icon, Img, Reveal, fadeLeft, fadeRight, zoomIn } from '../components/ui'

export default function Goals({ section }) {
  const d = section.data
  const goals = section.items.goals || []
  return (
    <section id="goals" className="section dark goals">
      <div className="container goals-grid">
        <Reveal className="goals-left" variants={fadeLeft}>
          {d.label && <div className="goals-label">{d.label}</div>}
          <Heading className="goals-title" text={d.title} />
          {d.description && <p>{d.description}</p>}
        </Reveal>
        <Reveal className="goals-target" variants={zoomIn}>
          <Img image={d.image} alt="Our goals" />
        </Reveal>
        <div className="goals-cards">
          {goals.map((g, i) => (
            <Reveal key={g.id} className="glass goal-card" variants={fadeRight} i={i}>
              <motion.span className="g-icon" whileHover={{ rotate: 360 }} transition={{ duration: 0.7 }}><Icon name={g.icon} size={22} /></motion.span>
              <div className="g-body"><h4>{g.title}</h4><p>{g.description}</p></div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  )
}
