# 🎨 IslamResearch UX Showcase

This document provides a visual overview of the platform's current UI/UX state, as captured by our automated browser testing suite.

---

## 🔬 Scholar IDE (Frontend)

The Scholar IDE is built with **Livewire 4** and **Tailwind v4**, featuring a reactive workspace for deep research.

### 🖥️ Desktop View
![Scholar IDE Desktop](../tests/Browser/Screenshots/scholar-desktop-full.png)

---

### 📱 Mobile View
Captured at 375x812 resolution to verify responsive adaptation.

![Scholar IDE Mobile](../tests/Browser/Screenshots/scholar-mobile-view.png)

---

## ⚙️ Admin Panel (Filament)

The backend management layer powered by **Filament V5**.

### 📊 Dashboard
![Filament Admin Dashboard](../tests/Browser/Screenshots/admin-dashboard.png)

---

> [!TIP]
> These screenshots are automatically updated whenever `vendor/bin/sail artisan test tests/Browser/SmokeTest.php` is executed.
