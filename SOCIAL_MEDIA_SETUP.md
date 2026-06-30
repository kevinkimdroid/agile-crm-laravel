# Social Media Integration Setup

Connect Facebook, Instagram, Twitter, YouTube, TikTok, and WhatsApp to your Agile Craft CRM.

## 1. Add Credentials to `.env`

Add your API keys to the `.env` file:

```env
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
INSTAGRAM_CLIENT_ID=your_meta_app_id
INSTAGRAM_CLIENT_SECRET=your_meta_app_secret
TWITTER_CLIENT_ID=your_twitter_client_id
TWITTER_CLIENT_SECRET=your_twitter_client_secret
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
TIKTOK_CLIENT_KEY=your_tiktok_client_key
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret

# WhatsApp Cloud API (Meta Business Suite → WhatsApp → API Setup)
WHATSAPP_ENABLED=true
WHATSAPP_SANDBOX=true
WHATSAPP_DISPLAY_PHONE="+254 700 000 001"
WHATSAPP_BUSINESS_NAME="Kenya Orient Gemini Life WhatsApp"
# Production only (WHATSAPP_SANDBOX=false):
# WHATSAPP_PHONE_NUMBER_ID=
# WHATSAPP_BUSINESS_ACCOUNT_ID=
# WHATSAPP_ACCESS_TOKEN=

# Meta webhook (shared for Facebook Page, Instagram, WhatsApp)
META_WEBHOOK_VERIFY_TOKEN=long-random-string
META_APP_SECRET=your_meta_app_secret

# Scheduled Facebook publishing (cron / Laravel scheduler)
SOCIAL_PUBLISH_SCHEDULED=true
```

## 2. Get API Keys

### Facebook & Instagram (Meta)
1. Go to [developers.facebook.com](https://developers.facebook.com/)
2. Create an app → Add **Facebook Login** and **Instagram Graph API**
3. Add redirect URIs: `{APP_URL}/social-auth/facebook/callback` and `{APP_URL}/social-auth/instagram/callback`
4. Use same App ID/Secret for both (Instagram uses Meta's API)
5. **For posting to Facebook Page:** Connect Facebook with `pages_manage_posts`. The CRM stores your Page access token automatically.
6. **For Meta Ad Campaigns:** Add the **Marketing API** product and request the `ads_read` permission.

### WhatsApp (Meta Cloud API)
1. **Sandbox (local test, no Meta account):** set `WHATSAPP_ENABLED=true` and `WHATSAPP_SANDBOX=true`, run `php artisan config:clear`, open **Support → WhatsApp Chat**. Use **Simulate customer message** to test inbound; reply from the chat composer.
2. **Production:** In Meta Business Suite, add **WhatsApp** and complete phone number verification.
2. In the app → **WhatsApp → API Setup**, copy **Phone number ID**, **WhatsApp Business Account ID**, and a **Permanent access token**.
3. Webhooks: use the same URL as Facebook — `{APP_URL}/webhooks/social/meta` — and subscribe to **messages** (WhatsApp product).
4. **Pricing:** Conversation-based billing via Meta. See the **WhatsApp** tab in Marketing → Social Media for a client consultation summary, or [Meta pricing docs](https://developers.facebook.com/docs/whatsapp/pricing).

### Twitter / X
1. Go to [developer.twitter.com](https://developer.twitter.com/)
2. Create a project and app
3. Enable OAuth 2.0, set callback: `{APP_URL}/social-auth/twitter/callback`
4. Add **Read and write** permissions

### YouTube (Google)
1. Go to [console.cloud.google.com](https://console.cloud.google.com/)
2. Create project → Enable **YouTube Data API v3**
3. Create OAuth 2.0 credentials (Web application)
4. Add redirect: `{APP_URL}/social-auth/youtube/callback`

### TikTok
1. Go to [developers.tiktok.com](https://developers.tiktok.com/)
2. Create an app
3. Add redirect URL: `{APP_URL}/social-auth/tiktok/callback`
4. Request scopes: `user.info.basic`, `video.list`

## 3. Redirect URIs

Replace `{APP_URL}` with your app URL (e.g. `http://localhost:8000` or `https://yourdomain.com`).

Ensure `APP_URL` in `.env` is correct.

## 4. Connect Accounts

1. Go to **Marketing → Social Media**
2. Click **Connect** on each platform (Facebook, Instagram, etc.)
3. Authorize the app when redirected
4. For **WhatsApp**, add env credentials (no OAuth button) and configure the Meta webhook

## 5. WhatsApp Chat (company number)

1. Go to **Marketing → WhatsApp Chat** (`/marketing/whatsapp`)
2. Customer messages to your business number appear in the conversation list
3. Staff reply from the same screen — messages send via your company WhatsApp (Meta Cloud API)
4. Set `WHATSAPP_DISPLAY_PHONE` and optional `WHATSAPP_BUSINESS_NAME` in `.env` so staff see the correct company number in the header

## 6. Publishing

- **Facebook:** Schedule posts in **Scheduling & Publishing**, or use **Publish to Facebook** for immediate posts.
- Due scheduled posts are sent by `php artisan social:publish-scheduled` (runs every minute when Laravel scheduler is active).
- **WhatsApp:** Inbound messages appear in the **WhatsApp** tab; reply within 24 hours of a customer message to use service conversation pricing.
