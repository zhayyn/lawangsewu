#!/bin/bash
# ============================================================
# CCTV Proxy Installer untuk Lawangsewu
# Server CCTV: 192.168.88.200
# ============================================================

set -e

CCTV_SERVER="192.168.88.200"
CCTV_PROXY_PORT="1984"
WEBRTC_PORT="8555"

echo "=============================================="
echo " CCTV Proxy Installer - Lawangsewu"
echo "=============================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "⚠️  Please run as root: sudo $0"
    exit 1
fi

# Detect OS
if [ -f /etc/debian_version ]; then
    OS="debian"
elif [ -f /etc/redhat-release ]; then
    OS="rhel"
else
    OS="unknown"
fi

echo "📦 Installing dependencies..."

case $OS in
    debian)
        apt update
        apt install -y curl wget ffmpeg
        ;;
    rhel)
        yum install -y curl wget ffmpeg
        ;;
    *)
        echo "Unsupported OS. Trying to continue anyway..."
        ;;
esac

echo ""
echo "🐳 Setting up Docker CCTV Proxy..."

# Create directory
mkdir -p /opt/lawangsewu-cctv-proxy
cd /opt/lawangsewu-cctv-proxy

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  go2rtc:
    image: alexxit/go2rtc:latest
    container_name: lawangsewu-cctv-proxy
    restart: unless-stopped
    network_mode: host
    volumes:
      - ./go2rtc.yaml:/config/go2rtc.yaml:Z
    environment:
      - TZ=Asia/Jakarta

EOF

# Create go2rtc.yaml
cat > go2rtc.yaml << EOF
streams:
  # PA Semarang CCTV Cameras
  # Server: ${CCTV_SERVER}

  lobby_utama:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream1

  lobby_ptsp:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream2

  ruang_sidang_1:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream3

  ruang_sidang_2:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream4

  area_parkir:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream5

  pintu_masuk:
    - rtsp://admin:admin123@${CCTV_SERVER}:554/stream6

log:
  level: info

api:
  listen: ":${CCTV_PROXY_PORT}"

webrtc:
  listen: ":${WEBRTC_PORT}"
EOF

echo ""
echo "✅ Configuration created!"
echo ""
echo "To configure your camera URLs, edit: /opt/lawangsewu-cctv-proxy/go2rtc.yaml"
echo ""
echo "Starting CCTV Proxy..."
docker compose up -d

echo ""
echo "⏳ Waiting for go2rtc to start..."
sleep 3

# Check if running
if curl -s http://localhost:${CCTV_PROXY_PORT}/api/streams > /dev/null 2>&1; then
    echo "✅ CCTV Proxy is running!"
    echo ""
    echo "📡 API available at: http://localhost:${CCTV_PROXY_PORT}/api/streams"
    echo "🌐 WebRTC available at: ws://localhost:${WEBRTC_PORT}"
    echo ""
    echo "📋 Test commands:"
    echo "   curl http://localhost:${CCTV_PROXY_PORT}/api/streams"
    echo "   docker logs lawangsewu-cctv-proxy -f"
else
    echo "⚠️  CCTV Proxy may not be fully started yet."
    echo "   Check logs with: docker logs lawangsewu-cctv-proxy -f"
fi

echo ""
echo "=============================================="
echo " Installation complete!"
echo "=============================================="
