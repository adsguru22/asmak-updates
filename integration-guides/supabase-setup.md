# 🗄️ Supabase Setup Guide

Complete guide untuk setup Supabase sebagai backend untuk adsguru Command Center.

---

## 🎯 Why Supabase?

✅ **Free tier** - 500MB database, 2GB bandwidth
✅ **PostgreSQL** - Powerful relational database  
✅ **Realtime** - WebSocket subscriptions
✅ **Auth** - Built-in authentication
✅ **Edge Functions** - Serverless functions (Deno)
✅ **Row Level Security** - Secure by default
✅ **Auto API** - REST & GraphQL APIs
✅ **Storage** - File uploads

---

## 🚀 Initial Setup

### 1. Create Supabase Project

1. Go to https://supabase.com
2. Sign in with GitHub
3. Click "New Project"
4. Fill in details:
   - Name: `adsguru-command-center`
   - Database Password: (generate strong password)
   - Region: Singapore (closest to Malaysia)
   - Pricing: Free tier
5. Wait for project to be provisioned (~2 minutes)

### 2. Get Project Credentials

From Settings > API:
- **Project URL**: `https://xxx.supabase.co`
- **Anon (public) key**: For client-side
- **Service role (secret) key**: For server-side

Save these in `.env`:
```bash
SUPABASE_URL=https://xxx.supabase.co
SUPABASE_ANON_KEY=eyJxxx...
SUPABASE_SERVICE_ROLE_KEY=eyJxxx...
```

---

## 📊 Database Schema

### Create Tables

Go to SQL Editor and run:

```sql
-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Users table
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  email TEXT UNIQUE NOT NULL,
  name TEXT,
  website TEXT,
  tier TEXT DEFAULT 'free' CHECK (tier IN ('free', 'pro', 'agency', 'lifetime')),
  status TEXT DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'deleted')),
  metadata JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Licenses table
CREATE TABLE licenses (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  license_key TEXT UNIQUE NOT NULL,
  tier TEXT NOT NULL CHECK (tier IN ('free', 'pro', 'agency', 'lifetime')),
  status TEXT DEFAULT 'active' CHECK (status IN ('active', 'expired', 'cancelled', 'suspended')),
  max_activations INTEGER DEFAULT 1,
  current_activations INTEGER DEFAULT 0,
  expires_at TIMESTAMP WITH TIME ZONE,
  metadata JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Activations table (track where license is used)
CREATE TABLE activations (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  license_id UUID NOT NULL REFERENCES licenses(id) ON DELETE CASCADE,
  site_url TEXT NOT NULL,
  site_name TEXT,
  ip_address TEXT,
  activated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  last_checked_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  status TEXT DEFAULT 'active' CHECK (status IN ('active', 'deactivated'))
);

-- Payments table
CREATE TABLE payments (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  license_id UUID REFERENCES licenses(id) ON DELETE SET NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency TEXT DEFAULT 'MYR',
  payment_gateway TEXT DEFAULT 'bcl',
  transaction_id TEXT,
  status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
  metadata JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Downloads table (track all downloads)
CREATE TABLE downloads (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  license_id UUID REFERENCES licenses(id) ON DELETE SET NULL,
  version TEXT NOT NULL,
  ip_address TEXT,
  user_agent TEXT,
  downloaded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Email sequences table (track email campaigns)
CREATE TABLE email_sequences (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  sequence_name TEXT NOT NULL, -- 'free_welcome', 'upgrade_nurture', etc.
  email_number INTEGER NOT NULL,
  status TEXT DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'sent', 'failed', 'skipped')),
  scheduled_at TIMESTAMP WITH TIME ZONE,
  sent_at TIMESTAMP WITH TIME ZONE,
  metadata JSONB DEFAULT '{}',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Analytics events table
CREATE TABLE analytics_events (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  event_name TEXT NOT NULL,
  event_data JSONB DEFAULT '{}',
  ip_address TEXT,
  user_agent TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tier ON users(tier);
CREATE INDEX idx_users_status ON users(status);

CREATE INDEX idx_licenses_key ON licenses(license_key);
CREATE INDEX idx_licenses_user ON licenses(user_id);
CREATE INDEX idx_licenses_status ON licenses(status);
CREATE INDEX idx_licenses_tier ON licenses(tier);

CREATE INDEX idx_activations_license ON activations(license_id);
CREATE INDEX idx_activations_status ON activations(status);

CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);

CREATE INDEX idx_downloads_user ON downloads(user_id);
CREATE INDEX idx_downloads_license ON downloads(license_id);
CREATE INDEX idx_downloads_date ON downloads(downloaded_at);

CREATE INDEX idx_email_sequences_user ON email_sequences(user_id);
CREATE INDEX idx_email_sequences_status ON email_sequences(status);

CREATE INDEX idx_analytics_events_name ON analytics_events(event_name);
CREATE INDEX idx_analytics_events_date ON analytics_events(created_at);

-- Create updated_at trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Add triggers to tables
CREATE TRIGGER update_users_updated_at
  BEFORE UPDATE ON users
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_licenses_updated_at
  BEFORE UPDATE ON licenses
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_payments_updated_at
  BEFORE UPDATE ON payments
  FOR EACH ROW
  EXECUTE FUNCTION update_updated_at_column();
```

---

## 🔐 Row Level Security (RLS)

Enable RLS on all tables:

```sql
-- Enable RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE licenses ENABLE ROW LEVEL SECURITY;
ALTER TABLE activations ENABLE ROW LEVEL SECURITY;
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;
ALTER TABLE downloads ENABLE ROW LEVEL SECURITY;
ALTER TABLE email_sequences ENABLE ROW LEVEL SECURITY;
ALTER TABLE analytics_events ENABLE ROW LEVEL SECURITY;

-- Policies for users table
CREATE POLICY "Users can view own data"
  ON users FOR SELECT
  USING (auth.uid() = id);

CREATE POLICY "Service role can do anything on users"
  ON users FOR ALL
  USING (auth.role() = 'service_role');

-- Policies for licenses table
CREATE POLICY "Users can view own licenses"
  ON licenses FOR SELECT
  USING (user_id IN (SELECT id FROM users WHERE auth.uid() = id));

CREATE POLICY "Service role can do anything on licenses"
  ON licenses FOR ALL
  USING (auth.role() = 'service_role');

-- Policies for activations
CREATE POLICY "Users can view own activations"
  ON activations FOR SELECT
  USING (license_id IN (
    SELECT id FROM licenses WHERE user_id IN (
      SELECT id FROM users WHERE auth.uid() = id
    )
  ));

CREATE POLICY "Service role can do anything on activations"
  ON activations FOR ALL
  USING (auth.role() = 'service_role');

-- Policies for payments
CREATE POLICY "Users can view own payments"
  ON payments FOR SELECT
  USING (user_id IN (SELECT id FROM users WHERE auth.uid() = id));

CREATE POLICY "Service role can do anything on payments"
  ON payments FOR ALL
  USING (auth.role() = 'service_role');

-- Public read for analytics (anonymous tracking)
CREATE POLICY "Anyone can insert analytics events"
  ON analytics_events FOR INSERT
  WITH CHECK (true);

CREATE POLICY "Service role can view analytics"
  ON analytics_events FOR SELECT
  USING (auth.role() = 'service_role');
```

---

## ⚡ Edge Functions

### Setup Supabase CLI

```bash
# Install Supabase CLI
npm install -g supabase

# Login
supabase login

# Link to your project
supabase link --project-ref YOUR_PROJECT_REF

# Initialize functions
supabase functions new signup-free
supabase functions new create-payment
supabase functions new bcl-webhook
supabase functions new check-payment
supabase functions new verify-license
supabase functions new download-plugin
```

### Deploy Functions

```bash
# Deploy all functions
supabase functions deploy signup-free
supabase functions deploy create-payment
supabase functions deploy bcl-webhook
supabase functions deploy check-payment
supabase functions deploy verify-license
supabase functions deploy download-plugin

# Set environment variables
supabase secrets set BCL_API_KEY=your_key
supabase secrets set BCL_SECRET_KEY=your_secret
supabase secrets set BCL_WEBHOOK_SECRET=your_webhook_secret
supabase secrets set SENDGRID_API_KEY=your_sendgrid_key
```

---

## 📧 Email Integration

### Setup SendGrid

1. Create SendGrid account at https://sendgrid.com
2. Get API Key
3. Create email templates
4. Add to Supabase secrets

### Email Function

**File: `supabase/functions/_shared/email.ts`**

```typescript
export async function sendEmail(data: {
  to: string
  subject: string
  html: string
  from?: string
}) {
  const apiKey = Deno.env.get('SENDGRID_API_KEY')
  
  const response = await fetch('https://api.sendgrid.com/v3/mail/send', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${apiKey}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      personalizations: [{
        to: [{ email: data.to }]
      }],
      from: {
        email: data.from || 'noreply@adsguru.com',
        name: 'adsguru Team'
      },
      subject: data.subject,
      content: [{
        type: 'text/html',
        value: data.html
      }]
    })
  })
  
  if (!response.ok) {
    throw new Error(`SendGrid error: ${await response.text()}`)
  }
  
  return true
}
```

---

## 🎨 Dashboard Views

Create helpful views for admin dashboard:

```sql
-- Sales dashboard
CREATE VIEW sales_dashboard AS
SELECT 
  DATE(created_at) as date,
  tier,
  COUNT(*) as sales_count,
  SUM(amount) as revenue
FROM payments
WHERE status = 'completed'
GROUP BY DATE(created_at), tier
ORDER BY date DESC;

-- User statistics
CREATE VIEW user_stats AS
SELECT
  tier,
  COUNT(*) as user_count,
  COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count
FROM users
GROUP BY tier;

-- License usage
CREATE VIEW license_usage AS
SELECT
  l.tier,
  COUNT(*) as total_licenses,
  COUNT(CASE WHEN l.status = 'active' THEN 1 END) as active_licenses,
  SUM(l.current_activations) as total_activations,
  AVG(l.current_activations::float / NULLIF(l.max_activations, 0)) * 100 as avg_usage_percent
FROM licenses l
GROUP BY l.tier;

-- Recent activity
CREATE VIEW recent_activity AS
SELECT
  'signup' as activity_type,
  email,
  tier,
  created_at
FROM users
UNION ALL
SELECT
  'payment' as activity_type,
  u.email,
  p.metadata->>'plan' as tier,
  p.created_at
FROM payments p
JOIN users u ON u.id = p.user_id
WHERE p.status = 'completed'
ORDER BY created_at DESC
LIMIT 50;
```

---

## 📊 Realtime Subscriptions

Enable realtime for live updates:

```typescript
// Client-side: Subscribe to payment updates
const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY)

const subscription = supabase
  .channel('payments')
  .on(
    'postgres_changes',
    {
      event: 'UPDATE',
      schema: 'public',
      table: 'payments',
      filter: `id=eq.${paymentId}`
    },
    (payload) => {
      console.log('Payment updated:', payload.new)
      if (payload.new.status === 'completed') {
        showSuccess(payload.new)
      }
    }
  )
  .subscribe()
```

---

## 🔍 Useful SQL Queries

### Check system health
```sql
SELECT
  (SELECT COUNT(*) FROM users) as total_users,
  (SELECT COUNT(*) FROM users WHERE tier != 'free') as paying_users,
  (SELECT COUNT(*) FROM licenses WHERE status = 'active') as active_licenses,
  (SELECT SUM(amount) FROM payments WHERE status = 'completed') as total_revenue;
```

### Find inactive licenses
```sql
SELECT l.*, u.email
FROM licenses l
JOIN users u ON u.id = l.user_id
WHERE l.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM activations a
    WHERE a.license_id = l.id
      AND a.last_checked_at > NOW() - INTERVAL '30 days'
  );
```

### Top customers
```sql
SELECT
  u.email,
  u.tier,
  COUNT(p.id) as payment_count,
  SUM(p.amount) as total_spent
FROM users u
LEFT JOIN payments p ON p.user_id = u.id AND p.status = 'completed'
GROUP BY u.id, u.email, u.tier
ORDER BY total_spent DESC
LIMIT 10;
```

---

## 🔧 Maintenance

### Backup Database

```bash
# Using Supabase CLI
supabase db dump -f backup.sql

# Restore
supabase db reset --db-url "postgresql://..."
```

### Monitor Performance

```sql
-- Slow queries
SELECT
  query,
  calls,
  total_time,
  mean_time,
  max_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;
```

---

## ✅ Implementation Checklist

- [ ] Create Supabase project
- [ ] Run database schema SQL
- [ ] Enable RLS policies
- [ ] Setup Edge Functions
- [ ] Configure SendGrid
- [ ] Deploy all functions
- [ ] Set environment variables
- [ ] Test free signup flow
- [ ] Test payment flow
- [ ] Test license verification
- [ ] Setup database backups
- [ ] Create admin dashboard
- [ ] Monitor error logs

---

## 🔗 Supabase Resources

- Dashboard: https://app.supabase.com
- Docs: https://supabase.com/docs
- CLI: https://supabase.com/docs/guides/cli
- Edge Functions: https://supabase.com/docs/guides/functions

---

**Next:** See `gated-content-system.md` for frontend implementation
