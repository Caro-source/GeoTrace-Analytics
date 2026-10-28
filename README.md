# GeoTrace-Analytics
**IP and domain intelligence dashboard.** 
Enter an IP address or a domain name and GeoTrace Analytics shows where it is on an 
interactive map, who owns the network, and a searchable history of every lookup, 
stored in a MySQL database. Batch uploads are saved as named reports you can reopen at any time.
---

## Features

- **Quick lookups:** look up a single IP address or domain name.
- **Detect my IP:** find your own public IP address and location with one click.
- **Interactive map:** every lookup drops a pin on a world map (Leaflet.js + OpenStreetMap).
- **Batch upload:** upload a `.txt` file with one IP or domain per line and look them all up at once.
- **Saved reports:** every batch upload is saved as a named report. Reopen it later to see its results and map pins again.
- **Lookup history:** every search is saved to MySQL and shown in a history table.
- **Analytics:** charts of lookups by country, by type, and over time (Chart.js).
- **Secure by design:** validated input, prepared SQL statements, and HTTPS on the live site.
- -
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
