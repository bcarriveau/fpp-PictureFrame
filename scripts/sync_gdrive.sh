#!/bin/bash

# Google Drive sync script for fpp-PictureFrame plugin
# Syncs images from one or more public shared Google Drive folders to /home/fpp/media/images/<friendly_folder_title>

LOG_FILE="/tmp/gdrive_sync.log"
echo "Google Drive sync started: $(date)" > "$LOG_FILE" 2>&1

# Load venv
VENV="/home/fpp/media/plugindata/PictureFrame/gdown_venv/.venv/bin/activate"
source "$VENV" >> "$LOG_FILE" 2>&1 || {
    echo "Failed to activate venv" >> "$LOG_FILE"
    exit 1
}

# Plugin settings file (FPP saves as key = value lines)
SETTINGS_FILE="/home/fpp/media/config/plugin.fpp-PictureFrame"
if [ ! -f "$SETTINGS_FILE" ]; then
    echo "Error: Settings file $SETTINGS_FILE not found" >> "$LOG_FILE"
    deactivate
    exit 1
fi

# Extract gdrive_folder_urls (handle quoted or unquoted value)
GDRIVE_URLS=$(sed -n 's/^gdrive_folder_urls = \(.*\)/\1/p' "$SETTINGS_FILE" | sed 's/^[" ]*//;s/[" ]*$//')

# Local base dir for images
LOCAL_BASE_DIR="/home/fpp/media/images"
mkdir -p "$LOCAL_BASE_DIR"
chown fpp:fpp "$LOCAL_BASE_DIR"

# Temp dir for sync
TEMP_DIR="/tmp/gdrive_sync_$$"
mkdir -p "$TEMP_DIR"

# Flag for successful sync
SUCCESS=0

# Function to sanitize folder name (replace invalid chars with _)
sanitize_name() {
    echo "$1" | tr -cd '[:alnum:]\-_ ' | tr ' ' '_'
}

# Split URLs by semicolon and sync each
IFS=';'
for URL in $GDRIVE_URLS; do
    URL=$(echo "$URL" | xargs)  # Trim whitespace
    if [ -z "$URL" ]; then continue; fi

    echo "Processing URL: $URL" >> "$LOG_FILE"

    # Fetch the folder title from the <title> tag (works for public shared folders)
    TITLE=$(curl -s --max-time 10 "$URL" | grep -oP '<title>\K[^<]+(?= - Google Drive</title>)' || echo "")
    if [ -z "$TITLE" ]; then
        echo "Warning: Could not fetch folder title from $URL (may not be public or network issue). Using fallback 'unknown'" >> "$LOG_FILE"
        TITLE="unknown"
    fi

    # Sanitize title for filesystem safety
    TITLE=$(sanitize_name "$TITLE")

    # Create subdir with sanitized title
    LOCAL_SUBDIR="$LOCAL_BASE_DIR/$TITLE"
    mkdir -p "$LOCAL_SUBDIR"
    chown fpp:fpp "$LOCAL_SUBDIR"

    echo "Syncing to subdir: $LOCAL_SUBDIR" >> "$LOG_FILE"

    # Download folder contents to temp
    gdown --folder "$URL" -O "$TEMP_DIR" --quiet --remaining-ok >> "$LOG_FILE" 2>&1
    if [ $? -eq 0 ]; then
        # Sync contents (flatten, ignore existing)
        rsync -av --ignore-existing "$TEMP_DIR/"* "$LOCAL_SUBDIR/" >> "$LOG_FILE" 2>&1
        SUCCESS=1
    else
        echo "Warning: gdown failed for $URL" >> "$LOG_FILE"
    fi

    # Clean temp
    rm -rf "$TEMP_DIR"/*
done

rm -rf "$TEMP_DIR"
deactivate

if [ $SUCCESS -eq 1 ]; then
    LAST_SYNC=$(date '+%Y-%m-%d %H:%M:%S')
    echo "Sync successful. Updating last sync time to $LAST_SYNC" >> "$LOG_FILE"
    if grep -q "^gdrive_last_sync =" "$SETTINGS_FILE"; then
        sed -i "s/^gdrive_last_sync =.*/gdrive_last_sync = \"$LAST_SYNC\"/" "$SETTINGS_FILE"
    else
        echo "gdrive_last_sync = \"$LAST_SYNC\"" >> "$SETTINGS_FILE"
    fi
else
    echo "No successful syncs occurred" >> "$LOG_FILE"
fi

echo "Sync finished - check $LOG_FILE for details" >> "$LOG_FILE"