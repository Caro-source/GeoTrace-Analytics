# GeoTrace Analytics

**IP and domain intelligence dashboard.** Enter an IP address or a domain name and
GeoTrace Analytics shows where it is on an interactive map, who owns the network, and
a searchable history of every lookup, stored in a MySQL database.

> **Status:** in development. See the [roadmap](#roadmap) for what's done and what's next.

---

## Features

- **Quick lookups:** look up a single IP address or domain name.
- **Detect my IP:** find your own public IP address and location with one click.
- **Interactive map:** every lookup drops a pin on a world map (Leaflet.js + OpenStreetMap).
- **Batch upload:** upload a `.txt` file with one IP or domain per line and look them all up at once.
- **Lookup history:** every search is saved to MySQL and shown in a history table.
- **Analytics:** charts of lookups by country, by type, and over time (Chart.js).
- **Secure by design:** validated input, prepared SQL statements, and HTTPS on the live site.

## Built with

| Part | Technology |
| --- | --- |
| Page structure | HTML5 |
| Styling | CSS3 |
| Interactivity | JavaScript (`fetch()`, `FileReader`) |
| Server / API | PHP 8 |
| Database | MySQL / MariaDB (XAMPP for local development) |
| Map | [Leaflet.js](https://leafletjs.com) with [OpenStreetMap](https://www.openstreetmap.org) tiles |
| Charts | [Chart.js](https://www.chartjs.org) |
| Location data | [ip-api.com](https://ip-api.com) (free, no API key) |
| Editor | Visual Studio Code |
| Hosting | PHP + MySQL web host with SSL, domain from Namecheap |

---

## Running it on your computer

### What you need

- [XAMPP](https://www.apachefriends.org) (PHP 8.1 or newer)
- [Git](https://git-scm.com/downloads)
- [Visual Studio Code](https://code.visualstudio.com)

### Setup

1. **Start XAMPP.** Open the XAMPP Control Panel and click **Start** next to **Apache** and **MySQL**.
2. **Get the code.** In VS Code press `Ctrl+Shift+P`, choose **Git: Clone**, paste
   `https://github.com/Caro-source/GeoTrace-Analytics.git`, and save it in `C:\xampp\htdocs`.
3. **Create the database.** Go to <http://localhost/phpmyadmin>, open the **Import** tab,
   choose `database/schema.sql`, and click **Import**.
4. **Add your settings.** Copy `config.example.php`, name the copy `config.php`, and fill in
   your database details. `config.php` is listed in `.gitignore`, so your password never
   reaches GitHub.
5. **Open the site** at <http://localhost/GeoTrace-Analytics/>.

### Recommended VS Code extensions

- PHP Intelephense
- SQLTools + SQLTools MySQL/MariaDB Driver
- EditorConfig

---

## Project structure

```
GeoTrace-Analytics/
├── index.html            Main dashboard page
├── css/style.css         Styles
├── js/app.js             Lookups, map, batch upload and charts
├── api/
│   ├── lookup.php        Look up an IP or domain and save it
│   ├── history.php       Return past lookups
│   └── stats.php         Counts for the charts
├── database/schema.sql   Creates the database and tables
├── config.example.php    Settings template (copy to config.php)
└── favicon files         Site icon
```

## API

All endpoints return JSON.

| Endpoint | What it does |
| --- | --- |
| `GET api/lookup.php?target=8.8.8.8` | Look up an IP or domain. Leave out `target` to look up your own IP. |
| `GET api/history.php` | The most recent lookups, newest first. |
| `GET api/stats.php` | Lookup counts by country, type and day. |

---

## Roadmap

**Week 1: Database and setup**
- [ ] XAMPP environment set up
- [ ] MySQL tables for lookup history
- [ ] PHP endpoints (`api/lookup.php`, `api/history.php`)

**Week 2: Frontend and core lookups**
- [ ] `index.html` and `style.css`
- [ ] Single IP / domain lookups with `fetch()`

**Week 3: Visualizations and batch upload**
- [ ] Leaflet.js map pins
- [ ] `.txt` batch upload with `FileReader`
- [ ] Chart.js analytics

**Week 4: Security and final polish**
- [ ] Input sanitization
- [ ] Custom favicon and branding
- [ ] Public IP auto-detection tested on the live site
- [ ] Project documentation

**Launch**
- [ ] Domain registered with Namecheap
- [ ] Hosted with an SSL certificate (HTTPS)

---

## Screenshots

_Coming soon._

## Credits

- Location data: [ip-api.com](https://ip-api.com)
- Maps: [Leaflet](https://leafletjs.com) and [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors
- Charts: [Chart.js](https://www.chartjs.org)

## License

[MIT](LICENSE) © 2026 Caroline
