---
name: FreeDmg Velocity
colors:
  surface: '#131316'
  surface-dim: '#131316'
  surface-bright: '#39393c'
  surface-container-lowest: '#0e0e11'
  surface-container-low: '#1b1b1e'
  surface-container: '#1f1f22'
  surface-container-high: '#2a2a2d'
  surface-container-highest: '#353438'
  on-surface: '#e4e1e6'
  on-surface-variant: '#c1c6d7'
  inverse-surface: '#e4e1e6'
  inverse-on-surface: '#303033'
  outline: '#8b90a0'
  outline-variant: '#414755'
  surface-tint: '#adc6ff'
  primary: '#adc6ff'
  on-primary: '#002e69'
  primary-container: '#4b8eff'
  on-primary-container: '#00285c'
  inverse-primary: '#005bc1'
  secondary: '#dcb8ff'
  on-secondary: '#480081'
  secondary-container: '#7701d0'
  on-secondary-container: '#dcb7ff'
  tertiary: '#ffb595'
  on-tertiary: '#571e00'
  tertiary-container: '#ef6719'
  on-tertiary-container: '#4c1a00'
  error: '#FF453A'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc6ff'
  on-primary-fixed: '#001a41'
  on-primary-fixed-variant: '#004493'
  secondary-fixed: '#efdbff'
  secondary-fixed-dim: '#dcb8ff'
  on-secondary-fixed: '#2c0051'
  on-secondary-fixed-variant: '#6700b5'
  tertiary-fixed: '#ffdbcc'
  tertiary-fixed-dim: '#ffb595'
  on-tertiary-fixed: '#351000'
  on-tertiary-fixed-variant: '#7c2e00'
  background: '#131316'
  on-background: '#e4e1e6'
  surface-variant: '#353438'
  bg-deep: '#050505'
  surface-glass: rgba(255, 255, 255, 0.04)
  border-subtle: rgba(255, 255, 255, 0.1)
  success: '#32D74B'
  warning: '#FFD60A'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  title-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1280px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 40px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

The design system is engineered for a high-performance software distribution platform. It moves away from the cluttered, document-centric layout of traditional download sites toward a **sleek, immersive digital storefront** aesthetic. 

The personality is **Reliable, Fast, and Modern**. It evokes a sense of technical precision and premium quality.

The visual style is a blend of **Minimalism** and **Glassmorphism**:
- **Minimalism:** Clean grids and generous negative space to ensure the software titles and calls-to-action (CTAs) remain the focal point.
- **Glassmorphism:** Use of frosted glass surfaces for navigation bars and secondary cards to create depth and a sense of "lightness" within a dark environment.
- **Precision Accents:** High-vibrancy "Electric Blue" triggers action, contrasted against a deep, multi-layered charcoal environment.

## Colors

The palette is optimized for a dark-mode-first experience. 

- **Primary & Secondary:** "Electric Blue" (#007AFF) is used for the primary action path (Downloads). "Neon Purple" (#8A2BE2) is reserved for secondary features, tags, and category highlights.
- **Backgrounds:** A layered approach using `#050505` for the base and `#0F0F12` for elevated sections. 
- **Functional Colors:** The admin portal utilizes high-contrast semantic colors (Success/Error) to ensure critical system states are unmistakable.
- **Translucency:** Glassmorphism is achieved through `surface-glass` combined with a background blur (16px - 32px), creating a premium, modern feel.

## Typography

This design system utilizes **Inter** exclusively to maintain a clean, systematic, and utilitarian appearance that aligns with developer-focused software distribution.

- **Scale:** Bold headings create a clear hierarchy between the software name and its description.
- **Spacing:** Negative letter-spacing is applied to larger headlines to maintain a compact, "engineered" look.
- **Labels:** Meta-information (date, file size, OS version) uses uppercase labels with increased letter spacing for maximum legibility at small sizes.

## Layout & Spacing

The layout uses a **12-column fluid grid** for the main software gallery, allowing for dynamic card sizes depending on the featured status of the application.

- **Rhythm:** A base-8 spacing scale ensures vertical consistency.
- **Breakpoints:**
  - **Mobile (< 768px):** Single column, 16px margins.
  - **Tablet (768px - 1024px):** 2-column grid, 24px margins.
  - **Desktop (> 1024px):** 3 or 4-column grid, 40px margins, centered container at 1280px.
- **Admin Portal:** Utilizes a sidebar-heavy fixed layout for efficient navigation between software management and analytics.

## Elevation & Depth

Visual hierarchy is achieved through a combination of **Tonal Layering** and **Ambient Shadows**.

1.  **Level 0 (Base):** Deep black (`#050505`).
2.  **Level 1 (Cards/Sidebar):** Surface charcoal (`#0F0F12`) with a subtle 1px border (`border-subtle`).
3.  **Level 2 (Modals/Popovers):** Glassmorphic surfaces with `backdrop-filter: blur(20px)` and a soft, diffused shadow (`0px 20px 40px rgba(0,0,0,0.5)`).

Shadows are not used on standard cards to maintain a "flat but deep" aesthetic; instead, a subtle glow is applied to the primary CTA buttons to make them feel "energized."

## Shapes

The design system adopts a **Rounded** shape language to soften the high-tech aesthetic and make the platform feel approachable.

- **Primary Components:** Cards, input fields, and main containers use a 0.5rem (8px) radius.
- **Action Elements:** Primary "Download" buttons and chips use a `rounded-xl` (1.5rem / 24px) or full pill-shape to distinguish them from structural containers.
- **Icons:** Should follow a "soft-square" aesthetic with consistent corner radii to match the UI.

## Components

### Buttons
- **Primary:** Gradient background (Electric Blue to a slightly darker shade), white text, pill-shaped, with a subtle blue outer glow on hover.
- **Secondary:** Transparent with an Electric Blue border and glass-blur background.

### Cards (Software Entry)
- No heavy shadows. Use a subtle `1px` border that lightens on hover. 
- High-quality software icons are the centerpiece, using a consistent 80x80px size with a 12px radius.

### Input Fields (Search/Admin)
- Deep background (`#0F0F12`), internal 1px border. 
- Focus state: Border color changes to Electric Blue with a soft outer glow.

### Chips/Tags
- Small, uppercase text. 
- Secondary color (#8A2BE2) used for "New" or "Hot" tags. Gray-scale for version numbers.

### Admin Portal Tables
- Clean, borderless rows with alternating glassmorphic backgrounds on hover. 
- Success/Error indicators use small colored pips (dots) next to status text.