import { useEffect, useRef, useState } from 'react'
import { animate, useInView } from 'framer-motion'
import { ArrowRight, Star } from 'lucide-react'
import { Eyebrow, Heading, Img, Reveal, SmartLink, fadeLeft, fadeRight, fadeUp, zoomIn } from '../components/ui'

/** Counts up the number inside a value like "98.5%" or "250+" */
function CountUp({ value = '' }) {
  const ref = useRef(null)
  const inView = useInView(ref, { once: true, amount: 0.6 })
  const m = String(value).match(/^([^\d]*)([\d.,]+)(.*)$/)
  const [shown, setShown] = useState(m ? `${m[1]}0${m[3]}` : value)
  useEffect(() => {
    if (!inView || !m) return
    const target = parseFloat(m[2].replace(/,/g, ''))
    const decimals = (m[2].split('.')[1] || '').length
    const c = animate(0, target, { duration: 1.8, ease: 'easeOut', onUpdate: (v) => setShown(`${m[1]}${v.toFixed(decimals)}${m[3]}`) })
    return () => c.stop()
  }, [inView]) // eslint-disable-line react-hooks/exhaustive-deps
  return <span ref={ref}>{m ? shown : value}</span>
}

export default function Training({ section }) {
  const d = section.data
  return (
    <section id="training" className="section training">
      <div className="container">
        <div className="section-head">
          <Eyebrow text={d.badge} pill />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>

        <div className="bento">
          <Reveal className="b-stat" variants={fadeLeft}>
            {d.stat1_intro && <p className="intro">{d.stat1_intro}</p>}
            <div className="value"><CountUp value={d.stat1_value} /></div>
            <div className="label">{d.stat1_label}</div>
            {d.stat1_text && <p className="small">{d.stat1_text}</p>}
          </Reveal>

          <Reveal className="b-feature" variants={zoomIn} i={1}>
            <div className="f-img">
              <Img image={d.feature_image} />
              <div className="f-top">
                {d.feature_badge && <span className="f-badge">{d.feature_badge}</span>}
                {d.feature_rating && <span className="f-rating"><Star size={13} /> {d.feature_rating}</span>}
              </div>
            </div>
            <div className="f-body">
              <h3>{d.feature_title}</h3>
              <p>{d.feature_text}</p>
              <div className="f-foot"><span>{d.feature_footer}</span><strong>{d.feature_footer_highlight}</strong></div>
            </div>
          </Reveal>

          <Reveal className="b-img" variants={fadeRight} i={2}>
            <Img image={d.card2_image} />
            {d.card2_caption && <span className="caption">{d.card2_caption}</span>}
          </Reveal>

          <Reveal className="b-img" variants={fadeLeft} i={2}>
            <Img image={d.card3_image} />
            {(d.card3_label || d.card3_title) && (
              <div className="overlay"><small>{d.card3_label}</small><strong>{d.card3_title}</strong></div>
            )}
          </Reveal>

          <Reveal className="b-stat2" variants={fadeUp} i={3}>
            <div className="value"><CountUp value={d.stat2_value} /></div>
            <div className="label">{d.stat2_label}</div>
            {d.stat2_text && <p>{d.stat2_text}</p>}
            <div className="row">
              {d.stat2_link_text && <SmartLink href={d.stat2_link}>{d.stat2_link_text} <ArrowRight size={15} /></SmartLink>}
              {d.stat2_tag && <em>{d.stat2_tag}</em>}
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  )
}
