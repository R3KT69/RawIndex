<div align="center">

# ⚡ RawIndex

**A zero-dependency, single-file PHP directory indexer and file-sharing utility for Apache / XAMPP.**

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![Apache](https://img.shields.io/badge/Server-Apache%20%2F%20XAMPP-D22128?style=flat-square&logo=apache&logoColor=white)
![Architecture](https://img.shields.io/badge/Footprint-Single--File-success?style=flat-square)
![UI](https://img.shields.io/badge/Theme-Dark%20Mode-121212?style=flat-square)

<br>

<img width="1075" height="796" alt="image" src="https://github.com/user-attachments/assets/e30346fc-2874-4f57-a3a9-031cdde9c7ab" />


</div>

---

## // Features

* **Zero Configuration:** Drop `index.php` into any directory and it works immediately—no database required.
* **Auto-Discovery:** Drag and drop files into the folder; they are automatically indexed, sized, and sorted by modification date.
* **Host-Only Inline Labeling:** Add or edit custom tags directly from the UI when browsing via `localhost`. Labels persist in a lightweight local `labels.json` file.
* **Public Read-Only Mode:** External visitors accessing via your public IP get a clean dark-mode interface with instant live search and one-click downloads, without permission to modify labels.

## // Directory Setup

Navigate to your XAMPP `htdocs` directory, create a `share` folder, and drop `index.php` inside:

<img width="361" height="30" alt="image" src="https://github.com/user-attachments/assets/df66b189-4467-43ea-8e04-f596a9be687e" />


```text
C:\xampp\htdocs\share\
├── index.php          # Core application script
├── labels.json        # Auto-generated when you add your first label
└── your_archive.zip   # Any files you drop here are auto-indexed
