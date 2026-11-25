import { useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { CheckCircle2, AlertCircle, MapPin, Phone, Send, Share2, Users } from 'lucide-react'
import { Heading, Img, Reveal, SocialIcon, fadeLeft, fadeRight, fadeUp } from '../components/ui'
import { sendEnquiry } from '../api'

const NAME_MAX = 50
const EMAIL_MAX = 100
const MSG_MIN = 10
const MSG_MAX = 1000
const EMAIL_RE = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}$/

const EMPTY = { name: '', email: '', phone: '', enquiry_type: '', message: '', website: '' }

export default function Contact({ section, site, social }) {
  const d = section.data
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState(null) // {type, text}
  const [sending, setSending] = useState(false)
  const types = d.enquiry_types || []

  // Strip anything a field may not contain while the visitor types or pastes
  const clean = {
    name: (v) => v.replace(/[^A-Za-z ]/g, '').replace(/ {2,}/g, ' ').replace(/^ /, '').slice(0, NAME_MAX),
    email: (v) => v.replace(/\s/g, '').slice(0, EMAIL_MAX),
    phone: (v) => { let d = v.replace(/\D/g, ''); if (d.length > 10 && d.startsWith('91')) d = d.slice(2); return d.slice(0, 10) },
    message: (v) => v.slice(0, MSG_MAX),
  }

  const check = (k, v) => {
    const t = String(v ?? '').trim()
    switch (k) {
      case 'name':
        if (!t) return 'Please enter your name.'
        if (!/^[A-Za-z]+( [A-Za-z]+)*$/.test(t)) return 'Name can contain only letters and spaces.'
        if (t.length < 2) return 'Name must be at least 2 letters.'
        return undefined
      case 'email':
        if (!t) return 'Please enter your email address.'
        if (!EMAIL_RE.test(t)) return 'Please enter a valid email address (e.g. name@company.com).'
        return undefined
      case 'phone':
        if (!t) return 'Please enter your mobile number.'
        if (!/^\d+$/.test(t)) return 'Mobile number can contain only digits.'
        if (t.length !== 10) return 'Mobile number must be exactly 10 digits.'
        if (!/^[6-9]/.test(t)) return 'Please enter a valid mobile number starting with 6, 7, 8 or 9.'
        return undefined
      case 'enquiry_type':
        if (!t) return 'Please select an enquiry type.'
        if (types.length && !types.includes(t)) return 'Please select a valid enquiry type.'
        return undefined
      case 'message':
        if (!t) return 'Please enter your message.'
        if (t.length < MSG_MIN) return `Message must be at least ${MSG_MIN} characters.`
        return undefined
      default: return undefined
    }
  }

  const set = (k) => (e) => {
    const v = clean[k] ? clean[k](e.target.value) : e.target.value
    setForm((f) => ({ ...f, [k]: v }))
    if (errors[k]) setErrors((er) => ({ ...er, [k]: check(k, v) }))
  }
  const blur = (k) => () => {
    const v = k === 'name' ? form.name.trim() : form[k]
    if (v !== form[k]) setForm((f) => ({ ...f, [k]: v }))
    setErrors((er) => ({ ...er, [k]: check(k, v) }))
  }

  const validate = () => {
    const e = {}
    for (const k of ['name', 'email', 'phone', 'enquiry_type', 'message']) {
      const m = check(k, form[k]); if (m) e[k] = m
    }
    return e
  }

  const submit = async (ev) => {
    ev.preventDefault()
    const e = validate()
    setErrors(e)
    if (Object.keys(e).length) { document.getElementById(`c_${Object.keys(e)[0]}`)?.focus(); return }
    setSending(true); setStatus(null)
    try {
      const r = await sendEnquiry({ ...form, name: form.name.trim(), email: form.email.trim(), message: form.message.trim() })
      setStatus({ type: 'ok', text: r.message || d.success_message })
      setForm(EMPTY)
    } catch (err) {
      setErrors(err.fields || {})
      setStatus({ type: 'bad', text: err.message })
    } finally { setSending(false) }
  }

  const field = (k, label, input, full = false) => (
    <div className={`${full ? 'full' : ''} ${errors[k] ? 'error' : ''}`}>
      <label htmlFor={`c_${k}`}>{label}</label>
      {input}
      {errors[k] && <span className="err">{errors[k]}</span>}
    </div>
  )
  const phoneHref = site.phone ? `tel:${site.phone.replace(/\s/g, '')}` : null

  return (
    <section id="contact" className="section dark contact">
      <div className="container">
        <div className="contact-grid">
          <Reveal className="contact-visual" variants={fadeLeft}>
            <div className="contact-main">
              <Img image={d.image} />
              {d.image_badge && <span className="contact-badge"><span className="dot" />{d.image_badge}</span>}
            </div>
            <div className="contact-sub">
              <div className="small-img"><Img image={d.small_image} /></div>
              {(d.sla_label || d.sla_value) && <div className="sla-card"><small>{d.sla_label}</small><strong>{d.sla_value}</strong></div>}
            </div>
          </Reveal>

          <Reveal variants={fadeRight}>
            <Heading className="contact-title" text={d.title} />
            {d.description && <p className="contact-desc">{d.description}</p>}
            <form className="form" onSubmit={submit} noValidate>
              {field('name', 'Name', <input id="c_name" value={form.name} onChange={set('name')} onBlur={blur('name')} placeholder="Type Your Name" autoComplete="name" maxLength={NAME_MAX} aria-invalid={!!errors.name} />, true)}
              {field('email', 'Mail', <input id="c_email" type="email" value={form.email} onChange={set('email')} onBlur={blur('email')} placeholder="Type Your Email" autoComplete="email" maxLength={EMAIL_MAX} aria-invalid={!!errors.email} />)}
              {field('phone', 'Mobile Number', <input id="c_phone" type="tel" inputMode="numeric" value={form.phone} onChange={set('phone')} onBlur={blur('phone')} placeholder="10-digit mobile number" autoComplete="tel-national" aria-invalid={!!errors.phone} />)}
              {field('enquiry_type', 'Enquiry Type', (
                <select id="c_enquiry_type" value={form.enquiry_type} onChange={set('enquiry_type')} onBlur={blur('enquiry_type')} aria-invalid={!!errors.enquiry_type}>
                  <option value="">Select Service of Interest</option>
                  {types.map((t) => <option key={t} value={t}>{t}</option>)}
                </select>
              ), true)}
              {field('message', 'Message', <><textarea id="c_message" value={form.message} onChange={set('message')} onBlur={blur('message')} placeholder="Type Your Message..." maxLength={MSG_MAX} aria-invalid={!!errors.message} /><small className="count">{form.message.trim().length}/{MSG_MAX}</small></>, true)}
              <input className="hp" tabIndex={-1} autoComplete="off" value={form.website} onChange={set('website')} aria-hidden="true" />
              <AnimatePresence>
                {status && (
                  <motion.div className={`form-msg ${status.type}`} initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0 }}>
                    {status.type === 'ok' ? <CheckCircle2 size={20} /> : <AlertCircle size={20} />}{status.text}
                  </motion.div>
                )}
              </AnimatePresence>
              <motion.button type="submit" className="btn btn-accent" disabled={sending} whileTap={{ scale: 0.96 }}>
                {sending ? <><span className="spinner" /> Sending…</> : <>{d.submit_text || 'Submit'} <Send size={17} /></>}
              </motion.button>
            </form>
          </Reveal>
        </div>

        <Reveal className="info-card" variants={fadeUp}>
          <div className="info-col">
            <h4><MapPin size={16} />{d.office_label || 'Corporate Office'}</h4>
            <p style={{ whiteSpace: 'pre-line' }}>{site.map_url ? <a href={site.map_url} target="_blank" rel="noopener noreferrer">{site.address}</a> : site.address}</p>
          </div>
          <div className="info-col">
            <h4><Phone size={16} />{d.direct_label || 'Direct Contact'}</h4>
            {site.phone && <p>Phone: <a href={phoneHref}><strong>{site.phone}</strong></a>{site.phone_alt && <> / <a href={`tel:${site.phone_alt}`}><strong>{site.phone_alt}</strong></a></>}</p>}
            {site.email && <p>Email: <a href={`mailto:${site.email}`}><strong>{site.email}</strong></a></p>}
          </div>
          <div className="info-col">
            <h4><Users size={16} />{d.social_label || 'Social Connection'}</h4>
            <div className="socials">
              {social.map((s) => <a key={s.key} href={s.url} target="_blank" rel="noopener noreferrer" aria-label={s.key}><SocialIcon name={s.key} /></a>)}
              {!social.length && <Share2 size={18} />}
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
