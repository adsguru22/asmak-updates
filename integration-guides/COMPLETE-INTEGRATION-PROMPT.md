# 🚀 COMPLETE INTEGRATION PROMPT

Full prompt untuk integrate semua systems ke dalam repo `adsguru-command-cent` yang dah generate dari Spark.

---

## 📋 CONTEXT

Anda ada 2 repositories:

1. **asmak-updates** (this repo) - Update server & landing page
2. **adsguru-command-cent** (Spark generated) - Main sales landing page

Anda nak integrate:
- ✅ Gated content system (email capture + paywall)
- ✅ bcl.my payment gateway
- ✅ Supabase backend
- ✅ Email sequences
- ✅ License management
- ✅ Download system

---

## 🎯 FINAL ARCHITECTURE

```
┌─────────────────────────────────────────────────────────────┐
│                    GitHub Pages                              │
│  https://adsguru22.github.io/adsguru-command-cent           │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Landing Page (from Spark)                           │  │
│  │  - Hero section                                      │  │
│  │  - Features showcase                                 │  │
│  │  - Pricing table with modals                        │  │
│  │  - Free tier signup form                            │  │
│  │  - Premium purchase flow                            │  │
│  └──────────────────────────────────────────────────────┘  │
│                           │                                  │
│                           ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Additional Pages                                    │  │
│  │  - /download - Download portal                      │  │
│  │  - /payment-success - Payment confirmation          │  │
│  │  - /verify-license - License verification           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    Supabase Backend                          │
│  https://xxx.supabase.co                                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database (PostgreSQL)                               │  │
│  │  - users, licenses, payments, downloads             │  │
│  └──────────────────────────────────────────────────────┘  │
│                           │                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Edge Functions (Deno)                               │  │
│  │  - signup-free - Free tier registration             │  │
│  │  - create-payment - bcl.my payment init             │  │
│  │  - bcl-webhook - Payment callback                   │  │
│  │  - verify-license - License validation              │  │
│  │  - download-plugin - Secure download                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    External Services                         │
│                                                              │
│  bcl.my - Payment Gateway                                   │
│  SendGrid - Email delivery                                  │
│  GitHub Releases - Plugin distribution                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔨 STEP-BY-STEP IMPLEMENTATION

### PHASE 1: Setup Backend (Supabase)

**Prompt for Cursor/Claude:**

```
Task: Setup Supabase backend for adsguru Command Center plugin sales system.

Requirements:
1. Create Supabase project named "adsguru-command-center"
2. Setup database with these tables:
   - users (email, name, tier, status)
   - licenses (user_id, license_key, tier, status, expires_at)
   - activations (license_id, site_url, activated_at)
   - payments (user_id, amount, status, transaction_id)
   - downloads (user_id, version, ip_address, downloaded_at)
   - email_sequences (user_id, sequence_name, status)
   - analytics_events (event_name, event_data, user_id)

3. Enable Row Level Security (RLS) with policies:
   - Users can only view their own data
   - Service role has full access
   - Analytics allows anonymous inserts

4. Create Edge Functions:
   - signup-free: Handle free tier registration
   - create-payment: Initialize bcl.my payment
   - bcl-webhook: Process payment callbacks
   - verify-license: Validate license keys
   - download-plugin: Secure download with license check

Use the database schema from `/integration-guides/supabase-setup.md` in the asmak-updates repo.

Environment variables needed:
- BCL_API_KEY
- BCL_SECRET_KEY
- BCL_WEBHOOK_SECRET
- SENDGRID_API_KEY

Generate complete SQL schema and TypeScript Edge Functions.
```

### PHASE 2: Integrate Gated Content into Landing Page

**Prompt for Cursor/Claude:**

```
Task: Add gated content modals to the Spark-generated landing page.

Context:
- Landing page already exists in adsguru-command-cent repo
- Need to add email capture for free tier
- Need to add payment flow for premium tiers

Requirements:

1. FREE TIER MODAL:
Add modal triggered by "Start Free Trial" buttons with:
- Email input (required)
- Name input (required)
- Website URL (optional)
- Privacy notice
- Submit button calling Supabase function

2. PREMIUM PURCHASE MODAL:
Add modal triggered by pricing table "Purchase" buttons with:
- Plan selection (Pro, Agency, Lifetime)
- User info form (email, name)
- Proceed to Payment button
- Redirect to bcl.my payment page

3. JAVASCRIPT INTEGRATION:
```javascript
// Free tier signup
async function signupFree(email, name, website) {
  const response = await fetch('SUPABASE_URL/functions/v1/signup-free', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer SUPABASE_ANON_KEY'
    },
    body: JSON.stringify({ email, name, website })
  })
  const data = await response.json()
  if (data.success) {
    window.location.href = data.downloadUrl
  }
}

// Premium purchase
async function createPayment(email, name, plan, amount) {
  const response = await fetch('SUPABASE_URL/functions/v1/create-payment', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer SUPABASE_ANON_KEY'
    },
    body: JSON.stringify({ email, name, plan, amount })
  })
  const data = await response.json()
  if (data.success) {
    window.location.href = data.paymentUrl // Redirect to bcl.my
  }
}
```

4. CSS STYLING:
Use modern modal styling with:
- Backdrop blur
- Smooth animations
- Mobile responsive
- High-contrast CTAs

Reference: `/integration-guides/gated-content-system.md` for complete implementation.

Add these modals to existing landing page WITHOUT replacing existing content.
```

### PHASE 3: Add Download Portal

**Prompt for Cursor/Claude:**

```
Task: Create secure download portal page.

Create new file: `/download/index.html`

Requirements:

1. LICENSE VERIFICATION:
- Get license key from URL parameter or form input
- Verify license via Supabase Edge Function
- Check license status (active/expired)
- Check activation limits

2. DOWNLOAD INTERFACE:
```html
<div class="download-portal">
  <h1>Download adsguru Command Center</h1>
  
  <!-- License verification form -->
  <form id="verifyForm">
    <input type="text" placeholder="Enter your license key" />
    <button>Verify & Download</button>
  </form>
  
  <!-- Download section (shown after verification) -->
  <div id="downloadSection" class="hidden">
    <div class="license-info">
      <p>License: <strong id="licenseKey"></strong></p>
      <p>Tier: <strong id="tier"></strong></p>
      <p>Status: <strong id="status"></strong></p>
    </div>
    
    <div class="version-selector">
      <select id="versionSelect">
        <option value="1.2.0">Version 1.2.0 (Latest)</option>
        <option value="1.1.0">Version 1.1.0</option>
      </select>
    </div>
    
    <button id="downloadBtn" class="download-btn">
      Download Plugin
    </button>
    
    <div class="installation-guide">
      <h3>Installation Instructions</h3>
      <ol>
        <li>Upload ZIP to WordPress</li>
        <li>Activate plugin</li>
        <li>Enter license key</li>
        <li>Configure settings</li>
      </ol>
    </div>
  </div>
</div>
```

3. JAVASCRIPT LOGIC:
```javascript
async function verifyLicense(licenseKey) {
  const response = await fetch('SUPABASE_URL/functions/v1/verify-license', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer SUPABASE_ANON_KEY'
    },
    body: JSON.stringify({ licenseKey })
  })
  return await response.json()
}

async function downloadPlugin(licenseKey, version) {
  const response = await fetch('SUPABASE_URL/functions/v1/download-plugin', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer SUPABASE_ANON_KEY'
    },
    body: JSON.stringify({ licenseKey, version })
  })
  const data = await response.json()
  if (data.downloadUrl) {
    window.location.href = data.downloadUrl
  }
}
```

4. SECURITY:
- Rate limit downloads (max 5 per hour per IP)
- Track download in analytics
- Verify license hasn't exceeded activation limit
- Log all download attempts

Reference: Complete implementation in `/integration-guides/gated-content-system.md`
```

### PHASE 4: Add Payment Success Page

**Prompt for Cursor/Claude:**

```
Task: Create payment success confirmation page.

Create new file: `/payment-success/index.html`

Requirements:

1. PAYMENT STATUS POLLING:
- Get payment ID from URL parameter
- Poll Supabase every second for payment status
- Maximum 30 seconds polling
- Show appropriate UI based on status

2. UI STATES:
```html
<!-- Processing state -->
<div id="processing">
  <div class="spinner"></div>
  <h2>Processing Your Payment...</h2>
</div>

<!-- Success state -->
<div id="success" class="hidden">
  <svg class="checkmark"><!-- Animated checkmark --></svg>
  <h1>Payment Successful!</h1>
  <p>License Key: <strong id="licenseKey"></strong></p>
  <p>Check your email for details.</p>
  <a href="/download" class="btn">Download Now</a>
</div>

<!-- Failed state -->
<div id="failed" class="hidden">
  <h1>Payment Failed</h1>
  <p>Your payment could not be processed.</p>
  <a href="/" class="btn">Try Again</a>
</div>
```

3. POLLING LOGIC:
```javascript
const paymentId = new URLSearchParams(window.location.search).get('id')

let pollCount = 0
const pollInterval = setInterval(async () => {
  pollCount++
  
  const response = await fetch('SUPABASE_URL/functions/v1/check-payment', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer SUPABASE_ANON_KEY'
    },
    body: JSON.stringify({ paymentId })
  })
  
  const data = await response.json()
  
  if (data.status === 'completed') {
    clearInterval(pollInterval)
    showSuccess(data.licenseKey, data.downloadUrl)
  } else if (data.status === 'failed' || pollCount >= 30) {
    clearInterval(pollInterval)
    showFailed()
  }
}, 1000)
```

4. ANIMATIONS:
- Spinner animation while processing
- Checkmark SVG animation on success
- Smooth transitions between states

Reference: `/integration-guides/bcl-payment-integration.md` for complete code.
```

### PHASE 5: Configure bcl.my Integration

**Prompt for Cursor/Claude:**

```
Task: Implement bcl.my payment gateway integration in Supabase Edge Functions.

Requirements:

1. CREATE PAYMENT FUNCTION:
File: `supabase/functions/create-payment/index.ts`

Logic:
- Validate user input (email, name, plan, amount)
- Create user record if doesn't exist
- Create payment record in database
- Call bcl.my API to create bill
- Return payment URL to frontend

bcl.my API call:
```typescript
const response = await fetch('https://www.billplz.com/api/v3/bills', {
  method: 'POST',
  headers: {
    'Authorization': `Basic ${btoa(BCL_API_KEY + ':')}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    collection_id: BCL_COLLECTION_ID,
    email,
    name,
    amount: amount * 100, // Convert to cents
    callback_url: 'SUPABASE_URL/functions/v1/bcl-webhook',
    redirect_url: 'GITHUB_PAGES_URL/payment-success',
    description: `adsguru ${plan} License`,
    reference_1: paymentId
  })
})
```

2. WEBHOOK HANDLER:
File: `supabase/functions/bcl-webhook/index.ts`

Logic:
- Receive webhook from bcl.my
- Verify webhook signature (HMAC SHA256)
- Update payment status in database
- If successful:
  * Generate license key
  * Create license record
  * Update user tier
  * Send email with license key
- Return 200 OK response

Signature verification:
```typescript
function verifyWebhook(payload, signature, secret) {
  const signatureString = Object.keys(payload)
    .filter(key => key !== 'x_signature')
    .sort()
    .map(key => `${key}${payload[key]}`)
    .join('|')
  
  const hmac = crypto.createHmac('sha256', secret)
  hmac.update(signatureString)
  const calculated = hmac.digest('hex')
  
  return calculated === signature
}
```

3. ENVIRONMENT VARIABLES:
Set in Supabase:
```bash
supabase secrets set BCL_API_KEY=your_api_key
supabase secrets set BCL_SECRET_KEY=your_secret_key
supabase secrets set BCL_COLLECTION_ID=your_collection_id
supabase secrets set BCL_WEBHOOK_SECRET=your_webhook_secret
```

4. ERROR HANDLING:
- Log all webhook events
- Retry failed license generation
- Alert admin on payment anomalies

Reference: Complete implementation in `/integration-guides/bcl-payment-integration.md`
```

### PHASE 6: Setup Email Automation

**Prompt for Cursor/Claude:**

```
Task: Integrate SendGrid for email automation.

Requirements:

1. FREE TIER WELCOME EMAIL (Immediate):
```
Subject: Your adsguru Command Center is Ready! 🚀

Hi {{name}},

Welcome to adsguru Command Center!

Your license key: {{license_key}}

Download here: {{download_url}}

Quick Start:
1. Download the plugin
2. Upload to WordPress (/wp-content/plugins/)
3. Activate and enter your license key
4. Start connecting APIs!

Need help? Reply to this email.

Best regards,
adsguru Team
```

2. UPGRADE NURTURE SEQUENCE (7 emails):
- Day 1: Setup help & quick start
- Day 2: Feature showcase (WhatsApp integration)
- Day 3: Case study & testimonial
- Day 4: Feature showcase (Payment gateways)
- Day 5: Upgrade offer (20% discount)
- Day 6: Testimonials & social proof
- Day 7: Last chance (urgency)

3. SENDGRID INTEGRATION:
```typescript
async function sendEmail(to, templateId, data) {
  const response = await fetch('https://api.sendgrid.com/v3/mail/send', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${SENDGRID_API_KEY}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      personalizations: [{
        to: [{ email: to }],
        dynamic_template_data: data
      }],
      from: {
        email: 'noreply@adsguru.com',
        name: 'adsguru Team'
      },
      template_id: templateId
    })
  })
  
  if (!response.ok) {
    throw new Error(`SendGrid error: ${await response.text()}`)
  }
}
```

4. SCHEDULE EMAILS:
Use Supabase cron jobs or external scheduler to send sequence emails based on user signup date.

Reference: Email templates and scheduling logic in integration guides.
```

---

## 🔗 CONNECT EVERYTHING

### Final Integration Checklist:

**Prompt for Cursor/Claude:**

```
Task: Final integration and testing of complete system.

Steps:

1. LINK REPOSITORIES:
- Update index.html in adsguru-command-cent with gated content modals
- Keep update mechanism in asmak-updates repo
- Cross-link pages: Landing → Download → Payment Success

2. CONFIGURE URLS:
Replace all placeholders:
- SUPABASE_URL → your Supabase project URL
- SUPABASE_ANON_KEY → your anon key
- BCL_API_KEY → your bcl.my API key
- GITHUB_PAGES_URL → https://adsguru22.github.io/adsguru-command-cent

3. ENABLE GITHUB PAGES:
- Go to adsguru-command-cent repository settings
- Enable GitHub Pages from main/master branch
- Set custom domain (optional)

4. TEST FLOWS:
Free tier signup:
- [ ] Fill form on landing page
- [ ] Receive email with license key
- [ ] Download free version
- [ ] Verify license works in WordPress

Premium purchase:
- [ ] Select plan and click purchase
- [ ] Fill checkout form
- [ ] Complete payment on bcl.my (use sandbox)
- [ ] Receive webhook callback
- [ ] Get license email
- [ ] Download premium version
- [ ] Verify all features unlocked

5. MONITORING:
- Setup error alerts in Supabase
- Monitor bcl.my dashboard
- Track email delivery rates
- Check download analytics

6. SECURITY:
- Enable rate limiting on Edge Functions
- Setup CORS properly
- Verify webhook signatures
- Enable RLS policies
- Use HTTPS everywhere

7. GO LIVE:
- Switch bcl.my from sandbox to production
- Update payment amounts to real prices
- Enable real email sending
- Announce launch!
```

---

## 📊 ANALYTICS TRACKING

Add tracking events throughout the flow:

```javascript
// Track key events
function trackEvent(eventName, eventData = {}) {
  fetch('SUPABASE_URL/functions/v1/track-event', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      event_name: eventName,
      event_data: eventData,
      page_url: window.location.href,
      user_agent: navigator.userAgent
    })
  })
}

// Track these events:
trackEvent('page_view', { page: 'landing' })
trackEvent('cta_click', { cta: 'start_free_trial' })
trackEvent('free_signup_started')
trackEvent('free_signup_completed', { email })
trackEvent('premium_interest', { plan })
trackEvent('checkout_started', { plan, amount })
trackEvent('payment_completed', { plan, amount, license })
trackEvent('download', { tier, version })
```

---

## ✅ SUCCESS CRITERIA

System is complete when:

- [x] Free tier signup works end-to-end
- [x] Premium purchase flows to bcl.my
- [x] Payment webhook generates license
- [x] Emails are sent automatically
- [x] Download portal verifies licenses
- [x] WordPress plugin accepts licenses
- [x] Analytics tracks all events
- [x] Error handling is robust
- [x] Security is properly configured
- [x] Mobile responsive
- [x] Load time < 3 seconds

---

## 🆘 TROUBLESHOOTING PROMPTS

If something doesn't work, use these prompts:

**Debug webhook not receiving:**
```
Debug: bcl.my webhook not triggering Supabase function.

Check:
1. Webhook URL is publicly accessible
2. HTTPS is enabled
3. Supabase function is deployed
4. bcl.my webhook URL configured correctly
5. Check Supabase function logs
6. Verify webhook secret matches

Show me how to add detailed logging to the webhook function.
```

**Debug license not generating:**
```
Debug: Payment successful but license not created.

Check:
1. Webhook signature verification passing
2. Database insert permissions (RLS policies)
3. License key generation function
4. User_id exists and matches
5. Check Supabase logs for errors

Show me how to add try-catch and logging to diagnose the issue.
```

**Debug email not sending:**
```
Debug: Emails not being delivered.

Check:
1. SendGrid API key is correct
2. From email is verified in SendGrid
3. Email template exists
4. No SendGrid errors in logs
5. Check spam folder
6. Verify recipient email is valid

Show me how to test email sending with detailed error logging.
```

---

## 🎉 YOU'RE DONE!

Semua systems sekarang integrated:
✅ Beautiful landing page (Spark)
✅ Gated content (Supabase)
✅ Payment processing (bcl.my)
✅ License management (Supabase)
✅ Email automation (SendGrid)
✅ Secure downloads (GitHub + Supabase)
✅ Analytics tracking (Supabase)

**Your complete adsguru Command Center sales system is LIVE!** 🚀
