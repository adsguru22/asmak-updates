# 🔐 Gated Content System - Implementation Guide

Complete system untuk email capture, free tier access, dan premium paywall.

---

## 🎯 Overview

**Free Tier (Lead Magnet):**
- User masukkan email
- Instant download link untuk free version
- Auto-enrolled dalam email sequence
- Limited features

**Premium Tier (Paid):**
- Payment via bcl.my
- Full access semua features
- License key auto-generated
- Stored di Supabase

---

## 📋 Database Schema (Supabase)

### Table: `users`
```sql
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  email TEXT UNIQUE NOT NULL,
  name TEXT,
  tier TEXT DEFAULT 'free', -- 'free', 'pro', 'agency', 'lifetime'
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tier ON users(tier);
```

### Table: `licenses`
```sql
CREATE TABLE licenses (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  license_key TEXT UNIQUE NOT NULL,
  tier TEXT NOT NULL,
  status TEXT DEFAULT 'active', -- 'active', 'expired', 'cancelled'
  expires_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_licenses_key ON licenses(license_key);
CREATE INDEX idx_licenses_user ON licenses(user_id);
CREATE INDEX idx_licenses_status ON licenses(status);
```

### Table: `downloads`
```sql
CREATE TABLE downloads (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  license_id UUID REFERENCES licenses(id) ON DELETE SET NULL,
  version TEXT NOT NULL,
  ip_address TEXT,
  user_agent TEXT,
  downloaded_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_downloads_user ON downloads(user_id);
CREATE INDEX idx_downloads_date ON downloads(downloaded_at);
```

### Table: `payments`
```sql
CREATE TABLE payments (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  license_id UUID REFERENCES licenses(id) ON DELETE SET NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency TEXT DEFAULT 'MYR',
  payment_gateway TEXT DEFAULT 'bcl',
  transaction_id TEXT UNIQUE,
  status TEXT DEFAULT 'pending', -- 'pending', 'completed', 'failed', 'refunded'
  metadata JSONB,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);
```

---

## 🌐 Frontend Implementation

### 1. Free Tier Signup Modal

**HTML Structure:**
```html
<!-- Free Tier Modal -->
<div id="freeTierModal" class="modal hidden">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>🎉 Start Free Trial</h2>
    <p>Get instant access to 2 modules and 5 APIs. No credit card required.</p>
    
    <form id="freeTierForm">
      <input type="email" id="email" name="email" required 
             placeholder="Your email address">
      <input type="text" id="name" name="name" required 
             placeholder="Your name">
      <input type="url" id="website" name="website" 
             placeholder="Website URL (optional)">
      
      <button type="submit" class="btn-primary">
        Get Free Access Now
      </button>
    </form>
    
    <p class="privacy-note">
      We respect your privacy. Unsubscribe anytime.
    </p>
  </div>
</div>
```

**JavaScript (Vanilla):**
```javascript
// Free Tier Signup
document.getElementById('freeTierForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = {
    email: document.getElementById('email').value,
    name: document.getElementById('name').value,
    website: document.getElementById('website').value
  };
  
  try {
    // Call Supabase Edge Function
    const response = await fetch('YOUR_SUPABASE_URL/functions/v1/signup-free', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_ANON_KEY'
      },
      body: JSON.stringify(formData)
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Show success message
      showSuccessMessage(data.downloadUrl);
      
      // Track conversion
      trackEvent('free_signup', { email: formData.email });
      
      // Redirect to download page
      setTimeout(() => {
        window.location.href = data.downloadUrl;
      }, 2000);
    }
  } catch (error) {
    showError('Signup failed. Please try again.');
  }
});
```

### 2. Premium Paywall

**HTML Structure:**
```html
<!-- Premium Purchase Modal -->
<div id="premiumModal" class="modal hidden">
  <div class="modal-content">
    <h2>🚀 Unlock Full Access</h2>
    
    <!-- Pricing Selection -->
    <div class="pricing-selector">
      <div class="plan" data-plan="pro" data-price="97">
        <h3>Professional</h3>
        <p class="price">RM97/year</p>
        <ul>
          <li>All 11 modules</li>
          <li>42+ APIs</li>
          <li>1 site license</li>
        </ul>
      </div>
      
      <div class="plan popular" data-plan="agency" data-price="297">
        <span class="badge">Most Popular</span>
        <h3>Agency</h3>
        <p class="price">RM297/year</p>
        <ul>
          <li>Everything in Pro</li>
          <li>Unlimited sites</li>
          <li>White-label</li>
        </ul>
      </div>
      
      <div class="plan" data-plan="lifetime" data-price="997">
        <h3>Lifetime</h3>
        <p class="price">RM997 one-time</p>
        <ul>
          <li>Everything in Agency</li>
          <li>Lifetime updates</li>
          <li>No recurring fees</li>
        </ul>
      </div>
    </div>
    
    <!-- User Info Form -->
    <form id="checkoutForm" class="hidden">
      <input type="email" id="checkoutEmail" required 
             placeholder="Email address">
      <input type="text" id="checkoutName" required 
             placeholder="Full name">
      
      <button type="submit" class="btn-purchase">
        Proceed to Payment
      </button>
    </form>
  </div>
</div>
```

**JavaScript:**
```javascript
// Premium Purchase Flow
let selectedPlan = null;

document.querySelectorAll('.plan').forEach(plan => {
  plan.addEventListener('click', () => {
    // Update selection
    document.querySelectorAll('.plan').forEach(p => p.classList.remove('selected'));
    plan.classList.add('selected');
    
    selectedPlan = {
      plan: plan.dataset.plan,
      price: plan.dataset.price
    };
    
    // Show checkout form
    document.getElementById('checkoutForm').classList.remove('hidden');
  });
});

document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const checkoutData = {
    email: document.getElementById('checkoutEmail').value,
    name: document.getElementById('checkoutName').value,
    plan: selectedPlan.plan,
    amount: selectedPlan.price
  };
  
  try {
    // Create payment session
    const response = await fetch('YOUR_SUPABASE_URL/functions/v1/create-payment', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_ANON_KEY'
      },
      body: JSON.stringify(checkoutData)
    });
    
    const data = await response.json();
    
    if (data.paymentUrl) {
      // Redirect to bcl.my payment page
      window.location.href = data.paymentUrl;
    }
  } catch (error) {
    showError('Payment initialization failed. Please try again.');
  }
});
```

---

## 🔌 Supabase Edge Functions

### Function 1: `signup-free`

**File: `supabase/functions/signup-free/index.ts`**
```typescript
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const { email, name, website } = await req.json()
    
    // Initialize Supabase client
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    // Check if user exists
    const { data: existingUser } = await supabase
      .from('users')
      .select('id, email')
      .eq('email', email)
      .single()
    
    let userId
    
    if (existingUser) {
      userId = existingUser.id
    } else {
      // Create new user
      const { data: newUser, error } = await supabase
        .from('users')
        .insert({
          email,
          name,
          tier: 'free'
        })
        .select()
        .single()
      
      if (error) throw error
      userId = newUser.id
    }
    
    // Generate free license
    const licenseKey = generateLicenseKey('FREE')
    
    await supabase
      .from('licenses')
      .insert({
        user_id: userId,
        license_key: licenseKey,
        tier: 'free',
        status: 'active'
      })
    
    // Send welcome email (integrate with your email service)
    await sendWelcomeEmail(email, name, licenseKey)
    
    return new Response(
      JSON.stringify({
        success: true,
        downloadUrl: `https://adsguru22.github.io/asmak-updates/download?key=${licenseKey}`,
        licenseKey
      }),
      { headers: { 'Content-Type': 'application/json' } }
    )
  } catch (error) {
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 400, headers: { 'Content-Type': 'application/json' } }
    )
  }
})

function generateLicenseKey(prefix: string): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  let key = prefix + '-'
  
  for (let i = 0; i < 4; i++) {
    for (let j = 0; j < 4; j++) {
      key += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    if (i < 3) key += '-'
  }
  
  return key
}

async function sendWelcomeEmail(email: string, name: string, licenseKey: string) {
  // Integrate with your email service (SendGrid, Mailgun, etc.)
  // Example placeholder
  console.log(`Send welcome email to ${email} with key ${licenseKey}`)
}
```

### Function 2: `create-payment`

**File: `supabase/functions/create-payment/index.ts`**
```typescript
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const { email, name, plan, amount } = await req.json()
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    // Get or create user
    let { data: user } = await supabase
      .from('users')
      .select('id')
      .eq('email', email)
      .single()
    
    if (!user) {
      const { data: newUser } = await supabase
        .from('users')
        .insert({ email, name, tier: 'free' })
        .select()
        .single()
      user = newUser
    }
    
    // Create payment record
    const { data: payment } = await supabase
      .from('payments')
      .insert({
        user_id: user.id,
        amount: amount,
        currency: 'MYR',
        payment_gateway: 'bcl',
        status: 'pending',
        metadata: { plan }
      })
      .select()
      .single()
    
    // Create bcl.my payment URL
    const bclPaymentUrl = await createBCLPayment({
      amount,
      email,
      name,
      reference: payment.id,
      returnUrl: `https://adsguru22.github.io/asmak-updates/payment-success`,
      callbackUrl: `YOUR_SUPABASE_URL/functions/v1/payment-callback`
    })
    
    return new Response(
      JSON.stringify({
        success: true,
        paymentUrl: bclPaymentUrl,
        paymentId: payment.id
      }),
      { headers: { 'Content-Type': 'application/json' } }
    )
  } catch (error) {
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 400, headers: { 'Content-Type': 'application/json' } }
    )
  }
})

async function createBCLPayment(data: any): string {
  // See bcl-payment-integration.md for detailed implementation
  return 'BCL_PAYMENT_URL'
}
```

### Function 3: `payment-callback`

**File: `supabase/functions/payment-callback/index.ts`**
```typescript
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const payload = await req.json()
    
    // Verify bcl.my signature (see bcl-payment-integration.md)
    if (!verifyBCLSignature(payload)) {
      throw new Error('Invalid signature')
    }
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    const { reference, status, transaction_id } = payload
    
    // Update payment record
    await supabase
      .from('payments')
      .update({
        status: status === 'success' ? 'completed' : 'failed',
        transaction_id,
        updated_at: new Date().toISOString()
      })
      .eq('id', reference)
    
    if (status === 'success') {
      // Get payment details
      const { data: payment } = await supabase
        .from('payments')
        .select('user_id, metadata')
        .eq('id', reference)
        .single()
      
      // Generate premium license
      const plan = payment.metadata.plan
      const licenseKey = generateLicenseKey(plan.toUpperCase())
      
      // Create license
      await supabase
        .from('licenses')
        .insert({
          user_id: payment.user_id,
          license_key: licenseKey,
          tier: plan,
          status: 'active',
          expires_at: plan === 'lifetime' ? null : getExpiryDate()
        })
      
      // Update user tier
      await supabase
        .from('users')
        .update({ tier: plan })
        .eq('id', payment.user_id)
      
      // Send license email
      await sendLicenseEmail(payment.user_id, licenseKey, plan)
    }
    
    return new Response('OK', { status: 200 })
  } catch (error) {
    console.error('Payment callback error:', error)
    return new Response('Error', { status: 400 })
  }
})

function verifyBCLSignature(payload: any): boolean {
  // Implement bcl.my signature verification
  return true
}

function generateLicenseKey(prefix: string): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  let key = prefix + '-'
  
  for (let i = 0; i < 4; i++) {
    for (let j = 0; j < 4; j++) {
      key += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    if (i < 3) key += '-'
  }
  
  return key
}

function getExpiryDate(): string {
  const date = new Date()
  date.setFullYear(date.getFullYear() + 1)
  return date.toISOString()
}

async function sendLicenseEmail(userId: string, licenseKey: string, plan: string) {
  // Send email with license key
  console.log(`Send license email for ${plan}: ${licenseKey}`)
}
```

---

## 🎨 CSS Styling

```css
/* Modal Styling */
.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.7);
}

.modal.show {
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: white;
  padding: 40px;
  border-radius: 20px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

/* Pricing Cards */
.pricing-selector {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin: 30px 0;
}

.plan {
  border: 2px solid #e5e7eb;
  border-radius: 15px;
  padding: 30px 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
}

.plan:hover {
  border-color: #667eea;
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
}

.plan.selected {
  border-color: #667eea;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.plan.popular {
  position: relative;
  border-color: #667eea;
}

.plan .badge {
  position: absolute;
  top: -10px;
  right: 10px;
  background: #f59e0b;
  color: white;
  padding: 5px 15px;
  border-radius: 20px;
  font-size: 0.8em;
  font-weight: 600;
}

/* Form Styling */
form input {
  width: 100%;
  padding: 15px;
  margin: 10px 0;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 16px;
}

form input:focus {
  outline: none;
  border-color: #667eea;
}

.btn-primary, .btn-purchase {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.btn-primary:hover, .btn-purchase:hover {
  transform: translateY(-2px);
}
```

---

## 📧 Email Sequences

### Free Tier Welcome Sequence (7 emails)

**Email 1 (Immediate):**
```
Subject: Your adsguru Command Center is Ready! 🚀

Hi {{name}},

Your free license key: {{license_key}}

Download: {{download_url}}

Quick Start:
1. Upload plugin to WordPress
2. Activate
3. Enter your license key

Need help? Reply to this email.

Best,
adsguru Team
```

**Emails 2-7:** See SETUP_GUIDE.md for complete sequence

---

## ✅ Implementation Checklist

- [ ] Setup Supabase project
- [ ] Create database tables
- [ ] Deploy Edge Functions
- [ ] Configure bcl.my account
- [ ] Setup email service (SendGrid/Mailgun)
- [ ] Add modals to landing page
- [ ] Test free signup flow
- [ ] Test payment flow
- [ ] Test license generation
- [ ] Test email sequences
- [ ] Enable Row Level Security (RLS)
- [ ] Add analytics tracking

---

## 🔒 Security Considerations

1. **Rate Limiting:** Limit signups per IP
2. **Email Validation:** Verify email addresses
3. **CAPTCHA:** Prevent bot signups
4. **Webhook Verification:** Verify bcl.my signatures
5. **RLS Policies:** Secure Supabase data
6. **API Keys:** Use environment variables
7. **HTTPS Only:** Enforce secure connections

---

## 📊 Analytics Events to Track

```javascript
// Track these events
trackEvent('page_view', { page: 'landing' })
trackEvent('cta_click', { cta: 'start_free_trial' })
trackEvent('free_signup_started', { })
trackEvent('free_signup_completed', { email })
trackEvent('premium_interest', { plan })
trackEvent('checkout_started', { plan, amount })
trackEvent('payment_completed', { plan, amount })
trackEvent('download', { tier, version })
```

---

**Next Steps:** Implement bcl.my integration (see bcl-payment-integration.md)
