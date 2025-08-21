# Custom Events Manager

A lightweight WordPress plugin to manage events with date, location, and organizer. Includes an admin settings page and a frontend shortcode to list upcoming events.

---

## Features
- Custom Post Type: **Events** (`cem_event`)
- Event details meta box: **Date**, **Location**, **Organizer**
- Admin columns for quick view (sortable by Date)
- Settings page under **Events → Settings**:
  - Default number of events to show
  - Include/exclude past events
  - Default organizer fallback
- Frontend shortcode `[cem_events]` to list events
- Uninstall script to clean up saved plugin options

---

## Installation
1. Download or clone the plugin folder `custom-events-manager`.
2. Upload to `/wp-content/plugins/` or use **Plugins → Add New → Upload Plugin**.
3. Activate **Custom Events Manager** from the WordPress Plugins page.
4. Go to **Settings → Permalinks → Save Changes** once (to refresh CPT links).

---

## Usage
1. Go to **Events → Add New** and create events.
2. Fill in **Date**, **Location**, and **Organizer** in the meta box.
3. Insert the shortcode on any page or post:

[cem_events]


### Shortcode Options
- `limit="10"` → number of events to show  
- `include_past="1"` → include past events  

**Examples:**


[cem_events limit="8"]
[cem_events include_past="1"]
[cem_events limit="12" include_past="1"]


---

## Author
**Royce Justun Vaz**
