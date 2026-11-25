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

Copy `backend/config/config.local.example.php` to `backend/config/config.local.php` and enter your MySQL password. This file is not committed to git.

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

`--fresh` replaces whatever is in Neon with the current MySQL data and images. Without it, the script stops if Neon already has data.

The script also copies every image file from `backend/uploads/` into Neon (the `media_files` table). To copy only the images again:

```powershell
php -d extension=pdo_pgsql backend/database/migrate_to_pgsql.php --images
```

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

To deploy later changes, push to GitHub. Render rebuilds automatically.

### 4. After deploying

1. **Change the admin password** in *Admin → My Account*. The copied data still uses the local password.
2. **Set up SEO** in *Admin → Site Settings → SEO*:
   - **Website URL (canonical):** `https://<service-name>.onrender.com`, or your own domain if you add one.
   - Add the Google Search Console code and Google Analytics ID if you have them.
   - Check that `https://<service-name>.onrender.com/robots.txt` and `/sitemap.xml` open. Then submit the sitemap in Google Search Console.
3. **Fix alt text:** in *Admin → Media Library*, choose the filter **Needs alt text** and describe each image.
4. **Contact form email:** the SMTP settings were copied with the data. Send a test email from *Admin → SMTP / Email*.

### Good to know

- **Images are kept in Neon.** Images uploaded in the admin on Render are saved in the database, so they survive redeploys and restarts. `backend/uploads/` on the server is only a cache that rebuilds itself from Neon.
- **The free plan sleeps after 15 minutes without visits.** The first visit after that takes about 30–60 seconds to wake up.
- **Your own `robots.txt` / `sitemap.xml`:** put them in `frontend/public/` and they are served as-is. Without them, the site generates both from the SEO settings.
- **Local MySQL and Neon are separate.** Changes made in the local admin don't reach Neon automatically. To copy local data to Neon again, run step 1 with `--fresh`, which replaces everything in Neon, including enquiries received on the live site.

## How content works

- Every section of the page (Header, Hero, About, Services, Goals, Process, Industries, Training, Careers, Partners, States, Contact, Footer, Privacy Policy & Terms) is edited in *Admin → Page Sections*: text, images, icons, cards, order and visibility.
- All images live in the **Media Library** (`backend/uploads/` plus the `media` table).
- Headings: `{orange words}` and `[indigo words]`. Descriptions: `**bold**`. Legal text: `## heading`, `- bullet`.
- SMTP (email) settings are in *Admin → SMTP / Email* and stored in the database. There is no .env file.
- Local MySQL credentials are in `backend/config/config.local.php` (not in git). On Render, the `DATABASE_URL` environment variable is used.
