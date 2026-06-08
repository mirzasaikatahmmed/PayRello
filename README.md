# 🐜 Payrello — Open Source Self-Hosted Payment Automation Platform

<p align="center">
    <picture>
        <source media="(prefers-color-scheme: light)" srcset="https://payrello.com/assets/images/logo-light.png">
        <source media="(prefers-color-scheme: dark)" srcset="https://payrello.com/assets/images/logo-dark.png">
        <img src="https://payrello.com/assets/images/logo-light.png" alt="Payrello" width="200">
    </picture>
</p>

<p align="center">
  <a href="https://github.com/mirzasaikatahmmed/PayRello/releases">
    <img src="https://img.shields.io/github/v/release/mirzasaikatahmmed/PayRello?include_prereleases&style=for-the-badge" alt="Latest Release">
  </a>

  <a href="https://www.facebook.com/groups/payrello">
    <img src="https://img.shields.io/badge/Facebook%20Group-Join%20Community-1877F2?logo=facebook&logoColor=white&style=for-the-badge" alt="Facebook Group">
  </a>

  <a href="https://github.com/mirzasaikatahmmed/PayRello/tree/main">
    <img src="https://img.shields.io/badge/PHP-8.2-777BB4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2">
  </a>

  <a href="LICENSE">
    <img src="https://img.shields.io/badge/License-AGPL--3.0-blue.svg?style=for-the-badge&logo=gnu&logoColor=white" alt="AGPL-3.0 License">
  </a>
</p>

Payrello is the **first open-source payment automation system (AGPL-3.0)** — a self-hosted, plugin-based platform that unifies payment gateways, wallets, APIs, and SMS-based verification into one system.

It helps developers and businesses **accept, verify, and automate payments from any method — API or non-API — in a single workflow.**

Supported payment networks include: Mobile Financial Services (MFS), payment gateways, and major banking systems across Bangladesh, India, and Pakistan — with full extensibility to integrate any custom or third-party provider.

Supported ecosystems include: bKash, Nagad, Rocket, Upay, and SureCash (Bangladesh); UPI, Paytm, PhonePe, Razorpay, and major Indian banks (India); Easypaisa, JazzCash, and leading Pakistani banking networks (Pakistan). Additional global gateways like Stripe, PayPal, and others can be added via plugins or custom integrations.

The system is fully expandable — developers can build and register new payment channels, gateways, and banking connectors without modifying core logic.

[Website](https://payrello.com) · [Documentation](https://help.payrello.com/) · [Changelog](https://payrello.com/changelog) · [API Reference](https://payrello.readme.io/reference/overview) · [Community Group](https://www.facebook.com/groups/payrello)

New install? Start here: [Getting started](https://help.payrello.com/hc/categories/31/installation-guide)

## 💖 Sponsors

<table>
  <tr>
    <td align="center" width="16.66%">
      <a href="https://www.flexohost.com/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://www.flexohost.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2FFlexoHostHorizontalWhite.b835e24f.png&w=256&q=75">
          <img src="https://www.flexohost.com/_next/image?url=%2F_next%2Fstatic%2Fmedia%2FFlexoHostHorizontalWhite.b835e24f.png&w=256&q=75" alt="" height="28">
        </picture>
      </a>
    </td>
    <td align="center" width="16.66%">
      <a href="https://hostingoxygen.com/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://panel.hostingoxygen.com/assets/img/logo.png">
          <img src="https://panel.hostingoxygen.com/assets/img/logo.png" alt="" height="35">
        </picture>
      </a>
    </td>
    <td align="center" width="16.66%">
      <a href="https://banglahoster.net/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://banglahoster.net/billing/templates/lagom2/assets/img/logo/logo_big.2060021846.svg">
          <img src="https://banglahoster.net/billing/templates/lagom2/assets/img/logo/logo_big.2060021846.svg" alt="" height="28">
        </picture>
      </a>
    </td>
    <td align="center" width="16.66%">
      <a href="https://zenorbd.com/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://zenorbd.com/storage/2024/12/ZENOR-BD-Logo.png">
          <img src="https://zenorbd.com/storage/2024/12/ZENOR-BD-Logo.png" alt="" height="28">
        </picture>
      </a>
    </td>
    <td align="center" width="16.66%">
      <a href="https://hostsite24.com/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://hostsite24.com/wp-content/uploads/2025/07/WEB-header-logo.png">
          <img src="https://hostsite24.com/wp-content/uploads/2025/07/WEB-header-logo.png" alt="" height="24">
        </picture>
      </a>
    </td>
    <td align="center" width="16.66%">
      <a href="https://dignityhost.com/">
        <picture>
          <source media="(prefers-color-scheme: light)" srcset="https://dignityhost.com/assets/images/logo/logo-dignity-black.svg">
          <img src="https://dignityhost.com/assets/images/logo/logo-dignity-black.svg" alt="" height="24">
        </picture>
      </a>
    </td>
  </tr>
</table>

## 🔔 Why Payrello Exists

Many regions lack modern payment infrastructure:

- No Stripe/PayPal availability  
- Local wallets without APIs  
- Manual SMS-based verification  
- Slow reconciliation processes  

Payrello solves this by:

- Automating payment verification  
- Turning SMS payments into programmable events  
- Unifying all gateways under one system  
- Removing manual transaction handling  

## ⚡ Features

- Plugin-based architecture  
- Multi-gateway support (Stripe, PayPal, bKash, Nagad, etc.)  
- SMS verification engine  
- Webhook automation  
- Custom gateway plugins  
- REST API + SDK support  
- Fully self-hosted  

## 📱 Mobile App (Android – Payrello Companion)

The official Payrello Android app is available on the Play Store:

[Download on Play Store](https://play.google.com/store/apps/details?id=com.qubeplug.billpax_tools)

This app acts as a secure companion tool for Payrello payment verification and automation.

## 🐳 Docker Quick Start

```bash
git clone https://github.com/mirzasaikatahmmed/PayRello.git
cd PayRello
cp .env.example .env
docker compose up -d
```

Open **http://localhost:5060/login** — default credentials: `admin` / `Admin@1234`

## 📖 Documentation

👉 Docs: https://help.payrello.com/  
👉 API Reference: https://payrello.readme.io  

- Install Payrello  
- Build plugins & modules  
- Integrate APIs  
- Configure gateways  
- Use webhooks & automation  

## 🤝 Contributing

Payrello is a community-first and developer-driven ecosystem.

Even if the project is still evolving, contributions are welcome across multiple areas:

- 🎨 **UI/UX Design**  
  We welcome professional designers to help shape a strong, modern fintech identity including logo design, brand system, and interface improvements.

- ⚙️ **Development & Extensions**  
  Developers can contribute ideas, plugins, integrations, and improvements to the Payrello ecosystem.

- 🤝 **Sponsorship & Infrastructure**  
  We are open to collaborations and sponsorships in areas such as CDN, cloud infrastructure, security tools, and scaling support.

## 🌟 Star History

<a href="https://www.star-history.com/?repos=mirzasaikatahmmed%2FPayRello&type=date&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/chart?repos=mirzasaikatahmmed/PayRello&type=date&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/chart?repos=mirzasaikatahmmed/PayRello&type=date&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/chart?repos=mirzasaikatahmmed/PayRello&type=date&legend=top-left" />
 </picture>
</a>

## 🛡️ License

AGPL-3.0 — You can use, modify, and self-host Payrello.  
If you distribute modified versions, you must keep them open-source under the same license.

## ❤️ Built by the Community, for the Community
