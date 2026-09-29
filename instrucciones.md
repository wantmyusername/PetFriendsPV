# Instrucciones — Pet Friends Puerto Vallarta

Documento de traspaso: repositorio, despliegue y cambios técnicos de SEO aplicados.

---

## 1. Repositorio GitHub

| Dato | Valor |
|---|---|
| Repositorio | https://github.com/wantmyusername/PetFriendsPV |
| Remote | `origin` -> `https://github.com/wantmyusername/PetFriendsPV.git` |
| Rama de producción | `main` |
| Ruta local | `/Users/jesusrodriguez/Projects/PetFriendsPV` |
| Último commit subido | `f4d4933` — "Add canonical tags, sitemap, robots.txt and HTTPS/www redirects" |

> IMPORTANTE: `origin` antes apuntaba por error a
> `github.com/wantmyusername/Universal-Analytics-Traffic-Bot-Node-Deprecated` (repo de otro proyecto,
> historia sin relación). Ya se corrigió a `PetFriendsPV`. Si algún día el push es rechazado o el
> historial aparece "divergido", revisa primero `git remote -v` antes de hacer nada.

Comandos útiles:

```bash
git remote -v                 # debe mostrar PetFriendsPV
git status -sb
git push origin main          # dispara el deploy automático
gh run list --repo wantmyusername/PetFriendsPV --limit 5
gh run watch <run-id> --repo wantmyusername/PetFriendsPV --exit-status
```

---

## 2. Despliegue

- Workflow: `.github/workflows/deploy.yml` — **"Deploy to cPanel (FTP)"**.
- Se dispara con cada `push` a `main` (o manualmente con `workflow_dispatch`).
- Build: `npm ci` + `npm run build`; luego inyecta `dist/pf-config.php` desde el secret `PF_SECRET`
  y sube `dist/` por FTP.
- Destino FTP: `50.31.188.8`, usuario `petfriends@petfriendspv.com`, `server-dir: ./` (raíz del docroot).
- Secrets requeridos en GitHub (Settings -> Secrets and variables -> Actions):
  - `FTP_PASSWORD`
  - `PF_SECRET`
- Los archivos `public/` (incluidos `.htaccess`, `robots.txt` y los `.php`) se copian a `dist/` y se suben.

Dominio productivo: `https://petfriendspv.com`

---

## 3. Por qué perdió posicionamiento

El captcha y el formulario **no** fueron la causa directa (no hay `noindex`, y Googlebot recibe el HTML
completo). Lo que estaba mal y lo que se corrigió:

1. **Sin canonical y con 4 variantes vivas** (`http`/`https` x `www`/sin-`www`), todas respondiendo 200.
   Google podía consolidar la marca a una URL distinta.
2. **Sin `robots.txt` ni `sitemap.xml`** (ambos 404).
3. **Lío de despliegue FTP** (carpeta anidada `public_html/petfriendspv.com/` que hubo que limpiar):
   pudo servir contenido roto/viejo y hacer caer el rastreo.
4. Posible **acción/actualización de spam de Google** por contenido delgado; verificar en Search Console.

---

## 4. Cambios aplicados

| Archivo | Cambio |
|---|---|
| `astro.config.mjs` | Añadido `site: "https://petfriendspv.com"` e integración `sitemap()` |
| `package.json` / `package-lock.json` | Dependencia `@astrojs/sitemap` |
| `src/pages/[lang]/{index,about-us,services,gallery,contact-us,terms}.astro` | `<link rel="canonical">` autorreferencial + tags Open Graph/Twitter. URLs con barra final |
| `src/components/Navbar.astro` | `p()` y `togglePath` normalizan a URL con barra final |
| `src/components/Footer.astro` | `p()` normaliza a URL con barra final |
| Enlaces internos sueltos en `index.astro`, `about-us.astro`, `services.astro` | Añadida barra final |
| `public/robots.txt` | **Nuevo.** Permite todo, bloquea los `.php` internos y declara el sitemap |
| `public/.htaccess` | **Nuevo.** 301 de `http`->`https`, `www`->sin-`www` y `/index.html`->`/` |

Convención de URLs (unificada, coincide con la barra final que ya fuerza Apache):

```
https://petfriendspv.com/            (EN home)
https://petfriendspv.com/<pagina>/   (EN)
https://petfriendspv.com/es/         (ES home)
https://petfriendspv.com/es/<pagina>/
```

---

## 5. Verificación en vivo (post-deploy)

```bash
curl -sS -o /dev/null -w "%{http_code}\n" https://petfriendspv.com/robots.txt
curl -sS -o /dev/null -w "%{http_code}\n" https://petfriendspv.com/sitemap-index.xml
curl -sS https://petfriendspv.com/ | grep -o '<link rel="canonical"[^>]*>'
curl -sS -o /dev/null -w "%{http_code} -> %{redirect_url}\n" http://petfriendspv.com/
curl -sS -o /dev/null -w "%{http_code} -> %{redirect_url}\n" https://www.petfriendspv.com/
curl -sS -o /dev/null -w "%{http_code} -> %{redirect_url}\n" https://petfriendspv.com/about-us
```

Resultados esperados (ya confirmados):

- `robots.txt` -> 200
- `sitemap-index.xml` y `sitemap-0.xml` -> 200
- Canonical del home -> `https://petfriendspv.com/`
- `http://petfriendspv.com/` -> 301 -> `https://petfriendspv.com/`
- `https://www.petfriendspv.com/` -> 301 -> `https://petfriendspv.com/`
- `https://petfriendspv.com/about-us` -> 301 -> `https://petfriendspv.com/about-us/`

El workflow del deploy salió en **success** (run `36504578018`).

---

## 6. Pendientes (acción manual)

1. **Google Search Console**
   - Enviar el sitemap: `https://petfriendspv.com/sitemap-index.xml`.
   - "Inspeccionar URL" en `https://petfriendspv.com/` y pulsar **Solicitar indexación**.
   - Revisar **Acciones manuales** y **Seguridad** (para descartar penalización).
   - Comparar `site:petfriendspv.com` en Google vs Bing.
   - Si la propiedad no está verificada, añadir el meta de verificación en el `<head>`
     (pedir el token y colocarlo en las 6 páginas).
2. **Correo del formulario**: crear `noreply@petfriendspv.com` en cPanel y configurar **SPF/DKIM**
   para que los correos no caigan en spam (no es SEO, pero afecta las conversiones).
3. **`index.html` "Coming Soon"** en la raíz del proyecto: no se despliega (solo se sube `dist/`),
   pero conviene eliminarlo para evitar que se cuele por error.
4. **Contenido**: revisar señales de contenido delgado (imágenes de Unsplash como "acreditaciones",
   testimonios genéricos) por si hay una actualización de spam de Google de por medio.

---

## 7. Desarrollo local

```bash
npm install
npm run dev       # http://localhost:4321
npm run build     # genera dist/
npm run preview
```

Nota: los `.php` no se ejecutan en `astro dev`. Para probarlos:
`php -S 127.0.0.1:8000 -t dist`.
