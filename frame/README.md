# Frame — creator + editor platform (UI prototype)

Plain HTML + CSS + vanilla JavaScript. No framework, no build step, no backend (all data is mock).

## Run
Open `index.html` in a browser, or serve the folder:
    python3 -m http.server 8000   # then open http://localhost:8000

## Structure
- index.html        entry point
- styles/app.css    all prototype styles (light/dark theme via CSS variables)
- js/app.js         all prototype logic: pages, data, admin roles
- reference/        optional starter files (token.css, global.css, ES-module components).
                    NOT wired into the prototype yet; class names differ from styles/app.css.

## Using the prototype
- Landing → Get Started → pick Creator or Editor. The "Role" pill in the top bar switches roles (demo).
- Admin: landing-page footer → "Admin access". Identities: Admin 1, Admin 2, Customer Support (demo only, not real auth).
- Permissions are frontend-only. Real authorization must be enforced on a server.

## Known limitations
Single large js/app.js (not yet split into pages/components/data); CSS charts (no Chart.js); no router/URLs.
