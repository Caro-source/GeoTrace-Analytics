# GeoTrace Analytics

An IP and domain intelligence dashboard. Type in an IP address or a domain
name and see where it is on a map, who owns the network, and a history of
every lookup, stored in a MySQL database.

Built with plain HTML, CSS, JavaScript and PHP, running on XAMPP.

> **Status:** Week 1 (database and API) is done. The web page itself comes in Week 2.

---

## What's in this repo

```
api/
  bootstrap.php     Shared setup: config, database connection, JSON helpers
  lookup.php        Look up an IP or domain and save it to the history
  history.php       Read back recent lookups
database/
  schema.sql        Creates the "geotrace" database and the "lookups" table
config.example.php  Settings template. Copy it to config.php (see below)
.vscode/            Recommended VS Code extensions and editor settings
```

---

## Setting it up on your computer (XAMPP)

You only do this once.

### 1. Install XAMPP

1. Download XAMPP from <https://www.apachefriends.org> (pick PHP 8.1 or newer).
2. Install it with the default options. On Windows it goes to `C:\xampp`.
3. Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.
   Both should turn green.

### 2. Put the project inside XAMPP's web folder

XAMPP serves websites from its `htdocs` folder. Clone (download) this repo there:

1. Open VS Code.
2. Press `Ctrl+Shift+P`, type **Git: Clone**, and press Enter.
3. Paste `https://github.com/Caro-source/GeoTrace-Analytics.git`.
4. When it asks where to save, choose `C:\xampp\htdocs` (Mac: `/Applications/XAMPP/htdocs`).
5. Click **Open** when VS Code offers to open the folder.
   If VS Code suggests installing recommended extensions, click **Install**.

You should now have `C:\xampp\htdocs\GeoTrace-Analytics`.

### 3. Create the database

1. In your browser go to <http://localhost/phpmyadmin>.
2. Click the **Import** tab at the top.
3. Click **Choose File** and pick `database/schema.sql` from the project folder.
4. Scroll down and click **Import**.

On the left you'll now see a database called **geotrace** with a table called **lookups**.

### 4. Create your config file

1. In VS Code, right-click `config.example.php` and choose **Copy**, then right-click
   the project folder and **Paste**.
2. Rename the copy to exactly `config.php`.
3. If you never set a MySQL password in XAMPP, you don't need to change anything.
   If you did, type it between the quotes on the `'password' => ''` line.

`config.php` is in `.gitignore`, so it is **never uploaded to GitHub**. Keep
real passwords only in that file.

### 5. Check that it works

Open these links in your browser. You should see text that starts with `{"ok":true`.

| What it tests | Link |
|---|---|
| Your own public IP | <http://localhost/GeoTrace-Analytics/api/lookup.php> |
| An IP address | <http://localhost/GeoTrace-Analytics/api/lookup.php?target=8.8.8.8> |
| A domain name | <http://localhost/GeoTrace-Analytics/api/lookup.php?target=github.com> |
| Lookup history | <http://localhost/GeoTrace-Analytics/api/history.php> |

Then go back to phpMyAdmin, click **geotrace → lookups**, and you'll see a row for
each lookup you just did.

**If something goes wrong**

- `config.php is missing` → repeat step 4.
- `"saved":false` with a warning about MySQL → MySQL isn't started in the XAMPP Control Panel,
  or the database wasn't imported (step 3).
- `geolocation service could not be reached` → check your internet connection. The free
  service allows 45 lookups per minute, so wait a minute if you've been testing a lot.
- Page not found → make sure the folder in `htdocs` is named `GeoTrace-Analytics`.

---

## API reference

All responses are JSON.

### `GET api/lookup.php`

| Parameter | Example | Meaning |
|---|---|---|
| `target` | `8.8.8.8`, `example.com`, `https://example.com/page` | What to look up. Leave it out to look up your own public IP. |

You can also `POST` with a JSON body `{"target": "example.com"}`.

Example response:

```json
{
  "ok": true,
  "saved": true,
  "data": {
    "id": 4,
    "query": "8.8.8.8",
    "type": "ip",
    "ip": "8.8.8.8",
    "status": "success",
    "country": "United States",
    "countryCode": "US",
    "region": "Virginia",
    "city": "Ashburn",
    "zip": "20149",
    "lat": 39.03,
    "lon": -77.5,
    "timezone": "America/New_York",
    "isp": "Google LLC",
    "org": "Google Public DNS",
    "as": "AS15169 Google LLC",
    "createdAt": "2026-10-27 20:02:01"
  }
}
```

| HTTP code | When |
|---|---|
| 200 | Lookup worked |
| 400 | Input isn't a valid IP or domain |
| 422 | Valid input but no location (private IP like `192.168.x.x`, or domain doesn't exist). Still saved to history. |
| 502 | The geolocation service couldn't be reached |

### `GET api/history.php`

| Parameter | Default | Meaning |
|---|---|---|
| `limit` | 50 | How many lookups to return (1 to 500), newest first |
| `status` | (all) | `success` or `fail` to filter |

---

## Credits

Location data from [ip-api.com](https://ip-api.com) (free, no key needed, for
non-commercial use).
