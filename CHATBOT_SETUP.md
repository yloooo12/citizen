# 🤖 AI Chatbot Setup Guide

## Powered by GitHub Models API (GPT-4o)

### 📋 Quick Setup (3 Steps)

#### Step 1: Get FREE GitHub Token
1. Go to: https://github.com/settings/tokens
2. Login to GitHub (FREE account)
3. Click "Generate new token" → "Generate new token (classic)"
4. Name: "LSPU-Chatbot"
5. Select scopes: **NONE needed** (leave all unchecked)
6. Click "Generate token"
7. **COPY the token** (starts with `github_pat_` or `ghp_`)

#### Step 2: Add Token to Code
1. Open `chatbot_api.php`
2. Find line 9:
   ```php
   define('GITHUB_TOKEN', 'github_pat_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
   ```
3. Replace with your actual token
4. Save the file

#### Step 3: Test!
1. Open your portal
2. Click the chatbot icon (bottom right)
3. Ask: "How do I register?"
4. AI will respond! 🎉

---

## 🚀 Features

### Hybrid AI System:
- **Primary**: GitHub Models GPT-4o (OpenAI's latest model)
- **Fallback**: Smart pattern matching (works without token)
- **Fast**: Quick responses for common questions
- **Intelligent**: Advanced natural language understanding

### What it can do:
✅ Answer registration questions
✅ Explain document requirements
✅ Help with password issues
✅ Provide grade information
✅ Give contact details
✅ Explain portal features
✅ Natural conversation
✅ Context-aware responses

---

## 🔧 Troubleshooting

### "I'm here to help! For specific questions..."
- **Cause**: GitHub token not set or invalid
- **Solution**: Follow Step 1 & 2 above
- **Note**: Chatbot still works with fallback responses!

### Slow responses
- **Cause**: First API call or network latency
- **Solution**: Wait 5-10 seconds
- **Note**: Subsequent responses are faster

### API Errors
- **Cause**: Rate limit (15 requests/minute)
- **Solution**: Wait a minute and try again
- **Note**: GitHub Models is FREE but has rate limits

---

## 💡 Tips

1. **First time use**: First AI response may take 5-10 seconds
2. **Rate limits**: Free tier allows 15 requests/minute, 150/hour
3. **Fallback**: Even without token, chatbot works with smart responses
4. **Best questions**: Ask clear, specific questions for best AI responses
5. **Model**: GPT-4o is OpenAI's latest and most powerful model!

---

## 📞 Support

For issues:
- Email: support@lspu.edu.ph
- Visit: CCS Office

---

## 🎯 Model Info

**Model**: GPT-4o (OpenAI)
**Type**: Advanced Large Language Model
**Provider**: GitHub Models (Azure AI)
**API**: https://models.inference.ai.azure.com
**License**: Free for development and testing
**Rate Limit**: 15 req/min, 150 req/hour

---

**Enjoy your AI-powered chatbot! 🚀**
