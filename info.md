# info.md — Pet Friends Puerto Vallarta

Documentación completa del sitio para el siguiente agente. Reemplaza al antiguo `skill.md` (desactualizado: decía que el sitio era monolingüe y tenía 5 páginas; hoy es bilingüe EN/ES con i18n).

---

## 1. Resumen

Sitio web estático del **Pet Friends Veterinary Hospital**, clínica veterinaria bilingüe en la Zona Romántica de Puerto Vallarta (Púlpito 196, Emiliano Zapata, 48380).

- 100% estático, sin backend ni base de datos.
- Bilingüe **inglés (default, sin prefijo) / español (prefijo `/es`)**, resuelto con el i18n nativo de Astro.
- El formulario de contacto es **decorativo/demo** (no envía nada; el del home muestra un `alert()`).
- No hay repositorio git en esta carpeta.

---

## 2. Stack técnico

| Elemento | Versión / detalle |
|---|---|
| Astro | `^5.3.0` (instalado v5.18.1) |
| Tailwind CSS | `^4.3.0` (vía `@tailwindcss/vite`, sin `tailwind.config`) |
| JS | Vanilla (scripts inline en `.astro`), sin React/Vue/Svelte |
| Fuentes | Google Fonts (importadas en `global.css`) |
| Node | v24.12.0 en la máquina actual |

`package.json`:
```json
{
  "name": "petfriendspv",
  "type": "module",
  "scripts": {
    "dev": "astro dev",
    "build": "astro build",
    "preview": "astro preview"
  },
  "dependencies": {
    "@tailwindcss/vite": "^4.3.0",
    "astro": "^5.3.0",
    "tailwindcss": "^4.3.0"
  }
}
```

---

## 3. Cómo correr

```bash
npm run dev       # servidor local con hot-reload
npm run build     # build estático a dist/
npm run preview   # previsualizar el build
```

---

## 4. Estructura del proyecto

```
PetFriendsPV/
├── astro.config.mjs         # config i18n + plugin Tailwind
├── package.json
├── index.html               # PÁGINA "Coming Soon" SUELTA (no la genera Astro; ver nota §13)
├── skill.md                 # doc antigua (desactualizada)
├── info.md                  # este archivo
├── public/                  # assets estáticos servidos en raíz (/imagen.jpg)
├── src/
│   ├── components/
│   │   ├── Navbar.astro      # navegación + menú móvil + toggle idioma
│   │   └── Footer.astro      # footer con pilares, horarios, emergencia, pagos
│   ├── i18n/
│   │   └── translations.js   # TODAS las cadenas EN/ES (diccionario `t`)
│   ├── pages/
│   │   ├── index.astro           # EN home  → importa [lang]/index.astro con lang="en"
│   │   ├── about-us.astro        # EN
│   │   ├── services.astro        # EN
│   │   ├── gallery.astro         # EN
│   │   ├── contact-us.astro      # EN
│   │   ├── terms.astro           # EN
│   │   └── [lang]/
│   │       ├── index.astro       # implementación real de la home
│   │       ├── about-us.astro    # implementación real
│   │       ├── services.astro    # implementación real
│   │       ├── gallery.astro     # implementación real
│   │       ├── contact-us.astro  # implementación real
│   │       └── terms.astro       # implementación real
│   └── styles/
│       └── global.css        # import fuentes + Tailwind + @theme
├── .astro/                   # generado por Astro
└── dist/                     # build (posiblemente DESACTUALIZADO, ver §13)
```

### Patrón de páginas (MUY importante)

Cada página existe dos veces:

1. `src/pages/[lang]/<pagina>.astro` → **implementación real**. Su `getStaticPaths()` solo devuelve `{ params: { lang: "es" } }`; el resto de idiomas lo resuelve vía `Astro.props.lang`.
2. `src/pages/<pagina>.astro` → **wrapper mínimo** para inglés:

```astro
---
import Page from "./[lang]/index.astro";
---
<Page lang="en" />
```

En cada implementación:
```astro
const lang = Astro.params.lang || Astro.props.lang || "en";
const dict = t[lang];
```

Para **añadir/editar una página**, casi siempre se edita el archivo de `[lang]/`. El wrapper raíz solo cambia si se agrega una ruta nueva.

---

## 5. i18n

`astro.config.mjs`:
```js
i18n: {
  defaultLocale: "en",
  locales: ["en", "es"],
  routing: { prefixDefaultLocale: false }, // EN en raíz, ES en /es
}
```

- Diccionario único: `src/i18n/translations.js` exporta `t = { en: {...}, es: {...} }`.
- Estructura anidada: `nav`, `footer`, `home`, `about`, `services`, `gallery`, `contact`, `terms`.
- Se accede con `dict.<seccion>.<clave>`.
- El toggle de idioma está en `Navbar.astro`:
  - `p(path)` genera el href con prefijo `/es` cuando `lang === "es"`.
  - `togglePath`: EN → `/es{currentPath}`; ES → quita `/es`.
- El `<html lang={lang}>` y los `<title>`/`<meta description>` se renderizan condicionalmente por idioma.
- Cada página incluye `hreflang` (en/es/x-default) apuntando a `https://petfriendspv.com`.

### URLs

| EN | ES |
|---|---|
| `/` | `/es/` |
| `/about-us` | `/es/about-us` |
| `/services` | `/es/services` |
| `/gallery` | `/es/gallery` |
| `/contact-us` | `/es/contact-us` |
| `/terms` | `/es/terms` |

---

## 6. Páginas — detalle de secciones

### Home (`src/pages/[lang]/index.astro`, ~1168 líneas)

1. **Hero** — badge bilingüe, H1, subtítulo, botones WhatsApp (`https://wa.me/523223846328`) y Emergencia (`tel:+523322232760`), rating 4.9, imagen `veterinario-principal.jpg` en marco orgánico, sello "Board Certified DVM", tarjeta de credencial.
2. **Intro editorial** — "A New Standard of Veterinary Medicine", cita, 2 tarjetas de valor.
3. **Panorama gallery** — 4 imágenes `gallery-home-1..4.jpg`.
4. **Bento grid de especialidades** (`id="specialties"`) — 5 tarjetas (Diagnostics, Certified, Surgery, Spa, Explore→`/services`). Cada una con SVG de fondo y `clip-path` blob.
5. **Why us + Medical Team** — lista de estándares + tarjeta del Dr. Mohammad Soleimani (avatar `/clinical-director.jpg`, stats 12+/2k+/4.9).
6. **Accreditations** — 4 "diplomas" con imágenes de Unsplash.
7. **Testimonials** — slider con JS (`define:vars={{ list }}`), botones prev/next y dots. Datos en `dict.home.testimonials.list`.
8. **Contact / Appointments** (`id="contact"`) — formulario demo (`onsubmit` con `alert`) + iframe Google Maps + tarjeta de dirección con botones WhatsApp/Emergencia.

### About (`[lang]/about-us.astro`)

Hero con ola SVG → Story & Purpose (stats 12+, 4.9★, 24/7, 4 features) → Meet the Team (Dr. Mohammad Soleimani, `/clinical-director-about.jpg`) → grid de 6 servicios (iconos SVG + `clip-path`) → Testimonio → CTA final.

### Services (`[lang]/services.astro`)

Hero → Intro (`id="catalogo"`) → **Catálogo de 9 servicios** generado por bucle: `dict.services.catalog.core` se mapea con `iconKeys` + `blobPaths` + `imgList` (imágenes de Unsplash) y `svgIcons` inline. Layout alternado izquierda/derecha con bullets y CTAs WhatsApp/Contacto → **Pasos "How it works"** (sección verde `#E1FFD5` con olas) → **FAQ acordeón** (JS) → **Urgency** banda de emergencia con `gato-telefono.webp`.

- Iconos disponibles en `svgIcons`: `veterinary, grooming, sitting, nutritional, hygiene, training, laboratory, pharmacy, vaccines`.
- ⚠️ Si se agrega un 10º servicio hay que añadir entrada en `iconKeys`, `blobPaths` e `imgList` (arrays indexados en paralelo).

### Gallery (`[lang]/gallery.astro`)

Hero → grid con **filtros** (`all`, `sitting`, `veterinary`, `grooming`) controlados por JS. 14 imágenes Unsplash + una "tarjeta informativa dinámica" que cambia texto/icono según el filtro (`dict.gallery.cards`). Títulos/categorías vienen de `dict.gallery.images` (14 entradas, con `categories.en/es`).

### Contact (`[lang]/contact-us.astro`)

Hero → formulario (nombre, apellido, email, teléfono, mensaje — decorativo) + tarjeta lateral (dirección, teléfonos, email, horarios) → sección mapa a pantalla completa con tarjeta flotante de horarios y bloque de emergencia.

### Terms (`[lang]/terms.astro`)

Página legal simple: itera `dict.terms.sections` (8 secciones). Convierte el email en `mailto:`.

---

## 7. Componentes

### `Navbar.astro`
- Header fijo flotante con efecto "pill" que se encoge al hacer scroll (JS con `#main-header` / `#nav-container`).
- Desktop: 5 links + toggle idioma + CTA "Book Appointment".
- Mobile: toggle idioma + botón hamburguesa que abre drawer `#mobile-menu` (JS, cierra al click fuera). Links numerados `01/…05/`.
- Resalta el link activo comparando `data-path` con `location.pathname` (quita prefijo `/es`).
- Props: `lang` (default `"en"`), `currentPath` (default `"/"`).
- Usa `/logo.png` y `/huella.svg`.

### `Footer.astro`
- 4 pilares clínicos (Aesthetic, Therapeutics, Nutrition, Behavior) con SVG de `/public`.
- Bloque marca (`/logo-footer.png`) + redes (Facebook `petfriendspv`, Instagram `petfriendspv`).
- Especialidades (texto), Secciones (links), Horarios + hotline 24/7, métodos de pago (`visa_logo.svg`, `master-card.svg`), copyright + link a `/terms`.
- Props: `lang` (default `"en"`).

---

## 8. Estilos y tema

`src/styles/global.css`:
```css
@import url('...Baloo+Da+2...Open+Sans...Montserrat...');
@import "tailwindcss";

@theme {
  --font-fredoka: 'Open Sans', sans-serif;   /* ¡font-fredoka = Open Sans! */
  --font-readex: 'Montserrat', sans-serif;   /* ¡font-readex = Montserrat! */
}
```

- Clases de fuente: `font-fredoka` (títulos, = Open Sans) y `font-readex` (cuerpo, = Montserrat). Los nombres NO coinciden con las familias reales.
- **`Baloo Da 2` se importa pero no se usa.** `font-manrope` se usa en `index.astro` pero **no está definida** en el `@theme` (clase inválida).
- No hay `tailwind.config.js`; el tema se define en el `@theme` de Tailwind v4.

### Paleta

| Color | Uso |
|---|---|
| `#0091a1` | Turquesa de marca (home, navbar, footer, botones) |
| `#0e144a` | Azul marino (páginas internas: about/services/gallery/contact/terms) |
| `#0a0e35` | Texto oscuro (home/footer) |
| `#f4f1eb` | Fondo crema |
| `#fdfdfd` / `#fcfcfa` | Blancos suaves |
| `emerald-500/600` | Acento verde en páginas internas (Tailwind por defecto, NO es color de marca) |
| `rose-*` | Emergencias |
| `#E1FFD5` | Fondo sección "How it works" |

⚠️ **Inconsistencia de marca:** la home/navbar/footer usan turquesa `#0091a1`; las páginas internas usan `#0e144a` + `emerald-500`. Si se unifica el diseño, revisar esas dos paletas.

---

## 9. Assets en `public/`

- **Logos:** `logo.png`, `logo.svg` (410 KB, pesado), `logo-footer.png`, `favicon.png`.
- **Imágenes de contenido:** `hero-home.jpg` (1.5 MB), `veterinario-principal.jpg`, `clinical-director.jpg`, `clinical-director-about.jpg`, `pet-friends-about.jpg`, `gallery-home-1..4.jpg`, `cabecera.jpg`, `pz-breadcrumb-image-1.jpg`, `footer-image-dog.webp`, `gato-telefono.webp`, `perro-telefono.webp`, `perro-sider.png`, `perro-gato.webp`, `perro-juguete.svg`, `blog-line-mask-image.webp`, `line-image.png`.
- **Iconos SVG:** `huella.svg`, `huesito.svg`, `health.svg`, `certified.svg`, `veterinary-care.svg`, `dog-grooming.svg`, `dog-boarding.svg`, `dog-daycare.svg`, `dog-training.svg`, `pet-food.svg`, `nutritional.svg`, `hygiene.svg`, `pet-salon.svg`, `more.svg`, `belt.svg`.
- **Pagos:** `visa_logo.svg`, `master-card.svg`.
- **Marcas de alimento (parecen sin uso actual):** `beneful.png`, `felix.png`, `friskies.webp`, `gourmet.png`, `pedigree.png`, `purina.webp`, `excellent.webp`.
- Imágenes externas: bastantes de **Unsplash** (accreditations, servicios, galería, heroes internos). Requieren conexión.

---

## 10. Datos de contacto / integraciones

| Dato | Valor |
|---|---|
| Emergencias 24/7 | `+52 322 223 2760` (`tel:+523222232760`) |
| WhatsApp | `+52 322 384 6328` (`https://wa.me/523223846328`) |
| Email | `petfriendspv@gmail.com` |
| Facebook | https://www.facebook.com/petfriendspv |
| Instagram | https://www.instagram.com/petfriendspv/ |
| Google Maps (embed) | iframe con place "Pet Friends Veterinary Hospital" |
| Maps link | https://maps.app.goo.gl/LTNGTdTTWT2TFGvn6 |
| Dominio canónico | `https://petfriendspv.com` |

**Horarios:** Lun–Vie 9:00–20:00 · Sáb 9:00–19:00 · Dom 11:00–19:00.

---

## 11. Formularios y anti-spam (hosting compartido + PHP)

Los formularios del **home** (`#home-contact-form`) y de **contacto** (`#contact-form`) son reales y funcionan sin servicios externos ni cuentas.

- `public/captcha.php` — genera una suma aleatoria y guarda la respuesta en `$_SESSION`; devuelve `{"q":"3 + 4"}`.
- `public/contact.php` — recibe el POST, valida y envía con `mail()`. Config editable al inicio del archivo:
  - `$TO = 'petfriendspv@gmail.com'` — destino de los correos (admite varios separados por coma)
  - `$FROM = 'noreply@petfriendspv.com'` — remitente del dominio; crear este correo en cPanel para no caer en spam. El `Reply-To` se pone automáticamente al email del visitante.
- Astro copia `public/` a `dist/`, así que los `.php` quedan en la raíz del sitio al subirlo.

**Anti-spam (sin cuentas, gratis):** capas en `contact.php`, en este orden:
1. **Origin/Referer** — el POST debe venir del propio dominio (bloquea posteos externos).
2. **Honeypot** `website` (oculto) — bots que lo rellenan se descartan en silencio.
3. **Token firmado HMAC** (`ts` + `token`) ligado a la sesión — no se puede postear sin cargar el captcha; ventana de 3 s a 2 h.
4. **Captcha de sesión** (`captcha`) — suma o multiplicación aleatoria (una sola vez).
5. **Rate limit por IP** (archivo `.pf_ratelimit.json` fuera de la raíz web) + máx. 3 por sesión.
6. **Filtro de enlaces** (>2 URLs) y **palabras clave** de spam → descarte silencioso.

**Config compartida:** `public/pf-config.php` define `$PF_SECRET` (clave HMAC). **Cámbiala por una cadena larga y aleatoria** antes de publicar.

**Notas:**
- En `astro dev` los `.php` **no se ejecutan**; probar en el hosting o con `php -S 127.0.0.1:8000 -t dist`.
- Los formularios se envían por `fetch` y muestran el resultado **inline** (`#home-form-status` / `#contact-form-status`), sin `alert`, con colores verde/rojo (`dict.contact.form.success/error`).
- El correo se envía en **multipart/alternative** (texto + HTML). La plantilla HTML está en `contact.php` (logo `https://petfriendspv.com/logo.png`, colores de marca, tabla de campos y CTA "Responder al cliente").
- Para entrega confiable: remitente del propio dominio, SPF/DKIM en cPanel, y revisar la carpeta de spam la primera vez.
- Si el hosting bloquea `mail()`, migrar a PHPMailer con el SMTP del hosting (sigue siendo gratis).

## 12. Convenciones de código

- Sin comentarios en el código (salvo los existentes de secciones).
- Componentes `.astro` con frontmatter `---` + HTML + `<style>` / `<script>` al final.
- Scripts de cliente con `<script>` vanilla; para pasar datos del servidor se usa `define:vars={{ ... }}`.
- Utilidades Tailwind inline, mobile-first (`sm:`, `md:`, `lg:`).
- Rutas de assets en `/public` siempre absolutas (`/imagen.jpg`).
- Contenido administrable editando `.astro` + `translations.js` directamente.

---

## 13. Estado actual / deuda técnica (para el siguiente agente)

1. **`skill.md` desactualizado** — describe un sitio monolingüe de 5 páginas. Usar este `info.md`.
2. **`dist/` probablemente desactualizado** (build de jun 2026; el código fuente tiene cambios posteriores). Reconstruir con `npm run build` antes de desplegar.
3. **`index.html` en la raíz** es una página "Coming Soon" independiente que **no genera Astro** (Astro escribe `dist/index.html`, no `/index.html`). Confirmar si debe eliminarse o moverse a `public/`.
4. **`font-manrope`** se usa en `index.astro` pero no existe en el `@theme` → clase sin efecto.
5. **`Baloo Da 2`** importada en `global.css` y sin uso.
6. **Footer con horarios hardcodeados en inglés** ("Mon - Fri…") incluso en la versión ES.
7. **Cita de testimonio hardcodeada en inglés** en el HTML inicial de la home (el JS la reemplaza al navegar, pero queda en el markup).
8. **Formularios ya funcionales** (ver §11) vía `public/contact.php` + `public/captcha.php`. Los correos llegan a `petfriendspv@gmail.com`. Falta crear el correo de dominio `noreply@petfriendspv.com` y configurar SPF/DKIM para evitar spam.
9. **Inconsistencia de paleta** turquesa (`#0091a1`) vs. navy+emerald en páginas internas (§8).
10. **Arrays indexados en paralelo** en `services.astro` (`iconKeys`/`blobPaths`/`imgList`/`dict...core`) — frágiles al añadir servicios.
11. **Dependencias de imágenes externas (Unsplash)** en varias páginas; considerar alojarlas en `/public` para evitar fallos y mejorar rendimiento.
12. **Assets pesados**: `logo.svg` (410 KB), `hero-home.jpg` (1.5 MB) — optimizar.
13. **No hay git** en la carpeta; no hay control de versiones.

---

## 14. Comandos útiles

```bash
npm run dev
npm run build
npm run preview
npx astro --version
```
