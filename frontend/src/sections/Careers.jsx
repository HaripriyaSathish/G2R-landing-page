import { motion } from 'framer-motion'
import { ArrowRight } from 'lucide-react'
import { Eyebrow, Heading, Img, Reveal, SmartLink, fadeLeft, zoomIn } from '../components/ui'

export default function Careers({ section }) {
  const d = section.data
  return (
    <section id="careers" className="careers">
      <div className="container">
        <Reveal className="careers-box" variants={zoomIn}>
          <motion.div className="careers-text" variants={fadeLeft}>
            <Eyebrow text={d.badge} pill light />
            <Heading text={d.title} />
            {d.description && <p>{d.description}</p>}
          </motion.div>
          <motion.div className="careers-img" initial={{ opacity: 0, y: 60 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ duration: 0.9, delay: 0.3 }}>
            <Img image={d.image} alt="Careers at G2R Global Solutions" />
          </motion.div>
          {d.btn_text && <SmartLink href={d.btn_link} className="btn btn-accent btn-sm">{d.btn_text} <ArrowRight size={16} /></SmartLink>}
        </Reveal>
      </div>
    </section>
  )
}
