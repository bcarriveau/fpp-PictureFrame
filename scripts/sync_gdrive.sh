#!/bin/bash

# Google Drive sync script for fpp-PictureFrame plugin
# Syncs images from one or more public shared Google Drive folders to /home/fpp/media/images/<friendly_folder_title>

LOG_FILE="/home/fpp/media/plugins/fpp-PictureFrame/gdrive_sync.log"
mkdir -p "$(dirname "$LOG_FILE")"  # Ensure plugin dir exists
chown fpp:fpp "$(dirname "$LOG_FILE")"

# Ensure log is writable by fpp
if [ -f "$LOG_FILE" ]; then
    chown fpp:fpp "$LOG_FILE" 2>/dev/null || rm -f "$LOG_FILE" 2>/dev/null
fi
touch "$LOG_FILE"
chown fpp:fpp "$LOG_FILE"

echo "Google Drive sync started: $(date)" > "$LOG_FILE" 2>&1

# Load venv
VENV="/home/fpp/media/plugindata/fpp-PictureFrame/gdown_venv/.venv/bin/activate"
source "$VENV" >> "$LOG_FILE" 2>&1 || {
    echo "Failed to activate venv" >> "$LOG_FILE"
    exit 1
}

# Plugin config file (JSON array of folders)
CONFIG_FILE="/home/fpp/media/config/plugin.fpp-PictureFrame.json"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: Config file $CONFIG_FILE not found" >> "$LOG_FILE"
    deactivate
    exit 1
fi

# If single URL arg provided, sync only that
if [ $# -eq 1 ]; then
    SINGLE_URL="$1"
    echo "Single folder sync for $SINGLE_URL" >> "$LOG_FILE"
    GDRIVE_FOLDERS=$(jq '.gdriveFolders // []' "$CONFIG_FILE")
else
    GDRIVE_FOLDERS=$(jq '.gdriveFolders // []' "$CONFIG_FILE")
fi

# Local base dir for images
LOCAL_BASE_DIR="/home/fpp/media/images"
mkdir -p "$LOCAL_BASE_DIR"
chown fpp:fpp "$LOCAL_BASE_DIR"

# Temp dir for sync
TEMP_DIR="/tmp/gdrive_sync_$$"
mkdir -p "$TEMP_DIR"

# Flag for successful sync
SUCCESS=0

# Function to sanitize folder name (replace invalid chars with _, spaces with _)
sanitize_name() {
    echo "$1" | tr -cd '[:alnum:]\-_ ' | tr ' ' '_'
}

# Iterate over each folder in the array
LENGTH=$(echo "$GDRIVE_FOLDERS" | jq 'length')
for (( i=0; i<LENGTH; i++ )); do
    URL=$(echo "$GDRIVE_FOLDERS" | jq -r ".[$i].url")
    if [ -z "$URL" ] || [ "$URL" = "null" ]; then continue; fi

    # For single mode, skip if not matching
    if [ $# -eq 1 ] && [ "$URL" != "$SINGLE_URL" ]; then continue; fi

    echo "Processing URL: $URL" >> "$LOG_FILE"

    # Fetch the folder title from the <title> tag (works for public shared folders)
    TITLE=$(curl -s --max-time 10 "$URL" | grep -oP '<title>\K[^<]+(?= - Google Drive)' || echo "")
    if [ -z "$TITLE" ]; then
        echo "Warning: Could not fetch folder title from $URL. Using fallback 'unknown_folder'" >> "$LOG_FILE"
        TITLE="unknown_folder"
    fi

    # Sanitize title for filesystem safety
    TITLE=$(sanitize_name "$TITLE")

    # Create subdir with sanitized title
    LOCAL_SUBDIR="$LOCAL_BASE_DIR/$TITLE"
    mkdir -p "$LOCAL_SUBDIR"
    chown fpp:fpp "$LOCAL_SUBDIR"

    echo "Syncing to subdir: $LOCAL_SUBDIR" >> "$LOG_FILE"
    echo "Downloading from Drive..." >> "$LOG_FILE"

    # Download folder contents to temp
    gdown --folder "$URL" -O "$TEMP_DIR" --quiet --remaining-ok >> "$LOG_FILE" 2>&1
    if [ $? -eq 0 ]; then
        echo "Download complete. Transferring files..." >> "$LOG_FILE"
        # Sync contents (flatten if subfolders in Drive, ignore existing)
        rsync -av --ignore-existing "$TEMP_DIR/"* "$LOCAL_SUBDIR/" >> "$LOG_FILE" 2>&1
        SUCCESS=1
        LAST_SYNC=$(date '+%Y-%m-%d %I:%M:%S %p')
        echo "Sync successful for $URL" >> "$LOG_FILE"
        # Update per-folder last_sync in config by searching URL
        jq '(.gdriveFolders[] | select(.url == "'"$URL"'").last_sync) = "'"$LAST_SYNC"'"' "$CONFIG_FILE" > "$CONFIG_FILE.tmp" && mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"
    else
        echo "Warning: gdown failed for $URL" >> "$LOG_FILE"
    fi

    # Clean temp
    rm -rf "$TEMP_DIR"/*
done

# Add global last_full_sync timestamp if this was a full sync (no arg) and at least one succeeded
if [ $# -eq 0 ] && [ $SUCCESS -eq 1 ]; then
    LAST_FULL_SYNC=$(date '+%Y-%m-%d %I:%M:%S %p')
    echo "Updating global last_full_sync: $LAST_FULL_SYNC" >> "$LOG_FILE"
    jq '.last_full_sync = "'"$LAST_FULL_SYNC"'"' "$CONFIG_FILE" > "$CONFIG_FILE.tmp" && mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"
fi

rm -rf "$TEMP_DIR"
deactivate

echo "Sync finished - check $LOG_FILE for details" >> "$LOG_FILE"