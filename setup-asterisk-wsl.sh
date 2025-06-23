#!/bin/bash

# Asterisk PBX Setup Script for WSL Ubuntu
# This script will install and configure Asterisk for Predictive Dialer

echo "🚀 Starting Asterisk PBX Setup for WSL..."

# Update system
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "📦 Installing required packages..."
sudo apt install -y \
    build-essential \
    wget \
    subversion \
    libssl-dev \
    libncurses5-dev \
    libnewt-dev \
    libxml2-dev \
    linux-headers-$(uname -r) \
    libsqlite3-dev \
    uuid-dev \
    libjansson-dev \
    libedit-dev \
    libsrtp2-dev \
    curl \
    git \
    unzip

# Create asterisk user
echo "👤 Creating asterisk user..."
sudo useradd -r -d /var/lib/asterisk -s /bin/bash asterisk
sudo usermod -aG audio,dialout asterisk

# Download and compile Asterisk
echo "📥 Downloading Asterisk 20 LTS..."
cd /usr/src
sudo wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-20-current.tar.gz
sudo tar -xzf asterisk-20-current.tar.gz
cd asterisk-20*/

# Install prerequisites
echo "🔧 Installing Asterisk prerequisites..."
sudo contrib/scripts/get_mp3_source.sh
sudo contrib/scripts/install_prereq install

# Configure build
echo "🔧 Configuring Asterisk build..."
sudo ./configure --with-pjproject-bundled --with-jansson-bundled

# Select modules (automated selection for predictive dialer)
sudo make menuselect.makeopts
sudo menuselect/menuselect \
    --enable app_queue \
    --enable app_dial \
    --enable app_originate \
    --enable chan_pjsip \
    --enable res_pjsip \
    --enable res_pjsip_session \
    --enable res_pjsip_outbound_registration \
    --enable res_pjsip_endpoint_identifier_ip \
    --enable manager \
    --enable cdr_csv \
    --enable cel_custom \
    menuselect.makeopts

# Compile and install
echo "🔨 Compiling Asterisk (this may take a while)..."
sudo make -j$(nproc)
sudo make install
sudo make samples
sudo make config
sudo ldconfig

# Set permissions
echo "🔐 Setting up permissions..."
sudo chown -R asterisk:asterisk /etc/asterisk
sudo chown -R asterisk:asterisk /var/lib/asterisk
sudo chown -R asterisk:asterisk /var/log/asterisk
sudo chown -R asterisk:asterisk /var/spool/asterisk
sudo chown -R asterisk:asterisk /usr/lib/asterisk

# Create systemd service
echo "⚙️ Creating systemd service..."
sudo tee /etc/systemd/system/asterisk.service > /dev/null <<EOF
[Unit]
Description=Asterisk PBX
After=network.target

[Service]
Type=forking
User=asterisk
Group=asterisk
ExecStart=/usr/sbin/asterisk -f -C /etc/asterisk/asterisk.conf
ExecReload=/usr/sbin/asterisk -rx 'core reload'
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable asterisk

echo "✅ Asterisk installation completed!"
echo "🔧 Now configuring Asterisk for Predictive Dialer..."

# Configure manager.conf for AMI
sudo tee /etc/asterisk/manager.conf > /dev/null <<EOF
[general]
enabled = yes
port = 5038
bindaddr = 0.0.0.0
displayconnects = yes

[admin]
secret = amp111
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
permit = 192.168.0.0/255.255.0.0
permit = 10.0.0.0/255.0.0.0
permit = 172.16.0.0/255.240.0.0
read = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,dtmf,reporting,cdr,dialplan,originate
writetimeout = 5000
EOF

# Configure pjsip.conf for SIP channels
sudo tee /etc/asterisk/pjsip.conf > /dev/null <<EOF
[global]
allow_guest=no
type=global
user_agent=Asterisk-PredictiveCall

[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060

; ======================
; REGISTRATION
; ======================
[trunk-mnd]
type=registration
outbound_auth=trunk-mnd-auth
server_uri=sip:49.128.184.138
client_uri=sip:mnd@49.128.184.138
retry_interval=60
forbidden_retry_interval=600
expiration=3600
transport=transport-udp   

; ======================
; AUTH
; ======================
[trunk-mnd-auth]
type=auth
auth_type=userpass
username=mnd
password=15$2g238!2dh3vHF2d33d

; ======================
; AOR
; ======================
[trunk-mnd-aor]
type=aor
contact=sip:49.128.184.138

; ======================
; ENDPOINT
; ======================
[trunk-mnd-endpoint]
type=endpoint
transport=transport-udp
context=predictive-outbound 
disallow=all
allow=ulaw
outbound_auth=trunk-mnd-auth
aors=trunk-mnd-aor
from_user=mnd
from_domain=49.128.184.138
t38_udptl=no
rtp_symmetric=yes
rewrite_contact=yes
force_rport=yes

; ======================
; IDENTIFY
; ======================
[trunk-mnd-identify]
type=identify
endpoint=trunk-mnd-endpoint
match=49.128.184.138

; === LOOP INTERAL 8 ACCOUNT SIP ===

[agent01]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent01-auth
aors=agent01
transport=transport-udp
direct_media=no

[agent01-auth]
type=auth
auth_type=userpass
username=agent01
password=agent01pass

[agent01]
type=aor
max_contacts=1
qualify_frequency=30

[agent02]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent02-auth
aors=agent02
transport=transport-udp
direct_media=no

[agent02-auth]
type=auth
auth_type=userpass
username=agent02
password=agent02pass

[agent02]
type=aor
max_contacts=1
qualify_frequency=30

[agent03]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent03-auth
aors=agent03
transport=transport-udp
direct_media=no

[agent03-auth]
type=auth
auth_type=userpass
username=agent03
password=agent03pass

[agent03]
type=aor
max_contacts=1
qualify_frequency=30

[agent04]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent04-auth
aors=agent04
transport=transport-udp
direct_media=no

[agent04-auth]
type=auth
auth_type=userpass
username=agent04
password=agent04pass

[agent04]
type=aor
max_contacts=1
qualify_frequency=30

[agent05]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent05-auth
aors=agent05
transport=transport-udp
direct_media=no

[agent05-auth]
type=auth
auth_type=userpass
username=agent05
password=agent05pass

[agent05]
type=aor
max_contacts=1
qualify_frequency=30

[agent06]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent06-auth
aors=agent06
transport=transport-udp
direct_media=no

[agent06-auth]
type=auth
auth_type=userpass
username=agent06
password=agent06pass

[agent06]
type=aor
max_contacts=1
qualify_frequency=30

[agent07]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent07-auth
aors=agent07
transport=transport-udp
direct_media=no

[agent07-auth]
type=auth
auth_type=userpass
username=agent07
password=agent07pass

[agent07]
type=aor
max_contacts=1
qualify_frequency=30

[agent08]
type=endpoint
context=agent-login
disallow=all
allow=ulaw,alaw
auth=agent08-auth
aors=agent08
transport=transport-udp
direct_media=no

[agent08-auth]
type=auth
auth_type=userpass
username=agent08
password=agent08pass

[agent08]
type=aor
max_contacts=1
qualify_frequency=30
EOF

# Configure extensions.conf for dialplan
sudo tee /etc/asterisk/extensions.conf > /dev/null <<EOF
[general]
static=yes
writeprotect=no
clearglobalvars=no

[globals]
CONSOLE=Console/dsp
IAXINFO=guest
TRUNK=PJSIP/trunk

[agents]
; Agent extensions
exten => 101,1,Dial(PJSIP/101,20)
exten => 101,n,Voicemail(101@default,u)
exten => 101,n,Hangup()

exten => 102,1,Dial(PJSIP/102,20)
exten => 102,n,Voicemail(102@default,u)
exten => 102,n,Hangup()

exten => 103,1,Dial(PJSIP/103,20)
exten => 103,n,Voicemail(103@default,u)
exten => 103,n,Hangup()

exten => 104,1,Dial(PJSIP/104,20)
exten => 104,n,Voicemail(104@default,u)
exten => 104,n,Hangup()

exten => 105,1,Dial(PJSIP/105,20)
exten => 105,n,Voicemail(105@default,u)
exten => 105,n,Hangup()

[from-internal]
; Internal calls between agents
exten => _1XX,1,Dial(PJSIP/\${EXTEN},20)
exten => _1XX,n,Voicemail(\${EXTEN}@default,u)
exten => _1XX,n,Hangup()

; Outbound calls
exten => _X.,1,Dial(\${TRUNK}/\${EXTEN})
exten => _X.,n,Hangup()

[predictive-dialer]
; Predictive dialer context
exten => s,1,NoOp(Predictive Dialer Call)
exten => s,n,Set(CALL_ID=\${CALL_ID})
exten => s,n,Set(CAMPAIGN_ID=\${CAMPAIGN_ID})
exten => s,n,Set(CUSTOMER_NAME=\${CUSTOMER_NAME})
exten => s,n,Set(CUSTOMER_PHONE=\${CUSTOMER_PHONE})
exten => s,n,Answer()
exten => s,n,Wait(1)
exten => s,n,Playback(beep)
exten => s,n,Queue(predictive-queue,t,,,300)
exten => s,n,Hangup()

; Handle answered calls
exten => answered,1,NoOp(Call Answered by Customer)
exten => answered,n,Set(CDR(userfield)=ANSWERED)
exten => answered,n,Queue(predictive-queue,t,,,300)
exten => answered,n,Hangup()

[from-trunk]
; Incoming calls from trunk
exten => _X.,1,Dial(PJSIP/101,20)
exten => _X.,n,Voicemail(101@default,u)
exten => _X.,n,Hangup()

[default]
; Default context
include => agents
include => from-internal
EOF

# Configure queues.conf
sudo tee /etc/asterisk/queues.conf > /dev/null <<EOF
[general]
persistentmembers = yes
autofill = yes
monitor-type = MixMonitor

[predictive-queue]
strategy = rrmemory
timeout = 30
retry = 5
maxlen = 0
announce-frequency = 0
announce-holdtime = no
announce-position = no
joinempty = yes
leavewhenempty = no
ringinuse = no
EOF

# Configure asterisk.conf
sudo tee /etc/asterisk/asterisk.conf > /dev/null <<EOF
[directories](!)
astetcdir => /etc/asterisk
astmoddir => /usr/lib/asterisk/modules
astvarlibdir => /var/lib/asterisk
astdbdir => /var/lib/asterisk
astkeydir => /var/lib/asterisk
astdatadir => /var/lib/asterisk
astagidir => /var/lib/asterisk/agi-bin
astspooldir => /var/spool/asterisk
astrundir => /var/run/asterisk
astlogdir => /var/log/asterisk
astsbindir => /usr/sbin

[options]
verbose = 3
debug = 3
alwaysfork = yes
nofork = no
quiet = no
timestamp = yes
execincludes = yes
console = yes
highpriority = yes
initcrypto = yes
nocolor = no
dontwarn = no
dumpcore = no
languageprefix = yes
systemname = asterisk-predictive
autosystemname = no
mindtmfduration = 80
maxcalls = 1000
maxload = 0.9
maxfiles = 1000
minmemfree = 1
cache_record_files = yes
record_cache_dir = /tmp
transmit_silence = yes
transcode_via_sln = yes
runuser = asterisk
rungroup = asterisk
EOF

# Set proper ownership
sudo chown -R asterisk:asterisk /etc/asterisk/

# Start Asterisk
echo "🚀 Starting Asterisk..."
sudo systemctl start asterisk

# Check status
echo "📊 Checking Asterisk status..."
sudo systemctl status asterisk --no-pager

echo ""
echo "✅ Asterisk PBX setup completed!"
echo ""
echo "📋 Configuration Summary:"
echo "  - AMI Port: 5038"
echo "  - AMI Username: admin"
echo "  - AMI Password: amp111"
echo "  - SIP Port: 5060"
echo "  - Agent Extensions: 101-105"
echo "  - Agent Password: password123"
echo ""
echo "🔧 Next Steps:"
echo "  1. Configure your SIP trunk in /etc/asterisk/pjsip.conf"
echo "  2. Update Laravel .env with Asterisk settings"
echo "  3. Test AMI connection: telnet localhost 5038"
echo "  4. Configure SIP clients for agents"
echo ""
echo "📱 SIP Client Settings for Agents:"
echo "  - Server: $(hostname -I | awk '{print $1}')"
echo "  - Port: 5060"
echo "  - Username: 101-105"
echo "  - Password: password123"
echo ""
echo "🔍 Useful Commands:"
echo "  - Check Asterisk CLI: sudo asterisk -r"
echo "  - Restart Asterisk: sudo systemctl restart asterisk"
echo "  - View logs: sudo tail -f /var/log/asterisk/messages"
echo "  - Test AMI: telnet localhost 5038"
EOF

chmod +x setup-asterisk-wsl.sh