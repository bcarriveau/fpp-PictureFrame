#!/bin/bash

# fpp-PictureFrame install script - Enhanced with gdown support for Google Drive shared folders

BASEDIR=$(dirname $0)
cd $BASEDIR
cd ..

dpkg --configure -a
apt-get update
apt-get -y install php-imap

cp scripts/CheckForNewPictureFrameImages.sh /home/fpp/media/scripts/
chown fpp:fpp /home/fpp/media/scripts/CheckForNewPictureFrameImages.sh

cp scripts/pf-monitor*.sh /home/fpp/media/scripts/
cp scripts/sync_gdrive.sh /home/fpp/media/scripts/  # Added: Copy sync_gdrive.sh to global scripts (but we'll call via PHP handler)
chown fpp:fpp /home/fpp/media/scripts/pf-monitor*sh
chown fpp:fpp /home/fpp/media/scripts/sync_gdrive.sh
chmod +x /home/fpp/media/scripts/sync_gdrive.sh  # Ensure executable

systemctl --now enable smbd
systemctl --now enable nmbd

sed -i '/^Service_smbd_nmbd/d' /home/fpp/media/settings
echo 'Service_smbd_nmbd = "1"' >> /home/fpp/media/settings

# ────────────────────────────────────────────────────────────────────────────────
# NEW: Install gdown (for Google Drive public shared folder downloads)
# ────────────────────────────────────────────────────────────────────────────────

echo "Installing gdown (Python tool for Google Drive shared folders) for PictureFrame plugin..."

VENV_DIR="/home/fpp/media/plugindata/fpp-PictureFrame/gdown_venv"
VENV_BIN="$VENV_DIR/.venv/bin"

# Create plugindata directory if it doesn't exist
mkdir -p "$(dirname "$VENV_DIR")"
chown fpp:fpp "$(dirname "$VENV_DIR")"  # Ensure permissions

# Skip if gdown is already installed in the venv
if [ -f "$VENV_BIN/gdown" ]; then
    echo "gdown already installed in venv → skipping installation"
else
    echo "Creating Python virtual environment and installing gdown..."

    # Ensure python3-venv is available (usually is on FPP, but safe)
    apt-get -y install python3-venv python3-pip

    python3 -m venv "$VENV_DIR/.venv" || {
        echo "ERROR: Failed to create venv"
        exit 1
    }

    source "$VENV_BIN/activate" || {
        echo "ERROR: Failed to activate venv"
        exit 1
    }

    pip install --upgrade pip setuptools wheel
    pip install gdown || {
        echo "ERROR: Failed to install gdown"
        deactivate
        exit 1
    }

    deactivate
    echo "gdown installed successfully in $VENV_DIR"
fi

# Fix permissions (important for fpp user to run it)
chown -R fpp:fpp "$VENV_DIR"

echo "fpp_install.sh completed (including gdown setup)"