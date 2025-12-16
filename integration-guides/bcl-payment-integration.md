# 💳 bcl.my Payment Integration Guide

Complete implementation guide untuk integrate bcl.my payment gateway dengan sistem adsguru.

---

## 📋 Overview

**bcl.my** adalah payment gateway Malaysia yang support:
- FPX (Online Banking)
- Credit/Debit Cards
- E-wallets (Touch 'n Go, Boost, GrabPay, etc.)
- BNPL (Buy Now Pay Later)

---

## 🔑 Setup bcl.my Account

### 1. Register Account
1. Pergi ke https://bcl.my
2. Sign up untuk merchant account
3. Complete KYC verification
4. Get API credentials

### 2. Get API Credentials

Selepas approval, dapatkan:
- **API Key** (Public)
- **Secret Key** (Private)
- **Merchant ID**
- **Webhook Secret**

**Simpan dalam environment variables:**
```bash
BCL_API_KEY=your_api_key
BCL_SECRET_KEY=your_secret_key
BCL_MERCHANT_ID=your_merchant_id
BCL_WEBHOOK_SECRET=your_webhook_secret
```

---

## 🔌 API Integration

### Payment Flow

```
1. User clicks "Purchase" on your site
2. Create payment bill via bcl.my API
3. Redirect user to bcl.my payment page
4. User completes payment
5. bcl.my sends webhook to your server
6. Verify webhook signature
7. Update payment status in database
8. Generate license key
9. Send email to user
```

---

## 💻 Implementation Code

### 1. Create Payment Bill

**Supabase Edge Function: `create-bcl-payment`**

```typescript
// supabase/functions/create-bcl-payment/index.ts
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const { email, name, plan, amount } = await req.json()
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    // Create payment record in database
    const { data: payment } = await supabase
      .from('payments')
      .insert({
        user_id: userId,
        amount,
        currency: 'MYR',
        payment_gateway: 'bcl',
        status: 'pending',
        metadata: { plan, email, name }
      })
      .select()
      .single()
    
    // Create bcl.my bill
    const bclResponse = await createBCLBill({
      amount: amount * 100, // Convert to cents
      email,
      name,
      description: `adsguru Command Center - ${plan} License`,
      reference_1: payment.id, // Your internal payment ID
      callback_url: `${Deno.env.get('SUPABASE_URL')}/functions/v1/bcl-webhook`,
      redirect_url: `https://adsguru22.github.io/asmak-updates/payment-success?id=${payment.id}`,
      reference_1_label: 'Payment ID',
      delivery_url: `https://adsguru22.github.io/asmak-updates/download`
    })
    
    // Update payment with bcl bill ID
    await supabase
      .from('payments')
      .update({
        transaction_id: bclResponse.id,
        metadata: {
          ...payment.metadata,
          bcl_bill_id: bclResponse.id
        }
      })
      .eq('id', payment.id)
    
    return new Response(
      JSON.stringify({
        success: true,
        paymentUrl: bclResponse.url,
        billId: bclResponse.id,
        paymentId: payment.id
      }),
      { headers: { 'Content-Type': 'application/json' } }
    )
  } catch (error) {
    console.error('BCL payment creation error:', error)
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 400, headers: { 'Content-Type': 'application/json' } }
    )
  }
})

async function createBCLBill(data: any) {
  const apiKey = Deno.env.get('BCL_API_KEY')
  const secretKey = Deno.env.get('BCL_SECRET_KEY')
  
  const payload = {
    collection_id: Deno.env.get('BCL_COLLECTION_ID'),
    email: data.email,
    name: data.name,
    amount: data.amount, // In cents (RM97.00 = 9700)
    callback_url: data.callback_url,
    redirect_url: data.redirect_url,
    description: data.description,
    reference_1_label: data.reference_1_label || 'Reference',
    reference_1: data.reference_1,
    delivery: data.delivery_url ? 1 : 0,
    delivery_url: data.delivery_url
  }
  
  // Create signature
  const signature = createBCLSignature(payload, secretKey)
  
  const response = await fetch('https://www.billplz-sandbox.com/api/v3/bills', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Basic ${btoa(apiKey + ':')}`
    },
    body: JSON.stringify({
      ...payload,
      x_signature: signature
    })
  })
  
  if (!response.ok) {
    const error = await response.json()
    throw new Error(`BCL API Error: ${JSON.stringify(error)}`)
  }
  
  return await response.json()
}

function createBCLSignature(data: any, secretKey: string): string {
  // bcl.my uses X-Signature for verification
  // Create signature string from data
  const signatureString = Object.keys(data)
    .sort()
    .map(key => `${key}${data[key]}`)
    .join('|')
  
  // HMAC SHA256
  const encoder = new TextEncoder()
  const keyData = encoder.encode(secretKey)
  const messageData = encoder.encode(signatureString)
  
  // You'll need to use Web Crypto API or a library for HMAC
  // This is a simplified example
  return signatureString // Replace with actual HMAC SHA256
}
```

### 2. Webhook Handler

**Supabase Edge Function: `bcl-webhook`**

```typescript
// supabase/functions/bcl-webhook/index.ts
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { createHmac } from 'https://deno.land/std@0.168.0/node/crypto.ts'

serve(async (req) => {
  try {
    // Get webhook payload
    const payload = await req.json()
    
    console.log('BCL Webhook received:', payload)
    
    // Verify signature
    const signature = req.headers.get('X-Signature')
    const webhookSecret = Deno.env.get('BCL_WEBHOOK_SECRET')
    
    if (!verifyBCLWebhook(payload, signature, webhookSecret)) {
      console.error('Invalid webhook signature')
      return new Response('Invalid signature', { status: 401 })
    }
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    // Extract data from webhook
    const {
      id: billId,
      paid,
      state,
      reference_1: paymentId,
      paid_amount,
      transaction_id,
      paid_at
    } = payload
    
    // Get payment record
    const { data: payment } = await supabase
      .from('payments')
      .select('*, users(email, name)')
      .eq('id', paymentId)
      .single()
    
    if (!payment) {
      console.error('Payment not found:', paymentId)
      return new Response('Payment not found', { status: 404 })
    }
    
    // Update payment status
    const paymentStatus = paid ? 'completed' : 
                         state === 'due' ? 'pending' : 'failed'
    
    await supabase
      .from('payments')
      .update({
        status: paymentStatus,
        transaction_id,
        metadata: {
          ...payment.metadata,
          paid_at,
          paid_amount,
          bcl_state: state
        },
        updated_at: new Date().toISOString()
      })
      .eq('id', paymentId)
    
    // If payment successful, create license
    if (paid) {
      await processSuccessfulPayment(supabase, payment)
    }
    
    return new Response('OK', { 
      status: 200,
      headers: { 'Content-Type': 'text/plain' }
    })
  } catch (error) {
    console.error('Webhook processing error:', error)
    return new Response(`Error: ${error.message}`, { status: 500 })
  }
})

function verifyBCLWebhook(
  payload: any, 
  signature: string | null, 
  secret: string
): boolean {
  if (!signature) return false
  
  // Create signature from payload
  const signatureString = Object.keys(payload)
    .filter(key => key !== 'x_signature')
    .sort()
    .map(key => `${key}${payload[key]}`)
    .join('|')
  
  // Calculate HMAC
  const hmac = createHmac('sha256', secret)
  hmac.update(signatureString)
  const calculatedSignature = hmac.digest('hex')
  
  return calculatedSignature === signature
}

async function processSuccessfulPayment(supabase: any, payment: any) {
  const { user_id, metadata } = payment
  const plan = metadata.plan
  
  // Generate license key
  const licenseKey = generateLicenseKey(plan.toUpperCase())
  
  // Create license record
  const expiresAt = plan === 'lifetime' ? null : getExpiryDate(1) // 1 year
  
  await supabase
    .from('licenses')
    .insert({
      user_id,
      license_key: licenseKey,
      tier: plan,
      status: 'active',
      expires_at: expiresAt
    })
  
  // Update user tier
  await supabase
    .from('users')
    .update({ tier: plan })
    .eq('id', user_id)
  
  // Send license email
  await sendLicenseEmail({
    email: payment.users.email,
    name: payment.users.name,
    licenseKey,
    plan,
    downloadUrl: `https://adsguru22.github.io/asmak-updates/download?key=${licenseKey}`
  })
  
  console.log(`License created: ${licenseKey} for user ${user_id}`)
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
  
  return key // e.g., PRO-A3B7-9K2M-X4Y8-P6Q3
}

function getExpiryDate(years: number): string {
  const date = new Date()
  date.setFullYear(date.getFullYear() + years)
  return date.toISOString()
}

async function sendLicenseEmail(data: any) {
  // Integrate with email service (see email-integration.md)
  console.log('Send license email:', data)
  
  // Use SendGrid, Mailgun, or your email service
  // Template should include:
  // - License key
  // - Download link
  // - Installation instructions
  // - Support contact
}
```

### 3. Check Payment Status

**Supabase Edge Function: `check-payment`**

```typescript
// supabase/functions/check-payment/index.ts
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const { paymentId } = await req.json()
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    )
    
    // Get payment from database
    const { data: payment } = await supabase
      .from('payments')
      .select('*, licenses(*)')
      .eq('id', paymentId)
      .single()
    
    if (!payment) {
      return new Response(
        JSON.stringify({ success: false, error: 'Payment not found' }),
        { status: 404, headers: { 'Content-Type': 'application/json' } }
      )
    }
    
    // If completed, return license info
    if (payment.status === 'completed' && payment.licenses.length > 0) {
      return new Response(
        JSON.stringify({
          success: true,
          status: 'completed',
          licenseKey: payment.licenses[0].license_key,
          downloadUrl: `https://adsguru22.github.io/asmak-updates/download?key=${payment.licenses[0].license_key}`
        }),
        { headers: { 'Content-Type': 'application/json' } }
      )
    }
    
    // Otherwise return current status
    return new Response(
      JSON.stringify({
        success: true,
        status: payment.status,
        message: getStatusMessage(payment.status)
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

function getStatusMessage(status: string): string {
  const messages = {
    pending: 'Waiting for payment...',
    completed: 'Payment successful!',
    failed: 'Payment failed. Please try again.',
    refunded: 'Payment has been refunded.'
  }
  return messages[status] || 'Unknown status'
}
```

---

## 🎨 Frontend Payment Flow

### HTML for Payment Success Page

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Processing - adsguru Command Center</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
    }
    
    .container {
      background: white;
      padding: 60px 40px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 500px;
      text-align: center;
    }
    
    .spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #667eea;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .success {
      display: none;
    }
    
    .success.show {
      display: block;
    }
    
    .checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: block;
      stroke-width: 2;
      stroke: #10b981;
      stroke-miterlimit: 10;
      margin: 20px auto;
      box-shadow: inset 0px 0px 0px #10b981;
      animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }
    
    .checkmark-circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 2;
      stroke-miterlimit: 10;
      stroke: #10b981;
      fill: none;
      animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark-check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }
    
    .download-btn {
      display: inline-block;
      padding: 15px 40px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      text-decoration: none;
      border-radius: 30px;
      font-weight: 600;
      margin-top: 20px;
      transition: transform 0.3s ease;
    }
    
    .download-btn:hover {
      transform: translateY(-3px);
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Processing State -->
    <div id="processing">
      <div class="spinner"></div>
      <h2>Processing Your Payment...</h2>
      <p>Please wait while we verify your payment.</p>
    </div>
    
    <!-- Success State -->
    <div id="success" class="success">
      <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
      </svg>
      
      <h1>🎉 Payment Successful!</h1>
      <p>Your license key: <strong id="licenseKey"></strong></p>
      <p>Check your email for download instructions.</p>
      
      <a href="#" id="downloadLink" class="download-btn">
        Download Plugin Now
      </a>
    </div>
    
    <!-- Failed State -->
    <div id="failed" class="success">
      <h1>❌ Payment Failed</h1>
      <p>Your payment could not be processed.</p>
      <a href="https://adsguru22.github.io/asmak-updates/" class="download-btn">
        Try Again
      </a>
    </div>
  </div>
  
  <script>
    // Get payment ID from URL
    const params = new URLSearchParams(window.location.search)
    const paymentId = params.get('id')
    
    if (!paymentId) {
      window.location.href = 'https://adsguru22.github.io/asmak-updates/'
    }
    
    // Poll payment status
    let pollCount = 0
    const maxPolls = 30 // 30 seconds max
    
    const pollInterval = setInterval(async () => {
      pollCount++
      
      try {
        const response = await fetch('YOUR_SUPABASE_URL/functions/v1/check-payment', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer YOUR_ANON_KEY'
          },
          body: JSON.stringify({ paymentId })
        })
        
        const data = await response.json()
        
        if (data.status === 'completed') {
          // Show success
          clearInterval(pollInterval)
          document.getElementById('processing').style.display = 'none'
          document.getElementById('success').classList.add('show')
          document.getElementById('licenseKey').textContent = data.licenseKey
          document.getElementById('downloadLink').href = data.downloadUrl
        } else if (data.status === 'failed' || pollCount >= maxPolls) {
          // Show failure
          clearInterval(pollInterval)
          document.getElementById('processing').style.display = 'none'
          document.getElementById('failed').classList.add('show')
        }
      } catch (error) {
        console.error('Poll error:', error)
      }
    }, 1000) // Poll every second
  </script>
</body>
</html>
```

---

## 🔐 Security Best Practices

### 1. Webhook Security
```typescript
// Always verify webhook signature
function verifyBCLWebhook(payload: any, signature: string, secret: string): boolean {
  const calculatedSignature = createHMAC(payload, secret)
  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(calculatedSignature)
  )
}
```

### 2. Environment Variables
```bash
# Never commit these to git!
BCL_API_KEY=your_api_key
BCL_SECRET_KEY=your_secret_key
BCL_WEBHOOK_SECRET=your_webhook_secret
BCL_COLLECTION_ID=your_collection_id
```

### 3. Idempotency
```typescript
// Prevent duplicate processing
const { data: existing } = await supabase
  .from('payments')
  .select('id')
  .eq('transaction_id', billId)
  .single()

if (existing) {
  return // Already processed
}
```

---

## 🧪 Testing

### Sandbox Mode
bcl.my provides sandbox for testing:

```typescript
const BCL_API_URL = Deno.env.get('BCL_ENV') === 'production'
  ? 'https://www.billplz.com/api/v3'
  : 'https://www.billplz-sandbox.com/api/v3'
```

### Test Cards
Use bcl.my sandbox test cards:
- Success: Any valid credit card format
- Failed: Use specific test numbers (check bcl.my docs)

---

## ✅ Implementation Checklist

- [ ] Register bcl.my merchant account
- [ ] Get API credentials
- [ ] Setup environment variables
- [ ] Deploy Supabase Edge Functions
- [ ] Configure webhook URL in bcl.my dashboard
- [ ] Test payment flow in sandbox
- [ ] Test webhook verification
- [ ] Test license generation
- [ ] Test email delivery
- [ ] Switch to production mode
- [ ] Monitor transactions
- [ ] Setup alerts for failures

---

## 📊 Monitoring

```typescript
// Log all payment events
await supabase.from('payment_logs').insert({
  payment_id: paymentId,
  event: 'webhook_received',
  status: 'success',
  metadata: payload,
  created_at: new Date().toISOString()
})
```

---

## 🆘 Troubleshooting

**Webhook not received:**
- Check webhook URL is publicly accessible
- Verify HTTPS is enabled
- Check bcl.my dashboard for webhook logs

**Payment stuck in pending:**
- User might not have completed payment
- Check bcl.my dashboard for transaction status
- Implement payment timeout (e.g., 1 hour)

**License not generated:**
- Check webhook signature verification
- Check Supabase logs
- Verify database permissions

---

**Next:** See `supabase-setup.md` for complete database configuration
