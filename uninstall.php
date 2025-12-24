#!/bin/bash

# fpp_uninstall.sh for fpp-PictureFrame plugin - Cleans up installed files, configs, and services

echo "Starting fpp-PictureFrame uninstall..."

# Remove config file
CONFIG_FILE="/home/fpp/media/config/plugin.fpp-PictureFrame.json"
if [ -f "$CONFIG_FILE" ]; then
    rm -f "$CONFIG_FILE"
    echo "Removed config file: $CONFIG_FILE"
else
    echo "Config file not found: $CONFIG_FILE (skipping)"
fi

# Remove copied scripts
rm -f /home/fpp/media/scripts/CheckForNewPictureFrameImages.sh
rm -f /home/fpp/media/scripts/pf-monitor*.sh
rm -f /home/fpp/media/scripts/sync_gdrive.sh
echo "Removed installed scripts from /home/fpp/media/scripts/"

# Remove gdown virtual environment and plugindata
VENV_DIR="/home/fpp/media/plugindata/fpp-PictureFrame"
if [ -d "$VENV_DIR" ]; then
    rm -rf "$VENV_DIR"
    echo "Removed plugindata directory: $VENV_DIR"
else
    echo "plugindata directory not found: $VENV_DIR (skipping)"
fi

# Remove any logs (if not already in plugindata)
LOG_FILE="/home/fpp/media/plugins/fpp-PictureFrame/gdrive_sync.log"
if [ -f "$LOG_FILE" ]; then
    rm -f "$LOG_FILE"
    echo "Removed log file: $LOG_FILE"
fi

# Disable and stop Samba services (reverse of install.sh)
systemctl stop smbd nmbd
systemctl disable smbd nmbd
echo "Disabled and stopped smbd/nmbd services"

# Remove Samba setting from FPP settings file
SETTINGS_FILE="/home/fpp/media/settings"
if [ -f "$SETTINGS_FILE" ]; then
    sed -i '/^Service_smbd_nmbd/d' "$SETTINGS_FILE"
    echo "Removed Service_smbd_nmbd setting from $SETTINGS_FILE"
else
    echo "Settings file not found: $SETTINGS_FILE (skipping)"
fi

# Optional: Remove php-imap (uncomment if you want to fully reverse dependencies; be cautious if other plugins use it)
# apt-get -y remove --purge php-imap
# apt-get -y autoremove
# echo "Removed php-imap package"

echo "fpp-PictureFrame uninstall completed"