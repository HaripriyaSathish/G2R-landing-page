import { Reveal, SmartLink, SocialIcon, fadeUp } from '../components/ui'

export default function Footer({ site, social, section, nav }) {
  const d = section?.data || {}
  const services = section?.items?.service_links || []
  const footerNav = section?.items?.nav_links?.length ? section.items.nav_links : nav
  const year = new Date().getFullYear()
  const logo = site.footer_logo || site.logo
  return (
    <footer className="footer">
      <div className="container">
        <div className="footer-grid">
          <Reveal variants={fadeUp}>
            <div className="footer-logo">
              {logo?.url && <img src={logo.url} alt={logo.alt || site.name} loading="lazy" />}
              <strong>{site.name}</strong>
            </div>
            {d.description && <p>{d.description}</p>}
            <div className="socials">
              {social.map((s) => <a key={s.key} href={s.url} target="_blank" rel="noopener noreferrer" aria-label={s.key}><SocialIcon name={s.key} /></a>)}
            </div>
          </Reveal>
          <Reveal variants={fadeUp} i={1}>
            <h4>{d.nav_title || 'Navigation'}</h4>
            <ul>{footerNav.map((n) => <li key={n.id}><SmartLink href={n.link}>{n.label}</SmartLink></li>)}</ul>
          </Reveal>
          <Reveal variants={fadeUp} i={2}>
            <h4>{d.services_title || 'Our Services'}</h4>
            <ul>{services.map((s) => <li key={s.id}><SmartLink href={s.link}>{s.label}</SmartLink></li>)}</ul>
          </Reveal>
          <Reveal variants={fadeUp} i={3}>
            <h4>{d.contact_title || 'Contact'}</h4>
            <ul className="footer-contact">
              {site.address && <li style={{ whiteSpace: 'pre-line' }}>{site.address}</li>}
              {site.phone && <li>Phone: <a href={`tel:${site.phone}`}><strong>{site.phone}</strong></a></li>}
              {site.email && <li>Email: <a href={`mailto:${site.email}`}><strong>{site.email}</strong></a></li>}
            </ul>
          </Reveal>
        </div>
        <div className="footer-bottom">
          <span>{(d.copyright || `© {year} ${site.name}. All rights reserved.`).replace('{year}', year)}</span>
          {d.credit_name && (
            <span className="credit">
              {d.credit_text || 'Designed & Developed by'}{' '}
              <a href={d.credit_link || '#'} target="_blank" rel="noopener noreferrer">{d.credit_name}</a>
            </span>
          )}
          <nav>
            {d.privacy_text && <SmartLink href={d.privacy_link || '#privacy'}>{d.privacy_text}</SmartLink>}
            {d.terms_text && <SmartLink href={d.terms_link || '#terms'}>{d.terms_text}</SmartLink>}
          </nav>
        </div>
      </div>
    </footer>
  )
}
