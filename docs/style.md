# style.md — Path of the Sage Mode
> Roleplay Forum · Naruto Universe · Ukiyo-e + Washi Paper Aesthetic

---

## Visual Concept

**Style name:** *Washi & Plum* — Handmade Japanese paper as the background, deep plum ink as the primary color, rust red as the single accent. Inspired by ukiyo-e woodblock prints and traditional Japanese calligraphy.

**Keywords:** washi · plum · rust · hanko · kanji · seal · fiber · cold parchment · ukiyo-e

**Design principles:**
- Maximum 2 primary fonts; a 3rd font is allowed only for decorative Japanese glyphs
- Rust red appears in exactly 3 places: title accent line, hanko seal, and Naruto-style spiral marks
- 0.5px borders — never thicker except intentional 1.5px accents
- White space as an active element, not emptiness
- Decorative kanji always at opacity 0.04–0.08 — never prominent

---

## 1. Typography

### Fonts
```
Display / Headings:  Cinzel (Google Fonts) → weights: 400, 700, 900
Body / Lore:         Source Serif 4 (Google Fonts) → weights: 300, 400, 600, 700
Decorative kanji:    Zen Old Mincho (Google Fonts) → weights: 400, 700
```

Import with:
```html
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Source+Serif+4:wght@300;400;600;700&family=Zen+Old+Mincho:wght@400;700&display=swap" rel="stylesheet">
```

### Hierarchy

| Element | Font | Size | Weight | Letter-spacing |
|---|---|---|---|---|
| Forum logo | Cinzel | 26px | 900 | 2px |
| Logo subtitle | Cinzel | 10px | 400 | 5px |
| Section titles | Cinzel | 17px | 900 | 2px |
| Navigation items | Cinzel | 12px | 700 | 1.5px |
| Tags / badges | Cinzel | 10px | 400 | 2px |
| Card labels | Cinzel | 13px | 700 | 1px |
| Card sublabels | Cinzel | 11px | 400 | 2px |
| Metadata / dates | Cinzel | 10px | 400 | 1px |
| News body text | Source Serif 4 | 14px | 400 | 0 |
| Decorative vertical kanji | Zen Old Mincho | 26px | 700 | 6px |
| Watermark kanji in cards | Zen Old Mincho | 62px | 700 | 0 |
| Watermark kanji in nav | Zen Old Mincho | 124px | 700 | 0 |

### Typography rules
- `text-align: justify` on all body paragraphs
- `line-height: 1.85–1.95` on news body text
- `text-transform: uppercase` on all Cinzel navigation text and tags
- Vertical header kanji uses `writing-mode: vertical-rl`

---

## 2. Color Palette

### Light Mode — Cold Washi

| Variable | Hex | Usage |
|---|---|---|
| `--bg` | `#f4f2f8` | Global page background |
| `--bg2` | `#ede9f4` | Header background, news cards |
| `--bg3` | `#e6e1f0` | Navigation panel background, recents section |
| `--border` | `rgba(90,46,154,0.14)` | All borders and dividers |
| `--ink` | `#1e1428` | Primary text, headings |
| `--plum` | `#3d1f6e` | Primary color — nav items, stats band |
| `--plum2` | `#5a2e9a` | Social icons, tags |
| `--plum3` | `#7b4ab8` | Border-top of recent cards |
| `--plum4` | `#9c6bcc` | Edit icons inside cards |
| `--lilac` | `#c4a0e0` | Occasional decorative use |
| `--lavender` | `#ede0fa` | Text on dark card backgrounds |
| `--oxide` | `#7a2a10` | Rust accent — title line, seal, spiral marks |
| `--oxide2` | `#a83a18` | Username in recent posts |
| `--stone` | `#6a587a` | Secondary text, descriptions |
| `--mist` | `#a898b8` | Metadata, dates, labels |
| `--fiber` | `rgba(140,120,180,0.06)` | Washi paper texture overlay |

### Dark Mode — Nocturnal Plum

| Variable | Hex | Usage |
|---|---|---|
| `--bg` | `#0f0c18` | Global page background |
| `--bg2` | `#130f1e` | Header background, news cards |
| `--bg3` | `#171224` | Navigation panel background |
| `--bg4` | `#1c162c` | Stats band, recent cards hover |
| `--border` | `rgba(156,107,204,0.12)` | All borders and dividers |
| `--ink` | `#e8dff8` | Primary text, headings |
| `--plum` | `#7b4ab8` | Primary color — active nav items |
| `--plum2` | `#9c6bcc` | Social icons, tags |
| `--plum3` | `#b890e0` | Text on dark backgrounds |
| `--plum4` | `#d4b8f0` | Titles inside village cards |
| `--dim` | `#4a3a6a` | Tool icons at rest |
| `--oxide` | `#c0582a` | Rust accent — title line, seal, spiral marks |
| `--oxide2` | `#e07040` | Username in recent posts |
| `--stone` | `#8a7a9a` | Secondary text, descriptions |
| `--mist` | `#5a4a6a` | Metadata, dates, stats band text |
| `--gold` | `#c09840` | Urgent notices in the stats band |
| `--fiber` | `rgba(156,107,204,0.04)` | Washi texture (very subtle in dark mode) |

---

## 3. Full CSS Variables

### Light Mode
```css
:root {
  --bg:       #f4f2f8;
  --bg2:      #ede9f4;
  --bg3:      #e6e1f0;
  --border:   rgba(90, 46, 154, 0.14);
  --ink:      #1e1428;
  --plum:     #3d1f6e;
  --plum2:    #5a2e9a;
  --plum3:    #7b4ab8;
  --plum4:    #9c6bcc;
  --lilac:    #c4a0e0;
  --lavender: #ede0fa;
  --oxide:    #7a2a10;
  --oxide2:   #a83a18;
  --stone:    #6a587a;
  --mist:     #a898b8;
  --fiber:    rgba(140, 120, 180, 0.06);
}
```

### Dark Mode
```css
[data-theme="dark"] {
  --bg:     #0f0c18;
  --bg2:    #130f1e;
  --bg3:    #171224;
  --bg4:    #1c162c;
  --border: rgba(156, 107, 204, 0.12);
  --ink:    #e8dff8;
  --plum:   #7b4ab8;
  --plum2:  #9c6bcc;
  --plum3:  #b890e0;
  --plum4:  #d4b8f0;
  --dim:    #4a3a6a;
  --oxide:  #c0582a;
  --oxide2: #e07040;
  --stone:  #8a7a9a;
  --mist:   #5a4a6a;
  --gold:   #c09840;
  --fiber:  rgba(156, 107, 204, 0.04);
}
```

To activate dark mode, add `data-theme="dark"` to the `<html>` or `<body>` element.

---

## 4. Washi Paper Texture

The washi paper texture is simulated with overlapping CSS lines at 3 angles:

```css
/* Applied via ::before on the root container */
.root::before {
  content: '';
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  background-image:
    repeating-linear-gradient(13deg,  transparent 0, transparent 24px, var(--fiber) 24px, var(--fiber) 25px),
    repeating-linear-gradient(82deg,  transparent 0, transparent 36px, var(--fiber) 36px, var(--fiber) 37px),
    repeating-linear-gradient(151deg, transparent 0, transparent 19px, var(--fiber) 19px, var(--fiber) 19.5px);
}

/* Subtler version for village image cards */
.cell-fiber {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image:
    repeating-linear-gradient(18deg,  transparent 0, transparent 12px, rgba(255,255,255,0.06) 12px, rgba(255,255,255,0.06) 13px),
    repeating-linear-gradient(104deg, transparent 0, transparent 20px, rgba(255,255,255,0.04) 20px, rgba(255,255,255,0.04) 21px);
}

/* Version for recent post cards */
.recent-card::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image:
    repeating-linear-gradient(20deg, transparent 0, transparent 18px, var(--fiber) 18px, var(--fiber) 19px);
}
```

---

## 5. Layout & Structure

### Main grid
```
Header (2-col grid: 1fr + 280px fixed)
  ├── Left:  logo + description + social buttons
  └── Right: navigation panel (6 items, 6-row grid)

Stats band (flex, 28px height)

Body (2-col grid: 1fr + 1fr)
  ├── Left column:  News
  └── Right column: Libraries (2×2 grid)

Recent posts (3-col grid, full body span)
```

### Standard spacing
```css
--padding-col:    20px 24px;  /* body column padding */
--padding-header: 24px 28px;  /* header left side padding */
--gap-cells:      3px;        /* gap between village cards and recents */
--height-cell:    104px;      /* village card height */
--height-cell-1:  112px;      /* first card height (col-span 2) */
--height-band:    28px;       /* stats band height */
--height-header:  215px;      /* minimum header height */
```

---

## 6. Components

### Header
```css
.header {
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
  display: grid;
  grid-template-columns: 1fr 280px;
  min-height: 215px;
  position: relative;
  overflow: hidden;
}

/* Subtle color wash in corners */
.header::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(ellipse at 100% 0%,   rgba(90,46,154,0.06)  0%, transparent 55%),
    radial-gradient(ellipse at 0%   100%,  rgba(122,42,16,0.04)  0%, transparent 40%);
  /* dark mode:
    radial-gradient(ellipse at 100% 0%,   rgba(123,74,184,0.08) 0%, transparent 55%),
    radial-gradient(ellipse at 0%   100%,  rgba(192,88,42,0.05)  0%, transparent 40%); */
}
```

### Title block (3 elements in a row)
```css
/* 1. Vertical kanji */
.kanji-col {
  border-right: 0.5px solid var(--border);
  padding-right: 14px;
  padding-top: 2px;
}
.kanji-col span {
  font-family: 'Zen Old Mincho', serif;
  font-size: 22px;
  font-weight: 700;
  color: var(--plum2);
  writing-mode: vertical-rl;
  letter-spacing: 6px;
}

/* 2. Rust red accent line above the title */
.title-accent {
  display: inline-block;
  width: 32px;
  height: 2px;
  background: var(--oxide);
  margin-bottom: 6px;
}

/* 3. Hanko seal */
.hanko {
  width: 50px;
  height: 50px;
  border: 1.5px solid var(--oxide);
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(122,42,16,0.05); /* dark: rgba(192,88,42,0.08) */
}
.hanko span  { font-family: 'Zen Old Mincho', serif; font-size: 14px; font-weight: 700; color: var(--oxide); }
.hanko small { font-size: 8px; color: var(--oxide); letter-spacing: 1px; }
```

### Navigation panel
```css
.nav-panel {
  border-left: 0.5px solid var(--border);
  background: var(--bg3);
  display: grid;
  grid-template-rows: repeat(6, 1fr);
  position: relative;
  overflow: hidden;
}

/* Kanji watermark */
.nav-panel::before {
  content: '忍';
  position: absolute;
  right: -12px;
  top: 50%;
  transform: translateY(-50%);
  font-family: 'Zen Old Mincho', serif;
  font-size: 110px;
  font-weight: 700;
  color: rgba(90,46,154,0.04); /* dark: rgba(123,74,184,0.05) */
  pointer-events: none;
  line-height: 1;
}

.nav-item {
  border-bottom: 0.5px solid var(--border);
  display: flex;
  align-items: center;
  padding: 0 16px;
  gap: 10px;
  cursor: pointer;
  transition: background 0.15s;
}
.nav-item:last-child { border-bottom: none; }
.nav-item:hover      { background: rgba(90,46,154,0.05); } /* dark: rgba(123,74,184,0.08) */

.nav-item span {
  font-family: 'Cinzel', serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: var(--plum2);
  text-transform: uppercase;
}
.nav-item:hover span { color: var(--ink); }

/* Navigation spiral — rust red accent */
.nav-spiral {
  width: 9px;
  height: 9px;
  flex-shrink: 0;
  color: var(--oxide);
}
```

### Stats band
```css
/* LIGHT: solid plum background */
.stats-band {
  background: var(--plum);
  height: 28px;
  display: flex;
  align-items: center;
  padding: 0 24px;
  gap: 20px;
  position: relative;
  overflow: hidden;
}

/* DARK: bg4 with top/bottom borders */
.stats-band {
  background: var(--bg4);
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}

/* Decorative subtle lines (both modes) */
.stats-band::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(
    90deg, transparent 0, transparent 24px,
    rgba(255,255,255,0.03) 24px, rgba(255,255,255,0.03) 25px
  );
}

.stat-label {
  font-family: 'Cinzel', serif;
  font-size: 9px;
  letter-spacing: 2px;
  color: rgba(255,255,255,0.4); /* dark: var(--mist) */
  text-transform: uppercase;
}
.stat-label strong { color: rgba(255,255,255,0.88); } /* dark: var(--plum3) */
```

### Section heading
```css
.section-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.section-head::before {
  content: '';
  width: 3px;
  height: 16px;
  background: var(--oxide); /* rust red — consistent in both modes */
  flex-shrink: 0;
}
.section-head::after {
  content: '';
  flex: 1;
  height: 0.5px;
  background: var(--border);
}
.section-title {
  font-family: 'Cinzel', serif;
  font-size: 14px;
  font-weight: 900;
  color: var(--ink);
  letter-spacing: 2px;
}
```

### Village cards (image grid)
```css
.img-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3px;
  margin-top: 12px;
}

.cell {
  position: relative;
  overflow: hidden;
  cursor: pointer;
  height: 104px;
  border: 0.5px solid var(--border);
}
.cell:first-child    { grid-column: span 2; height: 112px; }
.cell:hover .cell-overlay { opacity: 1; }

.cell-overlay {
  position: absolute;
  inset: 0;
  background: rgba(90,46,154,0.12); /* dark: rgba(123,74,184,0.15) */
  opacity: 0;
  transition: opacity 0.2s;
}

.cell-kanji-bg {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Zen Old Mincho', serif;
  font-size: 54px;
  font-weight: 700;
  color: rgba(255,255,255,0.08);
  pointer-events: none;
}

.cell-label {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: rgba(20,12,36,0.82);
  padding: 7px 10px;
  border-top: 0.5px solid rgba(156,107,204,0.2);
}
.cell-label-title {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  font-weight: 700;
  color: var(--lavender);
  letter-spacing: 1px;
  line-height: 1.2;
}
.cell-label-sub {
  font-family: 'Cinzel', serif;
  font-size: 9px;
  color: var(--plum4);
  letter-spacing: 2px;
  margin-top: 2px;
}

.cell-edit-icon {
  position: absolute;
  top: 8px; right: 8px;
  width: 20px; height: 20px;
  background: rgba(20,12,36,0.55);
  border: 0.5px solid rgba(156,107,204,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
}
```

#### Village card backgrounds

Light mode:
```css
.bg-konoha   { background: linear-gradient(150deg, #dce8dc 0%, #8aaa8a 45%, #2a4a2a 100%); }
.bg-suna     { background: linear-gradient(150deg, #e8dcc0 0%, #b89050 50%, #5a3a10 100%); }
.bg-kiri     { background: linear-gradient(150deg, #c8d8e8 0%, #5a8aaa 50%, #1a2a3a 100%); }
.bg-akatsuki { background: linear-gradient(150deg, #dcd0e8 0%, #7a4ab0 50%, #2a1040 100%); }
```

Dark mode:
```css
.bg-konoha   { background: linear-gradient(150deg, #1a2e1a 0%, #2d5a2d 45%, #0a140a 100%); }
.bg-suna     { background: linear-gradient(150deg, #2e2010 0%, #6b4c10 50%, #140e04 100%); }
.bg-kiri     { background: linear-gradient(150deg, #0e1e2e 0%, #1a4a6a 50%, #060c14 100%); }
.bg-akatsuki { background: linear-gradient(150deg, #1a0e2e 0%, #4a2a80 50%, #0a0614 100%); }
```

### Tags and badges
```css
.tag {
  font-family: 'Cinzel', serif;
  font-size: 8px;
  letter-spacing: 2px;
  padding: 2px 8px;
  border: 0.5px solid var(--plum3);
  color: var(--plum3);
  text-transform: uppercase;
}
```

### Recent post cards
```css
.recent-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 3px;
}

.recent-card {
  background: var(--bg2);
  border: 0.5px solid var(--border);
  border-top: 1.5px solid var(--plum3);
  padding: 10px 12px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.recent-card:hover {
  border-top-color: var(--oxide);
  background: var(--bg); /* dark: var(--bg4) */
}

/* Diamond accent — same shape as nav gem */
.recent-gem {
  width: 7px;
  height: 7px;
  background: var(--oxide);
  clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
  flex-shrink: 0;
}

.recent-badge {
  font-family: 'Cinzel', serif;
  font-size: 8px;
  letter-spacing: 1px;
  padding: 1px 6px;
  border: 0.5px solid var(--border);
  color: var(--plum3);
  text-transform: uppercase;
}

.recent-title {
  font-family: 'Cinzel', serif;
  font-size: 11px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.3;
  margin-bottom: 4px;
}

.recent-meta { font-family: 'Cinzel', serif; font-size: 9px; color: var(--mist); letter-spacing: 1px; }
.recent-user { color: var(--oxide2); font-weight: 700; }
```

### Social and tool buttons
```css
.social-btn {
  width: 28px; height: 28px;
  border: 0.5px solid var(--border);
  background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
.social-btn:hover            { background: var(--plum); border-color: var(--plum); }
.social-btn svg              { width: 12px; height: 12px; fill: var(--plum2); }
.social-btn:hover svg        { fill: white; }

.tool-btn {
  width: 28px; height: 28px;
  border: 0.5px solid var(--border);
  background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
.tool-btn:hover     { border-color: var(--plum3); }
.tool-btn svg       { width: 12px; height: 12px; fill: none; stroke: var(--dim, var(--plum2)); stroke-width: 1.5; }
.tool-btn:hover svg { stroke: var(--plum2); }
```

---

## 7. Key Differences Between Modes

| Element | Light Mode | Dark Mode |
|---|---|---|
| Page background | `#f4f2f8` | `#0f0c18` |
| Header background | `#ede9f4` | `#130f1e` |
| Primary text | `#1e1428` dark | `#e8dff8` light |
| Stats band | Solid `var(--plum)` | `var(--bg4)` + top/bottom borders |
| Stats text | `rgba(255,255,255,0.4)` | `var(--mist)` |
| Nav hover | `rgba(90,46,154,0.05)` | `rgba(123,74,184,0.08)` |
| Cell overlay | `rgba(90,46,154,0.12)` | `rgba(123,74,184,0.15)` |
| Nav kanji watermark | `rgba(90,46,154,0.04)` | `rgba(123,74,184,0.05)` |
| Rust oxide accent | `#7a2a10` darker | `#c0582a` brighter |

---

## 8. Rust Oxide Usage Rules

The rust oxide accent is the most important visual accent and must appear in **exactly these 3 places** and nowhere else:

1. **Accent line above the title** — `width: 32px; height: 2px`
2. **Hanko seal** — border and text of the logo circle
3. **Navigation and recent card spiral marks** — small Naruto-style swirl indicators, ideally inline SVG or curved line motifs

Never use oxide on: backgrounds, body text, primary buttons, or any role beyond these three.

---

## 9. Iconography

- All SVGs are inline — no external icon libraries
- Standard size: `12×12px` for social and tool buttons
- Card edit icons: `9×9px`
- Social icons: `fill: var(--plum2)` → on hover `fill: white`
- Card edit icons: `stroke: var(--plum2); stroke-width: 1.5; fill: none`
- Tool icons: stroke not fill, `var(--dim)` at rest, `var(--plum2)` on hover

---

## 10. Anime Images in Village Cards

When adding real Naruto artwork to the cards, apply:
```css
.cell-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.7) saturate(1.1);
}
```

In light mode, add a soft tint overlay on top:
```css
.cell-img-tint {
  position: absolute;
  inset: 0;
  background: rgba(61,31,110,0.08);
  mix-blend-mode: multiply;
}
```

The hanko seal can also be used as a watermark on section backgrounds:
```css
.section-hanko-watermark {
  opacity: 0.03;
  position: absolute;
  font-size: 120px;
  font-family: 'Zen Old Mincho', serif;
  font-weight: 700;
  color: var(--oxide);
  pointer-events: none;
}
```

---

## 11. Responsive

```css
@media (max-width: 640px) {
  .header           { grid-template-columns: 1fr; }
  .nav-panel        { display: none; } /* move to hamburger menu */
  .body             { grid-template-columns: 1fr; }
  .img-grid         { grid-template-columns: 1fr; }
  .recent-grid      { grid-template-columns: 1fr; }
  .cell             { height: 90px; }
  .cell:first-child { grid-column: span 1; height: 90px; }
  .header-desc      { grid-template-columns: 1fr; }
}
```

---

## 12. Theme Toggle

```js
function toggleTheme() {
  const html = document.documentElement;
  if (html.getAttribute('data-theme') === 'dark') {
    html.removeAttribute('data-theme');
    localStorage.setItem('theme', 'light');
  } else {
    html.setAttribute('data-theme', 'dark');
    localStorage.setItem('theme', 'dark');
  }
}

// Load saved preference on page load
const saved = localStorage.getItem('theme');
if (saved === 'dark') {
  document.documentElement.setAttribute('data-theme', 'dark');
}
```

---

## 13. Final Notes

- The `font-weight: 300` of Source Serif 4 is ideal for long-form Latin body readability; use `400` only when contrast requires it
- Never use `box-shadow` — fine 0.5px borders replace all visual elevation
- Keep the `3px` gap between cards — it creates the ukiyo-e mosaic feel without separating elements too much
- Decorative large kanji must never be readable at first glance — always at opacity ≤ 0.08
- Both modes share the exact same structure, proportions, and typography — only the color palette changes
