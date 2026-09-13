---
name: Precision Logistics
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#45464d'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#76777d'
  outline-variant: '#c6c6cd'
  surface-tint: '#565e74'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#131b2e'
  on-primary-container: '#7c839b'
  inverse-primary: '#bec6e0'
  secondary: '#515f74'
  on-secondary: '#ffffff'
  secondary-container: '#d5e3fd'
  on-secondary-container: '#57657b'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#271901'
  on-tertiary-container: '#98805d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#d5e3fd'
  secondary-fixed-dim: '#b9c7e0'
  on-secondary-fixed: '#0d1c2f'
  on-secondary-fixed-variant: '#3a485c'
  tertiary-fixed: '#fcdeb5'
  tertiary-fixed-dim: '#dec29a'
  on-tertiary-fixed: '#271901'
  on-tertiary-fixed-variant: '#574425'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  gutter: 20px
  sidebar-width: 260px
---

## Brand & Style
The design system is engineered for high-density information environments where clarity and operational efficiency are paramount. The brand personality is **Reliable, Efficient, and Data-Driven**, focusing on a "Single Source of Truth" aesthetic.

The visual style follows a **Corporate Modern** approach with elements of **Systematic Minimalism**. It prioritizes utilitarian clarity over decorative flair, utilizing generous whitespace to reduce cognitive load during high-intensity warehouse operations. The interface should feel like a precision tool: stable, responsive, and authoritative.

## Colors
The palette is anchored by **Deep Slate (#0F172A)** to convey stability and professional rigor. 

- **Primary**: Used for core navigation, primary actions, and branding.
- **Secondary**: Applied to sub-navigation and secondary interface iconography.
- **Neutrals**: A range of cool-toned slates (from #F8FAFC to #1E293B) manages background layering and subtle borders.
- **Functional (Logistics)**: 
    - **Success (Green)**: Used exclusively for completed shipments, received inventory, and "In-Stock" statuses.
    - **Warning (Yellow)**: Reserved for low-stock thresholds and expiring manifests.
    - **Danger (Red)**: Indicates shipment delays, safety incidents, or critical stock-outs.
    - **Info (Light Blue)**: Dedicated to active processes like picking, packing, or internal transfers.

## Typography
This design system utilizes **Inter** for its exceptional legibility in data-heavy contexts. The hierarchy is designed to guide the eye from high-level KPIs to granular inventory details.

For tabular data and stock numbers, always enable **tabular figures (tnum)** to ensure vertical alignment of digits, which is critical for scanning SKU lists and quantities. Use `label-sm` for table headers and section overviews to differentiate metadata from actionable content.

## Layout & Spacing
The layout follows a **Fixed Sidebar / Fluid Content** model. 

- **Sidebar**: Fixed at 260px. Houses primary navigation. On tablet, this collapses to a 64px icon-only rail.
- **Grid**: A 12-column system is used for dashboard widgets. Gutters are fixed at 20px to maintain a dense but readable "data-grid" feel.
- **Margins**: A standard 32px margin is applied to the main content container to frame the data away from the viewport edges.
- **Vertical Rhythm**: Use 8px increments (2 units) for element grouping and 16px (4 units) for component spacing.

## Elevation & Depth
To maintain a clean, corporate appearance, the design system uses **Tonal Layers** rather than heavy shadows.

- **Level 0 (Background)**: #F8FAFC (Slate 50).
- **Level 1 (Cards/Tables)**: Pure white (#FFFFFF) with a 1px border of #E2E8F0 (Slate 200).
- **Level 2 (Modals/Dropdowns)**: Pure white with a subtle ambient shadow (0px 4px 12px rgba(15, 23, 42, 0.08)) and a #CBD5E1 border.

Interactive elements should not feel "floating." Instead, they should feel like distinct, physical panels docked within the UI. Use a slight background shift (Slate 100) on hover for table rows and list items.

## Shapes
The shape language is **Soft and Precise**. A 4px (0.25rem) radius is used for most UI elements (Inputs, Buttons, Cards) to maintain a modern feel without appearing overly "bubbly" or informal. Status badges and tags utilize a slightly larger radius (rounded-lg) to distinguish them as distinct labels from structural elements.

## Components
- **Sidebar**: Dark-themed (#0F172A). Active states use a left-edge 4px accent line in Primary Blue and a subtle background highlight.
- **Information Cards**: Must include a "Change Indicator" (e.g., small trend line or percentage) in the functional color corresponding to the trend (Success/Danger).
- **Data Tables**: Use a fixed-height header with a Slate 100 background. Status badges in tables should use a "Light Fill" style (e.g., Success text on 10% opacity Success background).
- **Buttons**:
  - *Primary*: Solid #0F172A with white text. 
  - *Secondary*: White fill with #E2E8F0 border and #334155 text.
- **Input Fields**: 1px Slate 300 border. Focused state uses a 2px Primary Blue ring with 20% opacity.
- **Tabs**: Bottom-border style. The active tab has a 2px stroke in Primary Blue and semi-bold text.
- **Status Indicators**: Small 8px circles (dots) used within table rows to show real-time connectivity or sensor status in the warehouse.