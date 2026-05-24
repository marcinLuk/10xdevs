# Deploy Plan: GardenLog → Railway

**Data:** 2026-05-24
**Platforma:** Railway (Hobby plan → rozważ Pro przed public launch)
**Repo:** https://github.com/marcinLuk/10xdevs
**Deploy method:** GitHub-triggered (bezpieczniejsze niż `railway up`)

---

## Bramki ręczne (HUMAN-ONLY)

| # | Czynność | Status |
|---|----------|--------|
| GATE 1 | Zaloguj na railway.app | ⬜ |
| GATE 2 | New Project → Deploy from GitHub repo → `marcinLuk/10xdevs` | ⬜ |
| GATE 3 | Add Service → Database → MySQL 8 | ⬜ |
| GATE 4 | App service → Settings → Deploy → Start Command: `php artisan migrate --force && php artisan storage:link && php-fpm` | ⬜ |

---

## Zmienne środowiskowe (ustawione przez agenta)

```
APP_NAME=GardenLog
APP_ENV=production
APP_DEBUG=false
APP_KEY=<wygenerowany przez `php artisan key:generate --show` — ustaw przez railway variables lub dashboard, nie commituj>
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
APP_URL=<ustawiony po pierwszym deployu>
```

---

## Weryfikacja post-deploy

- [ ] `railway logs --lines 50` — brak ostrzeżeń o LOG_CHANNEL=single
- [ ] Strona główna dostępna pod Railway domain
- [ ] `/register` działa, można stworzyć konto
- [ ] Dodanie zadania pojawia się na liście

---

## Rollback

```bash
railway rollback                  # cofa app do poprzedniego buildu
php artisan migrate:rollback      # jeśli migracja z nowego deployu wymaga cofnięcia
```

---

## Co NIE jest objęte tym planem

- GitHub Actions CI/CD (Railway trigger wystarczy dla MVP)
- `ANTHROPIC_API_KEY` (gdy AI search zostanie zaimplementowany)
- `FILESYSTEM_DISK=s3` (gdy zostaną dodane uploady)
- Multi-region / HA
