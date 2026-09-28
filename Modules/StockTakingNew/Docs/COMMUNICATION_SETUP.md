# SMS, Email and WhatsApp Setup

Every shared document uses a cryptographically random 64-character token. Only its SHA-256 hash is stored. Links support expiry, revocation metadata, access count and optional PDF download.

- **Email:** Laravel `Mail::raw()` using the application mail transport.
- **SMS without API:** creates a native `sms:` compose link containing the secure URL.
- **SMS with API:** sends JSON `{to, message, sender_id}` to the configured endpoint with optional bearer token.
- **WhatsApp without API:** creates a `wa.me` compose link containing the secure URL.
- **WhatsApp Cloud API:** sends a text message with URL preview to the configured Meta endpoint.

Gateway responses and failures are recorded in `stk_share_dispatches`. Secure link access is recorded in `stk_share_links`.
