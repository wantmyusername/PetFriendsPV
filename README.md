# Pet Friends Puerto Vallarta

Sitio web del **Pet Friends Veterinary Hospital**, clínica veterinaria bilingüe (EN/ES) en la Zona Romántica de Puerto Vallarta, México.

## Resumen

- Sitio **100% estático** generado con **Astro 5** y estilizado con **Tailwind CSS 4** (vía `@tailwindcss/vite`).
- **Bilingüe inglés/español** con el i18n nativo de Astro: inglés en la raíz (`/`), español con prefijo (`/es/`).
- Páginas: Home, About Us, Services, Gallery, Contact y Terms.
- Formularios de contacto funcionales en hosting compartido con **PHP** (`public/contact.php` + `public/captcha.php`), con capas anti-spam (honeypot, captcha de sesión, token HMAC, rate limit por IP). Los correos llegan a `petfriendspv@gmail.com`.
- Sin backend ni base de datos; `public/` se copia al build final.

## Stack

| Elemento | Detalle |
|---|---|
| Astro | `^5.3.0` |
| Tailwind CSS | `^4.3.0` |
| JS | Vanilla (scripts inline en `.astro`) |
| Hosting | cPanel (FTP) + PHP para el correo |

## Desarrollo

```bash
npm install
npm run dev       # servidor local con hot-reload
npm run build     # build estático a dist/
npm run preview   # previsualizar el build
```

> Los `.php` no se ejecutan en `astro dev`. Para probarlos: `php -S 127.0.0.1:8000 -t dist`.

## Estructura

```
src/
├── components/   Navbar.astro, Footer.astro
├── i18n/         translations.js  (todas las cadenas EN/ES)
├── pages/        wrapper EN + implementación real en [lang]/
└── styles/       global.css
public/           assets, imágenes y PHP (contact.php, captcha.php)
```

Cada página existe dos veces: `src/pages/[lang]/<pagina>.astro` (implementación real) y `src/pages/<pagina>.astro` (wrapper de inglés). Ver `info.md` para el detalle completo.

## Despliegue automático (GitHub Actions + FTP)

Cada `push` a `main` construye el sitio y sube `dist/` al hosting por FTP mediante [SamKirkland/FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action). El workflow está en `.github/workflows/deploy.yml` y solo sube los archivos que cambian.

Destino FTP: `50.31.188.8` → `public_html/petfriendspv.com/`.

### Configuración requerida

Añadir en **Settings → Secrets and variables → Actions**:

| Secret | Valor |
|---|---|
| `FTP_PASSWORD` | Contraseña del usuario FTP `petfriends@petfriendspv.com` |
| `PF_SECRET` | Cadena aleatoria larga para la clave HMAC anti-spam. El workflow genera `dist/pf-config.php` con este valor en cada deploy (no se guarda en el repo). |

También se puede lanzar el deploy manualmente desde la pestaña **Actions** (`workflow_dispatch`).

## Documentación

`info.md` contiene la documentación técnica detallada: estructura de páginas, i18n, paleta, assets, formularios y deuda técnica.
