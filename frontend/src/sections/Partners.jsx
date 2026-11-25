import { Eyebrow, Heading, Reveal, zoomIn } from '../components/ui'

function Card({ p }) {
  const inner = (
    <>
      {p.logo?.url ? <img src={p.logo.url} alt={p.logo.alt || p.name} loading="eager" decoding="async" draggable="false" /> : <strong>{p.name}</strong>}
      <span>{p.name}</span>
    </>
  )
  return p.website
    ? <a className="partner-card" href={p.website} target="_blank" rel="noopener noreferrer">{inner}</a>
    : <div className="partner-card">{inner}</div>
}

export default function Partners({ section }) {
  const d = section.data
  const partners = section.items.partners || []
  if (!partners.length) return null
  // repeat so the strip is always wider than the screen, then double for a seamless loop
  const base = partners.length < 8 ? [...partners, ...partners, ...partners] : partners
  const loop = [...base, ...base]
  return (
    <section id="partners" className="section partners">
      <div className="container">
        <div className="section-head">
          <Eyebrow text={d.badge} center light />
          <Reveal><Heading className="section-title" text={d.title} /></Reveal>
          {d.description && <Reveal as="p" className="section-desc" i={1}>{d.description}</Reveal>}
        </div>
      </div>
      <Reveal className="marquee" variants={zoomIn} amount={0.1}>
        <div className="marquee-track">
          {loop.map((p, i) => <Card key={`${p.id}-${i}`} p={p} />)}
        </div>
      </Reveal>
    </section>
  )
}
