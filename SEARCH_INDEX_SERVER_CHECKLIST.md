# Product search index: server checklist

New and edited products are sent to Meilisearch automatically by a queued job, which retries for about 20 minutes. A scheduled re-sync every 10 minutes catches anything that was missed. Run these checks **on the salamat-pos production server**, from the project folder.

## 1. Meilisearch is configured and reachable

```bash
php artisan config:show services.meilisearch
curl -s "<host from above>/health"
```
- ✅ `host` is set, and it's the **same Meilisearch as tekzo-api**.
- ✅ `products_index` is `products`.
- ✅ `/health` returns `{"status":"available"}`.

## 2. The key can write

`MEILISEARCH_KEY` must be the **master/admin key**, or a key with `documents.add` and `documents.delete` on `products`. A search-only key will **not** work.

## 3. Cron runs the scheduler (this also runs the queue worker)

```bash
crontab -l | grep schedule:run
```
✅ Expected: `* * * * * cd /path/to/salamat-pos && php artisan schedule:run >> /dev/null 2>&1`

```bash
php artisan queue:failed    # should be empty
```

## 4. After each deploy

```bash
php artisan config:clear
php artisan products:sync-search-index --all
```
✅ Expected: `Synced N products to the search index.`

Then edit a product in the POS. It should appear in storefront search within about a minute.

## If it still fails

```bash
grep "Meilisearch" storage/logs/laravel*.log | tail -20
```

| Log says | Fix |
|---|---|
| `MEILISEARCH_HOST is not configured` | Set `MEILISEARCH_HOST` in `.env`, then run `config:clear` |
| `cURL error 7` / `failed, retrying` | The server can't reach Meilisearch. Check the host and firewall. |
| `401` / `403` | Wrong key, or a search-only key. Use the admin key. |
| `gave up syncing product X` | Meilisearch was down for more than 20 minutes. Run `--all` once. |
