#!/bin/bash

# fpp_install.sh for fpp-PictureFrame plugin
# Copies files and sets up gdown static binary

PLUGIN_DIR="/home/fpp/media/plugins/fpp-PictureFrame"

# Purge old venv and Python stuff if present
rm -rf /home/fpp/media/plugindata/fpp-PictureFrame/gdown_venv
rm -rf "$PLUGIN_DIR"/.venv  # Any other potential locations
rm -f "$PLUGIN_DIR"/scripts/gdown  # Old binary if any

# Copy sync_gdrive.sh
cp -f "$PLUGIN_DIR"/sync_gdrive.sh "$PLUGIN_DIR"/scripts/sync_gdrive.sh
chmod 755 "$PLUGIN_DIR"/scripts/sync_gdrive.sh
chown fpp:fpp "$PLUGIN_DIR"/scripts/sync_gdrive.sh

# Detect architecture
ARCH=$(uname -m)

case "$ARCH" in
    aarch64)
        BINARY_NAME="gdown-arm64"
        ;;
    armv7l|armv6l)
        BINARY_NAME="gdown-armhf"
        ;;
    x86_64)
        BINARY_NAME="gdown-amd64"
        ;;
    *)
        echo "Unsupported architecture: $ARCH"
        exit 1
        ;;
esac

# Download the static binary from GitHub releases (update tag/version as needed)
wget -O "$PLUGIN_DIR"/scripts/gdown "https://github.com/bcarriveau/fpp-PictureFrame/releases/download/v1.0/$BINARY_NAME"
if [ $? -ne 0 ]; then
    echo "Failed to download gdown binary for $ARCH"
    exit 1
fi

# Make executable
chmod +x "$PLUGIN_DIR"/scripts/gdown
chown fpp:fpp "$PLUGIN_DIR"/scripts/gdown

echo "Installation complete. gdown binary installed for $ARCH."