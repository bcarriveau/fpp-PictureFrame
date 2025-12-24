# fpp-PictureFrame
Use FPP as a Picture Frame

## Overview
This plugin allows you to transform your Falcon Player (FPP) setup into a digital picture frame. It supports fetching images via email (IMAP) from valid senders and displaying them in slideshow playlists. With the new addition, it also includes synchronization from public shared Google Drive folders.

## Installation
1. Install the plugin via FPP's Plugin Manager.
2. Configure in the plugin UI under Content Setup > Plugins > fpp-PictureFrame.

## Usage-(IMAP Mail Server)
- **Email Sync**: Set up IMAP server details and valid sender emails. Images attached to emails from approved senders are downloaded to specified folders.
- **Picture Folders**: Manage local image folders for organization.
- **Generate Playlist**: Create an example slideshow playlist that cycles through images with pauses.
- **Check For New Images**: Manually fetch new images from configured sources.

## New Feature: Google Drive Sync
This addition enables automatic or manual synchronization of images from public shared Google Drive folders to your local FPP image directories.

### How It Works
- Add shared folder URLs (must be public with "Anyone with the link" access) in the "Google Drive Folders" section of the plugin settings.
- The plugin fetches the folder title and creates a local subdirectory in `/home/fpp/media/images/` (e.g., `/home/fpp/media/images/My_Shared_Folder/`).
- Images are downloaded using `gdown` and synced (new files only, ignoring existing ones) via rsync.
- Last sync timestamps are updated in the config for each folder.

### Manual Sync
- Use the "Sync" button next to each folder for individual syncs.
- Use the "Sync All" button to sync all configured folders at once.
- Progress and logs are shown in a modal dialog.
Note- Too many Syns in short succession can cause google to block it for a while.

### Automatic Sync(If setup)
To enable background auto-syncing:
1. Add the sync_gdrive.sh script to a playlist or schedual
Note- Too many Syns in short succession can cause google to block it for a while.

This runs the full sync periodically. Check `/home/fpp/media/plugins/fpp-PictureFrame/gdrive_sync.log` for details.

### Requirements
- Public Google Drive folders (no authentication needed).
- `gdown` Python tool (automatically installed in a venv during plugin setup).
- jq and rsync (usually pre-installed on FPP).


## Uninstall
Run `uninstall.php` to clean up data, including the venv and logs.