# Pet Friends Puerto Vallarta

Sitio web estático del **Pet Friends Veterinary Hospital**, una clínica veterinaria bilingüe en Puerto Vallarta (Zona Romántica).

## Stack

- **Astro 5** — static site generator
- **Tailwind CSS v4** — estilos utilitarios
- **Google Fonts** — Open Sans (`font-fredoka`) y Montserrat (`font-readex`)
- **Sin framework JS** — cero React/Vue/Svelte, solo HTML + CSS + JS vanilla

## Estructura

```
src/
  pages/          → 5 rutas estáticas
    index.astro       Home
    about-us.astro    Quiénes somos
    services.astro    Servicios
    gallery.astro     Galería
    contact-us.astro  Contacto
  components/     → 2 componentes compartidos
    Navbar.astro      Navegación + menú móvil
    Footer.astro      Footer con horarios y emergencia
  styles/
    global.css        Fuentes, tema Tailwind, reset
public/           → Assets estáticos (imágenes, SVGs, favicon)
```

## Convenciones

- Todo en un solo idioma (inglés), orientado a expatriados y turistas
- Sin framework JS, sin estado, sin API calls — sitio 100% informativo
- Sin dependencias más allá de Astro y Tailwind
- Sin git — no hay repo, no hay commits
- Diseño mobile-first con Tailwind responsive utilities
- Paleta de colores: `#0e144a` (azul marino), `#0091a1` (turquesa), `#0a0e35` (texto oscuro), `#f4f1eb` (fondo crema)
- Imágenes en `/public/` referenciadas con ruta absoluta (`/imagen.jpg`)

## Horarios actuales

| Día       | Horario        |
|-----------|---------------|
| Lunes     | 9am – 8pm     |
| Martes    | 9am – 8pm     |
| Miércoles | 9am – 8pm     |
| Jueves    | 9am – 8pm     |
| Viernes   | 9am – 8pm     |
| Sábado    | 9am – 7pm     |
| Domingo   | 11am – 7pm    |

Emergencias 24/7: **+52 322 223 2760**

## Cómo correr

```bash
npm run dev     # servidor local con hot-reload
npm run build   # build a dist/
npm run preview # previsualizar build
```

## Notas

- El formulario de contacto es decorativo (demo), no tiene backend conectado
- Todo el contenido es administrable editando los `.astro` directamente
- Los SVGs decorativos están en `/public/` con naming en inglés descriptivo
