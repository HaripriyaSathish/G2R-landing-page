import { useEffect, useState } from 'react'
import Modal from '../components/Modal'
import { MiniMarkdown } from '../components/ui'

/** Privacy Policy / Terms of Service popup — opened by links to #privacy or #terms */
export default function LegalModal({ section }) {
  const d = section?.data || {}
  const [which, setWhich] = useState(null)

  useEffect(() => {
    const open = (e) => setWhich(e.detail)
    window.addEventListener('g2r:legal', open)
    if (location.hash === '#privacy' || location.hash === '#terms') setWhich(location.hash.slice(1))
    return () => window.removeEventListener('g2r:legal', open)
  }, [])

  if (!section) return null
  const isPrivacy = which === 'privacy'
  return (
    <Modal open={!!which} onClose={() => setWhich(null)} wide
      icon={isPrivacy ? 'shield-check' : 'file-text'}
      title={isPrivacy ? d.privacy_title || 'Privacy Policy' : d.terms_title || 'Terms of Service'}
      subtitle={d.updated ? `Last updated: ${d.updated}` : undefined}>
      <div className="legal-body"><MiniMarkdown text={isPrivacy ? d.privacy_content : d.terms_content} /></div>
    </Modal>
  )
}
