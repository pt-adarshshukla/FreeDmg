#!/bin/bash
# ==============================================================================
# FreeDmg - Local & LAN Server Launcher
# Binds to 0.0.0.0 to enable access from any device on your Wi-Fi/Network
# ==============================================================================

PORT=${1:-8000}
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

# Detect Primary LAN IP Address
LAN_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || ifconfig | grep "inet " | grep -v 127.0.0.1 | head -n 1 | awk '{print $2}')
if [ -z "$LAN_IP" ]; then
    LAN_IP="127.0.0.1"
fi

echo "================================================================================"
echo "                   🚀 FreeDmg Local & LAN Server is LIVE                        "
echo "================================================================================"
echo ""
echo "  💻 On this Mac (Localhost):"
echo "     👉 http://localhost:$PORT"
echo "     👉 http://127.0.0.1:$PORT"
echo ""
echo "  📱 On Other Devices (iPhone, Android, iPad, PC on same Wi-Fi):"
echo "     👉 http://$LAN_IP:$PORT"
echo ""
echo "  🔐 Admin Portal:"
echo "     👉 http://$LAN_IP:$PORT/admin/login.php"
echo "     • Username : FreeDmg"
echo "     • Password : freedmg@2007"
echo ""
echo "  ⚙️  Config: 0.0.0.0:$PORT | PHP $(php -r 'echo PHP_VERSION;') | SQLite"
echo "  💡 Press Ctrl+C anytime to stop the server."
echo "================================================================================"
echo ""

# Start PHP built-in server with router.php and custom php.ini configuration
exec php -c php.ini -S "0.0.0.0:$PORT" router.php
