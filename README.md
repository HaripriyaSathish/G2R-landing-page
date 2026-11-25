# G2R Global Solutions — Landing Page

| Part | Tech | Folder |
|---|---|---|
| Website | React + Vite + Framer Motion | `frontend/` |
| API + Admin panel | Core PHP 8 (no Composer) | `backend/` |
| Database | MySQL 8 (`g2r_landing`) | `backend/database/` |

## Run everything (one command)

```powershell
cd "C:\G2R landing page"
npm run dev
```

| Open | URL |
|---|---|
| Website | http://localhost:5173 |
| Admin panel | http://localhost:5173/admin (admin / admin123) |

This starts the PHP backend on port 8000 and the website on port 5173. Vite forwards `/admin`, `/api` and `/uploads` to PHP, so you only need port 5173. Press **Ctrl + C** to stop both.

The website always uses port 5173. If something else is already using that port, it stops with an error instead of switching to 5174. Close the other terminal first.

### Or run them in two terminals

```powershell
# Terminal 1 — backend
cd "C:\G2R landing page\backend"
php -S localhost:8000 router.php
```
```powershell
# Terminal 2 — frontend
cd "C:\G2R landing page\frontend"
npm run dev
```

### First time on a new computer

```powershell
cd "C:\G2R landing page"
npm run setup
php backend/database/install.php --images="C:\Users\Haripriya\Downloads\G2R IMAGES\G2R IMAGES"
```

`install.php --fresh` wipes the database and reinstalls the original content.

## Production build

```powershell
npm run build          # outputs frontend/dist
```
Set `VITE_API_URL` (e.g. `https://api.yourdomain.com`) before building if the backend is on a different domain, and add that website domain under *Admin → Site Settings → Advanced → Extra allowed origins*.

## Deploy to Render (one Web Service, one URL)

Local development keeps using MySQL. When the `DATABASE_URL` environment variable is set, the backend uses PostgreSQL (Neon) instead. Nothing else changes.

On Render, a single Docker web service runs everything at one address:

| URL | What |
|---|---|
| `https://<service-name>.onrender.com/` | Website |
| `https://<service-name>.onrender.com/admin` | Admin panel |
| `https://<service-name>.onrender.com/api/...` | API |

### 1. Copy the local MySQL data into Neon (already done once)

MySQL is only read; nothing in it changes.

```powershell
cd "C:\G2R landing page"
$env:DATABASE_URL = "<your Neon connection string>"
php -d extension=pdo_pgsql backend/database/migrate_to_pgsql.php --fresh
Remove-Item Env:DATABASE_URL
```

`--fresh` replaces whatever is in Neon with the current MySQL data. Without it, the script stops if Neon already has data.

### 2. Push the project to GitHub

```powershell
cd "C:\G2R landing page"
git init
git add .
git commit -m "G2R landing page"
git branch -M main
git remote add origin https://github.com/<you>/<repo>.git
git push -u origin main
```

### 3. Create the Web Service on Render (not a Blueprint)

1. Go to https://dashboard.render.com, then **New → Web Service**.
2. Connect the GitHub repo.
3. Set these fields:
   - **Language / Runtime:** Docker (Render detects the `Dockerfile` in the root).
   - **Region:** Ohio (US East), the same region as the Neon database, so the site stays fast.
   - **Branch:** main. Leave **Root Directory** empty.
   - **Instance type:** Free or Starter.
4. Under **Environment Variables**, add:
   - `DATABASE_URL` = your Neon connection string.
5. Click **Create Web Service**. The first build takes about 3–5 minutes.

Render then shows your single link, `https://<service-name>.onrender.com`. Open `/admin` on that link and log in.

### After deploying

- **Change the admin password** in *Admin → My Account*. The copied data still uses the local password.
- **Images uploaded on Render are temporary.** Render's disk is wiped on every deploy and restart. Images already in `backend/uploads/` are built into the deploy and always work. For images you add later, upload them locally, then commit and push. Alternatively, add a Render persistent disk (paid) mounted at `/app/backend/uploads`.
- **The free plan sleeps after 15 minutes without visits.** The first visit after that takes about 30–60 seconds to wake up.
- **Contact form email:** the SMTP settings were copied with the data. Check *Admin → SMTP / Email* on the live site.

## How content works

- Every section of the page (Header, Hero, About, Services, Goals, Process, Industries, Training, Careers, Partners, States, Contact, Footer, Privacy Policy & Terms) is edited in *Admin → Page Sections*: text, images, icons, cards, order and visibility.
- All images live in the **Media Library** (`backend/uploads/` plus the `media` table).
- Headings: `{orange words}` and `[indigo words]`. Descriptions: `**bold**`. Legal text: `## heading`, `- bullet`.
- SMTP (email) settings are in *Admin → SMTP / Email* and stored in the database. There is no .env file.
- Database credentials are in `backend/config/config.php`.
