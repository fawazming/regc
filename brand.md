## RGC Brand Typography System

### Primary Font — Montserrat

**Montserrat** should serve as the main corporate typeface. Its geometric, clean structure complements the strong RGC logo.

| Weight                   | Font Weight | Recommended Use                            |
| ------------------------ | ----------: | ------------------------------------------ |
| **Montserrat Light**     |         300 | Large supporting text, subtle captions     |
| **Montserrat Regular**   |         400 | Body copy, descriptions                    |
| **Montserrat Medium**    |         500 | Navigation, labels, UI text                |
| **Montserrat SemiBold**  |         600 | Subheadings, buttons, emphasis             |
| **Montserrat Bold**      |         700 | Main headings, titles                      |
| **Montserrat ExtraBold** |         800 | Hero headings, major statements            |
| **Montserrat Black**     |         900 | Large display typography, special emphasis |

### Recommended Hierarchy

```text
H1 — Montserrat ExtraBold 800
H2 — Montserrat Bold 700
H3 — Montserrat SemiBold 600
H4 — Montserrat SemiBold 600
Body — Montserrat Regular 400
Lead — Montserrat Medium 500
Caption — Montserrat Regular 400
Button — Montserrat SemiBold 600
Navigation — Montserrat Medium 500
```

### Secondary Font — Inter

**Inter** is recommended as the secondary/supporting typeface, particularly for digital products, websites, dashboards, documents and dense information.

| Weight             | Font Weight | Recommended Use         |
| ------------------ | ----------: | ----------------------- |
| **Inter Regular**  |         400 | Body/UI text            |
| **Inter Medium**   |         500 | Labels, navigation      |
| **Inter SemiBold** |         600 | Subheadings, buttons    |
| **Inter Bold**     |         700 | Emphasis, data headings |

### Font Pairing

**Corporate / Marketing**

```text
HEADINGS
Montserrat Bold / ExtraBold

BODY
Montserrat Regular
```

**Digital / Website / Application**

```text
HEADINGS
Montserrat Bold / SemiBold

BODY
Inter Regular

UI / BUTTONS
Inter Medium / SemiBold
```

This gives RGC a distinctive **Montserrat-led identity** while Inter improves readability in longer digital content.

---

# RGC Complete Color System

### Primary Colors

| Name           | HEX       | RGB            | CMYK            |
| -------------- | --------- | -------------- | --------------- |
| **RGC Navy**   | `#0D1E3F` | `13, 30, 63`   | `79, 52, 0, 75` |
| **RGC Orange** | `#F38321` | `243, 131, 33` | `0, 46, 86, 5`  |

### Secondary / Neutral Colors

| Name           | HEX       | RGB             | CMYK          |
| -------------- | --------- | --------------- | ------------- |
| **Light Gray** | `#F2F4F7` | `242, 244, 247` | `2, 1, 0, 3`  |
| **Charcoal**   | `#1A1A1A` | `26, 26, 26`    | `0, 0, 0, 90` |
| **White**      | `#FFFFFF` | `255, 255, 255` | `0, 0, 0, 0`  |

### Recommended Extended Digital Palette

For websites, dashboards and UI applications, I would extend the palette as follows:

```text
PRIMARY NAVY
#0D1E3F

NAVY 700
#102A56

NAVY 500
#1B3B70

NAVY 100
#E8EDF5

PRIMARY ORANGE
#F38321

ORANGE 700
#D9660C

ORANGE 500
#F79A42

ORANGE 100
#FFF0E2

LIGHT GRAY
#F2F4F7

GRAY 300
#D0D5DD

GRAY 500
#667085

GRAY 700
#344054

CHARCOAL
#1A1A1A

WHITE
#FFFFFF
```

### Color Usage Ratio

A strong RGC application should follow approximately:

**60% White / Light Gray**
**25–30% Deep Navy**
**10–15% Vibrant Orange**

The orange should remain an **accent**, rather than becoming the dominant background color. This preserves the premium corporate feel of the original logo.

### Brand CSS Variables

```css
:root {
  --rgc-navy: #0D1E3F;
  --rgc-navy-700: #102A56;
  --rgc-navy-500: #1B3B70;
  --rgc-navy-100: #E8EDF5;

  --rgc-orange: #F38321;
  --rgc-orange-700: #D9660C;
  --rgc-orange-500: #F79A42;
  --rgc-orange-100: #FFF0E2;

  --rgc-light-gray: #F2F4F7;
  --rgc-gray-300: #D0D5DD;
  --rgc-gray-500: #667085;
  --rgc-gray-700: #344054;

  --rgc-charcoal: #1A1A1A;
  --rgc-white: #FFFFFF;
}
```

**Final recommended combination:** **Montserrat + Inter**, with **`#0D1E3F` RGC Navy** as the dominant brand color and **`#F38321` RGC Orange** as the signature accent.
