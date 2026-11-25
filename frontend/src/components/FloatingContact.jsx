import { motion } from 'framer-motion'
import { Mail, Phone } from 'lucide-react'

/** Digits with India country code: "93458 05849" -> "919345805849" */
function intlDigits(num) {
  let d = String(num || '').replace(/\D/g, '')
  if (d.startsWith('0')) d = d.replace(/^0+/, '')
  if (d.length === 10) d = '91' + d
  return d
}

function WhatsAppIcon({ size = 24 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.4 9.4 0 0 1-1.44-5.01c0-5.2 4.23-9.43 9.44-9.43 2.52 0 4.89.98 6.67 2.77a9.36 9.36 0 0 1 2.76 6.67c0 5.2-4.24 9.43-9.44 9.43zm8.03-17.46A11.3 11.3 0 0 0 12.05.7C5.8.7.7 5.8.7 12.05c0 2 .52 3.95 1.52 5.67L.6 23.3l5.72-1.5a11.33 11.33 0 0 0 5.42 1.38h.01c6.25 0 11.34-5.09 11.35-11.34 0-3.03-1.18-5.88-3.32-8.02z" />
    </svg>
  )
}

/**
 * Floating Call / WhatsApp / Email buttons (bottom-left).
 * Uses the same phone and email as the footer (Admin → Site Settings → Contact details).
 * WhatsApp uses the WhatsApp number if set, otherwise the phone number.
 */
export default function FloatingContact({ site }) {
  const f = site?.floating || {}
  const on = (k) => f[k] !== false // on unless switched off in admin

  // Admin overrides first, then the footer contact details
  const phone = String(f.phone || site?.phone || '').trim()
  const callDigits = intlDigits(phone)
  const waDigits = intlDigits(f.wa_number || site?.whatsapp || phone)
  const mailRaw = String(f.mail || site?.email || '').trim()
  const email = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mailRaw) ? mailRaw : ''
  const greeting = encodeURIComponent(f.wa_text || `Hello ${site?.name || ''}, I would like to know more about your services.`)

  const items = [
    on('call') && callDigits.length >= 10 && { key: 'call', label: `Call ${phone}`, href: `tel:+${callDigits}`, icon: <Phone size={21} /> },
    on('whatsapp') && waDigits.length >= 10 && { key: 'whatsapp', label: 'Chat on WhatsApp', href: `https://wa.me/${waDigits}?text=${greeting}`, icon: <WhatsAppIcon size={24} />, external: true },
    on('email') && email && { key: 'email', label: `Email ${email}`, href: `mailto:${email}`, icon: <Mail size={21} /> },
  ].filter(Boolean)

  if (!items.length) return null

  return (
    <nav className={`float-contact ${f.position === 'left' ? '' : 'right'}`} aria-label="Contact us">
      {items.map((it, i) => (
        <motion.a
          key={it.key}
          className={`fc-btn fc-${it.key}`}
          href={it.href}
          aria-label={it.label}
          {...(it.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
          initial={{ opacity: 0, x: f.position === 'left' ? -30 : 30, scale: 0.6 }}
          animate={{ opacity: 1, x: 0, scale: 1 }}
          transition={{ delay: 0.6 + i * 0.12, type: 'spring', stiffness: 260, damping: 18 }}
          whileHover={{ scale: 1.1 }}
          whileTap={{ scale: 0.92 }}
        >
          {it.icon}
          <span className="fc-tip">{it.label}</span>
        </motion.a>
      ))}
    </nav>
  )
}
